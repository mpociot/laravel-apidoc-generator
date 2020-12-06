<?php

namespace Mpociot\ApiDoc;

use Illuminate\Support\ServiceProvider;
use Mpociot\ApiDoc\Commands\GenerateDocumentation;
use Mpociot\ApiDoc\Commands\RebuildDocumentation;
use Mpociot\ApiDoc\Matching\RouteMatcher;
use Mpociot\ApiDoc\Matching\RouteMatcherInterface;

class ApiDocGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views/', 'apidoc');

        $this->publishes([
            __DIR__ . '/../resources/views' => $this->app->basePath('resources/views/vendor/apidoc'),
        ], 'apidoc-views');

        $this->publishes([
            __DIR__ . '/../config/apidoc.php' => $this->app->configPath('apidoc.php'),
        ], 'apidoc-config');

        $this->mergeConfigFrom(__DIR__ . '/../config/apidoc.php', 'apidoc');

        $this->bootRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateDocumentation::class,
                RebuildDocumentation::class,
            ]);
        }

        // Bind the route matcher implementation
        $this->app->bind(RouteMatcherInterface::class, config('apidoc.routeMatcher', RouteMatcher::class));
    }

    /**
     * Initializing routes in the application.
     */
    protected function bootRoutes()
    {
        if (
            config('apidoc.type', 'static') === 'laravel' &&
            config('apidoc.laravel.autoload', false)
        ) {
            $this->loadRoutesFrom(
                __DIR__ . '/../routes/laravel.php'
            );
        }
    }
}
