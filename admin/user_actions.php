<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Security: Only admins allowed
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_GET['action'] ?? '';
$id = isset($_POST['id']) ? (int) $_POST['id'] : null;
$loggedInId = $_SESSION['user']['id'];

// --- HANDLE DELETE/DISABLE ---
if ($action === 'delete' && $id) {
    if ($id === $loggedInId) {
        echo json_encode(['success' => false, 'message' => 'Security: You cannot disable yourself!']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Audit Log
        $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'USER_DISABLED', ?)");
        $desc = "Disabled account for User ID: $id";
        $log->bind_param("is", $loggedInId, $desc);
        $log->execute();
        echo json_encode(['success' => true, 'message' => 'User account disabled.']);
    }
    exit;
}

// --- HANDLE SAVE (CREATE & EDIT) ---
$username = trim($_POST['username'] ?? '');
$role = $_POST['role'] ?? 'staff';
$isActive = ($id === $loggedInId) ? 1 : ($_POST['is_active'] ?? 1);
$password = $_POST['password'] ?? '';

if ($action === 'edit' && $id) {
    // 1. Update Core Info
    $stmt = $conn->prepare("UPDATE users SET username=?, role=?, is_active=? WHERE user_id=?");
    $stmt->bind_param("ssii", $username, $role, $isActive, $id);
    $stmt->execute();

    // 2. Update Password if provided
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pStmt = $conn->prepare("UPDATE users SET password_hash=? WHERE user_id=?");
        $pStmt->bind_param("si", $hash, $id);
        $pStmt->execute();
    }

    $logMsg = "Updated profile for $username";
    $logAction = "USER_EDITED";
} else {
    // 3. Create New User
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $username, $hash, $role);
    $logMsg = "Created new account: $username";
    $logAction = "USER_CREATED";
}

if ($stmt->execute()) {
    // Log the successful action to Audit Logs
    $audit = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
    $audit->bind_param("iss", $loggedInId, $logAction, $logMsg);
    $audit->execute();

    echo json_encode(['success' => true, 'message' => 'User saved successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}