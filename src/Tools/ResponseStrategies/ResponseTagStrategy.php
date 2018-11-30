<?php

namespace Mpociot\ApiDoc\Tools\ResponseStrategies;

use Illuminate\Routing\Route;
use Mpociot\Reflection\DocBlock\Tag;

/**
 * Get a response from the docblock ( @response ).
 */
class ResponseTagStrategy
{
    /**
     * @param Route $route
     * @param array $tags
     * @param array $routeProps
     *
     * @return mixed
     */
    public function __invoke(Route $route, array $tags, array $routeProps)
    {
        return $this->getDocBlockResponse($tags);
    }

    /**
     * Get the response from the docblock if available.
     *
     * @param array $tags
     *
     * @return mixed
     */
    protected function getDocBlockResponse(array $tags)
    {
        $responseTags = array_filter($tags, function ($tag) {
            return $tag instanceof Tag && strtolower($tag->getName()) === 'response';
        });

        if (empty($responseTags)) {
            return;
        }

        return array_map(function ($responseTag) {
            preg_match('/^(\d{3})?\s?([\s\S]*)$/', $responseTag->getContent(), $result);

            $status = $result[1] ?: 200;
            $content = $result[2] ?: '{}';

            return response()->json(json_decode($content, true), (int) $status);
        }, $responseTags);
    }
}
