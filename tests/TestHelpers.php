<?php

namespace Mpociot\ApiDoc\Tests;

use Illuminate\Contracts\Console\Kernel;

trait TestHelpers
{
    /**
     * @param string $command
     * @param array $parameters
     *
     * @return mixed
     */
    public function artisan($command, $parameters = [])
    {
        $this->app[Kernel::class]->call($command, $parameters);

        return $this->app[Kernel::class]->output();
    }

    private function assertFilesHaveSameContent($pathToExpected, $pathToActual)
    {
        $actual = $this->getFileContents($pathToActual);
        $expected = $this->getFileContents($pathToExpected);
        $this->assertSame($expected, $actual);
    }

    /**
     * Get the contents of a file in a cross-platform-compatible way.
     *
     * @param $path
     *
     * @return string
     */
    private function getFileContents($path)
    {
        return str_replace("\r\n", "\n", file_get_contents($path));
    }

    /**
     * Assert that a string contains another string, ignoring all whitespace.
     *
     * @param $needle
     * @param $haystack
     */
    private function assertContainsIgnoringWhitespace($needle, $haystack)
    {
        $haystack = preg_replace('/\s/', '', $haystack);
        $needle = preg_replace('/\s/', '', $needle);
        $this->assertStringContainsString($needle, $haystack);
    }
}
