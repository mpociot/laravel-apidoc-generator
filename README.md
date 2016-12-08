## Laravel API Documentation Generator

Automatically generate your API documentation from your existing Laravel routes. Take a look at the [example documentation](http://marcelpociot.com/whiteboard/).

`php artisan api:gen --routePrefix="settings/api/*"`

![image](http://img.shields.io/packagist/v/mpociot/laravel-apidoc-generator.svg?style=flat)
![image](http://img.shields.io/packagist/l/mpociot/laravel-apidoc-generator.svg?style=flat)
[![codecov.io](https://codecov.io/github/mpociot/laravel-apidoc-generator/coverage.svg?branch=master)](https://codecov.io/github/mpociot/laravel-apidoc-generator?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mpociot/laravel-apidoc-generator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mpociot/laravel-apidoc-generator/?branch=master)
[![Build Status](https://travis-ci.org/mpociot/laravel-apidoc-generator.svg?branch=master)](https://travis-ci.org/mpociot/laravel-apidoc-generator)
[![StyleCI](https://styleci.io/repos/57999295/shield)](https://styleci.io/repos/57999295)
[![Dependency Status](https://www.versioneye.com/php/mpociot:laravel-apidoc-generator/dev-master/badge?style=flat)](https://www.versioneye.com/php/mpociot:laravel-apidoc-generator/dev-master)


## Installation

Require this package with composer using the following command:

```sh
$ composer require mpociot/laravel-apidoc-generator
```
Go to your `config/app.php` and add the service provider:

```php
Mpociot\ApiDoc\ApiDocGeneratorServiceProvider::class,
```

## Usage

To generate your API documentation, use the `api:generate` artisan command.

```sh
$ php artisan api:generate --routePrefix="api/v1/*"
```

This command will scan your applications routes for the URIs matching `api/v1/*` and will parse these controller methods and form requests.

### Available command options:

Option | Description
--------- | -------
`output` | The output path used for the generated documentation. Default: `public/docs`
`routePrefix` | The route prefix to use for generation - `*` can be used as a wildcard
`routes` | The route names to use for generation - Required if no routePrefix is provided
`middleware` | The middlewares to use for generation
`noResponseCalls` | Disable API response calls
`noPostmanCollection` | Disable Postman collection creation
`useMiddlewares` | Use all configured route middlewares (Needed for Laravel 5.3 `SubstituteBindings` middleware)
`actAsUserId` | The user ID to use for authenticated API response calls
`router` | The router to use, when processing the route files (can be Laravel or Dingo - defaults to Laravel)
`bindings` | List of route bindings that should be replaced when trying to retrieve route results. Syntax format: `binding_one,id|binding_two,id`
`force` | Force the re-generation of existing/modified API routes
`header` | Custom HTTP headers to add to the example requests. Separate the header name and value with ":". For example: `--header 'Authorization: CustomToken'`

## Publish rule descriptions for customisation or translation.

 By default, this package returns the descriptions in english. You can publish the packages language files, to customise and translate the documentation output.

 ```sh
 $ php artisan vendor:publish
 ```

 After the files are published you can customise or translate the descriptions in the language you want by renaming the `en` folder and editing the files in `public/vendor/apidoc/resources/lang`.


### How does it work?

This package uses these resources to generate the API documentation:

#### Controller doc block

This package uses the HTTP controller doc blocks to create a table of contents and show descriptions for your API methods.
Using @resource is useful as it creates a new Group in the API documentation, but is not required. The short description
after the @resource should be unique to allow anchor tags to navigate to this section. A longer description can be included below.

```php
/**
 * @resource Account
 *
 * Longer description
 */
class AccountController extends Controller {

	/**
	 * This is the short description [and should be unique as anchor tags link to this in navigation menu]
	 *
	 * This can be an optional longer description of your API call, used within the documentation.
	 *
	 */
	 public function foo(){

	 }
```

**Result:** ![Doc block result](http://marcelpociot.com/documentarian/doc_block.png)

#### Form request validation rules

To display a list of valid parameters, your API methods accepts, this package uses Laravels [Form Requests Validation](https://laravel.com/docs/5.2/validation#form-request-validation).


```php
public function rules()
{
    return [
        'title' => 'required|max:255',
        'body' => 'required',
        'type' => 'in:foo,bar',
        'thumbnail' => 'required_if:type,foo|image',
    ];
}
```

**Result:** ![Form Request](http://marcelpociot.com/documentarian/form_request.png)

#### API responses

If your API route accepts a `GET` method, this package tries to call the API route with all middleware disabled to fetch an example API response. 

If your API needs an authenticated user, you can use the `actAsUserId` option to specify a user ID that will be used for making these API calls:

```sh
$ php artisan api:generate --routePrefix="api/*" --actAsUserId=1
```

If you don't want to automatically perform API response calls, use the `noResponseCalls` option.

```sh
$ php artisan api:generate --routePrefix="api/*" --noResponseCalls
```

> Note: The example API responses work best with seeded data.

#### Postman collections

The generator automatically creates a Postman collection file, which you can import to use within your [Postman App](https://www.getpostman.com/apps) for even simpler API testing and usage.

If you don't want to create a Postman collection, use the `--noPostmanCollection` option, when generating the API documentation.

## Modify the generated documentation

If you want to modify the content of your generated documentation, go ahead and edit the generated `index.md` file.
The default location of this file is: `public/docs/source/index.md`.
 
After editing the markdown file, use the `api:update` command to rebuild your documentation as a static HTML file.

```sh
$ php artisan api:update
```

As an optional parameter, you can use `--location` to tell the update command where your documentation can be found.

## Skip single routes

If you want to skip a single route from a list of routes that match a given prefix, you can use the `@hideFromAPIDocumentation` tag on the Controller method you do not want to document.

## Further modification

This package uses [Documentarian](https://github.com/mpociot/documentarian) to generate the API documentation. If you want to modify the CSS files of your documentation, or simply want to learn more about what is possible, take a look at the [Documentarian guide](http://marcelpociot.com/documentarian/installation).

### License

The Laravel API Documentation Generator is free software licensed under the MIT license.
