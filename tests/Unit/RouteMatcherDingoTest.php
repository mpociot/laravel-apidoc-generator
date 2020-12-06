<?php

namespace Mpociot\ApiDoc\Tests\Unit;

use Dingo\Api\Routing\Router;
use Illuminate\Support\Str;
use Mpociot\ApiDoc\Matching\RouteMatcher;
use Orchestra\Testbench\TestCase;

/**
 * @group dingo
 */
class RouteMatcherDingoTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            "\Dingo\Api\Provider\LaravelServiceProvider",
        ];
    }

    public function testRespectsDomainsRuleForDingoRouter()
    {
        $this->registerDingoRoutes();
        $routeRules[0]['match']['versions'] = ['v1'];
        $routeRules[0]['match']['prefixes'] = ['*'];

        $routeRules[0]['match']['domains'] = ['*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $this->assertCount(12, $routes);

        $routeRules[0]['match']['domains'] = ['domain1.*', 'domain2.*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $this->assertCount(12, $routes);

        $routeRules[0]['match']['domains'] = ['domain1.*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $this->assertCount(6, $routes);
        foreach ($routes as $route) {
            $this->assertStringContainsString('domain1', $route['route']->getDomain());
        }

        $routeRules[0]['match']['domains'] = ['domain2.*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $this->assertCount(6, $routes);
        foreach ($routes as $route) {
            $this->assertStringContainsString('domain2', $route['route']->getDomain());
        }
    }

    public function testRespectsPrefixesRuleForDingoRouter()
    {
        $this->registerDingoRoutes();
        $routeRules[0]['match']['versions'] = ['v1'];
        $routeRules[0]['match']['domains'] = ['*'];

        $routeRules[0]['match']['prefixes'] = ['*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $this->assertCount(12, $routes);

        $routeRules[0]['match']['prefixes'] = ['prefix1/*', 'prefix2/*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $this->assertCount(8, $routes);

        $routeRules[0]['match']['prefixes'] = ['prefix1/*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $this->assertCount(4, $routes);
        foreach ($routes as $route) {
            $this->assertTrue(Str::is('prefix1/*', $route['route']->uri()));
        }

        $routeRules[0]['match']['prefixes'] = ['prefix2/*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $this->assertCount(4, $routes);
        foreach ($routes as $route) {
            $this->assertTrue(Str::is('prefix2/*', $route['route']->uri()));
        }
    }

    public function testRespectsVersionsRuleForDingoRouter()
    {
        $this->registerDingoRoutes();

        $routeRules[0]['match']['versions'] = ['v2'];
        $routeRules[0]['match']['domains'] = ['*'];
        $routeRules[0]['match']['prefixes'] = ['*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $this->assertCount(6, $routes);
        foreach ($routes as $route) {
            $this->assertNotEmpty(array_intersect($route['route']->versions(), ['v2']));
        }

        $routeRules[0]['match']['versions'] = ['v1', 'v2'];
        $routeRules[0]['match']['domains'] = ['*'];
        $routeRules[0]['match']['prefixes'] = ['*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $this->assertCount(18, $routes);
    }

    public function testWillIncludeRouteIfListedExplicitlyForDingoRouter()
    {
        $this->registerDingoRoutes();

        $mustInclude = 'v2.domain2';
        $routeRules = [
            [
                'match' => [
                    'domains' => ['domain1.*'],
                    'prefixes' => ['prefix1/*'],
                    'versions' => ['v1'],
                ],
                'include' => [$mustInclude],
            ],
        ];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $oddRuleOut = collect($routes)->filter(function ($route) use ($mustInclude) {
            return $route['route']->getName() === $mustInclude;
        });
        $this->assertCount(1, $oddRuleOut);
    }

    public function testWillIncludeRouteIfMatchForAnIncludePatternForDingoRouter()
    {
        $this->registerDingoRoutes();

        $mustInclude = ['v2.domain1', 'v2.domain2'];
        $includePattern = 'v2.domain*';
        $routeRules = [
            [
                'match' => [
                    'domains' => ['domain1.*'],
                    'prefixes' => ['prefix1/*'],
                    'versions' => ['v1'],
                ],
                'include' => [$includePattern],
            ],
        ];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $oddRuleOut = collect($routes)->filter(function ($route) use ($mustInclude) {
            return in_array($route['route']->getName(), $mustInclude);
        });
        $this->assertCount(count($mustInclude), $oddRuleOut);
    }

    public function testWillExcludeRouteIfListedExplicitlyForDingoRouter()
    {
        $this->registerDingoRoutes();

        $mustNotInclude = 'v2.domain2';
        $routeRules = [
            [
                'match' => [
                    'domains' => ['domain2.*'],
                    'prefixes' => ['*'],
                    'versions' => ['v2'],
                ],
                'exclude' => [$mustNotInclude],
            ],
        ];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $oddRuleOut = collect($routes)->filter(function ($route) use ($mustNotInclude) {
            return $route['route']->getName() === $mustNotInclude;
        });
        $this->assertCount(0, $oddRuleOut);
    }

    public function testWillExcludeRouteIfMatchForAnExcludePatterForDingoRouter()
    {
        $this->registerDingoRoutes();

        $mustNotInclude = ['v2.prefix1.domain2', 'v2.prefix2.domain2'];
        $excludePattern = 'v2.*.domain2';
        $routeRules = [
            [
                'match' => [
                    'domains' => ['domain2.*'],
                    'prefixes' => ['*'],
                    'versions' => ['v2'],
                ],
                'exclude' => [$excludePattern],
            ],
        ];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $oddRuleOut = collect($routes)->filter(function ($route) use ($mustNotInclude) {
            return in_array($route['route']->getName(), $mustNotInclude);
        });
        $this->assertCount(0, $oddRuleOut);
    }

    public function testMergesRoutesFromDifferentRuleGroupsForDingoRouter()
    {
        $this->registerDingoRoutes();
        $routeRules = [
            [
                'match' => [
                    'domains' => ['*'],
                    'prefixes' => ['*'],
                    'versions' => ['v1'],
                ],
            ],
            [
                'match' => [
                    'domains' => ['*'],
                    'prefixes' => ['*'],
                    'versions' => ['v2'],
                ],
            ],
        ];

        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules, 'dingo');
        $this->assertCount(18, $routes);

        $routes = collect($routes);
        $firstRuleGroup = $routes->filter(function ($route) {
            return ! empty(array_intersect($route['route']->versions(), ['v1']));
        });
        $this->assertCount(12, $firstRuleGroup);

        $secondRuleGroup = $routes->filter(function ($route) {
            return ! empty(array_intersect($route['route']->versions(), ['v2']));
        });
        $this->assertCount(6, $secondRuleGroup);
    }

    private function registerDingoRoutes()
    {
        $api = app('api.router');
        $api->version('v1', function (Router $api) {
            $api->group(['domain' => 'domain1.app.test'], function (Router $api) {
                $api->post('/domain1-1', function () {
                    return 'hi';
                })->name('v1.domain1-1');
                $api->get('domain1-2', function () {
                    return 'hi';
                })->name('v1.domain1-2');
                $api->get('/prefix1/domain1-1', function () {
                    return 'hi';
                })->name('v1.prefix1.domain1-1');
                $api->get('prefix1/domain1-2', function () {
                    return 'hi';
                })->name('v1.prefix1.domain1-2');
                $api->get('/prefix2/domain1-1', function () {
                    return 'hi';
                })->name('v1.prefix2.domain1-1');
                $api->get('prefix2/domain1-2', function () {
                    return 'hi';
                })->name('v1.prefix2.domain1-2');
            });
            $api->group(['domain' => 'domain2.app.test'], function (Router $api) {
                $api->post('/domain2-1', function () {
                    return 'hi';
                })->name('v1.domain2-1');
                $api->get('domain2-2', function () {
                    return 'hi';
                })->name('v1.domain2-2');
                $api->get('/prefix1/domain2-1', function () {
                    return 'hi';
                })->name('v1.prefix1.domain2-1');
                $api->get('prefix1/domain2-2', function () {
                    return 'hi';
                })->name('v1.prefix1.domain2-2');
                $api->get('/prefix2/domain2-1', function () {
                    return 'hi';
                })->name('v1.prefix2.domain2-1');
                $api->get('prefix2/domain2-2', function () {
                    return 'hi';
                })->name('v1.prefix2.domain2-2');
            });
        });
        $api->version('v2', function (Router $api) {
            $api->group(['domain' => 'domain1.app.test'], function (Router $api) {
                $api->post('/domain1', function () {
                    return 'hi';
                })->name('v2.domain1');
                $api->get('/prefix1/domain1', function () {
                    return 'hi';
                })->name('v2.prefix1.domain1');
                $api->get('/prefix2/domain1', function () {
                    return 'hi';
                })->name('v2.prefix2.domain1');
            });
            $api->group(['domain' => 'domain2.app.test'], function (Router $api) {
                $api->post('/domain2', function () {
                    return 'hi';
                })->name('v2.domain2');
                $api->get('/prefix1/domain2', function () {
                    return 'hi';
                })->name('v2.prefix1.domain2');
                $api->get('/prefix2/domain2', function () {
                    return 'hi';
                })->name('v2.prefix2.domain2');
            });
        });
    }
}
