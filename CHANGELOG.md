# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [6.0.0]

### Breaking changes

- The package now declares `php` as `>=8.4.27 <8.5.0 || >=8.5.12 <8.6.0`. Earlier releases declared no PHP constraint. Applications on an older PHP version, or on a PHP patch version below the security floors of 8.4 and 8.5, can no longer install this version.
- `maatify/logger` now requires `^5.1 || ^7.0` (previously `^5`), because log calls pass an explicit PSR-3 level, which `maatify/logger` supports from `5.1`. `maatify/json` now requires `^5.0.4` (previously `^5`), the release that allows `maatify/logger` 7. `psr/log` `^3` is now a direct dependency.

### Upgrading from 5.x

1. Run the application on PHP 8.4.27 or newer in 8.4, or 8.5.12 or newer in 8.5.
2. Change the version constraint of the library in your project to `^6.0`, and update it together with its dependencies. Once `6.0.0` is published, this does both:

   ```bash
   composer require 'maatify/cloudflare-turnstile:^6.0' --with-all-dependencies
   ```

   A constraint such as `^5.0` in your `composer.json` keeps Composer on 5.x, so `composer update` alone does not upgrade. Make sure `maatify/logger` ends up on `5.1` or newer and `maatify/json` on `5.0.4` or newer; if your project pins them lower, raise those constraints too.
3. No change to the public classes or method signatures is required.

### Added

- `TurnstileRequestCall::endpoint()`, a protected method that returns the verification URL (Cloudflare `siteverify` by default). A subclass may override it to target a different endpoint, for example a local test double. It only changes the URL; request construction and response mapping are unchanged.
- Support metadata in `composer.json` (`support.issues`, `support.source`, `support.security`), `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md` and `CODE_OF_CONDUCT.md`.

### Changed

- `composer.json` metadata (description, keywords, homepage, authors, configuration and dependency policy) now follows the Maatify Composer package standard, and the `version` field was removed: versions come from Git tags.

### Fixed

- Log calls passed the log-file name where `maatify/logger` expects the PSR-3 level, which `maatify/logger` 7 rejects with `InvalidArgumentException`. They now pass `level` (`info` on success, `error` on failure) and `logFile` separately, so `maatify/logger` 7 can be installed.
- A request whose parameters cannot be JSON-encoded now returns an unsuccessful result and logs the error instead of sending an empty request.
- A response that is valid JSON but not an object (for example a number, a string, a list or `null`) is now treated as an unsuccessful validation instead of raising a `TypeError`.
- A non-string `cf-turnstile-response` value is now sent as an empty token.
- `jsonErrors()` uses the first Cloudflare error code only when it is a non-empty string.

### Removed

- The call to `curl_close()`, which has had no effect since PHP 8.0 and is deprecated in PHP 8.5.

## [5.0.1] - 2025-08-10

Published release. Changes made before `6.0.0` are not itemized in this changelog; see the Git history and the [GitHub release](https://github.com/Maatify/CloudflareTurnstile/releases/tag/5.0.1).

[5.0.1]: https://github.com/Maatify/CloudflareTurnstile/releases/tag/5.0.1
