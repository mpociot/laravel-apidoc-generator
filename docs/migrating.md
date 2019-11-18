# Migrating
Note: This isn't meant to be an exhaustive guide to the changes in v4. Please see the changelog for more details (and full list of new features).

## Requirements
- PHP version: 7.2+
- Laravel/Lumen version: 5.7+

## Configuration 
- Rename your old config file (for instance to `apidoc.old.php`). Publish the new config file via `php artisan vendor:publish --provider="Mpociot\ApiDoc\ApiDocGeneratorServiceProvider" --tag=apidoc-config`. Then copy over any changes you've made in the old one and delete it when you're done.

- Remove the `output` key. Source files now go to `resources/docs/source` and generated docs go to either `public/docs/` or `resources/views/apidoc`.

- Make sure the `type` value is set appropriately. See [the docs](config.html#type). 

- Remove the `bindings` section. It has been superseded by the `@urlParam` annotation, which works similarly to the existing `@queryParam` annotation. See [the docs](documenting.html#specifying-request-parameters)

- Remove the `response_calls.bindings` section. Use the `Example: ` feature of `@urlParam` to specify the value you want to be used in response calls.

- Rename the `query` and `body` sections in `response_calls` section to `queryParams` and `bodyParams`

- Remove the `apply.response_calls.headers`. Move any headers you had there to `apply.headers` 

## Assets
- If you've published the vendor views, rename them (for instance to `route.old.blade.php`). Publish the new views via `php artisan vendor:publish --provider="Mpociot\ApiDoc\ApiDocGeneratorServiceProvider" --tag=apidoc-views`. Compare the two views and reconcile your changes, then delete the old views. Some of the data being passed to the views (the `$route` object) has changed in either name or format, so things will likely break if you use old views.
The major change here is the introduction of the `urlParameters` section and the collapsing of route `title`, `description`, `groupName`, `groupDescription`, and authentication status (`authenticated` into a `metadata` section.

- The location of the source files for the generated docs has changed. Move any prepend/append files you've created from `public/docs/source` to the new location (`resources/docs/source`)

## API
- Verify that any custom strategies you've written match the new signatures. See [the docs](plugins.html#strategies). Also note the order of execution and the new stages present.

## Other new features (highlights)
- [Non-static docs/docs with authentication](config.html#type)
- [`@apiResource` for Eloquent API resources](documenting.html#apiresource-apiresourcecollection-and-apiresourcemodel)
- You can now mix and match response strategies and status codes as you like.
