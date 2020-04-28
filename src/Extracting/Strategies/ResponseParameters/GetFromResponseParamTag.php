<?php

namespace Mpociot\ApiDoc\Extracting\Strategies\ResponseParameters;

use Dingo\Api\Http\FormRequest as DingoFormRequest;
use Illuminate\Foundation\Http\FormRequest as LaravelFormRequest;
use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Extracting\ParamHelpers;
use Mpociot\ApiDoc\Extracting\RouteDocBlocker;
use Mpociot\ApiDoc\Extracting\Strategies\Strategy;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;
use ReflectionClass;
use ReflectionMethod;

class GetFromResponseParamTag extends Strategy
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

            // If there's a FormRequest, we check there for @bodyParam tags.
            if (class_exists(LaravelFormRequest::class) && $parameterClass->isSubclassOf(LaravelFormRequest::class)
                || class_exists(DingoFormRequest::class) && $parameterClass->isSubclassOf(DingoFormRequest::class)) {
                $formRequestDocBlock = new DocBlock($parameterClass->getDocComment());
                $bodyParametersFromDocBlock = $this->getBodyParametersFromDocBlock($formRequestDocBlock->getTags());

                if (count($bodyParametersFromDocBlock)) {
                    return $bodyParametersFromDocBlock;
                }
            }
        }

        /** @var DocBlock $methodDocBlock */
        $methodDocBlock = RouteDocBlocker::getDocBlocksFromRoute($route)['method'];

        return $this->getBodyParametersFromDocBlock($methodDocBlock->getTags());
    }

    private function getBodyParametersFromDocBlock($tags)
    {
        $parameters = collect($tags)
            ->filter(function ($tag) {
                return $tag instanceof Tag && $tag->getName() === 'responseParam';
            })
            ->mapWithKeys(function (Tag $tag) {
                // Format:
                // @responseParam <name> <type> <description>
                // Examples:
                // @responseParam user_id integer The ID of the user.
                preg_match('/(.+?)\s+(.+?)\s+(.*)/', $tag->getContent(), $content);
                $content = preg_replace('/\s?No-example.?/', '', $content);
                if (empty($content)) {
                    // this means only name and type were supplied
                    list($name, $type) = preg_split('/\s+/', $tag->getContent());
                } else {
                    list($_, $name, $type, $description) = $content;
                    $description = trim($description);
                }

                $type = $this->normalizeParameterType($type);
                list($description, $example) = $this->parseParamDescription($description, $type);
                $value = is_null($example) && ! $this->shouldExcludeExample($tag->getContent())
                    ? $this->generateDummyValue($type)
                    : $example;

                return [$name => compact('type', 'description', 'value')];
            })->toArray();

        return $parameters;
    }
}
