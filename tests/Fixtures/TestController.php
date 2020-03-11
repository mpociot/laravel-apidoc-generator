<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mpociot\ApiDoc\Tests\Unit\GeneratorTestCase;

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
        return 'Group B, baby!';
    }

    /**
     * This is also in Group B. No route description. Route title before gropp.
     *
     * @group Group B
     */
    public function withGroupOverride2()
    {
        return '';
    }

    /**
     * @group Group B
     *
     * This is also in Group B. Route title after group.
     */
    public function withGroupOverride3()
    {
        return '';
    }

    /**
     * This is in Group C. Route title before group.
     *
     * @group Group C
     *
     * Group description after group.
     */
    public function withGroupOverride4()
    {
        return '';
    }

    /**
     * Endpoint with body parameters.
     *
     * @bodyParam user_id int required The id of the user. Example: 9
     * @bodyParam room_id string The id of the room.
     * @bodyParam forever boolean Whether to ban the user forever. Example: false
     * @bodyParam another_one number Just need something here.
     * @bodyParam yet_another_param object required Some object params.
     * @bodyParam yet_another_param.name string required Subkey in the object param.
     * @bodyParam even_more_param array Some array params.
     * @bodyParam even_more_param.* float Subkey in the array param.
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

    /**
     * Endpoint with body parameters as array.
     *
     * @bodyParam *.first_name string The first name of the user. Example: John
     * @bodyParam *.last_name string The last name of the user. Example: Doe
     * @bodyParam *.contacts.*.first_name string The first name of the contact. Example: John
     * @bodyParam *.contacts.*.last_name string The last name of the contact. Example: Doe
     * @bodyParam *.roles.* string The name of the role. Example: Admin
     */
    public function withBodyParametersAsArray()
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
     * @queryParam url_encoded  Used for testing that URL parameters will be URL-encoded where needed. Example: + []&=
     */
    public function withQueryParameters()
    {
        return '';
    }

    /**
     * @bodyParam included string required Exists in examples. Example: 'Here'
     * @bodyParam  excluded_body_param int Does not exist in examples. No-example
     * @queryParam excluded_query_param Does not exist in examples. No-example
     */
    public function withExcludedExamples()
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

    /**
     * @apiResource \Mpociot\ApiDoc\Tests\Fixtures\TestUserApiResource
     * @apiResourceModel \Mpociot\ApiDoc\Tests\Fixtures\TestUser
     */
    public function withEloquentApiResource()
    {
        return new TestUserApiResource(factory(TestUser::class)->make(['id' => 0]));
    }

    /**
     * @group Other😎
     *
     * @apiResourceCollection Mpociot\ApiDoc\Tests\Fixtures\TestUserApiResource
     * @apiResourceModel Mpociot\ApiDoc\Tests\Fixtures\TestUser
     */
    public function withEloquentApiResourceCollection()
    {
        return TestUserApiResource::collection(
            collect([factory(TestUser::class)->make(['id' => 0])])
        );
    }

    /**
     * @group Other😎
     *
     * @apiResourceCollection Mpociot\ApiDoc\Tests\Fixtures\TestUserApiResourceCollection
     * @apiResourceModel Mpociot\ApiDoc\Tests\Fixtures\TestUser
     */
    public function withEloquentApiResourceCollectionClass()
    {
        return new TestUserApiResourceCollection(
            collect([factory(TestUser::class)->make(['id' => 0])])
        );
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
            'weight' => $fruit->weight . ' kg',
            'delicious' => $fruit->delicious,
            'responseCall' => true,
        ];
    }

    public function echoesConfig()
    {
        return [
            'app.env' => config('app.env'),
        ];
    }

    /**
     * @group Other😎
     *
     * @urlParam param required Example: 4
     * @urlParam param2
     * @urlParam param4 No-example.
     *
     * @queryParam something
     */
    public function echoesUrlParameters($param, $param2, $param3 = null, $param4 = null)
    {
        return compact('param', 'param2', 'param3', 'param4');
    }

    /**
     * @urlparam $id Example: 3
     */
    public function shouldFetchRouteResponseWithEchoedSettings($id)
    {
        return [
            '{id}' => $id,
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
     *   "delicious": true,
     *   "responseTag": true
     * }
     */
    public function withResponseTag()
    {
        GeneratorTestCase::$globalValue = rand();

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
     *   "delicious": true,
     *   "multipleResponseTagsAndStatusCodes": true
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
     * @transformer 201 \Mpociot\ApiDoc\Tests\Fixtures\TestTransformer
     */
    public function transformerTagWithStatusCode()
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

    /**
     * @responseFile i-do-not-exist.json
     */
    public function withNonExistentResponseFile()
    {
        return '';
    }
}
