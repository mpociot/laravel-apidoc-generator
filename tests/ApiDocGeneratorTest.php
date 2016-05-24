<?php

use Illuminate\Routing\Route;
use Illuminate\Routing\Controller;
use Mpociot\ApiDoc\ApiDocGenerator;
use Illuminate\Foundation\Http\FormRequest;

class ApiDocGeneratorTest extends Orchestra\Testbench\TestCase
{
    /**
     * @var \Mpociot\ApiDoc\ApiDocGenerator
     */
    protected $generator;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->generator = new ApiDocGenerator();
    }

    public function testCanParseMethodDescription()
    {
        \Illuminate\Support\Facades\Route::get('/api/test', 'TestController@parseMethodDescription');
        $route = new Route(['GET'], '/api/test', ['uses' => 'TestController@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);

        $this->assertSame('Example title.', $parsed['title']);
        $this->assertSame("This will be the long description.\nIt can also be multiple lines long.", $parsed['description']);
    }

    public function testCanParseRouteMethods()
    {
        \Illuminate\Support\Facades\Route::get('/get', 'TestController@dummy');
        \Illuminate\Support\Facades\Route::post('/post', 'TestController@dummy');
        \Illuminate\Support\Facades\Route::put('/put', 'TestController@dummy');
        \Illuminate\Support\Facades\Route::delete('/delete', 'TestController@dummy');

        $route = new Route(['GET'], '/get', ['uses' => 'TestController@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['GET', 'HEAD'], $parsed['methods']);

        $route = new Route(['POST'], '/post', ['uses' => 'TestController@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['POST'], $parsed['methods']);

        $route = new Route(['PUT'], '/put', ['uses' => 'TestController@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['PUT'], $parsed['methods']);

        $route = new Route(['DELETE'], '/delete', ['uses' => 'TestController@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['DELETE'], $parsed['methods']);
    }

    public function testCanParseFormRequestRules()
    {
        \Illuminate\Support\Facades\Route::post('/post', 'TestController@parseFormRequestRules');
        $route = new Route(['POST'], '/post', ['uses' => 'TestController@parseFormRequestRules']);
        $parsed = $this->generator->processRoute($route);
        $parameters = $parsed['parameters'];

        $testRequest = new TestRequest();
        $rules = $testRequest->rules();

        foreach ($rules as $name => $rule) {
            $attribute = $parameters[$name];

            switch ($name) {

                case 'required':
                    $this->assertTrue($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(0, $attribute['description']);
                    break;
                case 'accepted':
                    $this->assertTrue($attribute['required']);
                    $this->assertSame('boolean', $attribute['type']);
                    $this->assertCount(0, $attribute['description']);
                    break;
                case 'active_url':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('url', $attribute['type']);
                    $this->assertCount(0, $attribute['description']);
                    break;
                case 'alpha':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Only alphabetic characters allowed', $attribute['description'][0]);
                    break;
                case 'alpha_dash':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Allowed: alpha-numeric characters, as well as dashes and underscores.', $attribute['description'][0]);
                    break;
                case 'alpha_num':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Only alpha-numeric characters allowed', $attribute['description'][0]);
                    break;
                case 'array':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('array', $attribute['type']);
                    $this->assertCount(0, $attribute['description']);
                    break;
                case 'between':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('numeric', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Between: `5` and `200`', $attribute['description'][0]);
                    break;
                case 'before':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('date', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Must be a date preceding: `Saturday, 23-Apr-16 14:31:00 UTC`', $attribute['description'][0]);
                    break;
                case 'boolean':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('boolean', $attribute['type']);
                    $this->assertCount(0, $attribute['description']);
                    break;
                case 'date':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('date', $attribute['type']);
                    $this->assertCount(0, $attribute['description']);
                    break;
                case 'date_format':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('date', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Date format: `j.n.Y H:iP`', $attribute['description'][0]);
                    break;
                case 'different':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Must have a different value than parameter: `alpha_num`', $attribute['description'][0]);
                    break;
                case 'digits':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('numeric', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Must have an exact length of `2`', $attribute['description'][0]);
                    break;
                case 'digits_between':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('numeric', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Must have a length between `2` and `10`', $attribute['description'][0]);
                    break;
                case 'email':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('email', $attribute['type']);
                    $this->assertCount(0, $attribute['description']);
                    break;
                case 'exists':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Valid user firstname', $attribute['description'][0]);
                    break;
                case 'image':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('image', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Must be an image (jpeg, png, bmp, gif, or svg)', $attribute['description'][0]);
                    break;
                case 'in':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('`jpeg`, `png`, `bmp`, `gif` or `svg`', $attribute['description'][0]);
                    break;
                case 'integer':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('integer', $attribute['type']);
                    $this->assertCount(0, $attribute['description']);
                    break;
                case 'ip':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('ip', $attribute['type']);
                    $this->assertCount(0, $attribute['description']);
                    break;
                case 'json':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Must be a valid JSON string.', $attribute['description'][0]);
                    break;
                case 'max':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Maximum: `10`', $attribute['description'][0]);
                    break;
                case 'min':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Minimum: `20`', $attribute['description'][0]);
                    break;
                case 'mimes':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Allowed mime types: `jpeg`, `bmp` or `png`', $attribute['description'][0]);
                    break;
                case 'not_in':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Not in: `foo` or `bar`', $attribute['description'][0]);
                    break;
                case 'numeric':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('numeric', $attribute['type']);
                    $this->assertCount(0, $attribute['description']);
                    break;
                case 'regex':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Must match this regular expression: `(.*)`', $attribute['description'][0]);
                    break;
                case 'required_if':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Required if `foo` is `bar`', $attribute['description'][0]);
                    break;
                case 'required_unless':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Required unless `foo` is `bar`', $attribute['description'][0]);
                    break;
                case 'required_with':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Required if the parameters `foo`, `bar` or `baz` are present.', $attribute['description'][0]);
                    break;
                case 'required_with_all':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Required if the parameters `foo`, `bar` and `baz` are present.', $attribute['description'][0]);
                    break;
                case 'required_without':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Required if the parameters `foo`, `bar` or `baz` are not present.', $attribute['description'][0]);
                    break;
                case 'required_without_all':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Required if the parameters `foo`, `bar` and `baz` are not present.', $attribute['description'][0]);
                    break;
                case 'same':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Must be the same as `foo`', $attribute['description'][0]);
                    break;
                case 'size':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Must have the size of `51`', $attribute['description'][0]);
                    break;
                case 'timezone':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Must be a valid timezone identifier', $attribute['description'][0]);
                    break;
                case 'url':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('url', $attribute['type']);
                    $this->assertCount(0, $attribute['description']);
                    break;

            }
        }
    }
}

class TestController extends Controller
{
    public function dummy()
    {
        return '';
    }

    /**
     * Example title.
     *
     * This will be the long description.
     * It can also be multiple lines long.
     */
    public function parseMethodDescription()
    {
        return '';
    }

    public function parseFormRequestRules(TestRequest $request)
    {
        return '';
    }
}

class TestRequest extends FormRequest
{
    public function rules()
    {
        return [
            'required' => 'required',
            'accepted' => 'accepted',
            'after' => 'after:2016-04-23 14:31:00',
            'active_url' => 'active_url',
            'alpha' => 'alpha',
            'alpha_dash' => 'alpha_dash',
            'alpha_num' => 'alpha_num',
            'array' => 'array',
            'before' => 'before:2016-04-23 14:31:00',
            'between' => 'between:5,200',
            'boolean' => 'boolean',
            'date' => 'date',
            'date_format' => 'date_format:j.n.Y H:iP',
            'different' => 'different:alpha_num',
            'digits' => 'digits:2',
            'digits_between' => 'digits_between:2,10',
            'exists' => 'exists:users,firstname',
            'in' => 'in:jpeg,png,bmp,gif,svg',
            'integer' => 'integer',
            'ip' => 'ip',
            'json' => 'json',
            'min' => 'min:20',
            'max' => 'max:10',
            'mimes' => 'mimes:jpeg,bmp,png',
            'not_in' => 'not_in:foo,bar',
            'numeric' => 'numeric',
            'regex' => 'regex:(.*)',
            'required_if' => 'required_if:foo,bar',
            'required_unless' => 'required_unless:foo,bar',
            'required_with' => 'required_with:foo,bar,baz',
            'required_with_all' => 'required_with_all:foo,bar,baz',
            'required_without' => 'required_without:foo,bar,baz',
            'required_without_all' => 'required_without_all:foo,bar,baz',
            'same' => 'same:foo',
            'size' => 'size:51',
            'timezone' => 'timezone',
            'url' => 'url',
        ];
    }
}
