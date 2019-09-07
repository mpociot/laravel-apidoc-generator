# Extending functionality with plugins
You can use plugins to alter how the Generator fetches data about your routes. For instance, suppose all your routes have a body parameter `organizationId`, and you don't want to annotate this with `@queryParam` on each method. You can create a plugin that adds this to all your body parameters. Let's see how to do this.

## The stages of route processing
Route processing is performed in four stages:
- metadata (this covers route `title`, route `description`, route `groupName`, route `groupDescription`, and authentication status (`authenticated`))
- bodyParameters
- queryParameters
- responses

For each stage, the Generator attempts the specified strategies to fetch data. The Generator will call of the strategies configured, progressively combining their results together before to produce the final output of that stage.

There are a number of strategies inccluded with the package, so you don't have to set up anything to get it working.

> Note: The included ResponseCalls strategy is designed to stop if a response has already been gotten from any other strategy.

## Strategies
To create a strategy, create a class that extends `\Mpociot\ApiDoc\Strategies\Strategy`.

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
use Mpociot\ApiDoc\Strategies\Strategy;

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
            \Mpociot\ApiDoc\Strategies\Metadata\GetFromDocBlocks::class,
        ],
        'bodyParameters' => [
            \Mpociot\ApiDoc\Strategies\BodyParameters\GetFromBodyParamTag::class,
        ],
        'queryParameters' => [
            \Mpociot\ApiDoc\Strategies\QueryParameters\GetFromQueryParamTag::class,
        ],
        'responses' => [
            \Mpociot\ApiDoc\Strategies\Responses\UseResponseTag::class,
            \Mpociot\ApiDoc\Strategies\Responses\UseResponseFileTag::class,
            \Mpociot\ApiDoc\Strategies\Responses\UseTransformerTags::class,
            \Mpociot\ApiDoc\Strategies\Responses\ResponseCalls::class,
        ],
    ],
...
```

You can add, replace or remove strategies from here. In our case, we're adding our bodyParameter strategy:

```php

        'bodyParameters' => [
            \Mpociot\ApiDoc\Strategies\BodyParameters\GetFromBodyParamTag::class,
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

The strategy class also has access to the current apidoc configuration via its `config` property. For instance, you can retrieve the default group with `$this->config->get('default_group')`.

Yopu are also provided with the instance pproperty `stage`, which is set to the name of the currently executing stage.


## Utilities
You have access to a number of tools when developing strategies. They include:

- The `RouteDocBlocker` class (in the `\Mpociot\ApiDoc\Tools` namespace) has a single public static method, `getDocBlocksFromRoute(Route $route)`. It allows you to retrieve the docblocks for a given route. It returns an array of with two keys: `method` and `class` containing the docblocks for the method and controller handling the route respectively. Both are instances of `\Mpociot\Reflection\DocBlock`.

- The `ParamsHelper` trait (in the `\Mpociot\ApiDoc\Tools` namespace) can be included in your strategies. It contains a number of useful methods for working with parameters, including type casting and generating dummy values.

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
'type', // Only used in bodyParameters
'description', 
'required', // boolean
'value', // An example value for the parameter
```
- In the `responses` stage, your strategy should return an array containing the responses for different status codes. Each key in the array should be a HTTP status code, and each value should be a string containing the response.
