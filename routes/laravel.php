<?php

use Illuminate\Support\Facades\Route;

$prefix = config('apidoc.laravel.docs_url', '/doc');
$middleware = config('apidoc.laravel.middleware', []);

Route::namespace('\Mpociot\ApiDoc\Http')
    ->middleware($middleware)
    ->group(function () use ($prefix) {
        Route::get($prefix, 'Controller@html')->name('apidoc');
        Route::get("$prefix.json", 'Controller@json')->name('apidoc.json');
    });
