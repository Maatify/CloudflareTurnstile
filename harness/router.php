<?php

declare(strict_types=1);

/** Deterministic provider stand-in for the Consumer Verification Harness (php -S router). */

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : '/';

header('Content-Type: application/json');

echo match ($path) {
    '/valid' => json_encode(['success' => true, 'error-codes' => [], 'hostname' => 'example.com']),
    '/invalid' => json_encode(['success' => false, 'error-codes' => ['invalid-input-response']]),
    default => json_encode(['success' => false, 'error-codes' => ['bad-request']]),
};
