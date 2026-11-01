<?php

// Deterministic local stand-in for Cloudflare siteverify (router for `php -S`).
header('Content-Type: application/json');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
echo match ($path) {
    '/rejected' => json_encode(['success' => false, 'error-codes' => ['invalid-input-secret']]),
    default => json_encode(['success' => true, 'hostname' => 'example.test', 'cdata' => 'CANARY-CDATA']),
};
