<?php

namespace Mpociot\ApiDoc\Tools;

use DirectoryIterator;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

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
    public static function replaceUrlParameterBindings(string $uri, array $bindings)
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

    public static function deleteFolderWithFiles(string $folder)
    {
        if (is_dir($folder)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
            rmdir($folder);
        }
    }

    // TODO replace this shit with a better file manipulation library
    public static function moveFilesFromFolder($src, $dest)
    {

        // If source is not a directory stop processing
        if (! is_dir($src)) {
            return false;
        }

        // If the destination directory does not exist create it
        if (! is_dir($dest)) {
            if (! mkdir($dest)) {
                // If the destination directory could not be created stop processing
                return false;
            }
        }

        // Open the source directory to read in files
        $i = new DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                rename($f->getRealPath(), "$dest/".$f->getFilename());
            } elseif (! $f->isDot() && $f->isDir()) {
                rcopy($f->getRealPath(), "$dest/$f");
            }
        }
    }
}
