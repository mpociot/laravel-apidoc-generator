<?php

namespace Mpociot\ApiDoc\Tools;

use Illuminate\Routing\Route;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarExporter\VarExporter;

class Utils
{
    public static function getFullUrl(Route $route, array $urlParameters = []): string
    {
        $uri = $route->uri();

        return self::replaceUrlParameterPlaceholdersWithValues($uri, $urlParameters);
    }

    /**
     * @param array|Route $routeOrAction
     *
     * @return array|null
     */
    public static function getRouteClassAndMethodNames($routeOrAction)
    {
        $action = $routeOrAction instanceof Route ? $routeOrAction->getAction() : $routeOrAction;

        if ($action['uses'] !== null) {
            if (is_array($action['uses'])) {
                return $action['uses'];
            } elseif (is_string($action['uses'])) {
                return explode('@', $action['uses']);
            }
        }
        if (array_key_exists(0, $action) && array_key_exists(1, $action)) {
            return [
                0 => $action[0],
                1 => $action[1],
            ];
        }
    }

    /**
     * Transform parameters in URLs into real values (/users/{user} -> /users/2).
     * Uses @urlParam values specified by caller, otherwise just uses '1'.
     *
     * @param string $uri
     * @param array $urlParameters Dictionary of url params and example values
     *
     * @return mixed
     */
    public static function replaceUrlParameterPlaceholdersWithValues(string $uri, array $urlParameters)
    {
        $matches = preg_match_all('/{.+?}/i', $uri, $parameterPaths);
        if (! $matches) {
            return $uri;
        }

        foreach ($parameterPaths[0] as $parameterPath) {
            $key = trim($parameterPath, '{?}');
            if (isset($urlParameters[$key])) {
                $example = $urlParameters[$key];
                $uri = str_replace($parameterPath, $example, $uri);
            }
        }
        // Remove unbound optional parameters with nothing
        $uri = preg_replace('#{([^/]+\?)}#', '', $uri);
        // Replace any unbound non-optional parameters with '1'
        $uri = preg_replace('#{([^/]+)}#', '1', $uri);

        return $uri;
    }

    public static function dumpException(\Exception $e)
    {
        if (class_exists(\NunoMaduro\Collision\Handler::class)) {
            $output = new ConsoleOutput(OutputInterface::VERBOSITY_VERBOSE);
            $handler = new \NunoMaduro\Collision\Handler(new \NunoMaduro\Collision\Writer($output));
            $handler->setInspector(new \Whoops\Exception\Inspector($e));
            $handler->setException($e);
            $handler->handle();
        } else {
            dump($e);
            echo "You can get better exception output by installing the library \nunomaduro/collision (PHP 7.1+ only).\n";
        }
    }

    public static function deleteDirectoryAndContents($dir)
    {
        $adapter = new Local(realpath(__DIR__.'/../../'));
        $fs = new Filesystem($adapter);
        $fs->deleteDir($dir);
    }

    public static function printPhpValue($value, $indentationLevel = 0)
    {
        $output = VarExporter::export($value);
        // Padding with x spaces so they align
        $split = explode("\n", $output);
        $result = '';
        $padWith = str_repeat(' ', $indentationLevel);
        foreach ($split as $index => $line) {
            $result .= ($index == 0 ? '' : "\n$padWith").$line;
        }

        return $result;
    }
}
