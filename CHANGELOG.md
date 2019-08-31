# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased
### Added

### Changed

### Fixed

### Removed

## [3.15.0] - Saturday, 31 August 2019
### Added
- Ability to exclude a query or body parameter from being included in the example requests (https://github.com/mpociot/laravel-apidoc-generator/pull/552)

## [3.14.0] - Saturday, 31 August 2019
### Fixed
- Backwards compatibility for the changes to `@group` introduced in 3.12.0 (https://github.com/mpociot/laravel-apidoc-generator/commit/5647eda35ebb7f8aed35b31790c5f220b736e985)

## [3.13.0] (deleted)

## [3.12.0] - Sunday, 25 August 2019
### Fixed
- Specifying an `@group` for a method no longer requires you to add the description. (https://github.com/mpociot/laravel-apidoc-generator/pull/556)
- Pass the verbosity level down to the Collision library. (https://github.com/mpociot/laravel-apidoc-generator/pull/556)

## [3.11.0] - Friday, 9 August 2019
### Added
- Support for query parameters in the bash template (https://github.com/mpociot/laravel-apidoc-generator/pull/545)
- Include query parameters and headers in the generated Postman collection (https://github.com/mpociot/laravel-apidoc-generator/pull/537)
- Include Python out of the box as example language (https://github.com/mpociot/laravel-apidoc-generator/pull/524)

### Changed
- Moved nunomaduro/collision to "suggested" so it doesn't break PHP 7.0 (https://github.com/mpociot/laravel-apidoc-generator/commit/2f3a2144e1a4f1eb0229aea8b4d11707cb4aabbf)

### Fixed
- Stopped using config helper inside config file (https://github.com/mpociot/laravel-apidoc-generator/pull/548)

## [3.10.0] - Sunday, 23 June 2019
### Added
- `--verbose` flag to show exception encountered when making response calls (https://github.com/mpociot/laravel-apidoc-generator/commit/dc987f296e5a3d073f56c67911b2cb61ae47e9dc)

## [3.9.0] - Saturday, 8 June 2019
### Modified
- Postman collections and URLs in example requests now use the `apidoc.base_url` config variable (https://github.com/mpociot/laravel-apidoc-generator/pull/523)

## [3.8.0] - Wednesday, 29 May 2019
### Added
- Support for PHP array callable syntax in route action (https://github.com/mpociot/laravel-apidoc-generator/pull/516)

## [3.7.3] - Thursday, 23 May 2019
### Fixed
- Added faker_seed (https://github.com/mpociot/laravel-apidoc-generator/commit/d2901e51a68c17066d4dd96054ff5bfdf124945b)

## [3.7.2] - Sunday, 19 May 2019
### Added
- Support for URL paths in include/exclude rules (https://github.com/mpociot/laravel-apidoc-generator/pull/507)

## [3.7.1] - Friday, 17 May 2019
### Fixed
- Handle exception for no URL::forceRootURL() method in Lumen (https://github.com/mpociot/laravel-apidoc-generator/commit/2146fa114dc18bc32c00b5c5550266d753d5aef3)
- Url parameter bindings (https://github.com/mpociot/laravel-apidoc-generator/commit/f0dc118c6b7792894bf9baa352d7fc4ca8ca74b5)

## [3.7.0] - Thursday, 2 May 2019
### Added
- Support for `@queryParams` in Dingo FormRequests (https://github.com/mpociot/laravel-apidoc-generator/pull/506)
- Easier customisation of example languages (https://github.com/mpociot/laravel-apidoc-generator/commit/0aa737a2e54a913eab4d024a1644c5ddd5dee8b8)
- Include PHP as example language (https://github.com/mpociot/laravel-apidoc-generator/commit/c5814762fa52095fe645716554839b6ae110ef89)

## [3.6.0] - Monday, 29 April 2019
### Added
- Support for `@queryParams` in FormRequests (https://github.com/mpociot/laravel-apidoc-generator/pull/504)
- Added `default_group` key to replace `ungrouped_name` (https://github.com/mpociot/laravel-apidoc-generator/commit/72b5f546c1b84e69fe43c720a04f448c3b96e345)

## [3.5.0] - Tuesday, 23 April 2019
### Added
- Option to seed faker for deterministic output (https://github.com/mpociot/laravel-apidoc-generator/pull/503)
- Support for binding prefixes (https://github.com/mpociot/laravel-apidoc-generator/pull/498)
- Ability to override Laravel `config` (https://github.com/mpociot/laravel-apidoc-generator/pull/496)
- Allow override of the name 'general' for ungrouped routes (https://github.com/mpociot/laravel-apidoc-generator/pull/491)

### Changed
- Use parameter-bound URL in doc examples (https://github.com/mpociot/laravel-apidoc-generator/pull/500)

### Fixed
- Request router now matches when router has sub-domain (https://github.com/mpociot/laravel-apidoc-generator/pull/493)

## [3.4.4] - Saturday, 30 March 2019
### Fixed
- Allow users specify custom Content-type header for Markdown examples (https://github.com/mpociot/laravel-apidoc-generator/pull/486)

## [3.4.3] - Wednesday, 13 March 2019
### Fixed
- Ignore scalar type hints when checking for FormRequests (https://github.com/mpociot/laravel-apidoc-generator/pull/474)

## [3.4.2] - Sunday, 10 March 2019
### Added
- Ability to set cookies on response calls (https://github.com/mpociot/laravel-apidoc-generator/pull/471)

## [3.4.1] - Monday, 4 March 2019
### Fixed
- Support for Lumen 5.7 (https://github.com/mpociot/laravel-apidoc-generator/pull/467)

## [3.4.0] - Wednesday, 27 February 2019
### Added
- Support for Laravel 5.8 (https://github.com/mpociot/laravel-apidoc-generator/pull/462)
- Ability to annotate body parameters on FormRequest (https://github.com/mpociot/laravel-apidoc-generator/pull/460)


## [3.3.2] - Tuesday, 12 February 2019
### Added
- Ability to specify array and object body/query params using dot notation (https://github.com/mpociot/laravel-apidoc-generator/pull/445)
- Ability to specify name and description of Postman collection (https://github.com/mpociot/laravel-apidoc-generator/pull/443)

### Fixed
- Postman collection and documentation base URL now uses `config('app.url')` (https://github.com/mpociot/laravel-apidoc-generator/pull/458)

## [3.3.1] - Tuesday, 8 January 2019
### Fixed
- Fixed vendor tags (https://github.com/mpociot/laravel-apidoc-generator/pull/444)

## [3.3.0] - Wednesday, 2 January 2019
### Added
- Ability to replace json key values in response file (https://github.com/mpociot/laravel-apidoc-generator/pull/434)
- Support for custom transfer serializers (https://github.com/mpociot/laravel-apidoc-generator/pull/441)

## [3.2.0] - Wednesday, 12 December 2018
### Changed
- API groups are now sorted "naturally" (https://github.com/mpociot/laravel-apidoc-generator/pull/428)

### Fixed
- Partial resource controllers are now properly supported (https://github.com/mpociot/laravel-apidoc-generator/pull/429)
- PUT request body now formatted as `urlencoded` in Postman collection (https://github.com/mpociot/laravel-apidoc-generator/pull/418)
- `@responseFile` strategy now properly renders responses (https://github.com/mpociot/laravel-apidoc-generator/pull/427)

## [3.1.1] - Wednesday, 5 December 2018
### Added
- Ability to specify different responses for different status codes. (https://github.com/mpociot/laravel-apidoc-generator/pull/416)

## [3.1.0] - Wednesday, 28 November 2018
### Added
- Add `ResponseFileStrategy` to retrieve responses from files. (https://github.com/mpociot/laravel-apidoc-generator/pull/410)

### Modified
- Switch from `jQuery` to `fetch` in JavaScript examples. (https://github.com/mpociot/laravel-apidoc-generator/pull/411)

## [3.0.6] - Saturday, 24 November 2018
### Added
- `include` and `exclude` route options now support wildcards (https://github.com/mpociot/laravel-apidoc-generator/pull/409)

## [3.0.5] - Thursday, 15 November 2018
### Fixed
- Make `router` option case-insensitive (https://github.com/mpociot/laravel-apidoc-generator/pull/407)

## [3.0.4] - Wednesday, 7 November 2018
### Fixed
- Replaced use of `Storage::copy` with PHP's `copy` to work with absolute paths (https://github.com/mpociot/laravel-apidoc-generator/pull/404)

## [3.0.3] - Friday, 2 November 2018
### Fixed
- Replaced use of `config_path` with more generic option for better Lumen compatibility (https://github.com/mpociot/laravel-apidoc-generator/pull/398)

## [3.0.2] - Friday, 26 October 2018
### Added
- Ability to specify examples for body and query parameters (https://github.com/mpociot/laravel-apidoc-generator/pull/394)
### Fixed
- Rendering of example requests' descriptions (https://github.com/mpociot/laravel-apidoc-generator/pull/393)

## [3.0.1] - Monday, 22 October 2018
### Fixed
- Rendering of query parameters' descriptions (https://github.com/mpociot/laravel-apidoc-generator/pull/387)

## [3.0] - Sunday, 21 October 2018
### Added
- Official Lumen support (https://github.com/mpociot/laravel-apidoc-generator/pull/382)
- `@queryParam` annotation (https://github.com/mpociot/laravel-apidoc-generator/pull/383)
- `@bodyParam` annotation (https://github.com/mpociot/laravel-apidoc-generator/pull/362, https://github.com/mpociot/laravel-apidoc-generator/pull/366)
- `@authenticated` annotation (https://github.com/mpociot/laravel-apidoc-generator/pull/369)
- Ability to override the controller `@group` from the method. (https://github.com/mpociot/laravel-apidoc-generator/pull/372)
- Ability to use a custom logo (https://github.com/mpociot/laravel-apidoc-generator/pull/368)

### Changed
- Moved from command-line options to a config file  (https://github.com/mpociot/laravel-apidoc-generator/pull/362)
- Commands have been renamed to the `apidoc` namespace (previously `api`). (https://github.com/mpociot/laravel-apidoc-generator/pull/350)
- The `update` command has been renamed to `rebuild` and now uses the output path configured in the config file. (https://github.com/mpociot/laravel-apidoc-generator/pull/370)
- `@resource` renamed to `@group` (https://github.com/mpociot/laravel-apidoc-generator/pull/371)
- Added more configuration options for response calls (https://github.com/mpociot/laravel-apidoc-generator/pull/377)

### Fixed

### Removed
- FormRequest parsing is no longer supported (https://github.com/mpociot/laravel-apidoc-generator/pull/362)
