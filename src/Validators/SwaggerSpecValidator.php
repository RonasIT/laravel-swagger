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
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissedDocDefinitionsException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\MissedDocFieldException;
use RonasIT\Support\AutoDoc\Exceptions\SpecValidation\PathParamMissingException;
use RonasIT\Support\AutoDoc\Interfaces\DocValidator;

class SwaggerSpecValidator implements DocValidator
{
    const SCHEMA_TYPES = [
        self::SCHEMA_TYPE_ARRAY,
        'boolean',
        'integer',
        'number',
        'string',
        'object',
        'null',
        'undefined'
    ];
    const SCHEMA_TYPE_ARRAY = 'array';

    const PRIMITIVE_TYPES = [
        'array', 'boolean', 'integer', 'number', 'string'
    ];

    const REQUIRED_DOC_FIELDS = [
        'swagger', 'info', 'paths'
    ];

    const REQUIRED_INFO_FIELDS = [
        'title', 'version'
    ];

    const REQUIRED_ENDPOINT_FIELDS = [
        'responses'
    ];

    const REQUIRED_PARAMETER_FIELDS = [
        'in', 'name'
    ];

    const REQUIRED_RESPONSE_FIELDS = [
        'description'
    ];

    const REQUIRED_DEFINITION_FIELDS = [
        'type'
    ];

    const REQUIRED_TAG_FIELDS = [
        'name'
    ];

    const REQUIRED_SECURITY_DEFINITIONS_FIELDS = [
        'type'
    ];

    const PARAMETER_IN_BODY = 'body';
    const PARAMETER_IN_PATH = 'path';
    const PARAMETER_IN_QUERY = 'query';
    const PARAMETER_IN_HEADER = 'header';
    const PARAMETER_IN_FORM_DATA = 'formData';

    const AVAILABLE_PARAMETER_IN = [
        self::PARAMETER_IN_BODY,
        self::PARAMETER_IN_PATH,
        self::PARAMETER_IN_QUERY,
        self::PARAMETER_IN_HEADER,
        self::PARAMETER_IN_FORM_DATA
    ];

    const AVAILABLE_PARAMETER_TYPE = [
        'string', 'number', 'integer', 'boolean', 'array', 'file'
    ];

    const AVAILABLE_SECURITY_DEFINITIONS_TYPE = [
        'basic', 'apiKey', 'oauth2'
    ];
    const AVAILABLE_SECURITY_DEFINITIONS_IN = [
        'query', 'header'
    ];
    const AVAILABLE_SECURITY_DEFINITIONS_FLOW = [
        'implicit', 'password', 'application', 'accessCode'
    ];

    const AVAILABLE_SCHEMES = [
        'http', 'https', 'ws', 'wss'
    ];

    const SUPPORTED_HTTP_METHODS = [
        'get', 'post', 'put', 'patch', 'delete', 'head', 'options'
    ];

    const PATH_PARAM_REGEXP = '#(?<={)[^/}]+(?=})#';

    public function validate(array $doc): void
    {
        $this->validateVersion($doc);

        $missedDocFields = $this->getMissedFields($doc, self::REQUIRED_DOC_FIELDS);

        if (!empty($missedDocFields)) {
            throw new MissedDocFieldException($missedDocFields);
        }

        $this->validateInfo($doc);
        $this->validateSchemes($doc);
        $this->validatePaths($doc);
        $this->validateDefinitions($doc);
        $this->validateSecurityDefinitions($doc);
        $this->validateTags($doc);
    }

    protected function validateVersion(array $doc): void
    {
        if (!empty($doc['swagger'])) {
            if (
                version_compare($doc['swagger'], '2.0', '<')
                || version_compare($doc['swagger'], '3.0', '>=')
            ) {
                throw new InvalidSwaggerVersionException($doc['swagger']);
            }
        } else {
            throw new MissedDocFieldException(['swagger']);
        }
    }

    protected function validateInfo(array $doc): void
    {
        $missedInfoFields = $this->getMissedFields($doc['info'], self::REQUIRED_INFO_FIELDS);

        if (!empty($missedInfoFields)) {
            throw new MissedDocFieldException($missedInfoFields, 'info');
        }
    }

    protected function validateSchemes(array $doc): void
    {
        $notAvailableSchemes = $this->getInvalidFieldValues(Arr::get($doc, 'schemes', []), self::AVAILABLE_SCHEMES);

        if (!empty($notAvailableSchemes)) {
            throw new InvalidDocFieldValueException('schemes', $notAvailableSchemes);
        }
    }

    protected function validatePaths(array $doc): void
    {
        foreach ($doc['paths'] as $path => $endpoints) {
            $this->checkForPlaceholderDuplicates($path);

            foreach ($endpoints as $method => $endpoint) {
                $endpointId = $path . '.' . $method;

                if (!in_array($method, self::SUPPORTED_HTTP_METHODS)) {
                    throw new InvalidHttpMethodException($method, $path);
                }

                $missedEndpointFields = $this->getMissedFields($endpoint, self::REQUIRED_ENDPOINT_FIELDS);

                if (!empty($missedEndpointFields)) {
                    throw new MissedDocFieldException($missedEndpointFields, $endpointId);
                }

                $this->validateParameters(Arr::get($endpoint, 'parameters', []), $path, $endpointId);

                foreach ($endpoint['responses'] as $statusCode => $response) {
                    $this->validateResponse($response, $statusCode, $endpointId);
                }
            }
        }
    }

    protected function validateDefinitions(array $doc): void
    {
        $definitions = Arr::get($doc, 'definitions', []);

        foreach ($definitions as $index => $definition) {
            $missedDefinitionFields = $this->getMissedFields($definition, self::REQUIRED_DEFINITION_FIELDS);

            if (!empty($missedDefinitionFields)) {
                throw new MissedDocFieldException($missedDefinitionFields, "definitions.{$index}");
            }
        }

        $refs = [];
        array_walk_recursive($doc, function ($item, $key) use (&$refs) {
            if ($key === '$ref') {
                $refs[] = str_replace('#/definitions/', '', $item);
            }
        });

        $missedDefinitionObjects = $this->getMissedFields($definitions, $refs);

        if (!empty($missedDefinitionObjects)) {
            throw new MissedDocDefinitionsException($missedDefinitionObjects);
        }
    }

    protected function validateSecurityDefinitions(array $doc): void
    {
        foreach ($doc['securityDefinitions'] as $index => $securityDefinition) {
            $fieldId = "securityDefinitions.{$index}";
            $missedSecurityDefinitionFields = $this->getMissedFields($securityDefinition, self::REQUIRED_SECURITY_DEFINITIONS_FIELDS);

            if (!empty($missedSecurityDefinitionFields)) {
                throw new MissedDocFieldException($missedSecurityDefinitionFields, $fieldId);
            }

            if (!in_array($securityDefinition['type'], self::AVAILABLE_SECURITY_DEFINITIONS_TYPE)) {
                throw new InvalidDocFieldValueException("{$fieldId}.type", $securityDefinition['type']);
            }

            if (
                !empty($securityDefinition['in'])
                && !in_array($securityDefinition['in'], self::AVAILABLE_SECURITY_DEFINITIONS_IN)
            ) {
                throw new InvalidDocFieldValueException("{$fieldId}.in", $securityDefinition['in']);
            }

            if (
                !empty($securityDefinition['flow'])
                && !in_array($securityDefinition['flow'], self::AVAILABLE_SECURITY_DEFINITIONS_FLOW)
            ) {
                throw new InvalidDocFieldValueException("{$fieldId}.flow", $securityDefinition['flow']);
            }
        }
    }

    protected function validateTags(array $doc): void
    {
        foreach ($doc['tags'] as $index => $tag) {
            $missedTagFields = $this->getMissedFields($tag, self::REQUIRED_TAG_FIELDS);

            if (!empty($missedTagFields)) {
                throw new MissedDocFieldException($missedTagFields, "tags.{$index}");
            }
        }
    }

    protected function validateResponse(array $response, string $statusCode, string $endpointId): void
    {
        $responseId = $endpointId . '.responses.' . $statusCode;

        $missedResponseFields = $this->getMissedFields($response, self::REQUIRED_RESPONSE_FIELDS);

        if (!empty($missedResponseFields)) {
            throw new MissedDocFieldException($missedResponseFields, $responseId);
        }

        if (($statusCode < 100) || ($statusCode > 599)) {
            throw new InvalidResponseCodeException($statusCode, $responseId);
        }

        foreach (Arr::get($response, 'headers', []) as $headerName => $header) {
            $this->validateSchema($header, self::PRIMITIVE_TYPES, "{$responseId}.headers.{$headerName}");
        }

        if (!empty($response['schema'])) {
            $validSchemaTypes = array_merge(self::SCHEMA_TYPES, ['files']);

            $this->validateSchema($response['schema'], $validSchemaTypes, $responseId . '.schema');
        }
    }

    protected function validateParameters(array $parameters, string $path, string $endpointId): void
    {
        foreach ($parameters as $index => $param) {
            $paramId = $endpointId . '.parameters.' . $index;

            $missedParamFields = $this->getMissedFields($param, self::REQUIRED_PARAMETER_FIELDS);

            if (!empty($missedParamFields)) {
                throw new MissedDocFieldException($missedParamFields, "{$endpointId}.parameters");
            }

            if (!in_array($param['in'], self::AVAILABLE_PARAMETER_IN)) {
                throw new InvalidDocFieldValueException("{$paramId}.in", $param['in']);
            }

            if (
                !empty($param['type'])
                && !in_array($param['type'], self::AVAILABLE_PARAMETER_TYPE)
            ) {
                throw new InvalidDocFieldValueException("{$paramId}.type", $param['type']);
            }
        }

        $this->validateParamDuplicates($parameters);

        $this->validatePathParameters($parameters, $path, $endpointId);
        $this->validateBodyParameters($parameters, $endpointId);

        $this->validateParameterTypes();
    }

    protected function validateSchema(array $schema, array $validTypes, string $schemaId): void
    {
        $schemaType = Arr::get($schema, 'type');

        if (!empty($schemaType) && !in_array($schemaType, $validTypes)) {
            throw new InvalidDocFieldValueException("{$schemaId}.type", $schema['type']);
        }

        if (($schemaType === self::SCHEMA_TYPE_ARRAY) && empty($schema['items'])) {
            throw new InvalidSwaggerSpecException("Validation failed. {$schemaId} is an array, so it must include an 'items' field.");
        }
    }

    protected function getInvalidFieldValues(array $values, array $validValues): array
    {
        $approvedValues = array_intersect($values, $validValues);

        return array_diff($values, $approvedValues);
    }

    protected function getMissedFields(array $parentField, array $requiredFields): array
    {
        return array_diff($requiredFields, array_keys($parentField));
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

    protected function validatePathParameters(array $params, string $path, string $endpointId): void
    {
        $pathParams = Arr::where($params, function ($param) {
            return $param['in'] === 'path';
        });

        preg_match_all(self::PATH_PARAM_REGEXP, $path, $placeholders);

        foreach ($pathParams as $param) {
            if (Arr::get($param, 'required', false)) {
                throw new InvalidSwaggerSpecException("Validation failed. Path parameters cannot be optional. Set required=true for the {$param['name']} parameter at {$endpointId}");
            }

            $placeholderIndex = array_search($param['name'], $placeholders);
            
            if ($placeholderIndex === false) {
                throw new InvalidSwaggerSpecException("Validation failed. {$endpointId} has a path parameter named {$param['name']}, but there is no corresponding {$param['name']} in the path string");
            }

            unset($placeholders[$placeholderIndex]);
        }

        if (!empty($placeholders)) {
            throw new PathParamMissingException($path, $placeholders);
        }
    }

    protected function validateBodyParameters(array $parameters, string $endpointId): void
    {
        $bodyParams = Arr::where($parameters, function ($param) {
            return $param['in'] === 'body';
        });
        $formParams = Arr::where($parameters, function ($param) {
            return $param['in'] === 'formData';
        });

        $bodyParamsCount = count($bodyParams);

        if ($bodyParamsCount > 1) {
            throw new InvalidSwaggerSpecException("Validation failed. Endpoint {$endpointId} has {$bodyParamsCount} body parameters. Only one is allowed.");
        }

        if (!empty($bodyParams) && !empty($formParams)) {
            throw new InvalidSwaggerSpecException("Validation failed. Endpoint {$endpointId} has body parameters and formData parameters. Only one or the other is allowed.");
        }
    }

    protected function checkForPlaceholderDuplicates(string $path): void
    {
        preg_match_all(self::PATH_PARAM_REGEXP, $path, $placeholders);

        $duplicatedPlaceholders = array_get_duplicates($placeholders);

        if (!empty($duplicatedPlaceholders)) {
            throw new DuplicatedPathPlaceholderException($duplicatedPlaceholders, $path);
        }
    }

    protected function validateParameterTypes(): void
    {
        // TODO
    }
}