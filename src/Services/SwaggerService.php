<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 26.08.16
 * Time: 13:09
 */

namespace RonasIT\Support\AutoDoc\Services;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Minime\Annotations\Reader as AnnotationReader;
use Minime\Annotations\Parser;
use Minime\Annotations\Cache\ArrayCache;
use RonasIT\Support\AutoDoc\Interfaces\DataCollectorInterface;
use RonasIT\Support\AutoDoc\Traits\GetDependenciesTrait;
use RonasIT\Support\AutoDoc\Exceptions\WrongSecurityConfigException;
use RonasIT\Support\AutoDoc\Exceptions\DataCollectorClassNotFoundException;
use RonasIT\Support\DataCollectors\LocalDataCollector;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property DataCollectorInterface $dataCollector
*/
class SwaggerService
{
    use GetDependenciesTrait;

    protected $annotationReader;
    protected $dataCollector;

    protected $data;
    protected $container;
    private $uri;
    private $method;
    private $request;
    private $item;
    private $security;

    public function __construct(Container $container)
    {
        $this->setDataCollector();

        if (config('app.env')) {
            $this->container = $container;

            $this->annotationReader = new AnnotationReader(new Parser, new ArrayCache);;

            $this->security = config('auto-doc.security');

            $this->data = $this->dataCollector->getTmpData();

            if (empty($this->data)) {
                $this->data = $this->generateEmptyData();

                $this->dataCollector->saveTmpData($this->data);
            }
        }
    }

    protected function setDataCollector() {
        $dataCollectorClass = config('auto-doc.data_collector');

        if (empty($dataCollectorClass)) {
            $this->dataCollector = app(LocalDataCollector::class);
        } elseif (!class_exists($dataCollectorClass)) {
            throw new DataCollectorClassNotFoundException();
        } else {
            $this->dataCollector = app($dataCollectorClass);
        }
    }

    protected function generateEmptyData() {
        $data = [
            'swagger' => config('auto-doc.swagger.version'),
            'info' => config('auto-doc.info'),
            'host' => $this->getAppUrl(),
            'basePath' => config('auto-doc.basePath'),
            'schemes' => config('auto-doc.schemes'),
            'paths' => [],
            'securityDefinitions' => $this->generateSecurityDefinition(),
            'definitions' => config('auto-doc.definitions')
        ];

        $data['info']['description'] = view($data['info']['description'])->render();

        return $data;
    }

    protected function getAppUrl() {
        $url = config('app.url');

        return str_replace(['http://', 'https://', '/'], '', $url);
    }

    protected function generateSecurityDefinition() {
        $availableTypes = ['jwt', 'laravel'];
        $security = $this->security;

        if (empty($security)) {
            return '';
        }

        if (!in_array($security, $availableTypes)) {
            throw new WrongSecurityConfigException();
        }

        return [
            $security => $this->generateSecurityDefinitionObject($security)
        ];
    }

    protected function generateSecurityDefinitionObject($type) {
        switch ($type) {
            case 'jwt':
                return [
                    "type" => "apiKey",
                    "name" => "authorization",
                    "in" => "header"
                ];

            case 'laravel':
                return [
                    "type" => "apiKey",
                    "name" => "Cookie",
                    "in" => "header"
                ];
        }
    }

    public function addData($request, $response) {
        $this->request = $request;

        if (empty($this->request->route())) {
            return;
        }

        $this->prepareItem();

        $this->parseRequest();
        $this->parseResponse($response);

        $this->dataCollector->saveTmpData($this->data);
    }

    protected function prepareItem() {
        $this->uri = "/{$this->getUri()}";
        $this->method = strtolower($this->request->getMethod());

        if (empty(array_get($this->data, "paths.{$this->uri}.{$this->method}"))) {
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

    protected function getUri() {
        $uri = $this->request->route()->uri();
        $basePath = preg_replace("/^\//", '', config('auto-doc.basePath'));
        $preparedUri = preg_replace("/^{$basePath}/", '', $uri);

        return preg_replace("/^\//", '', $preparedUri);
    }

    protected function getPathParams() {
        $params = [];

        preg_match_all('/{.*?}/', $this->uri, $params);

        $params = array_collapse($params);

        $result = [];

        foreach ($params as $param) {
            $key = preg_replace('/[{}]/', '', $param);

            $result[] = [
                'in' => 'path',
                'name' => $key,
                'description' => '',
                'required' => true,
                'type' => 'string'
            ];
        }

        return $result;
    }

    protected function parseRequest() {
        $this->saveConsume();
        $this->saveTags();
        $this->saveParameters();
        $this->saveDescription();
        $this->saveSecurity();
    }

    protected function parseResponse($response) {
        $produceList = $this->data['paths'][$this->uri][$this->method]['produces'];

        $produce = $response->headers->get('Content-type');
        if (is_null($produce)) {
            $produce = 'text/plain';
        }

        if (!in_array($produce, $produceList)) {
            $this->item['produces'][] = $produce;
        }

        $responses = $this->item['responses'];
        $code = $response->getStatusCode();

        if (!in_array($code, $responses)) {
            $this->saveExample(
                $response->getStatusCode(),
                $response->getContent(),
                $produce
            );
        }
    }

    protected function saveExample($code, $content, $produce) {
        $description = $this->getResponseDescription($code);
        $availableContentTypes = [
            'application',
            'text'
        ];
        $explodedContentType = explode('/', $produce);

        if (in_array($explodedContentType[0], $availableContentTypes)) {
            $this->item['responses'][$code] = $this->makeResponseExample($content, $produce, $description);
        } else {
            $this->item['responses'][$code] = '*Unavailable for preview*';
        }
    }

    protected function makeResponseExample($content, $mimeType, $description = '')
    {
        $responseExample = [
            'description' => $description
        ];

        if ($mimeType === 'application/json') {
            $responseExample['schema'] = [
                'example' => json_decode($content, true),
            ];
        } else {
            $responseExample['examples']['example'] = $content;
        }

        return $responseExample;
    }

    protected function saveParameters() {
        $request = $this->getConcreteRequest();

        if (empty($request)) {
            return;
        }

        $annotations = $this->annotationReader->getClassAnnotations($request);
        $rules = $request::getRules();
        $actionName = $this->getActionName($this->uri);

        if (in_array($this->method, ["get", "delete"])) {
            $this->saveGetRequestParameters($rules, $annotations);
        } else {
            $this->savePostRequestParameters($actionName, $rules, $annotations);
        }
    }

    protected function saveGetRequestParameters($rules, $annotations) {
        foreach ($rules as $parameter => $rule) {
            $validation = explode('|', $rule);

            $description = $annotations->get($parameter, implode(', ', $validation));

            $existedParameter = array_first($this->item['parameters'], function ($existedParameter, $key) use ($parameter) {
                return $existedParameter['name'] == $parameter;
            });

            if (empty($existedParameter)) {
                $this->item['parameters'][] = [
                    'in' => 'query',
                    'name' => $parameter,
                    'description' => $description,
                    'required' => in_array('required', $validation),
                    'type' => implode(', ', $validation)
                ];
            }
        }
    }

    protected function savePostRequestParameters($actionName, $rules, $annotations) {
        if ($this->requestHasMoreProperties($actionName)) {
            if ($this->requestHasBody()) {
                $this->item['parameters'][] = [
                    'in' => 'body',
                    'name' => "body",
                    'description' => "",
                    'required' => true,
                    'schema' => [
                        "\$ref" => "#/definitions/$actionName"."Object"
                    ]
                ];
            }

            $this->saveDefinitions($actionName, $rules, $annotations);
        }
    }

    protected function saveDefinitions($objectName, $rules, $annotations) {
        $data = [
            'type' => 'object',
            'required' => [],
            'properties' => []
        ];
        foreach ($rules as $parameter => $rule) {
            $this->saveParameterType($data, $parameter, $rule, $annotations);

            if($rule == 'required') {
                array_push($data['required'], $parameter);
            }
        }

        $data['example'] = $this->generateExample($data['properties']);
        $this->data['definitions'][$objectName."Object"] = $data;
    }

    protected function saveParameterType(&$data, $parameter, $rule, $annotations) {
        $validationRules = [
            'array' => 'object',
            'boolean' => 'boolean',
            'date' => 'date',
            'digits' => 'integer',
            'email' => 'string',
            'integer' => 'integer',
            'numeric' => 'double',
            'string' => 'string'
        ];

        $data['properties'][$parameter] = [
            'type' => 'string',
        ];

        $rulesArray = explode('|', $rule);

        foreach ($rulesArray as $item) {
            if (in_array($item, array_keys($validationRules))) {
                $data['properties'][$parameter] = [
                    'type' => $validationRules[$item],
                ];
            }
        }
        $description = $annotations->get($parameter, implode(', ', $rulesArray));
        $data['properties'][$parameter]['description'] = $description;
    }

    protected function requestHasMoreProperties($actionName) {
        $requestParametersCount = count($this->request->all());

        if (isset($this->data['definitions'][$actionName."Object"]['properties'])) {
            $objectParametersCount = count($this->data['definitions'][$actionName."Object"]['properties']);
        } else {
            $objectParametersCount = 0;
        }

        return $requestParametersCount > $objectParametersCount;
    }

    protected function requestHasBody() {
        $parameters = $this->data["paths"][$this->uri][$this->method]['parameters'];

        $bodyParamExisted = array_where($parameters, function($value, $key) {
            return $value['name'] == 'body';
        });

        return empty($bodyParamExisted);
    }

    protected function getValidationRules() {
        $request = $this->getConcreteRequest();

        if (empty($request)) {
            return [];
        }

        return $request::getRules();
    }

    public function getConcreteRequest() {
        $controller = $this->request->route()->getActionName();

        if ($controller == 'Closure') {
            return null;
        }

        $explodedController = explode('@', $controller);

        $class = $explodedController[0];
        $method = $explodedController[1];

        $instance = app($class);
        $route = $this->request->route();

        $parameters = $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $instance, $method
        );

        return array_first($parameters, function ($key, $parameter) {
            return preg_match('/Request/', $key);
        });
    }

    public function saveConsume() {
        $consumeList = $this->data['paths'][$this->uri][$this->method]['consumes'];
        $consume = $this->request->header('Content-Type');

        if (!empty($consume) && !in_array($consume, $consumeList)) {
            $this->item['consumes'][] = $consume;
        }
    }

    public function saveTags() {
        $tagIndex = 1;

        $explodedUri = explode('/', $this->uri);

        $tag = array_get($explodedUri, $tagIndex);

        $this->item['tags'] = [$tag];
    }

    public function saveDescription() {
        $request = $this->getConcreteRequest();
        $this->item['description'] = '';

        if (empty($request)) {
            return;
        }

        $this->item['summary'] = $this->getSummary($request);

        $description = $this->getDescription($request);

        if (!empty($description)) {
            $this->item['description'] = $description;
        }
    }

    protected function saveSecurity() {
        if ($this->requestSupportAuth()) {
            $this->addSecurityToOperation();
        }
    }

    protected function addSecurityToOperation() {
        $security = &$this->data['paths'][$this->uri][$this->method]['security'];
        if (empty($security)) {
            $security[] = [
                "{$this->security}" => []
            ];
        }
    }

    protected function getSummary($request) {
        $annotations = $this->annotationReader->getClassAnnotations($request);

        $summary = $annotations->get('summary');

        if (empty($summary)) {
            $summary = $this->parseRequestName($request);
        }

        return $summary;
    }

    protected function getDescription($request) {
        $annotations = $this->annotationReader->getClassAnnotations($request);

        return $annotations->get('description');
    }

    protected function requestSupportAuth() {
        switch ($this->security) {
            case 'jwt' :
                $header = $this->request->header('authorization');
                break;
            case 'laravel' :
                $header = $this->request->cookie('__ym_uid');
                break;
        }

        return !empty($header);

    }

    protected function parseRequestName($request) {
        $explodedRequest = explode('\\', $request);
        $requestName = array_pop($explodedRequest);

        $underscoreRequestName = $this->camelCaseToUnderScore($requestName);

        return preg_replace('/[_]/', ' ', $underscoreRequestName);
    }

    protected function getResponseDescription($code) {
        $request = $this->getConcreteRequest();

        return elseChain(
            function() use ($request, $code) {
                return empty($request) ? Response::$statusTexts[$code] : null;
            },
            function() use ($request, $code) {
                return $this->annotationReader->getClassAnnotations($request)->get("_{$code}");
            },
            function() use ($code) {
                return config("auto-doc.defaults.code-descriptions.{$code}");
            },
            function() use ($code) {
                return Response::$statusTexts[$code];
            }
        );
    }

    protected function getActionName($uri) {
        $action = preg_replace('[\/]','',$uri);

        return Str::camel($action);
    }

    protected function saveTempData() {
        $exportFile = config('auto-doc.files.temporary');
        $data = json_encode($this->data);

        file_put_contents($exportFile, $data);
    }

    public function saveProductionData() {
        $this->dataCollector->saveData();
    }

    public function getDocFileContent() {
        $data = $this->dataCollector->getDocumentation();

        return $data;
    }

    private function camelCaseToUnderScore($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    protected function generateExample($properties){
        $parameters = $this->request->all();
        $example = [];

        $this->replaceNullValues($parameters, $properties, $example);

        return $example;
    }

    /**
     * NOTE: All functions below are temporary solution for
     * this issue: https://github.com/OAI/OpenAPI-Specification/issues/229
     * We hope swagger developers will resolve this problem in next release of Swagger OpenAPI
     * */

    private function replaceNullValues($parameters, $types, &$example) {
        foreach ($parameters as $parameter => $value) {
            if (is_null($value) && in_array($parameter, $types)) {
                $example[$parameter] = $this->getDefaultValueByType($types[$parameter]['type']);
            } elseif (is_array($value)) {
                $this->replaceNullValues($value, $types, $example[$parameter]);
            } else {
                $example[$parameter] = $value;
            }
        }
    }

    private function getDefaultValueByType($type) {
        $values = [
            'object' => 'null',
            'boolean' => false,
            'date' => "0000-00-00",
            'integer' => 0,
            'string' => "",
            'double' => 0
        ];

        return $values[$type];
    }
}