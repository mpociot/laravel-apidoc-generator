<?php

namespace Mpociot\ApiDoc\Tests\Unit;

use Illuminate\Support\Collection;
use Mpociot\ApiDoc\Writing\PostmanCollectionWriter;
use Orchestra\Testbench\TestCase;

class PostmanCollectionWriterTest extends TestCase
{
    public function testNameIsPresentInCollection()
    {
        \Config::set('apidoc.postman', [
            'name' => 'Test collection',
        ]);

        $writer = new PostmanCollectionWriter(new Collection(), '');
        $collection = $writer->getCollection();

        $this->assertSame('Test collection', json_decode($collection)->info->name);
    }

    public function testFallbackCollectionNameIsUsed()
    {
        \Config::set('app.name', 'Fake App');

        $writer = new PostmanCollectionWriter(new Collection(), '');
        $collection = $writer->getCollection();

        $this->assertSame('Fake App API', json_decode($collection)->info->name);
    }

    public function testDescriptionIsPresentInCollection()
    {
        \Config::set('apidoc.postman', [
            'description' => 'A fake description',
        ]);

        $writer = new PostmanCollectionWriter(new Collection(), '');
        $collection = $writer->getCollection();

        $this->assertSame('A fake description', json_decode($collection)->info->description);
    }

    public function testAuthIsNotIncludedWhenNull()
    {
        $writer = new PostmanCollectionWriter(new Collection(), '');
        $collection = $writer->getCollection();

        $this->assertArrayNotHasKey('auth', json_decode($collection, true));
    }

    public function testAuthIsIncludedVerbatim()
    {
        $auth = [
            'type' => 'test',
            'test' => ['a' => 1],
        ];
        \Config::set('apidoc.postman', [
            'auth' => $auth,
        ]);

        $writer = new PostmanCollectionWriter(new Collection(), '');
        $collection = $writer->getCollection();

        $this->assertSame($auth, json_decode($collection, true)['auth']);
    }

    public function testEndpointIsParsed()
    {
        $route = $this->createMockRouteData('some/path');

        // Ensure method is set correctly for assertion later
        $route['methods'] = ['GET'];

        $collection = $this->createMockRouteGroup([$route], 'Group');

        $writer = new PostmanCollectionWriter($collection, 'fake.localhost');
        $collection = json_decode($writer->getCollection(), true);

        $this->assertSame('Group', data_get($collection, 'item.0.name'), 'Group name exists');

        $item = data_get($collection, 'item.0.item.0');
        $this->assertSame('some/path', $item['name'], 'Name defaults to path');
        $this->assertSame('http', data_get($item, 'request.url.protocol'), 'Protocol defaults to http');
        $this->assertSame('fake.localhost', data_get($item, 'request.url.host'), 'Host uses what\'s given');
        $this->assertSame('some/path', data_get($item, 'request.url.path'), 'Path is set correctly');
        $this->assertEmpty(data_get($item, 'request.url.query'), 'Query parameters are empty');
        $this->assertSame('GET', data_get($item, 'request.method'), 'Method is correctly resolved');
        $this->assertContains([
            'key' => 'Accept',
            'value' => 'application/json',
        ], data_get($item, 'request.header'), 'JSON Accept header is added');
    }

    public function testHttpsProtocolIsDetected()
    {
        $collection = $this->createMockRouteGroup([$this->createMockRouteData('fake')]);
        $writer = new PostmanCollectionWriter($collection, 'https://fake.localhost');
        $collection = json_decode($writer->getCollection(), true);

        $this->assertSame('https', data_get($collection, 'item.0.item.0.request.url.protocol'));
    }

    public function testHeadersArePulledFromRoute()
    {
        $route = $this->createMockRouteData('some/path');

        $route['headers'] = ['X-Fake' => 'Test'];

        $collection = $this->createMockRouteGroup([$route], 'Group');
        $writer = new PostmanCollectionWriter($collection, 'fake.localhost');
        $collection = json_decode($writer->getCollection(), true);

        $this->assertContains([
            'key' => 'X-Fake',
            'value' => 'Test',
        ], data_get($collection, 'item.0.item.0.request.header'));
    }

    public function testUrlParametersAreConverted()
    {
        $collection = $this->createMockRouteGroup([$this->createMockRouteData('fake/{param}')]);
        $writer = new PostmanCollectionWriter($collection, 'fake.localhost');
        $collection = json_decode($writer->getCollection(), true);

        $item = data_get($collection, 'item.0.item.0');
        $this->assertSame('fake/{param}', $item['name'], 'Name defaults to path');
        $this->assertSame('fake/:param', data_get($item, 'request.url.path'), 'Path is converted');
    }

    public function testUrlParamsResolveTheirDocumentation()
    {
        $fakeRoute = $this->createMockRouteData('fake/{param}');

        $fakeRoute['urlParameters'] = ['param' => [
            'description' => 'A test description for the test param',
            'required' => true,
            'value' => 'foobar',
        ]];

        $collection = $this->createMockRouteGroup([$fakeRoute]);
        $writer = new PostmanCollectionWriter($collection, 'fake.localhost');
        $collection = json_decode($writer->getCollection(), true);

        $variableData = data_get($collection, 'item.0.item.0.request.url.variable');

        $this->assertCount(1, $variableData);
        $this->assertSame([
            'id' => 'param',
            'key' => 'param',
            'value' => 'foobar',
            'description' => 'A test description for the test param',
        ], $variableData[0]);
    }

    public function testQueryParametersAreDocumented()
    {
        $fakeRoute = $this->createMockRouteData('fake/path');

        $fakeRoute['queryParameters'] = ['limit' => [
            'description' => 'A fake limit for my fake endpoint',
            'required' => false,
            'value' => 5,
        ]];

        $collection = $this->createMockRouteGroup([$fakeRoute]);
        $writer = new PostmanCollectionWriter($collection, 'fake.localhost');
        $collection = json_decode($writer->getCollection(), true);

        $variableData = data_get($collection, 'item.0.item.0.request.url.query');

        $this->assertCount(1, $variableData);
        $this->assertSame([
            'key' => 'limit',
            'value' => '5',
            'description' => 'A fake limit for my fake endpoint',
            'disabled' => false,
        ], $variableData[0]);
    }

    public function testUrlParametersAreNotIncludedIfMissingFromPath()
    {
        $fakeRoute = $this->createMockRouteData('fake/path');

        $fakeRoute['urlParameters'] = ['limit' => [
            'description' => 'A fake limit for my fake endpoint',
            'required' => false,
            'value' => 5,
        ]];

        $collection = $this->createMockRouteGroup([$fakeRoute]);
        $writer = new PostmanCollectionWriter($collection, 'fake.localhost');
        $collection = json_decode($writer->getCollection(), true);

        $variableData = data_get($collection, 'item.0.item.0.request.url.query');

        $this->assertCount(0, $variableData);
    }

    public function testQueryParametersAreDisabledWithNoValueWhenNotRequired()
    {
        $fakeRoute = $this->createMockRouteData('fake/path');
        $fakeRoute['queryParameters'] = [
            'required' => [
                'description' => 'A required param with a null value',
                'required' => true,
                'value' => null,
            ],
            'not_required' => [
                'description' => 'A not required param with a null value',
                'required' => false,
                'value' => null,
            ],
        ];

        $collection = $this->createMockRouteGroup([$fakeRoute]);
        $writer = new PostmanCollectionWriter($collection, 'fake.localhost');
        $collection = json_decode($writer->getCollection(), true);

        $variableData = data_get($collection, 'item.0.item.0.request.url.query');

        $this->assertCount(2, $variableData);
        $this->assertContains([
            'key' => 'required',
            'value' => null,
            'description' => 'A required param with a null value',
            'disabled' => false,
        ], $variableData);
        $this->assertContains([
            'key' => 'not_required',
            'value' => null,
            'description' => 'A not required param with a null value',
            'disabled' => true,
        ], $variableData);
    }

    /**
     * @dataProvider provideAuthConfigHeaderData
     */
    public function testAuthAutoExcludesHeaderDefinitions(array $authConfig, array $expectedRemovedHeaders)
    {
        \Config::set('apidoc.postman', [
            'auth' => $authConfig,
        ]);

        $route = $this->createMockRouteData('some/path');
        $route['headers'] = $expectedRemovedHeaders;
        $collection = $this->createMockRouteGroup([$route], 'Group');
        $writer = new PostmanCollectionWriter($collection, 'fake.localhost');
        $collection = json_decode($writer->getCollection(), true);

        foreach ($expectedRemovedHeaders as $key => $value) {
            $this->assertNotContains(compact('key', 'value'), data_get($collection, 'item.0.item.0.request.header'));
        }
    }

    public function provideAuthConfigHeaderData()
    {
        yield [
            ['type' => 'bearer', 'bearer' => ['token' => 'Test']],
            ['Authorization' => 'Bearer Test'],
        ];

        yield [
            ['type' => 'apikey', 'apikey' => ['value' => 'Test', 'key' => 'X-Authorization']],
            ['X-Authorization' => 'Test'],
        ];
    }

    public function testApiKeyAuthIsIgnoredIfExplicitlyNotInHeader()
    {
        \Config::set('apidoc.postman', [
            'auth' => ['type' => 'apikey', 'apikey' => [
                'value' => 'Test',
                'key' => 'X-Authorization',
                'in' => 'notheader',
            ]],
        ]);

        $route = $this->createMockRouteData('some/path');
        $route['headers'] = ['X-Authorization' => 'Test'];
        $collection = $this->createMockRouteGroup([$route], 'Group');
        $writer = new PostmanCollectionWriter($collection, 'fake.localhost');
        $collection = json_decode($writer->getCollection(), true);

        $this->assertContains([
            'key' => 'X-Authorization',
            'value' => 'Test',
        ], data_get($collection, 'item.0.item.0.request.header'));
    }

    protected function createMockRouteData($path, $title = '')
    {
        return [
            'uri' => $path,
            'methods' => ['GET'],
            'headers' => [],
            'metadata' => [
                'groupDescription' => '',
                'title' => $title,
            ],
            'queryParameters' => [],
            'urlParameters' => [],
            'cleanBodyParameters' => [],
        ];
    }

    protected function createMockRouteGroup(array $routes, $groupName = 'Group')
    {
        return collect([$groupName => collect($routes)]);
    }
}
