<?php
session_start();
include '../../includes/db.php';

// Check if a session exists before logging
if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $username = $_SESSION['user']['name'];
    $reason = isset($_GET['reason']) ? $_GET['reason'] : '';

    // Determine the action and description
    if ($reason === 'timeout') {
        $action = "Auto Logout";
        $description = "User $username was automatically logged out due to inactivity.";
    } else {
        $action = "Manual Logout";
        $description = "User $username logged out manually.";
    }

    // Insert into audit_logs
    $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log_stmt->bind_param("iss", $user_id, $action, $description);
    $log_stmt->execute();
}

// 1. Clear all session variables
$_SESSION = array();

// 2. Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// 3. Destroy the session on the server
session_destroy();

// 4. Redirect to login with the reason for the UI message
if (isset($reason) && $reason === 'timeout') {
    header("Location: login.php?msg=timeout");
} else {
    header("Location: login.php");
}
exit;