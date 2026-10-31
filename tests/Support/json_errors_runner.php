<?php

declare(strict_types=1);

use Maatify\Turnstile\Tests\Support\TestValidation;

/**
 * Runs TurnstileValidation::jsonErrors() in its own process (it terminates the
 * request). Usage: php json_errors_runner.php <endpoint-url>
 */

require __DIR__ . '/../../vendor/autoload.php';

$_POST['cf-turnstile-response'] = 'token';
$endpoint = $argv[1] ?? null;
if (!is_string($endpoint) || $endpoint === '') {
    fwrite(STDERR, "Usage: php json_errors_runner.php <endpoint-url>\n");
    exit(2);
}

(new TestValidation($endpoint))->jsonErrors();
