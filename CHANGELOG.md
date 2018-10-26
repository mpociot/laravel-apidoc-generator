# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased
### Added

### Changed

### Fixed

### Removed


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
