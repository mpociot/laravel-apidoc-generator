<?php

use Illuminate\Support\Facades\Route;

$prefix = config('apidoc.laravel.docs_url', '/doc');
$middleware = config('apidoc.laravel.middleware', []);

Route::prefix($prefix)
    ->namespace('\Mpociot\ApiDoc\Http')
    ->middleware($middleware)
    ->group(function () {
        Route::get('/', 'Controller@html')->name('apidoc');
        Route::get('.json', 'Controller@json')->name('apidoc.json');
    });
