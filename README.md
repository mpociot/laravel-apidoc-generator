## Laravel API Documentation Generator

Automatically generate your API documentation from your existing Laravel/Lumen/[Dingo](https://github.com/dingo/api) routes. [Here's what the output looks like](http://marcelpociot.de/whiteboard/).

`php artisan apidoc:generate`

[![Latest Stable Version](https://poser.pugx.org/mpociot/laravel-apidoc-generator/v/stable)](https://packagist.org/packages/mpociot/laravel-apidoc-generator)[![Total Downloads](https://poser.pugx.org/mpociot/laravel-apidoc-generator/downloads)](https://packagist.org/packages/mpociot/laravel-apidoc-generator)
[![License](https://poser.pugx.org/mpociot/laravel-apidoc-generator/license)](https://packagist.org/packages/mpociot/laravel-apidoc-generator)
[![codecov.io](https://codecov.io/github/mpociot/laravel-apidoc-generator/coverage.svg?branch=master)](https://codecov.io/github/mpociot/laravel-apidoc-generator?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mpociot/laravel-apidoc-generator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mpociot/laravel-apidoc-generator/?branch=master)
[![Build Status](https://travis-ci.org/mpociot/laravel-apidoc-generator.svg?branch=master)](https://travis-ci.org/mpociot/laravel-apidoc-generator)
[![StyleCI](https://styleci.io/repos/57999295/shield?style=flat)](https://styleci.io/repos/57999295)

> Note: this is the documentation for version 3, which changes significantly from version 2. if you're on v2, you can check out its documentation [here](https://github.com/mpociot/laravel-apidoc-generator/blob/2.x/README.md). We strongly recommend you upgrade, though, as v3 is more robust and fixes a lot of the problems with v2.

## Installation
> Note: PHP 7 and Laravel 5.5 or higher are required.

```sh
$ composer require mpociot/laravel-apidoc-generator
```

### Laravel
Publish the config file by running:

```bash
php artisan vendor:publish --provider="Mpociot\ApiDoc\ApiDocGeneratorServiceProvider" --tag=config
```
This will create an `apidoc.php` file in your `config` folder.

### Lumen
- Register the service provider in your `bootstrap/app.php`:
```php
$app->register(\Mpociot\ApiDoc\ApiDocGeneratorServiceProvider::class);
```
- Copy the config file from `vendor/mpociot/laravel-apidoc-generator/config/apidoc.php` to your project as `config/apidoc.php`. Then add to your `bootstrap/app.php`:
```php
$app->configure('apidoc');
```


## Usage
Before you can generate your documentation, you'll need to configure a few things in your `config/apidoc.php`.
- `output`
This is the file path where the generated documentation will be written to. Default: **public/docs**

- `postman`
Set this option to true if you want a Postman collection to be generated along with the documentation. Default: **true**

- `router`
The router to use when processing the route (can be Laravel or Dingo. Defaults to **Laravel**)

- `logo`
You can specify your custom logo to be used on the generated documentation. Set the `logo` option to an absolute path pointing to your logo file.

- `routes`
This is where you specify what rules documentation should be generated for. You specify routes to be parsed by defining conditions that the routes should meet and rules that should be applied when generating documentation. These conditions and rules are specified in groups, allowing you to apply different rules to different routes.

For instance, suppose your configuration looks like this:

```php
return [
     //...,
  
     'routes' => [
          [
              'match' => [
                  'domains' => ['*'],
                  'prefixes' => ['api/*', 'v2-api/*'],
                  'versions' => ['v1'],
              ],
              'include' => ['users.index', 'healthcheck*'],
              'exclude' => ['users.create', 'admin.*'],
              'apply' => [
                  'headers' => [
                      'Authorization' => 'Bearer: {token}',
                  ],
              ],
          ],
];
```

This means documentation will be generated for routes in all domains ('&ast;' is a wildcard meaning 'any character') which match any of the patterns 'api/&ast;' or 'v2-api/&ast;', excluding the 'users.create' route and any routes whose names begin with `admin.`, and including the 'users.index' route and any routes whose names begin with `healthcheck.`. (The `versions` key is ignored unless you are using Dingo router).
Also, in the generated documentation, these routes will have the header 'Authorization: Bearer: {token}' added to the example requests.

You can also separate routes into groups to apply different rules to them:

```php
<?php
return [
     //...,
  
     'routes' => [
          [
              'match' => [
                  'domains' => ['v1.*'],
                  'prefixes' => ['*'],
              ],
              'include' => [],
              'exclude' => [],
              'apply' => [
                  'headers' => [
                      'Token' => '{token}',
                      'Version' => 'v1',
                  ],
              ],
          ],
          [
              'match' => [
                  'domains' => ['v2.*'],
                  'prefixes' => ['*'],
              ],
              'include' => [],
              'exclude' => [],
              'apply' => [
                  'headers' => [
                      'Authorization' => 'Bearer: {token}',
                      'Api-Version' => 'v2',
                  ],
              ],
          ],
];
```

With the configuration above, routes on the `v1.*` domain will have the `Token` and `Version` headers applied, while routes on the `v2.*` domain will have the `Authorization` and `Api-Version` headers applied.

> Note: the `include` and `exclude` items are arrays of route names. THe &ast; wildcard is supported.
> Note: If you're using DIngo router, the `versions` parameter is required in each route group. This parameter does not support wildcards. Each version must be listed explicitly,

To generate your API documentation, use the `apidoc:generate` artisan command.

```sh
$ php artisan apidoc:generate

```

It will generate documentation using your specified configuration.

## Documenting your API

This package uses these resources to generate the API documentation:

### Grouping endpoints

This package uses the HTTP controller doc blocks to create a table of contents and show descriptions for your API methods.

Using `@group` in a controller doc block creates a Group within the API documentation. All routes handled by that controller will be grouped under this group in the sidebar. The short description after the `@group` should be unique to allow anchor tags to navigate to this section. A longer description can be included below. Custom formatting and `<aside>` tags are also supported. (see the [Documentarian docs](http://marcelpociot.de/documentarian/installation/markdown_syntax))

 > Note: using `@group` is optional. Ungrouped routes will be placed in a "general" group.

Above each method within the controller you wish to include in your API documentation you should have a doc block. This should include a unique short description as the first entry. An optional second entry can be added with further information. Both descriptions will appear in the API documentation in a different format as shown below.
You can also specify an `@group` on a single method to override the group defined at the controller level.

```php
/**
 * @group User management
 *
 * APIs for managing users
 */
class UserController extends Controller
{

	/**
	 * Create a user
	 *
	 * [Insert optional longer description of the API endpoint here.]
	 *
	 */
	 public function createUser()
	 {

	 }
	 
	/**
	 * @group Account management
	 *
	 */
	 public function changePassword()
	 {

	 }
}
```

**Result:** 

![Doc block result](http://headsquaredsoftware.co.uk/images/api_generator_docblock.png)

### Specifying request parameters

To specify a list of valid parameters your API route accepts, use the `@bodyParam` and `@queryParam` annotations.
- The `@bodyParam` annotation takes the name of the parameter, its type, an optional "required" label, and then its description. 
- The `@queryParam` annotation takes the name of the parameter, an optional "required" label, and then its description


```php
/**
 * @bodyParam title string required The title of the post.
 * @bodyParam body string required The title of the post.
 * @bodyParam type string The type of post to create. Defaults to 'textophonious'.
 * @bodyParam author_id int the ID of the author
 * @bodyParam thumbnail image This is required if the post type is 'imagelicious'.
 */
public function createPost()
{
    // ...
}

/**
 * @queryParam sort Field to sort by
 * @queryParam page The page number to return
 * @queryParam fields required The fields to include
 */
public function listPosts()
{
    // ...
}
```

They will be included in the generated documentation text and example requests.

**Result:**

![](body_params.png)

Note: a random value will be used as the value of each parameter in the example requests. If you'd like to specify an example value, you can do so by adding `Example: your-example` to the end of your description. For instance:

```php
    /**
     * @queryParam location_id required The id of the location.
     * @queryParam user_id required The id of the user. Example: me
     * @queryParam page required The page number. Example: 4
     * @bodyParam user_id int required The id of the user. Example: 9
     * @bodyParam room_id string The id of the room.
     * @bodyParam forever boolean Whether to ban the user forever. Example: false
     */
```

### Indicating auth status
You can use the `@authenticated` annotation on a method to indicate if the endpoint is authenticated. A "Requires authentication" badge will be added to that route in the generated documentation.

### Providing an example response
You can provide an example response for a route. This will be displayed in the examples section. There are several ways of doing this.

#### @response
You can provide an example response for a route by using the `@response` annotation with valid JSON:

```php
/**
 * @response {
 *  "id": 4,
 *  "name": "Jessica Jones",
 *  "roles": ["admin"]
 * }
 */
public function show($id)
{
    return User::find($id);
}
```

Moreover, you can define multiple `@response` tags as well as the HTTP status code related to a particular response (if no status code set, `200` will be returned):
```php
/**
 * @response {
 *  "id": 4,
 *  "name": "Jessica Jones",
 *  "roles": ["admin"]
 * }
 * @response 404 {
 *  "message": "No query results for model [\App\User]"
 * }
 */
public function show($id)
{
    return User::findOrFail($id);
}
```

#### @transformer, @transformerCollection, and @transformerModel
You can define the transformer that is used for the result of the route using the `@transformer` tag (or `@transformerCollection` if the route returns a list). The package will attempt to generate an instance of the model to be transformed using the following steps, stopping at the first successful one:

1. Check if there is a `@transformerModel` tag to define the model being transformed. If there is none, use the class of the first parameter to the transformer's `transform()` method.
2. Get an instance of the model from the Eloquent model factory
2. If the parameter is an Eloquent model, load the first from the database.
3. Create an instance using `new`.

Finally, it will pass in the model to the transformer and display the result of that as the example response.

For example:

```php
/**
 * @transformer \App\Transformers\UserTransformer
 * @transformerModel \App\User
 */
public function listUsers()
{
    //...
}

/**
 * @transformer \App\Transformers\UserTransformer
 */
public function showUser(User $user)
{
    //...
}

/**
 * @transformer \App\Transformers\UserTransformer
 * @transformerModel \App\User
 */
public function showUser(int $id)
{
    // ...
}
```
For the first route above, this package will generate a set of two users then pass it through the transformer. For the last two, it will generate a single user and then pass it through the transformer.

> Note: for transformer support, you need to install the league/fractal package

```bash
composer require league/fractal
```

#### @responseFile

For large response bodies, you may want to use a dump of an actual response. You can put this response in a file (as a JSON string) within your Laravel storage directory and link to it. For instance, we can put this response in a file named `users.get.json` in `storage/responses`:

```
{"id":5,"name":"Jessica Jones","gender":"female"}
```

Then in your controller, link to it by:

```php
/**
 * @responseFile responses/users.get.json
 */
public function getUser(int $id)
{
  // ...
}
```
The package will parse this response and display in the examples for this route.

Similarly to `@response` tag, you can provide multiple `@responseFile` tags along with the HTTP status code of the response:
```php
/**
 * @responseFile responses/users.get.json
 * @responseFile 404 responses/model.not.found.json
 */
public function getUser(int $id)
{
  // ...
}
```

#### Generating responses automatically
If you don't specify an example response using any of the above means, this package will attempt to get a sample response by making a request to the route (a "response call"). A few things to note about response calls:
- They are done within a database transaction and changes are rolled back afterwards.
- The configuration for response calls is located in the `config/apidoc.php`. They are configured within the `['apply']['response_calls']` section for each route group, allowing you to apply different settings for different sets of routes.
- By default, response calls are only made for GET routes, but you can configure this. Set the `methods` key to an array of methods or '*' to mean all methods. Leave it as an empty array to turn off response calls for that route group.
- Parameters in URLs (example: `/users/{user}`, `/orders/{id?}`) will be replaced with '1' by default. You can configure this, however. Put the parameter names (including curly braces and question marks) as the keys and their replacements as the values in the `bindings` key.
- You can configure environment variables (this is useful so you can prevent external services like notifications from being triggered). By default the APP_ENV is set to 'documentation'. You can add more variables in the `env` key.
- By default, the package will generate dummy values for your documented body and query parameters and send in the request. (If you specified example values using `@bodyParam` or `@queryParam`, those will be used instead.) You can configure what headers and additional query and parameters should be sent when making the request (the `headers`, `query`, and `body` keys respectively).


### Postman collections

The generator automatically creates a Postman collection file, which you can import to use within your [Postman app](https://www.getpostman.com/apps) for even simpler API testing and usage.

If you don't want to create a Postman collection, set the `postman` config option to false.

The default base URL added to the Postman collection will be that found in your Laravel `config/app.php` file. This will likely be `http://localhost`. If you wish to change this setting you can directly update the url or link this config value to your environment file to make it more flexible (as shown below):

```php
'url' => env('APP_URL', 'http://yourappdefault.app'),
```

If you are referring to the environment setting as shown above, then you should ensure that you have updated your `.env` file to set the APP_URL value as appropriate. Otherwise the default value (`http://yourappdefault.app`) will be used in your Postman collection. Example environment value:

```
APP_URL=http://yourapp.app
```

## Modifying the generated documentation

If you want to modify the content of your generated documentation, go ahead and edit the generated `index.md` file.
The default location of this file is: `public/docs/source/index.md`.
 
After editing the markdown file, use the `apidoc:rebuild` command to rebuild your documentation as a static HTML file.

```sh
$ php artisan apidoc:rebuild
```

As an optional parameter, you can use `--location` to tell the update command where your documentation can be found.

If you wish to regenerate your documentation, you can run the `generate` command, you can use the `force` option to force the re-generation of existing/modified API routes.

## Automatically add markdown to the beginning or end of the documentation
 If you wish to automatically add the same content to the docs every time you generate, you can add a `prepend.md` and/or `append.md` file to the source folder, and they will be included above and below the generated documentation.
 
 **File locations:**
- `public/docs/source/prepend.md` - Will be added after the front matter and info text
- `public/docs/source/append.md` - Will be added at the end of the document.

## Further modification

This package uses [Documentarian](https://github.com/mpociot/documentarian) to generate the API documentation. If you want to modify the CSS files of your documentation, or simply want to learn more about what is possible, take a look at the [Documentarian guide](http://marcelpociot.de/documentarian/installation).

### License

The Laravel API Documentation Generator is free software licensed under the MIT license.
