<?php

namespace Mpociot\ApiDoc\Tests;

use Illuminate\Routing\Route;
use Orchestra\Testbench\TestCase;
use Mpociot\ApiDoc\Generators\LaravelGenerator;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Illuminate\Support\Facades\Route as RouteFacade;

class ApiDocGeneratorTest extends TestCase
{
    /**
     * @var \Mpociot\ApiDoc\AbstractGenerator
     */
    protected $generator;

    protected function getPackageProviders($app)
    {
        return [
            ApiDocGeneratorServiceProvider::class,
        ];
    }

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->generator = new LaravelGenerator();
    }

    public function testCanParseMethodDescription()
    {
        RouteFacade::get('/api/test', TestController::class.'@parseMethodDescription');
        $route = new Route(['GET'], '/api/test', ['uses' => TestController::class.'@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);

        $this->assertSame('Example title.', $parsed['title']);
        $this->assertSame("This will be the long description.\nIt can also be multiple lines long.", $parsed['description']);
    }

    public function testCanParseRouteMethods()
    {
        RouteFacade::get('/get', TestController::class.'@dummy');
        RouteFacade::post('/post', TestController::class.'@dummy');
        RouteFacade::put('/put', TestController::class.'@dummy');
        RouteFacade::delete('/delete', TestController::class.'@dummy');

        $route = new Route(['GET'], '/get', ['uses' => TestController::class.'@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['GET'], $parsed['methods']);

        $route = new Route(['POST'], '/post', ['uses' => TestController::class.'@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['POST'], $parsed['methods']);

        $route = new Route(['PUT'], '/put', ['uses' => TestController::class.'@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['PUT'], $parsed['methods']);

        $route = new Route(['DELETE'], '/delete', ['uses' => TestController::class.'@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['DELETE'], $parsed['methods']);
    }

    public function testCanParseDependencyInjectionInControllerMethods()
    {
        RouteFacade::post('/post', TestController::class.'@dependencyInjection');
        $route = new Route(['POST'], '/post', ['uses' => TestController::class.'@dependencyInjection']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
    }

    public function testCanParseResponseTag()
    {
        RouteFacade::post('/responseTag', TestController::class.'@responseTag');
        $route = new Route(['POST'], '/responseTag', ['uses' => TestController::class.'@responseTag']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertJsonStringEqualsJsonString($parsed['response'], '{ "data": []}');
    }

    public function testCanParseTransformerTag()
    {
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            $this->markTestSkipped('The transformer tag without model need PHP 7');
        }
        RouteFacade::post('/transformerTag', TestController::class.'@transformerTag');
        $route = new Route(['GET'], '/transformerTag', ['uses' => TestController::class.'@transformerTag']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}'
        );
    }

    public function testCanParseTransformerTagWithModel()
    {
        RouteFacade::post('/transformerTagWithModel', TestController::class.'@transformerTagWithModel');
        $route = new Route(['GET'], '/transformerTagWithModel', ['uses' => TestController::class.'@transformerTagWithModel']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}'
        );
    }

    public function testCanParseTransformerCollectionTag()
    {
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            $this->markTestSkipped('The transformer tag without model need PHP 7');
        }
        RouteFacade::post('/transformerCollectionTag', TestController::class.'@transformerCollectionTag');
        $route = new Route(['GET'], '/transformerCollectionTag', ['uses' => TestController::class.'@transformerCollectionTag']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},'.
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }

    public function testCanParseTransformerCollectionTagWithModel()
    {
        RouteFacade::post('/transformerCollectionTagWithModel', TestController::class.'@transformerCollectionTagWithModel');
        $route = new Route(['GET'], '/transformerCollectionTagWithModel', ['uses' => TestController::class.'@transformerCollectionTagWithModel']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},'.
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }
}
