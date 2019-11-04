# Extending functionality with plugins
You can use plugins to alter how the Generator fetches data about your routes. For instance, suppose all your routes have a body parameter `organizationId`, and you don't want to annotate this with `@queryParam` on each method. You can create a plugin that adds this to all your body parameters. Let's see how to do this.

## The stages of route processing
Route processing is performed in six stages:
- metadata (this covers route `title`, route `description`, route `groupName`, route `groupDescription`, and authentication status (`authenticated`))
- urlParameters
- queryParameters
- headers (headers to be added to example request and response calls)
- bodyParameters
- responses

For each stage, the Generator attempts the specified strategies to fetch data. The Generator will call of the strategies configured, progressively combining their results together before to produce the final output of that stage.

There are a number of strategies included with the package, so you don't have to set up anything to get it working.

> Note: The included ResponseCalls strategy is designed to stop if a response with a 2xx status code has already been gotten via any other strategy.

## Strategies
To create a strategy, create a class that extends `\Mpociot\ApiDoc\Extracting\Strategies\Strategy`.

The `__invoke` method of the strategy is where you perform your actions and return data. It receives the following arguments:
- the route (instance of `\Illuminate\Routing\Route`)
- the controller class handling the route (`\ReflectionClass`)
- the controller method (`\ReflectionMethod $method`)
 - the rules specified in the apidoc.php config file for the group this route belongs to, under the `apply` section (array)
 - the context. This contains all data for the route that has been parsed thus far in the previous stages. This means, by the `responses` stage, the context will contain the following keys: `metadata`, `bodyParameters` and `queryParameters`.
 
 Here's what your strategy in our example would look like:
 
 ```php
<?php

use Illuminate\Routing\Route;
use Mpociot\ApiDoc\Extracting\Strategies\Strategy;

class AddOrganizationIdBodyParameter extends Strategy
{
    public function __invoke(Route $route, \ReflectionClass $controller, \ReflectionMethod $method, array $routeRules, array $context = [])
    {
        return [
            'organizationId' => [
                'type' => 'integer',
                'description' => 'The ID of the organization', 
                'required' => true, 
                'value' => 2,
            ]
        ];
    }
}
```

The last thing to do is to register the strategy. Strategies are registered in a `strategies` key in the `apidoc.php` file. Here's what the file looks like by default:

```php
...
    'strategies' => [
        'metadata' => [
            \Mpociot\ApiDoc\Extracting\Strategies\Metadata\GetFromDocBlocks::class,
        ],
        'urlParameters' => [
            \Mpociot\ApiDoc\Extracting\Strategies\UrlParameters\GetFromUrlParamTag::class,
        ],
        'queryParameters' => [
            \Mpociot\ApiDoc\Extracting\Strategies\QueryParameters\GetFromQueryParamTag::class,
        ],
        'headers' => [
            \Mpociot\ApiDoc\Extracting\Strategies\RequestHeaders\GetFromRouteRules::class,
        ],
        'bodyParameters' => [
            \Mpociot\ApiDoc\Extracting\Strategies\BodyParameters\GetFromBodyParamTag::class,
        ],
        'responses' => [
            \Mpociot\ApiDoc\Extracting\Strategies\Responses\UseTransformerTags::class,
            \Mpociot\ApiDoc\Extracting\Strategies\Responses\UseResponseTag::class,
            \Mpociot\ApiDoc\Extracting\Strategies\Responses\UseResponseFileTag::class,
            \Mpociot\ApiDoc\Extracting\Strategies\Responses\UseApiResourceTags::class,
            \Mpociot\ApiDoc\Extracting\Strategies\Responses\ResponseCalls::class,
        ],
    ],
...
```

You can add, replace or remove strategies from here. In our case, we're adding our bodyParameter strategy:

```php

        'bodyParameters' => [
            \Mpociot\ApiDoc\Extracting\Strategies\BodyParameters\GetFromBodyParamTag::class,
            AddOrganizationIdBodyParameter::class,
        ],
```

And we're done. Now, when we run `php artisan docs:generate`, all our routes will have this bodyParameter added.


We could go further and modify our strategy so it doesn't add this parameter if the route is a GET route or is authenticated:

```php
public function __invoke(Route $route, \ReflectionClass $controller, \ReflectionMethod $method, array $routeRules, array $context = [])
{
    if (in_array('GET', $route->methods()) {
        return null;
    }

    if ($context['metadata']['authenticated']) {
        return null;
    }

    return [
        'organizationId' => [
            'type' => 'integer',
            'description' => 'The ID of the organization', 
            'required' => true, 
            'value' => 2,
        ]
    ];
}
```

> Note: If you would like a parameter (body or query) to be included in the documentation but excluded from examples, set its `value` property to `null`.

The strategy class also has access to the current apidoc configuration via its `config` property. For instance, you can retrieve the default group with `$this->config->get('default_group')`.

You are also provided with the instance pproperty `stage`, which is set to the name of the currently executing stage.


## Utilities
You have access to a number of tools when developing strategies. They include:

- The `RouteDocBlocker` class (in the `\Mpociot\ApiDoc\Extracting` namespace) has a single public static method, `getDocBlocksFromRoute(Route $route)`. It allows you to retrieve the docblocks for a given route. It returns an array of with two keys: `method` and `class` containing the docblocks for the method and controller handling the route respectively. Both are instances of `\Mpociot\Reflection\DocBlock`.

- The `ParamsHelper` trait (in the `\Mpociot\ApiDoc\Extracting` namespace) can be included in your strategies. It contains a number of useful methods for working with parameters, including type casting and generating dummy values.

## API
Each strategy class must implement the __invoke method with the parameters as described above. This method must return the needed data for the intended stage, or `null` to indicate failure.
- In the `metadata` stage, strategies should return an array. These are the expected keys (you may omit some, or all):

```
'groupName'
'groupDescription'
'title'
'description'
'authenticated' // boolean
```

- In the `bodyParameters` and `queryParameters` stages, you can return an array with arbitrary keys. These keys will serve as the names of your parameters. Array keys can be indicated with Laravel's dot notation. The value of each key should be an array with the following keys:

```
'type', // Only valid in bodyParameters
'description', 
'required', // boolean
'value', // An example value for the parameter
```
- In the `responses` stage, your strategy should return an array containing the responses for different status codes. Each item in the array should be an array representing the response with a `status` key containing the HTTP status code, and a `content` key a string containing the response. For example:

```php

    public function __invoke(Route $route, \ReflectionClass $controller, \ReflectionMethod $method, array $routeRules, array $context = [])
    {
        return [
            [
                'content' => "Haha",
                'status' => 201
            ],
            [
                'content' => "Nope",
                'status' => 404
            ],
        ]
    }
```

Responses are _additive_. This means all the responses returned from each stage are added to the `responses` array. But note that the `ResponseCalls` strategy will only attempt to fetch a response if there are no responses with a status code of 2xx already.

- In the `headers` stage, you can return an array of headers. You may also negate existing headers by providing `false` as the header value.
