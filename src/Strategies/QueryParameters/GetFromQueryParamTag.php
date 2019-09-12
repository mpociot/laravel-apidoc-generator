<?php

namespace Mpociot\ApiDoc\Strategies\QueryParameters;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;
use Mpociot\ApiDoc\Strategies\Strategy;
use Mpociot\ApiDoc\Tools\RouteDocBlocker;
use Dingo\Api\Http\FormRequest as DingoFormRequest;
use Mpociot\ApiDoc\Tools\Traits\DocBlockParamHelpers;
use Illuminate\Foundation\Http\FormRequest as LaravelFormRequest;

class GetFromQueryParamTag extends Strategy
{
    use DocBlockParamHelpers;

    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        foreach ($method->getParameters() as $param) {
            $paramType = $param->getType();
            if ($paramType === null) {
                continue;
            }

            $parameterClassName = version_compare(phpversion(), '7.1.0', '<')
                ? $paramType->__toString()
                : $paramType->getName();

            try {
                $parameterClass = new ReflectionClass($parameterClassName);
            } catch (\ReflectionException $e) {
                continue;
            }

            // If there's a FormRequest, we check there for @queryParam tags.
            if (class_exists(LaravelFormRequest::class) && $parameterClass->isSubclassOf(LaravelFormRequest::class)
                || class_exists(DingoFormRequest::class) && $parameterClass->isSubclassOf(DingoFormRequest::class)) {
                $formRequestDocBlock = new DocBlock($parameterClass->getDocComment());
                $queryParametersFromDocBlock = $this->getqueryParametersFromDocBlock($formRequestDocBlock->getTags());

                if (count($queryParametersFromDocBlock)) {
                    return $queryParametersFromDocBlock;
                }
            }
        }

        $methodDocBlock = RouteDocBlocker::getDocBlocksFromRoute($route)['method'];

        return $this->getqueryParametersFromDocBlock($methodDocBlock->getTags());
    }

    private function getQueryParametersFromDocBlock($tags)
    {
        $parameters = collect($tags)
            ->filter(function ($tag) {
                return $tag instanceof Tag && $tag->getName() === 'queryParam';
            })
            ->mapWithKeys(function ($tag) {
                preg_match('/(.+?)\s+(required\s+)?(.*)/', $tag->getContent(), $content);
                $content = preg_replace('/\s?No-example.?/', '', $content);
                if (empty($content)) {
                    // this means only name was supplied
                    list($name) = preg_split('/\s+/', $tag->getContent());
                    $required = false;
                    $description = '';
                } else {
                    list($_, $name, $required, $description) = $content;
                    $description = trim($description);
                    if ($description == 'required' && empty(trim($required))) {
                        $required = $description;
                        $description = '';
                    }
                    $required = trim($required) == 'required' ? true : false;
                }

                list($description, $value) = $this->parseParamDescription($description, 'string');
                if (is_null($value) && ! $this->shouldExcludeExample($tag)) {
                    $value = Str::contains($description, ['number', 'count', 'page'])
                        ? $this->generateDummyValue('integer')
                        : $this->generateDummyValue('string');
                }

                return [$name => compact('description', 'required', 'value')];
            })->toArray();

        return $parameters;
    }
}
