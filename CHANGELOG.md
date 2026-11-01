# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [6.0.0] - 2026-11-01

### Breaking changes

- The package now declares `php` as `>=8.4.27 <8.5.0 || >=8.5.12 <8.6.0`. Earlier releases declared no PHP constraint. Applications on an older PHP version, or on a PHP patch version below the security floors of 8.4 and 8.5, can no longer install this version.
- `maatify/logger` now requires `^5.1 || ^7.0` (previously `^5`), because log calls pass an explicit PSR-3 level, which `maatify/logger` supports from `5.1`. `maatify/json` now requires `^5.0.4` (previously `^5`), the release that allows `maatify/logger` 7. `psr/log` `^3` is now a direct dependency.

- The public classes moved to the canonical namespace: `Maatify\Turnstile\TurnstileValidation` is now `Maatify\CloudflareTurnstile\Service\TurnstileValidation`, and `Maatify\Turnstile\TurnstileRequestCall` is now `Maatify\CloudflareTurnstile\Adapter\TurnstileRequestCall`. Class names, method signatures and behavior are unchanged; only the `use` statements and any `extends` clauses need to change.

### Upgrading from 5.x

1. Run the application on PHP 8.4.27 or newer in 8.4, or 8.5.12 or newer in 8.5.
2. Change the version constraint of the library in your project to `^6.0`, and update it together with its dependencies. This does both:

   ```bash
   composer require 'maatify/cloudflare-turnstile:^6.0' --with-all-dependencies
   ```

   A constraint such as `^5.0` in your `composer.json` keeps Composer on 5.x, so `composer update` alone does not upgrade. Make sure `maatify/logger` ends up on `5.1` or newer and `maatify/json` on `5.0.4` or newer; if your project pins them lower, raise those constraints too.
3. Update the class references in your code:

   ```php
   // before (5.x)
   use Maatify\Turnstile\TurnstileValidation;
   // after (6.0.0)
   use Maatify\CloudflareTurnstile\Service\TurnstileValidation;
   ```

   If you extend the base class, import `Maatify\CloudflareTurnstile\Adapter\TurnstileRequestCall` instead of `Maatify\Turnstile\TurnstileRequestCall`. Method signatures are unchanged.

### Added

- `TurnstileRequestCall::endpoint()`, a protected method that returns the verification URL (Cloudflare `siteverify` by default). A subclass may override it to target a different endpoint, for example a local test double. It only changes the URL; request construction and response mapping are unchanged.
- `.gitattributes` keeps development and CI files out of the Composer dist archive; CI inspects the resulting archive.
- `llms.txt`, a navigation file for AI consumers that points to the Package Reference, Usage Guide, README and examples.
- `docs/guides/USAGE_GUIDE.md`, a consumer guide with a capability map, walkthroughs and links to the examples.
- Consumer examples in `examples/` (one Host-dependent form example and two standalone examples that run against a local stand-in), smoke-executed by CI.
- Support metadata in `composer.json` (`support.issues`, `support.source`, `support.security`), `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md` and `CODE_OF_CONDUCT.md`.

### Changed

- `composer.json` metadata (description, keywords, homepage, authors, configuration and dependency policy) now follows the Maatify Composer package standard, and the `version` field was removed: versions come from Git tags.
- `README.md` was rewritten for 6.0.0 (the 5.x README showed the old namespace and examples).
- The PHP sources moved from `turnstile/` to `src/Adapter/` and `src/Service/`. Class names are unchanged.

### Fixed

- Log entries no longer contain request parameter values: only the parameter names and an allow-listed subset of the response are logged.
- Log calls passed the log-file name where `maatify/logger` expects the PSR-3 level, which `maatify/logger` 7 rejects with `InvalidArgumentException`. They now pass `level` (`info` on success, `error` on failure) and `logFile` separately, so `maatify/logger` 7 can be installed.
- A request whose parameters cannot be JSON-encoded now returns an unsuccessful result and logs the error instead of sending an empty request.
- A response that is valid JSON but not an object (for example a number, a string, a list or `null`) is now treated as an unsuccessful validation instead of raising a `TypeError`.
- A non-string `cf-turnstile-response` value is now sent as an empty token.
- `jsonErrors()` uses the first Cloudflare error code only when it is a non-empty string.

### Removed

- The call to `curl_close()`, which has had no effect since PHP 8.0 and is deprecated in PHP 8.5.

## [5.0.2] - 2026-11-01

Security maintenance release for the `5.x` line.

### Fixed

- Log entries no longer contain request parameter values: only the parameter names and an allow-listed subset of the response are logged.
- `Logger::RecordLog()` is now called with named arguments, so the call is valid for every `maatify/logger` `^5` release; with `maatify/logger` 5.1 and newer the PSR-3 level is passed as well.

### Changed

- The `version` field was removed from `composer.json`: versions come from Git tags.

## [5.0.1] - 2025-08-10

Published release. The changes in this release are not itemized in this changelog; see the Git history and the [GitHub release](https://github.com/Maatify/CloudflareTurnstile/releases/tag/5.0.1).

[6.0.0]: https://github.com/Maatify/CloudflareTurnstile/releases/tag/6.0.0
[5.0.2]: https://github.com/Maatify/CloudflareTurnstile/releases/tag/5.0.2
[5.0.1]: https://github.com/Maatify/CloudflareTurnstile/releases/tag/5.0.1
