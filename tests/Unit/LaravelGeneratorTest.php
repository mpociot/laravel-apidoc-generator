<?php

namespace Mpociot\ApiDoc\Tests\Unit;

use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Generators\LaravelGenerator;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;

class LaravelGeneratorTest extends GeneratorTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ApiDocGeneratorServiceProvider::class,
        ];
    }

    public function setUp()
    {
        parent::setUp();

        $this->generator = new LaravelGenerator();
    }

    public function createRoute(string $httpMethod, string $path, string $controllerMethod)
    {
        return new Route([$httpMethod], $path, ['uses' => TestController::class."@$controllerMethod"]);
    }
}
