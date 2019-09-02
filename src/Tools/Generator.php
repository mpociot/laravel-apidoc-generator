<?php

namespace Mpociot\ApiDoc\Tools;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Tools\Traits\ParamHelpers;
use Symfony\Component\HttpFoundation\Response;

class Generator
{
    use ParamHelpers;

    /**
     * @var DocumentationConfig
     */
    private $config;

    public function __construct(DocumentationConfig $config = null)
    {
        // If no config is injected, pull from global
        $this->config = $config ?: new DocumentationConfig(config('apidoc'));
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
     * @param \Illuminate\Routing\Route $route
     * @param array $rulesToApply Rules to apply when generating documentation for this route
     *
     * @return array
     */
    public function processRoute(Route $route, array $rulesToApply = [])
    {
        list($controllerName, $methodName) = Utils::getRouteClassAndMethodNames($route->getAction());
        $controller = new ReflectionClass($controllerName);
        $method = $controller->getMethod($methodName);

        $parsedRoute = [
            'id' => md5($this->getUri($route).':'.implode($this->getMethods($route))),
            'methods' => $this->getMethods($route),
            'uri' => $this->getUri($route),
            'boundUri' => Utils::getFullUrl($route, $rulesToApply['bindings'] ?? ($rulesToApply['response_calls']['bindings'] ?? [])),
        ];
        $metadata = $this->fetchMetadata($controller, $method, $route, $rulesToApply);
        $parsedRoute += $metadata;
        $bodyParameters = $this->fetchBodyParameters($controller, $method, $route, $rulesToApply, $parsedRoute);
        $parsedRoute['bodyParameters'] = $bodyParameters;
        $parsedRoute['cleanBodyParameters'] = $this->cleanParams($bodyParameters);

        $queryParameters = $this->fetchQueryParameters($controller, $method, $route, $rulesToApply, $parsedRoute);
        $parsedRoute['queryParameters'] = $queryParameters;
        $parsedRoute['cleanQueryParameters'] = $this->cleanParams($queryParameters);

        $responses = $this->fetchResponses($controller, $method, $route, $rulesToApply, $parsedRoute);
        $parsedRoute['response'] = $responses;
        $parsedRoute['showresponse'] = ! empty($responses);

        $parsedRoute['headers'] = $rulesToApply['headers'] ?? [];

        return $parsedRoute;
    }

    protected function fetchMetadata(ReflectionClass $controller, ReflectionMethod $method, Route $route, array $rulesToApply)
    {
        return $this->iterateThroughStrategies('metadata', [$route, $controller, $method, $rulesToApply]);
    }

    protected function fetchBodyParameters(ReflectionClass $controller, ReflectionMethod $method, Route $route, array $rulesToApply, array $context = [])
    {
        return $this->iterateThroughStrategies('bodyParameters', [$route, $controller, $method, $rulesToApply]);
    }

    protected function fetchQueryParameters(ReflectionClass $controller, ReflectionMethod $method, Route $route, array $rulesToApply, array $context = [])
    {
        return $this->iterateThroughStrategies('queryParameters', [$route, $controller, $method, $rulesToApply]);
    }

    protected function fetchResponses(ReflectionClass $controller, ReflectionMethod $method, Route $route, array $rulesToApply, array $context = [])
    {
        $responses = $this->iterateThroughStrategies('responses', [$route, $controller, $method, $rulesToApply, $context]);
        if (count($responses)) {
            return array_map(function (Response $response) {
                return [
                    'status' => $response->getStatusCode(),
                    'content' => $response->getContent(),
                ];
            }, $responses);
        }

        return null;
    }

    protected function iterateThroughStrategies(string $key, array $arguments)
    {
        $strategies = $this->config->get("strategies.$key", []);
        $results = [];

        foreach ($strategies as $strategyClass) {
            $strategy = new $strategyClass($this->config);
            $results = $strategy(...$arguments);
            if (! is_null($results)) {
                break;
            }
        }

        return is_null($results) ? [] : $results;
    }
}
