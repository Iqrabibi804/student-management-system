<?php
/**
 * Logout Handler
 */
session_start();
require_once 'includes/db.php';

if (isset($_SESSION['admin_id'])) {
    logActivity('LOGOUT', 'Admin logged out');
}

$_SESSION = [];
session_destroy();

header('Location: auth/login.php');
exit;
