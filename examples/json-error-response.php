<?php

declare(strict_types=1);

/**
 * Let the library end a failed request with a JSON error response.
 *
 * jsonErrors() writes the error and ends the request when the token is missing
 * or invalid, and returns normally only when the validation succeeded. This
 * example points the library at a local stand-in through endpoint() so it can
 * run without contacting Cloudflare.
 *
 * Usage: php examples/json-error-response.php <endpoint-url>
 */

use Maatify\CloudflareTurnstile\Service\TurnstileValidation;

require __DIR__ . '/../vendor/autoload.php';

final class StandInTurnstileValidation extends TurnstileValidation
{
    /**
     * @param non-empty-string $endpointUrl
     */
    public function __construct(private readonly string $endpointUrl)
    {
        parent::__construct('example-secret-key');
    }

    protected function endpoint(): string
    {
        return $this->endpointUrl;
    }
}

$endpointUrl = $argv[1] ?? '';
if ($endpointUrl === '') {
    fwrite(STDERR, "Usage: php examples/json-error-response.php <endpoint-url>\n");
    exit(1);
}

$_POST['cf-turnstile-response'] = 'example-token';

(new StandInTurnstileValidation($endpointUrl))->jsonErrors();

// Reached only when the validation succeeded.
echo "validated\n";
