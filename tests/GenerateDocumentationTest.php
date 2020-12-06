<?php

namespace Mpociot\ApiDoc\Tests;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;
use Mpociot\ApiDoc\Tests\Fixtures\TestGroupController;
use Mpociot\ApiDoc\Tests\Fixtures\TestPartialResourceController;
use Mpociot\ApiDoc\Tests\Fixtures\TestResourceController;
use Mpociot\ApiDoc\Tests\Fixtures\TestUser;
use Mpociot\ApiDoc\Tools\Utils;
use Orchestra\Testbench\TestCase;
use ReflectionException;

class GenerateDocumentationTest extends TestCase
{
    use TestHelpers;

    protected function setUp(): void
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
    }

    public function tearDown(): void
    {
        Utils::deleteDirectoryAndContents('/public/docs');
        Utils::deleteDirectoryAndContents('/resources/docs');
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ApiDocGeneratorServiceProvider::class,
        ];
    }

    /** @test */
    public function console_command_does_not_work_with_closure()
    {
        RouteFacade::get('/api/closure', function () {
            return 'hi';
        });
        RouteFacade::get('/api/test', TestController::class . '@withEndpointDescription');

        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        $output = $this->artisan('apidoc:generate');

        $this->assertStringContainsString('Skipping route: [GET] api/closure', $output);
        $this->assertStringContainsString('Processed route: [GET] api/test', $output);
    }

    /** @test */
    public function console_command_works_with_routes_callable_tuple()
    {
        RouteFacade::get('/api/array/test', [TestController::class, 'withEndpointDescription']);

        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        $output = $this->artisan('apidoc:generate');

        $this->assertStringNotContainsString('Skipping route: [GET] api/array/test', $output);
        $this->assertStringContainsString('Processed route: [GET] api/array/test', $output);
    }

    /** @test */
    public function can_skip_single_routes()
    {
        RouteFacade::get('/api/skip', TestController::class . '@skip');
        RouteFacade::get('/api/test', TestController::class . '@withEndpointDescription');

        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        $output = $this->artisan('apidoc:generate');

        $this->assertStringContainsString('Skipping route: [GET] api/skip', $output);
        $this->assertStringContainsString('Processed route: [GET] api/test', $output);
    }

    /** @test */
    public function can_skip_non_existent_response_files()
    {
        RouteFacade::get('/api/non-existent', TestController::class . '@withNonExistentResponseFile');

        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        $output = $this->artisan('apidoc:generate');

        $this->assertStringContainsString('Skipping route: [GET] api/non-existent', $output);
        $this->assertStringContainsString('@responseFile i-do-not-exist.json does not exist', $output);
    }

    /** @test */
    public function can_parse_resource_routes()
    {
        RouteFacade::resource('/api/users', TestResourceController::class);

        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        config([
            'apidoc.routes.0.apply.headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $this->artisan('apidoc:generate');

        $fixtureMarkdown = __DIR__ . '/Fixtures/resource_index.md';
        $generatedMarkdown = __DIR__ . '/../resources/docs/source/index.md';
        $this->assertFilesHaveSameContent($fixtureMarkdown, $generatedMarkdown);
    }

    /** @test */
    public function can_parse_partial_resource_routes()
    {
        RouteFacade::resource('/api/users', TestResourceController::class)
                ->only(['index', 'create']);

        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        config([
            'apidoc.routes.0.apply.headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $this->artisan('apidoc:generate');

        $fixtureMarkdown = __DIR__ . '/Fixtures/partial_resource_index.md';
        $generatedMarkdown = __DIR__ . '/../resources/docs/source/index.md';
        $this->assertFilesHaveSameContent($fixtureMarkdown, $generatedMarkdown);

        RouteFacade::apiResource('/api/users', TestResourceController::class)
                ->only(['index', 'create']);

        $this->artisan('apidoc:generate');

        $fixtureMarkdown = __DIR__ . '/Fixtures/partial_resource_index.md';
        $generatedMarkdown = __DIR__ . '/../resources/docs/source/index.md';
        $this->assertFilesHaveSameContent($fixtureMarkdown, $generatedMarkdown);
    }

    /** @test */
    public function generated_markdown_file_is_correct()
    {
        RouteFacade::get('/api/withDescription', [TestController::class, 'withEndpointDescription']);
        RouteFacade::get('/api/withResponseTag', TestController::class . '@withResponseTag');
        RouteFacade::get('/api/withBodyParameters', TestController::class . '@withBodyParameters');
        RouteFacade::get('/api/withQueryParameters', TestController::class . '@withQueryParameters');
        RouteFacade::get('/api/withAuthTag', TestController::class . '@withAuthenticatedTag');
        RouteFacade::get('/api/withEloquentApiResource', [TestController::class, 'withEloquentApiResource']);
        RouteFacade::get('/api/withEloquentApiResourceCollectionClass', [TestController::class, 'withEloquentApiResourceCollectionClass']);
        RouteFacade::post('/api/withMultipleResponseTagsAndStatusCode', [TestController::class, 'withMultipleResponseTagsAndStatusCode']);
        RouteFacade::get('/api/echoesUrlParameters/{param}-{param2}/{param3?}', [TestController::class, 'echoesUrlParameters']);

        // We want to have the same values for params each time
        config(['apidoc.type' => 'static']);
        config(['apidoc.faker_seed' => 1234]);
        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        config([
            'apidoc.routes.0.apply.headers' => [
                'Authorization' => 'customAuthToken',
                'Custom-Header' => 'NotSoCustom',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
        $this->artisan('apidoc:generate');

        $generatedMarkdown = __DIR__ . '/../resources/docs/source/index.md';
        $compareMarkdown = __DIR__ . '/../resources/docs/source/.compare.md';
        $fixtureMarkdown = __DIR__ . '/Fixtures/index.md';

        $this->assertFilesHaveSameContent($fixtureMarkdown, $generatedMarkdown);
        $this->assertFilesHaveSameContent($fixtureMarkdown, $compareMarkdown);
    }

    /** @test */
    public function can_prepend_and_append_data_to_generated_markdown()
    {
        RouteFacade::get('/api/test', TestController::class . '@withEndpointDescription');
        RouteFacade::get('/api/responseTag', TestController::class . '@withResponseTag');

        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        $this->artisan('apidoc:generate');

        $prependMarkdown = __DIR__ . '/Fixtures/prepend.md';
        $appendMarkdown = __DIR__ . '/Fixtures/append.md';
        copy($prependMarkdown, __DIR__ . '/../resources/docs/source/prepend.md');
        copy($appendMarkdown, __DIR__ . '/../resources/docs/source/append.md');

        $this->artisan('apidoc:generate');

        $generatedMarkdown = __DIR__ . '/../resources/docs/source/index.md';
        $this->assertContainsIgnoringWhitespace($this->getFileContents($prependMarkdown), $this->getFileContents($generatedMarkdown));
        $this->assertContainsIgnoringWhitespace($this->getFileContents($appendMarkdown), $this->getFileContents($generatedMarkdown));
    }

    /** @test */
    public function generated_postman_collection_file_is_correct()
    {
        RouteFacade::get('/api/withDescription', [TestController::class, 'withEndpointDescription']);
        RouteFacade::get('/api/withResponseTag', TestController::class . '@withResponseTag');
        RouteFacade::post('/api/withBodyParameters', TestController::class . '@withBodyParameters');
        RouteFacade::get('/api/withQueryParameters', TestController::class . '@withQueryParameters');
        RouteFacade::get('/api/withAuthTag', TestController::class . '@withAuthenticatedTag');
        RouteFacade::get('/api/withEloquentApiResource', [TestController::class, 'withEloquentApiResource']);
        RouteFacade::get('/api/withEloquentApiResourceCollectionClass', [TestController::class, 'withEloquentApiResourceCollectionClass']);
        RouteFacade::post('/api/withMultipleResponseTagsAndStatusCode', [TestController::class, 'withMultipleResponseTagsAndStatusCode']);
        RouteFacade::get('/api/echoesUrlParameters/{param}-{param2}/{param3?}', [TestController::class, 'echoesUrlParameters']);

        // We want to have the same values for params each time
        config(['apidoc.faker_seed' => 1234]);
        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        config([
            'apidoc.routes.0.apply.headers' => [
                'Authorization' => 'customAuthToken',
                'Custom-Header' => 'NotSoCustom',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->artisan('apidoc:generate');

        $generatedCollection = json_decode(file_get_contents(__DIR__ . '/../public/docs/collection.json'), true);
        // The Postman ID varies from call to call; erase it to make the test data reproducible.
        $generatedCollection['info']['_postman_id'] = '';
        $fixtureCollection = json_decode(file_get_contents(__DIR__ . '/Fixtures/collection.json'), true);
        $this->assertEquals($fixtureCollection, $generatedCollection);
    }

    /** @test */
    public function generated_postman_collection_domain_is_correct()
    {
        $domain = 'http://somedomain.test';
        RouteFacade::get('/api/test', TestController::class . '@withEndpointDescription');

        config(['apidoc.base_url' => $domain]);
        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        $this->artisan('apidoc:generate');

        $generatedCollection = json_decode(file_get_contents(__DIR__ . '/../public/docs/collection.json'));
        $endpointUrl = $generatedCollection->item[0]->item[0]->request->url->host;
        $this->assertTrue(Str::startsWith($endpointUrl, 'somedomain.test'));
    }

    /** @test */
    public function generated_postman_collection_can_have_custom_url()
    {
        Config::set('apidoc.base_url', 'http://yourapp.app');
        RouteFacade::get('/api/test', TestController::class . '@withEndpointDescription');
        RouteFacade::post('/api/responseTag', TestController::class . '@withResponseTag');

        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        $this->artisan('apidoc:generate');

        $generatedCollection = json_decode(file_get_contents(__DIR__ . '/../public/docs/collection.json'), true);
        // The Postman ID varies from call to call; erase it to make the test data reproducible.
        $generatedCollection['info']['_postman_id'] = '';
        $fixtureCollection = json_decode(file_get_contents(__DIR__ . '/Fixtures/collection_custom_url.json'), true);
        $this->assertEquals($fixtureCollection, $generatedCollection);
    }

    /** @test */
    public function generated_postman_collection_can_have_secure_url()
    {
        Config::set('apidoc.base_url', 'https://yourapp.app');
        RouteFacade::get('/api/test', TestController::class . '@withEndpointDescription');
        RouteFacade::post('/api/responseTag', TestController::class . '@withResponseTag');

        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        $this->artisan('apidoc:generate');

        $generatedCollection = json_decode(file_get_contents(__DIR__ . '/../public/docs/collection.json'), true);
        // The Postman ID varies from call to call; erase it to make the test data reproducible.
        $generatedCollection['info']['_postman_id'] = '';
        $fixtureCollection = json_decode(file_get_contents(__DIR__ . '/Fixtures/collection_with_secure_url.json'), true);
        $this->assertEquals($fixtureCollection, $generatedCollection);
    }

    /** @test */
    public function generated_postman_collection_can_append_custom_http_headers()
    {
        RouteFacade::get('/api/headers', TestController::class . '@checkCustomHeaders');
        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        config([
            'apidoc.routes.0.apply.headers' => [
                'Authorization' => 'customAuthToken',
                'Custom-Header' => 'NotSoCustom',
            ],
        ]);
        $this->artisan('apidoc:generate');

        $generatedCollection = json_decode(file_get_contents(__DIR__ . '/../public/docs/collection.json'), true);
        // The Postman ID varies from call to call; erase it to make the test data reproducible.
        $generatedCollection['info']['_postman_id'] = '';
        $fixtureCollection = json_decode(file_get_contents(__DIR__ . '/Fixtures/collection_with_custom_headers.json'), true);
        $this->assertEquals($fixtureCollection, $generatedCollection);
    }

    /** @test */
    public function generated_postman_collection_can_have_query_parameters()
    {
        RouteFacade::get('/api/withQueryParameters', TestController::class . '@withQueryParameters');
        // We want to have the same values for params each time
        config(['apidoc.faker_seed' => 1234]);
        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        $this->artisan('apidoc:generate');

        $generatedCollection = json_decode(file_get_contents(__DIR__ . '/../public/docs/collection.json'), true);
        // The Postman ID varies from call to call; erase it to make the test data reproducible.
        $generatedCollection['info']['_postman_id'] = '';
        $fixtureCollection = json_decode(file_get_contents(__DIR__ . '/Fixtures/collection_with_query_parameters.json'), true);
        $this->assertEquals($fixtureCollection, $generatedCollection);
    }

    /** @test */
    public function generated_postman_collection_can_add_body_parameters()
    {
        RouteFacade::get('/api/withBodyParameters', TestController::class . '@withBodyParameters');
        // We want to have the same values for params each time
        config(['apidoc.faker_seed' => 1234]);
        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        $this->artisan('apidoc:generate');

        $generatedCollection = json_decode(file_get_contents(__DIR__ . '/../public/docs/collection.json'), true);
        // The Postman ID varies from call to call; erase it to make the test data reproducible.
        $generatedCollection['info']['_postman_id'] = '';
        $fixtureCollection = json_decode(file_get_contents(__DIR__ . '/Fixtures/collection_with_body_parameters.json'), true);
        $this->assertEquals($fixtureCollection, $generatedCollection);
    }

    /** @test */
    public function can_append_custom_http_headers()
    {
        RouteFacade::get('/api/headers', TestController::class . '@checkCustomHeaders');

        config(['apidoc.routes.0.match.prefixes' => ['api/*']]);
        config([
            'apidoc.routes.0.apply.headers' => [
                'Authorization' => 'customAuthToken',
                'Custom-Header' => 'NotSoCustom',
            ],
        ]);
        $this->artisan('apidoc:generate');

        $generatedMarkdown = $this->getFileContents(__DIR__ . '/../resources/docs/source/index.md');
        $this->assertContainsIgnoringWhitespace('"Authorization": "customAuthToken","Custom-Header":"NotSoCustom"', $generatedMarkdown);
    }

    /** @test */
    public function can_parse_utf8_response()
    {
        RouteFacade::get('/api/utf8', TestController::class . '@withUtf8ResponseTag');

        config(['apidoc.routes.0.prefixes' => ['api/*']]);
        $this->artisan('apidoc:generate');

        $generatedMarkdown = file_get_contents(__DIR__ . '/../resources/docs/source/index.md');
        $this->assertStringContainsString('Лорем ипсум долор сит амет', $generatedMarkdown);
    }

    /** @test */
    public function sorts_group_naturally()
    {
        RouteFacade::get('/api/action1', TestGroupController::class . '@action1');
        RouteFacade::get('/api/action1b', TestGroupController::class . '@action1b');
        RouteFacade::get('/api/action2', TestGroupController::class . '@action2');
        RouteFacade::get('/api/action10', TestGroupController::class . '@action10');

        config(['apidoc.routes.0.prefixes' => ['api/*']]);
        $this->artisan('apidoc:generate');
        $generatedMarkdown = file_get_contents(__DIR__ . '/../resources/docs/source/index.md');

        $firstGroup1Occurrence = strpos($generatedMarkdown, '#1. Group 1');
        $firstGroup2Occurrence = strpos($generatedMarkdown, '#2. Group 2');
        $firstGroup10Occurrence = strpos($generatedMarkdown, '#10. Group 10');

        $this->assertNotFalse($firstGroup1Occurrence);
        $this->assertNotFalse($firstGroup2Occurrence);
        $this->assertNotFalse($firstGroup2Occurrence);

        $this->assertTrue(
            $firstGroup1Occurrence < $firstGroup2Occurrence && $firstGroup2Occurrence < $firstGroup10Occurrence
        );
    }

    /** @test */
    public function supports_partial_resource_controller()
    {
        RouteFacade::resource('/api/partial', TestPartialResourceController::class);

        config(['apidoc.routes.0.prefixes' => ['api/*']]);

        $thrownException = null;

        try {
            $this->artisan('apidoc:generate');
        } catch (ReflectionException $e) {
            $thrownException = $e;
        }

        $this->assertNull($thrownException);
        $generatedMarkdown = file_get_contents(__DIR__ . '/../resources/docs/source/index.md');
        $this->assertStringContainsString('Group A', $generatedMarkdown);
        $this->assertStringContainsString('Group B', $generatedMarkdown);
    }
}
