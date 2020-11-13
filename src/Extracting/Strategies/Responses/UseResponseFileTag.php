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
     * @return array|null
     * @throws \Exception If the response file does not exist
     *
     */
    public function __invoke(
        Route $route,
        \ReflectionClass $controller,
        \ReflectionMethod $method,
        array $routeRules,
        array $context = []
    ) {
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
            if (!file_exists($filePath)) {
                throw new \Exception('@responseFile ' . $relativeFilePath . ' does not exist');
            }
            $status = $result[1] ?: 200;
            $content = $result[2] ? file_get_contents($filePath, true) : '{}';
            $json = !empty($result[3]) ? str_replace("'", '"', $result[3]) : '{}';
            $merged = array_merge(json_decode($content, true), json_decode($json, true));
            $content = json_encode($merged);
            $contentWithReplacedTags = $this->replaceJsonFileTags($content);
            return ['content' => $contentWithReplacedTags, 'status' => (int)$status];
        }, $responseFileTags);

        return $responses;
    }

    /**
     * Replaces nested file tags @responseFile:path/to/file.json
     *
     * @param string $content
     * @return string
     */
    protected function replaceJsonFileTags(string $content): string
    {
        // finding all matching responseFile tags
        preg_match_all('/@responseFile:[\S]*[\s]*\.json?/', $content, $result);

        // continuing if we get any result
        if (count($result) > 0) {
            foreach ($result[0] as $replaceValuePath) {
                $relativeFilePath = str_replace('@responseFile:', '', $replaceValuePath);
                $relativeFilePath = str_replace('\\', '', $relativeFilePath);
                $filePath = storage_path($relativeFilePath);

                if (!file_exists($filePath)) {
                    throw new \Exception('@responseFile ' . $relativeFilePath . ' does not exist');
                }

                // fetching the file content and recursively replacing any matches within the file tagged
                $fileContent = file_get_contents($filePath, true);
                $normalizedFileContent = json_encode(json_decode($fileContent, true));
                $nestedReplacedFileContent = $this->replaceJsonFileTags($normalizedFileContent);
                $content = str_replace(
                    '"' . $replaceValuePath . '"',
                    $nestedReplacedFileContent,
                    $content
                );
            }
        }

        return $content;
    }
}
