<?php
/** Sign out. POST + CSRF only, so other sites cannot log users out with a link or image. */
require_once __DIR__ . '/includes/bootstrap.php';

if (!is_post()) {
    redirect('index.php');
}
verify_csrf();
if (!empty($_SESSION['user_id'])) {
    audit('Signed out');
}
destroy_session();
start_secure_session();
session_regenerate_id(true);
flash('success', 'You have been signed out securely.');
redirect('login.php');
