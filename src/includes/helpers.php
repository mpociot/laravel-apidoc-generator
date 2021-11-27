<?php

/**
 * Output the given text to the console.
 *
 * @param  string $output
 * @return void
 */
if (!function_exists('info')) {
    function info($output)
    {
        output('<info>' . $output . '</info>');
    }
}

/**
 * Output the given text to the console.
 *
 * @param  string $output
 * @return void
 */
if (!function_exists('output')) {
    function output($output)
    {
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] == 'testing') {
            return;
        }
        (new Symfony\Component\Console\Output\ConsoleOutput)->writeln($output);
    }
}

/**
 * Recursively copy files from one directory to another
 *
 * @param String $src - Source of files being moved
 * @param String $dest - Destination of files being moved
 * @return bool
 */

if (!function_exists('rcopy')) {
    function rcopy($src, $dest)
    {

        // If source is not a directory stop processing
        if (!is_dir($src)) return false;

        // If the destination directory does not exist create it
        if (!is_dir($dest)) {
            if (!mkdir($dest)) {
                // If the destination directory could not be created stop processing
                return false;
            }
        }

        // Open the source directory to read in files
        $i = new DirectoryIterator($src);
        foreach ($i as $f) {
            if ($f->isFile()) {
                copy($f->getRealPath(), "$dest/" . $f->getFilename());
            } else if (!$f->isDot() && $f->isDir()) {
                rcopy($f->getRealPath(), "$dest/$f");
            }
        }
    }
}