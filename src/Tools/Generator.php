<?php

namespace Mpociot\ApiDoc\Tools;

use Faker\Factory;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Routing\Route;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;
use Mpociot\ApiDoc\Tools\Traits\ParamHelpers;

class Generator
{
    use ParamHelpers;

    /**
     * @param Route $route
     *
     * @return mixed
     */
    public function getUri(Route $route)
    {
        return $route->uri();
    }

    /**
     * @param Route $route
     *
     * @return mixed
     */
    public function getMethods(Route $route)
    {
        return array_diff($route->methods(), ['HEAD']);
    }

    /**
     * @param  \Illuminate\Routing\Route $route
     * @param array $apply Rules to apply when generating documentation for this route
     *
     * @return array
     */
    public function processRoute(Route $route, array $rulesToApply = [])
    {
        $routeAction = $route->getAction();
        list($class, $method) = explode('@', $routeAction['uses']);
        $controller = new ReflectionClass($class);
        $method = $controller->getMethod($method);

        $routeGroup = $this->getRouteGroup($controller, $method);
        $docBlock = $this->parseDocBlock($method);
        $bodyParameters = $this->getBodyParameters($method, $docBlock['tags']);
        $queryParameters = $this->getQueryParametersFromDocBlock($docBlock['tags']);
        $content = ResponseResolver::getResponse($route, $docBlock['tags'], [
            'rules' => $rulesToApply,
            'body' => $bodyParameters,
            'query' => $queryParameters,
        ]);

        $parsedRoute = [
            'id' => md5($this->getUri($route).':'.implode($this->getMethods($route))),
            'group' => $routeGroup,
            'title' => $docBlock['short'],
            'description' => $docBlock['long'],
            'methods' => $this->getMethods($route),
            'uri' => $this->getUri($route),
            'bodyParameters' => $bodyParameters,
            'cleanBodyParameters' => $this->cleanParams($bodyParameters),
            'queryParameters' => $queryParameters,
            'authenticated' => $this->getAuthStatusFromDocBlock($docBlock['tags']),
            'response' => $content,
            'showresponse' => ! empty($content),
        ];
        $parsedRoute['headers'] = $rulesToApply['headers'] ?? [];

        return $parsedRoute;
    }

    protected function getBodyParameters(ReflectionMethod $method, array $tags)
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

            if (class_exists('\Illuminate\Foundation\Http\FormRequest') && $parameterClass->isSubclassOf(\Illuminate\Foundation\Http\FormRequest::class)) {
                $formRequestDocBlock = new DocBlock($parameterClass->getDocComment());
                $bodyParametersFromDocBlock = $this->getBodyParametersFromDocBlock($formRequestDocBlock->getTags());

                if (count($bodyParametersFromDocBlock)) {
                    return $bodyParametersFromDocBlock;
                }
            }
        }

        return $this->getBodyParametersFromDocBlock($tags);
    }

    /**
     * @param array $tags
     *
     * @return array
     */
    protected function getBodyParametersFromDocBlock(array $tags)
    {
        $parameters = collect($tags)
            ->filter(function ($tag) {
                return $tag instanceof Tag && $tag->getName() === 'bodyParam';
            })
            ->mapWithKeys(function ($tag) {
                preg_match('/(.+?)\s+(.+?)\s+(required\s+)?(.*)/', $tag->getContent(), $content);
                if (empty($content)) {
                    // this means only name and type were supplied
                    list($name, $type) = preg_split('/\s+/', $tag->getContent());
                    $required = false;
                    $description = '';
                } else {
                    list($_, $name, $type, $required, $description) = $content;
                    $description = trim($description);
                    if ($description == 'required' && empty(trim($required))) {
                        $required = $description;
                        $description = '';
                    }
                    $required = trim($required) == 'required' ? true : false;
                }

                $type = $this->normalizeParameterType($type);
                list($description, $example) = $this->parseDescription($description, $type);
                $value = is_null($example) ? $this->generateDummyValue($type) : $example;

                return [$name => compact('type', 'description', 'required', 'value')];
            })->toArray();

        return $parameters;
    }

    /**
     * @param array $tags
     *
     * @return array
     */
    protected function getQueryParametersFromDocBlock(array $tags)
    {
        $parameters = collect($tags)
            ->filter(function ($tag) {
                return $tag instanceof Tag && $tag->getName() === 'queryParam';
            })
            ->mapWithKeys(function ($tag) {
                preg_match('/(.+?)\s+(required\s+)?(.*)/', $tag->getContent(), $content);
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

                list($description, $value) = $this->parseDescription($description, 'string');
                if (is_null($value)) {
                    $value = str_contains($description, ['number', 'count', 'page'])
                        ? $this->generateDummyValue('integer')
                        : $this->generateDummyValue('string');
                }

                return [$name => compact('description', 'required', 'value')];
            })->toArray();

        return $parameters;
    }

    /**
     * @param array $tags
     *
     * @return bool
     */
    protected function getAuthStatusFromDocBlock(array $tags)
    {
        $authTag = collect($tags)
            ->first(function ($tag) {
                return $tag instanceof Tag && strtolower($tag->getName()) === 'authenticated';
            });

        return (bool) $authTag;
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return array
     */
    protected function parseDocBlock(ReflectionMethod $method)
    {
        $comment = $method->getDocComment();
        $phpdoc = new DocBlock($comment);

        return [
            'short' => $phpdoc->getShortDescription(),
            'long' => $phpdoc->getLongDescription()->getContents(),
            'tags' => $phpdoc->getTags(),
        ];
    }

    /**
     * @param ReflectionClass $controller
     * @param ReflectionMethod $method
     *
     * @return string
     */
    protected function getRouteGroup(ReflectionClass $controller, ReflectionMethod $method)
    {
        // @group tag on the method overrides that on the controller
        $docBlockComment = $method->getDocComment();
        if ($docBlockComment) {
            $phpdoc = new DocBlock($docBlockComment);
            foreach ($phpdoc->getTags() as $tag) {
                if ($tag->getName() === 'group') {
                    return $tag->getContent();
                }
            }
        }

        $docBlockComment = $controller->getDocComment();
        if ($docBlockComment) {
            $phpdoc = new DocBlock($docBlockComment);
            foreach ($phpdoc->getTags() as $tag) {
                if ($tag->getName() === 'group') {
                    return $tag->getContent();
                }
            }
        }

        return 'general';
    }

    private function normalizeParameterType($type)
    {
        $typeMap = [
            'int' => 'integer',
            'bool' => 'boolean',
            'double' => 'float',
        ];

        return $type ? ($typeMap[$type] ?? $type) : 'string';
    }

    private function generateDummyValue(string $type)
    {
        $faker = Factory::create();
        $fakes = [
            'integer' => function () {
                return rand(1, 20);
            },
            'number' => function () use ($faker) {
                return $faker->randomFloat();
            },
            'float' => function () use ($faker) {
                return $faker->randomFloat();
            },
            'boolean' => function () use ($faker) {
                return $faker->boolean();
            },
            'string' => function () use ($faker) {
                return str_random();
            },
            'array' => function () {
                return [];
            },
            'object' => function () {
                return new \stdClass;
            },
        ];

        $fake = $fakes[$type] ?? $fakes['string'];

        return $fake();
    }

    /**
     * Allows users to specify an example for the parameter by writing 'Example: the-example',
     * to be used in example requests and response calls.
     *
     * @param string $description
     * @param string $type The type of the parameter. Used to cast the example provided, if any.
     *
     * @return array The description and included example.
     */
    private function parseDescription(string $description, string $type)
    {
        $example = null;
        if (preg_match('/(.*)\s+Example:\s*(.*)\s*/', $description, $content)) {
            $description = $content[1];

            // examples are parsed as strings by default, we need to cast them properly
            $example = $this->castToType($content[2], $type);
        }

        return [$description, $example];
    }

    /**
     * Cast a value from a string to a specified type.
     *
     * @param string $value
     * @param string $type
     *
     * @return mixed
     */
    private function castToType(string $value, string $type)
    {
        $casts = [
            'integer' => 'intval',
            'number' => 'floatval',
            'float' => 'floatval',
            'boolean' => 'boolval',
        ];

        // First, we handle booleans. We can't use a regular cast,
        //because PHP considers string 'false' as true.
        if ($value == 'false' && $type == 'boolean') {
            return false;
        }

        if (isset($casts[$type])) {
            return $casts[$type]($value);
        }

        return $value;
    }
}
