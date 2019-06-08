<?php

use Illuminate\Support\Facades\Route;

Route::get('/apidoc/{id}', function ($id) {
    return view("apidoc.$id", ['id' => $id]);
})->name('apidoc');

Route::get('/apidoc/{id}/collection.json', function ($id) {
    return response()->download(resource_path("views/apidoc/$id.json"));
})->name('apidoc.collection');
