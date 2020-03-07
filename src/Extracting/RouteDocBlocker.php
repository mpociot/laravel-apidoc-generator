<?php

namespace Mpociot\ApiDoc\Extracting;

use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Tools\Utils;
use Mpociot\Reflection\DocBlock;
use ReflectionClass;

/**
 * Class RouteDocBlocker
 * Utility class to help with retrieving doc blocks from route classes and methods.
 * Also caches them so repeated access is faster.
 */
class RouteDocBlocker
{
    protected static $docBlocks = [];

    /**
     * @param Route $route
     *
     * @throws \ReflectionException
     * @throws \Exception
     *
     * @return array<string, DocBlock> Method and class docblocks
     */
    public static function getDocBlocksFromRoute(Route $route): array
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
            'class' => new DocBlock($class->getDocComment() ?: ''),
        ];
        self::cacheDocBlocks($route, $className, $methodName, $docBlocks);

        return $docBlocks;
    }

    protected static function getCachedDocBlock(Route $route, string $className, string $methodName)
    {
        $routeId = self::getRouteCacheId($route, $className, $methodName);

        return self::$docBlocks[$routeId] ?? null;
    }

    protected static function cacheDocBlocks(Route $route, string $className, string $methodName, array $docBlocks)
    {
        $routeId = self::getRouteCacheId($route, $className, $methodName);
        self::$docBlocks[$routeId] = $docBlocks;
    }

    private static function getRouteCacheId(Route $route, string $className, string $methodName): string
    {
        return $route->uri()
            . ':'
            . implode(array_diff($route->methods(), ['HEAD']))
            . $className
            . $methodName;
    }
}
