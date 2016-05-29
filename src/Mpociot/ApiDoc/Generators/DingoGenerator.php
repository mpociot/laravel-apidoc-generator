<?php

namespace Mpociot\ApiDoc\Generators;

use Illuminate\Support\Facades\App;

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
        $response = $this->getRouteResponse($route, $bindings);

        $routeAction = $route->getAction();
        $routeGroup = $this->getRouteGroup($routeAction['uses']);
        $routeDescription = $this->getRouteDescription($routeAction['uses']);

        if ($response->headers->get('Content-Type') === 'application/json') {
            $content = json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT);
        } else {
            $content = $response->getContent();
        }

        return $this->getParameters([
            'resource' => $routeGroup,
            'title' => $routeDescription['short'],
            'description' => $routeDescription['long'],
            'methods' => $route->getMethods(),
            'uri' => $route->getUri(),
            'parameters' => [],
            'response' => $content,
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
