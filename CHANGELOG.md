# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased
### Added

### Changed

### Fixed

### Removed


## [2.1.5] - 123h September, 2018
### Fixed
- Parse JSON responses from `@transformer` tag for DIngo router (https://github.com/mpociot/laravel-apidoc-generator/pull/323)

## [2.1.4] - 12th September, 2018
### Fixed
- Parse JSON responses from  `@response` and `@transformer` tags correctly (https://github.com/mpociot/laravel-apidoc-generator/pull/321)

## [2.1.3] - 11th September, 2018
### Fixed
- Parse `@response` tags regardless of HTTP method (https://github.com/mpociot/laravel-apidoc-generator/pull/318)

## [2.1.2] - 10th September, 2018
### Fixed
- Set correct HTTP method when parsing FormRequest (https://github.com/mpociot/laravel-apidoc-generator/pull/314)

## [2.1.1] - 10th September, 2018
### Fixed
- Print the correct file path of generated documentation (https://github.com/mpociot/laravel-apidoc-generator/pull/311)
- Removed any extra slashes in URLs displayed in code samples (https://github.com/mpociot/laravel-apidoc-generator/pull/310)
- Response calls are now also made for only GET in DIngo Router (https://github.com/mpociot/laravel-apidoc-generator/pull/309)
- HEAD routes are no longer automatically generated for GET routes in DIngo Router (https://github.com/mpociot/laravel-apidoc-generator/pull/309)

## [2.1.0] - 9th September, 2018
### Added
- Added support for multiple route domains (https://github.com/mpociot/laravel-apidoc-generator/pull/255) 
- Added support for descriptions in custom validation rules (https://github.com/mpociot/laravel-apidoc-generator/pull/208)
- Added support for multiple route prefixes (https://github.com/mpociot/laravel-apidoc-generator/pull/203)
- Added support for formatting and `<aside>` tags (https://github.com/mpociot/laravel-apidoc-generator/pull/261)
- Support for Laravel 5.5 auto-discovery (https://github.com/mpociot/laravel-apidoc-generator/pull/217)

### Changed
- Response calls are now only made when route is GET (https://github.com/mpociot/laravel-apidoc-generator/pull/279)
- Validator factory is now passed to `FormRequest::validator` method (https://github.com/mpociot/laravel-apidoc-generator/pull/236)
- Bind optional model parameters in routes (https://github.com/mpociot/laravel-apidoc-generator/pull/297/)
- HEAD routes are no longer automatically generated for GET routes (https://github.com/mpociot/laravel-apidoc-generator/pull/180)
- `actAsUserId` option is no longer cast to an int (https://github.com/mpociot/laravel-apidoc-generator/pull/257)

### Fixed
- `useMiddleware` option is now actually used (https://github.com/mpociot/laravel-apidoc-generator/pull/297/)
- Changes to the info vendor view are now persisted (https://github.com/mpociot/laravel-apidoc-generator/pull/120)
- Fixed memory leak issues (https://github.com/mpociot/laravel-apidoc-generator/pull/256)
- Fixed issues with validating array parameters (https://github.com/mpociot/laravel-apidoc-generator/pull/299)
- `@response` tag now parses content correctly as JSON (https://github.com/mpociot/laravel-apidoc-generator/pull/271)

### Removed
