<?php
/**
 * CSRF protection helpers
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token'], $token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_valid_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        http_response_code(403);
        die('Invalid security token. Please refresh and try again.');
    }
}
