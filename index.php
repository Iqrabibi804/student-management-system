<?php
/**
 * Root index — redirect to dashboard (or login if not authenticated)
 */
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: auth/login.php');
}
exit;
