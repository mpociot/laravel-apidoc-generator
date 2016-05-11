## Laravel API Documentation Generator (WIP)

`php artisan api:gen --routePrefix=settings/api/*`


### Install

Require this package with composer using the following command:

```bash
composer require mpociot/laravel-apidoc-generator
```
Go to your `config/app.php` and add the service provider:

```php
Mpociot\ApiDoc\ApiDocGeneratorServiceProvider::class
```

### Usage


```
php artisan api:generate 
    {--output=public/docs : The output path for the generated documentation}
    {--routePrefix= : The route prefix to use for generation - * can be used as a wildcard}
    {--routes=* : The route names to use for generation - if no routePrefix is provided}
    {--actAsUserId= : The user ID to use for API response calls}
```


### License

The Laravel API Documentation Generator is free software licensed under the MIT license.