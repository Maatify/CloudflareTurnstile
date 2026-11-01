# Usage Guide

How to validate a Cloudflare Turnstile token on your server with `maatify/cloudflare-turnstile`. The stable contract and the full Public Runtime API are in the [Package Reference](../../CLOUDFLARE_TURNSTILE_PACKAGE_REFERENCE.md); this guide shows how to use it.

## Fit

Use this library when your PHP application renders a Turnstile widget and must confirm on the server that the token the widget produced is valid before it accepts a form, login or any other request.

It sends the token and your secret key to the Cloudflare `siteverify` endpoint, maps the answer, and fails closed: if the request cannot be sent or answered, the result is "not successful", never "successful".

## Requirements

- PHP and dependency constraints are declared in [`composer.json`](../../composer.json): PHP `>=8.4.27 <8.5.0 || >=8.5.12 <8.6.0`, the `curl` extension, and the Maatify packages it lists.
- A Turnstile site key (used by the widget in your page) and the matching **secret key** (used only on your server).
- Network access from your server to `challenges.cloudflare.com`.

### Release status and installation

This guide describes release `6.0.0`, which is **not published yet**. The published `5.x` line uses the previous namespace, so the code in this guide does not work with it. Once `6.0.0` is published, install it with:

```bash
composer require 'maatify/cloudflare-turnstile:^6.0' --with-all-dependencies
```

Upgrading from `5.x`: the namespace changed. See [CHANGELOG.md](../../CHANGELOG.md).

## Non-goals

- The widget and any front-end code.
- Framework, routing or session integration.
- Storing or protecting the secret key. Keep it in your own secret storage and never commit it.
- Retrying failed requests, or caching results across requests.

## Primary calls

| Call | Input | Output |
|---|---|---|
| `TurnstileValidation::getInstance($secretKey)` | Your secret key (non-empty on the first call) | The shared validator |
| `isSuccess()` | The posted `cf-turnstile-response` field | `bool` |
| `getResponse()` | The same posted field | The decoded Cloudflare response as an array |
| `jsonErrors()` | The same posted field | Nothing on success; on failure it writes a JSON error and ends the request |

All of them live in `Maatify\CloudflareTurnstile\Service\TurnstileValidation`. The widget posts the token in the form field `cf-turnstile-response`; the library reads it from `$_POST`.

## Capability map

| Capability | Walkthrough | Example |
|---|---|---|
| Accept or reject a request by its token | [Validate a form token](#validate-a-form-token) | [`examples/validate-form-token.php`](../../examples/validate-form-token.php) |
| Answer a failed validation with a JSON error | [Return a JSON error](#return-a-json-error) | [`examples/json-error-response.php`](../../examples/json-error-response.php) |
| Inspect the Cloudflare response | [Validate a form token](#validate-a-form-token) | [`examples/validate-with-test-double.php`](../../examples/validate-with-test-double.php) |
| Run against a local stand-in instead of Cloudflare | [Test without Cloudflare](#test-without-cloudflare) | [`examples/validate-with-test-double.php`](../../examples/validate-with-test-double.php) |

## Validate a form token

```text
Input → Public Call → Result → Boundary
```

- **Input:** the posted `cf-turnstile-response` field and your secret key.
- **Public call:**

  ```php
  use Maatify\CloudflareTurnstile\Service\TurnstileValidation;

  $validation = TurnstileValidation::getInstance($secretKey);

  if ($validation->isSuccess()) {
      // process the form
  } else {
      // reject the request
  }
  ```

- **Result:** `isSuccess()` is `true` only when Cloudflare reports success. `getResponse()` returns the decoded answer, including `error-codes` when Cloudflare rejected the token. A failed validation never throws; it is reported as unsuccessful.
- **Boundary:** `getInstance()` keeps the key of the first call; later keys are ignored. The first `isSuccess()` or `getResponse()` sends the request, and later calls on the same instance reuse the result. A missing or non-string token is sent as an empty token, which Cloudflare rejects.

Complete example: [`examples/validate-form-token.php`](../../examples/validate-form-token.php).

## Return a JSON error

```text
Input → Public Call → Result → Boundary
```

- **Input:** the same posted field and secret key.
- **Public call:** `TurnstileValidation::getInstance($secretKey)->jsonErrors();`
- **Result:** when the token is missing or the validation fails, a JSON error response is written and the request ends. When the validation succeeds, the call returns and your code continues.
- **Boundary:** the response is produced through `maatify/json` and ends the request, so use it only where ending the request is the behavior you want; otherwise use `isSuccess()` and respond yourself.

Complete example: [`examples/json-error-response.php`](../../examples/json-error-response.php).

## Test without Cloudflare

```text
Input → Public Call → Result → Boundary
```

- **Input:** a local URL that answers like `siteverify`.
- **Public call:** subclass `TurnstileValidation` and override the protected `endpoint()` to return that URL.
- **Result:** the same `isSuccess()` and `getResponse()` behavior, driven by your stand-in.
- **Boundary:** `endpoint()` changes only the URL. Request construction, response mapping and logging stay the library's. Never point production code at anything but the real endpoint.

Complete, runnable example (it needs a stand-in URL as its argument): [`examples/validate-with-test-double.php`](../../examples/validate-with-test-double.php).

## Logging and secrets

Each request that is sent to Cloudflare, or that fails while being prepared, writes one entry through `maatify/logger`; a later call that reuses a result already held by the instance sends nothing and writes no new entry. The entry contains the endpoint URL and the request parameter names, never the secret key, the token or other parameter values. Details are in the [Package Reference](../../CLOUDFLARE_TURNSTILE_PACKAGE_REFERENCE.md#logging).

## Where to go next

- Complete API and guarantees: [Package Reference](../../CLOUDFLARE_TURNSTILE_PACKAGE_REFERENCE.md).
- Release history and the `5.x` upgrade path: [CHANGELOG.md](../../CHANGELOG.md).
- Reporting a vulnerability: [SECURITY.md](../../SECURITY.md).
