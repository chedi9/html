<?php
/**
 * Dashboard Redirect
 * Redirects users from the old dashboard to the unified dashboard
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect to unified dashboard
header('Location: unified_dashboard.php');
exit();
?> 