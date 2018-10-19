<?php

namespace Mpociot\ApiDoc\Tools;

use Illuminate\Routing\Route;

/**
 * Class LumenRouteAdapter.
 */
class LumenRouteAdapter extends Route
{
    /**
     * LumenRouteAdapter constructor.
     *
     * @param array $lumenRoute
     */
    public function __construct(array $lumenRoute)
    {
        parent::__construct($lumenRoute['method'], $lumenRoute['uri'], $lumenRoute['action']);
    }
}
