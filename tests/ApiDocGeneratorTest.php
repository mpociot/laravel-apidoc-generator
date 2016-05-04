<?php

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Route;
use Mpociot\ApiDoc\ApiDocGenerator;

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

        $this->assertEquals('Example title', $parsed['title']);
        $this->assertEquals("This will be the long description.\nIt can also be multiple lines long.", $parsed['description']);
    }

    public function testCanParseRouteMethods()
    {
        \Illuminate\Support\Facades\Route::get('/get', 'TestController@dummy');
        \Illuminate\Support\Facades\Route::post('/post', 'TestController@dummy');
        \Illuminate\Support\Facades\Route::put('/put', 'TestController@dummy');
        \Illuminate\Support\Facades\Route::delete('/delete', 'TestController@dummy');

        $route = new Route(['GET'], '/get', ['uses' => 'TestController@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertEquals(['GET','HEAD'], $parsed['methods']);

        $route = new Route(['POST'], '/post', ['uses' => 'TestController@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertEquals(['POST'], $parsed['methods']);

        $route = new Route(['PUT'], '/put', ['uses' => 'TestController@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertEquals(['PUT'], $parsed['methods']);

        $route = new Route(['DELETE'], '/delete', ['uses' => 'TestController@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertEquals(['DELETE'], $parsed['methods']);
    }

    public function testCanParseFormRequestRules()
    {
        \Illuminate\Support\Facades\Route::post('/post', 'TestController@parseFormRequestRules');
        $route = new Route(['POST'], '/post', ['uses' => 'TestController@parseFormRequestRules']);
        $parsed = $this->generator->processRoute($route);
        $parameters = $parsed['parameters'];
        $this->assertArrayHasKey('required_attribute', $parameters);

        $required_attribute = $parameters['required_attribute'];

        $this->assertTrue( $required_attribute['required'] );
        $this->assertEquals( 'string', $required_attribute['type'] );
        $this->assertCount( 0, $required_attribute['description'] );
    }

}

class TestController extends Controller
{

    public function dummy()
    {
        return '';
    }

    /**
     * Example title
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
            'required_attribute' => 'required'
        ];
    }
}