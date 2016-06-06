<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Routing\Controller;

class DingoTestController extends Controller
{
    public function dummy()
    {
        return '';
    }

    /**
     * Example title.
     * This will be the long description.
     * It can also be multiple lines long.
     */
    public function parseMethodDescription()
    {
        return '';
    }

    public function parseFormRequestRules(DingoTestRequest $request)
    {
        return '';
    }
}
