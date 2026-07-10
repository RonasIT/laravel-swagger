<?php

namespace RonasIT\AutoDoc\Support\Factories;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ReflectionClass;
use RonasIT\AutoDoc\DTO\RequestData;

class RequestDataFactory
{
    protected array $booleanAnnotations = [
        'deprecated',
    ];

    public function make(Request $request, ?string $requestClass): RequestData
    {
        if (empty($requestClass)) {
            return new RequestData(
                payload: $request->all(),
                contentType: $request->header('Content-Type'),
                rules: [],
                attributes: [],
                annotations: [],
            );
        }

        $formRequest = new $requestClass();
        $formRequest->setUserResolver($request->getUserResolver());
        $formRequest->setRouteResolver($request->getRouteResolver());

        $rules = method_exists($formRequest, 'rules') ? $this->prepareRules($formRequest->rules()) : [];
        $attributes = method_exists($formRequest, 'attributes') ? $formRequest->attributes() : [];

        return new RequestData(
            payload: $request->all(),
            contentType: $request->header('Content-Type'),
            rules: $rules,
            attributes: $attributes,
            annotations: $this->getClassAnnotations($requestClass),
        );
    }

    protected function prepareRules(array $rules): array
    {
        $preparedRules = [];

        foreach ($rules as $field => $rulesField) {
            if (is_array($rulesField)) {
                $rulesField = array_map(fn ($rule) => $this->getRuleAsString($rule), $rulesField);

                $preparedRules[$field] = implode('|', $rulesField);
            } else {
                $preparedRules[$field] = $this->getRuleAsString($rulesField);
            }
        }

        return $preparedRules;
    }

    protected function getRuleAsString(string|object $rule): string
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

    protected function getClassAnnotations(string $class): array
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
}
