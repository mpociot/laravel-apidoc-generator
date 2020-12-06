<?php

namespace Mpociot\ApiDoc\Tests\Unit;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Routing\Route;
use Mpociot\ApiDoc\ApiDocGeneratorServiceProvider;
use Mpociot\ApiDoc\Extracting\Generator;
use Mpociot\ApiDoc\Extracting\Strategies\Strategy;
use Mpociot\ApiDoc\Tests\Fixtures\TestController;
use Mpociot\ApiDoc\Tools\DocumentationConfig;
use ReflectionClass;
use ReflectionMethod;

class GeneratorPluginSystemTestCase extends LaravelGeneratorTest
{
    use ArraySubsetAsserts;

    /**
     * @var \Mpociot\ApiDoc\Extracting\Generator
     */
    protected $generator;

    protected function getPackageProviders($app)
    {
        return [
            ApiDocGeneratorServiceProvider::class,
        ];
    }

    /** @test */
    public function only_specified_strategies_are_loaded()
    {
        $config = [
            'strategies' => [
                'metadata' => [EmptyStrategy1::class],
                'bodyParameters' => [
                    EmptyStrategy1::class,
                    EmptyStrategy2::class,
                ],
                'responses' => [EmptyStrategy1::class],
            ],
        ];
        $route = $this->createRoute('GET', '/api/test', 'dummy', true, TestController::class);
        $generator = new Generator(new DocumentationConfig($config));
        $generator->processRoute($route);

        // Probably not the best way to do this, but 🤷‍♂️
        $this->assertTrue(EmptyStrategy1::$called['metadata']);

        $this->assertTrue(EmptyStrategy1::$called['bodyParameters']);
        $this->assertTrue(EmptyStrategy2::$called['bodyParameters']);

        $this->assertArrayNotHasKey('queryParameters', EmptyStrategy1::$called);

        $this->assertTrue(EmptyStrategy1::$called['responses']);
    }

    /** @test */
    public function combines_responses_from_different_strategies()
    {
        $config = [
            'strategies' => [
                'responses' => [DummyResponseStrategy200::class, DummyResponseStrategy400::class],
            ],
        ];
        $route = $this->createRoute('GET', '/api/test', 'dummy', true, TestController::class);
        $generator = new Generator(new DocumentationConfig($config));
        $parsed = $generator->processRoute($route);

        $this->assertTrue($parsed['showresponse']);
        $this->assertCount(2, $parsed['responses']);
        $first = array_shift($parsed['responses']);
        $this->assertTrue(is_array($first));
        $this->assertEquals(200, $first['status']);
        $this->assertEquals('dummy', $first['content']);
        $second = array_shift($parsed['responses']);
        $this->assertTrue(is_array($second));
        $this->assertEquals(400, $second['status']);
        $this->assertEquals('dummy2', $second['content']);
    }

    // This is a generalized test, as opposed to the one above for responses only

    /** @test */
    public function combines_results_from_different_strategies_in_same_stage()
    {
        $config = [
            'strategies' => [
                'metadata' => [PartialDummyMetadataStrategy1::class, PartialDummyMetadataStrategy2::class],
            ],
        ];
        $route = $this->createRoute('GET', '/api/test', 'dummy', true, TestController::class);
        $generator = new Generator(new DocumentationConfig($config));
        $parsed = $generator->processRoute($route);

        $expectedMetadata = [
            'groupName' => 'dummy',
            'groupDescription' => 'dummy',
            'title' => 'dummy',
            'description' => 'dummy',
            'authenticated' => false,
        ];
        $this->assertArraySubset($expectedMetadata, $parsed['metadata']);
    }

    /** @test */
    public function missing_metadata_is_filled_in()
    {
        $config = [
            'strategies' => [
                'metadata' => [PartialDummyMetadataStrategy2::class],
            ],
        ];
        $route = $this->createRoute('GET', '/api/test', 'dummy', true, TestController::class);
        $generator = new Generator(new DocumentationConfig($config));
        $parsed = $generator->processRoute($route);

        $expectedMetadata = [
            'groupName' => '',
            'groupDescription' => 'dummy',
            'title' => '',
            'description' => 'dummy',
            'authenticated' => false,
        ];
        $this->assertArraySubset($expectedMetadata, $parsed['metadata']);
    }

    /** @test */
    public function overwrites_metadat_from_previous_strategies_in_same_stage()
    {
        $config = [
            'strategies' => [
                'metadata' => [NotDummyMetadataStrategy::class, PartialDummyMetadataStrategy1::class],
            ],
        ];
        $route = $this->createRoute('GET', '/api/test', 'dummy', true, TestController::class);
        $generator = new Generator(new DocumentationConfig($config));
        $parsed = $generator->processRoute($route);

        $expectedMetadata = [
            'groupName' => 'dummy',
            'groupDescription' => 'notdummy',
            'title' => 'dummy',
            'description' => 'dummy',
            'authenticated' => false,
        ];
        $this->assertArraySubset($expectedMetadata, $parsed['metadata']);
    }

    public function dataResources()
    {
        return [
            [
                null,
                '{"data":{"id":1,"description":"Welcome on this test versions","name":"TestName"}}',
            ],
            [
                'League\Fractal\Serializer\JsonApiSerializer',
                '{"data":{"type":null,"id":"1","attributes":{"description":"Welcome on this test versions","name":"TestName"}}}',
            ],
        ];
    }
}

class EmptyStrategy1 extends Strategy
{
    public static $called = [];

    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        static::$called[$this->stage] = true;
    }
}

class EmptyStrategy2 extends Strategy
{
    public static $called = [];

    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        static::$called[$this->stage] = true;
    }
}

class NotDummyMetadataStrategy extends Strategy
{
    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        return [
            'groupName' => 'notdummy',
            'groupDescription' => 'notdummy',
            'title' => 'notdummy',
            'description' => 'notdummy',
            'authenticated' => true,
        ];
    }
}

class PartialDummyMetadataStrategy1 extends Strategy
{
    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        return [
            'groupName' => 'dummy',
            'title' => 'dummy',
            'description' => 'dummy',
            'authenticated' => false,
        ];
    }
}

class PartialDummyMetadataStrategy2 extends Strategy
{
    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        return [
            'description' => 'dummy',
            'groupDescription' => 'dummy',
        ];
    }
}

class DummyResponseStrategy200 extends Strategy
{
    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        return [['status' => 200, 'content' => 'dummy']];
    }
}

class DummyResponseStrategy400 extends Strategy
{
    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        return [['status' => 400, 'content' => 'dummy2']];
    }
}
