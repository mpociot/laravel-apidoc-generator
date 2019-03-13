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
     * @bodyParam user_id int required The id of the user. Example: 9
     * @bodyParam room_id string The id of the room.
     * @bodyParam forever boolean Whether to ban the user forever. Example: false
     * @bodyParam another_one number Just need something here.
     * @bodyParam yet_another_param object required
     * @bodyParam even_more_param array
     * @bodyParam book.name string
     * @bodyParam book.author_id integer
     * @bodyParam book[pages_count] integer
     * @bodyParam ids.* integer
     * @bodyParam users.*.first_name string The first name of the user. Example: John
     * @bodyParam users.*.last_name string The last name of the user. Example: Doe
     */
    public function withBodyParameters()
    {
        return '';
    }

    public function withFormRequestParameter(TestRequest $request)
    {
        return '';
    }

    public function withMultipleFormRequestParameters(string $test, TestRequest $request)
    {
        return '';
    }

    /**
     * @bodyParam direct_one string Is found directly on the method.
     */
    public function withNonCommentedFormRequestParameter(TestNonCommentedRequest $request)
    {
        return '';
    }

    /**
     * @queryParam location_id required The id of the location.
     * @queryParam user_id required The id of the user. Example: me
     * @queryParam page required The page number. Example: 4
     * @queryParam filters  The filters.
     */
    public function withQueryParameters()
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
     * @response 422 {
     *   "message": "Validation error"
     * }
     */
    public function withResponseTagAndStatusCode()
    {
        return '';
    }

    /**
     * @response {
     *   "id": 4,
     *   "name": "banana",
     *   "color": "red",
     *   "weight": "1 kg",
     *   "delicious": true
     * }
     * @response 401 {
     *   "message": "Unauthorized"
     * }
     */
    public function withMultipleResponseTagsAndStatusCode()
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

    /**
     * @responseFile response_test.json
     */
    public function responseFileTag()
    {
        return '';
    }

    /**
     * @responseFile response_test.json
     * @responseFile 401 response_error_test.json
     */
    public function withResponseFileTagAndStatusCode()
    {
        return '';
    }

    /**
     * @responseFile response_test.json {"message" : "Serendipity"}
     */
    public function responseFileTagAndCustomJson()
    {
        return '';
    }
}
