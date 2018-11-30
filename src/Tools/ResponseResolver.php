<?php

namespace Mpociot\ApiDoc\Tools;

use Illuminate\Routing\Route;
use Illuminate\Http\JsonResponse;
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
     * @return array
     */
    private function resolve(array $tags, array $routeProps)
    {
        $response = null;

        foreach (static::$strategies as $strategy) {
            $strategy = new $strategy();

            /** @var JsonResponse|array|null $response */
            $response = $strategy($this->route, $tags, $routeProps);

            if (! is_null($response)) {
                if (is_array($response)) {
                    return array_map(function (JsonResponse $response) {
                        return ['status' => $response->getStatusCode(), 'content' => $this->getResponseContent($response)];
                    }, $response);
                }

                return [['status' => $response->getStatusCode(), 'content' => $this->getResponseContent($response)]];
            }
        }
    }

    /**
     * @param $route
     * @param $tags
     * @param $routeProps
     *
     * @return array
     */
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
