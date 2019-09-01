<?php

namespace Mpociot\ApiDoc\Tools;

use ReflectionClass;
use Illuminate\Routing\Route;
use Mpociot\Reflection\DocBlock;

class RouteDocBlocker
{

    public static $docBlocks = [];

    public static function getDocBlocksFromRoute(Route $route)
    {
        list($className, $methodName) = Utils::getRouteClassAndMethodNames($route);
        $docBlocks = self::getCachedDocBlock($route, $className, $methodName);
        if ($docBlocks) {
            return $docBlocks;
        }

        $class = new ReflectionClass($className);

        if (! $class->hasMethod($methodName)) {
            throw new \Exception("Error while fetching docblock for route: Class $className does not contain method $methodName");
        }

        $docBlocks = [
            'method' => new DocBlock($class->getMethod($methodName)->getDocComment() ?: ''),
            'class' => new DocBlock($class->getDocComment() ?: '')
        ];
        self::cacheDocBlocks($route, $className, $methodName, $docBlocks);
        return $docBlocks;
    }

    protected static function getCachedDocBlock(Route $route, string $className, string $methodName)
    {
        $routeId = self::getRouteId($route, $className, $methodName);
        return self::$docBlocks[$routeId] ?? null;
    }

    protected static function cacheDocBlocks(Route $route, string $className, string $methodName, array $docBlocks)
    {
        $routeId = self::getRouteId($route, $className, $methodName);
        self::$docBlocks[$routeId] = $docBlocks;
    }

    private static function getRouteId(Route $route, string $className, string $methodName)
    {
        return $route->uri()
            .':'
            .implode(array_diff($route->methods(), ['HEAD']))
            .$className
            .$methodName;
    }
}
