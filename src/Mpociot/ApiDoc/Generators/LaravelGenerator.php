<?php

namespace Mpociot\ApiDoc\Generators;

use ReflectionClass;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;
use Illuminate\Foundation\Http\FormRequest;

class LaravelGenerator extends AbstractGenerator
{
    /**
     * @param Route $route
     *
     * @return mixed
     */
    protected function getUri($route)
    {
        return $route->getUri();
    }

    /**
     * @param  \Illuminate\Routing\Route $route
     * @param array $bindings
     * @param array $headers
     * @param bool $withResponse
     *
     * @return array
     */
    public function processRoute($route, $bindings = [], $headers = [], $withResponse = true)
    {
        $content = '';

        $routeAction = $route->getAction();
        $routeGroup = $this->getRouteGroup($routeAction['uses']);
        $routeDescription = $this->getRouteDescription($routeAction['uses']);

        if ($withResponse) {
            $response = $this->getRouteResponse($route, $bindings, $headers);
            if ($response->headers->get('Content-Type') === 'application/json') {
                $content = json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT);
            } else {
                $content = $response->getContent();
            }
        }

        return $this->getParameters([
            'id' => md5($route->getUri().':'.implode($route->getMethods())),
            'resource' => $routeGroup,
            'title' => $routeDescription['short'],
            'description' => $routeDescription['long'],
            'methods' => $route->getMethods(),
            'uri' => $route->getUri(),
            'parameters' => [],
            'response' => $content,
        ], $routeAction, $bindings);
    }

    /**
     * Prepares / Disables route middlewares.
     *
     * @param  bool $disable
     *
     * @return  void
     */
    public function prepareMiddleware($disable = true)
    {
        App::instance('middleware.disable', true);
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
    public function callRoute($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $server = collect([
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ])->merge($server)->toArray();

        $request = Request::create(
            $uri, $method, $parameters,
            $cookies, $files, $this->transformHeadersToServerVars($server), $content
        );

        $kernel = App::make('Illuminate\Contracts\Http\Kernel');
        $response = $kernel->handle($request);

        $kernel->terminate($request, $response);

        if (file_exists($file = App::bootstrapPath().'/app.php')) {
            $app = require $file;
            $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        }

        return $response;
    }

    /**
     * @param  string $route
     * @param  array $bindings
     *
     * @return array
     */
    protected function getRouteRules($route, $bindings)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);
        $reflectionMethod = $reflection->getMethod($method);

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameterType = $parameter->getClass();
            if (! is_null($parameterType) && class_exists($parameterType->name)) {
                $className = $parameterType->name;

                if (is_subclass_of($className, FormRequest::class)) {
                    $parameterReflection = new $className;
                    $parameterReflection->setContainer(app());
                    // Add route parameter bindings
                    $parameterReflection->query->add($bindings);
                    $parameterReflection->request->add($bindings);

                    if (method_exists($parameterReflection, 'validator')) {
                        return app()->call([$parameterReflection, 'validator'])
                            ->getRules();
                    } else {
                        return app()->call([$parameterReflection, 'rules']);
                    }
                }
            }
        }

        return [];
    }
}
