<?php

namespace Mpociot\ApiDoc\Extracting\Strategies\ResponseParameters;

use Mpociot\ApiDoc\Extracting\ParamHelpers;
use Mpociot\Reflection\DocBlock\Tag;

trait FromDocBlockHelper
{
    use ParamHelpers;

    private function getResponseParametersFromDocBlock($tags)
    {
        $parameters = collect($tags)
            ->filter(function ($tag) {
                return $tag instanceof Tag && $tag->getName() === 'responseParam';
            })
            ->mapWithKeys(function (Tag $tag) {
                // Format:
                // @responseParam <name> <type> <description>
                // Examples:
                // @responseParam user_id integer The ID of the user.
                preg_match('/(.+?)\s+(.+?)\s+(.*)/', $tag->getContent(), $content);
                $content = preg_replace('/\s?No-example.?/', '', $content);
                if (empty($content)) {
                    // this means only name and type were supplied
                    list($name, $type) = preg_split('/\s+/', $tag->getContent());
                } else {
                    list($_, $name, $type, $description) = $content;
                    $description = trim($description);
                }

                $type = $this->normalizeParameterType($type);
                list($description, $example) = $this->parseParamDescription($description, $type);
                $value = is_null($example) && ! $this->shouldExcludeExample($tag->getContent())
                    ? $this->generateDummyValue($type)
                    : $example;

                return [$name => compact('type', 'description', 'value', 'example')];
            })->toArray();

        return $parameters;
    }
}
