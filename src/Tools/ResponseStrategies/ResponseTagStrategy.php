<?php

namespace Mpociot\ApiDoc\Tools\ResponseStrategies;

use Illuminate\Routing\Route;
use Illuminate\Http\JsonResponse;
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
     * @return array|null
     */
    public function __invoke(Route $route, array $tags, array $routeProps)
    {
        return $this->getDocBlockResponses($tags);
    }

    /**
     * Get the response from the docblock if available.
     *
     * @param array $tags
     *
     * @return array|null
     */
    protected function getDocBlockResponses(array $tags)
    {
        $responseTags = array_values(
            array_filter($tags, function ($tag) {
                return $tag instanceof Tag && strtolower($tag->getName()) === 'response';
            })
        );

        if (empty($responseTags)) {
            return;
        }

        return array_map(function (Tag $responseTag) {
            preg_match('/^(\d{3})?\s?([\s\S]*)$/', $responseTag->getContent(), $result);

            $status = $result[1] ?: 200;
            $content = $result[2] ?: '{}';

            return new JsonResponse(json_decode($content, true), (int) $status);
        }, $responseTags);
    }
}
