<?php

namespace Mpociot\ApiDoc\Extracting\Strategies;

use Illuminate\Foundation\Http\FormRequest as LaravelFormRequest;
use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Extracting\ParamHelpers;
use ReflectionClass;
use ReflectionMethod;

class FromRequestRulesStrategy extends Strategy
{
    use ParamHelpers;

    public const TYPE_RULES = [
        'accepted' => 'boolean',
        'alpha' => 'string',
        'alpha_dash' => 'string',
        'alpha_num' => 'string',
        'array' => 'array',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'date' => 'date',
        'email' => 'string',
        'file' => 'object',
        'image' => 'object',
        'integer' => 'integer',
        'ip' => 'string',
        'ipv4' => 'string',
        'ipv6' => 'string',
        'json' => 'string',
        'numeric' => 'number',
        'string' => 'string',
        'url' => 'string',
        'uuid' => 'string',
    ];

    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        foreach ($method->getParameters() as $param) {
            $paramType = $param->getType();
            if ($paramType === null) {
                continue;
            }

            $parameterClassName = $paramType->getName();

            $fromRequestClass = $this->extractFromRequestClass($parameterClassName);

            if (count($fromRequestClass)) {
                return $fromRequestClass;
            }
        }

        return [];
    }

    protected function extractFromRequestClass(string $parameterClassName)
    {
        try {
            $parameterClass = new ReflectionClass($parameterClassName);
        } catch (\ReflectionException $e) {
            return null;
        }

        if (
            class_exists(LaravelFormRequest::class)
            && $parameterClass->isSubclassOf(LaravelFormRequest::class)
            && $parameterClass->hasMethod('rules')
        ) {
            try {
                $method = $parameterClass->getMethod('rules');
                $requestInstance = $parameterClass->newInstance();
                $rules = $method->invoke($requestInstance, $method);
                $parametersFromRules = $this->getParametersFromRequestRules($rules);

                if (count($parametersFromRules)) {
                    return $parametersFromRules;
                }
            } catch (\ReflectionException $e) {
                return null;
            }
        }

        return null;
    }

    protected function getParametersFromRequestRules($rules)
    {
        $paramsFromRules = collect($rules)
            ->map(function ($rules) {
                return is_string($rules) ? explode('|', $rules) : $rules;
            })
            ->filter(function ($rules) {
                return is_array($rules);
            })
            ->mapWithKeys(function ($rules, $name) {
                $rules = collect($rules)
                    ->map(function ($rule) {
                        return is_string($rule);
                    })
                    ->all();

                $required = in_array('required', $rules) || in_array('accepted', $rules);

                $typeRules = array_intersect($rules, array_keys(self::TYPE_RULES));
                $type = empty($typeRules)
                    ? null
                    : $this->normalizeParameterType(array_pop($typeRules));

                $value = !empty($type) ? $this->generateDummyValue($type) : null;

                return [ $name => compact('required', 'type', 'value') ];
            })
            ->toArray();

        return $paramsFromRules;
    }

    protected function normalizeParameterType(string $type)
    {
        return $type ? (self::TYPE_RULES[$type] ?? $type) : 'string';
    }
}
