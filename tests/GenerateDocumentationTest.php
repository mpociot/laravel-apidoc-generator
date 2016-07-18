<?php

namespace Mpociot\ApiDoc\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Routing\Route;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Mpociot\ApiDoc\Generators\LaravelGenerator;
use Orchestra\Testbench\TestCase;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;
use Illuminate\Support\Facades\Route as RouteFacade;

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

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [ApiDocGeneratorServiceProvider::class];
    }

    public function testConsoleCommandNeedsAPrefixOrRoute()
    {
        $output = $this->artisan('api:generate');
        $this->assertEquals('You must provide either a route prefix or a route to generate the documentation.'.PHP_EOL, $output);
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
        $this->assertContains('Skipping route: api/closure - contains closure.', $output);
        $this->assertContains('Processed route: api/test', $output);
    }

    public function testGeneratedMarkdownFileIsCorrect()
    {
        RouteFacade::get('/api/test', TestController::class.'@parseMethodDescription');
        RouteFacade::get('/api/fetch', TestController::class.'@fetchRouteResponse');

        $output = $this->artisan('api:generate', [
            '--routePrefix' => 'api/*',
        ]);

        $generatedMarkdown = file_get_contents(__DIR__.'/../public/docs/source/index.md');
        $fixtureMarkdown = file_get_contents(__DIR__.'/Fixtures/index.md');
        $this->assertSame($generatedMarkdown, $fixtureMarkdown);
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
