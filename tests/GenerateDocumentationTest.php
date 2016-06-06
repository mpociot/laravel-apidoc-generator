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
