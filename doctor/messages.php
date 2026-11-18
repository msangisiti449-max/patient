<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = require_role('doctor');
$selfPath = 'doctor/messages.php';
$peerRole = 'patient';
require __DIR__ . '/../includes/messages_module.php';
