# Security Policy

[![Maatify CloudflareTurnstile](https://img.shields.io/badge/Maatify-CloudflareTurnstile-blue?style=for-the-badge)](https://github.com/Maatify/CloudflareTurnstile)
[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-9C27B0?style=for-the-badge)](https://github.com/Maatify)

## Supported Versions

The actively supported release line is `5.x`.

Security fixes are provided in the latest stable release within the supported `5.x` line. Users should upgrade to the latest available `5.x` version before reporting a vulnerability.

| Version | Supported |
|---------|-----------|
| `5.x`   | Yes       |
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
