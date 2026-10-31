<?php

declare(strict_types=1);

/**
 * Deterministic local stand-in for Cloudflare siteverify (router for `php -S`).
 * The path selects the response shape; no real provider is contacted.
 */

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = is_string($uri) ? parse_url($uri, PHP_URL_PATH) : '/';
$body = json_decode((string)file_get_contents('php://input'));

header('Content-Type: application/json');

if (is_string($path) && preg_match('#^/fixture/([a-z-]+)$#', $path, $m) === 1) {
    $file = __DIR__ . '/../Fixtures/siteverify/' . $m[1] . '.json';
    if (is_file($file)) {
        readfile($file);
        return;
    }
}

echo match ($path) {
    '/capture' => json_encode([
        'success' => true,
        'method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
        'raw' => file_get_contents('php://input'),
    ]),
    '/object' => json_encode(['success' => true, 'echo' => $body]),
    '/number' => '123',
    '/string' => '"text"',
    '/list' => '[1]',
    '/null' => 'null',
    '/errors' => json_encode(['success' => false, 'error-codes' => ['invalid-input-secret']]),
    '/errors-nonstring' => json_encode(['success' => false, 'error-codes' => [123]]),
    default => json_encode(['success' => false]),
};
