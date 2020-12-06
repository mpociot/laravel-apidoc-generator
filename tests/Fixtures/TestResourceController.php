<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TestResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @response {
     *   "index_resource": true
     * }
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return [
            'index_resource' => true,
        ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @response {
     *   "create_resource": true
     * }
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return [
            'create_resource' => true,
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @response {
     *   "show_resource": true
     * }
     *
     * @param  int  $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return [
            'show_resource' => true,
        ];
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @response {
     *   "edit_resource": true
     * }
     *
     * @param  int  $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return [
            'edit_resource' => true,
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }
}
