<?php

namespace Mpociot\ApiDoc\Extracting\Strategies\QueryParameters;

use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Mpociot\ApiDoc\Extracting\Strategies\FromRequestRulesStrategy;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

class GetFromRulesMethod extends FromRequestRulesStrategy
{
    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        if (!Arr::hasAny(array_flip($route->methods()), [Request::METHOD_GET, Request::METHOD_HEAD])) {
            return null;
        }

        return parent::__invoke($route, $controller, $method, $routeRules, $context);
    }
}
