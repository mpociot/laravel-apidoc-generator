<?php

namespace Mpociot\ApiDoc\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Mpociot\ApiDoc\Tools\Generator;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;

abstract class GeneratorTestCase extends TestCase
{
    /**
     * @var \Mpociot\ApiDoc\Tools\Generator
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

        $this->generator = new Generator();
    }

    /** @test */
    public function can_parse_endpoint_description()
    {
        $route = $this->createRoute('GET', '/api/test', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);

        $this->assertSame('Example title.', $parsed['title']);
        $this->assertSame("This will be the long description.\nIt can also be multiple lines long.", $parsed['description']);
    }

    /** @test */
    public function can_parse_body_parameters()
    {
        $route = $this->createRoute('GET', '/api/test', 'withBodyParameters');
        $bodyParameters = $this->generator->processRoute($route)['bodyParameters'];

        $this->assertArraySubset([
            'user_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'The id of the user.',
            ],
            'room_id' => [
                'type' => 'string',
                'required' => false,
                'description' => 'The id of the room.',
            ],
            'forever' => [
                'type' => 'boolean',
                'required' => false,
                'description' => 'Whether to ban the user forever.',
            ],
            'another_one' => [
                'type' => 'number',
                'required' => false,
                'description' => 'Just need something here.',
            ],
            'yet_another_param' => [
                'type' => 'object',
                'required' => true,
                'description' => '',
            ],
            'even_more_param' => [
                'type' => 'array',
                'required' => false,
                'description' => '',
            ],
        ], $bodyParameters);
    }

    /** @test */
    public function can_parse_query_parameters()
    {
        $route = $this->createRoute('GET', '/api/test', 'withQueryParameters');
        $queryParameters = $this->generator->processRoute($route)['queryParameters'];

        $this->assertArraySubset([
            'location_id' => [
                'required' => true,
                'description' => 'The id of the location.',
            ],
            'filters' => [
                'required' => false,
                'description' => 'The filters.',
            ],
        ], $queryParameters);
    }

    /** @test */
    public function can_parse_route_group()
    {
        $route = $this->createRoute('GET', '/api/test', 'dummy');
        $routeGroup = $this->generator->processRoute($route)['group'];

        $this->assertSame('Group A', $routeGroup);
    }

    /** @test */
    public function method_can_override_controller_group()
    {
        $route = $this->createRoute('GET', '/api/test', 'withGroupOverride');
        $routeGroup = $this->generator->processRoute($route)['group'];

        $this->assertSame('Group B', $routeGroup);
    }

    /** @test */
    public function can_parse_auth_tags()
    {
        $route = $this->createRoute('GET', '/api/test', 'withAuthenticatedTag');
        $authenticated = $this->generator->processRoute($route)['authenticated'];
        $this->assertTrue($authenticated);

        $route = $this->createRoute('GET', '/api/test', 'dummy');
        $authenticated = $this->generator->processRoute($route)['authenticated'];
        $this->assertFalse($authenticated);
    }

    /** @test */
    public function can_parse_route_methods()
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
    public function can_parse_response_tag()
    {
        $route = $this->createRoute('POST', '/responseTag', 'withResponseTag');

        $parsed = $this->generator->processRoute($route);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertArraySubset([
            'id' => 4,
            'name' => 'banana',
            'color' => 'red',
            'weight' => '1 kg',
            'delicious' => true,
        ], json_decode($parsed['response'], true));
    }

    /** @test */
    public function can_parse_transformer_tag()
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
    public function can_parse_transformer_tag_with_model()
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
    public function can_parse_transformer_collection_tag()
    {
        $route = $this->createRoute('GET', '/transformerCollectionTag', 'transformerCollectionTag');
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

    /** @test */
    public function can_parse_transformer_collection_tag_with_model()
    {
        $route = $this->createRoute('GET', '/transformerCollectionTagWithModel', 'transformerCollectionTagWithModel');
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

    /** @test */
    public function can_call_route_and_generate_response()
    {
        $route = $this->createRoute('PUT', '/shouldFetchRouteResponse', 'shouldFetchRouteResponse', true);

        $rules = [
            'response_calls' => [
                'methods' => ['*'],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ],
        ];
        $parsed = $this->generator->processRoute($route, $rules);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertArraySubset([
            'id' => 4,
            'name' => 'banana',
            'color' => 'red',
            'weight' => '1 kg',
            'delicious' => true,
        ], json_decode($parsed['response'], true));
    }

    /** @test */
    public function uses_configured_settings_when_calling_route()
    {
        $route = $this->createRoute('PUT', '/echo/{id}', 'shouldFetchRouteResponseWithEchoedSettings', true);

        $rules = [
            'response_calls' => [
                'methods' => ['*'],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'header' => 'value',
                ],
                'bindings' => [
                    '{id}' => 3,
                ],
                'env' => [
                    'APP_ENV' => 'documentation',
                ],
                'query' => [
                    'queryParam' => 'queryValue',
                ],
                'body' => [
                    'bodyParam' => 'bodyValue',
                ],
            ],
        ];
        $parsed = $this->generator->processRoute($route, $rules);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $response = json_decode($parsed['response'], true);
        $this->assertEquals(3, $response['{id}']);
        $this->assertEquals('documentation', $response['APP_ENV']);
        $this->assertEquals('queryValue', $response['queryParam']);
        $this->assertEquals('bodyValue', $response['bodyParam']);
        $this->assertEquals('value', $response['header']);
    }

    abstract public function createRoute(string $httpMethod, string $path, string $controllerMethod, $register = false);
}
