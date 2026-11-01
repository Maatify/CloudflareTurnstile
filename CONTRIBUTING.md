# Contributing

Thank you for helping improve `maatify/cloudflare-turnstile`.

## Package identity

`maatify/cloudflare-turnstile` is a PHP library that validates Cloudflare Turnstile tokens on the server by calling the Turnstile `siteverify` endpoint and exposing the result through `Maatify\CloudflareTurnstile\Service\TurnstileValidation`.

## Package boundaries

- In scope: building the `siteverify` request, mapping its response and errors, logging the outcome, and failing closed (a failed or unreadable validation is never reported as a success).
- Out of scope: the Turnstile widget and any front-end code, framework or application integration, and storing your secret key. Keep contributions inside these boundaries.
- The public API and namespace of the library are a compatibility contract. Do not change them as a side effect of another change.

## Ways to contribute

- Report a bug or propose an improvement through a GitHub issue.
- Send a pull request that fixes a bug, adds tests, or improves documentation.
- Report a security issue **privately**, never through a public issue or pull request: follow [SECURITY.md](SECURITY.md).

## Requirements

- PHP `>=8.4.27 <8.5.0 || >=8.5.12 <8.6.0` (the declared `require.php` constraint) with `ext-curl`.
- Composer 2.10 or newer.
- `composer.lock` is **not** committed; it is ignored by `.gitignore`. Resolve dependencies with `composer update`.

## Local verification

Install the dependencies and run the checks that CI runs:

```bash
composer update
composer validate --strict
composer analyse
composer test
```

Individual suites:

```bash
composer test:regression
composer test:system
```

CI runs two required jobs, and the `CI gate` check aggregates both: the `Verify` matrix and `Workflow lint`.

`scripts/ci/verify.sh` runs everything the `Verify` matrix runs, in latest or lowest dependency mode: Composer validation and resolution, platform requirements, the strict PSR-4 autoload check, the PHP patch floor and `require.php` boundary checks, syntax, whitespace, PHPStan, the regression and system suites, the Consumer Verification Harness (a clean Composer consumer that installs the package from the committed tree and exercises its public API), smoke execution of the standalone examples, an inspection of the distribution archive (required files, excluded files and document links), and `composer audit`:

```bash
scripts/ci/verify.sh latest
scripts/ci/verify.sh lowest
```

The harness can also be run on its own with `harness/run.sh latest` (or `lowest`).

Before a release, `scripts/release/verify-artifact.sh <ref>` verifies the candidate artifact of an exact commit (archive content and links, `composer validate --strict`, the harness in both modes) and prints a record of the target, commit, tree and results. CI runs it on the checked-out commit and records the result in the job summary. A qualification run requires a supported PHP patch and refuses Composer platform overrides; `RELEASE_VERIFY_DRY_RUN=1` allows a local rehearsal whose record is labelled `DRY RUN` and never reports `PASS`. After a release is published, the `Verify published release` workflow (manual run, inputs: version and expected commit SHA) installs it from Packagist on supported PHP versions with no platform override and runs `scripts/release/verify-published.sh`, which checks the install type, the package reference, the installed files and the public classes (`VERIFY_PUBLISHED_DRY_RUN=1` allows a local rehearsal that never reports `PASS`).

`Workflow lint` is not part of `verify.sh`. Run it locally with the same pinned `actionlint` version as CI (`1.7.12`; `ACTIONLINT_VERSION` in `.github/workflows/ci.yml` is the source of truth, and the release archive is verified against the `ACTIONLINT_SHA256` there):

```bash
actionlint --version   # must report 1.7.12
actionlint .github/workflows/*.yml
```

The gate checks the PHP patch floor of the interpreter it runs on, so use a PHP version within the declared constraint. The harness packages the committed tree (`git archive HEAD`), so commit your changes before running it.

## Tests

- Every bug fix needs a regression test that fails without the fix.
- New observable behavior needs a system-level test through the public API.
- Tests never contact Cloudflare. They use the local deterministic provider stand-in in `tests/Support` and the fixtures in `tests/Fixtures/siteverify`; new fixtures need an entry in `PROVENANCE.json` and must contain no real secrets or tokens.
- This library has no database or other repository-controlled service that CI could provision, so there are no Integration suites. Cloudflare Turnstile is an externally controlled provider: it is verified deterministically with the fixtures above, and any live check against it is a separate, controlled verification that is never part of baseline CI.
- PHPStan runs at level `max` with no baseline and no suppressions; fix the underlying type issue instead.

## Pull requests

- Open one pull request per change, targeting `main`, with a clear description of what changed and why.
- Keep the change focused: do not mix unrelated fixes, refactors, or formatting.
- CI must pass; the `CI gate` check is the one that decides.
- Do not include secrets, tokens, `.env` files, or logs.
- Do not commit `composer.lock` or the `version` field in `composer.json`; versions come from Git tags.

## Architecture discussions

Discuss with the maintainers before implementing anything that changes the public API, the namespace, the supported PHP versions or dependency constraints, or the request/response contract. Describe the compatibility impact and the migration path. Small fixes and documentation changes do not need prior discussion.
