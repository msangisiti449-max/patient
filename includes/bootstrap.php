<?php
/**
 * Loaded at the top of every page.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

send_security_headers();
start_secure_session();

// Pages behind login must never be cached by the browser (back button after logout).
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
