# Cloudflare Turnstile Package Reference

Canonical reference for the stable public, runtime and behavioral contract of `maatify/cloudflare-turnstile`.

Composer identity, metadata, requirements, dependencies, autoloading and scripts are owned by [`composer.json`](composer.json) and are not restated here. Changes between releases are recorded in [CHANGELOG.md](CHANGELOG.md).

Usage walkthroughs are in the [Usage Guide](docs/guides/USAGE_GUIDE.md); runnable code is in [`examples/`](examples/).

## Purpose and boundaries

The library validates a Cloudflare Turnstile token on the server. It builds the `siteverify` request, sends it, maps the response and errors, logs the outcome, and fails closed: a request that cannot be sent, answered or read is reported as an unsuccessful validation, never as a success.

Non-goals:

- the Turnstile widget and any front-end code;
- framework, routing or session integration;
- storing, rotating or protecting the secret key (the application owns it);
- retrying failed requests or caching validation results across requests.

## Source topology

```text
Source Topology: Single Capability
```

Capability: server-side Turnstile token validation.

The sources live under `src/{Responsibility}/` (the Single Capability layout) and use the canonical namespace `Maatify\CloudflareTurnstile\` (`Maatify\{PackageName}\` plus the responsibility):

| Class | File |
|---|---|
| `Maatify\CloudflareTurnstile\Adapter\TurnstileRequestCall` | `src/Adapter/TurnstileRequestCall.php` (provider adaptation) |
| `Maatify\CloudflareTurnstile\Service\TurnstileValidation` | `src/Service/TurnstileValidation.php` (validation orchestration) |

`composer.json` maps `Maatify\CloudflareTurnstile\` to `src/`. No structural exception applies: the `5.x` namespace `Maatify\Turnstile\` was replaced in `6.0.0`, a breaking release, and the migration is described in [CHANGELOG.md](CHANGELOG.md).

## Consumer workflow

```text
Host input → TurnstileValidation → siteverify request → Cloudflare Turnstile → observable result
```

1. The Host page posts the widget token in the `cf-turnstile-response` field.
2. The Host server creates the validator with its secret key, `TurnstileValidation::getInstance($secretKey)`.
3. The Host calls `isSuccess()`, `getResponse()` or `jsonErrors()`.
4. The library sends one JSON `POST` to the verification endpoint and exposes the mapped result.

## Public Runtime API

### `Maatify\CloudflareTurnstile\Adapter\TurnstileRequestCall` (abstract)

Base class that owns the HTTP call.

| Member | Contract |
|---|---|
| `__construct(string $secret_key = '')` | Stores the key when it is not empty and prepares the cURL handle. Throws `RuntimeException` if cURL cannot be initialized. |
| `curlPost(array $params): stdClass` | Sends `params` as a JSON `POST` with `Content-Type: application/json` and returns the decoded JSON object. Values must be `scalar\|null`. |
| `curlGet(): stdClass` | Sends the call without a request body (`POST` disabled) and returns the decoded JSON object. |
| `protected endpoint(): string` | The verification URL, Cloudflare `siteverify` by default. See [Extension point](#extension-point). |
| `protected string $secret_key` | The secret key. Accessing it before a non-empty key was supplied raises an `Error`. |

Result of both calls: the decoded response when it is a JSON object; otherwise an object with `success = false`. This happens when cURL fails, when the response is empty or is not a JSON object (a number, string, list or `null`), and when `params` cannot be JSON-encoded (in that case no request is sent).

### `Maatify\CloudflareTurnstile\Service\TurnstileValidation` (extends `TurnstileRequestCall`)

| Member | Contract |
|---|---|
| `static getInstance(string $secret_key = ''): self` | Returns the shared instance. The key of the first call is kept; later keys are ignored. A non-empty key is required on the first call. |
| `isSuccess(): bool` | Validates once and returns whether Cloudflare reported success. The result is kept on the instance and later calls do not send another request. |
| `getResponse(): array` | Validates if needed and returns the decoded Cloudflare response as an array. |
| `jsonErrors(): void` | Writes a JSON error response and ends the request when the token is missing (`cf-turnstile-response` empty) or the validation fails. It returns normally only when the validation succeeds. When Cloudflare returned a non-empty string as its first `error-codes` entry, that code is reported; otherwise the JSON-formatted response is. It relies on `maatify/json` for the response. |
| `public ?bool $success` | `null` until validated, then the result. |
| `public array $response` | The decoded response, empty until validated. |

## Request contract

- Method and encoding: `POST`, JSON body, `Content-Type: application/json`.
- Body: exactly the keys `secret` (the configured key) and `response` (the posted token).
- Token source: `$_POST['cf-turnstile-response']`. A value that is not a string is sent as an empty string.
- Endpoint: `https://challenges.cloudflare.com/turnstile/v0/siteverify`.
- TLS: peer and host verification are enabled. Timeouts are 120 seconds to connect and 60 seconds in total.

## Response and error mapping

- `isSuccess()` is `true` only when the response object has `success` set to a truthy value.
- A response object is exposed as received. Documented Cloudflare error codes (`missing-input-secret`, `invalid-input-secret`, `missing-input-response`, `invalid-input-response`, `bad-request`, `timeout-or-duplicate`, `internal-error`) appear in `error-codes`.
- A transport error, an empty or non-object response, or a request that cannot be encoded yields an object with `success = false` and no other fields.

## Exception and failure guarantees

- No exception is thrown for a failed validation; failures are reported through `success = false`.
- `RuntimeException` is thrown only if cURL cannot be initialized in the constructor.
- `Error` is raised when the secret key was never supplied and is then used.
- `jsonErrors()` ends the request when it reports a failure.

## Logging

Every call that reaches Cloudflare or fails while preparing the request writes one entry through `maatify/logger`, under the log file `CloudflareTurnstile/turnstile_<success|failed>_<milliseconds>`:

- Level `info`, log file `turnstile_success_…`: a response that decodes to a JSON object was received. This is logged whatever the value of its `success` field, so a validation rejected by Cloudflare (`success: false`) is also logged at `info`; use the returned result, not the log level, to learn the outcome.
- Level `error`, log file `turnstile_failed_…`: a transport (cURL) error, an empty or non-object response (the message names the HTTP status when it is not 200), or request parameters that cannot be JSON-encoded.

The entry contains the endpoint URL and the request parameter names (`param_names`), never their values: the secret key, the Turnstile token and the `remoteip` value of the library's request are not logged. This covers what the library passes to the logger; it does not control other operational data (for example a client address) that the logger itself may record. A logged response keeps only `success`, `error-codes`, `hostname`, `action` and `challenge_ts`; other fields such as `cdata` and `metadata` are omitted.

## Extension point

`TurnstileRequestCall::endpoint()` is protected and may be overridden in a subclass to point the library at a different URL, for example a local test double.

It only changes the URL. Request construction, serialization, response mapping and logging stay the library's. A subclass must return a non-empty string and must not rely on this method to alter the request contract.

## Compatibility notes

- The `Maatify\CloudflareTurnstile` namespace, the class names and the signatures above are the public contract of the `6.0.0` line.
- The exact PHP and dependency requirements are those declared in `composer.json`; the changes introduced by `6.0.0` and the upgrade path from `5.x` are in [CHANGELOG.md](CHANGELOG.md).
