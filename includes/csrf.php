<?php
/**
 * CSRF protection: one random token per session, required on every POST.
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

/** Call at the top of any POST handler. Stops the request if the token is missing or wrong. */
function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(403);
        audit('CSRF token validation failed on ' . ($_SERVER['SCRIPT_NAME'] ?? ''));
        exit('Invalid or expired form submission. Please go back, refresh the page and try again.');
    }
}
