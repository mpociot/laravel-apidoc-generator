<?php

namespace Mpociot\ApiDoc\Tools\Traits;

use Mpociot\Reflection\DocBlock\Tag;

trait DocBlockParamHelpers
{
    use ParamHelpers;

    /**
     * Allows users to specify that we shouldn't generate an example for the parameter
     * by writing 'No-example'.
     *
     * @param Tag $tag
     *
     * @return bool Whether no example should be generated
     */
    protected function shouldExcludeExample(Tag $tag)
    {
        return strpos($tag->getContent(), ' No-example') !== false;
    }

    /**
     * Allows users to specify an example for the parameter by writing 'Example: the-example',
     * to be used in example requests and response calls.
     *
     * @param string $description
     * @param string $type The type of the parameter. Used to cast the example provided, if any.
     *
     * @return array The description and included example.
     */
    protected function parseParamDescription(string $description, string $type)
    {
        $example = null;
        if (preg_match('/(.*)\s+Example:\s*(.+)\s*/', $description, $content)) {
            $description = $content[1];

            // examples are parsed as strings by default, we need to cast them properly
            $example = $this->castToType($content[2], $type);
        }

        return [$description, $example];
    }
}
