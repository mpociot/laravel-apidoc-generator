<?php

namespace Mpociot\ApiDoc\Generators;

use Faker\Factory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mpociot\ApiDoc\Parsers\RuleDescriptionParser as Description;
use ReflectionClass;
use Mpociot\Reflection\DocBlock;

abstract class AbstractGenerator
{
    /**
     * @param $route
     *
     * @return mixed
     */
    abstract protected function getUri($route);

    /**
     * @param  \Illuminate\Routing\Route $route
     * @param array $bindings
     * @param bool $withResponse
     *
     * @return array
     */
    abstract public function processRoute($route, $bindings = [], $withResponse = true);

    /**
     * @param array $routeData
     * @param array $routeAction
     *
     * @return mixed
     */
    protected function getParameters($routeData, $routeAction)
    {
        $validator = Validator::make([], $this->getRouteRules($routeAction['uses']));
        foreach ($validator->getRules() as $attribute => $rules) {
            $attributeData = [
                'required' => false,
                'type' => 'string',
                'default' => '',
                'value' => '',
                'description' => [],
            ];
            foreach ($rules as $rule) {
                $this->parseRule($rule, $attributeData);
            }
            $routeData['parameters'][$attribute] = $attributeData;
        }

        return $routeData;
    }

    /**
     * @param  $route
     *
     * @return \Illuminate\Http\Response
     */
    protected function getRouteResponse($route, $bindings)
    {
        $uri = $this->addRouteModelBindings($route, $bindings);

        $methods = $route->getMethods();

        return $this->callRoute(array_shift($methods), $uri);
    }

    /**
     * @param $route
     * @param array $bindings
     *
     * @return mixed
     */
    protected function addRouteModelBindings($route, $bindings)
    {
        $uri = $this->getUri($route);
        foreach ($bindings as $model => $id) {
            $uri = str_replace('{'.$model.'}', $id, $uri);
        }

        return $uri;
    }

    /**
     * @param  \Illuminate\Routing\Route  $route
     *
     * @return string
     */
    protected function getRouteDescription($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);
        $reflectionMethod = $reflection->getMethod($method);

        $comment = $reflectionMethod->getDocComment();
        $phpdoc = new DocBlock($comment);

        return [
            'short' => $phpdoc->getShortDescription(),
            'long' => $phpdoc->getLongDescription()->getContents(),
        ];
    }

    /**
     * @param  string  $route
     *
     * @return string
     */
    protected function getRouteGroup($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);
        $comment = $reflection->getDocComment();
        if ($comment) {
            $phpdoc = new DocBlock($comment);
            foreach ($phpdoc->getTags() as $tag) {
                if ($tag->getName() === 'resource') {
                    return $tag->getContent();
                }
            }
        }

        return 'general';
    }

    /**
     * @param  $route
     *
     * @return array
     */
    protected function getRouteRules($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);
        $reflectionMethod = $reflection->getMethod($method);

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameterType = $parameter->getClass();
            if (is_null($parameterType) && ! class_exists($parameterType->name)) {
                continue;
            }
            $className = $parameterType->name;
            $parameterReflection = new $className;
            if ($parameterReflection instanceof FormRequest && method_exists($parameterReflection, 'validator')) {
                return $parameterReflection->validator()->getRules();
            }
            
            return $parameterReflection->rules();
        }

        return [];
    }

    /**
     * @param  array  $arr
     * @param  string  $first
     * @param  string  $last
     *
     * @return string
     */
    protected function fancyImplode($arr, $first, $last)
    {
        $arr = array_map(function ($value) {
            return '`'.$value.'`';
        }, $arr);
        array_push($arr, implode($last, array_splice($arr, -2)));

        return implode($first, $arr);
    }

    /**
     * @param  string  $rule
     * @param  array  $attributeData
     *
     * @return void
     */
    protected function parseRule($rule, &$attributeData)
    {
        $faker = Factory::create();

        $parsedRule = $this->parseStringRule($rule);
        $parsedRule[0] = $this->normalizeRule($parsedRule[0]);
        list($rule, $parameters) = $parsedRule;

        switch ($rule) {
            case 'required':
                $attributeData['required'] = true;
                break;
            case 'accepted':
                $attributeData['required'] = true;
                $attributeData['type'] = 'boolean';
                $attributeData['value'] = true;
                break;
            case 'after':
                $attributeData['type'] = 'date';
                $attributeData['description'][] = Description::parse($rule)->with(date(DATE_RFC850, strtotime($parameters[0])))->getDescription();
                $attributeData['value'] = date(DATE_RFC850, strtotime('+1 day', strtotime($parameters[0])));
                break;
            case 'alpha':
                $attributeData['description'][] = Description::parse($rule)->getDescription();
                $attributeData['value'] = $faker->word;
                break;
            case 'alpha_dash':
                $attributeData['description'][] = Description::parse($rule)->getDescription();
                break;
            case 'alpha_num':
                $attributeData['description'][] = Description::parse($rule)->getDescription();
                break;
            case 'in':
                $attributeData['description'][] = Description::parse($rule)->with($this->fancyImplode($parameters, ', ', ' or '))->getDescription();
                $attributeData['value'] = $faker->randomElement($parameters);
                break;
            case 'not_in':
                $attributeData['description'][] = Description::parse($rule)->with($this->fancyImplode($parameters, ', ', ' or '))->getDescription();
                $attributeData['value'] = $faker->word;
                break;
            case 'min':
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'max':
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'between':
                $attributeData['type'] = 'numeric';
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                $attributeData['value'] = $faker->numberBetween($parameters[0], $parameters[1]);
                break;
            case 'before':
                $attributeData['type'] = 'date';
                $attributeData['description'][] = Description::parse($rule)->with(date(DATE_RFC850, strtotime($parameters[0])))->getDescription();
                $attributeData['value'] = date(DATE_RFC850, strtotime('-1 day', strtotime($parameters[0])));
                break;
            case 'date_format':
                $attributeData['type'] = 'date';
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'different':
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'digits':
                $attributeData['type'] = 'numeric';
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                $attributeData['value'] = $faker->randomNumber($parameters[0], true);
                break;
            case 'digits_between':
                $attributeData['type'] = 'numeric';
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'image':
                $attributeData['type'] = 'image';
                $attributeData['description'][] = Description::parse($rule)->getDescription();
                break;
            case 'json':
                $attributeData['type'] = 'string';
                $attributeData['description'][] = Description::parse($rule)->getDescription();
                $attributeData['value'] = json_encode(['foo', 'bar', 'baz']);
                break;
            case 'mimetypes':
            case 'mimes':
                $attributeData['description'][] = Description::parse($rule)->with($this->fancyImplode($parameters, ', ', ' or '))->getDescription();
                break;
            case 'required_if':
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'required_unless':
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'required_with':
                $attributeData['description'][] = Description::parse($rule)->with($this->fancyImplode($parameters, ', ', ' or '))->getDescription();
                break;
            case 'required_with_all':
                $attributeData['description'][] = Description::parse($rule)->with($this->fancyImplode($parameters, ', ', ' and '))->getDescription();
                break;
            case 'required_without':
                $attributeData['description'][] = Description::parse($rule)->with($this->fancyImplode($parameters, ', ', ' or '))->getDescription();
                break;
            case 'required_without_all':
                $attributeData['description'][] = Description::parse($rule)->with($this->fancyImplode($parameters, ', ', ' and '))->getDescription();
                break;
            case 'same':
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'size':
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'timezone':
                $attributeData['description'][] = Description::parse($rule)->getDescription();
                $attributeData['value'] = $faker->timezone;
                break;
            case 'exists':
                $attributeData['description'][] = Description::parse($rule)->with([Str::singular($parameters[0]), $parameters[1]])->getDescription();
                break;
            case 'active_url':
                $attributeData['type'] = 'url';
                $attributeData['value'] = $faker->url;
                break;
            case 'regex':
                $attributeData['type'] = 'string';
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'boolean':
                $attributeData['value'] = true;
                $attributeData['type'] = $rule;
                break;
            case 'array':
                $attributeData['value'] = $faker->word;
                $attributeData['type'] = $rule;
                break;
            case 'date':
                $attributeData['value'] = $faker->date();
                $attributeData['type'] = $rule;
                break;
            case 'email':
                $attributeData['value'] = $faker->safeEmail;
                $attributeData['type'] = $rule;
                break;
            case 'string':
                $attributeData['value'] = $faker->word;
                $attributeData['type'] = $rule;
                break;
            case 'integer':
                $attributeData['value'] = $faker->randomNumber();
                $attributeData['type'] = $rule;
                break;
            case 'numeric':
                $attributeData['value'] = $faker->randomNumber();
                $attributeData['type'] = $rule;
                break;
            case 'url':
                $attributeData['value'] = $faker->url;
                $attributeData['type'] = $rule;
                break;
            case 'ip':
                $attributeData['value'] = $faker->ipv4;
                $attributeData['type'] = $rule;
                break;
        }

        if ($attributeData['value'] === '') {
            $attributeData['value'] = $faker->word;
        }
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  string  $content
     *
     * @return \Illuminate\Http\Response
     */
    abstract public function callRoute($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null);

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     *
     * @param  array  $headers
     *
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        $server = [];
        $prefix = 'HTTP_';

        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');

            if (! Str::startsWith($name, $prefix) && $name !== 'CONTENT_TYPE') {
                $name = $prefix.$name;
            }

            $server[$name] = $value;
        }

        return $server;
    }

    /**
     * Parse a string based rule.
     *
     * @param  string  $rules
     *
     * @return array
     */
    protected function parseStringRule($rules)
    {
        $parameters = [];

        // The format for specifying validation rules and parameters follows an
        // easy {rule}:{parameters} formatting convention. For instance the
        // rule "Max:3" states that the value may only be three letters.
        if (strpos($rules, ':') !== false) {
            list($rules, $parameter) = explode(':', $rules, 2);

            $parameters = $this->parseParameters($rules, $parameter);
        }

        return [strtolower(trim($rules)), $parameters];
    }

    /**
     * Parse a parameter list.
     *
     * @param  string  $rule
     * @param  string  $parameter
     *
     * @return array
     */
    protected function parseParameters($rule, $parameter)
    {
        if (strtolower($rule) === 'regex') {
            return [$parameter];
        }

        return str_getcsv($parameter);
    }

    /**
     * Normalizes a rule so that we can accept short types.
     *
     * @param  string $rule
     *
     * @return string
     */
    protected function normalizeRule($rule)
    {
        switch ($rule) {
            case 'int':
                return 'integer';
            case 'bool':
                return 'boolean';
            default:
                return $rule;
        }
    }
}
