<?php

declare(strict_types=1);

/**
 * Validate the Turnstile token posted by a form.
 *
 * Host-dependent example: it needs the form's POST field `cf-turnstile-response`
 * and your secret key in the TURNSTILE_SECRET_KEY environment variable, and it
 * contacts Cloudflare. It is checked for syntax and static analysis only, not
 * smoke-executed. For a runnable version see validate-with-test-double.php.
 */

use Maatify\CloudflareTurnstile\Service\TurnstileValidation;

require __DIR__ . '/../vendor/autoload.php';

$secretKey = (string)getenv('TURNSTILE_SECRET_KEY');
if ($secretKey === '') {
    fwrite(STDERR, "Set TURNSTILE_SECRET_KEY to your Turnstile secret key.\n");
    exit(1);
}

$validation = TurnstileValidation::getInstance($secretKey);

if ($validation->isSuccess()) {
    echo "Token accepted: continue processing the form.\n";
} else {
    echo "Token rejected: do not process the form.\n";
}
