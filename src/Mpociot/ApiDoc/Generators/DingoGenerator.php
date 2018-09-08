<?php

namespace Mpociot\ApiDoc\Generators;

use Exception;

class DingoGenerator extends AbstractGenerator
{
    /**
     * @param \Illuminate\Routing\Route $route
     * @param array $bindings
     * @param array $headers
     * @param bool $withResponse
     *
     * @return array
     */
    public function processRoute($route, $bindings = [], $headers = [], $withResponse = true)
    {
        $routeAction = $route->getAction();
        $routeGroup = $this->getRouteGroup($routeAction['uses']);
        $routeDescription = $this->getRouteDescription($routeAction['uses']);

        $parameters = $this->getParameters([
            'id' => md5($route->uri().':'.implode($route->getMethods())),
            'resource' => $routeGroup,
            'title' => $routeDescription['short'],
            'description' => $routeDescription['long'],
            'methods' => $route->getMethods(),
            'uri' => $route->uri(),
            'parameters' => [],
            'response' => '',
        ], $routeAction, $bindings);

        $response = '';

        if ($withResponse) {
            foreach ($parameters['parameters'] as $paramName => $attr) {
                if ($attr['required']) {
                    $params[$paramName] = $attr['value'];
                }
            }
            try {
                $response = $this->getRouteResponse($route, $bindings, $params, $headers);
                $parameters['response'] = $response->original;
            } catch (Exception $e) {
            }
        }

        return $parameters;
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
        // Not needed by Dingo
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function callRoute($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $dispatcher = app('Dingo\Api\Dispatcher')->raw();

        collect($server)->map(function ($key, $value) use ($dispatcher) {
            $dispatcher->header($value, $key);
        });

        return call_user_func_array([$dispatcher->with($parameters), strtolower($method)], [$uri]);
    }

    /**
     * {@inheritdoc}
     */
    public function getUri($route)
    {
        return $route->uri();
    }

    /**
     * {@inheritdoc}
     */
    public function getMethods($route)
    {
        return array_diff($route->getMethods(), ['HEAD']);
    }
}
