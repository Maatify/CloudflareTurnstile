<div align="center">

# Cloudflare Turnstile

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://www.maatify.dev/company-profile/maatify_logo_white.svg">
  <source media="(prefers-color-scheme: light)" srcset="https://www.maatify.dev/company-profile/maatify_logo_dark.svg">
  <img alt="Maatify.dev" src="https://www.maatify.dev/company-profile/maatify_logo_dark.svg">
</picture>

[![Release Target](https://img.shields.io/badge/Release%20Target-6.0.0%20(unpublished)-blue)](CHANGELOG.md)
[![Published at qualification](https://img.shields.io/badge/Published%20at%20qualification-5.0.2-lightgrey)](https://packagist.org/packages/maatify/cloudflare-turnstile)
[![PHP](https://img.shields.io/badge/PHP-8.4.27%2B%20%7C%208.5.12%2B%20(%3C8.6)-8892BF)](composer.json)
[![License](https://img.shields.io/badge/License-Proprietary-green)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-brightgreen)](phpstan.neon)
[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-blueviolet)](https://github.com/Maatify)

[![Packagist](https://img.shields.io/badge/Packagist-maatify%2Fcloudflare--turnstile-blue)](https://packagist.org/packages/maatify/cloudflare-turnstile)
[![Monthly Downloads](https://img.shields.io/packagist/dm/maatify/cloudflare-turnstile)](https://packagist.org/packages/maatify/cloudflare-turnstile/stats)
[![Total Downloads](https://img.shields.io/packagist/dt/maatify/cloudflare-turnstile)](https://packagist.org/packages/maatify/cloudflare-turnstile/stats)

[![Usage Guide](https://img.shields.io/badge/Docs-Usage%20Guide-blue)](docs/guides/USAGE_GUIDE.md)
[![Examples](https://img.shields.io/badge/Docs-Examples-blue)](examples/)
[![Package Reference](https://img.shields.io/badge/Docs-Package%20Reference-blue)](CLOUDFLARE_TURNSTILE_PACKAGE_REFERENCE.md)
[![Changelog](https://img.shields.io/badge/Docs-Changelog-blue)](CHANGELOG.md)
[![Security Policy](https://img.shields.io/badge/Docs-Security-blue)](SECURITY.md)
[![Contributing](https://img.shields.io/badge/Docs-Contributing-blue)](CONTRIBUTING.md)

PHP library that validates a Cloudflare Turnstile token on the server and reports the result, failing closed when a request cannot be sent or answered.

</div>

---

## Package status

This tree is the artifact for release **`6.0.0`**, which is **not published yet**. The version published on Packagist at the time of qualification is `5.0.2`, and it uses the previous namespace; see [Upgrade notes](#upgrade-notes). `6.0.0` is a breaking release: the namespace and the PHP, `maatify/logger` and `maatify/json` requirements changed.

## Key Features

- Validates the `cf-turnstile-response` token against the Cloudflare `siteverify` endpoint with one JSON `POST`.
- Fails closed: a transport error, an empty or non-object response, or an unencodable request is reported as an unsuccessful validation, never as a success.
- Returns a plain boolean (`isSuccess()`), the decoded Cloudflare response (`getResponse()`), or ends the request with a JSON error (`jsonErrors()`).
- Logs each request through `maatify/logger` without writing the secret key, the token or other request values.
- One documented extension point, `endpoint()`, for running against a local stand-in.

## Requirements

- PHP `>=8.4.27 <8.5.0 || >=8.5.12 <8.6.0` with the `curl` extension.
- `maatify/functions ^5`, `maatify/json ^5.0.4`, `maatify/logger ^5.1 || ^7.0` and `psr/log ^3`.
- A Turnstile secret key and network access to `challenges.cloudflare.com`.

The authoritative constraints are in [`composer.json`](composer.json).

## Installation

```bash
composer require maatify/cloudflare-turnstile:6.0.0
```

This command resolves through Packagist only **after** `6.0.0` has been published; until then it cannot install this tree. With the constraint `^5.0` an existing project stays on `5.x`, so to move an existing project to `6.x` use `composer require 'maatify/cloudflare-turnstile:^6.0' --with-all-dependencies` once it is published.

## Quick Usage

```php
<?php

use Maatify\CloudflareTurnstile\Service\TurnstileValidation;

require 'vendor/autoload.php';

// The widget posts the token in $_POST['cf-turnstile-response'].
$secretKey = (string)getenv('TURNSTILE_SECRET_KEY');
$validation = TurnstileValidation::getInstance($secretKey);

if ($validation->isSuccess()) {
    // the token is valid: process the request
} else {
    // reject the request
}
```

The Usage Guide walks through each call as `Input → Public Call → Result → Boundary`.

## Public Runtime API

| Class | Role |
|---|---|
| `Maatify\CloudflareTurnstile\Service\TurnstileValidation` | Entry point: `getInstance()`, `isSuccess()`, `getResponse()`, `jsonErrors()`, and the public `$success` and `$response` properties. |
| `Maatify\CloudflareTurnstile\Adapter\TurnstileRequestCall` | Abstract base that owns the HTTP call: `curlPost()`, `curlGet()` and the protected `endpoint()` extension point. |

The complete inventory, signatures and guarantees are in the [Package Reference](CLOUDFLARE_TURNSTILE_PACKAGE_REFERENCE.md).

## Critical Runtime Behavior

- A failed validation does not throw; it is reported as `success = false`.
- `getInstance()` keeps the secret key of its first call, and the first `isSuccess()` or `getResponse()` sends the request; later calls on the same instance reuse the result.
- `jsonErrors()` ends the request when the token is missing or the validation fails.

## Exception and Error Propagation

`RuntimeException` is thrown only if cURL cannot be initialized when the object is created. Using the secret key before one was supplied raises an `Error`. Everything else, including network failures, is returned as an unsuccessful result.

## Security and Trust Boundaries

- The secret key belongs to your application: keep it in your own secret storage and never commit it. The library does not store or rotate it.
- Log entries contain the endpoint URL and the request parameter names, never the secret key, the token or other parameter values.
- Report a vulnerability privately, as described in [SECURITY.md](SECURITY.md); do not use public issues.

## Examples

Runnable and Host-dependent examples are in [`examples/`](examples/): validating a form token, answering a failure with a JSON error, and running against a local stand-in for Cloudflare.

## Documentation

- [Usage Guide](docs/guides/USAGE_GUIDE.md): integration workflows and boundaries.
- [Package Reference](CLOUDFLARE_TURNSTILE_PACKAGE_REFERENCE.md): the stable public, runtime and behavioral contract.
- [Changelog](CHANGELOG.md): release history and the upgrade path from `5.x`.
- [Security Policy](SECURITY.md) and [Contributing Guide](CONTRIBUTING.md).

## Quality Status

Verified in CI on PHP 8.4 and 8.5, and on the lowest supported dependencies:

- PHPStan at level `max`.
- Regression and system test suites, with deterministic provider-contract tests driven by fixtures. They never contact Cloudflare.
- A consumer harness that installs the package from the committed tree into a clean Composer project and exercises its public API.
- Smoke execution of the standalone examples, and an inspection of the distribution archive.
- `composer audit` with abandoned packages treated as failures.

These checks prove the implemented contract against deterministic fixtures. They do not prove current live Cloudflare behavior.

## Development and Testing

```bash
composer update
composer analyse
composer test
scripts/ci/verify.sh latest
```

`scripts/ci/verify.sh` runs the same checks as CI. See [CONTRIBUTING.md](CONTRIBUTING.md).

## Upgrade Notes

`6.0.0` moves the classes to the canonical namespace and raises the PHP and dependency requirements:

```php
// 5.x
use Maatify\Turnstile\TurnstileValidation;

// 6.0.0
use Maatify\CloudflareTurnstile\Service\TurnstileValidation;
```

The complete upgrade steps are in the [Changelog](CHANGELOG.md).

## License

Proprietary. See [LICENSE](LICENSE).

## Author

Engineered by **Mohamed Abdulalim** ([@megyptm](https://github.com/megyptm))<br>
Backend Lead & Technical Architect<br>
[https://www.maatify.dev](https://www.maatify.dev)

---

<div align="center">

[Built with ❤️ by Maatify.dev — Unified Ecosystem for Modern PHP Libraries](https://www.maatify.dev)

</div>
