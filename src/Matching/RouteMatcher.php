<?php

namespace Mpociot\ApiDoc\Matching;

use Dingo\Api\Routing\RouteCollection;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Mpociot\ApiDoc\Matching\RouteMatcher\Match;

class RouteMatcher implements RouteMatcherInterface
{
    public function getRoutes(array $routeRules = [], string $router = 'laravel')
    {
        $usingDingoRouter = strtolower($router) == 'dingo';

        return $this->getRoutesToBeDocumented($routeRules, $usingDingoRouter);
    }

    private function getRoutesToBeDocumented(array $routeRules, bool $usingDingoRouter = false)
    {
        $allRoutes = $this->getAllRoutes($usingDingoRouter);

        $matchedRoutes = [];

        foreach ($routeRules as $routeRule) {
            $includes = $routeRule['include'] ?? [];

            foreach ($allRoutes as $route) {
                if (is_array($route)) {
                    $route = new LumenRouteAdapter($route);
                }

                if ($this->shouldExcludeRoute($route, $routeRule)) {
                    continue;
                }

                if ($this->shouldIncludeRoute($route, $routeRule, $includes, $usingDingoRouter)) {
                    $matchedRoutes[] = new Match($route, $routeRule['apply'] ?? []);
                }
            }
        }

        return $matchedRoutes;
    }

    private function getAllRoutes(bool $usingDingoRouter)
    {
        if (! $usingDingoRouter) {
            return RouteFacade::getRoutes();
        }

        $allRouteCollections = app(\Dingo\Api\Routing\Router::class)->getRoutes();

        return collect($allRouteCollections)
            ->flatMap(function (RouteCollection $collection) {
                return $collection->getRoutes();
            })->toArray();
    }

    private function shouldIncludeRoute(Route $route, array $routeRule, array $mustIncludes, bool $usingDingoRouter)
    {
        $matchesVersion = $usingDingoRouter
            ? ! empty(array_intersect($route->versions(), $routeRule['match']['versions'] ?? []))
            : true;

        return Str::is($mustIncludes, $route->getName())
            || Str::is($mustIncludes, $route->uri())
            || (Str::is($routeRule['match']['domains'] ?? [], $route->getDomain())
            && Str::is($routeRule['match']['prefixes'] ?? [], $route->uri())
            && $matchesVersion);
    }

    private function shouldExcludeRoute(Route $route, array $routeRule)
    {
        $excludes = $routeRule['exclude'] ?? [];

        // Exclude this package's routes
        $excludes[] = 'apidoc';

        // Exclude Laravel Telescope routes
        if (class_exists("Laravel\Telescope\Telescope")) {
            $excludes[] = 'telescope/*';
        }

        return Str::is($excludes, $route->getName())
            || Str::is($excludes, $route->uri());
    }
}
