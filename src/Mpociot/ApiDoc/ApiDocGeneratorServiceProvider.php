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
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', 'apidoc');

        $this->publishes([
            __DIR__.'/../../resources/lang' => $this->resource_path('lang/vendor/apidoc'),
            __DIR__.'/../../resources/views' => $this->resource_path('views/vendor/apidoc'),
        ]);
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

    /**
     * Return a fully qualified path to a given file.
     *
     * @param string $path
     *
     * @return string
     */
    public function resource_path($path = '')
    {
        return app()->basePath().'/resources'.($path ? '/'.$path : $path);
    }
}
