<?php

namespace Mpociot\ApiDoc\Matching\RouteMatcher;

use Illuminate\Routing\Route;

class Match implements \ArrayAccess
{
    /**
     * @var Route
     */
    protected $route;

    /**
     * @var array
     */
    protected $rules;

    /**
     * Match constructor.
     *
     * @param Route $route
     * @param array $applyRules
     */
    public function __construct(Route $route, array $applyRules)
    {
        $this->route = $route;
        $this->rules = $applyRules;
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return is_callable([$this, 'get' . ucfirst($offset)]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return call_user_func([$this, 'get' . ucfirst($offset)]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        return $this->$offset = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }
}
