<?php

declare(strict_types=1);

/**
 * Runnable end-to-end example against a local stand-in for Cloudflare.
 *
 * The library's only extension point, endpoint(), is overridden so the request
 * goes to the URL given on the command line instead of Cloudflare. Everything
 * else (request body, response mapping, result) is the library's own.
 *
 * Usage: php examples/validate-with-test-double.php <endpoint-url>
 */

use Maatify\CloudflareTurnstile\Service\TurnstileValidation;

require __DIR__ . '/../vendor/autoload.php';

final class LocalTurnstileValidation extends TurnstileValidation
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
    fwrite(STDERR, "Usage: php examples/validate-with-test-double.php <endpoint-url>\n");
    exit(1);
}

// In a real request the widget posts this field; here it is set by hand.
$_POST['cf-turnstile-response'] = 'example-token';

$validation = new LocalTurnstileValidation($endpointUrl);

echo $validation->isSuccess() ? "accepted\n" : "rejected\n";
echo json_encode($validation->getResponse(), JSON_THROW_ON_ERROR), "\n";
