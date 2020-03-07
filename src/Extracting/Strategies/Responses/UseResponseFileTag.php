<?php

namespace Mpociot\ApiDoc\Extracting\Strategies\Responses;

use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Extracting\RouteDocBlocker;
use Mpociot\ApiDoc\Extracting\Strategies\Strategy;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;

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
     * @throws \Exception If the response file does not exist
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
            $relativeFilePath = trim($result[2]);
            $filePath = storage_path($relativeFilePath);
            if (! file_exists($filePath)) {
                throw new \Exception('@responseFile ' . $relativeFilePath . ' does not exist');
            }
            $status = $result[1] ?: 200;
            $content = $result[2] ? file_get_contents($filePath, true) : '{}';
            $json = ! empty($result[3]) ? str_replace("'", '"', $result[3]) : '{}';
            $merged = array_merge(json_decode($content, true), json_decode($json, true));

            return ['content' => json_encode($merged), 'status' => (int) $status];
        }, $responseFileTags);

        return $responses;
    }
}
