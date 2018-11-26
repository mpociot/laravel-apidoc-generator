<?php

namespace Mpociot\ApiDoc\Tools\ResponseStrategies;

use Illuminate\Routing\Route;
use Mpociot\Reflection\DocBlock\Tag;

/**
 * Get a response from from a file in the docblock ( @responseFile ).
 */
class ResponseFileStrategy
{
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
        $responseTags = array_filter($tags, function ($tag) {
            return $tag instanceof Tag && strtolower($tag->getName()) == 'responsefile';
        });
        if (empty($responseTags)) {
            return;
        }
        $responseTag = array_first($responseTags);

        $json = json_decode(file_get_contents(storage_path($responseTag->getContent()), true), true);

        return response()->json($json);
    }
}
