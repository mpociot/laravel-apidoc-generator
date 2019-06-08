<?php

namespace Mpociot\ApiDoc\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Mpociot\ApiDoc\Tools\Generator;
use Illuminate\Support\Facades\Storage;
use Mpociot\ApiDoc\Tools\DocumentationConfig;
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
                'value' => 9,
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
                'value' => false,
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
    public function it_ignores_non_commented_form_request()
    {
        $route = $this->createRoute('GET', '/api/test', 'withNonCommentedFormRequestParameter');
        $bodyParameters = $this->generator->processRoute($route)['bodyParameters'];

        $this->assertArraySubset([
            'direct_one' => [
                'type' => 'string',
                'description' => 'Is found directly on the method.',
            ],
        ], $bodyParameters);
    }

    /** @test */
    public function can_parse_form_request_body_parameters()
    {
        $route = $this->createRoute('GET', '/api/test', 'withFormRequestParameter');
        $bodyParameters = $this->generator->processRoute($route)['bodyParameters'];

        $this->assertArraySubset([
            'user_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'The id of the user.',
                'value' => 9,
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
                'value' => false,
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
    public function can_parse_multiple_form_request_body_parameters()
    {
        $route = $this->createRoute('GET', '/api/test', 'withMultipleFormRequestParameters');
        $bodyParameters = $this->generator->processRoute($route)['bodyParameters'];

        $this->assertArraySubset([
            'user_id' => [
                'type' => 'integer',
                'required' => true,
                'description' => 'The id of the user.',
                'value' => 9,
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
                'value' => false,
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
            'user_id' => [
                'required' => true,
                'description' => 'The id of the user.',
                'value' => 'me',
            ],
            'page' => [
                'required' => true,
                'description' => 'The page number.',
                'value' => '4',
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
        $response = array_first($parsed['response']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertArraySubset([
            'id' => 4,
            'name' => 'banana',
            'color' => 'red',
            'weight' => '1 kg',
            'delicious' => true,
        ], json_decode($response['content'], true));
    }

    /** @test */
    public function can_parse_response_tag_with_status_code()
    {
        $route = $this->createRoute('POST', '/responseTag', 'withResponseTagAndStatusCode');
        $parsed = $this->generator->processRoute($route);
        $response = array_first($parsed['response']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(422, $response['status']);
        $this->assertArraySubset([
            'message' => 'Validation error',
        ], json_decode($response['content'], true));
    }

    /** @test */
    public function can_parse_multiple_response_tags()
    {
        $route = $this->createRoute('POST', '/responseTag', 'withMultipleResponseTagsAndStatusCode');
        $parsed = $this->generator->processRoute($route);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($parsed['response'][0]));
        $this->assertEquals(200, $parsed['response'][0]['status']);
        $this->assertArraySubset([
            'id' => 4,
            'name' => 'banana',
            'color' => 'red',
            'weight' => '1 kg',
            'delicious' => true,
        ], json_decode($parsed['response'][0]['content'], true));
        $this->assertTrue(is_array($parsed['response'][1]));
        $this->assertEquals(401, $parsed['response'][1]['status']);
        $this->assertArraySubset([
            'message' => 'Unauthorized',
        ], json_decode($parsed['response'][1]['content'], true));
    }

    /**
     * @param $serializer
     * @param $expected
     *
     * @test
     * @dataProvider dataResources
     */
    public function can_parse_transformer_tag($serializer, $expected)
    {
        config(['apidoc.fractal.serializer' => $serializer]);
        $route = $this->createRoute('GET', '/transformerTag', 'transformerTag');
        $parsed = $this->generator->processRoute($route);
        $response = array_first($parsed['response']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertSame(
            $response['content'],
            $expected
        );
    }

    public function dataResources()
    {
        return [
            [
                null,
                '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}',
            ],
            [
                'League\Fractal\Serializer\JsonApiSerializer',
                '{"data":{"type":null,"id":"1","attributes":{"description":"Welcome on this test versions","name":"TestName"}}}',
            ],
        ];
    }

    /** @test */
    public function can_parse_transformer_tag_with_model()
    {
        $route = $this->createRoute('GET', '/transformerTagWithModel', 'transformerTagWithModel');
        $parsed = $this->generator->processRoute($route);
        $response = array_first($parsed['response']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertSame(
            $response['content'],
            '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}'
        );
    }

    /** @test */
    public function can_parse_transformer_collection_tag()
    {
        $route = $this->createRoute('GET', '/transformerCollectionTag', 'transformerCollectionTag');
        $parsed = $this->generator->processRoute($route);
        $response = array_first($parsed['response']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertSame(
            $response['content'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},'.
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }

    /** @test */
    public function can_parse_transformer_collection_tag_with_model()
    {
        $route = $this->createRoute('GET', '/transformerCollectionTagWithModel', 'transformerCollectionTagWithModel');
        $parsed = $this->generator->processRoute($route);
        $response = array_first($parsed['response']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertSame(
            $response['content'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},'.
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }

    /** @test */
    public function can_call_route_and_generate_response()
    {
        $route = $this->createRoute('POST', '/shouldFetchRouteResponse', 'shouldFetchRouteResponse', true);

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
        $response = array_first($parsed['response']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertArraySubset([
            'id' => 4,
            'name' => 'banana',
            'color' => 'red',
            'weight' => '1 kg',
            'delicious' => true,
        ], json_decode($response['content'], true));
    }

    /** @test */
    public function can_parse_response_file_tag()
    {
        // copy file to storage
        $filePath = __DIR__.'/../Fixtures/response_test.json';
        $fixtureFileJson = file_get_contents($filePath);
        copy($filePath, storage_path('response_test.json'));

        $route = $this->createRoute('GET', '/responseFileTag', 'responseFileTag');
        $parsed = $this->generator->processRoute($route);
        $response = array_first($parsed['response']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertSame(
            $response['content'],
            $fixtureFileJson
        );

        unlink(storage_path('response_test.json'));
    }

    /** @test */
    public function can_add_or_replace_key_value_pair_in_response_file()
    {
        // copy file to storage
        $filePath = __DIR__.'/../Fixtures/response_test.json';
        $fixtureFileJson = file_get_contents($filePath);
        copy($filePath, storage_path('response_test.json'));

        $route = $this->createRoute('GET', '/responseFileTagAndCustomJson', 'responseFileTagAndCustomJson');
        $parsed = $this->generator->processRoute($route);
        $response = array_first($parsed['response']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertNotSame(
            $response['content'],
            $fixtureFileJson
        );

        unlink(storage_path('response_test.json'));
    }

    /** @test */
    public function can_parse_multiple_response_file_tags_with_status_codes()
    {
        // copy file to storage
        $successFilePath = __DIR__.'/../Fixtures/response_test.json';
        $successFixtureFileJson = file_get_contents($successFilePath);
        copy($successFilePath, storage_path('response_test.json'));
        $errorFilePath = __DIR__.'/../Fixtures/response_error_test.json';
        $errorFixtureFileJson = file_get_contents($errorFilePath);
        copy($errorFilePath, storage_path('response_error_test.json'));

        $route = $this->createRoute('GET', '/responseFileTag', 'withResponseFileTagAndStatusCode');
        $parsed = $this->generator->processRoute($route);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($parsed['response'][0]));
        $this->assertEquals(200, $parsed['response'][0]['status']);
        $this->assertSame(
            $parsed['response'][0]['content'],
            $successFixtureFileJson
        );
        $this->assertTrue(is_array($parsed['response'][1]));
        $this->assertEquals(401, $parsed['response'][1]['status']);
        $this->assertSame(
            $parsed['response'][1]['content'],
            $errorFixtureFileJson
        );

        unlink(storage_path('response_test.json'));
        unlink(storage_path('response_error_test.json'));
    }

    /** @test */
    public function generates_consistent_examples_when_faker_seed_is_set()
    {
        $route = $this->createRoute('GET', '/withBodyParameters', 'withBodyParameters');

        $paramName = 'room_id';
        $results = [];
        $results[$this->generator->processRoute($route)['cleanBodyParameters'][$paramName]] = true;
        $results[$this->generator->processRoute($route)['cleanBodyParameters'][$paramName]] = true;
        $results[$this->generator->processRoute($route)['cleanBodyParameters'][$paramName]] = true;
        $results[$this->generator->processRoute($route)['cleanBodyParameters'][$paramName]] = true;
        $results[$this->generator->processRoute($route)['cleanBodyParameters'][$paramName]] = true;
        // Examples should have different values
        $this->assertNotEquals(count($results), 1);

        $generator = new Generator(new DocumentationConfig(['faker_seed' => 12345]));
        $results = [];
        $results[$generator->processRoute($route)['cleanBodyParameters'][$paramName]] = true;
        $results[$generator->processRoute($route)['cleanBodyParameters'][$paramName]] = true;
        $results[$generator->processRoute($route)['cleanBodyParameters'][$paramName]] = true;
        $results[$generator->processRoute($route)['cleanBodyParameters'][$paramName]] = true;
        // Examples should have same values
        $this->assertEquals(count($results), 1);
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
        $response = array_first($parsed['response']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $responseContent = json_decode($response['content'], true);
        $this->assertEquals(3, $responseContent['{id}']);
        $this->assertEquals('documentation', $responseContent['APP_ENV']);
        $this->assertEquals('queryValue', $responseContent['queryParam']);
        $this->assertEquals('bodyValue', $responseContent['bodyParam']);
        $this->assertEquals('value', $responseContent['header']);
    }

    abstract public function createRoute(string $httpMethod, string $path, string $controllerMethod, $register = false);
}
