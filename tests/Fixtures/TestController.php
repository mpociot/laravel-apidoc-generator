<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
     * @bodyParam title string required The title of the post.
     * @bodyParam body string required The title of the post.
     * @bodyParam type string The type of post to create. Defaults to 'textophonious'.
    @bodyParam author_id int the ID of the author
     * @bodyParam thumbnail image This is required if the post type is 'imagelicious
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
        $fixture = new \stdClass();
        $fixture->id = 1;
        $fixture->name = 'banana';
        $fixture->color = 'red';
        $fixture->weight = 300;
        $fixture->delicious = 1;

        return [
            'id' => (int) $fixture->id,
            'name' => ucfirst($fixture->name),
            'color' => ucfirst($fixture->color),
            'weight' => $fixture->weight.' grams',
            'delicious' => (bool) $fixture->delicious,
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
