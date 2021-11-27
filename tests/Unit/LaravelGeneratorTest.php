<?php

namespace LeonardoHipolito\ApiDoc\Tests\Unit;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use LeonardoHipolito\ApiDoc\ApiDocGeneratorServiceProvider;
use LeonardoHipolito\ApiDoc\Tests\Fixtures\TestController;

class LaravelGeneratorTest extends GeneratorTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ApiDocGeneratorServiceProvider::class,
        ];
    }

    public function createRoute(string $httpMethod, string $path, string $controllerMethod, $register = false, $class = TestController::class)
    {
        if ($register) {
            return RouteFacade::{$httpMethod}($path, $class . "@$controllerMethod");
        } else {
            return new Route([$httpMethod], $path, ['uses' => $class . "@$controllerMethod"]);
        }
    }

    public function createRouteUsesArray(string $httpMethod, string $path, string $controllerMethod, $register = false, $class = TestController::class)
    {
        if ($register) {
            return RouteFacade::{$httpMethod}($path, [$class . "$controllerMethod"]);
        } else {
            return new Route([$httpMethod], $path, ['uses' => [$class, $controllerMethod]]);
        }
    }
}
