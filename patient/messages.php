<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('patient');
$selfPath = 'patient/messages.php';
$peerRole = 'doctor';
require __DIR__ . '/../includes/messages_module.php';
