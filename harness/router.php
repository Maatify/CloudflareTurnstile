<?php

declare(strict_types=1);

/**
 * Deterministic provider stand-in for the Consumer Verification Harness (php -S router).
 *
 * It enforces the siteverify request contract: a JSON POST whose body carries exactly
 * `secret` and `response`, with the secret the harness consumer configures. A request
 * that deviates is answered with `bad-request`, and every received request is appended
 * to the JSONL file named by HARNESS_REQUEST_LOG so the harness can assert on it.
 */

const EXPECTED_SECRET = 'consumer-secret';

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : '/';
$method = $_SERVER['REQUEST_METHOD'] ?? '';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$raw = (string)file_get_contents('php://input');
$body = json_decode($raw, true);

$log = getenv('HARNESS_REQUEST_LOG');
if (is_string($log) && $log !== '') {
    file_put_contents(
        $log,
        json_encode(['path' => $path, 'method' => $method, 'content_type' => $contentType, 'body' => $body]) . "\n",
        FILE_APPEND
    );
}

$contractOk = $method === 'POST'
    && $contentType === 'application/json'
    && is_array($body)
    && array_keys($body) === ['secret', 'response']
    && $body['secret'] === EXPECTED_SECRET;

header('Content-Type: application/json');

if (!$contractOk) {
    echo json_encode(['success' => false, 'error-codes' => ['bad-request']]);
    return;
}

echo match ($path) {
    '/valid' => json_encode(['success' => true, 'error-codes' => [], 'hostname' => 'example.com']),
    '/invalid' => json_encode(['success' => false, 'error-codes' => ['invalid-input-response']]),
    default => json_encode(['success' => false, 'error-codes' => ['bad-request']]),
};
