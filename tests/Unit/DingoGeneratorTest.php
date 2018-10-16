<?php

namespace Mpociot\ApiDoc\Tests\Unit;

use Dingo\Api\Routing\Router;
use Mpociot\ApiDoc\Generators\DingoGenerator;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;

class DingoGeneratorTest extends GeneratorTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ApiDocGeneratorServiceProvider::class,
            \Dingo\Api\Provider\LaravelServiceProvider::class,
        ];
    }

    public function setUp()
    {
        parent::setUp();

        $this->generator = new DingoGenerator();
        config(['apidoc.router' => 'dingo']);

    }

    public function createRoute(string $httpMethod, string $path, string $controllerMethod, $register = false)
    {
        $route = null;
        /** @var Router $api */
        $api = app(Router::class);
        $api->version('v1', function (Router $api) use ($controllerMethod, $path, $httpMethod, &$route) {
            $route = $api->$httpMethod($path, TestController::class."@$controllerMethod");
        });

        return $route;
    }
}
