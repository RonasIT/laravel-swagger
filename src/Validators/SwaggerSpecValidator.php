<?php

namespace RonasIT\AutoDoc\Validators;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RonasIT\AutoDoc\Exceptions\SpecValidation\DuplicateFieldException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\DuplicateParamException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\DuplicatePathPlaceholderException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\InvalidPathException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\InvalidFieldValueException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\InvalidStatusCodeException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\InvalidSwaggerSpecException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\InvalidSwaggerVersionException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingExternalRefException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingLocalRefException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingFieldException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingPathParamException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingPathPlaceholderException;
use RonasIT\AutoDoc\Exceptions\SpecValidation\MissingRefFileException;
use RonasIT\AutoDoc\Services\SwaggerService;

/**
 * @property array $doc
 */
class SwaggerSpecValidator
{
    public const SCHEMA_TYPES = [
        'array',
        'boolean',
        'integer',
        'number',
        'string',
        'object',
        'null',
        'undefined',
    ];

    public const PRIMITIVE_TYPES = [
        'array',
        'boolean',
        'integer',
        'number',
        'string',
        'object',
        'date',
        'double',
    ];

    public const REQUIRED_FIELDS = [
        'components' => ['type'],
        'doc' => ['openapi', 'info', 'paths'],
        'info' => ['title', 'version'],
        'item' => ['type'],
        'header' => ['type'],
        'operation' => ['responses'],
        'parameter' => ['in', 'name'],
        'requestBody' => ['content'],
        'response' => ['description'],
        'security_definition' => ['type'],
        'tag' => ['name'],
    ];

    public const ALLOWED_VALUES = [
        'parameter_collection_format' => ['csv', 'ssv', 'tsv', 'pipes', 'multi'],
        'items_collection_format' => ['csv', 'ssv', 'tsv', 'pipes'],
        'header_collection_format' => ['csv', 'ssv', 'tsv', 'pipes'],
        'parameter_in' => ['body', 'formData', 'query', 'path', 'header'],
        'schemes' => ['http', 'https', 'ws', 'wss'],
        'security_definition_flow' => ['implicit', 'password', 'application', 'accessCode'],
        'security_definition_in' => ['query', 'header'],
        'security_definition_type' => ['basic', 'apiKey', 'oauth2'],
    ];

    public const ALLOWED_TYPES = [
        self::MIME_TYPE_APPLICATION_URLENCODED,
        self::MIME_TYPE_MULTIPART_FORM_DATA,
        self::MIME_TYPE_APPLICATION_JSON,
    ];

    public const PATH_PARAM_REGEXP = '#(?<={)[^/}]+(?=})#';
    public const PATH_REGEXP = '/^x-/';

    public const MIME_TYPE_MULTIPART_FORM_DATA = 'multipart/form-data';
    public const MIME_TYPE_APPLICATION_URLENCODED = 'application/x-www-form-urlencoded';
    public const MIME_TYPE_APPLICATION_JSON = 'application/json';

    protected $doc;

    public function validate(array $doc): void
    {
        $this->doc = $doc;

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
        $version = Arr::get($this->doc, 'openapi', '');

        if (version_compare($version, SwaggerService::OPEN_API_VERSION, '!=')) {
            throw new InvalidSwaggerVersionException($version);
        }
    }

    protected function validateInfo(): void
    {
        $this->validateFieldsPresent(Arr::get($this->doc, 'info', []), self::REQUIRED_FIELDS['info'], 'info');
    }

    protected function validateSchemes(): void
    {
        $this->validateFieldValue($this->doc, 'schemes', self::ALLOWED_VALUES['schemes']);
    }

    protected function validatePaths(): void
    {
        foreach ($this->doc['paths'] as $path => $operations) {
            if (!Str::startsWith($path, '/') && !preg_match(self::PATH_REGEXP, $path)) {
                throw new InvalidPathException("paths.{$path}");
            }

            foreach ($operations as $pathKey => $operation) {
                $operationId = "paths.{$path}.{$pathKey}";

                $this->validateFieldsPresent($operation, self::REQUIRED_FIELDS['operation'], $operationId);

                $this->validateFieldValue($operation, 'schemes', self::ALLOWED_VALUES['schemes'], $operationId);

                $this->validateParameters($operation, $path, $operationId);

                if (!empty($operation['requestBody'])) {
                    $this->validateRequestBody($operation, $operationId);
                }

                foreach ($operation['responses'] as $statusCode => $response) {
                    $this->validateResponse($response, $statusCode, $operationId);
                }
            }
        }

        $this->validateOperationIdsUnique();
    }

    protected function validateDefinitions(): void
    {
        $definitions = Arr::get($this->doc, 'components.schemas', []);

        foreach ($definitions as $index => $definition) {
            $this->validateFieldsPresent($definition, self::REQUIRED_FIELDS['components'], "components.schemas.{$index}");
        }
    }

    protected function validateSecurityDefinitions(): void
    {
        $securityDefinitions = Arr::get($this->doc, 'securityDefinitions', []);

        foreach ($securityDefinitions as $index => $securityDefinition) {
            $parentId = "securityDefinitions.{$index}";

            $this->validateFieldsPresent($securityDefinition, self::REQUIRED_FIELDS['security_definition'], $parentId);

            $this->validateFieldValue($securityDefinition, 'type', self::ALLOWED_VALUES['security_definition_type'], $parentId);
            $this->validateFieldValue($securityDefinition, 'in', self::ALLOWED_VALUES['security_definition_in'], $parentId);
            $this->validateFieldValue($securityDefinition, 'flow', self::ALLOWED_VALUES['security_definition_flow'], $parentId);
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

        if (
            ($statusCode !== 'default')
            && !$this->isValidStatusCode($statusCode)
            && !preg_match(self::PATH_REGEXP, $statusCode)
        ) {
            throw new InvalidStatusCodeException($responseId);
        }

        foreach (Arr::get($response, 'headers', []) as $headerName => $header) {
            $this->validateHeader($header, "{$responseId}.headers.{$headerName}");
        }

        if (!empty($response['schema'])) {
            $this->validateType(
                $response['schema'],
                array_merge(self::SCHEMA_TYPES, ['file']),
                "{$responseId}.schema"
            );

            if (!empty($response['schema']['items'])) {
                $this->validateItems($response['schema']['items'], "{$responseId}.schema.items");
            }
        }
    }

    protected function validateParameters(array $operation, string $path, string $operationId): void
    {
        $parameters = Arr::get($operation, 'parameters', []);

        foreach ($parameters as $index => $param) {
            $paramId = "{$operationId}.parameters.{$index}";

            $this->validateFieldsPresent($param, self::REQUIRED_FIELDS['parameter'], $paramId);

            $this->validateFieldValue($param, 'in', self::ALLOWED_VALUES['parameter_in'], $paramId);
            $this->validateFieldValue($param, 'collectionFormat', self::ALLOWED_VALUES['parameter_collection_format'], $paramId);

            $this->validateParameterType($param, $operation, $paramId, $operationId);

            if (!empty($param['schema']['items'])) {
                $this->validateItems($param['schema']['items'], "{$paramId}.schema.items");
            }
        }

        $this->validateParamsUnique($parameters, $operationId);
        $this->validatePathParameters($parameters, $path, $operationId);
        $this->validateBodyParameters($parameters, $operationId);
    }

    protected function validateRequestBody(array $operation, string $operationId): void
    {
        $requestBody = Arr::get($operation, 'requestBody', []);

        $this->validateFieldsPresent($requestBody, self::REQUIRED_FIELDS['requestBody'], "{$operationId}.requestBody");

        $this->validateRequestBodyContent($requestBody['content'], $operationId);
    }

    protected function validateRequestBodyContent(array $content, string $operationId): void
    {
        $invalidContentTypes = array_diff(array_keys($content), self::ALLOWED_TYPES);

        if (!empty($invalidContentTypes)) {
            $invalidTypes = implode(', ', $invalidContentTypes);

            throw new InvalidSwaggerSpecException(
                "Operation '{$operationId}' has invalid content types: {$invalidTypes}."
            );
        }
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
        $placeholders = Arr::first($matches);

        $placeholderDuplicates = $this->getArrayDuplicates($placeholders);

        if (!empty($placeholderDuplicates)) {
            throw new DuplicatePathPlaceholderException($placeholderDuplicates, $path);
        }

        $missedRequiredParams = array_filter($pathParams, function ($param) use ($placeholders) {
            return Arr::get($param, 'required', false) && !in_array(Arr::get($param, 'name'), $placeholders);
        });

        if (!empty($missedRequiredParams)) {
            $missedRequiredString = implode(',', Arr::pluck($missedRequiredParams, 'name'));

            throw new InvalidSwaggerSpecException(
                "Path parameters cannot be optional. Set required=true for the "
                . "'{$missedRequiredString}' parameters at operation '{$operationId}'."
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

                $requiredFields = ['schema'];
                $validTypes = array_merge(self::PRIMITIVE_TYPES, ['file']);

                break;
            default:
                $requiredFields = ['schema'];
                $validTypes = self::PRIMITIVE_TYPES;
        }

        $this->validateFieldsPresent($param, $requiredFields, $paramId);

        $schema = Arr::get($param, 'schema', $param);
        $this->validateType($schema, $validTypes, $paramId);
    }

    protected function validateHeader(array $header, string $headerId): void
    {
        $this->validateFieldsPresent($header, self::REQUIRED_FIELDS['header'], $headerId);
        $this->validateType($header, self::PRIMITIVE_TYPES, $headerId);
        $this->validateFieldValue($header, 'collectionFormat', self::ALLOWED_VALUES['header_collection_format'], $headerId);

        if (!empty($header['items'])) {
            $this->validateItems($header['items'], "{$headerId}.items");
        }
    }

    protected function validateItems(array $items, string $itemsId): void
    {
        $this->validateFieldsPresent($items, self::REQUIRED_FIELDS['item'], $itemsId);
        $this->validateType($items, self::PRIMITIVE_TYPES, $itemsId);
        $this->validateFieldValue($items, 'collectionFormat', self::ALLOWED_VALUES['items_collection_format'], $itemsId);
    }

    protected function getMissingFields(array $requiredFields, array $doc, ?string $path = null): array
    {
        if (!empty($path)) {
            $segments = explode('/', str_replace('.', '/', $path));

            foreach ($segments as $segment) {
                $doc = Arr::get($doc, $segment, []);
            }
        }

        return array_diff($requiredFields, array_keys($doc));
    }

    protected function validateFieldsPresent(array $data, array $requiredFields, ?string $filedName = null): void
    {
        $missing = array_diff($requiredFields, array_keys($data));

        if (!empty($missing)) {
            throw new MissingFieldException($missing, $filedName);
        }
    }

    protected function validateFieldValue(array $data, string $field, array $allowedValues, ?string $path = null): void
    {
        if (!Arr::has($data, $field)) {
            return;
        }

        $invalidValues = array_diff(Arr::wrap($data[$field]), $allowedValues);

        if (!empty($invalidValues)) {
            throw new InvalidFieldValueException("{$path}.{$field}", $allowedValues, $invalidValues);
        }
    }

    protected function validateRefs(): void
    {
        array_walk_recursive($this->doc, function ($item, $key) {
            if ($key === '$ref') {
                $refParts = explode('#/', $item);
                $refFilename = Arr::first($refParts);

                if (count($refParts) > 1) {
                    $refPath = Arr::last($refParts);
                    $segments = explode('/', $refPath);

                    $refKey = array_pop($segments);
                    $refParentKey = implode('/', $segments);
                }

                if (!empty($refFilename) && !file_exists($refFilename)) {
                    throw new MissingRefFileException($refFilename);
                }

                $missingRefs = $this->getMissingFields(
                    (array) $refKey,
                    !empty($refFilename)
                        ? json_decode(file_get_contents($refFilename), true)
                        : $this->doc,
                    $refParentKey,
                );

                if (!empty($missingRefs)) {
                    if (!empty($refFilename)) {
                        throw new MissingExternalRefException($refKey, $refFilename);
                    } else {
                        throw new MissingLocalRefException($refKey, $refParentKey);
                    }
                }
            }
        });
    }

    protected function validateParamsUnique(array $params, string $operationId): void
    {
        $collection = collect($params);
        $duplicates = $collection->duplicates(function ($item) {
            return $item['in'] . $item['name'];
        });

        if ($duplicates->count()) {
            $duplicateIndex = $duplicates->keys()->first();

            throw new DuplicateParamException($params[$duplicateIndex]['in'], $params[$duplicateIndex]['name'], $operationId);
        }
    }

    protected function validateTagsUnique(): void
    {
        $tags = Arr::get($this->doc, 'tags', []);
        $tagNames = Arr::pluck($tags, 'name');
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
        $consumes = Arr::get($operation, 'consumes', []);

        $requiredConsume = Arr::first($consumes, function ($consume) {
            return in_array($consume, [
                self::MIME_TYPE_APPLICATION_URLENCODED,
                self::MIME_TYPE_MULTIPART_FORM_DATA,
            ]);
        });

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

    protected function isValidStatusCode(string $code): bool
    {
        $code = intval($code);

        return $code >= 100 && $code < 600;
    }
}
