<?php

namespace Mpociot\ApiDoc\Tools\Traits;

use Faker\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Mpociot\Reflection\DocBlock\Tag;

trait ParamHelpers
{
    /**
     * Create proper arrays from dot-noted parameter names. Also filter out parameters which were excluded from having examples.
     *
     * @param array $params
     *
     * @return array
     */
    protected function cleanParams(array $params)
    {
        $values = [];
        $params = array_filter($params, function ($details) {
            return ! is_null($details['value']);
        });

        foreach ($params as $name => $details) {
            $this->cleanValueFrom($name, $details['value'], $values);
        }

        return $values;
    }

    /**
     * Converts dot notation names to arrays and sets the value at the right depth.
     *
     * @param string $name
     * @param mixed $value
     * @param array $values The array that holds the result
     *
     * @return void
     */
    protected function cleanValueFrom($name, $value, array &$values = [])
    {
        if (Str::contains($name, '[')) {
            $name = str_replace(['][', '[', ']', '..'], ['.', '.', '', '.*.'], $name);
        }
        Arr::set($values, str_replace('.*', '.0', $name), $value);
    }

    /**
     * Allows users to specify that we shouldn't generate an example for the parameter
     * by writing 'No-example'.
     *
     * @param Tag $tag
     *
     * @return bool Whether no example should be generated
     */
    private function shouldExcludeExample(Tag $tag)
    {
        return strpos($tag->getContent(), ' No-example') !== false;
    }

    private function generateDummyValue(string $type)
    {
        $faker = Factory::create();
        if ($this->config->get('faker_seed')) {
            $faker->seed($this->config->get('faker_seed'));
        }
        $fakeFactories = [
            'integer' => function () use ($faker) {
                return $faker->numberBetween(1, 20);
            },
            'number' => function () use ($faker) {
                return $faker->randomFloat();
            },
            'float' => function () use ($faker) {
                return $faker->randomFloat();
            },
            'boolean' => function () use ($faker) {
                return $faker->boolean();
            },
            'string' => function () use ($faker) {
                return $faker->word;
            },
            'array' => function () {
                return [];
            },
            'object' => function () {
                return new \stdClass;
            },
        ];

        $fakeFactory = $fakeFactories[$type] ?? $fakeFactories['string'];

        return $fakeFactory();
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
    private function parseParamDescription(string $description, string $type)
    {
        $example = null;
        if (preg_match('/(.*)\s+Example:\s*(.*)\s*/', $description, $content)) {
            $description = $content[1];

            // examples are parsed as strings by default, we need to cast them properly
            $example = $this->castToType($content[2], $type);
        }

        return [$description, $example];
    }

    /**
     * Cast a value from a string to a specified type.
     *
     * @param string $value
     * @param string $type
     *
     * @return mixed
     */
    private function castToType(string $value, string $type)
    {
        $casts = [
            'integer' => 'intval',
            'number' => 'floatval',
            'float' => 'floatval',
            'boolean' => 'boolval',
        ];

        // First, we handle booleans. We can't use a regular cast,
        //because PHP considers string 'false' as true.
        if ($value == 'false' && $type == 'boolean') {
            return false;
        }

        if (isset($casts[$type])) {
            return $casts[$type]($value);
        }

        return $value;
    }

    private function normalizeParameterType(string $type)
    {
        $typeMap = [
            'int' => 'integer',
            'bool' => 'boolean',
            'double' => 'float',
        ];

        return $type ? ($typeMap[$type] ?? $type) : 'string';
    }
}
