<?php

namespace Mpociot\ApiDoc\Tests;

use Illuminate\Routing\Route;
use Orchestra\Testbench\TestCase;
use Illuminate\Contracts\Console\Kernel;
use Dingo\Api\Provider\LaravelServiceProvider;
use Mpociot\ApiDoc\Generators\LaravelGenerator;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Illuminate\Support\Facades\Route as RouteFacade;
use Mpociot\ApiDoc\Tests\Fixtures\DingoTestController;
use Mpociot\ApiDoc\Tests\Fixtures\TestResourceController;

class GenerateDocumentationTest extends TestCase
{
    /**
     * @var \Mpociot\ApiDoc\AbstractGenerator
     */
    protected $generator;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->generator = new LaravelGenerator();
    }

    public function tearDown()
    {
        exec('rm -rf '.__DIR__.'/../public/docs');
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            LaravelServiceProvider::class,
            ApiDocGeneratorServiceProvider::class,
        ];
    }

    public function testConsoleCommandNeedsAPrefixOrRoute()
    {
        $output = $this->artisan('api:generate');
        $this->assertEquals('You must provide either a route prefix, a route domain, a route or a middleware to generate the documentation.'.PHP_EOL, $output);
    }

    public function testConsoleCommandDoesNotWorkWithClosure()
    {
        RouteFacade::get('/api/closure', function () {
            return 'foo';
        });
        RouteFacade::get('/api/test', TestController::class.'@parseMethodDescription');

        $output = $this->artisan('api:generate', [
            '--routePrefix' => 'api/*',
        ]);
        $this->assertContains('Skipping route: [GET] api/closure', $output);
        $this->assertContains('Processed route: [GET] api/test', $output);
    }

    public function testConsoleCommandDoesNotWorkWithClosureUsingDingo()
    {
        if (version_compare($this->app->version(), '5.4', '>=')) {
            $this->markTestSkipped('Dingo does not support Laravel 5.4');
        }

        $api = app('Dingo\Api\Routing\Router');
        $api->version('v1', function ($api) {
            $api->get('/closure', function () {
                return 'foo';
            });
            $api->get('/test', DingoTestController::class.'@parseMethodDescription');

            $output = $this->artisan('api:generate', [
                '--router' => 'dingo',
                '--routePrefix' => 'v1',
            ]);
            $this->assertContains('Skipping route: [GET] closure', $output);
            $this->assertContains('Processed route: [GET] test', $output);
        });
    }

    public function testCanSkipSingleRoutesCommandDoesNotWorkWithClosure()
    {
        RouteFacade::get('/api/skip', TestController::class.'@skip');
        RouteFacade::get('/api/test', TestController::class.'@parseMethodDescription');

        $output = $this->artisan('api:generate', [
            '--routePrefix' => 'api/*',
        ]);
        $this->assertContains('Skipping route: [GET] api/skip', $output);
        $this->assertContains('Processed route: [GET] api/test', $output);
    }

    public function testCanParseResourceRoutes()
    {
        RouteFacade::resource('/api/user', TestResourceController::class);
        $output = $this->artisan('api:generate', [
            '--routePrefix' => 'api/*',
        ]);
        $generatedMarkdown = file_get_contents(__DIR__.'/../public/docs/source/index.md');
        $fixtureMarkdown = file_get_contents(__DIR__.'/Fixtures/resource_index.md');
        $this->assertSame($generatedMarkdown, $fixtureMarkdown);
    }

    public function testGeneratedMarkdownFileIsCorrect()
    {
        RouteFacade::get('/api/test', TestController::class.'@parseMethodDescription');
        RouteFacade::get('/api/fetch', TestController::class.'@fetchRouteResponse');

        $output = $this->artisan('api:generate', [
            '--routePrefix' => 'api/*',
        ]);

        $generatedMarkdown = file_get_contents(__DIR__.'/../public/docs/source/index.md');
        $compareMarkdown = file_get_contents(__DIR__.'/../public/docs/source/.compare.md');
        $fixtureMarkdown = file_get_contents(__DIR__.'/Fixtures/index.md');
        $this->assertSame($generatedMarkdown, $fixtureMarkdown);
        $this->assertSame($compareMarkdown, $fixtureMarkdown);
    }

    public function testAddsBindingsToGetRouteRules()
    {
        RouteFacade::get('/api/test/{foo}', TestController::class.'@addRouteBindingsToRequestClass');

        $this->artisan('api:generate', [
            '--routePrefix' => 'api/*',
            '--bindings' => 'foo,bar',
        ]);

        $generatedMarkdown = file_get_contents(__DIR__.'/../public/docs/source/index.md');

        $this->assertContains('Not in: `bar`', $generatedMarkdown);
    }

    public function testGeneratedPostmanCollectionFileIsCorrect()
    {
        RouteFacade::get('/api/test', TestController::class.'@parseMethodDescription');
        RouteFacade::post('/api/fetch', TestController::class.'@fetchRouteResponse');

        $output = $this->artisan('api:generate', [
            '--routePrefix' => 'api/*',
        ]);

        $generatedCollection = json_decode(file_get_contents(__DIR__.'/../public/docs/collection.json'));
        $generatedCollection->info->_postman_id = '';

        $fixtureCollection = json_decode(file_get_contents(__DIR__.'/Fixtures/collection.json'));
        $this->assertEquals($generatedCollection, $fixtureCollection);
    }

    public function testCanAppendCustomHttpHeaders()
    {
        RouteFacade::get('/api/headers', TestController::class.'@checkCustomHeaders');

        $output = $this->artisan('api:generate', [
            '--routePrefix' => 'api/*',
            '--header' => [
                'Authorization: customAuthToken',
                'X-Custom-Header: foobar',
            ],
        ]);

        $generatedMarkdown = file_get_contents(__DIR__.'/../public/docs/source/index.md');
        $this->assertContains('"authorization": [
        "customAuthToken"
    ],
    "x-custom-header": [
        "foobar"
    ]', $generatedMarkdown);
    }

    public function testGeneratesUTF8Responses()
    {
        RouteFacade::get('/api/utf8', TestController::class.'@utf8');

        $output = $this->artisan('api:generate', [
            '--routePrefix' => 'api/*',
        ]);

        $generatedMarkdown = file_get_contents(__DIR__.'/../public/docs/source/index.md');
        $this->assertContains('Лорем ипсум долор сит амет', $generatedMarkdown);
    }

    /**
     * @param string $command
     * @param array $parameters
     *
     * @return mixed
     */
    public function artisan($command, $parameters = [])
    {
        $this->app[Kernel::class]->call($command, $parameters);

        return $this->app[Kernel::class]->output();
    }
}
