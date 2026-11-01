# Security Policy

[![Maatify CloudflareTurnstile](https://img.shields.io/badge/Maatify-CloudflareTurnstile-blue?style=for-the-badge)](https://github.com/Maatify/CloudflareTurnstile)
[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-9C27B0?style=for-the-badge)](https://github.com/Maatify)

## Supported Versions

At `6.0.0` release qualification, `5.x` was the published and supported release line; it receives security maintenance fixes only. It is intended for applications that cannot yet run PHP 8.4 or newer. Users should upgrade to the latest available `5.x` version before reporting a vulnerability.

This tree is the artifact for release `6.0.0`, which, as of the `6.0.0` release qualification, had **not yet been published**. Release preparation does not make `6.x` a supported line: it becomes supported only once `6.0.0` is published and available to consumers. From that point `6.x` is the actively supported line and `5.x` remains security-maintenance only.

| Version | Supported |
|---------|-----------|
| `5.x`   | Security fixes only |
| `6.0.0` (this artifact) | Supported once published (not yet published at qualification) |
| Older lines | No    |

## Reporting a Vulnerability

Please do not report security vulnerabilities through public GitHub issues, pull requests, or discussions.

Report them privately by email to [support@maatify.dev](mailto:support@maatify.dev) with the subject `Security: maatify/cloudflare-turnstile`. Include:

- the affected version;
- a description of the issue and its impact;
- steps or a minimal example that reproduces it;
- any suggested mitigation, if you have one.

We will acknowledge the report, assess it, and coordinate a fix and disclosure with you. Please keep the details private until a fix is available.

## Scope

In scope: the code in this repository, including how it builds the Cloudflare Turnstile `siteverify` request, maps the response, and handles failures (a failed validation must never be reported as a success).

Out of scope:

- vulnerabilities in Cloudflare Turnstile or its service;
- vulnerabilities in third-party dependencies, which should be reported to their maintainers;
- the secrets and configuration of the application that uses this library. Never commit your Turnstile secret key; keep it in your own secret storage.
