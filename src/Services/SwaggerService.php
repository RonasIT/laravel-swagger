<?php

namespace KWXS\Support\AutoDoc\Services;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Minime\Annotations\Cache\ArrayCache;
use Minime\Annotations\Interfaces\AnnotationsBagInterface;
use Minime\Annotations\Reader as AnnotationReader;
use KWXS\Support\AutoDoc\Exceptions\DataCollectorClassNotFoundException;
use KWXS\Support\AutoDoc\Exceptions\WrongSecurityConfigException;
use KWXS\Support\AutoDoc\Interfaces\DataCollectorInterface;
use KWXS\Support\AutoDoc\Traits\GetDependenciesTrait;
use KWXS\Support\AutoDoc\DataCollectors\LocalDataCollector;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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

	/** @var \Illuminate\Http\Request */
	private $request;

	private $item;

	private $security;

	public function __construct(Container $container)
	{
		$this->setDataCollector();
		$allowedEnv = config('swagger.allowedEnv');

		if (in_array(config('app.env'), $allowedEnv)) {
			$this->container = $container;

			$this->annotationReader = new AnnotationReader(new Parser(), new ArrayCache());

			$this->security = config('swagger.security');

			$this->data = $this->dataCollector->getTmpData();

			if (empty($this->data)) {
				$this->data = $this->generateEmptyData();

				$this->dataCollector->saveTmpData($this->data);
			}
		}
	}

	public function addData(Request $request, $response)
	{
		$this->request = clone $request;
		$this->request->replace((array) json_decode($this->request->getContent(), true));

		try {
			$this->prepareItem();
			$this->parseRequest($request);
			$this->parseResponse($response);

			$this->dataCollector->saveTmpData($this->data);
		} catch (Throwable $e) {
			return;
		}
	}

	public function getConcreteRequest()
	{
		$route = $this->request->route();

		$routeController = $route->getActionName();
		if ($routeController == 'Closure') {
			return null;
		}

		$controller = $route->getController();
		$method = Str::contains($routeController, '@') ? $route->getActionMethod() : '__invoke';

		$parameters = $this->resolveClassMethodDependencies(
			$route->parametersWithoutNulls(), $controller, $method
		);

		$request = Arr::first($parameters, function ($key, $parameter) {
			return preg_match('/Request/', $key);
		});

		return $request;
	}

	public function getMethodAnnotation()
	{
		$route = $this->request->route();
		$routeController = $route->getActionName();
		if ($routeController == 'Closure') {
			return null;
		}

		$class = get_class($route->getController());
		$method = Str::contains($routeController, '@') ? $route->getActionMethod() : '__invoke';


		return $this->annotationReader->getMethodAnnotations($class, $method);
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
		$tagIndex = 1;

		$explodedUri = explode('/', $this->uri);

		$tag = Arr::get($explodedUri, $tagIndex);

		$this->item['tags'] = [$tag];
	}

	public function saveDescription($request, AnnotationsBagInterface $annotations)
	{
		$this->item['summary'] = $this->getSummary($request, $annotations);
		$this->item['description'] = $annotations->get('description', '');

		$tags = $annotations->get('tag', []);

		if (count($tags)) {
			$this->item['tags'] = $tags;
		}

		$this->item['summary'] .= ' || Tags: ' . implode(',', $this->item['tags']);
	}

	public function saveProductionData(?string $filePath = null)
	{
		$this->dataCollector->saveData($filePath);
	}

	public function getDocFileContent()
	{
		return $this->dataCollector->getDocumentation();
	}

	protected function setDataCollector()
	{
		$dataCollectorClass = config('swagger.data_collector');

		if (empty($dataCollectorClass)) {
			$this->dataCollector = app(LocalDataCollector::class);
		} elseif (!class_exists($dataCollectorClass)) {
			throw new DataCollectorClassNotFoundException();
		} else {
			$this->dataCollector = app($dataCollectorClass);
		}
	}

	protected function generateEmptyData()
	{
		$data = [
			'swagger'     => config('swagger.swagger.version'),
			'host'        => $this->getAppUrl(),
			'basePath'    => config('swagger.basePath'),
			'schemes'     => config('swagger.schemes'),
			'paths'       => [],
			'definitions' => config('swagger.definitions'),
		];

		$info = $this->prepareInfo(config('swagger.info'));

		if (!empty($info)) {
			$data['info'] = $info;
		}

		$securityDefinitions = $this->generateSecurityDefinition();

		if (!empty($securityDefinitions)) {
			$data['securityDefinitions'] = $securityDefinitions;
		}

		$data['info']['description'] = view($data['info']['description'])->render();

		return $data;
	}

	protected function getAppUrl()
	{
		$url = config('app.url');

		return str_replace(['http://', 'https://', '/'], '', $url);
	}

	protected function generateSecurityDefinition()
	{
		$availableTypes = ['jwt', 'laravel'];
		$security = $this->security;

		if (empty($security)) {
			return '';
		}

		if (!in_array($security, $availableTypes)) {
			throw new WrongSecurityConfigException();
		}

		return [
			$security => $this->generateSecurityDefinitionObject($security),
		];
	}

	protected function generateSecurityDefinitionObject($type)
	{
		switch ($type) {
			case 'jwt':
				return [
					'type' => 'apiKey',
					'name' => 'authorization',
					'in'   => 'header',
				];

			case 'laravel':
				return [
					'type' => 'apiKey',
					'name' => 'Cookie',
					'in'   => 'header',
				];
		}
	}

	protected function prepareItem()
	{
		$this->uri = "/{$this->getUri()}";
		$this->method = mb_strtolower($this->request->getMethod());

		if (empty(Arr::get($this->data, "paths.{$this->uri}.{$this->method}"))) {
			$this->data['paths'][$this->uri][$this->method] = [
				'tags'        => [],
				'consumes'    => [],
				'produces'    => [],
				'parameters'  => $this->getPathParams(),
				'responses'   => [],
				'security'    => [],
				'description' => '',
			];
		}

		$this->item = &$this->data['paths'][$this->uri][$this->method];
	}

	protected function getUri()
	{
		$uri = $this->request->route()->uri();
		$basePath = preg_replace('/^\\//', '', config('swagger.basePath'));
		$preparedUri = preg_replace("/^{$basePath}/", '', $uri);

		return preg_replace('/^\\//', '', $preparedUri);
	}

	protected function getPathParams()
	{
		$params = [];

		preg_match_all('/{.*?}/', $this->uri, $params);

		$params = Arr::collapse($params);

		$result = [];

		foreach ($params as $param) {
			$key = preg_replace('/[{}]/', '', $param);

			$result[] = [
				'in'          => 'path',
				'name'        => $key,
				'description' => '',
				'required'    => true,
				'type'        => 'string',
			];
		}

		return $result;
	}

	protected function parseRequest($request)
	{
		$this->saveConsume();
		$this->saveTags();
		$this->saveSecurity();

		$concreteRequest = $this->getConcreteRequest();
		$descriptionAnnotation = $this->getMethodAnnotation();

		if (empty($concreteRequest)) {
			$this->item['description'] = $descriptionAnnotation->get('description', '');

			return;
		}

		$annotations = $this->annotationReader->getClassAnnotations($concreteRequest);

		$this->saveParameters($request, $annotations);
		$this->saveDescription($concreteRequest, $descriptionAnnotation);
	}

	protected function parseResponse($response)
	{
		$produceList = $this->data['paths'][$this->uri][$this->method]['produces'];

		$produce = $response->headers->get('Content-type');

		if ($produce === null) {
			$produce = 'text/plain';
		}

		if (!in_array($produce, $produceList)) {
			$this->item['produces'][] = $produce;
		}

		$responses = $this->item['responses'];
		$code = $response->getStatusCode();

		// Remove debug trace from response
		$content = json_decode($response->getContent(), true);
		Arr::except($content, [
			'code',
			'exception',
			'file',
			'line',
			'trace'
		]);

		if (!in_array($code, $responses)) {
			$this->saveExample(
				$response->getStatusCode(),
				json_encode($content),
				$produce
			);
		}
	}

	protected function saveExample($code, $content, $produce)
	{
		$description = $this->getResponseDescription($code);
		$availableContentTypes = [
			'application',
			'text',
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
			'description' => $description,
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

	protected function saveParameters($request, AnnotationsBagInterface $annotations): void
	{
		$requestObj = $request->setRouteResolver($this->request->getRouteResolver());
		$rules = method_exists($requestObj, 'rules') ? $requestObj->rules() : [];
		$actionName = $this->getActionName($this->uri);

		if (in_array($this->method, ['get', 'delete'])) {
			$this->saveGetRequestParameters($rules, $annotations);
		} else {
			$this->savePostRequestParameters($actionName, $rules, $annotations);
		}
	}

	protected function saveGetRequestParameters($rules, AnnotationsBagInterface $annotations)
	{
		foreach ($rules as $parameter => $rule) {
			$validation = is_array($rule) ? Arr::where($rule, fn ($item) => is_string($item)) : explode('|', $rule);

			$description = $annotations->get($parameter, implode(', ', $validation));

			$existedParameter = Arr::first($this->item['parameters'], function ($existedParameter, $key) use ($parameter) {
				return $existedParameter['name'] == $parameter;
			});

			if (empty($existedParameter)) {
				$parameterDefinition = [
					'in'          => 'query',
					'name'        => $parameter,
					'description' => $description,
					'type'        => $this->getParameterType($validation),
				];

				if (in_array('required', $validation)) {
					$parameterDefinition['required'] = true;
				}

				$this->item['parameters'][] = $parameterDefinition;
			}
		}
	}

	protected function savePostRequestParameters($actionName, $rules, AnnotationsBagInterface $annotations)
	{
		if ($this->requestHasMoreProperties($actionName)) {
			if ($this->requestHasBody()) {
				$this->item['parameters'][] = [
					'in'          => 'body',
					'name'        => 'body',
					'description' => '',
					'required'    => true,
					'schema'      => [
						'$ref' => "#/definitions/{$actionName}Object",
					],
				];
			}

			$this->saveDefinitions($actionName, $rules, $annotations);
		}
	}

	protected function saveDefinitions($objectName, $rules, $annotations)
	{
		$data = [
			'type'       => 'object',
			'properties' => [],
		];

		foreach ($rules as $parameter => $rule) {
			$rulesArray = is_array($rule) ? $rule : explode('|', $rule);
			$parameterType = $this->getParameterType($rulesArray);
			$this->saveParameterType($data, $parameter, $parameterType);

			try {
				$this->saveParameterDescription($data, $parameter, $rulesArray, $annotations);
			} catch (Exception $e) {
				continue;
			}

			if (in_array('required', $rulesArray)) {
				$data['required'][] = $parameter;
			}
		}

		$data['example'] = $this->generateExample($data['properties']);
		$this->data['definitions'][$objectName . 'Object'] = $data;
	}

	protected function getParameterType(array $validation)
	{
		$validationRules = [
			'array'   => 'object',
			'boolean' => 'boolean',
			'date'    => 'date',
			'digits'  => 'integer',
			'email'   => 'string',
			'integer' => 'integer',
			'numeric' => 'double',
			'string'  => 'string',
		];

		$parameterType = 'string';

		foreach ($validation as $item) {
			if (in_array($item, array_keys($validationRules))) {
				$parameterType = $validationRules[$item];

				break;
			}
		}

		return $parameterType;
	}

	protected function saveParameterType(&$data, $parameter, $parameterType)
	{
		$data['properties'][$parameter] = [
			'type' => $parameterType,
		];
	}

	protected function saveParameterDescription(&$data, $parameter, array $rulesArray, AnnotationsBagInterface $annotations)
	{
		try {
			$rules = array_filter($rulesArray, function ($rule) {
				return is_string($rule);
			});
			$description = $annotations->get($parameter, implode(', ', $rules));
			$data['properties'][$parameter]['description'] = $description;
		} catch (Throwable $e) {
			return;
		}
	}

	protected function requestHasMoreProperties($actionName)
	{
		$requestParametersCount = count($this->request->all());

		if (isset($this->data['definitions'][$actionName . 'Object']['properties'])) {
			$objectParametersCount = count($this->data['definitions'][$actionName . 'Object']['properties']);
		} else {
			$objectParametersCount = 0;
		}

		return $requestParametersCount > $objectParametersCount;
	}

	protected function requestHasBody()
	{
		$parameters = $this->data['paths'][$this->uri][$this->method]['parameters'];

		$bodyParamExisted = Arr::where($parameters, function ($value, $key) {
			return $value['name'] == 'body';
		});

		return empty($bodyParamExisted);
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
				"{$this->security}" => [],
			];
		}
	}

	protected function getSummary($request, AnnotationsBagInterface $annotations)
	{
		$summary = $annotations->get('summary');

		if (empty($summary)) {
			$summary = $this->parseRequestName($request);
		}

		return $summary;
	}

	protected function requestSupportAuth()
	{
		switch ($this->security) {
			case 'jwt':
				$header = $this->request->header('authorization');

				break;
			case 'laravel':
				$header = $this->request->cookie('__ym_uid');

				break;
		}

		return !empty($header);
	}

	protected function parseRequestName($request)
	{
		$explodedRequest = explode('\\', $request);
		$requestName = array_pop($explodedRequest);

		$underscoreRequestName = $this->camelCaseToUnderScore($requestName);

		return preg_replace('/[_]/', ' ', $underscoreRequestName);
	}

	protected function getResponseDescription($code)
	{
		$request = $this->getConcreteRequest();

		return $this->elseChain(
			function () use ($request, $code) {
				return empty($request) ? Response::$statusTexts[$code] : null;
			},
			function () use ($request, $code) {
				return $this->annotationReader->getClassAnnotations($request)->get("_{$code}");
			},
			function () use ($code) {
				return config("swagger.defaults.code-descriptions.{$code}");
			},
			function () use ($code) {
				return Response::$statusTexts[$code];
			}
		);
	}

	protected function getActionName($uri)
	{
		$action = preg_replace('[\/]', '', $uri);

		return Str::camel($action);
	}

	protected function saveTempData()
	{
		$exportFile = config('swagger.files.temporary');
		$data = json_encode($this->data);

		file_put_contents($exportFile, $data);
	}

	protected function generateExample($properties)
	{
		$parameters = $this->replaceObjectValues($this->request->all());
		$example = [];

		$this->replaceNullValues($parameters, $properties, $example);

		return $example;
	}

	protected function replaceObjectValues($parameters)
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

	/**
	 * @param $info
	 *
	 * @return mixed
	 */
	protected function prepareInfo($info)
	{
		if (empty($info)) {
			return $info;
		}

		foreach ($info['license'] as $key => $value) {
			if (empty($value)) {
				unset($info['license'][$key]);
			}
		}

		if (empty($info['license'])) {
			unset($info['license']);
		}

		return $info;
	}

	protected function throwTraitMissingException()
	{
		$message = "ERROR:\n" .
			"It looks like you did not add AutoDocRequestTrait to your requester. \n" .
			"Please add it or mark in the test that you do not want to collect the \n" .
			"documentation for this case using the skipDocumentationCollecting() method\n";

		fwrite(STDERR, print_r($message, true));

		die;
	}

	private function camelCaseToUnderScore($input)
	{
		preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
		$ret = $matches[0];

		foreach ($ret as &$match) {
			$match = $match == mb_strtoupper($match) ? mb_strtolower($match) : lcfirst($match);
		}

		return implode('_', $ret);
	}

	/**
	 * NOTE: All functions below are temporary solution for
	 * this issue: https://github.com/OAI/OpenAPI-Specification/issues/229
	 * We hope swagger developers will resolve this problem in next release of Swagger OpenAPI
	 *
	 * @param mixed $parameters
	 * @param mixed $types
	 * @param mixed $example
	 * */
	private function replaceNullValues($parameters, $types, &$example)
	{
		foreach ($parameters as $parameter => $value) {
			if ($value === null && in_array($parameter, $types)) {
				$example[$parameter] = $this->getDefaultValueByType($types[$parameter]['type']);
			} elseif (is_array($value)) {
				$this->replaceNullValues($value, $types, $example[$parameter]);
			} else {
				$example[$parameter] = $value;
			}
		}
	}

	private function getDefaultValueByType($type)
	{
		$values = [
			'object'  => 'null',
			'boolean' => false,
			'date'    => '0000-00-00',
			'integer' => 0,
			'string'  => '',
			'double'  => 0,
		];

		return $values[$type];
	}

	private function elseChain(...$callbacks)
	{
		$value = null;

		foreach ($callbacks as $callback) {
			$value = $callback();

			if (!empty($value)) {
				return $value;
			}
		}

		return $value;
	}
}
