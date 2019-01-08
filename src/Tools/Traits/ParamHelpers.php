<?php

namespace Mpociot\ApiDoc\Tools\Traits;

trait ParamHelpers
{
    /**
     * Create proper arrays from dot-noted parameter names.
     *
     * @param array $params
     *
     * @return array
     */
    protected function cleanParams(array $params)
    {
        $values = [];
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
        if (str_contains($name, '[')) {
            $name = str_replace(['][', '[', ']', '..'], ['.', '.', '', '.*.'], $name);
        }
        array_set($values, str_replace('.*', '.0', $name), $value);
    }
}
