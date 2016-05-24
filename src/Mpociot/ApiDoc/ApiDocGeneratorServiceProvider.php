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
        $this->loadViewsFrom(__DIR__.'/../../resources/views/', 'apidoc');
    }

    /**
     * Register the API doc commands.
     *
     * @return void
     */
    public function register()
    {
        $this->app['apidoc.generate'] = $this->app->share(function () {
            return new GenerateDocumentation();
        });
        $this->app['apidoc.update'] = $this->app->share(function () {
            return new UpdateDocumentation();
        });

        $this->commands([
            'apidoc.generate',
            'apidoc.update',
        ]);
    }
}
