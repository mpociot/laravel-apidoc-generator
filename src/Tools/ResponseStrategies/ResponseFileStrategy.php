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
     * @return mixed
     */
    public function __invoke(Route $route, array $tags, array $routeProps)
    {
        return $this->getFileResponse($tags);
    }

    /**
     * Get the response from the file if available.
     *
     * @param array $tags
     *
     * @return mixed
     */
    protected function getFileResponse(array $tags)
    {
        $responseFileTags = array_filter($tags, function ($tag) {
            return $tag instanceof Tag && strtolower($tag->getName()) === 'responsefile';
        });

        if (empty($responseFileTags)) {
            return;
        }

        return array_map(function ($responseFileTag) {
            preg_match('/^(\d{3})?\s?([\s\S]*)$/', $responseFileTag->getContent(), $result);

            $status = $result[1] ?: 200;
            $content = $result[2] ? file_get_contents(storage_path($result[2]), true) : '{}';

            return new JsonResponse(json_decode($content, true), (int) $status);
        }, $responseFileTags);
    }
}
