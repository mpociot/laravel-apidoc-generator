<?php

namespace Mpociot\ApiDoc\Tests;

use Orchestra\Testbench\TestCase;
use Mpociot\ApiDoc\Generators\DingoGenerator;
use Dingo\Api\Provider\LaravelServiceProvider;
use Mpociot\ApiDoc\Tests\Fixtures\TestRequest;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Mpociot\ApiDoc\Tests\Fixtures\DingoTestController;

class DingoGeneratorTest extends TestCase
{
    /**
     * @var \Mpociot\ApiDoc\Generators\DingoGenerator
     */
    protected $generator;

    protected function getPackageProviders($app)
    {
        return [
            LaravelServiceProvider::class,
            ApiDocGeneratorServiceProvider::class,
        ];
    }

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->generator = new DingoGenerator();
    }

    public function testCanParseMethodDescription()
    {
        $api = app('Dingo\Api\Routing\Router');
        $api->version('v1', function ($api) {
            $api->get('/api/test', TestController::class.'@parseMethodDescription');
        });
        $route = app('Dingo\Api\Routing\Router')->getRoutes()['v1']->getRoutes()[0];

        $parsed = $this->generator->processRoute($route);

        $this->assertSame('Example title.', $parsed['title']);
        $this->assertSame("This will be the long description.\nIt can also be multiple lines long.", $parsed['description']);
    }

    public function testCanParseRouteMethods()
    {
        $api = app('Dingo\Api\Routing\Router');
        $api->version('v1', function ($api) {
            $api->get('/get', TestController::class.'@dummy');
            $api->post('/post', TestController::class.'@dummy');
            $api->put('/put', TestController::class.'@dummy');
            $api->delete('/delete', TestController::class.'@dummy');
        });
        $route = app('Dingo\Api\Routing\Router')->getRoutes()['v1']->getRoutes()[0];
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['GET'], $parsed['methods']);

        $route = app('Dingo\Api\Routing\Router')->getRoutes()['v1']->getRoutes()[1];
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['POST'], $parsed['methods']);

        $route = app('Dingo\Api\Routing\Router')->getRoutes()['v1']->getRoutes()[2];
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['PUT'], $parsed['methods']);

        $route = app('Dingo\Api\Routing\Router')->getRoutes()['v1']->getRoutes()[3];
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['DELETE'], $parsed['methods']);
    }

}
