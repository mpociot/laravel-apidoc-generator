<?php

namespace Mpociot\ApiDoc\Tools;

use Illuminate\Routing\Route;
use Dingo\Api\Routing\RouteCollection;
use Illuminate\Support\Facades\Route as RouteFacade;

class RouteMatcher
{
    public function getDingoRoutesToBeDocumented(array $routeRules)
    {
        return $this->getRoutesToBeDocumented($routeRules,true);
    }

    public function getLaravelRoutesToBeDocumented(array $routeRules)
    {
        return $this->getRoutesToBeDocumented($routeRules);
    }

    /**
     * @return mixed
     */
    public function getRoutesToBeDocumented(array $routeRules, bool $usingDingoRouter = false)
    {
        $matchedRoutes = [];

        foreach ($routeRules as $routeRule) {
            $excludes = $routeRule['exclude'] ?? [];
            $includes = $routeRule['include'] ?? [];
            $allRoutes = $this->getAllRoutes($usingDingoRouter, $routeRule['match']['versions'] ?? []);

            foreach ($allRoutes as $route) {
                /** @var Route $route */
                if (in_array($route->getName(), $excludes)) {
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
        if (!$usingDingoRouter) {
            return RouteFacade::getRoutes();
        }

        $allRouteCollections = app(\Dingo\Api\Routing\Router::class)->getRoutes();
        if (empty($versions)) {
            return $allRoutes;
        }

        return collect($allRouteCollections)
            ->flatMap(function (RouteCollection $collection) {
                return $collection->getRoutes();
            })->toArray();
    }

    private function shouldIncludeRoute(Route $route, $routeRule, array $mustIncludes, bool $usingDingoRouter)
    {
        $matchesVersion = $usingDingoRouter
            ? !empty(array_intersect($route->versions(), $routeRule['match']['versions'] ?? []))
            : true;

        return in_array($route->getName(), $mustIncludes)
            || (str_is($routeRule['match']['domains'] ?? [], $route->getDomain())
            && str_is($routeRule['match']['prefixes'] ?? [], $route->uri())
            && $matchesVersion);
    }

}
