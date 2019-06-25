<?php

namespace Mpociot\ApiDoc\Tools\ResponseStrategies;

use Illuminate\Routing\Route;
use Illuminate\Http\JsonResponse;
use Mpociot\Reflection\DocBlock\Tag;

/**
 * Get a response from from a file in the docblock ( @responseFile ).
 */
class ResponsePdfFileStrategy
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
        return $this->getPdfFileResponses($tags);
    }

    /**
     * Get the response from the file if available.
     *
     * @param array $tags
     *
     * @return array|null
     */
    protected function getPdfFileResponses(array $tags)
    {
        // avoid "holes" in the keys of the filtered array, by using array_values on the filtered array
        $responsePdfFileTags = array_values(
            array_filter($tags, function ($tag) {
                return $tag instanceof Tag && strtolower($tag->getName()) === 'responsepdffile';
            })
        );

        if (empty($responsePdfFileTags)) {
            return;
        }

        return array_map(function (Tag $responsePdfFileTag) {
            return new JsonResponse(null, 200, ['Content-Type' => 'application/pdf']);
        }, $responsePdfFileTags);
    }
}
