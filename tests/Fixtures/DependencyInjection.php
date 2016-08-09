<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Contracts\Filesystem\Filesystem;

class DependencyInjection
{
    /**
     * @var
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }
}
