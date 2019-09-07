<?php

namespace Mpociot\ApiDoc\Tools;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;

class Generator
{
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
        $metadata = $this->fetchMetadata($controller, $method, $route, $rulesToApply, $parsedRoute);
        $parsedRoute['metadata'] = $metadata;
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

        $parsedRoute += $metadata;

        return $parsedRoute;
    }

    protected function fetchMetadata(ReflectionClass $controller, ReflectionMethod $method, Route $route, array $rulesToApply, array $context = [])
    {
        $context['metadata'] = [
            'groupName' => $this->config->get('default_group', ''),
            'groupDescription' => '',
            'title' => '',
            'description' => '',
            'authenticated' => false,
        ];

        return $this->iterateThroughStrategies('metadata', $context, [$route, $controller, $method, $rulesToApply]);
    }

    protected function fetchBodyParameters(ReflectionClass $controller, ReflectionMethod $method, Route $route, array $rulesToApply, array $context = [])
    {
        return $this->iterateThroughStrategies('bodyParameters', $context, [$route, $controller, $method, $rulesToApply]);
    }

    protected function fetchQueryParameters(ReflectionClass $controller, ReflectionMethod $method, Route $route, array $rulesToApply, array $context = [])
    {
        return $this->iterateThroughStrategies('queryParameters', $context, [$route, $controller, $method, $rulesToApply]);
    }

    protected function fetchResponses(ReflectionClass $controller, ReflectionMethod $method, Route $route, array $rulesToApply, array $context = [])
    {
        $responses = $this->iterateThroughStrategies('responses', $context, [$route, $controller, $method, $rulesToApply]);
        if (count($responses)) {
            return collect($responses)->map(function (string $response, int $status) {
                return [
                    'status' => $status ?: 200,
                    'content' => $response,
                ];
            })->values()->toArray();
        }

        return null;
    }

    protected function iterateThroughStrategies(string $stage, array $context, array $arguments)
    {
        $defaultStrategies = [
            'metadata' => [
                \Mpociot\ApiDoc\Strategies\Metadata\GetFromDocBlocks::class,
            ],
            'bodyParameters' => [
                \Mpociot\ApiDoc\Strategies\BodyParameters\GetFromBodyParamTag::class,
            ],
            'queryParameters' => [
                \Mpociot\ApiDoc\Strategies\QueryParameters\GetFromQueryParamTag::class,
            ],
            'responses' => [
                \Mpociot\ApiDoc\Strategies\Responses\UseResponseTag::class,
                \Mpociot\ApiDoc\Strategies\Responses\UseResponseFileTag::class,
                \Mpociot\ApiDoc\Strategies\Responses\UseTransformerTags::class,
                \Mpociot\ApiDoc\Strategies\Responses\ResponseCalls::class,
            ],
        ];

        // Use the default strategies for the stage, unless they were explicitly set
        $strategies = $this->config->get("strategies.$stage", $defaultStrategies[$stage]);
        $context[$stage] = $context[$stage] ?? [];
        foreach ($strategies as $strategyClass) {
            $strategy = new $strategyClass($stage, $this->config);
            $arguments[] = $context;
            $results = $strategy(...$arguments);
            if (! is_null($results)) {
                foreach ($results as $index => $item) {
                    // Using a for loop rather than array_merge or +=
                    // so it does not renumber numeric keys
                    // and also allows values to be overwritten

                    // Don't allow overwriting if an empty value is trying to replace a set one
                    if (! in_array($context[$stage], [null, ''], true) && in_array($item, [null, ''], true)) {
                        continue;
                    } else {
                        $context[$stage][$index] = $item;
                    }
                }
            }
        }

        return $context[$stage];
    }

    /**
     * Create samples at index 0 for array parameters.
     * Also filter out parameters which were excluded from having examples.
     *
     * @param array $params
     *
     * @return array
     */
    protected function cleanParams(array $params)
    {
        $values = [];

        // Remove params which have no examples.
        $params = array_filter($params, function ($details) {
            return ! is_null($details['value']);
        });

        foreach ($params as $paramName => $details) {
            $this->generateConcreteSampleForArrayKeys(
                $paramName, $details['value'], $values
            );
        }

        return $values;
    }

    /**
     * For each array notation parameter (eg user.*, item.*.name, object.*.*, user[])
     * generate concrete sample (user.0, item.0.name, object.0.0, user.0) with example as value.
     *
     * @param string $paramName
     * @param mixed $paramExample
     * @param array $values The array that holds the result
     *
     * @return void
     */
    protected function generateConcreteSampleForArrayKeys($paramName, $paramExample, array &$values = [])
    {
        if (Str::contains($paramName, '[')) {
            // Replace usages of [] with dot notation
            $paramName = str_replace(['][', '[', ']', '..'], ['.', '.', '', '.*.'], $paramName);
        }
        // Then generate a sample item for the dot notation
        Arr::set($values, str_replace('.*', '.0', $paramName), $paramExample);
    }
}
