<?php

namespace Mpociot\ApiDoc;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlock;
use ReflectionClass;

class ApiDocGenerator
{

    /**
     * @param Route $route
     * @return array
     */
    public function processRoute(Route $route)
    {
        $routeAction = $route->getAction();
        $response = $this->getRouteResponse($route);
        $routeDescription = $this->getRouteDescription($routeAction['uses']);
        $routeData = [
            'title' => $routeDescription['short'],
            'description' => $routeDescription['long'],
            'methods' => $route->getMethods(),
            'uri' => $route->getUri(),
            'parameters' => [],
            'response' => ($response->headers->get('Content-Type') === 'application/json') ? json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT) : $response->getContent()
        ];

        $validator = Validator::make([], $this->getRouteRules($routeAction['uses']));
        foreach ($validator->getRules() as $attribute => $rules) {
            $attributeData = [
                'required' => false,
                'type' => 'string',
                'default' => '',
                'description' => []
            ];
            foreach ($rules as $rule) {
                $this->parseRule($rule, $attributeData);
            }
            $routeData['parameters'][$attribute] = $attributeData;
        }

        return $routeData;
    }

    /**
     * @param \Illuminate\Routing\Route $route
     * @return \Illuminate\Http\Response
     */
    private function getRouteResponse(Route $route)
    {
        $methods = $route->getMethods();
        $response = $this->callRoute(array_shift($methods), $route->getUri());
        return $response;
    }

    /**
     * @param $route
     * @return string
     */
    private function getRouteDescription($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);
        $reflectionMethod = $reflection->getMethod($method);

        $comment = $reflectionMethod->getDocComment();
        $phpdoc = new DocBlock($comment);
        return [
            'short' => $phpdoc->getShortDescription(),
            'long' => $phpdoc->getLongDescription()->getContents()
        ];
    }


    /**
     * @param $route
     * @return array
     */
    private function getRouteRules($route)
    {
        list($class, $method) = explode('@', $route);
        $reflection = new ReflectionClass($class);
        $reflectionMethod = $reflection->getMethod($method);

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameterType = $parameter->getType();
            if (!is_null($parameterType) && class_exists($parameterType)) {
                $className = $parameterType->__toString();
                $parameterReflection = new $className;
                if ($parameterReflection instanceof FormRequest) {
                    if (method_exists($parameterReflection, 'validator')) {
                        return $parameterReflection->validator()->getRules();
                    } else {
                        return $parameterReflection->rules();
                    }
                }
            }
        }

        return [];
    }

    /**
     * @param $rule
     * @param $attributeData
     */
    protected function parseRule($rule, &$attributeData)
    {
        $parsedRule = $this->parseStringRule($rule);
        $parsedRule[0] = $this->normalizeRule($parsedRule[0]);
        list($rule, $parameters) = $parsedRule;

        switch ($rule) {
            case 'required':
                $attributeData['required'] = true;
                break;
            case 'in':
                $attributeData['description'][] = implode(' or ', $parameters);
                break;
            case 'not_in':
                $attributeData['description'][] = 'Not in: ' . implode(' or ', $parameters);
                break;
            case 'min':
                $attributeData['description'][] = 'Minimum: `' . $parameters[0] . '`';
                break;
            case 'max':
                $attributeData['description'][] = 'Maximum: `' . $parameters[0] . '`';
                break;
            case 'between':
                $attributeData['description'][] = 'Between: `' . $parameters[0] . '` and ' . $parameters[1];
                break;
            case 'date_format':
                $attributeData['description'][] = 'Date format: ' . $parameters[0];
                break;
            case 'mimetypes':
            case 'mimes':
                $attributeData['description'][] = 'Allowed mime types: ' . implode(', ', $parameters);
                break;
            case 'required_if':
                $attributeData['description'][] = 'Required if `' . $parameters[0] . '` is `' . $parameters[1] . '`';
                break;
            case 'exists':
                $attributeData['description'][] = 'Valid ' . Str::singular($parameters[0]) . ' ' . $parameters[1];
                break;
            case 'active_url':
                $attributeData['type'] = 'url';
                break;
            case 'boolean':
            case 'email':
            case 'image':
            case 'string':
            case 'integer':
            case 'json':
            case 'numeric':
            case 'url':
            case 'ip':
                $attributeData['type'] = $rule;
                break;
        }
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string $method
     * @param  string $uri
     * @param  array $parameters
     * @param  array $cookies
     * @param  array $files
     * @param  array $server
     * @param  string $content
     * @return \Illuminate\Http\Response
     */
    public function callRoute($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $kernel = App::make('Illuminate\Contracts\Http\Kernel');
        App::instance('middleware.disable', true);

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ];

        $request = Request::create(
            $uri, $method, $parameters,
            $cookies, $files, $this->transformHeadersToServerVars($server), $content
        );

        $response = $kernel->handle($request);

        $kernel->terminate($request, $response);

        return $response;
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     *
     * @param  array $headers
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        $server = [];
        $prefix = 'HTTP_';

        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');

            if (!starts_with($name, $prefix) && $name != 'CONTENT_TYPE') {
                $name = $prefix . $name;
            }

            $server[$name] = $value;
        }

        return $server;
    }

    /**
     * Parse a string based rule.
     *
     * @param  string $rules
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
     * @param  string $rule
     * @param  string $parameter
     * @return array
     */
    protected function parseParameters($rule, $parameter)
    {
        if (strtolower($rule) == 'regex') {
            return [$parameter];
        }

        return str_getcsv($parameter);
    }

    /**
     * Normalizes a rule so that we can accept short types.
     *
     * @param  string $rule
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