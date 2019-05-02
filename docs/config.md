# Configuration

Before you can generate your documentation, you'll need to configure a few things in your `config/apidoc.php`. If you aren't sure what an option does, it's best to leave it set to the default. If you don't have this config file, see the [installation instructions](index.html#installation).

## `output`
This is the file path where the generated documentation will be written to. Note that the documentation is generated as static HTML and CSS assets, so the route is accessed directly, and not via the Laravel routing mechanism. This path should be relative to the root of your application. Default: **public/docs**

## `router`
The router to use when processing your routes (can be Laravel or Dingo. Defaults to **Laravel**)

## `postman`
This package can automatically generate a Postman collection for your routes, along with the documentation. This section is where you can configure (or disable) that.

### `enabled`
Whether or not to generate a Postman API collection. Default: **true**

### `name`
The name for the exported Postman collection. If you leave this as null, this package will default to `config('app.name')." API"`.

### `description`
The description for the generated Postman collection.

## `logo`
You can specify a custom logo to be used on the generated documentation. Set the `logo` option to an absolute path pointing to your logo file. For example:
```
'logo' => resource_path('views') . '/api/logo.png'
```

If you want to use this, please note that the image size must be 230 x 52.

## `default_group`
When [documenting your api](documenting.md), you use `@group` annotations to group API endpoints. Endpoints which do not have a ggroup annotation will be grouped under the `default_group`. Defaults to **"general"**.

## `example_languages`
For each endpoint, an example request is shown in each of the languages specified in this array. Currently only `bash`, `javascript` and `php` are supported. You can add your own language, but you must also define the corresponding view (see [Specifying languages for examples](generating-documentation.html#specifying-language-for-examples)). Default: `["bash", "javascript"]` 
 
##  `faker_seed`
When generating example requests, this package uses fzanninoto/faker to generate random values. If you would like the package to generate the same example values for parameters on each run, set this to any number (eg. 1234). (Note: alternatively, you can set example values for parameters when [documenting them.](documenting.html#specifying-request-parameters))
       
## `fractal`
This section only applies if you're using [Transformers]() for your API, and documenting responses with `@transformer` and `@transformerCollection`. Here, you configure how responses are transformed.

> Note: using transformers requires league/fractal package. Run `composer require league/fractal to install

### serializer
If you are using a custom serializer with league/fractal,  you can specify it here. league/fractal comes with the following serializers:
- \League\Fractal\Serializer\ArraySerializer::class
- \League\Fractal\Serializer\DataArraySerializer::class
- \League\Fractal\Serializer\JsonApiSerializer::class

Leave this as null to use no serializer or return a simple JSON.

## `routes`
The `routes` section is an array of items, describing what routes in your application that should have documentation generated for them. Each item in the array contains rules about what routes belong in that group, and what rules to apply to them. This allows you to apply different settings to different routes.

> Note: This package does not work with Closure-based routes. If you want your route to be captured by this package, you need a controller.

Each item in the `routes` array (a route group) has keys which are explained below. We'll use this sample route definition for a Laravel app to demonstarte them:

```php
<?php

Route::group(['domain' => 'api.acme.co'], function () {
  Route::get('/apps', 'AppController@listApps')
    ->name('apps.list');
  Route::get('/apps/{id}', 'AppController@getApp')
    ->name('apps.get');
  Route::post('/apps', 'AppController@createApp')
    ->name('apps.create');
  Route::get('/users', 'UserController@listUsers')
    ->name('users.list');
  Route::get('/users/{id}', 'UserController@getUser')
    ->name('users.get');
});

Route::group(['domain' => 'public-api.acme.co'], function () {
  Route::get('/stats', 'PublicController@getStats')
    ->name('public.stats');
});

Route::group(['domain' => 'status.acme.co'], function () {
  Route::get('/status', 'PublicController@getStatus')
    ->name('status');
});
```

### `match`
In this section, you define the rules that will be used to determine what routes in your application fall into this group. There are three kinds of rules defined here (keys in the `match` array):

#### `domains`
This key takes an array of domain names as its value. Only routes which are defined on the domains specified here will be matched as part of this group. For instance, in our sample routes above, we may wish to apply different settings to documentation based on the domains. For instance, the routes on the `api.acme.co` domain need authentication, while those on the other domains do not. We can searate them into two groups like this:

```php
<?php
return [
  //...,
  
  'routes' => [
    [
      'match' => [
        'domains' => ['api.acme.co'],
        'prefixes' => ['*'],
      ],
      'apply' => [
        'headers' => [ 'Authorization' => 'Bearer {your-token}']
      ]
    ],
    [
      'match' => [
        'domains' => ['public-api.acme.co', 'status.acme.co'],
        'prefixes' => ['*'],
      ],
    ],
  ],
];
```
The first group will match all routes on the 'api.acme.co' domain, and add a header 'Authorization: Bearer {your-token}' to the examples in the generated documentation. The second group will pick up the other routes. The Authorization header will not be added for those ones.

You can use the `*` wildcard to match all domains (or as a placeholder in a pattern).

#### `prefixes`
The prefixes key is similar to the `domains` key, but is based on URL path prefixes (ie. what the part starts with, after the domain name). You could use prefixes to rewrite our example configuration above in a different way:

```php
<?php
return [
  //...,
  
  'routes' => [
    [
      'match' => [
         'domains' => ['*'],
         'prefixes' => ['users/*', 'apps/*'],
       ],
       'apply' => [
         'headers' => [ 'Authorization' => 'Bearer {your-token}']
       ]
    ],
    [
      'match' => [
         'domains' => ['*'],
         'prefixes' => ['stats/*', 'status/*'],
      ],
    ],
  ],
];
```

This would achieve the same as the first configuration. As with domains, the `*` character is a wildcard. This means you can set up a ingle group to match all your routes by using `'domains' => ['*'], 'prefixes' => ['*']`. (This is set by default.)

> The `domains` and `prefixes` keys are both required for all route groups.

#### `versions`
> This section only applies if you're using Dingo Router

When using Dingo's Router, all routes must be specified inside versions. This means that you must specify the versions to be matched along with the domains and prefixes when describing a route group. Note that wildcards in `versions` are not supported; you must list out all your versions explicitly. Example:

 ```php
<?php
return [
  //...,
  
  'routes' => [
    [
      'match' => [
        'domains' => ['*'],
        'prefixes' => ['*'],
        'versions' => ['v1', 'beta'], // only if you're using Dingo router
      ],
    ],
  ],
];
```

### `include` and `exclude`
The `include` key holds an array of route names which should be included in this group, *even if they do not match the rules in the `match` section*.
The `exclude` key holds an array of route names which should be excluded from this group, *even if they match the rules in the `match` section*.

> Remember that these two keys work with route *names*, not paths.

Using our above sample routes, asuming you wanted to place the `users.list` route in the second group (no Authorization header), here's how you could do it:

```php
<?php
return [
  //...,
  
  'routes' => [
    [
      'match' => [
        'domains' => ['api.acme.co'],
        'prefixes' => ['*'],
      ],
      'exclude' => ['users.list'],
      'apply' => [
        'headers' => [ 'Authorization' => 'Bearer {your-token}']
      ]
    ],
    [
      'match' => [
        'domains' => ['public-api.acme.co', 'status.acme.co'],
        'prefixes' => ['*'],
      ],
      'include' => ['users.list'],
    ],
  ],
];
```

### `apply`
After defining the routes in `match` (and `include` or `exclude`), `apply` is where you specify the settings to be applied to those routes when generating documentation. There are a bunch of settings you can tweak here:

#### `headers`
Like we've demonstrated above, any headers you specify here will be added to the headers shown in the example requests in your documenation. Headers are specified as key => value strings.

#### `response_calls`
These are the settings that will be applied when making ["response calls"](documenting.html#generating-responses-automatically). See the linked section for details.
