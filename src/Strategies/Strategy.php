<?php

namespace Mpociot\ApiDoc\Strategies;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Tools\DocumentationConfig;

abstract class Strategy
{
    protected $config;

    public function __construct(DocumentationConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param Route $route
     * @param ReflectionClass $controller
     * @param ReflectionMethod $method
     * @param array $routeRules Array of rules for the ruleset which this route belongs to.
     * @param array $context Results from the previous stages
     *
     * @throws \Exception
     *
     * @return array
     */
    abstract public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = []);
}
