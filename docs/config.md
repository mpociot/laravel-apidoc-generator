# Configuration

Before you can generate your documentation, you'll need to configure a few things in your `config/apidoc.php`.

- `output`
This is the file path where the generated documentation will be written to. Default: **public/docs**

- `postman`
This package can automatically generate a Postman collection for your routes, along with the documentation. This section is where you can configure (or disable) that.

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
