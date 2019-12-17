<?php

use Illuminate\Support\Facades\Route;

$prefix = config('apidoc.url_prefix', '/doc');
$middleware = config('apidoc.middleware', []);

Route::prefix($prefix)
    ->namespace('\Mpociot\ApiDoc\Http')
    ->middleware($middleware)
    ->group(function () {
        Route::get('/', 'Controller@blade')->name('apidoc');
        Route::get('.json', 'Controller@json')->name('apidoc.json');
    });
