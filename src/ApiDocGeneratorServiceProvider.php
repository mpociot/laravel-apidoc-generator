<?php

namespace Mpociot\ApiDoc;

use Illuminate\Support\ServiceProvider;
use Mpociot\ApiDoc\Commands\UpdateDocumentation;
use Mpociot\ApiDoc\Commands\GenerateDocumentation;

class ApiDocGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'apidoc');

        $this->publishes([
            __DIR__.'/../resources/views' => app()->basePath().'/resources/views/vendor/apidoc',
        ], 'views');

        $this->publishes([
            __DIR__.'/../config/apidoc.php' => app()->basePath().'/config/apidoc.php',
        ], 'apidoc-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateDocumentation::class,
                UpdateDocumentation::class,
            ]);
        }
    }

    /**
     * Register the API doc commands.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(app()->basePath().'/config/apidoc.php', 'apidoc');
    }
}
