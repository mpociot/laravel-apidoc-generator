<?php

namespace Mpociot\ApiDoc\Matching;

use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Dingo\Api\Routing\RouteCollection;
use Illuminate\Support\Facades\Route as RouteFacade;

class RouteMatcher
{
    /**
     * @var string
     */
    protected $router;

    /**
     * @var array
     */
    protected $routeRules;

    public function __construct(array $routeRules = [], string $router = 'laravel')
    {
        $this->router = $router;
        $this->routeRules = $routeRules;
    }

    public function getRoutes()
    {
        $usingDingoRouter = strtolower($this->router) == 'dingo';

        return $this->getRoutesToBeDocumented($this->routeRules, $usingDingoRouter);
    }

    protected function getRoutesToBeDocumented(array $routeRules, bool $usingDingoRouter = false)
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
