<?php
/**
 * Auth Guard - include at top of every protected page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/auth/') !== false ? 'login.php' : 'auth/login.php'));
    exit;
}

require_once __DIR__ . '/db.php';
