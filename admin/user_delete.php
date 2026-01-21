<?php
require_once '../includes/db.php'; 
require_once '../includes/auth.php';

/* ===============================
   AUTH & ADMIN CHECK
================================ */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../public/auth/login.php');
    exit;
}

$id = $_GET['id'] ?? null;
$loggedInUserId = $_SESSION['user']['id'];

/* ===============================
   SAFETY CHECKS
================================ */
// 1. Validate ID
if (!$id || !is_numeric($id)) {
    header('Location: users.php?error=invalid_id');
    exit;
}

// 2. Prevent self-disabling (Matches your security requirement)
if ((int)$id === (int)$loggedInUserId) {
    header('Location: users.php?error=self_disable');
    exit;
}

/* ===============================
   PROCESS DISABLE (is_active = 0)
================================ */
try {
    // Check if user exists before disabling
    $check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        header('Location: users.php?error=not_found');
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Optional: Audit Log (only if table exists)
        // If you don't have an audit_log table, wrap this in a table check or remove it
        
        $adminName = $_SESSION['user']['username'];
        $logMsg = "Admin $adminName disabled User ID #$id";
        $log = $conn->prepare("INSERT INTO audit_log (user_id, action, description) VALUES (?, 'DISABLE_USER', ?)");
        $log->bind_param("is", $loggedInUserId, $logMsg);
        $log->execute();

        header('Location: users.php?disabled=1');
        exit;
    } else {
        throw new Exception("Database update failed.");
    }

} catch (Exception $e) {
    // In production, log the error and redirect with a generic system error
    header('Location: users.php?error=system_error');
    exit;
}