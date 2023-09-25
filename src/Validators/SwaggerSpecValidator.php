<?php

namespace RonasIT\Support\AutoDoc\Validators;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\DuplicateFieldException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\DuplicateParamException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\DuplicatePathPlaceholderException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidPathException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidFieldValueException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidStatusCodeException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidSwaggerSpecException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\InvalidSwaggerVersionException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissingExternalRefException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissingLocalRefException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissingFieldException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissingPathParamException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissingPathPlaceholderException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissingRefFileException;
use RonasIT\Support\AutoDoc\Services\SwaggerService;

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

    public const ALLOWED_VALUES = [
        'parameter_collection_format' => ['csv', 'ssv', 'tsv', 'pipes', 'multi'],
        'items_collection_format' => ['csv', 'ssv', 'tsv', 'pipes'],
        'header_collection_format' => ['csv', 'ssv', 'tsv', 'pipes'],
        'parameter_in' => ['body', 'formData', 'query', 'path', 'header'],
        'schemes' => ['http', 'https', 'ws', 'wss'],
        'security_definition_flow' => ['implicit', 'password', 'application', 'accessCode'],
        'security_definition_in' => ['query', 'header'],
        'security_definition_type' => ['basic', 'apiKey', 'oauth2']
    ];

    public const PATH_PARAM_REGEXP = '#(?<={)[^/}]+(?=})#';
    public const REF_REGEXP = '/^(.+\.json)?(?:#\/(.+)\/(.+))?/';
    public const ADDITIONAL_FIELD_REGEXP = '/^x-/';

    public const MIME_TYPE_MULTIPART_FORM_DATA = 'multipart/form-data';
    public const MIME_TYPE_APPLICATION_URLENCODED = 'application/x-www-form-urlencoded';

    /**
     * @var array
     */
    protected $doc;

    public function validate(array $doc): void
    {
        $this->doc = $doc;

        $this->validateVersion();
        $this->validateFieldsPresent(self::REQUIRED_FIELDS['doc']);
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

        if (version_compare($version, SwaggerService::SWAGGER_VERSION, '!=')) {
            throw new InvalidSwaggerVersionException($version);
        }
    }

    protected function validateInfo(): void
    {
        $this->validateFieldsPresent(self::REQUIRED_FIELDS['info'], 'info');
    }

    protected function validateSchemes(): void
    {
        $this->validateFieldValue('schemes', self::ALLOWED_VALUES['schemes']);
    }

    protected function validatePaths(): void
    {
        foreach ($this->doc['paths'] as $path => $operations) {
            if (!Str::startsWith($path, '/') && !preg_match(self::ADDITIONAL_FIELD_REGEXP, $path)) {
                throw new InvalidPathException("paths.{$path}");
            }

            foreach ($operations as $pathKey => $operation) {
                $operationId = "paths.{$path}.{$pathKey}";

                $this->validateFieldsPresent(self::REQUIRED_FIELDS['operation'], $operationId);
                $this->validateFieldValue("{$operationId}.schemes", self::ALLOWED_VALUES['schemes']);

                $this->validateParameters($operation, $path, $operationId);

                foreach ($operation['responses'] as $statusCode => $response) {
                    $this->validateResponse($response, $statusCode, $operationId);
                }
            }
        }

        $this->validateOperationIdsUnique();
    }

    protected function validateDefinitions(): void
    {
        $definitions = Arr::get($this->doc, 'definitions', []);

        foreach ($definitions as $index => $definition) {
            $this->validateFieldsPresent(self::REQUIRED_FIELDS['definition'], "definitions.{$index}");
        }
    }

    protected function validateSecurityDefinitions(): void
    {
        $securityDefinitions = Arr::get($this->doc, 'securityDefinitions', []);

        foreach ($securityDefinitions as $index => $securityDefinition) {
            $parentId = "securityDefinitions.{$index}";

            $this->validateFieldsPresent(self::REQUIRED_FIELDS['security_definition'], $parentId);

            $this->validateFieldValue("{$parentId}.'type", self::ALLOWED_VALUES['security_definition_type']);
            $this->validateFieldValue("{$parentId}.'in", self::ALLOWED_VALUES['security_definition_in']);
            $this->validateFieldValue("{$parentId}.'flow", self::ALLOWED_VALUES['security_definition_flow']);
        }
    }

    protected function validateTags(): void
    {
        $tags = Arr::get($this->doc, 'tags', []);

        foreach ($tags as $index => $tag) {
            $this->validateFieldsPresent(self::REQUIRED_FIELDS['tag'], "tags.{$index}");
        }

        $this->validateTagsUnique();
    }

    protected function validateResponse(array $response, string $statusCode, string $operationId): void
    {
        $responseId = "{$operationId}.responses.{$statusCode}";

        $this->validateFieldsPresent(self::REQUIRED_FIELDS['response'], $responseId);

        if (
            ($statusCode !== 'default')
            && !preg_match('/^\d{3}$/', $statusCode)
            && !preg_match(self::ADDITIONAL_FIELD_REGEXP, $statusCode)
        ) {
            throw new InvalidStatusCodeException($responseId);
        }

        foreach (Arr::get($response, 'headers', []) as $headerName => $header) {
            $this->validateHeader($header, "{$responseId}.headers.{$headerName}");
        }

        if (!empty($response['schema'])) {
            $this->validateType(
                $response['schema'], array_merge(self::SCHEMA_TYPES, ['file']), "{$responseId}.schema"
            );
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

            $this->validateFieldsPresent(self::REQUIRED_FIELDS['parameter'], $paramId);

            $this->validateFieldValue("{$paramId}.in", self::ALLOWED_VALUES['parameter_in']);
            $this->validateFieldValue(
                "{$paramId}.collectionFormat", self::ALLOWED_VALUES['parameter_collection_format']
            );

            $this->validateParameterType($param, $operation, $paramId, $operationId);

            if (!empty($param['items'])) {
                $this->validateItems($param['items'], "{$paramId}.items");
            }
        }

        $this->validateParamsUnique($parameters, $operationId);

        $this->validatePathParameters($parameters, $path, $operationId);
        $this->validateBodyParameters($parameters, $operationId);
    }

    protected function validateType(array $schema, array $validTypes, string $schemaId): void
    {
        $schemaType = Arr::get($schema, 'type');

        if (!empty($schemaType) && !in_array($schemaType, $validTypes)) {
            throw new InvalidFieldValueException("{$schemaId}.type", $validTypes, [$schema['type']]);
        }

        if (($schemaType === 'array') && empty($schema['items'])) {
            throw new InvalidSwaggerSpecException("{$schemaId} is an array, so it must include an 'items' field.");
        }
    }

    protected function validatePathParameters(array $params, string $path, string $operationId): void
    {
        $pathParams = Arr::where($params, function ($param) {
            return $param['in'] === 'path';
        });

        preg_match_all(self::PATH_PARAM_REGEXP, $path, $matches);
        $placeholders = $matches[0];

        $placeholderDuplicates = $this->getArrayDuplicates($placeholders);

        if (!empty($placeholderDuplicates)) {
            throw new DuplicatePathPlaceholderException($placeholderDuplicates, $path);
        }

        $requiredParams = implode(',', array_filter($pathParams, function ($param) {
            return !Arr::get($param, 'required', false);
        }));

        if (!empty($requiredParams)) {
            throw new InvalidSwaggerSpecException(
                "Path parameters cannot be optional. Set required=true for the "
                . "'{$requiredParams}' parameters at operation '{$operationId}'."
            );
        }

        $missingPlaceholders = array_diff(Arr::pluck($pathParams, 'name'), $placeholders);

        if (!empty($missingPlaceholders)) {
            throw new MissingPathPlaceholderException($operationId, $missingPlaceholders);
        }

        $missingPathParams = array_diff($placeholders, Arr::pluck($pathParams, 'name'));

        if (!empty($missingPathParams)) {
            throw new MissingPathParamException($operationId, $missingPathParams);
        }
    }

    protected function validateBodyParameters(array $parameters, string $operationId): void
    {
        $bodyParamsCount = collect($parameters)->where('in', 'body')->count();
        $formParamsCount = collect($parameters)->where('in', 'formData')->count();

        if ($bodyParamsCount > 1) {
            throw new InvalidSwaggerSpecException(
                "Operation '{$operationId}' has {$bodyParamsCount} body parameters. Only one is allowed."
            );
        }

        if ($bodyParamsCount && $formParamsCount) {
            throw new InvalidSwaggerSpecException(
                "Operation '{$operationId}' has body and formData parameters. Only one or the other is allowed."
            );
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
                $this->validateFormDataConsumes($operation, $operationId);

                $requiredFields = ['type'];
                $validTypes = array_merge(self::PRIMITIVE_TYPES, ['file']);
                break;
            default:
                $requiredFields = ['type'];
                $validTypes = self::PRIMITIVE_TYPES;
        }

        $this->validateFieldsPresent($requiredFields, $paramId);

        $schema = Arr::get($param, 'schema', $param);
        $this->validateType($schema, $validTypes, $paramId);
    }

    protected function validateHeader(array $header, string $headerId): void
    {
        $this->validateFieldsPresent(self::REQUIRED_FIELDS['header'], $headerId);

        $this->validateType($header, self::PRIMITIVE_TYPES, $headerId);
        $this->validateFieldValue("{$headerId}.collectionFormat", self::ALLOWED_VALUES['header_collection_format']);

        if (!empty($header['items'])) {
            $this->validateItems($header['items'], $headerId);
        }
    }

    protected function validateItems(array $items, string $itemsId): void
    {
        $this->validateFieldsPresent(self::REQUIRED_FIELDS['item'], $itemsId);

        $this->validateType($items, self::PRIMITIVE_TYPES, $itemsId);
        $this->validateFieldValue("{$itemsId}.collectionFormat", self::ALLOWED_VALUES['items_collection_format']);
    }

    protected function getMissingFields(array $requiredFields, ?string $fieldName = null, ?array $doc = null): array
    {
        return array_diff(
            $requiredFields,
            array_keys(Arr::get($doc ?? $this->doc, $fieldName))
        );
    }

    protected function validateFieldsPresent(array $requiredFields, ?string $fieldName = null): void
    {
        $missingDocFields = $this->getMissingFields($requiredFields, $fieldName);

        if (!empty($missingDocFields)) {
            throw new MissingFieldException($missingDocFields, $fieldName);
        }
    }

    protected function validateFieldValue(string $fieldName, array $allowedValues): void
    {
        $inputValue = Arr::wrap(Arr::get($this->doc, $fieldName, []));
        $approvedValues = array_intersect($inputValue, $allowedValues);
        $invalidValues = array_diff($inputValue, $approvedValues);

        if (!empty($invalidValues)) {
            throw new InvalidFieldValueException($fieldName, $allowedValues, $invalidValues);
        }
    }

    protected function validateRefs(): void
    {
        array_walk_recursive($this->doc, function ($item, $key) {
            if (
                ($key === '$ref')
                && preg_match(self::REF_REGEXP, $item, $matches)
            ) {
                $refFilename = Arr::get($matches, 1);
                $refParentKey = Arr::get($matches, 2);
                $refKey = Arr::get($matches, 3);

                if (!empty($refFilename) && !file_exists($refFilename)) {
                    throw new MissingRefFileException($refFilename);
                }

                if (!empty($refFilename)) {
                    $externalDoc = json_decode(file_get_contents($refFilename), true);

                    $missingRefs = $this->getMissingFields([$refKey], $refParentKey, $externalDoc);

                    if (!empty($missingRefs)) {
                        throw new MissingExternalRefException($refKey, $refFilename);
                    }
                } else {
                    $missingRefs = $this->getMissingFields([$refKey], $refParentKey);

                    if (!empty($missingRefs)) {
                        throw new MissingLocalRefException($refKey, $refParentKey);
                    }
                }
            }
        });
    }

    protected function validateParamsUnique(array $params, string $operationId): void
    {
        for ($i = 0; $i < count($params) - 1; $i++) {
            $outer = $params[$i];

            for ($j = $i + 1; $j < count($params); $j++) {
                $inner = $params[$j];

                if (($outer['name'] === $inner['name']) && ($outer['in'] === $inner['in'])) {
                    throw new DuplicateParamException($outer['in'], $outer['name'], $operationId);
                }
            }
        }
    }

    protected function validateTagsUnique(): void
    {
        $tags = Arr::get($this->doc, 'tags', []);
        $tagNames = Arr::flatten(Arr::pluck($tags, 'name'));
        $duplicates = $this->getArrayDuplicates($tagNames);

        if (!empty($duplicates)) {
            throw new DuplicateFieldException('tags.*.name', $duplicates);
        }
    }

    protected function validateOperationIdsUnique(): void
    {
        $operationIds = Arr::pluck(Arr::get($this->doc, 'paths', []), '*.operationId');
        $operationIds = Arr::flatten($operationIds);
        $duplicates = $this->getArrayDuplicates($operationIds);

        if (!empty($duplicates)) {
            throw new DuplicateFieldException('paths.*.*.operationId', $duplicates);
        }
    }

    protected function validateFormDataConsumes(array $operation, string $operationId): void
    {
        $requiredConsume = Arr::first(
            Arr::get($operation, 'consumes', []),
            function ($consume) {
                return in_array($consume, [
                    self::MIME_TYPE_APPLICATION_URLENCODED, self::MIME_TYPE_MULTIPART_FORM_DATA
                ]);
            }
        );

        if (empty($requiredConsume)) {
            throw new InvalidSwaggerSpecException(
                "Operation '{$operationId}' has body and formData parameters. Only one or the other is allowed."
            );
        }
    }

    protected function getArrayDuplicates(array $array): array
    {
        $array = array_filter($array);
        $duplicates = array_filter(array_count_values($array), function ($value) {
            return $value > 1;
        });

        return array_keys($duplicates);
    }
}
