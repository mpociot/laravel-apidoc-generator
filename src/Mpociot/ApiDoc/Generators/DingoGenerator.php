<?php

namespace Mpociot\ApiDoc\Generators;

use Exception;

class DingoGenerator extends AbstractGenerator
{
    /**
     * @param $route
     * @param array $bindings
     *
     * @return array
     */
    public function processRoute($route, $bindings = [])
    {
        try {
            $response = $this->getRouteResponse($route, $bindings);
        } catch (Exception $e) {
            $response = '';
        }
        $routeAction = $route->getAction();
        $routeGroup = $this->getRouteGroup($routeAction['uses']);
        $routeDescription = $this->getRouteDescription($routeAction['uses']);

        return $this->getParameters([
            'resource' => $routeGroup,
            'title' => $routeDescription['short'],
            'description' => $routeDescription['long'],
            'methods' => $route->getMethods(),
            'uri' => $route->uri(),
            'parameters' => [],
            'response' => $response,
        ], $routeAction);
    }

    /**
     * {@inheritdoc}
     */
    public function callRoute($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        return call_user_func_array([app('Dingo\Api\Dispatcher'), strtolower($method)], [$uri]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUri($route)
    {
        return $route->uri();
    }
}
