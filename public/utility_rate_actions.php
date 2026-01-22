<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check Authentication
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_GET['action'] ?? '';
$id = $_POST['id'] ?? null;
$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;

// --- 1. DELETE LOGIC ---
if ($action === 'delete' && $id) {
    // Audit Log: Get details before deleting
    $stmt = $conn->prepare("SELECT effective_date FROM utility_rates WHERE rate_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $date = $stmt->get_result()->fetch_assoc()['effective_date'] ?? 'Unknown';

    $del = $conn->prepare("DELETE FROM utility_rates WHERE rate_id = ?");
    $del->bind_param("i", $id);
    
    if ($del->execute()) {
        $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'DELETE_UTILITY_RATE', ?)");
        $desc = "Removed utility rate effective date: $date (ID: $id)";
        $log->bind_param("is", $userId, $desc);
        $log->execute();

        echo json_encode(['success' => true, 'message' => 'Rate record deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}

// --- 2. SAVE LOGIC (CREATE & UPDATE) ---
$e_rate   = $_POST['electric_rate'] ?? 0;
$w_rate   = $_POST['water_rate'] ?? 0;
$eff_date = $_POST['effective_date'] ?? date('Y-m-d');

if ($action === 'edit' && $id) {
    $stmt = $conn->prepare("UPDATE utility_rates SET electric_rate=?, water_rate=?, effective_date=? WHERE rate_id=?");
    $stmt->bind_param("ddsi", $e_rate, $w_rate, $eff_date, $id);
    $logAction = "UPDATE_UTILITY_RATE";
} else {
    $stmt = $conn->prepare("INSERT INTO utility_rates (electric_rate, water_rate, effective_date) VALUES (?, ?, ?)");
    $stmt->bind_param("dds", $e_rate, $w_rate, $eff_date);
    $logAction = "CREATE_UTILITY_RATE";
}

if ($stmt->execute()) {
    $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
    $desc = "$logAction: E:$e_rate, W:$w_rate starting $eff_date";
    $log->bind_param("iss", $userId, $logAction, $desc);
    $log->execute();

    echo json_encode(['success' => true, 'message' => 'Utility rates saved successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
}