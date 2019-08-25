<?php

namespace Mpociot\ApiDoc\Tools;

use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Mpociot\ApiDoc\Tools\ResponseStrategies\ResponseTagStrategy;
use Mpociot\ApiDoc\Tools\ResponseStrategies\ResponseCallStrategy;
use Mpociot\ApiDoc\Tools\ResponseStrategies\ResponseFileStrategy;
use Mpociot\ApiDoc\Tools\ResponseStrategies\TransformerTagsStrategy;

class ResponseResolver
{
    /**
     * @var array
     */
    public static $strategies = [
        ResponseTagStrategy::class,
        TransformerTagsStrategy::class,
        ResponseFileStrategy::class,
        ResponseCallStrategy::class,
    ];

    /**
     * @var Route
     */
    private $route;

    /**
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * @param array $tags
     * @param array $routeProps
     *
     * @return array|null
     */
    private function resolve(array $tags, array $routeProps)
    {
        foreach (static::$strategies as $strategy) {
            $strategy = new $strategy();

            /** @var Response[]|null $response */
            $responses = $strategy($this->route, $tags, $routeProps);

            if (! is_null($responses)) {
                return array_map(function (Response $response) {
                    return ['status' => $response->getStatusCode(), 'content' => $this->getResponseContent($response)];
                }, $responses);
            }
        }
    }

    /**
     * @param Route $route
     * @param array $tags
     * @param array $routeProps
     *
     * @return array
     */
    public static function getResponse(Route $route, array $tags, array $routeProps)
    {
        return (new static($route))->resolve($tags, $routeProps);
    }

    /**
     * @param Response $response
     *
     * @return string
     */
    private function getResponseContent(Response $response)
    {
        return $response->getContent() ?: '';
    }
}
