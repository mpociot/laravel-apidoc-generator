<?php

namespace Mpociot\ApiDoc\Http;

use Illuminate\Support\Facades\Storage;
use Mpociot\ApiDoc\Exceptions\FileNotFoundException;

class Controller
{
    public function html()
    {
        return view('apidoc.index');
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function json()
    {
        if (!Storage::disk(config('apidoc.storage'))->has('apidoc/collection.json')) {
            throw new FileNotFoundException(400, 'Please run php artisan apidoc:generate.');
        }

        return response()->json(
            json_decode(Storage::disk(config('apidoc.storage'))->get('apidoc/collection.json'))
        );
    }
}
