<?php

declare(strict_types=1);

use Maatify\Turnstile\TurnstileValidation;

/**
 * Realistic consumer workflow through the production autoload and the public
 * API: a Host validates a submitted Turnstile token. The provider endpoint is
 * redirected to the harness's deterministic local stand-in through the
 * documented protected extension point; Cloudflare is never contacted.
 *
 * Usage: php workflow.php <endpoint-base-url>
 */

require __DIR__ . '/vendor/autoload.php';

$base = $argv[1] ?? null;
if (!is_string($base) || $base === '') {
    fwrite(STDERR, "Usage: php workflow.php <endpoint-base-url>\n");
    exit(2);
}

final class ConsumerValidation extends TurnstileValidation
{
    public function __construct(private readonly string $endpointUrl)
    {
        parent::__construct('consumer-secret');
    }

    protected function endpoint(): string
    {
        return $this->endpointUrl;
    }
}

$reflection = new ReflectionClass(TurnstileValidation::class);
$packageFile = (string)$reflection->getFileName();
$vendorRoot = (string)realpath(__DIR__ . '/vendor');

$results = [];
foreach (['valid-token' => '/valid', 'invalid-token' => '/invalid'] as $token => $path) {
    $_POST['cf-turnstile-response'] = $token;
    $validation = new ConsumerValidation($base . $path);
    $results[$token] = [
        'success' => $validation->isSuccess(),
        'response' => $validation->getResponse(),
    ];
}

echo json_encode([
    'loaded_from_vendor' => str_starts_with((string)realpath($packageFile), $vendorRoot . DIRECTORY_SEPARATOR),
    'results' => $results,
], JSON_THROW_ON_ERROR), "\n";
