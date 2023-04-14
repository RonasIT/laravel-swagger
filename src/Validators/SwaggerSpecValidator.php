<?php

namespace RonasIT\Support\AutoDoc\Validators;

use Illuminate\Support\Arr;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\DuplicatedParamException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\DuplicatedPathPlaceholderException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidDocFieldValueException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidHttpMethodException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidResponseCodeException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidSwaggerSpecException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidSwaggerVersionException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissedDocDefinitionException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissedDocFieldException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\PathParamMissingException;

class SwaggerSpecValidator
{
    const SCHEMA_TYPES = [
        'array', 'boolean', 'integer', 'number', 'string', 'object', 'null', 'undefined'
    ];
    const PRIMITIVE_TYPES = [
        'array', 'boolean', 'integer', 'number', 'string'
    ];

    const REQUIRED_FIELDS = [
        'definition' => ['type'],
        'doc' => ['swagger', 'info', 'paths'],
        'info' => ['title', 'version'],
        'item' => ['type'],
        'header' => ['type'],
        'operation' => ['responses'],
        'parameter' => ['in', 'name'],
        'response' => ['description'],
        'security_definition' => ['type'],
        'tag' => ['name']
    ];

    const AVAILABLE_VALUES = [
        'collection_format' => ['csv', 'ssv', 'tsv', 'pipes', 'multi'],
        'http_method' => ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'],
        'parameter_in' => ['body', 'formData', 'query', 'path', 'header'],
        'schemes' => ['http', 'https', 'ws', 'wss'],
        'security_definition_flow' => ['implicit', 'password', 'application', 'accessCode'],
        'security_definition_in' => ['query', 'header'],
        'security_definition_type' => ['basic', 'apiKey', 'oauth2']
    ];

    const PATH_PARAM_REGEXP = '#(?<={)[^/}]+(?=})#';
    const DEFINITION_REF_REGEXP = '/^#\/definitions\/.+/';

    const MIME_TYPE_MULTIPART_FORM_DATA = 'multipart/form-data';
    const MIME_TYPE_APPLICATION_URLENCODED = 'application/x-www-form-urlencoded';

    /**
     * @var array
     */
    protected $doc;

    public function __construct(array $doc)
    {
        $this->doc = $doc;
    }

    public function validate(): void
    {
        $this->validateVersion();
        $this->validateMissedFields($this->doc, self::REQUIRED_FIELDS['doc']);
        $this->validateInfo();
        $this->validateSchemes();
        $this->validatePaths();
        $this->validateDefinitions();
        $this->validateSecurityDefinitions();
        $this->validateTags();
    }

    protected function validateVersion(): void
    {
        $version = Arr::get($this->doc, 'swagger', '');

        if (version_compare($version, '2.0', '!=')) {
            throw new InvalidSwaggerVersionException($version);
        }
    }

    protected function validateInfo(): void
    {
        $this->validateMissedFields($this->doc['info'], self::REQUIRED_FIELDS['info'], 'info');
    }

    protected function validateSchemes(): void
    {
        $this->validateFieldHasAvailableValues('schemes', $this->doc, self::AVAILABLE_VALUES['schemes']);
    }

    protected function validatePaths(): void
    {
        foreach ($this->doc['paths'] as $path => $operations) {
            foreach ($operations as $method => $operation) {
                $operationId = "paths|{$path}|{$method}";

                if (!in_array($method, self::AVAILABLE_VALUES['http_method'])) {
                    throw new InvalidHttpMethodException($method, $path);
                }

                $this->validateMissedFields($operation, self::REQUIRED_FIELDS['operation'], $operationId);

                $this->validateParameters($operation, $path, $operationId);

                foreach ($operation['responses'] as $statusCode => $response) {
                    $this->validateResponse($response, $statusCode, $operationId);
                }
            }
        }
    }

    protected function validateDefinitions(): void
    {
        $definitions = Arr::get($this->doc, 'definitions', []);

        foreach ($definitions as $index => $definition) {
            $this->validateMissedFields($definition, self::REQUIRED_FIELDS['definition'], "definitions|{$index}");
        }

        $missedDefinitions = $this->getMissedFields($definitions, $this->getRefs());

        if (!empty($missedDefinitions)) {
            throw new MissedDocDefinitionException($missedDefinitions);
        }
    }

    protected function validateSecurityDefinitions(): void
    {
        $securityDefinitions = Arr::get($this->doc, 'securityDefinitions', []);

        foreach ($securityDefinitions as $index => $securityDefinition) {
            $parentId = "securityDefinitions|{$index}";

            $this->validateMissedFields($securityDefinition, self::REQUIRED_FIELDS['security_definition'], $parentId);

            $this->validateFieldHasAvailableValue('type', $securityDefinition, self::AVAILABLE_VALUES['security_definition_type'], $parentId);
            $this->validateFieldHasAvailableValue('in', $securityDefinition, self::AVAILABLE_VALUES['security_definition_in'], $parentId);
            $this->validateFieldHasAvailableValue('flow', $securityDefinition, self::AVAILABLE_VALUES['security_definition_flow'], $parentId);
        }
    }

    protected function validateTags(): void
    {
        $tags = Arr::get($this->doc, 'tags', []);

        foreach ($tags as $index => $tag) {
            $this->validateMissedFields($tag, self::REQUIRED_FIELDS['tag'], "tags|{$index}");
        }
    }

    protected function validateResponse(array $response, string $statusCode, string $operationId): void
    {
        $responseId = "{$operationId}|responses|{$statusCode}";

        $this->validateMissedFields($response, self::REQUIRED_FIELDS['response'], $responseId);

        if (($statusCode !== 'default') && (($statusCode < 100) || ($statusCode > 599))) {
            throw new InvalidResponseCodeException($statusCode, $responseId);
        }

        foreach (Arr::get($response, 'headers', []) as $headerName => $header) {
            $this->validateHeader($header, "{$responseId}|headers|{$headerName}");
        }

        if (!empty($response['schema'])) {
            $this->validateSchema($response['schema'], array_merge(self::SCHEMA_TYPES, ['files']), "{$responseId}|schema");
        }

        if (!empty($response['items'])) {
            $this->validateItems($response['items'], "{$responseId}|items");
        }
    }

    protected function validateParameters(array $operation, string $path, string $operationId): void
    {
        $parameters = Arr::get($operation, 'parameters', []);

        foreach ($parameters as $index => $param) {
            $paramId = "{$operationId}|parameters|{$index}";

            $this->validateMissedFields($param, self::REQUIRED_FIELDS['parameter'], $paramId);

            $this->validateFieldHasAvailableValue('in', $param, self::AVAILABLE_VALUES['parameter_in'], $paramId);
            $this->validateFieldHasAvailableValue('collectionFormat', $param, self::AVAILABLE_VALUES['collection_format'], $paramId);

            $this->validateParameterType($param, $operation, $paramId, $operationId);

            if (!empty($param['items'])) {
                $this->validateItems($param['items'], "{$paramId}|items");
            }
        }

        $this->validateParamDuplicates($parameters);

        $this->validatePathParameters($parameters, $path, $operationId);
        $this->validateBodyParameters($parameters, $operationId);
    }

    protected function validateSchema(array $schema, array $validTypes, string $schemaId): void
    {
        $schemaType = Arr::get($schema, 'type');

        if (!empty($schemaType) && !in_array($schemaType, $validTypes)) {
            throw new InvalidDocFieldValueException("{$schemaId}|type", $schema['type']);
        }

        if (($schemaType === 'array') && empty($schema['items'])) {
            throw new InvalidSwaggerSpecException("Validation failed. {$schemaId} is an array, so it must include an 'items' field.");
        }
    }

    protected function validateParamDuplicates(array $params): void
    {
        for ($i = 0; $i < count($params) - 1; $i++) {
            $outer = $params[$i];

            for ($j = $i + 1; $j < count($params); $j++) {
                $inner = $params[$j];

                if (($outer['name'] === $inner['name']) && ($outer['in'] === $inner['in'])) {
                    throw new DuplicatedParamException($outer['in'], $outer['name']);
                }
            }
        }
    }

    protected function validatePathParameters(array $params, string $path, string $operationId): void
    {
        $pathParams = Arr::where($params, function ($param) {
            return ($param['in'] === 'path');
        });

        preg_match_all(self::PATH_PARAM_REGEXP, $path, $placeholders);
        $placeholders = $placeholders[0];

        $placeholderDuplicates = array_get_duplicates($placeholders);

        if (!empty($placeholderDuplicates)) {
            throw new DuplicatedPathPlaceholderException($placeholderDuplicates, $path);
        }

        foreach ($pathParams as $param) {
            if (!Arr::get($param, 'required', false)) {
                throw new InvalidSwaggerSpecException("Validation failed. Path parameters cannot be optional. Set required=true for the '{$param['name']}' parameter at operation '{$operationId}'.");
            }

            $placeholderIndex = array_search($param['name'], $placeholders);

            if ($placeholderIndex === false) {
                throw new InvalidSwaggerSpecException("Validation failed. Operation {$operationId} has a path parameter named '{$param['name']}', but there is no corresponding '{$param['name']}' in the path string.");
            }

            unset($placeholders[$placeholderIndex]);
        }

        if (!empty($placeholders)) {
            throw new PathParamMissingException($operationId, $placeholders);
        }
    }

    protected function validateBodyParameters(array $parameters, string $operationId): void
    {
        $bodyParams = Arr::where($parameters, function ($param) {
            return ($param['in'] === 'body');
        });
        $formParams = Arr::where($parameters, function ($param) {
            return ($param['in'] === 'formData');
        });

        if (($bodyParamsCount = count($bodyParams)) > 1) {
            throw new InvalidSwaggerSpecException("Validation failed. Operation '{$operationId}' has {$bodyParamsCount} body parameters. Only one is allowed.");
        }

        if (!empty($bodyParams) && !empty($formParams)) {
            throw new InvalidSwaggerSpecException("Validation failed. Operation '{$operationId}' has body parameters and formData parameters. Only one or the other is allowed.");
        }
    }

    protected function validateParameterType(array $param, array $operation, string $paramId, string $operationId): void
    {
        switch ($param['in']) {
            case 'body':
                $requiredFields = ['schema'];
                $validTypes = self::SCHEMA_TYPES;
                break;
            case 'formData':
                $requiredFields = ['type'];
                $validTypes = array_merge(self::PRIMITIVE_TYPES, ['file']);
                break;
            default:
                $requiredFields = ['type'];
                $validTypes = self::PRIMITIVE_TYPES;
        }

        $this->validateMissedFields($param, $requiredFields, $paramId);

        $schema = Arr::get($param, 'schema', $param);
        $this->validateSchema($schema, $validTypes, $paramId);

        if (Arr::get($schema, 'type') === 'file') {
            $requiredMimeType = Arr::first(
                Arr::get($operation, 'consumes', []),
                function ($consume) {
                    return ($consume === self::MIME_TYPE_APPLICATION_URLENCODED || $consume === self::MIME_TYPE_MULTIPART_FORM_DATA);
                }
            );

            if (empty($requiredMimeType)) {
                throw new InvalidSwaggerSpecException("Validation failed. Operation '{$operationId}' has a file parameter, so it must consume 'multipart/form-data' or 'application/x-www-form-urlencoded'.");
            }
        }
    }

    protected function validateHeader(array $header, string $headerId): void
    {
        $this->validateMissedFields($header, self::REQUIRED_FIELDS['header'], $headerId);

        $this->validateSchema($header, self::PRIMITIVE_TYPES, $headerId);

        if (!empty($header['items'])) {
            $this->validateItems($header['items'], $headerId);
        }
    }

    protected function validateItems(array $items, string $itemsId): void
    {
        $this->validateMissedFields($items, self::REQUIRED_FIELDS['item'], $itemsId);

        $this->validateSchema($items, self::PRIMITIVE_TYPES, $itemsId);
    }

    protected function getMissedFields(array $parentField, array $requiredFields): array
    {
        return array_diff($requiredFields, array_keys($parentField));
    }

    protected function validateMissedFields(array $parent, array $requiredFields, string $parentId = null): void
    {
        $missedDocFields = $this->getMissedFields($parent, $requiredFields);

        if (!empty($missedDocFields)) {
            throw new MissedDocFieldException($missedDocFields, $parentId);
        }
    }

    protected function validateFieldHasAvailableValue(string $fieldName, array $parent, array $availableValues, string $parentId = null): void
    {
        if (
            !empty($parent[$fieldName])
            && !in_array($parent[$fieldName], $availableValues)
        ) {
            throw new InvalidDocFieldValueException($parentId ? "{$parentId}|{$fieldName}" : $fieldName, $parent[$fieldName]);
        }
    }

    protected function validateFieldHasAvailableValues(string $fieldName, array $parent, array $availableValues, string $parentId = null): void
    {
        $inputValue = Arr::get($parent, $fieldName, []);
        $approvedValues = array_intersect($inputValue, $availableValues);
        $invalidValues = array_diff($inputValue, $approvedValues);

        if (!empty($invalidValues)) {
            throw new InvalidDocFieldValueException($parentId ? "{$parentId}|{$fieldName}" : $fieldName, $invalidValues);
        }
    }

    protected function getRefs(): array
    {
        $refs = [];

        array_walk_recursive($this->doc, function ($item, $key) use (&$refs) {
            if (
                ($key === '$ref')
                && preg_match(self::DEFINITION_REF_REGEXP, $item)
            ) {
                $refs[] = str_replace('#/definitions/', '', $item);
            }
        });

        return $refs;
    }
}