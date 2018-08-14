## Laravel API Documentation Generator

Automatically generate your API documentation from your existing Laravel routes. Take a look at the [example documentation](http://marcelpociot.de/whiteboard/).

`php artisan api:gen --routePrefix="settings/api/*"`

![image](http://img.shields.io/packagist/v/mpociot/laravel-apidoc-generator.svg?style=flat)
![image](http://img.shields.io/packagist/l/mpociot/laravel-apidoc-generator.svg?style=flat)
[![codecov.io](https://codecov.io/github/mpociot/laravel-apidoc-generator/coverage.svg?branch=master)](https://codecov.io/github/mpociot/laravel-apidoc-generator?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mpociot/laravel-apidoc-generator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mpociot/laravel-apidoc-generator/?branch=master)
[![Build Status](https://travis-ci.org/mpociot/laravel-apidoc-generator.svg?branch=master)](https://travis-ci.org/mpociot/laravel-apidoc-generator)
[![StyleCI](https://styleci.io/repos/57999295/shield?style=flat)](https://styleci.io/repos/57999295)
[![Dependency Status](https://www.versioneye.com/php/mpociot:laravel-apidoc-generator/dev-master/badge?style=flat)](https://www.versioneye.com/php/mpociot:laravel-apidoc-generator/dev-master)

## Changes in fork
#### Fixed display parameters for array items in request
 Now you will see all parameters of request in documentation.
#### Solved problem with parsing of custom rules
For correct parsing custom rule you should add `__toString` method.


Ex.: 
``` php
public function __toString()
{
  return 'field_type:field description';
}
```
Field types for custom rules:
 -  `custom_string`
 -  `custom_integer`
 -  `custom_boolean`
 -  `custom_date`
 


