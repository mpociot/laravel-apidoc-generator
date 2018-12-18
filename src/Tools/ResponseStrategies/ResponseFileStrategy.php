<?php

namespace Mpociot\ApiDoc\Tools\ResponseStrategies;

use Illuminate\Routing\Route;
use Illuminate\Http\JsonResponse;
use Mpociot\Reflection\DocBlock\Tag;

/**
 * Get a response from from a file in the docblock ( @responseFile ).
 */
class ResponseFileStrategy
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
        return $this->getFileResponses($tags);
    }

    /**
     * Get the response from the file if available.
     *
     * @param array $tags
     *
     * @return array|null
     */
    protected function getFileResponses(array $tags)
    {
        // avoid "holes" in the keys of the filtered array, by using array_values on the filtered array
        $responseFileTags = array_values(
            array_filter($tags, function ($tag) {
                return $tag instanceof Tag && strtolower($tag->getName()) === 'responsefile';
            })
        );

        if (empty($responseFileTags)) {
            return;
        }

        return array_map(function (Tag $responseFileTag) {
            preg_match('/^(\d{3})?\s?([\S]*[\s]*?)(\{.*\})?$/', $responseFileTag->getContent(), $result);
            $status = $result[1] ?: 200;
            $content = $result[2] ? file_get_contents(storage_path(trim($result[2])), true) : '{}';
            $json = ! empty($result[3]) ? str_replace("'", '"', $result[3]) : '{}';
            $merged = array_merge(json_decode($content, true), json_decode($json, true));

            return new JsonResponse($merged, (int) $status);
        }, $responseFileTags);
    }
}
