<?php

namespace Mpociot\ApiDoc\Http;

use Illuminate\Support\Facades\Storage;

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
        return response()->json(
            json_decode(Storage::disk('local')->get('apidoc/collection.json'))
        );
    }
}
