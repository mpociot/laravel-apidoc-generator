<?php

namespace Mpociot\ApiDoc\Parsers;

class RuleDescriptionParser
{
    private $rule;

    private $parameters = [];

    /**
     * @param null $rule
     */
    public function __construct($rule = null)
    {
        $this->rule = $rule;
    }

    /**
     * @return array|string
     */
    public function getDescription()
    {
        $key = "apidoc::rules.{$this->rule}";

        $description = $this->parameters ? $this->translateWithAttributes($key) : trans($key);

        return $description != $key ? $description : [];
    }

    /**
     * @param string|array $parameters
     * 
     * @return $this
     */
    public function with($parameters)
    {
        is_array($parameters) ? $this->parameters += $parameters : $this->parameters[] = $parameters;

        return $this;
    }

    /**
     * @param $key
     * 
     * @return string
     */
    protected function translateWithAttributes($key)
    {
        $translate = trans($key);

        foreach ($this->parameters as $parameter) {
            $translate = preg_replace('/:attribute/', $parameter, $translate, 1);
        }

        return $translate;
    }

    /**
     * @param null $rule
     * 
     * @return static
     */
    public static function parse($rule = null)
    {
        return new static($rule);
    }
}
