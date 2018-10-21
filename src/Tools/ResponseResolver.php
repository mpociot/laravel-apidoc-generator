<?php

namespace Mpociot\ApiDoc\Tools;

use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Tools\ResponseStrategies\ResponseTagStrategy;
use Mpociot\ApiDoc\Tools\ResponseStrategies\ResponseCallStrategy;
use Mpociot\ApiDoc\Tools\ResponseStrategies\TransformerTagsStrategy;

class ResponseResolver
{
    public static $strategies = [
        ResponseTagStrategy::class,
        TransformerTagsStrategy::class,
        ResponseCallStrategy::class,
    ];

    /**
     * @var Route
     */
    private $route;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    private function resolve(array $tags, array $routeProps)
    {
        $response = null;
        foreach (static::$strategies as $strategy) {
            $strategy = new $strategy();
            $response = $strategy($this->route, $tags, $routeProps);
            if (! is_null($response)) {
                return $this->getResponseContent($response);
            }
        }
    }

    public static function getResponse($route, $tags, $routeProps)
    {
        return (new static($route))->resolve($tags, $routeProps);
    }

    /**
     * @param $response
     *
     * @return mixed
     */
    private function getResponseContent($response)
    {
        return $response ? $response->getContent() : '';
    }
}
