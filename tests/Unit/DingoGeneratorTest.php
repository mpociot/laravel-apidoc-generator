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
            \Dingo\Api\Provider\LaravelServiceProvider::class,
            ApiDocGeneratorServiceProvider::class,
        ];
    }

    public function setUp()
    {
        parent::setUp();

        $this->generator = new DingoGenerator();
    }

    public function createRoute(string $httpMethod, string $path, string $controllerMethod)
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
