# Generating Documentation

To generate your API documentation, use the `apidoc:generate` artisan command.

```sh
php artisan apidoc:generate

```

It will generate documentation using your specified configuration. The documentation will be generated as static HTML and CSS assets within the specified output folder.

## Regenerating
When you make changes to your routes, you can safely regenerate your documentation by running the `generate` command. This will rewrite the documentation for only the routes you have changed. You can use the `force` option to force the regeneration of existing/unmodified API routes.

## Postman collections

The generator automatically creates a Postman collection file, which you can import to use within your [Postman app](https://www.getpostman.com/apps) for even simpler API testing and usage.

If you don't want to create a Postman collection, set the `postman.enabled` config option to false.

The base URL used in the Postman collection will be the value of the `base_url` key in your Laravel `config/apidoc.php` file. 

## Manually modifying the content of the generated documentation
If you want to modify the content of your generated documentation without changing the routes, go ahead and edit the generated `index.md` file.

This file is located in the `source` folder of  your `output` directory (see [configuration](config.html#output)), so by default, this is `public/docs/source/index.md`.
 
After editing the markdown file, you can use the `apidoc:rebuild` command to rebuild your documentation into HTML.

```sh
php artisan apidoc:rebuild
```

## Automatically add markdown to the beginning or end of the documentation
 If you wish to automatically add the same content to the docs every time you generate (for instance, an introduction, a disclaimer or an authenticatino guide), you can add a `prepend.md` and/or `append.md` file to the `source` folder in the source output directory (`resources/docs/source`), and they will be added to the generated documentation. 
 
 The contents of `prepend.md` will be added after the front matter and info text, while the contents of `append.md` will be added at the end of the document.
 
 ## Specifying language for examples
 For each endpoint, an example request is shown in [each language configured](config.html#example-languages). To add a language which is not supported by this package, you'll have to create your own view for how an example should render. Here's how:
 
 - Publish the vendor views by running:
 
 ```bash
 php artisan vendor:publish --provider="Mpociot\ApiDoc\ApiDocGeneratorServiceProvider" --tag=apidoc-views
 ```
 
 This will copy the views files to `\resources\views\vendor\apidoc`.
 
 - Next, create a file called {language-name}.blade.php (for example, ruby.blade.php) in the partials/example-requests directory. You can then write Markdown with Blade templating that describes how the example request for the language should be rendered. You have the `$route` variable available to you. This variable is an array with the following keys:
- `methods`: an array of the HTTP methods for that route
- `boundUri`: the complete URL for the route, with any url parameters replaced (/users/{id} -> /users/1)
- `headers`: key-value array of headers to be sent with route (according to your configuration)
- `cleanQueryParameters`: key-value array of query parameters with example values to be sent with the request. Parameters which have been excluded from the example requests (see [Example Parameters](documenting.html#example-parameters)) will not be present here.
- `cleanBodyParameters`: key-value array of body parameters with example values to be sent with the request. Parameters which have been excluded from the example requests (see [Example Parameters](documenting.html#example-parameters)) will not be present here.

- Add the language to the `example_languages` array in the package config.

- Generate your documentation 

To customise existing language templates you can perform the `vendor:publish` command above, then modify the blade templates in `resources/` as necessary.

## Memory Limitations

Generating docs for large APIs can be memory intensive. If you run into memory limits, consider running PHP with command line flags to increase memory limit or update your CLI php.ini file:

```
php -d memory_limit=1G artisan apidoc:generate
```

## Further modification

This package uses [Documentarian](https://github.com/mpociot/documentarian) to generate the API documentation. If you want to modify the CSS files of your documentation, or simply want to learn more about what is possible, take a look at the [Documentarian guide](http://marcelpociot.de/documentarian/installation).
