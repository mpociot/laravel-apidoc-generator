<?php

namespace Mpociot\ApiDoc\Tests;

use Illuminate\Routing\Route;
use Orchestra\Testbench\TestCase;
use Mpociot\ApiDoc\Tests\Fixtures\TestRequest;
use Mpociot\ApiDoc\Generators\LaravelGenerator;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Illuminate\Support\Facades\Route as RouteFacade;

class ApiDocGeneratorTest extends TestCase
{
    /**
     * @var \Mpociot\ApiDoc\AbstractGenerator
     */
    protected $generator;

    protected function getPackageProviders($app)
    {
        return [
            ApiDocGeneratorServiceProvider::class,
        ];
    }

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();

        $this->generator = new LaravelGenerator();
    }

    public function testCanParseMethodDescription()
    {
        RouteFacade::get('/api/test', TestController::class.'@parseMethodDescription');
        $route = new Route(['GET'], '/api/test', ['uses' => TestController::class.'@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);

        $this->assertSame('Example title.', $parsed['title']);
        $this->assertSame("This will be the long description.\nIt can also be multiple lines long.", $parsed['description']);
    }

    public function testCanParseRouteMethods()
    {
        RouteFacade::get('/get', TestController::class.'@dummy');
        RouteFacade::post('/post', TestController::class.'@dummy');
        RouteFacade::put('/put', TestController::class.'@dummy');
        RouteFacade::delete('/delete', TestController::class.'@dummy');

        $route = new Route(['GET'], '/get', ['uses' => TestController::class.'@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['GET', 'HEAD'], $parsed['methods']);

        $route = new Route(['POST'], '/post', ['uses' => TestController::class.'@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['POST'], $parsed['methods']);

        $route = new Route(['PUT'], '/put', ['uses' => TestController::class.'@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['PUT'], $parsed['methods']);

        $route = new Route(['DELETE'], '/delete', ['uses' => TestController::class.'@parseMethodDescription']);
        $parsed = $this->generator->processRoute($route);
        $this->assertSame(['DELETE'], $parsed['methods']);
    }

    public function testCanParseDependencyInjectionInControllerMethods()
    {
        RouteFacade::post('/post', TestController::class.'@dependencyInjection');
        $route = new Route(['POST'], '/post', ['uses' => TestController::class.'@dependencyInjection']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
    }

    public function testCanParseFormRequestRules()
    {
        RouteFacade::post('/post', TestController::class.'@parseFormRequestRules');
        $route = new Route(['POST'], '/post', ['uses' => TestController::class.'@parseFormRequestRules']);
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
                case 'string_between':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
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
                case 'single_exists':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Valid user single_exists', $attribute['description'][0]);
                    break;
                case 'file':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('file', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Must be a file upload', $attribute['description'][0]);
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
                case 'multiple_required_if':
                    $this->assertFalse($attribute['required']);
                    $this->assertSame('string', $attribute['type']);
                    $this->assertCount(1, $attribute['description']);
                    $this->assertSame('Required if `foo` is `bar` or `baz` is `qux`', $attribute['description'][0]);
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

    public function testCanParseResponseTag()
    {
        RouteFacade::post('/responseTag', TestController::class.'@responseTag');
        $route = new Route(['GET'], '/responseTag', ['uses' => TestController::class.'@responseTag']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame($parsed['response'], '"{\n data: [],\n}"');
    }

    public function testCanParseTransformerTag()
    {
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            $this->markTestSkipped('The transformer tag without model need PHP 7');
        }
        RouteFacade::post('/transformerTag', TestController::class.'@transformerTag');
        $route = new Route(['GET'], '/transformerTag', ['uses' => TestController::class.'@transformerTag']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}'
        );
    }

    public function testCanParseTransformerTagWithModel()
    {
        RouteFacade::post('/transformerTagWithModel', TestController::class.'@transformerTagWithModel');
        $route = new Route(['GET'], '/transformerTagWithModel', ['uses' => TestController::class.'@transformerTagWithModel']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}'
        );
    }

    public function testCanParseTransformerCollectionTag()
    {
        if (version_compare(PHP_VERSION, '7.0.0', '<')) {
            $this->markTestSkipped('The transformer tag without model need PHP 7');
        }
        RouteFacade::post('/transformerCollectionTag', TestController::class.'@transformerCollectionTag');
        $route = new Route(['GET'], '/transformerCollectionTag', ['uses' => TestController::class.'@transformerCollectionTag']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},'.
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }

    public function testCanParseTransformerCollectionTagWithModel()
    {
        RouteFacade::post('/transformerCollectionTagWithModel', TestController::class.'@transformerCollectionTagWithModel');
        $route = new Route(['GET'], '/transformerCollectionTagWithModel', ['uses' => TestController::class.'@transformerCollectionTagWithModel']);
        $parsed = $this->generator->processRoute($route);
        $this->assertTrue(is_array($parsed));
        $this->assertArrayHasKey('showresponse', $parsed);
        $this->assertTrue($parsed['showresponse']);
        $this->assertSame(
            $parsed['response'],
            '{"data":[{"id":1,"description":"Welcome on this test versions","name":"TestName"},'.
            '{"id":1,"description":"Welcome on this test versions","name":"TestName"}]}'
        );
    }
}
