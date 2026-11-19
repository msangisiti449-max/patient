<?php
/**
 * Application configuration.
 * This folder is blocked from web access by config/.htaccess.
 */

// ---- Database -------------------------------------------------------------
// For production, create a dedicated MySQL user with only
// SELECT, INSERT, UPDATE, DELETE on patient_portal instead of root.
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'patient_portal');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---- Application ----------------------------------------------------------
define('APP_NAME', 'CarePoint Patient Portal');
// URL path where the project lives inside htdocs (no trailing slash).
define('BASE_URL', '/patient');
define('CURRENCY', 'TZS');
date_default_timezone_set('Africa/Dar_es_Salaam');

// ---- Security -------------------------------------------------------------
define('SESSION_NAME', 'CPPSESSID');
define('SESSION_IDLE_TIMEOUT', 1800);      // 30 minutes of inactivity
define('SESSION_REGEN_INTERVAL', 900);     // regenerate session id every 15 minutes
define('MAX_LOGIN_ATTEMPTS', 5);           // per account before lockout
define('LOCKOUT_MINUTES', 15);
define('IP_MAX_ATTEMPTS', 20);             // per IP within IP_WINDOW_MINUTES
define('IP_WINDOW_MINUTES', 15);

// ---- Error handling: never show errors to visitors ------------------------
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
