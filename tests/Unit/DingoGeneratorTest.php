<?php

namespace Mpociot\ApiDoc\Tests\Unit;

use Dingo\Api\Routing\Router;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;

/**
 * @group dingo
 */
class DingoGeneratorTest extends GeneratorTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ApiDocGeneratorServiceProvider::class,
            \Dingo\Api\Provider\LaravelServiceProvider::class,
        ];
    }

    public function setUp(): void
    {
        parent::setUp();

        config(['apidoc.router' => 'dingo']);
    }

    public function createRoute(string $httpMethod, string $path, string $controllerMethod, $register = false, $class = TestController::class)
    {
        $route = null;
        /** @var Router $api */
        $api = app(Router::class);
        $api->version('v1', function (Router $api) use ($class, $controllerMethod, $path, $httpMethod, &$route) {
            $route = $api->$httpMethod($path, $class . "@$controllerMethod");
        });

        return $route;
    }

    public function createRouteUsesArray(string $httpMethod, string $path, string $controllerMethod, $register = false, $class = TestController::class)
    {
        $route = null;
        /** @var Router $api */
        $api = app(Router::class);
        $api->version('v1', function (Router $api) use ($class, $controllerMethod, $path, $httpMethod, &$route) {
            $route = $api->$httpMethod($path, [$class, $controllerMethod]);
        });

        return $route;
    }
}
