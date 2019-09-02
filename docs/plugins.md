# Extending functionality with plugins
You can use plugins to alter how the Generator fetches data about your routes. For instance, suppose all your routes have a body parameter `organizationId`, and you don't want to annotate this with `@queryParam` on each method. You can create a plugin that adds this to all your body parameters. Let's see how to do this.

## The stages of route processing
Route processing is performed in four stages:
- metadata (this covers route title, route description, route group name, route group description, and authentication status)
- bodyParameters
- queryParameters
- responses

For each stage, the Generator attempts one or more configured strategies to fetch data. The Generator will call of the strategies configured, progressively combining their results together before to produce the final output of that stage.

## Strategies
To create a strategy, create a class that extends `\Mpociot\ApiDoc\Strategies\Strategy`.

The `__invoke` method of the strategy is where you perform your actions and return data. It receives the following arguments:
- the route (instance of `\Illuminate\Routing\Route`)
- the controller class handling the route (`\ReflectionClass`)
- the controller method (`\ReflectionMethod $method`)
 - the rules specified in the apidoc.php config file for the group this route belongs to, under the `apply` section (array)
 - the context. This contains all data for the route that has been parsed thus far in the previous stages.
 
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
            \Mpociot\ApiDoc\Strategies\BodyParameters\GetFromDocBlocks::class,
        ],
        'queryParameters' => [
            \Mpociot\ApiDoc\Strategies\QueryParameters\GetFromDocBlocks::class,
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
            \Mpociot\ApiDoc\Strategies\BodyParameters\GetFromDocBlocks::class,
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

The strategy class also has access to the current apidoc configuration via its config property. For instance, you can retrieve the deafult group with `$this->config->get('default_group')`.
