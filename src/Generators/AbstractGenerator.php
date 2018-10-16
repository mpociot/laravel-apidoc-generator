<?php

namespace Mpociot\ApiDoc\Generators;

use Faker\Factory;
use ReflectionClass;
use ReflectionMethod;
use Illuminate\Routing\Route;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;
use Mpociot\ApiDoc\Tools\ResponseResolver;

abstract class AbstractGenerator
{
    /**
     * @param Route $route
     *
     * @return mixed
     */
    public function getDomain(Route $route)
    {
        return $route->domain() == null ? '*' : $route->domain();
    }

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
        $content = ResponseResolver::getResponse($route, $docBlock['tags'], $rulesToApply);

        $parsedRoute = [
            'id' => md5($this->getUri($route).':'.implode($this->getMethods($route))),
            'group' => $routeGroup,
            'title' => $docBlock['short'],
            'description' => $docBlock['long'],
            'methods' => $this->getMethods($route),
            'uri' => $this->getUri($route),
            'parameters' => $this->getParametersFromDocBlock($docBlock['tags']),
            'authenticated' => $this->getAuthStatusFromDocBlock($docBlock['tags']),
            'response' => $content,
            'showresponse' => ! empty($content),
        ];
        $parsedRoute['headers'] = $rulesToApply['headers'] ?? [];

        return $parsedRoute;
    }

    /**
     * Prepares / Disables route middlewares.
     *
     * @param  bool $disable
     *
     * @return  void
     */
    abstract public function prepareMiddleware($enable = false);

    /**
     * @param array $tags
     *
     * @return array
     */
    protected function getParametersFromDocBlock(array $tags)
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
                $value = $this->generateDummyValue($type);

                return [$name => compact('type', 'description', 'required', 'value')];
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
     *
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
                return '[]';
            },
            'object' => function () {
                return '{}';
            },
        ];

        $fake = $fakes[$type] ?? $fakes['string'];

        return $fake();
    }
}
