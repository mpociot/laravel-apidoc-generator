<?php

namespace Mpociot\ApiDoc\Tools\ResponseStrategies;

use Illuminate\Routing\Route;
use Mpociot\Reflection\DocBlock\Tag;

/**
 * Get a response from the docblock ( @response ).
 */
class ResponseTagStrategy
{
    public function __invoke(Route $route, array $tags, array $rulesToApply)
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
            return $tag instanceof Tag && strtolower($tag->getName()) == 'response';
        });
        if (empty($responseTags)) {
            return;
        }
        $responseTag = array_first($responseTags);

        return response()->json(json_decode($responseTag->getContent(), true));
    }
}
