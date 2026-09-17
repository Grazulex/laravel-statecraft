# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [v1.4.0] - 2026-09-17

### Added

- Laravel 13 support (`illuminate/support` and `illuminate/contracts` `^12.0|^13.0`).
- `symfony/yaml` `^8.0` is now accepted alongside `^7.3`.
- CI test matrix now covers PHP 8.3 / 8.4 with Laravel 12 and 13 (Testbench 10 / 11), in `prefer-lowest` and `prefer-stable` modes.

### Changed

- PHP 8.3 is now the minimum supported version.
- Development dependencies updated: Pest `^3.8|^4.0`, Pest Laravel plugin `^3.2|^4.0`, Orchestra Testbench `^10.0|^11.0`.
- Release workflow now runs against Laravel 13 / Testbench 11.
- Code quality workflow installs dependencies with `composer update` since the package does not ship a lock file.

## [v1.3.0] - 2025-07-20

Last release supporting Laravel 12 only. See the [GitHub releases](https://github.com/Grazulex/laravel-statecraft/releases) for earlier history.

[Unreleased]: https://github.com/Grazulex/laravel-statecraft/compare/v1.4.0...HEAD
[v1.4.0]: https://github.com/Grazulex/laravel-statecraft/compare/v1.3.0...v1.4.0
[v1.3.0]: https://github.com/Grazulex/laravel-statecraft/releases/tag/v1.3.0
