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

        $metadata = $this->fetchMetadata($controller, $method, $route, $rulesToApply);
        $bodyParameters = $this->fetchBodyParameters($controller, $method, $route, $rulesToApply);
        $queryParameters = $this->fetchQueryParameters($controller, $method, $route, $rulesToApply);
        // $this->fetchResponse();

        $docBlocks = RouteDocBlocker::getDocBlocksFromRoute($route);
        /** @var DocBlock $methodDocBlock */
        $methodDocBlock = $docBlocks['method'];
        $content = ResponseResolver::getResponse($route, $methodDocBlock->getTags(), [
            'rules' => $rulesToApply,
            'body' => $bodyParameters,
            'query' => $queryParameters,
        ]);

        $parsedRoute = [
            'id' => md5($this->getUri($route).':'.implode($this->getMethods($route))),
            'methods' => $this->getMethods($route),
            'uri' => $this->getUri($route),
            'boundUri' => Utils::getFullUrl($route, $rulesToApply['bindings'] ?? ($rulesToApply['response_calls']['bindings'] ?? [])),
            'queryParameters' => $queryParameters,
            'bodyParameters' => $bodyParameters,
            'cleanBodyParameters' => $this->cleanParams($bodyParameters),
            'cleanQueryParameters' => $this->cleanParams($queryParameters),
            'response' => $content,
            'showresponse' => ! empty($content),
        ];
        $parsedRoute['headers'] = $rulesToApply['headers'] ?? [];
        $parsedRoute += $metadata;

        return $parsedRoute;
    }

    protected function fetchMetadata(ReflectionClass $controller, ReflectionMethod $method, Route $route, array $rulesToApply)
    {
        $metadataStrategies = $this->config->get('strategies.metadata', []);
        $results = [];

        foreach ($metadataStrategies as $strategyClass) {
            $strategy = new $strategyClass($this->config);
            $results = $strategy($route, $controller, $method, $rulesToApply);
            if (count($results)) {
                break;
            }
        }
        return count($results) ? $results : [];
    }

    protected function fetchBodyParameters(ReflectionClass $controller, ReflectionMethod $method, Route $route, array $rulesToApply)
    {
        $bodyParametersStrategies = $this->config->get('strategies.bodyParameters', []);
        $results = [];

        foreach ($bodyParametersStrategies as $strategyClass) {
            $strategy = new $strategyClass($this->config);
            $results = $strategy($route, $controller, $method, $rulesToApply);
            if (count($results)) {
                break;
            }
        }
        return count($results) ? $results : [];
    }

    protected function fetchQueryParameters(ReflectionClass $controller, ReflectionMethod $method, Route $route, array $rulesToApply)
    {
        $queryParametersStrategies = $this->config->get('strategies.queryParameters', []);
        $results = [];

        foreach ($queryParametersStrategies as $strategyClass) {
            $strategy = new $strategyClass($this->config);
            $results = $strategy($route, $controller, $method, $rulesToApply);
            if (count($results)) {
                break;
            }
        }
        return count($results) ? $results : [];
    }
}
