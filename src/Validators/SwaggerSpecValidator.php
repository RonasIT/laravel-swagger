<?php

namespace RonasIT\Support\AutoDoc\Validators;

use Illuminate\Support\Arr;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\DuplicateFieldException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\DuplicateParamException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\DuplicatePathPlaceholderException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidFieldNameException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidFieldValueException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidSwaggerSpecException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidSwaggerVersionException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissingRefException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissingFieldException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissingPathParamException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissingPathPlaceholderException;

class SwaggerSpecValidator
{
    public const SCHEMA_TYPES = [
        'array', 'boolean', 'integer', 'number', 'string', 'object', 'null', 'undefined'
    ];
    public const PRIMITIVE_TYPES = [
        'array', 'boolean', 'integer', 'number', 'string'
    ];

    public const REQUIRED_FIELDS = [
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

    public const AVAILABLE_VALUES = [
        'parameter_collection_format' => ['csv', 'ssv', 'tsv', 'pipes', 'multi'],
        'items_collection_format' => ['csv', 'ssv', 'tsv', 'pipes'],
        'header_collection_format' => ['csv', 'ssv', 'tsv', 'pipes'],
        'path_key' => ['get', 'post', 'put', 'patch', 'delete', 'head', 'options', '$ref', 'parameters'],
        'parameter_in' => ['body', 'formData', 'query', 'path', 'header'],
        'schemes' => ['http', 'https', 'ws', 'wss'],
        'security_definition_flow' => ['implicit', 'password', 'application', 'accessCode'],
        'security_definition_in' => ['query', 'header'],
        'security_definition_type' => ['basic', 'apiKey', 'oauth2']
    ];

    public const PATH_PARAM_REGEXP = '#(?<={)[^/}]+(?=})#';
    public const REF_REGEXP = '/^#\/(.+)\/(.+)/';

    public const MIME_TYPE_MULTIPART_FORM_DATA = 'multipart/form-data';
    public const MIME_TYPE_APPLICATION_URLENCODED = 'application/x-www-form-urlencoded';

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
        $this->validateFieldsPresent($this->doc, self::REQUIRED_FIELDS['doc']);
        $this->validateInfo();
        $this->validateSchemes();
        $this->validatePaths();
        $this->validateDefinitions();
        $this->validateSecurityDefinitions();
        $this->validateTags();
        $this->validateRefs();
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
        $this->validateFieldsPresent($this->doc['info'], self::REQUIRED_FIELDS['info'], 'info');
    }

    protected function validateSchemes(): void
    {
        $this->validateFieldValue('schemes', $this->doc, self::AVAILABLE_VALUES['schemes']);
    }

    protected function validatePaths(): void
    {
        foreach ($this->doc['paths'] as $path => $operations) {
            foreach ($operations as $pathKey => $operation) {
                $operationId = "paths.{$path}.{$pathKey}";

                if (!in_array($pathKey, self::AVAILABLE_VALUES['path_key'])) {
                    throw new InvalidFieldNameException($operationId);
                }

                $this->validateFieldsPresent($operation, self::REQUIRED_FIELDS['operation'], $operationId);
                $this->validateFieldValue('schemes', $operation, self::AVAILABLE_VALUES['schemes'], $operationId);

                $this->validateParameters($operation, $path, $operationId);

                foreach ($operation['responses'] as $statusCode => $response) {
                    $this->validateResponse($response, $statusCode, $operationId);
                }
            }
        }

        $this->validateOperationIdUnique();
    }

    protected function validateDefinitions(): void
    {
        $definitions = Arr::get($this->doc, 'definitions', []);

        foreach ($definitions as $index => $definition) {
            $this->validateFieldsPresent($definition, self::REQUIRED_FIELDS['definition'], "definitions.{$index}");
        }
    }

    protected function validateSecurityDefinitions(): void
    {
        $securityDefinitions = Arr::get($this->doc, 'securityDefinitions', []);

        foreach ($securityDefinitions as $index => $securityDefinition) {
            $parentId = "securityDefinitions.{$index}";

            $this->validateFieldsPresent($securityDefinition, self::REQUIRED_FIELDS['security_definition'], $parentId);

            $this->validateFieldValue('type', $securityDefinition, self::AVAILABLE_VALUES['security_definition_type'], $parentId);
            $this->validateFieldValue('in', $securityDefinition, self::AVAILABLE_VALUES['security_definition_in'], $parentId);
            $this->validateFieldValue('flow', $securityDefinition, self::AVAILABLE_VALUES['security_definition_flow'], $parentId);
        }
    }

    protected function validateTags(): void
    {
        $tags = Arr::get($this->doc, 'tags', []);

        foreach ($tags as $index => $tag) {
            $this->validateFieldsPresent($tag, self::REQUIRED_FIELDS['tag'], "tags.{$index}");
        }

        $this->validateTagsUnique();
    }

    protected function validateResponse(array $response, string $statusCode, string $operationId): void
    {
        $responseId = "{$operationId}.responses.{$statusCode}";

        $this->validateFieldsPresent($response, self::REQUIRED_FIELDS['response'], $responseId);

        if (($statusCode !== 'default') && (($statusCode < 100) || ($statusCode > 599))) {
            throw new InvalidFieldNameException($responseId);
        }

        foreach (Arr::get($response, 'headers', []) as $headerName => $header) {
            $this->validateHeader($header, "{$responseId}.headers.{$headerName}");
        }

        if (!empty($response['schema'])) {
            $this->validateType($response['schema'], array_merge(self::SCHEMA_TYPES, ['file']), "{$responseId}.schema");
        }

        if (!empty($response['items'])) {
            $this->validateItems($response['items'], "{$responseId}.items");
        }
    }

    protected function validateParameters(array $operation, string $path, string $operationId): void
    {
        $parameters = Arr::get($operation, 'parameters', []);

        foreach ($parameters as $index => $param) {
            $paramId = "{$operationId}.parameters.{$index}";

            $this->validateFieldsPresent($param, self::REQUIRED_FIELDS['parameter'], $paramId);

            $this->validateFieldValue('in', $param, self::AVAILABLE_VALUES['parameter_in'], $paramId);
            $this->validateFieldValue('collectionFormat', $param, self::AVAILABLE_VALUES['parameter_collection_format'], $paramId);

            $this->validateParameterType($param, $operation, $paramId, $operationId);

            if (!empty($param['items'])) {
                $this->validateItems($param['items'], "{$paramId}.items");
            }
        }

        $this->validateParamsUnique($parameters);

        $this->validatePathParameters($parameters, $path, $operationId);
        $this->validateBodyParameters($parameters, $operationId);
    }

    protected function validateType(array $schema, array $validTypes, string $schemaId): void
    {
        $schemaType = Arr::get($schema, 'type');

        if (!empty($schemaType) && !in_array($schemaType, $validTypes)) {
            throw new InvalidFieldValueException("{$schemaId}.type", $schema['type']);
        }

        if (($schemaType === 'array') && empty($schema['items'])) {
            throw new InvalidSwaggerSpecException("Validation failed. {$schemaId} is an array, so it must include an 'items' field.");
        }
    }

    protected function validatePathParameters(array $params, string $path, string $operationId): void
    {
        $pathParams = Arr::where($params, function ($param) {
            return ($param['in'] === 'path');
        });

        preg_match_all(self::PATH_PARAM_REGEXP, $path, $matches);
        $placeholders = $matches[0];

        $placeholderDuplicates = array_get_duplicates($placeholders);

        if (!empty($placeholderDuplicates)) {
            throw new DuplicatePathPlaceholderException($placeholderDuplicates, $path);
        }

        foreach ($pathParams as $param) {
            if (!Arr::get($param, 'required', false)) {
                throw new InvalidSwaggerSpecException("Validation failed. Path parameters cannot be optional. Set required=true for the '{$param['name']}' parameter at operation '{$operationId}'.");
            }

            $placeholderIndex = array_search($param['name'], $placeholders);

            if ($placeholderIndex === false) {
                throw new MissingPathPlaceholderException($operationId, $param['name']);
            }

            unset($placeholders[$placeholderIndex]);
        }

        if (!empty($placeholders)) {
            throw new MissingPathParamException($operationId, $placeholders);
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

        $this->validateFieldsPresent($param, $requiredFields, $paramId);

        $schema = Arr::get($param, 'schema', $param);
        $this->validateType($schema, $validTypes, $paramId);

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
        $this->validateFieldsPresent($header, self::REQUIRED_FIELDS['header'], $headerId);

        $this->validateType($header, self::PRIMITIVE_TYPES, $headerId);
        $this->validateFieldValue('collectionFormat', $header, self::AVAILABLE_VALUES['header_collection_format'], $headerId);

        if (!empty($header['items'])) {
            $this->validateItems($header['items'], $headerId);
        }
    }

    protected function validateItems(array $items, string $itemsId): void
    {
        $this->validateFieldsPresent($items, self::REQUIRED_FIELDS['item'], $itemsId);

        $this->validateType($items, self::PRIMITIVE_TYPES, $itemsId);
        $this->validateFieldValue('collectionFormat', $items, self::AVAILABLE_VALUES['items_collection_format'], $itemsId);
    }

    protected function getMissingFields(array $parentField, array $requiredFields): array
    {
        return array_diff($requiredFields, array_keys($parentField));
    }

    protected function validateFieldsPresent(array $parent, array $requiredFields, string $parentId = null): void
    {
        $missingDocFields = $this->getMissingFields($parent, $requiredFields);

        if (!empty($missingDocFields)) {
            throw new MissingFieldException($missingDocFields, $parentId);
        }
    }

    protected function validateFieldValue(string $fieldName, array $parent, array $availableValues, string $parentId = null): void
    {
        $inputValue = Arr::wrap(Arr::get($parent, $fieldName, []));
        $approvedValues = array_intersect($inputValue, $availableValues);
        $invalidValues = array_diff($inputValue, $approvedValues);

        if (!empty($invalidValues)) {
            throw new InvalidFieldValueException($parentId ? "{$parentId}.{$fieldName}" : $fieldName, $invalidValues);
        }
    }

    protected function validateRefs(): void
    {
        array_walk_recursive($this->doc, function ($item, $key) {
            if (
                ($key === '$ref')
                && preg_match(self::REF_REGEXP, $item, $matches)
            ) {
                $refParentKey = $matches[1];
                $refKey = $matches[2];

                $missingRefs = $this->getMissingFields(Arr::get($this->doc, $refParentKey, []), [$refKey]);

                if (!empty($missingRefs)) {
                    throw new MissingRefException($refKey, $refParentKey);
                }
            }
        });
    }

    protected function validateParamsUnique(array $params): void
    {
        for ($i = 0; $i < count($params) - 1; $i++) {
            $outer = $params[$i];

            for ($j = $i + 1; $j < count($params); $j++) {
                $inner = $params[$j];

                if (($outer['name'] === $inner['name']) && ($outer['in'] === $inner['in'])) {
                    throw new DuplicateParamException($outer['in'], $outer['name']);
                }
            }
        }
    }

    protected function validateOperationIdUnique(): void
    {
        $operationIds = array_filter(
            Arr::flatten(
                Arr::pluck($this->doc['paths'], '*.operationId')
            )
        );
        $duplicateOperationIds = array_get_duplicates($operationIds);

        if (!empty($duplicateOperationIds)) {
            throw new DuplicateFieldException('paths.*.operationId', $duplicateOperationIds);
        }
    }

    protected function validateTagsUnique(): void
    {
        $tagIds = array_filter(
            Arr::flatten(
                array_values(Arr::get($this->doc, 'tags', []))
            )
        );
        $duplicateTags = array_get_duplicates($tagIds);

        if (!empty($duplicateTags)) {
            throw new DuplicateFieldException('tags.*.name', $duplicateTags);
        }
    }
}
