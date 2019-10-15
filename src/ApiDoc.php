<?php

namespace Mpociot\ApiDoc;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class ApiDoc
{
    public static function routes($path = '/doc')
    {
        return Route::get("$path{format?}", function (?string $format = null) {
            if ($format == '.json') {
                return response(
                    Storage::disk('local')->get('apidoc/collection.json'),
                    200,
                    ['Content-type' => 'application/json']

                );
            }

            return view('apidoc.index');
        })->name('apidoc');
    }
}
