<?php

namespace Mpociot\ApiDoc\Generators;

use Faker\Factory;
use ReflectionClass;
use Illuminate\Support\Str;
use League\Fractal\Manager;
use Illuminate\Routing\Route;
use Mpociot\Reflection\DocBlock;
use League\Fractal\Resource\Item;
use Mpociot\Reflection\DocBlock\Tag;
use League\Fractal\Resource\Collection;

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
    public function processRoute($route)
    {
        $routeAction = $route->getAction();
        $routeGroup = $this->getRouteGroup($routeAction['uses']);
        $docBlock = $this->parseDocBlock($routeAction['uses']);
        $content = $this->getResponse($docBlock['tags']);

        return [
            'id' => md5($this->getUri($route).':'.implode($this->getMethods($route))),
            'resource' => $routeGroup,
            'title' => $docBlock['short'],
            'description' => $docBlock['long'],
            'methods' => $this->getMethods($route),
            'uri' => $this->getUri($route),
            'parameters' => $this->getParametersFromDocBlock($docBlock['tags']),
            'response' => $content,
            'showresponse' => ! empty($content),
        ];
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
     * Get the response from the docblock if available.
     *
     * @param array $tags
     *
     * @return mixed
     */
    protected function getDocblockResponse($tags)
    {
        $responseTags = array_filter($tags, function ($tag) {
            return $tag instanceof Tag && \strtolower($tag->getName()) == 'response';
        });
        if (empty($responseTags)) {
            return;
        }
        $responseTag = \array_first($responseTags);

        return \response(json_encode($responseTag->getContent()), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @param array $tags
     *
     * @return array
     */
    protected function getParametersFromDocBlock($tags)
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
     * @param  $route
     * @param  $bindings
     * @param  $headers
     *
     * @return \Illuminate\Http\Response
     */
    protected function getRouteResponse($route, $bindings, $headers = [])
    {
        $uri = $this->addRouteModelBindings($route, $bindings);

        $methods = $this->getMethods($route);

        // Split headers into key - value pairs
        $headers = collect($headers)->map(function ($value) {
            $split = explode(':', $value); // explode to get key + values
            $key = array_shift($split); // extract the key and keep the values in the array
            $value = implode(':', $split); // implode values into string again

            return [trim($key) => trim($value)];
        })->collapse()->toArray();

        //Changes url with parameters like /users/{user} to /users/1
        $uri = preg_replace('/{(.*?)}/', 1, $uri); // 1 is the default value for route parameters

        return $this->callRoute(array_shift($methods), $uri, [], [], [], $headers);
    }

    /**
     * @param $route
     * @param array $bindings
     *
     * @return mixed
     */
    protected function addRouteModelBindings($route, $bindings)
    {
        $uri = $this->getUri($route);
        foreach ($bindings as $model => $id) {
            $uri = str_replace('{'.$model.'}', $id, $uri);
            $uri = str_replace('{'.$model.'?}', $id, $uri);
        }

        return $uri;
    }

    /**
     * @param  \Illuminate\Routing\Route  $route
     *
     * @return array
     */
    protected function parseDocBlock($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);
        $reflectionMethod = $reflection->getMethod($method);

        $comment = $reflectionMethod->getDocComment();
        $phpdoc = new DocBlock($comment);

        return [
            'short' => $phpdoc->getShortDescription(),
            'long' => $phpdoc->getLongDescription()->getContents(),
            'tags' => $phpdoc->getTags(),
        ];
    }

    /**
     * @param  string  $route
     *
     * @return string
     */
    protected function getRouteGroup($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);
        $comment = $reflection->getDocComment();
        if ($comment) {
            $phpdoc = new DocBlock($comment);
            foreach ($phpdoc->getTags() as $tag) {
                if ($tag->getName() === 'resource') {
                    return $tag->getContent();
                }
            }
        }

        return 'general';
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  string  $content
     *
     * @return \Illuminate\Http\Response
     */
    abstract public function callRoute($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null);

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     *
     * @param  array  $headers
     *
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        $server = [];
        $prefix = 'HTTP_';

        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');

            if (! Str::startsWith($name, $prefix) && $name !== 'CONTENT_TYPE') {
                $name = $prefix.$name;
            }

            $server[$name] = $value;
        }

        return $server;
    }

    /**
     * @param $response
     *
     * @return mixed
     */
    private function getResponseContent($response)
    {
        if (empty($response)) {
            return '';
        }
        if ($response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), JSON_PRETTY_PRINT);
        } else {
            $content = $response->getContent();
        }

        return $content;
    }

    /**
     * Get a response from the transformer tags.
     *
     * @param array $tags
     *
     * @return mixed
     */
    protected function getTransformerResponse($tags)
    {
        try {
            $transFormerTags = array_filter($tags, function ($tag) {
                if (! ($tag instanceof Tag)) {
                    return false;
                }

                return \in_array(\strtolower($tag->getName()), ['transformer', 'transformercollection']);
            });
            if (empty($transFormerTags)) {
                // we didn't have any of the tags so goodbye
                return false;
            }

            $modelTag = array_first(array_filter($tags, function ($tag) {
                if (! ($tag instanceof Tag)) {
                    return false;
                }

                return \in_array(\strtolower($tag->getName()), ['transformermodel']);
            }));
            $tag = \array_first($transFormerTags);
            $transformer = $tag->getContent();
            if (! \class_exists($transformer)) {
                // if we can't find the transformer we can't generate a response
                return;
            }
            $demoData = [];

            $reflection = new ReflectionClass($transformer);
            $method = $reflection->getMethod('transform');
            $parameter = \array_first($method->getParameters());
            $type = null;
            if ($modelTag) {
                $type = $modelTag->getContent();
            }
            if (\is_null($type)) {
                if ($parameter->hasType() &&
                    ! $parameter->getType()->isBuiltin() &&
                    \class_exists((string) $parameter->getType())) {
                    //we have a type
                    $type = (string) $parameter->getType();
                }
            }
            if ($type) {
                // we have a class so we try to create an instance
                $demoData = new $type;
                try {
                    // try a factory
                    $demoData = \factory($type)->make();
                } catch (\Exception $e) {
                    if ($demoData instanceof \Illuminate\Database\Eloquent\Model) {
                        // we can't use a factory but can try to get one from the database
                        try {
                            // check if we can find one
                            $newDemoData = $type::first();
                            if ($newDemoData) {
                                $demoData = $newDemoData;
                            }
                        } catch (\Exception $e) {
                            // do nothing
                        }
                    }
                }
            }

            $fractal = new Manager();
            $resource = [];
            if ($tag->getName() == 'transformer') {
                // just one
                $resource = new Item($demoData, new $transformer);
            }
            if ($tag->getName() == 'transformercollection') {
                // a collection
                $resource = new Collection([$demoData, $demoData], new $transformer);
            }

            return \response($fractal->createData($resource)->toJson());
        } catch (\Exception $e) {
            // it isn't possible to parse the transformer
            return;
        }
    }

    private function getResponse(array $annotationTags)
    {
        $response = null;
        if ($docblockResponse = $this->getDocblockResponse($annotationTags)) {
            // we have a response from the docblock ( @response )
            $response = $docblockResponse;
        }
        if (! $response && ($transformerResponse = $this->getTransformerResponse($annotationTags))) {
            // we have a transformer response from the docblock ( @transformer || @transformercollection )
            $response = $transformerResponse;
        }

        $content = $response ? $this->getResponseContent($response) : null;

        return $content;
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

        return $fakes[$type]() ?? $fakes['string']();
    }
}
