<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

start_session();
logout_user();
header('Location: ' . APP_URL . '/login.php');
exit;
