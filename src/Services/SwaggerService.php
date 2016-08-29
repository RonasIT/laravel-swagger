<?php

/**
 * Created by PhpStorm.
 * User: roman
 * Date: 26.08.16
 * Time: 13:09
 */

namespace RonasIT\Support\AutoDoc\Services;

use App\Http\Requests\Request;
use Illuminate\Container\Container;
use Illuminate\Http\Exception\HttpResponseException;
use RonasIT\Support\AutoDoc\Traits\GetDependenciesTrait;
use RonasIT\Support\AutoDoc\Exceptions\CannotFindTemporaryFileException;
use Symfony\Component\HttpFoundation\Response;

class SwaggerService
{
    use GetDependenciesTrait;

    protected $data;
    protected $container;
    private $uri;
    private $method;
    private $request;
    private $response;
    private $item;

    public function __construct(Container $container)
    {
        $this->container = $container;

        $file = config('auto-doc.files.temporary');

        if (file_exists($file)) {
            $this->data = json_decode(file_get_contents($file), true);
        } else {
            $this->data = $this->generateEmptyData();
        }
    }

    protected function generateEmptyData() {
        return [
            'swagger' => config('auto-doc.swagger.version'),
            'info' => config('auto-doc.info'),
            'host' => config('app.url'),
            'basePath' => config('auto-doc.basePath'),
            'schemes' => config('auto-doc.schemes'),
            'paths' => [],
            'definitions' => config('auto-doc.definitions'),
        ];
    }

    public function addData($request, $response) {
        $this->request = $request;
        $this->response = $response;

        $this->prepareItem();

        $this->parseRequest();
        $this->parseResponse();

        $this->saveTempData();
    }

    protected function prepareItem() {
        $this->uri = $this->request->route()->uri();
        $this->method = strtolower($this->request->getMethod());

        if (empty(array_get($this->data, "paths.{$this->uri}.{$this->method}"))) {
            $this->data['paths'][$this->uri][$this->method] = [
                'tags' => [],
                'consumes' => [],
                'produces' => [],
                'parameters' => [],
                'responses' => [],
                'security' => []
            ];
        }

        $this->item = &$this->data['paths'][$this->uri][$this->method];
    }

    protected function parseRequest() {
        $this->saveConsume();
        $this->saveTags();
        $this->saveParameters();
        $this->saveDescription();
    }

    protected function parseResponse() {
        $produceList = $this->data['paths'][$this->uri][$this->method]['produces'];
        $produce = 'application/json'; // TODO: this is temporary solution

        if (!in_array($produce, $produceList)) {
            $this->item['produces'][] = $produce;
        }

        $responses = $this->item['responses'];
        $code = $this->response->getStatusCode();

        if (!in_array($code, $responses)) {
            $this->item['responses'][$code] = [
                'description' => $this->getResponseDescription($code),
                'schema' => [
                    'example' => json_decode($this->response->getContent(), true)
                ]
            ];
        }
    }

    protected function saveParameters() {
        $rules = $this->getValidationRules();
        $bodyMethods = ['post', 'put'];

        foreach ($rules as $parameter => $rule) {
            $validation = explode('|', $rule);

            $existedParameter = array_first($this->item['parameters'], function ($key, $existedParameter) use ($parameter) {
                return $existedParameter['name'] == $parameter;
            });

            if (empty($existedParameter)) {
                $this->item['parameters'][] = [
                    'in' => in_array($this->method, $bodyMethods) ? 'body' : 'query',
                    'name' => $parameter,
                    'description' => implode(', ', $validation),
                    'required' => in_array('required', $validation),
                    'type' => implode(', ', $validation)
                ];
            }
        }
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

        $explodedController = explode('@', $controller);

        $class = $explodedController[0];
        $method = $explodedController[1];

        $instance = app($class);
        $route = $this->request->route();

        $parameters = $this->resolveClassMethodDependencies(
            $route->parametersWithoutNulls(), $instance, $method
        );

        return array_first($parameters, function ($key, $parameter) {
            return preg_match('/Request/', $parameter);
        });
    }

    public function saveConsume() {
        $consumeList = $this->data['paths'][$this->uri][$this->method]['consumes'];
        $consume = $this->request->header('Content-Type');

        if (!in_array($consume, $consumeList)) {
            $this->item['consumes'][] = $consume;
        }
    }

    public function saveTags() {
        $explodedUri = explode('/', $this->uri);

        $tag = array_get($explodedUri, 0);

        $this->item['tags'] = [$tag];
    }

    public function saveDescription() {
        $request = $this->getConcreteRequest();

        $explodedRequest = explode('\\', $request);
        $requestName = array_pop($explodedRequest);

        $underscoreRequestName = $this->camelCaseToUnderScore($requestName);

        $this->item['summary'] = preg_replace('/[_]/', ' ', $underscoreRequestName);

        if (!empty($request)) {
            $this->item['description'] = $request::getDescription();
        }
    }

    protected function getResponseDescription($code) {
        $request = $this->getConcreteRequest();

        if (empty($request)) {
            return Response::$statusTexts[$code];
        }

        $description = $request::getDescriptionOfResponse($code);

        if (empty($description)) {
            return Response::$statusTexts[$code];
        }

        return $description;
    }

    protected function saveTempData() {
        $exportFile = config('auto-doc.files.temporary');
        $data = json_encode($this->data);

        file_put_contents($exportFile, $data);
    }

    public function saveProductionData() {
        $tempFile = config('auto-doc.files.temporary');
        $prodFile = config('auto-doc.files.production');

        if (!file_exists($tempFile)) {
            throw new CannotFindTemporaryFileException();
        }

        rename($tempFile, $prodFile);
    }

    private function camelCaseToUnderScore($input) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }
}