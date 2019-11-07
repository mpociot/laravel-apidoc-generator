<?php

namespace Mpociot\ApiDoc\Matching;

use Illuminate\Routing\Route;

/**
 * Class LumenRouteAdapter.
 * Lumen routes don't extend from Laravel routes,
 * so we need this class to convert a Lmen route to a Laravel one.
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
