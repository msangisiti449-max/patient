<?php
/** JSON: unread notification count for the signed-in user (used by the bell icon). */
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Only same-origin fetch() calls from our own pages.
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') {
    http_response_code(400);
    echo json_encode(['error' => 'bad request']);
    exit;
}
$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}
if ($user['role'] === 'patient') {
    generate_reminders((int)$user['id']);
}
echo json_encode(['unread' => unread_notifications((int)$user['id'])]);
