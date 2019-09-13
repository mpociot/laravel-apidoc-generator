<?php

namespace Mpociot\ApiDoc\Strategies\Metadata;

use ReflectionClass;
use ReflectionMethod;
use Illuminate\Routing\Route;
use Laravel\Passport\Passport;
use Mpociot\ApiDoc\Strategies\Strategy;

class GetFromMiddleware extends Strategy
{
    public function __invoke(Route $route, ReflectionClass $controller, ReflectionMethod $method, array $routeRules, array $context = [])
    {
        return [
            'scopes' => $this->getScopesInfo($route),
            'headers' => $this->getHeaders($route),
        ];
    }

    protected function getScopesInfo($route)
    {
        $middlewares = $route->gatherMiddleware();
        $anyScopeMiddleware = collect($middlewares)->first(function ($item) {
            return preg_match('/^scope:/', $item);
        });
        if ($anyScopeMiddleware) {
            $scopes = explode(',', preg_replace('/^scope:/', '', $anyScopeMiddleware));
            $scopesInfo = Passport::scopes()->filter(function ($item) use ($scopes) {
                return in_array($item->id, $scopes);
            })->values();

            return [
                'type' => 'any',
                'scopes' => $scopesInfo,
            ];
        }
        $allScopeMiddleware = collect($middlewares)->first(function ($item) {
            return preg_match('/^scopes:/', $item);
        });
        if ($allScopeMiddleware) {
            $scopes = explode(',', preg_replace('/^scopes:/', '', $allScopeMiddleware));
            $scopesInfo = Passport::scopes()->filter(function ($item) use ($scopes) {
                return in_array($item->id, $scopes);
            })->values();

            return [
                'type' => 'all',
                'scopes' => $scopesInfo,
            ];
        }

        return null;
    }

    protected function getHeaders($route)
    {
        $middlewares = $route->gatherMiddleware();
        $authMiddleware = collect($middlewares)->first(function ($item) {
            return preg_match('/^auth:/', $item);
        });
        $headers = [];
        if ($authMiddleware) {
            $guard = preg_replace('/^auth:/', '', $authMiddleware);
            $driver = config('auth.guards.'.$guard.'.driver');
            if ($driver === 'token') {
                $headers['Authorization'] = 'Token {token}';
            } elseif ($driver === 'passport' || $driver === 'jwt') {
                $headers['Authorization'] = 'Bearer {access_token}';
            }
        }

        return $headers;
    }
}
