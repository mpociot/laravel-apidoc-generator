<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * @group Group A
 */
class TestController extends Controller
{
    public function dummy()
    {
        return '';
    }

    /**
     * Example title.
     * This will be the long description.
     * It can also be multiple lines long.
     */
    public function withEndpointDescription()
    {
        return '';
    }

    /**
     * @group Group B
     */
    public function withGroupOverride()
    {
        return '';
    }

    /**
     * @bodyParam user_id int required The id of the user.
     * @bodyParam room_id string The id of the room.
     * @bodyParam forever boolean Whether to ban the user forever.
     * @bodyParam another_one number Just need something here.
     * @bodyParam yet_another_param object required
     * @bodyParam even_more_param array
     */
    public function withBodyParameters()
    {
        return '';
    }

    /**
     * @authenticated
     */
    public function withAuthenticatedTag()
    {
        return '';
    }

    public function checkCustomHeaders(Request $request)
    {
        return $request->headers->all();
    }

    public function shouldFetchRouteResponse()
    {
        $fruit = new \stdClass();
        $fruit->id = 4;
        $fruit->name = ' banana  ';
        $fruit->color = 'RED';
        $fruit->weight = 1;
        $fruit->delicious = true;

        return [
            'id' => (int) $fruit->id,
            'name' => trim($fruit->name),
            'color' => strtolower($fruit->color),
            'weight' => $fruit->weight.' kg',
            'delicious' => $fruit->delicious,
        ];
    }

    public function shouldFetchRouteResponseWithEchoedSettings($id)
    {
        return [
            '{id}' => $id,
            'APP_ENV' => getenv('APP_ENV'),
            'header' => request()->header('header'),
            'queryParam' => request()->query('queryParam'),
            'bodyParam' => request()->get('bodyParam'),
        ];
    }

    /**
     * @response {
     *   "result": "Лорем ипсум долор сит амет"
     * }
     */
    public function withUtf8ResponseTag()
    {
        return ['result' => 'Лорем ипсум долор сит амет'];
    }

    /**
     * @hideFromAPIDocumentation
     */
    public function skip()
    {
    }

    /**
     * @response {
     *   "id": 4,
     *   "name": "banana",
     *   "color": "red",
     *   "weight": "1 kg",
     *   "delicious": true
     * }
     */
    public function withResponseTag()
    {
        return '';
    }

    /**
     * @transformer \Mpociot\ApiDoc\Tests\Fixtures\TestTransformer
     */
    public function transformerTag()
    {
        return '';
    }

    /**
     * @transformer \Mpociot\ApiDoc\Tests\Fixtures\TestTransformer
     * @transformermodel \Mpociot\ApiDoc\Tests\Fixtures\TestModel
     */
    public function transformerTagWithModel()
    {
        return '';
    }

    /**
     * @transformercollection \Mpociot\ApiDoc\Tests\Fixtures\TestTransformer
     */
    public function transformerCollectionTag()
    {
        return '';
    }

    /**
     * @transformercollection \Mpociot\ApiDoc\Tests\Fixtures\TestTransformer
     * @transformermodel \Mpociot\ApiDoc\Tests\Fixtures\TestModel
     */
    public function transformerCollectionTagWithModel()
    {
        return '';
    }
}
