<?php

namespace Mpociot\ApiDoc\Tools\ResponseStrategies;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
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
        $responseFileTags = array_filter($tags, function ($tag) {
            return $tag instanceof Tag && strtolower($tag->getName()) == 'responsefile';
        });
        if (empty($responseFileTags)) {
            return;
        }
        $responseFileTag = array_first($responseFileTags);

        $json = json_decode(Storage::get($responseFileTag->getContent()), true);

        return response()->json($json);
    }
}
