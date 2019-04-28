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

The base URL added to the Postman collection will be the value of the `url` key in your Laravel `config/app.php` file. 

## Manually modifying the content of the generated documentation
If you want to modify the content of your generated documentation without changing the routes, go ahead and edit the generated `index.md` file.

This file is located in the `source` folder of  your `output` directory (see [configuration](config.md#output)), so by default, this is `public/docs/source/index.md`.
 
After editing the markdown file, you can use the `apidoc:rebuild` command to rebuild your documentation into HTML.

```sh
php artisan apidoc:rebuild
```

## Automatically add markdown to the beginning or end of the documentation
 If you wish to automatically add the same content to the docs every time you generate (for instance, an introduction, a disclaimer or an authenticatino guide), you can add a `prepend.md` and/or `append.md` file to the `source` folder in the `output` directory, and they will be added to the generated documentation. 
 
 The contents of `prepend.md` will be added after the front matter and info text, while the contents of `append.md` will be added at the end of the document.

## Further modification

This package uses [Documentarian](https://github.com/mpociot/documentarian) to generate the API documentation. If you want to modify the CSS files of your documentation, or simply want to learn more about what is possible, take a look at the [Documentarian guide](http://marcelpociot.de/documentarian/installation).
