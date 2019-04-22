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
