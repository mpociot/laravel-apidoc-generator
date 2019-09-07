<?php

namespace Mpociot\ApiDoc\Strategies\Responses;

use Illuminate\Routing\Route;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;
use Mpociot\ApiDoc\Strategies\Strategy;
use Mpociot\ApiDoc\Tools\RouteDocBlocker;

/**
 * Get a response from from a file in the docblock ( @responseFile ).
 */
class UseResponseFileTag extends Strategy
{
    /**
     * @param Route $route
     * @param \ReflectionClass $controller
     * @param \ReflectionMethod $method
     * @param array $routeRules
     * @param array $context
     *
     * @throws \Exception
     *
     * @return array|null
     */
    public function __invoke(Route $route, \ReflectionClass $controller, \ReflectionMethod $method, array $routeRules, array $context = [])
    {
        $docBlocks = RouteDocBlocker::getDocBlocksFromRoute($route);
        /** @var DocBlock $methodDocBlock */
        $methodDocBlock = $docBlocks['method'];

        return $this->getFileResponses($methodDocBlock->getTags());
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
        // Avoid "holes" in the keys of the filtered array, by using array_values on the filtered array
        $responseFileTags = array_values(
            array_filter($tags, function ($tag) {
                return $tag instanceof Tag && strtolower($tag->getName()) === 'responsefile';
            })
        );

        if (empty($responseFileTags)) {
            return null;
        }

        $responses = array_map(function (Tag $responseFileTag) {
            preg_match('/^(\d{3})?\s?([\S]*[\s]*?)(\{.*\})?$/', $responseFileTag->getContent(), $result);
            $status = $result[1] ?: 200;
            $content = $result[2] ? file_get_contents(storage_path(trim($result[2])), true) : '{}';
            $json = ! empty($result[3]) ? str_replace("'", '"', $result[3]) : '{}';
            $merged = array_merge(json_decode($content, true), json_decode($json, true));

            return [json_encode($merged), (int) $status];
        }, $responseFileTags);

        // Convert responses to [200 => 'response', 401 => 'response']
        return collect($responses)->pluck('0', '1')->toArray();
    }
}
