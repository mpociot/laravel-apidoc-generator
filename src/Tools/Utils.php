<?php

namespace Mpociot\ApiDoc\Tools;

use Illuminate\Support\Str;
use Illuminate\Routing\Route;

class Utils
{
    public static function getFullUrl(Route $route, array $bindings = []): string
    {
        $uri = $route->uri();

        return self::replaceUrlParameterBindings($uri, $bindings);
    }

    public static function getRouteActionUses(array $action): ?array
    {
        if ($action['uses'] !== null) {
            if (is_array($action['uses'])) {
                return $action['uses'];
            }
            elseif (is_string($action['uses'])) {
                return explode('@', $action['uses']);
            }
        }
        if (array_key_exists(0, $action) && array_key_exists(1, $action)) {
            return [
                0 => $action[0],
                1 => $action[1]
            ];
        }

        return null;
    }

    /**
     * Transform parameters in URLs into real values (/users/{user} -> /users/2).
     * Uses bindings specified by caller, otherwise just uses '1'.
     *
     * @param string $uri
     * @param array $bindings
     *
     * @return mixed
     */
    protected static function replaceUrlParameterBindings(string $uri, array $bindings)
    {
        foreach ($bindings as $path => $binding) {
            // So we can support partial bindings like
            // 'bindings' => [
            //  'foo/{type}' => 4,
            //  'bar/{type}' => 2
            //],
            if (Str::is("*$path*", $uri)) {
                preg_match('/({.+?})/', $path, $parameter);
                $uri = str_replace("{$parameter['1']}", $binding, $uri);
            }
        }
        // Replace any unbound parameters with '1'
        $uri = preg_replace('/{(.+?)}/', 1, $uri);

        return $uri;
    }
}
