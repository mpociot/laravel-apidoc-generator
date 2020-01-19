# Overview

Automatically generate your API documentation from your existing Laravel/Lumen/[Dingo](https://github.com/dingo/api) routes. [Here's what the output looks like](https://shalvah.me/TheCensorshipAPI/).

`php artisan apidoc:generate`

## Contents
* [How This Works](description.md)
* [Configuration](config.md)
* [Migrating from v3 to v4](migrating.md)
* [Generating Documentation](generating-documentation.md)
* [Documenting Your API](documenting.md)
* [Extending functionality with plugins](plugins.md)
* [Internal Architecture](architecture.md)

## Installation
> Note: PHP 7 and Laravel 5.5 or higher are required.

```sh
composer require mpociot/laravel-apidoc-generator
```

### Laravel
Publish the config file by running:

```bash
php artisan vendor:publish --provider="Mpociot\ApiDoc\ApiDocGeneratorServiceProvider" --tag=apidoc-config
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
