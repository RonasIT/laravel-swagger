<?php

namespace RonasIT\AutoDoc\Services;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use ReflectionClass;
use RonasIT\AutoDoc\Contracts\SwaggerDriverContract;
use RonasIT\AutoDoc\Exceptions\DocFileNotExistsException;
use RonasIT\AutoDoc\Exceptions\EmptyContactEmailException;
use RonasIT\AutoDoc\Exceptions\EmptyDocFileException;
use RonasIT\AutoDoc\Exceptions\InvalidDriverClassException;
use RonasIT\AutoDoc\Exceptions\LegacyConfigException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\InvalidSwaggerSpecException;
use RonasIT\AutoDoc\Exceptions\SwaggerDriverClassNotFoundException;
use RonasIT\AutoDoc\Exceptions\UnsupportedDocumentationViewerException;
use RonasIT\AutoDoc\Exceptions\WrongSecurityConfigException;
use RonasIT\AutoDoc\Traits\GetDependenciesTrait;
use RonasIT\AutoDoc\Validators\SwaggerSpecValidator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Exception;
/**
 * @property SwaggerDriverContract $driver
 */
class SwaggerService
{
    use GetDependenciesTrait;

    public const string OPEN_API_VERSION = '3.1.0';

    protected $driver;
    protected $openAPIValidator;

    protected $data;
    protected $config;
    protected $container;
    private $uri;
    private $method;
    /**
     * @var Request
     */
    private $request;
    private $item;
    private $security;

    protected array $ruleToTypeMap = [
        'array' => 'object',
        'boolean' => 'boolean',
        'date' => 'date',
        'digits' => 'integer',
        'integer' => 'integer',
        'numeric' => 'double',
        'string' => 'string',
        'int' => 'integer',
    ];

    protected $booleanAnnotations = [
        'deprecated',
    ];

    public function __construct(Container $container)
    {
        $this->openAPIValidator = app(SwaggerSpecValidator::class);

        $this->initConfig();

        $this->setDriver();

        if (config('app.env') === 'testing') {
            $this->container = $container;

            $this->security = $this->config['security'];

            $this->data = $this->driver->getProcessTmpData();

            if (empty($this->data)) {
                $this->data = $this->generateEmptyData();

                $this->driver->saveProcessTmpData($this->data);
            }
        }
    }

    protected function initConfig()
    {
        $this->config = config('auto-doc');

        $version = Arr::get($this->config, 'config_version');

        if (empty($version)) {
            throw new LegacyConfigException();
        }

        $packageConfigs = require __DIR__ . '/../../config/auto-doc.php';

        if (version_compare($packageConfigs['config_version'], $version, '>')) {
            throw new LegacyConfigException();
        }

        $documentationViewer = (string) Arr::get($this->config, 'documentation_viewer');

        if (!view()->exists("auto-doc::documentation-{$documentationViewer}")) {
            throw new UnsupportedDocumentationViewerException($documentationViewer);
        }

        $securityDriver = Arr::get($this->config, 'security');

        if ($securityDriver && !array_key_exists($securityDriver, Arr::get($this->config, 'security_drivers'))) {
            throw new WrongSecurityConfigException();
        }
    }

    protected function setDriver()
    {
        $driver = $this->config['driver'];
        $className = Arr::get($this->config, "drivers.{$driver}.class");

        if (!class_exists($className)) {
            throw new SwaggerDriverClassNotFoundException($className);
        } else {
            $this->driver = app($className);
        }

        if (!$this->driver instanceof SwaggerDriverContract) {
            throw new InvalidDriverClassException($driver);
        }
    }

    protected function generateEmptyData(?string $view = null, array $viewData = [], array $license = []): array
    {
        // client must enter at least `contact.email` to generate a default `info` block
        // otherwise an exception will be called
        $this->checkEmail();

        if (empty($view) && !empty($this->config['info'])) {
            $view = $this->config['info']['description'];
        }

        $data = $this->prepareEmptyData($view, $viewData, $license);

        $securityDefinitions = $this->generateSecurityDefinition();

        if (!empty($securityDefinitions)) {
            $data['securityDefinitions'] = $securityDefinitions;
        }

        return $data;
    }

    protected function checkEmail(): void
    {
        if (!empty($this->config['info']) && !Arr::get($this->config, 'info.contact.email')) {
            throw new EmptyContactEmailException();
        }
    }

    protected function prepareEmptyData(?string $view = null, array $viewData = [], array $license = []): array
    {
        return [
            'openapi' => self::OPEN_API_VERSION,
            'servers' => [
                ['url' => URL::query($this->config['basePath'])],
            ],
            'paths' => [],
            'components' => [
                'schemas' => $this->config['definitions'],
            ],
            'info' => $this->prepareInfo($view, $viewData, $license),
        ];
    }

    protected function generateSecurityDefinition(): ?array
    {
        if (empty($this->security)) {
            return null;
        }

        return [
            $this->security => $this->generateSecurityDefinitionObject($this->security)
        ];
    }

    protected function generateSecurityDefinitionObject($type): array
    {
        return [
            'type' => $this->config['security_drivers'][$type]['type'],
            'name' => $this->config['security_drivers'][$type]['name'],
            'in' => $this->config['security_drivers'][$type]['in']
        ];
    }

    public function addData(Request $request, $response)
    {
        $this->request = $request;

        $this->prepareItem();

        $this->parseRequest();
        $this->parseResponse($response);

        $this->driver->saveProcessTmpData($this->data);
    }

    protected function prepareItem()
    {
        $this->uri = "/{$this->getUri()}";
        $this->method = strtolower($this->request->getMethod());

        if (empty(Arr::get($this->data, "paths.{$this->uri}.{$this->method}"))) {
            $this->data['paths'][$this->uri][$this->method] = [
                'tags' => [],
                'consumes' => [],
                'produces' => [],
                'parameters' => $this->getPathParams(),
                'responses' => [],
                'security' => [],
                'description' => ''
            ];
        }

        $this->item = &$this->data['paths'][$this->uri][$this->method];
    }

    protected function getUri()
    {
        $uri = $this->request->route()->uri();
        $basePath = preg_replace("/^\//", '', $this->config['basePath']);
        $preparedUri = preg_replace("/^{$basePath}/", '', $uri);

        return preg_replace("/^\//", '', $preparedUri);
    }

    protected function getPathParams(): array
    {
        $params = [];

        preg_match_all('/{.*?}/', $this->uri, $params);

        $params = Arr::collapse($params);

        $result = [];

        foreach ($params as $param) {
            $key = preg_replace('/[{}]/', '', $param);

            $result[] = [
                'in' => 'path',
                'name' => $key,
                'description' => $this->generatePathDescription($key),
                'required' => true,
                'schema' => [
                    'type' => 'string'
                ]
            ];
        }

        return $result;
    }

    protected function generatePathDescription(string $key): string
    {
        $expression = Arr::get($this->request->route()->wheres, $key);

        if (empty($expression)) {
            return '';
        }

        $exploded = explode('|', $expression);

        foreach ($exploded as $value) {
            if (!preg_match('/^[a-zA-Z0-9\.]+$/', $value)) {
                return "regexp: {$expression}";
            }
        }

        return 'in: ' . implode(',', $exploded);
    }

    protected function parseRequest()
    {
        $this->saveConsume();
        $this->saveTags();
        $this->saveSecurity();

        $concreteRequest = $this->getConcreteRequest();

        if (empty($concreteRequest)) {
            $this->item['description'] = '';

            return;
        }

        $annotations = $this->getClassAnnotations($concreteRequest);

        $this->markAsDeprecated($annotations);
        $this->saveParameters($concreteRequest, $annotations);
        $this->saveDescription($concreteRequest, $annotations);
    }

    protected function markAsDeprecated(array $annotations)
    {
        $this->item['deprecated'] = Arr::get($annotations, 'deprecated', false);
    }

    protected function saveResponseSchema(?array $content, string $definition): void
    {
        $schemaProperties = [];
        $schemaType = 'object';

        if (!empty($content) && array_is_list($content)) {
            $this->saveListResponseDefinitions($content, $schemaProperties);

            $schemaType = 'array';
        } else {
            $this->saveObjectResponseDefinitions($content, $schemaProperties, $definition);
        }

        $this->data['components']['schemas'][$definition] = [
            'type' => $schemaType,
            'properties' => $schemaProperties
        ];
    }

    protected function saveListResponseDefinitions(array $content, array &$schemaProperties): void
    {
        $types = [];

        foreach ($content as $value) {
            $type = gettype($value);

            if (!in_array($type, $types)) {
                $types[] = $type;
                $schemaProperties['items']['allOf'][]['type'] = $type;
            }
        }
    }

    protected function saveObjectResponseDefinitions(array $content, array &$schemaProperties, string $definition): void
    {
        $properties = Arr::get($this->data, "components.schemas.{$definition}", []);

        foreach ($content as $name => $value) {
            $property = Arr::get($properties, "properties.{$name}", []);

            if (is_null($value)) {
                $property['nullable'] = true;
            } else {
                $property['type'] = gettype($value);
            }

            $schemaProperties[$name] = $property;
        }
    }

    protected function parseResponse($response)
    {
        $produceList = $this->data['paths'][$this->uri][$this->method]['produces'];

        $produce = $response->headers->get('Content-type');

        if (is_null($produce)) {
            $produce = 'text/plain';
        }

        if (!in_array($produce, $produceList)) {
            $this->item['produces'][] = $produce;
        }

        $responses = $this->item['responses'];

        $responseExampleLimitCount = config('auto-doc.response_example_limit_count');

        $content = json_decode($response->getContent(), true) ?? [];

        if (!empty($responseExampleLimitCount)) {
            if (!empty($content['data'])) {
                $limitedResponseData = array_slice($content['data'], 0, $responseExampleLimitCount, true);
                $content['data'] = $limitedResponseData;
                $content['to'] = count($limitedResponseData);
                $content['total'] = count($limitedResponseData);
            }
        }

        if (!empty($content['exception'])) {
            $uselessKeys = array_keys(Arr::except($content, ['message']));

            $content = Arr::except($content, $uselessKeys);
        }

        $code = $response->getStatusCode();

        if (!in_array($code, $responses)) {
            $this->saveExample(
                $code,
                json_encode($content, JSON_PRETTY_PRINT),
                $produce
            );
        }

        $action = Str::ucfirst($this->getActionName($this->uri));
        $definition = "{$this->method}{$action}{$code}ResponseObject";

        $this->saveResponseSchema($content, $definition);

        if (is_array($this->item['responses'][$code])) {
            $this->item['responses'][$code]['content'][$produce]['schema']['$ref'] = "#/components/schemas/{$definition}";
        }
    }

    protected function saveExample($code, $content, $produce)
    {
        $description = $this->getResponseDescription($code);
        $availableContentTypes = [
            'application',
            'text',
            'image',
        ];
        $explodedContentType = explode('/', $produce);

        if (in_array($explodedContentType[0], $availableContentTypes)) {
            $this->item['responses'][$code] = $this->makeResponseExample($content, $produce, $description);
        } else {
            $this->item['responses'][$code] = '*Unavailable for preview*';
        }
    }

    protected function makeResponseExample($content, $mimeType, $description = ''): array
    {
        $example = match ($mimeType) {
            'application/json' => json_decode($content, true),
            'application/pdf' => base64_encode($content),
            default => $content,
        };

        return [
            'description' => $description,
            'content' => [
                $mimeType => [
                    'schema' => [
                        'type' => 'object',
                    ],
                    'example' => $example,
                ],
            ],
        ];
    }

    protected function saveParameters($request, array $annotations)
    {
        $formRequest = new $request();
        $formRequest->setUserResolver($this->request->getUserResolver());
        $formRequest->setRouteResolver($this->request->getRouteResolver());
        $rules = method_exists($formRequest, 'rules') ? $this->prepareRules($formRequest->rules()) : [];
        $attributes = method_exists($formRequest, 'attributes') ? $formRequest->attributes() : [];

        $actionName = $this->getActionName($this->uri);

        if (in_array($this->method, ['get', 'delete'])) {
            $this->saveGetRequestParameters($rules, $attributes, $annotations);
        } else {
            $this->savePostRequestParameters($actionName, $rules, $attributes, $annotations);
        }
    }

    protected function prepareRules(array $rules): array
    {
        $preparedRules = [];

        foreach ($rules as $field => $rulesField) {
            if (is_array($rulesField)) {
                $rulesField = array_map(function ($rule) {
                    return $this->getRuleAsString($rule);
                }, $rulesField);

                $preparedRules[$field] = implode('|', $rulesField);
            } else {
                $preparedRules[$field] = $this->getRuleAsString($rulesField);
            }
        }

        return $preparedRules;
    }

    protected function getRuleAsString($rule): string
    {
        if (is_object($rule)) {
            if (method_exists($rule, '__toString')) {
                return $rule->__toString();
            }

            $shortName = Str::afterLast(get_class($rule), '\\');

            $ruleName = preg_replace('/Rule$/', '', $shortName);

            return Str::snake($ruleName);
        }

        return $rule;
    }

    protected function saveGetRequestParameters($rules, array $attributes, array $annotations)
    {
        foreach ($rules as $parameter => $rule) {
            $validation = explode('|', $rule);

            $description = Arr::get($annotations, $parameter);

            if (empty($description)) {
                $description = Arr::get($attributes, $parameter, implode(', ', $validation));
            }

            $existedParameter = Arr::first($this->item['parameters'], function ($existedParameter) use ($parameter) {
                return $existedParameter['name'] === $parameter;
            });

            if (empty($existedParameter)) {
                $parameterDefinition = [
                    'in' => 'query',
                    'name' => $parameter,
                    'description' => $description,
                    'schema' => [
                        'type' => $this->getParameterType($validation),
                    ],
                ];
                if (in_array('required', $validation)) {
                    $parameterDefinition['required'] = true;
                }

                $this->item['parameters'][] = $parameterDefinition;
            }
        }
    }

    protected function savePostRequestParameters($actionName, $rules, array $attributes, array $annotations)
    {
        if ($this->requestHasMoreProperties($actionName)) {
            if ($this->requestHasBody()) {
                $type = $this->request->header('Content-Type', 'application/json');

                $this->item['requestBody'] = [
                    'content' => [
                        $type => [
                            'schema' => [
                                '$ref' => "#/components/schemas/{$actionName}Object",
                            ],
                        ],
                    ],
                    'description' => '',
                    'required' => true,
                ];
            }

            $this->saveDefinitions($actionName, $rules, $attributes, $annotations);
        }
    }

    protected function saveDefinitions($objectName, $rules, $attributes, array $annotations)
    {
        $data = [
            'type' => 'object',
            'properties' => []
        ];

        foreach ($rules as $parameter => $rule) {
            $rulesArray = (is_array($rule)) ? $rule : explode('|', $rule);
            $parameterType = $this->getParameterType($rulesArray);
            $this->saveParameterType($data, $parameter, $parameterType);

            $uselessRules = $this->ruleToTypeMap;
            $uselessRules['required'] = 'required';

            if (in_array('required', $rulesArray)) {
                $data['required'][] = $parameter;
            }

            $rulesArray = array_flip(array_diff_key(array_flip($rulesArray), $uselessRules));

            $this->saveParameterDescription($data, $parameter, $rulesArray, $attributes, $annotations);
        }

        $data['example'] = $this->generateExample($data['properties']);
        $this->data['components']['schemas']["{$objectName}Object"] = $data;
    }

    protected function getParameterType(array $validation): string
    {
        $validationRules = $this->ruleToTypeMap;
        $validationRules['email'] = 'string';

        $parameterType = 'string';

        foreach ($validation as $item) {
            if (in_array($item, array_keys($validationRules))) {
                return $validationRules[$item];
            }
        }

        return $parameterType;
    }

    protected function saveParameterType(&$data, $parameter, $parameterType)
    {
        $data['properties'][$parameter] = [
            'type' => $parameterType
        ];
    }

    protected function saveParameterDescription(
        array &$data,
        string $parameter,
        array $rulesArray,
        array $attributes,
        array $annotations
    ) {
        $description = Arr::get($annotations, $parameter);

        if (empty($description)) {
            $description = Arr::get($attributes, $parameter, implode(', ', $rulesArray));
        }

        $data['properties'][$parameter]['description'] = $description;
    }

    protected function requestHasMoreProperties($actionName): bool
    {
        $requestParametersCount = count($this->request->all());

        $properties = Arr::get($this->data, "components.schemas.{$actionName}Object.properties", []);
        $objectParametersCount = count($properties);

        return $requestParametersCount > $objectParametersCount;
    }

    protected function requestHasBody(): bool
    {
        $parameters = $this->data['paths'][$this->uri][$this->method]['parameters'];

        $bodyParamExisted = Arr::where($parameters, function ($value) {
            return $value['name'] === 'body';
        });

        return empty($bodyParamExisted);
    }

    public function getConcreteRequest()
    {
        $controller = $this->request->route()->getActionName();

        if ($controller === 'Closure') {
            return null;
        }

        $explodedController = explode('@', $controller);

        $class = $explodedController[0];
        $method = $explodedController[1];

        if (!method_exists($class, $method)) {
            return null;
        }

        $parameters = $this->resolveClassMethodDependencies(
            app($class),
            $method
        );

        return Arr::first($parameters, function ($key) {
            return preg_match('/Request/', $key);
        });
    }

    public function saveConsume()
    {
        $consumeList = $this->data['paths'][$this->uri][$this->method]['consumes'];
        $consume = $this->request->header('Content-Type');

        if (!empty($consume) && !in_array($consume, $consumeList)) {
            $this->item['consumes'][] = $consume;
        }
    }

    public function saveTags()
    {
        $globalPrefix = config('auto-doc.global_prefix');
        $globalPrefix = Str::after($globalPrefix, '/');

        $explodedUri = explode('/', $this->uri);
        $explodedUri = array_filter($explodedUri);

        $tag = array_shift($explodedUri);

        if ($globalPrefix === $tag) {
            $tag = array_shift($explodedUri);
        }

        $this->item['tags'] = [$tag];
    }

    public function saveDescription($request, array $annotations)
    {
        $this->item['summary'] = $this->getSummary($request, $annotations);

        $description = Arr::get($annotations, 'description');

        if (!empty($description)) {
            $this->item['description'] = $description;
        }
    }

    protected function saveSecurity()
    {
        if ($this->requestSupportAuth()) {
            $this->addSecurityToOperation();
        }
    }

    protected function addSecurityToOperation()
    {
        $security = &$this->data['paths'][$this->uri][$this->method]['security'];

        if (empty($security)) {
            $security[] = [
                "{$this->security}" => []
            ];
        }
    }

    protected function getSummary($request, array $annotations)
    {
        $summary = Arr::get($annotations, 'summary');

        if (empty($summary)) {
            $summary = $this->parseRequestName($request);
        }

        return $summary;
    }

    protected function requestSupportAuth(): bool
    {
        $security = Arr::get($this->config, 'security');
        $securityDriver = Arr::get($this->config, "security_drivers.{$security}");

        switch (Arr::get($securityDriver, 'in')) {
            case 'header':
                // TODO Change this logic after migration on Swagger 3.0
                // Swagger 2.0 does not support cookie authorization.
                $securityToken = $this->request->hasHeader($securityDriver['name'])
                    ? $this->request->header($securityDriver['name'])
                    : $this->request->cookie($securityDriver['name']);

                break;
            case 'query':
                $securityToken = $this->request->query($securityDriver['name']);

                break;
            default:
                $securityToken = null;
        }

        return !empty($securityToken);
    }

    protected function parseRequestName($request)
    {
        $explodedRequest = explode('\\', $request);
        $requestName = array_pop($explodedRequest);
        $summaryName = str_replace('Request', '', $requestName);

        $underscoreRequestName = $this->camelCaseToUnderScore($summaryName);

        return preg_replace('/[_]/', ' ', $underscoreRequestName);
    }

    protected function getResponseDescription($code)
    {
        $defaultDescription = Response::$statusTexts[$code];

        $request = $this->getConcreteRequest();

        if (empty($request)) {
            return $defaultDescription;
        }

        $annotations = $this->getClassAnnotations($request);

        $localDescription = Arr::get($annotations, "_{$code}");

        if (!empty($localDescription)) {
            return $localDescription;
        }

        return Arr::get($this->config, "defaults.code-descriptions.{$code}", $defaultDescription);
    }

    protected function getActionName($uri): string
    {
        $action = preg_replace('[\/]', '', $uri);

        return Str::camel($action);
    }

    public function saveProductionData()
    {
        if (ParallelTesting::token()) {
            $this->driver->appendProcessDataToTmpFile(function (array $sharedTmpData) {
                $resultDocContent = (empty($sharedTmpData))
                    ? $this->generateEmptyData($this->config['info']['description'])
                    : $sharedTmpData;

                $this->mergeOpenAPIDocs($resultDocContent, $this->data);

                return $resultDocContent;
            });
        }

        $this->driver->saveData();
    }

    public function getDocFileContent()
    {
        try {
            $this->checkEmail();

            $documentation = $this->driver->getDocumentation();

            $this->openAPIValidator->validate($documentation);
        } catch (EmptyContactEmailException $exception) {
            return $this->prepareEmptyData(
                $this->config['defaults']['error'],
                [
                    'message' => $exception->getMessage(),
                    'type' => $exception::class,
                ],
            );
        } catch (Throwable $exception) {
            $message = ($exception instanceof Exception)
                ? $exception->getMessage()
                : __('validation.unhandled_error_message');

            return $this->generateEmptyData(
                $this->config['defaults']['error'],
                [
                    'message' => $message,
                    'type' => $exception::class,
                ]
            );
        }

        $additionalDocs = config('auto-doc.additional_paths', []);

        foreach ($additionalDocs as $filePath) {
            try {
                $additionalDocContent = $this->getOpenAPIFileContent(base_path($filePath));
            } catch (DocFileNotExistsException|EmptyDocFileException|InvalidSwaggerSpecException $exception) {
                report($exception);

                continue;
            }

            $this->mergeOpenAPIDocs($documentation, $additionalDocContent);
        }

        return $documentation;
    }

    protected function camelCaseToUnderScore($input): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];

        foreach ($ret as &$match) {
            $match = ($match === strtoupper($match)) ? strtolower($match) : lcfirst($match);
        }

        return implode('_', $ret);
    }

    protected function generateExample($properties): array
    {
        $parameters = $this->replaceObjectValues($this->request->all());
        $example = [];

        $this->replaceNullValues($parameters, $properties, $example);

        return $example;
    }

    protected function replaceObjectValues($parameters): array
    {
        $classNamesValues = [
            File::class => '[uploaded_file]',
        ];

        $parameters = Arr::dot($parameters);
        $returnParameters = [];

        foreach ($parameters as $parameter => $value) {
            if (is_object($value)) {
                $class = get_class($value);

                $value = Arr::get($classNamesValues, $class, $class);
            }

            Arr::set($returnParameters, $parameter, $value);
        }

        return $returnParameters;
    }

    protected function getClassAnnotations($class): array
    {
        $reflection = new ReflectionClass($class);

        $annotations = $reflection->getDocComment();

        $annotations = Str::of($annotations)->remove("\r");

        $blocks = explode("\n", $annotations);

        $result = [];

        foreach ($blocks as $block) {
            if (Str::contains($block, '@')) {
                $index = strpos($block, '@');
                $block = substr($block, $index);
                $exploded = explode(' ', $block);

                $paramName = str_replace('@', '', array_shift($exploded));
                $paramValue = implode(' ', $exploded);

                if (in_array($paramName, $this->booleanAnnotations)) {
                    $paramValue = true;
                }

                $result[$paramName] = $paramValue;
            }
        }

        return $result;
    }

    /**
     * NOTE: All functions below are temporary solution for
     * this issue: https://github.com/OAI/OpenAPI-Specification/issues/229
     * We hope swagger developers will resolve this problem in next release of Swagger OpenAPI
     * */
    protected function replaceNullValues($parameters, $types, &$example)
    {
        foreach ($parameters as $parameter => $value) {
            if (is_null($value) && array_key_exists($parameter, $types)) {
                $example[$parameter] = $this->getDefaultValueByType($types[$parameter]['type']);
            } elseif (is_array($value)) {
                $this->replaceNullValues($value, $types, $example[$parameter]);
            } else {
                $example[$parameter] = $value;
            }
        }
    }

    protected function getDefaultValueByType($type)
    {
        $values = [
            'object' => 'null',
            'boolean' => false,
            'date' => "0000-00-00",
            'integer' => 0,
            'string' => '',
            'double' => 0
        ];

        return $values[$type];
    }

    protected function prepareInfo(?string $view = null, array $viewData = [], array $license = []): array
    {
        $info = [];

        $license = array_filter($license);

        if (!empty($license)) {
            $info['license'] = $license;
        }

        if (!empty($view)) {
            $info['description'] = view($view, $viewData)->render();
        }

        return array_merge($this->config['info'], $info);
    }

    protected function getOpenAPIFileContent(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new DocFileNotExistsException($filePath);
        }

        $fileContent = json_decode(file_get_contents($filePath), true);

        if (empty($fileContent)) {
            throw new EmptyDocFileException($filePath);
        }

        $this->openAPIValidator->validate($fileContent);

        return $fileContent;
    }

    protected function mergeOpenAPIDocs(array &$documentation, array $additionalDocumentation): void
    {
        $paths = array_keys($additionalDocumentation['paths']);

        foreach ($paths as $path) {
            $additionalDocPath = $additionalDocumentation['paths'][$path];

            if (empty($documentation['paths'][$path])) {
                $documentation['paths'][$path] = $additionalDocPath;
            } else {
                $methods = array_keys($documentation['paths'][$path]);
                $additionalDocMethods = array_keys($additionalDocPath);

                foreach ($additionalDocMethods as $method) {
                    if (!in_array($method, $methods)) {
                        $documentation['paths'][$path][$method] = $additionalDocPath[$method];
                    }
                }
            }
        }

        $definitions = array_keys($additionalDocumentation['components']['schemas']);

        foreach ($definitions as $definition) {
            $documentation = Arr::add(
                array: $documentation,
                key: "components.schemas.{$definition}",
                value: $additionalDocumentation['components']['schemas'][$definition],
            );
        }
    }
}
