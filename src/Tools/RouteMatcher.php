<?php

namespace Mpociot\ApiDoc\Tools;

use Illuminate\Routing\Route;
use Dingo\Api\Routing\RouteCollection;
use Illuminate\Support\Facades\Route as RouteFacade;

class RouteMatcher
{
    public function getDingoRoutesToBeDocumented(array $routeRules)
    {
        return $this->getRoutesToBeDocumented($routeRules, true);
    }

    public function getLaravelRoutesToBeDocumented(array $routeRules)
    {
        return $this->getRoutesToBeDocumented($routeRules);
    }

    public function getRoutesToBeDocumented(array $routeRules, bool $usingDingoRouter = false)
    {
        $matchedRoutes = [];

        foreach ($routeRules as $routeRule) {
            $includes = $routeRule['include'] ?? [];
            $allRoutes = $this->getAllRoutes($usingDingoRouter, $routeRule['match']['versions'] ?? []);

            foreach ($allRoutes as $route) {
                if (is_array($route)) {
                    $route = new LumenRouteAdapter($route);
                }

                if ($this->shouldExcludeRoute($route, $routeRule)) {
                    continue;
                }

                if ($this->shouldIncludeRoute($route, $routeRule, $includes, $usingDingoRouter)) {
                    $matchedRoutes[] = [
                        'route' => $route,
                        'apply' => $routeRule['apply'] ?? [],
                    ];
                    continue;
                }
            }
        }

        return $matchedRoutes;
    }

    private function getAllRoutes(bool $usingDingoRouter, array $versions = [])
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

        return str_is($mustIncludes, $route->getName())
            || (str_is($routeRule['match']['domains'] ?? [], $route->getDomain())
            && str_is($routeRule['match']['prefixes'] ?? [], $route->uri())
            && $matchesVersion);
    }

    private function shouldExcludeRoute(Route $route, array $routeRule)
    {
        $excludes = $routeRule['exclude'] ?? [];

        return str_is($excludes, $route->getName());
    }
}
