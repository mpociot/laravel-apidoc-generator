<?php

namespace Mpociot\ApiDoc;

use Illuminate\Support\ServiceProvider;
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
     * Register the API doc commands
     */
    public function register()
    {
        $this->app['apidoc.generate'] = $this->app->share(function () {
            return new GenerateDocumentation();
        });

        $this->commands(
            'apidoc.generate'
        );
    }

}
