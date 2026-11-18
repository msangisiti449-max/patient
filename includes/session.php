<?php
/**
 * Hardened session handling and HTTP security headers.
 */

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
}

function send_security_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; "
        . "script-src 'self'; "
        . "style-src 'self' https://cdnjs.cloudflare.com https://fonts.googleapis.com; "
        . "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; "
        . "img-src 'self' data:; connect-src 'self'; "
        . "form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    header_remove('X-Powered-By');
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');   // reject uninitialised (attacker-supplied) ids
    ini_set('session.use_only_cookies', '1');  // never accept ids from the URL
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.sid_length', '48');
    ini_set('session.sid_bits_per_character', '6');

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,               // browser-session cookie
        'path'     => BASE_URL . '/',
        'domain'   => '',
        'secure'   => is_https(),      // sent only over HTTPS when HTTPS is used
        'httponly' => true,            // not readable by JavaScript (blocks cookie theft via XSS)
        'samesite' => 'Strict',        // not sent on cross-site requests (CSRF defence)
    ]);
    session_start();

    $now = time();

    // Idle timeout
    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
        destroy_session();
        session_start();
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Your session expired due to inactivity. Please sign in again.'];
    }
    $_SESSION['last_activity'] = $now;

    // Bind the session to the browser's user agent (makes stolen ids harder to reuse)
    $fp = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    if (isset($_SESSION['fingerprint']) && !hash_equals($_SESSION['fingerprint'], $fp)) {
        destroy_session();
        session_start();
    }
    $_SESSION['fingerprint'] = $fp;

    // Periodic id regeneration
    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = $now;
    } elseif ($now - $_SESSION['created_at'] > SESSION_REGEN_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['created_at'] = $now;
    }
}

function destroy_session(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Strict',
        ]);
        session_destroy();
    }
}
