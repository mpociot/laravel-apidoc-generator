<?php

namespace Mpociot\ApiDoc\Generators;

use Faker\Factory;
use ReflectionClass;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use League\Fractal\Manager;
use Illuminate\Routing\Route;
use Mpociot\Reflection\DocBlock;
use League\Fractal\Resource\Item;
use Mpociot\Reflection\DocBlock\Tag;
use League\Fractal\Resource\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Mpociot\ApiDoc\Parsers\RuleDescriptionParser as Description;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

abstract class AbstractGenerator
{
    /**
     * @param Route $route
     *
     * @return mixed
     */
    public function getDomain($route)
    {
        return $route->domain();
    }

    /**
     * @param Route $route
     *
     * @return mixed
     */
    public function getUri($route)
    {
        return $route->uri();
    }

    /**
     * @param Route $route
     *
     * @return mixed
     */
    public function getMethods($route)
    {
        return array_diff($route->methods(), ['HEAD']);
    }

    /**
     * @param  \Illuminate\Routing\Route $route
     * @param array $bindings
     * @param bool $withResponse
     *
     * @return array
     */
    public function processRoute($route, $bindings = [], $headers = [], $withResponse = true)
    {
        $routeDomain = $route->domain();
        $routeAction = $route->getAction();
        $routeGroup = $this->getRouteGroup($routeAction['uses']);
        $routeDescription = $this->getRouteDescription($routeAction['uses']);
        $showresponse = null;

        // set correct route domain
        $headers[] = "HTTP_HOST: {$routeDomain}";
        $headers[] = "SERVER_NAME: {$routeDomain}";

        $response = null;
        $docblockResponse = $this->getDocblockResponse($routeDescription['tags']);
        if ($docblockResponse) {
            // we have a response from the docblock ( @response )
            $response = $docblockResponse;
            $showresponse = true;
        }
        if (! $response) {
            $transformerResponse = $this->getTransformerResponse($routeDescription['tags']);
            if ($transformerResponse) {
                // we have a transformer response from the docblock ( @transformer || @transformercollection )
                $response = $transformerResponse;
                $showresponse = true;
            }
        }
        if (! $response && $withResponse) {
            try {
                $response = $this->getRouteResponse($route, $bindings, $headers);
            } catch (\Exception $e) {
                echo "Couldn't get response for route: ".implode(',', $this->getMethods($route)).$route->uri().']: '.$e->getMessage()."\n";
            }
        }

        $content = $this->getResponseContent($response);

        return $this->getParameters([
            'id' => md5($this->getUri($route).':'.implode($this->getMethods($route))),
            'resource' => $routeGroup,
            'title' => $routeDescription['short'],
            'description' => $routeDescription['long'],
            'methods' => $this->getMethods($route),
            'uri' => $this->getUri($route),
            'parameters' => [],
            'response' => $content,
            'showresponse' => $showresponse,
        ], $routeAction, $bindings);
    }

    /**
     * Prepares / Disables route middlewares.
     *
     * @param  bool $disable
     *
     * @return  void
     */
    abstract public function prepareMiddleware($enable = false);

    /**
     * Get the response from the docblock if available.
     *
     * @param array $tags
     *
     * @return mixed
     */
    protected function getDocblockResponse($tags)
    {
        $responseTags = array_filter($tags, function ($tag) {
            if (! ($tag instanceof Tag)) {
                return false;
            }

            return \strtolower($tag->getName()) == 'response';
        });
        if (empty($responseTags)) {
            return;
        }
        $responseTag = \array_first($responseTags);

        return \response(json_encode($responseTag->getContent()), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * @param array $routeData
     * @param array $routeAction
     * @param array $bindings
     *
     * @return mixed
     */
    protected function getParameters($routeData, $routeAction, $bindings)
    {
        $validationRules = $this->getRouteValidationRules($routeData['methods'], $routeAction['uses'], $bindings);
        $rules = $this->simplifyRules($validationRules);

        foreach ($rules as $attribute => $ruleset) {
            $attributeData = [
                'required' => false,
                'type' => null,
                'default' => '',
                'value' => '',
                'description' => [],
            ];
            foreach ($ruleset as $rule) {
                $this->parseRule($rule, $attribute, $attributeData, $routeData['id'], $routeData);
            }
            $routeData['parameters'][$attribute] = $attributeData;
        }

        return $routeData;
    }

    /**
     * Format the validation rules as a plain array.
     *
     * @param array $rules
     *
     * @return array
     */
    protected function simplifyRules($rules)
    {
        // this will split all string rules into arrays of strings
        $newRules = Validator::make([], $rules)->getRules();

        // Laravel will ignore the nested array rules unless the key referenced exists and is an array
        // So we'll create an empty array for each array attribute
        $values = collect($newRules)
            ->filter(function ($values) {
                return in_array('array', $values);
            })->map(function ($val, $key) {
                return [str_random()];
            })->all();

        // Now this will return the complete ruleset.
        // Nested array parameters will be present, with '*' replaced by '0'
        return Validator::make($values, $rules)->getRules();
    }

    /**
     * @param  $route
     * @param  $bindings
     * @param  $headers
     *
     * @return \Illuminate\Http\Response
     */
    protected function getRouteResponse($route, $bindings, $headers = [])
    {
        $uri = $this->addRouteModelBindings($route, $bindings);

        $methods = $this->getMethods($route);

        // Split headers into key - value pairs
        $headers = collect($headers)->map(function ($value) {
            $split = explode(':', $value); // explode to get key + values
            $key = array_shift($split); // extract the key and keep the values in the array
            $value = implode(':', $split); // implode values into string again

            return [trim($key) => trim($value)];
        })->collapse()->toArray();

        //Changes url with parameters like /users/{user} to /users/1
        $uri = preg_replace('/{(.*?)}/', 1, $uri); // 1 is the default value for route parameters

        return $this->callRoute(array_shift($methods), $uri, [], [], [], $headers);
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
            $uri = str_replace('{'.$model.'?}', $id, $uri);
        }

        return $uri;
    }

    /**
     * @param  \Illuminate\Routing\Route  $route
     *
     * @return array
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
            'tags' => $phpdoc->getTags(),
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
     * @param  array $routeMethods
     * @param  string $routeAction
     * @param  array $bindings
     *
     * @return array
     */
    protected function getRouteValidationRules(array $routeMethods, $routeAction, $bindings)
    {
        list($controller, $method) = explode('@', $routeAction);
        $reflection = new ReflectionClass($controller);
        $reflectionMethod = $reflection->getMethod($method);

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameterType = $parameter->getClass();
            if (! is_null($parameterType) && class_exists($parameterType->name)) {
                $className = $parameterType->name;

                if (is_subclass_of($className, FormRequest::class)) {
                    /** @var FormRequest $formRequest */
                    $formRequest = new $className;
                    // Add route parameter bindings
                    $formRequest->setContainer(app());
                    $formRequest->request->add($bindings);
                    $formRequest->query->add($bindings);
                    $formRequest->setMethod($routeMethods[0]);

                    if (method_exists($formRequest, 'validator')) {
                        $factory = app(ValidationFactory::class);

                        return call_user_func_array([$formRequest, 'validator'], [$factory])
                            ->getRules();
                    } else {
                        return call_user_func_array([$formRequest, 'rules'], []);
                    }
                }
            }
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

    protected function splitValuePairs($parameters, $first = 'is ', $last = 'or ')
    {
        $attribute = '';
        collect($parameters)->map(function ($item, $key) use (&$attribute, $first, $last) {
            $attribute .= '`'.$item.'` ';
            if (($key + 1) % 2 === 0) {
                $attribute .= $last;
            } else {
                $attribute .= $first;
            }
        });
        $attribute = rtrim($attribute, $last);

        return $attribute;
    }

    /**
     * @param  string  $rule
     * @param  string  $attribute
     * @param  array  $attributeData
     * @param  int  $seed
     *
     * @return void
     */
    protected function parseRule($rule, $attribute, &$attributeData, $seed, $routeData)
    {
        $faker = Factory::create();
        $faker->seed(crc32($seed));

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
                $format = isset($attributeData['format']) ? $attributeData['format'] : DATE_RFC850;

                if (strtotime($parameters[0]) === false) {
                    // the `after` date refers to another parameter in the request
                    $paramName = $parameters[0];
                    $attributeData['description'][] = Description::parse($rule)->with($paramName)->getDescription();
                    $attributeData['value'] = date($format, strtotime('+1 day', strtotime($routeData['parameters'][$paramName]['value'])));
                } else {
                    $attributeData['description'][] = Description::parse($rule)->with(date($format, strtotime($parameters[0])))->getDescription();
                    $attributeData['value'] = date($format, strtotime('+1 day', strtotime($parameters[0])));
                }
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
                if (Arr::get($attributeData, 'type') === 'numeric' || Arr::get($attributeData, 'type') === 'integer') {
                    $attributeData['value'] = $faker->numberBetween($parameters[0]);
                }
                break;
            case 'max':
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                if (Arr::get($attributeData, 'type') === 'numeric' || Arr::get($attributeData, 'type') === 'integer') {
                    $attributeData['value'] = $faker->numberBetween(0, $parameters[0]);
                }
                break;
            case 'between':
                if (! isset($attributeData['type'])) {
                    $attributeData['type'] = 'numeric';
                }
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                $attributeData['value'] = $faker->numberBetween($parameters[0], $parameters[1]);
                break;
            case 'before':
                $attributeData['type'] = 'date';
                $format = isset($attributeData['format']) ? $attributeData['format'] : DATE_RFC850;

                if (strtotime($parameters[0]) === false) {
                    // the `before` date refers to another parameter in the request
                    $paramName = $parameters[0];
                    $attributeData['description'][] = Description::parse($rule)->with($paramName)->getDescription();
                    $attributeData['value'] = date($format, strtotime('-1 day', strtotime($routeData['parameters'][$paramName]['value'])));
                } else {
                    $attributeData['description'][] = Description::parse($rule)->with(date($format, strtotime($parameters[0])))->getDescription();
                    $attributeData['value'] = date($format, strtotime('-1 day', strtotime($parameters[0])));
                }
                break;
            case 'date_format':
                $attributeData['type'] = 'date';
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                $attributeData['format'] = $parameters[0];
                $attributeData['value'] = date($attributeData['format']);
                break;
            case 'different':
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'digits':
                $attributeData['type'] = 'numeric';
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                $attributeData['value'] = ($parameters[0] < 9) ? $faker->randomNumber($parameters[0], true) : substr(mt_rand(100000000, mt_getrandmax()), 0, $parameters[0]);
                break;
            case 'digits_between':
                $attributeData['type'] = 'numeric';
                $attributeData['description'][] = Description::parse($rule)->with($parameters)->getDescription();
                break;
            case 'file':
                $attributeData['type'] = 'file';
                $attributeData['description'][] = Description::parse($rule)->getDescription();
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
                $attributeData['description'][] = Description::parse($rule)->with($this->splitValuePairs($parameters))->getDescription();
                break;
            case 'required_unless':
                $attributeData['description'][] = Description::parse($rule)->with($this->splitValuePairs($parameters))->getDescription();
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
                $fieldName = isset($parameters[1]) ? $parameters[1] : $attribute;
                $attributeData['description'][] = Description::parse($rule)->with([Str::singular($parameters[0]), $fieldName])->getDescription();
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
                $attributeData['description'][] = Description::parse($rule)->getDescription();
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
            default:
                $unknownRuleDescription = Description::parse($rule)->getDescription();
                if ($unknownRuleDescription) {
                    $attributeData['description'][] = $unknownRuleDescription;
                }
                break;
        }

        if ($attributeData['value'] === '') {
            $attributeData['value'] = $faker->word;
        }

        if (is_null($attributeData['type'])) {
            $attributeData['type'] = 'string';
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
        // rule "max:3" states that the value may only be three letters.
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

    /**
     * @param $response
     *
     * @return mixed
     */
    private function getResponseContent($response)
    {
        if (empty($response)) {
            return '';
        }
        if ($response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), JSON_PRETTY_PRINT);
        } else {
            $content = $response->getContent();
        }

        return $content;
    }

    /**
     * Get a response from the transformer tags.
     *
     * @param array $tags
     *
     * @return mixed
     */
    protected function getTransformerResponse($tags)
    {
        try {
            $transFormerTags = array_filter($tags, function ($tag) {
                if (! ($tag instanceof Tag)) {
                    return false;
                }

                return \in_array(\strtolower($tag->getName()), ['transformer', 'transformercollection']);
            });
            if (empty($transFormerTags)) {
                // we didn't have any of the tags so goodbye
                return false;
            }

            $modelTag = array_first(array_filter($tags, function ($tag) {
                if (! ($tag instanceof Tag)) {
                    return false;
                }

                return \in_array(\strtolower($tag->getName()), ['transformermodel']);
            }));
            $tag = \array_first($transFormerTags);
            $transformer = $tag->getContent();
            if (! \class_exists($transformer)) {
                // if we can't find the transformer we can't generate a response
                return;
            }
            $demoData = [];

            $reflection = new ReflectionClass($transformer);
            $method = $reflection->getMethod('transform');
            $parameter = \array_first($method->getParameters());
            $type = null;
            if ($modelTag) {
                $type = $modelTag->getContent();
            }
            if (version_compare(PHP_VERSION, '7.0.0') >= 0 && \is_null($type)) {
                // we can only get the type with reflection for PHP 7
                if ($parameter->hasType() &&
                    ! $parameter->getType()->isBuiltin() &&
                    \class_exists((string) $parameter->getType())) {
                    //we have a type
                    $type = (string) $parameter->getType();
                }
            }
            if ($type) {
                // we have a class so we try to create an instance
                $demoData = new $type;
                try {
                    // try a factory
                    $demoData = \factory($type)->make();
                } catch (\Exception $e) {
                    if ($demoData instanceof \Illuminate\Database\Eloquent\Model) {
                        // we can't use a factory but can try to get one from the database
                        try {
                            // check if we can find one
                            $newDemoData = $type::first();
                            if ($newDemoData) {
                                $demoData = $newDemoData;
                            }
                        } catch (\Exception $e) {
                            // do nothing
                        }
                    }
                }
            }

            $fractal = new Manager();
            $resource = [];
            if ($tag->getName() == 'transformer') {
                // just one
                $resource = new Item($demoData, new $transformer);
            }
            if ($tag->getName() == 'transformercollection') {
                // a collection
                $resource = new Collection([$demoData, $demoData], new $transformer);
            }

            return \response($fractal->createData($resource)->toJson());
        } catch (\Exception $e) {
            // it isn't possible to parse the transformer
            return;
        }
    }
}
