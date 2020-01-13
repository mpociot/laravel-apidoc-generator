<?php

namespace Mpociot\ApiDoc;

use Illuminate\Support\Facades\Route;

class ApiDoc
{
    /**
     * Binds the ApiDoc routes into the controller.
     *
     * @deprecated Use
     *
     * @param string $path
     */
    public static function routes($path = '/doc')
    {
        Route::prefix($path)
            ->namespace('\Mpociot\ApiDoc\Http')
            ->middleware(static::middleware())
            ->group(function () {
                Route::get('/', 'Controller@blade')->name('apidoc');
                Route::get('.json', 'Controller@json')->name('apidoc.json');
            });
    }

    /**
     * Get the middlewares for Laravel routes.
     *
     * @return array
     */
    protected static function middleware()
    {
        return config('apidoc.routes.laravel.middleware', []);
    }
}
