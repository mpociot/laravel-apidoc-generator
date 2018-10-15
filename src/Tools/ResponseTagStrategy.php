<?php
/**
 * Created by shalvah
 * Date: 15-Oct-18
 * Time: 15:07
 */

namespace Mpociot\ApiDoc\Tools;


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
            return $tag instanceof Tag && \strtolower($tag->getName()) == 'response';
        });
        if (empty($responseTags)) {
            return;
        }
        $responseTag = \array_first($responseTags);

        return \response()->json($responseTag->getContent());
    }

}
