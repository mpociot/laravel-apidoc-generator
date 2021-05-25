<?php

namespace Mpociot\ApiDoc\Extracting\Strategies\RequestHeaders;

use Dingo\Api\Http\FormRequest as DingoFormRequest;
use Illuminate\Foundation\Http\FormRequest as LaravelFormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Mpociot\ApiDoc\Extracting\ParamHelpers;
use Mpociot\ApiDoc\Extracting\RouteDocBlocker;
use Mpociot\ApiDoc\Extracting\Strategies\Strategy;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;
use ReflectionClass;
use ReflectionMethod;

class GetFromHeaderTag extends Strategy
{
    use ParamHelpers;

    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        foreach ($method->getParameters() as $param) {
            $paramType = $param->getType();
            if ($paramType === null) {
                continue;
            }

            $parameterClassName = $paramType->getName();

            try {
                $parameterClass = new ReflectionClass($parameterClassName);
            } catch (\ReflectionException $e) {
                continue;
            }

            // If there's a FormRequest, we check there for @urlParam tags.
            if (class_exists(LaravelFormRequest::class) && $parameterClass->isSubclassOf(LaravelFormRequest::class)
                || class_exists(DingoFormRequest::class) && $parameterClass->isSubclassOf(DingoFormRequest::class)) {
                $formRequestDocBlock = new DocBlock($parameterClass->getDocComment());
                $headersFromDocBlock = $this->getHeadersFromDocBlock($formRequestDocBlock->getTags());

                if (count($headersFromDocBlock)) {
                    return $headersFromDocBlock;
                }
            }
        }

        /** @var DocBlock $methodDocBlock */
        $methodDocBlock = RouteDocBlocker::getDocBlocksFromRoute($route)['method'];

        return $this->getHeadersFromDocBlock($methodDocBlock->getTags());
    }

    private function getHeadersFromDocBlock($tags)
    {
        $parameters = collect($tags)
            ->filter(function ($tag) {
                return $tag instanceof Tag && $tag->getName() === 'header';
            })
            ->mapWithKeys(function (Tag $tag) {
                // Format:
                // @header <content>
                // Examples:
                // @header Cookie foo Example: crumbs
                preg_match('/(.+?)\s+(.*)/', $tag->getContent(), $content);
                list($content, $name, $value) = $content;
                return [$name => $value];
            })->toArray();

        return $parameters;
    }
}
