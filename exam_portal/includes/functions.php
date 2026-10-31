<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Escape output before displaying user-controlled content in HTML.
 */
function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Redirect to a local page.
 */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * Generate or retrieve a CSRF token.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Validate a submitted CSRF token.
 */
function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($submitted) ||
        !is_string($stored) ||
        $stored === '' ||
        !hash_equals($stored, $submitted)
    ) {
        http_response_code(403);
        exit('Invalid request. Please refresh the page and try again.');
    }
}

/**
 * Display a one-time message.
 */
function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

/**
 * Retrieve and remove a one-time message.
 */
function get_flash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;

    unset($_SESSION['flash'][$key]);

    return $message;
}