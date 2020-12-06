<?php

namespace Mpociot\ApiDoc\Tests\Unit;

use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Mpociot\ApiDoc\Matching\RouteMatcher;
use Orchestra\Testbench\TestCase;

class RouteMatcherTest extends TestCase
{
    public function testRespectsDomainsRuleForLaravelRouter()
    {
        $this->registerLaravelRoutes();
        $routeRules[0]['match']['prefixes'] = ['*'];

        $routeRules[0]['match']['domains'] = ['*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $this->assertCount(12, $routes);

        $routeRules[0]['match']['domains'] = ['domain1.*', 'domain2.*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $this->assertCount(12, $routes);

        $routeRules[0]['match']['domains'] = ['domain1.*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $this->assertCount(6, $routes);
        foreach ($routes as $route) {
            $this->assertStringContainsString('domain1', $route['route']->getDomain());
        }

        $routeRules[0]['match']['domains'] = ['domain2.*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $this->assertCount(6, $routes);
        foreach ($routes as $route) {
            $this->assertStringContainsString('domain2', $route['route']->getDomain());
        }
    }

    public function testRespectsPrefixesRuleForLaravelRouter()
    {
        $this->registerLaravelRoutes();
        $routeRules[0]['match']['domains'] = ['*'];

        $routeRules[0]['match']['prefixes'] = ['*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $this->assertCount(12, $routes);

        $routeRules[0]['match']['prefixes'] = ['prefix1/*', 'prefix2/*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $this->assertCount(8, $routes);

        $routeRules[0]['match']['prefixes'] = ['prefix1/*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $this->assertCount(4, $routes);
        foreach ($routes as $route) {
            $this->assertTrue(Str::is('prefix1/*', $route['route']->uri()));
        }

        $routeRules[0]['match']['prefixes'] = ['prefix2/*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $this->assertCount(4, $routes);
        foreach ($routes as $route) {
            $this->assertTrue(Str::is('prefix2/*', $route['route']->uri()));
        }
    }

    public function testWillIncludeRouteIfListedExplicitlyForLaravelRouter()
    {
        $this->registerLaravelRoutes();
        $mustInclude = 'domain1-1';
        $routeRules[0]['include'] = [$mustInclude];

        $routeRules[0]['match']['domains'] = ['domain1.*'];
        $routeRules[0]['match']['prefixes'] = ['prefix1/*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $oddRuleOut = collect($routes)->filter(function ($route) use ($mustInclude) {
            return $route['route']->getName() === $mustInclude;
        });
        $this->assertCount(1, $oddRuleOut);
    }

    public function testWillIncludeRouteIfMatchForAnIncludePatternForLaravelRouter()
    {
        $this->registerLaravelRoutes();
        $mustInclude = ['domain1-1', 'domain1-2'];
        $includePattern = 'domain1-*';
        $routeRules[0]['include'] = [$includePattern];

        $routeRules[0]['match']['domains'] = ['domain1.*'];
        $routeRules[0]['match']['prefixes'] = ['prefix1/*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $oddRuleOut = collect($routes)->filter(function ($route) use ($mustInclude) {
            return in_array($route['route']->getName(), $mustInclude);
        });
        $this->assertCount(count($mustInclude), $oddRuleOut);
    }

    public function testWillExcludeRouteIfListedExplicitlyForLaravelRouter()
    {
        $this->registerLaravelRoutes();
        $mustNotInclude = 'prefix1.domain1-1';
        $routeRules[0]['exclude'] = [$mustNotInclude];

        $routeRules[0]['match']['domains'] = ['domain1.*'];
        $routeRules[0]['match']['prefixes'] = ['prefix1/*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $oddRuleOut = collect($routes)->filter(function ($route) use ($mustNotInclude) {
            return $route['route']->getName() === $mustNotInclude;
        });
        $this->assertCount(0, $oddRuleOut);
    }

    public function testWillExcludeRouteIfMatchForAnExcludePatternForLaravelRouter()
    {
        $this->registerLaravelRoutes();
        $mustNotInclude = ['prefix1.domain1-1', 'prefix1.domain1-2'];
        $excludePattern = 'prefix1.domain1-*';
        $routeRules[0]['exclude'] = [$excludePattern];

        $routeRules[0]['match']['domains'] = ['domain1.*'];
        $routeRules[0]['match']['prefixes'] = ['prefix1/*'];
        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $oddRuleOut = collect($routes)->filter(function ($route) use ($mustNotInclude) {
            return in_array($route['route']->getName(), $mustNotInclude);
        });
        $this->assertCount(0, $oddRuleOut);
    }

    public function testMergesRoutesFromDifferentRuleGroupsForLaravelRouter()
    {
        $this->registerLaravelRoutes();

        $routeRules = [
            [
                'match' => [
                    'domains' => ['domain1.*'],
                    'prefixes' => ['prefix1/*'],
                ],
            ],
            [
                'match' => [
                    'domains' => ['domain2.*'],
                    'prefixes' => ['prefix2*'],
                ],
            ],
        ];

        $matcher = new RouteMatcher();
        $routes = $matcher->getRoutes($routeRules);
        $this->assertCount(4, $routes);

        $routes = collect($routes);
        $firstRuleGroup = $routes->filter(function ($route) {
            return Str::is('prefix1/*', $route['route']->uri())
                && Str::is('domain1.*', $route['route']->getDomain());
        });
        $this->assertCount(2, $firstRuleGroup);

        $secondRuleGroup = $routes->filter(function ($route) {
            return Str::is('prefix2/*', $route['route']->uri())
                && Str::is('domain2.*', $route['route']->getDomain());
        });
        $this->assertCount(2, $secondRuleGroup);
    }

    private function registerLaravelRoutes()
    {
        RouteFacade::group(['domain' => 'domain1.app.test'], function () {
            RouteFacade::post('/domain1-1', function () {
                return 'hi';
            })->name('domain1-1');
            RouteFacade::get('domain1-2', function () {
                return 'hi';
            })->name('domain1-2');
            RouteFacade::get('/prefix1/domain1-1', function () {
                return 'hi';
            })->name('prefix1.domain1-1');
            RouteFacade::get('prefix1/domain1-2', function () {
                return 'hi';
            })->name('prefix1.domain1-2');
            RouteFacade::get('/prefix2/domain1-1', function () {
                return 'hi';
            })->name('prefix2.domain1-1');
            RouteFacade::get('prefix2/domain1-2', function () {
                return 'hi';
            })->name('prefix2.domain1-2');
        });
        RouteFacade::group(['domain' => 'domain2.app.test'], function () {
            RouteFacade::post('/domain2-1', function () {
                return 'hi';
            })->name('domain2-1');
            RouteFacade::get('domain2-2', function () {
                return 'hi';
            })->name('domain2-2');
            RouteFacade::get('/prefix1/domain2-1', function () {
                return 'hi';
            })->name('prefix1.domain2-1');
            RouteFacade::get('prefix1/domain2-2', function () {
                return 'hi';
            })->name('prefix1.domain2-2');
            RouteFacade::get('/prefix2/domain2-1', function () {
                return 'hi';
            })->name('prefix2.domain2-1');
            RouteFacade::get('prefix2/domain2-2', function () {
                return 'hi';
            })->name('prefix2.domain2-2');
        });
    }
}
