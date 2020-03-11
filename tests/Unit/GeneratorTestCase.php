<?php

/** @noinspection ALL */

namespace Mpociot\ApiDoc\Tests\Unit;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Support\Arr;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Mpociot\ApiDoc\Extracting\Generator;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;
use Mpociot\ApiDoc\Tests\Fixtures\TestUser;
use Mpociot\ApiDoc\Tools\DocumentationConfig;
use Orchestra\Testbench\TestCase;

abstract class GeneratorTestCase extends TestCase
{
    use ArraySubsetAsserts;

    /**
     * @var \Mpociot\ApiDoc\Extracting\Generator
     */
    protected $generator;
    private $config = [
        'strategies' => [
            'metadata' => [
                \Mpociot\ApiDoc\Extracting\Strategies\Metadata\GetFromDocBlocks::class,
            ],
            'urlParameters' => [
                \Mpociot\ApiDoc\Extracting\Strategies\UrlParameters\GetFromUrlParamTag::class,
            ],
            'queryParameters' => [
                \Mpociot\ApiDoc\Extracting\Strategies\QueryParameters\GetFromQueryParamTag::class,
            ],
            'headers' => [
                \Mpociot\ApiDoc\Extracting\Strategies\RequestHeaders\GetFromRouteRules::class,
            ],
            'bodyParameters' => [
                \Mpociot\ApiDoc\Extracting\Strategies\BodyParameters\GetFromBodyParamTag::class,
            ],
            'responses' => [
                \Mpociot\ApiDoc\Extracting\Strategies\Responses\UseTransformerTags::class,
                \Mpociot\ApiDoc\Extracting\Strategies\Responses\UseResponseTag::class,
                \Mpociot\ApiDoc\Extracting\Strategies\Responses\UseResponseFileTag::class,
                \Mpociot\ApiDoc\Extracting\Strategies\Responses\UseApiResourceTags::class,
                \Mpociot\ApiDoc\Extracting\Strategies\Responses\ResponseCalls::class,
            ],
        ],
        'default_group' => 'general',
    ];

    public static $globalValue = null;

    protected function getPackageProviders($app)
    {
        return [
            ApiDocGeneratorServiceProvider::class,
        ];
    }

    /**
     * Setup the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        $factory = app(\Illuminate\Database\Eloquent\Factory::class);
        $factory->define(TestUser::class, function () {
            return [
                'id' => 4,
                'first_name' => 'Tested',
                'last_name' => 'Again',
                'email' => 'a@b.com',
            ];
        });
        $this->generator = new Generator(new DocumentationConfig($this->config));
    }

    /** @test */
    public function can_parse_endpoint_description()
    {
        $route = $this->createRoute('GET', '/api/test', 'withEndpointDescription');
        $parsed = $this->generator->processRoute($route);

        $this->assertSame('Example title.', $parsed['metadata']['title']);
        $this->assertSame("This will be the long description.\nIt can also be multiple lines long.", $parsed['metadata']['description']);
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
                'description' => 'Some object params.',
            ],
            'yet_another_param.name' => [
                'type' => 'string',
                'description' => 'Subkey in the object param.',
                'required' => true,
            ],
            'even_more_param' => [
                'type' => 'array',
                'required' => false,
                'description' => 'Some array params.',
            ],
            'even_more_param.*' => [
                'type' => 'float',
                'description' => 'Subkey in the array param.',
                'required' => false,
            ],
            'book.name' => [
                'type' => 'string',
                'description' => '',
                'required' => false,
            ],
            'book.author_id' => [
                'type' => 'integer',
                'description' => '',
                'required' => false,
            ],
            'book[pages_count]' => [
                'type' => 'integer',
                'description' => '',
                'required' => false,
            ],
            'ids.*' => [
                'type' => 'integer',
                'description' => '',
                'required' => false,
            ],
            'users.*.first_name' => [
                'type' => 'string',
                'description' => 'The first name of the user.',
                'required' => false,
                'value' => 'John',
            ],
            'users.*.last_name' => [
                'type' => 'string',
                'description' => 'The last name of the user.',
                'required' => false,
                'value' => 'Doe',
            ],
        ], $bodyParameters);
    }

    /** @test */
    public function can_parse_body_parameters_as_array()
    {
        $route = $this->createRoute('GET', '/api/test', 'withBodyParametersAsArray');
        $generator = $this->generator->processRoute($route);
        $bodyParameters = $generator['bodyParameters'];
        $cleanBodyParameters = $generator['cleanBodyParameters'];

        $this->assertArraySubset([
            '*.first_name' => [
                'type' => 'string',
                'description' => 'The first name of the user.',
                'required' => false,
                'value' => 'John',
            ],
            '*.last_name' => [
                'type' => 'string',
                'description' => 'The last name of the user.',
                'required' => false,
                'value' => 'Doe',
            ],
            '*.contacts.*.first_name' => [
                'type' => 'string',
                'description' => 'The first name of the contact.',
                'required' => false,
                'value' => 'John',
            ],
            '*.contacts.*.last_name' => [
                'type' => 'string',
                'description' => 'The last name of the contact.',
                'required' => false,
                'value' => 'Doe',
            ],
            '*.roles.*' => [
                'type' => 'string',
                'description' => 'The name of the role.',
                'required' => false,
                'value' => 'Admin',
            ],
        ], $bodyParameters);

        $this->assertArraySubset([
            [
               'first_name' => 'John',
               'last_name' => 'Doe',
               'contacts' => [
                    [
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                    ]
                ],
                'roles' => [
                    'Admin'
                ]
            ]
        ], $cleanBodyParameters);
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
    public function it_does_not_generate_values_for_excluded_params_and_excludes_them_from_clean_params()
    {
        $route = $this->createRoute('GET', '/api/test', 'withExcludedExamples');
        $parsed = $this->generator->processRoute($route);
        $cleanBodyParameters = $parsed['cleanBodyParameters'];
        $cleanQueryParameters = $parsed['cleanQueryParameters'];
        $bodyParameters = $parsed['bodyParameters'];
        $queryParameters = $parsed['queryParameters'];

        $this->assertArrayHasKey('included', $cleanBodyParameters);
        $this->assertArrayNotHasKey('excluded_body_param', $cleanBodyParameters);
        $this->assertEmpty($cleanQueryParameters);

        $this->assertArraySubset([
            'included' => [
                'required' => true,
                'type' => 'string',
                'description' => 'Exists in examples.',
            ],
            'excluded_body_param' => [
                'type' => 'integer',
                'description' => 'Does not exist in examples.',
            ],
        ], $bodyParameters);

        $this->assertArraySubset([
            'excluded_query_param' => [
                'description' => 'Does not exist in examples.',
            ],
        ], $queryParameters);
    }

    /** @test */
    public function can_parse_route_group()
    {
        $route = $this->createRoute('GET', '/api/test', 'dummy');
        $routeGroup = $this->generator->processRoute($route)['metadata']['groupName'];

        $this->assertSame('Group A', $routeGroup);
    }

    /** @test */
    public function method_can_override_controller_group()
    {
        $route = $this->createRoute('GET', '/group/1', 'withGroupOverride');
        $parsedRoute = $this->generator->processRoute($route);
        $this->assertSame('Group B', $parsedRoute['metadata']['groupName']);
        $this->assertSame('', $parsedRoute['metadata']['groupDescription']);

        $route = $this->createRoute('GET', '/group/2', 'withGroupOverride2');
        $parsedRoute = $this->generator->processRoute($route);
        $this->assertSame('Group B', $parsedRoute['metadata']['groupName']);
        $this->assertSame('', $parsedRoute['metadata']['groupDescription']);
        $this->assertSame('This is also in Group B. No route description. Route title before gropp.', $parsedRoute['metadata']['title']);

        $route = $this->createRoute('GET', '/group/3', 'withGroupOverride3');
        $parsedRoute = $this->generator->processRoute($route);
        $this->assertSame('Group B', $parsedRoute['metadata']['groupName']);
        $this->assertSame('', $parsedRoute['metadata']['groupDescription']);
        $this->assertSame('This is also in Group B. Route title after group.', $parsedRoute['metadata']['title']);

        $route = $this->createRoute('GET', '/group/4', 'withGroupOverride4');
        $parsedRoute = $this->generator->processRoute($route);
        $this->assertSame('Group C', $parsedRoute['metadata']['groupName']);
        $this->assertSame('Group description after group.', $parsedRoute['metadata']['groupDescription']);
        $this->assertSame('This is in Group C. Route title before group.', $parsedRoute['metadata']['title']);
    }

    /** @test */
    public function can_parse_auth_tags()
    {
        $route = $this->createRoute('GET', '/api/test', 'withAuthenticatedTag');
        $authenticated = $this->generator->processRoute($route)['metadata']['authenticated'];
        $this->assertTrue($authenticated);

        $route = $this->createRoute('GET', '/api/test', 'dummy');
        $authenticated = $this->generator->processRoute($route)['metadata']['authenticated'];
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
    public function can_parse_apiresource_tags()
    {
        $route = $this->createRoute('POST', '/withEloquentApiResource', 'withEloquentApiResource');

        $config = $this->config;
        $config['strategies']['responses'] = [\Mpociot\ApiDoc\Extracting\Strategies\Responses\UseApiResourceTags::class];
        $generator = new Generator(new DocumentationConfig($config));
        $parsed = $this->generator->processRoute($route);

        $response = Arr::first($parsed['responses']);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertArraySubset([
            'data' => [
                'id' => 4,
                'name' => 'Tested Again',
                'email' => 'a@b.com',
            ],
        ], json_decode($response['content'], true));
    }

    /** @test */
    public function can_parse_apiresourcecollection_tags()
    {
        $route = $this->createRoute('POST', '/withEloquentApiResourceCollection', 'withEloquentApiResourceCollection');

        $config = $this->config;
        $config['strategies']['responses'] = [\Mpociot\ApiDoc\Extracting\Strategies\Responses\UseApiResourceTags::class];
        $generator = new Generator(new DocumentationConfig($config));
        $parsed = $this->generator->processRoute($route);

        $response = Arr::first($parsed['responses']);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $content = json_decode($response['content'], true);
        $this->assertIsArray($content);
        $this->assertArraySubset([
            'id' => 4,
            'name' => 'Tested Again',
            'email' => 'a@b.com',
        ], $content['data'][0]);
        $this->assertArraySubset([
            'id' => 4,
            'name' => 'Tested Again',
            'email' => 'a@b.com',
        ], $content['data'][1]);
    }

    /** @test */
    public function can_parse_apiresourcecollection_tags_with_collection_class()
    {
        $route = $this->createRoute('POST', '/withEloquentApiResourceCollectionClass', 'withEloquentApiResourceCollectionClass');

        $config = $this->config;
        $config['strategies']['responses'] = [\Mpociot\ApiDoc\Extracting\Strategies\Responses\UseApiResourceTags::class];
        $generator = new Generator(new DocumentationConfig($config));
        $parsed = $this->generator->processRoute($route);

        $response = Arr::first($parsed['responses']);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $content = json_decode($response['content'], true);
        $this->assertIsArray($content);
        $this->assertArraySubset([
            'data' => [
                [
                    'id' => 4,
                    'name' => 'Tested Again',
                    'email' => 'a@b.com',
                ],
                [
                    'id' => 4,
                    'name' => 'Tested Again',
                    'email' => 'a@b.com',
                ],
            ],
            'links' => [
                'self' => 'link-value',
            ],
        ], $content);
    }

    /** @test */
    public function can_parse_response_tag()
    {
        $route = $this->createRoute('POST', '/responseTag', 'withResponseTag');
        $parsed = $this->generator->processRoute($route);
        $response = Arr::first($parsed['responses']);

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
        $response = Arr::first($parsed['responses']);

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
        $this->assertTrue(is_array($parsed['responses'][0]));
        $this->assertEquals(200, $parsed['responses'][0]['status']);
        $this->assertArraySubset([
            'id' => 4,
            'name' => 'banana',
            'color' => 'red',
            'weight' => '1 kg',
            'delicious' => true,
        ], json_decode($parsed['responses'][0]['content'], true));
        $this->assertTrue(is_array($parsed['responses'][1]));
        $this->assertEquals(401, $parsed['responses'][1]['status']);
        $this->assertArraySubset([
            'message' => 'Unauthorized',
        ], json_decode($parsed['responses'][1]['content'], true));
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
        $response = Arr::first($parsed['responses']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertSame(
            $expected,
            $response['content']
        );
    }

    /** @test */
    public function can_parse_transformer_tag_with_model()
    {
        $route = $this->createRoute('GET', '/transformerTagWithModel', 'transformerTagWithModel');
        $parsed = $this->generator->processRoute($route);
        $response = Arr::first($parsed['responses']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertSame(
            '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}',
            $response['content']
        );
    }

    /** @test */
    public function can_parse_transformer_tag_with_status_code()
    {
        $route = $this->createRoute('GET', '/transformerTagWithStatusCode', 'transformerTagWithStatusCode');
        $parsed = $this->generator->processRoute($route);
        $response = Arr::first($parsed['responses']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(201, $response['status']);
        $this->assertSame(
            '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}',
            $response['content']
        );
    }

    /** @test */
    public function can_parse_transformer_collection_tag()
    {
        $route = $this->createRoute('GET', '/transformerCollectionTag', 'transformerCollectionTag');
        $parsed = $this->generator->processRoute($route);
        $response = Arr::first($parsed['responses']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertSame(
            $response['content'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},' .
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }

    /** @test */
    public function can_parse_transformer_collection_tag_with_model()
    {
        $route = $this->createRoute('GET', '/transformerCollectionTagWithModel', 'transformerCollectionTagWithModel');
        $parsed = $this->generator->processRoute($route);
        $response = Arr::first($parsed['responses']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $this->assertSame(
            $response['content'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},' .
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }

    /** @test */
    public function can_call_route_and_generate_response()
    {
        $route = $this->createRoute('POST', '/shouldFetchRouteResponse', 'shouldFetchRouteResponse', true);

        $rules = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'response_calls' => [
                'methods' => ['*'],
            ],
        ];
        $parsed = $this->generator->processRoute($route, $rules);
        $response = Arr::first($parsed['responses']);

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
    public function does_not_make_response_call_if_success_response_already_gotten()
    {
        $route = $this->createRoute('POST', '/withResponseTag', 'withResponseTag', true);

        $rules = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'response_calls' => [
                'methods' => ['*'],
            ],
        ];
        $config = [
            'strategies' => [
                'responses' => [
                    \Mpociot\ApiDoc\Extracting\Strategies\Responses\UseResponseTag::class,
                    \Mpociot\ApiDoc\Extracting\Strategies\Responses\ResponseCalls::class,
                ],
            ],
        ];
        $generator = new Generator(new DocumentationConfig($config));
        $parsed = $generator->processRoute($route, $rules);

        $this->assertCount(1, $parsed['responses']);
        $response = Arr::first($parsed['responses']);

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
            'responseTag' => true,
        ], json_decode($response['content'], true));

        // This may probably not be the best way to test this, but 🤷‍♀️
        $this->assertNull(static::$globalValue);
    }

    /** @test */
    public function can_override_config_during_response_call()
    {
        $route = $this->createRoute('POST', '/echoesConfig', 'echoesConfig', true);

        $rules = [
            'response_calls' => [
                'methods' => ['*'],
            ],
        ];
        $parsed = $this->generator->processRoute($route, $rules);
        $response = json_decode(Arr::first($parsed['responses'])['content'], true);
        $originalValue = $response['app.env'];

        $now = time();
        $rules = [
            'response_calls' => [
                'methods' => ['*'],
                'config' => [
                    'app.env' => $now,
                ],
            ],
        ];
        $parsed = $this->generator->processRoute($route, $rules);
        $response = json_decode(Arr::first($parsed['responses'])['content'], true);
        $newValue = $response['app.env'];
        $this->assertEquals($now, $newValue);
        $this->assertNotEquals($originalValue, $newValue);
    }

    /** @test */
    public function can_override_url_path_parameters_with_urlparam_annotation()
    {
        $route = $this->createRoute('POST', '/echoesUrlParameters/{param}', 'echoesUrlParameters', true);
        $rules = [
            'response_calls' => [
                'methods' => ['*'],
            ],
        ];
        $parsed = $this->generator->processRoute($route, $rules);
        $response = json_decode(Arr::first($parsed['responses'])['content'], true);
        $this->assertEquals(4, $response['param']);
    }

    /** @test */
    public function ignores_or_inserts_optional_url_path_parameters_according_to_annotations()
    {
        $route = $this->createRoute('POST', '/echoesUrlParameters/{param}/{param2?}/{param3}/{param4?}', 'echoesUrlParameters', true);

        $rules = [
            'response_calls' => [
                'methods' => ['*'],
            ],
        ];
        $parsed = $this->generator->processRoute($route, $rules);
        $response = json_decode(Arr::first($parsed['responses'])['content'], true);
        $this->assertEquals(4, $response['param']);
        $this->assertNotNull($response['param2']);
        $this->assertEquals(1, $response['param3']);
        $this->assertNull($response['param4']);
    }

    /** @test */
    public function can_parse_response_file_tag()
    {
        // copy file to storage
        $filePath = __DIR__ . '/../Fixtures/response_test.json';
        $fixtureFileJson = file_get_contents($filePath);
        copy($filePath, storage_path('response_test.json'));

        $route = $this->createRoute('GET', '/responseFileTag', 'responseFileTag');
        $parsed = $this->generator->processRoute($route);
        $response = Arr::first($parsed['responses']);

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
        $filePath = __DIR__ . '/../Fixtures/response_test.json';
        $fixtureFileJson = file_get_contents($filePath);
        copy($filePath, storage_path('response_test.json'));

        $route = $this->createRoute('GET', '/responseFileTagAndCustomJson', 'responseFileTagAndCustomJson');
        $parsed = $this->generator->processRoute($route);
        $response = Arr::first($parsed['responses']);

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
        $successFilePath = __DIR__ . '/../Fixtures/response_test.json';
        $successFixtureFileJson = file_get_contents($successFilePath);
        copy($successFilePath, storage_path('response_test.json'));
        $errorFilePath = __DIR__ . '/../Fixtures/response_error_test.json';
        $errorFixtureFileJson = file_get_contents($errorFilePath);
        copy($errorFilePath, storage_path('response_error_test.json'));

        $route = $this->createRoute('GET', '/responseFileTag', 'withResponseFileTagAndStatusCode');
        $parsed = $this->generator->processRoute($route);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($parsed['responses'][0]));
        $this->assertEquals(200, $parsed['responses'][0]['status']);
        $this->assertSame(
            $parsed['responses'][0]['content'],
            $successFixtureFileJson
        );
        $this->assertTrue(is_array($parsed['responses'][1]));
        $this->assertEquals(401, $parsed['responses'][1]['status']);
        $this->assertSame(
            $parsed['responses'][1]['content'],
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

        $generator = new Generator(new DocumentationConfig($this->config + ['faker_seed' => 12345]));
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
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'header' => 'value',
            ],
            'response_calls' => [
                'methods' => ['*'],
                'queryParams' => [
                    'queryParam' => 'queryValue',
                ],
                'bodyParams' => [
                    'bodyParam' => 'bodyValue',
                ],
            ],
        ];
        $parsed = $this->generator->processRoute($route, $rules);
        $response = Arr::first($parsed['responses']);

        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertTrue(is_array($response));
        $this->assertEquals(200, $response['status']);
        $responseContent = json_decode($response['content'], true);
        $this->assertEquals('queryValue', $responseContent['queryParam']);
        $this->assertEquals('bodyValue', $responseContent['bodyParam']);
        $this->assertEquals('value', $responseContent['header']);
    }

    /** @test */
    public function can_use_arrays_in_routes_uses()
    {
        $route = $this->createRouteUsesArray('GET', '/api/array/test', 'withEndpointDescription');

        $parsed = $this->generator->processRoute($route);

        $this->assertSame('Example title.', $parsed['metadata']['title']);
        $this->assertSame("This will be the long description.\nIt can also be multiple lines long.", $parsed['metadata']['description']);
    }

    abstract public function createRoute(string $httpMethod, string $path, string $controllerMethod, $register = false, $class = TestController::class);

    abstract public function createRouteUsesArray(string $httpMethod, string $path, string $controllerMethod, $register = false, $class = TestController::class);

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
}
