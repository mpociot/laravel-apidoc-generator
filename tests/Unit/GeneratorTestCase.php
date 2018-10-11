<?php

namespace Mpociot\ApiDoc\Tests\Unit;

use Illuminate\Routing\Route;
use Orchestra\Testbench\TestCase;
use Mpociot\ApiDoc\Generators\LaravelGenerator;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Illuminate\Support\Facades\Route as RouteFacade;

abstract class GeneratorTestCase extends TestCase
{
    /**
     * @var \Mpociot\ApiDoc\Generators\AbstractGenerator
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

    /** @test */
    public function test_can_parse_endpoint_description()
    {
        $route = $this->createRoute('GET', '/api/test', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);

        $this->assertSame('Example title.', $parsed['title']);
        $this->assertSame("This will be the long description.\nIt can also be multiple lines long.", $parsed['description']);
    }

    /** @test */
    public function test_can_parse_body_parameters()
    {
        $route = $this->createRoute('GET', '/api/test', 'withBodyParameters');
        $parameters = $this->generator->processRoute($route)['parameters'];

        $this->assertArraySubset([
            'user_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'The id of the user.',
            ],
            'room_id' => [
                'type' => 'string',
                'required' => false,
                'description' => 'The id of the room.'
            ]
        ], $parameters);
    }

    /** @test */
    public function test_can_parse_route_methods()
    {
        $route = $this->createRoute('GET', '/get', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['GET'], $parsed['methods']);

        $route = $this->createRoute('POST', '/post', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['POST'], $parsed['methods']);

        $route = $this->createRoute('PUT', '/put', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['PUT'], $parsed['methods']);

        $route = $this->createRoute('DELETE', '/delete', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['DELETE'], $parsed['methods']);
    }

    /** @test */
    public function test_can_parse_response_tag()
    {
        $route = $this->createRoute('POST', '/responseTag', 'withResponseTag');

        $parsed = $this->generator->processRoute($route);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertJsonStringEqualsJsonString($parsed['response'], '{ "data": []}');
    }

    /** @test */
    public function test_can_parse_transformer_tag()
    {
         $route = $this->createRoute('GET', '/transformerTag', 'transformerTag');
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}'
        );
    }

    /** @test */
    public function test_can_parse_transformer_tag_with_model()
    {
        $route = $this->createRoute('GET', '/transformerTagWithModel', 'transformerTagWithModel');
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}'
        );
    }

    /** @test */
    public function test_can_parse_transformer_collection_tag()
    {
        $route = $this->createRoute('GET', '/transformerCollectionTag', 'transformerCollectionTag');
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},' .
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }

    /** @test */
    public function test_can_parse_transformer_collection_tag_with_model()
    {
        $route = $this->createRoute('GET', '/transformerCollectionTagWithModel', 'transformerCollectionTagWithModel');
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},' .
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }

    abstract public function createRoute(string $httpMethod, string $path, string $controllerMethod);
}
