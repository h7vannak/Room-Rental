<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Always return JSON for this file
header('Content-Type: application/json');

// Check Authentication
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$action = $_GET['action'] ?? '';
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : null;
$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;

// --- 1. DELETE LOGIC ---
if ($action === 'delete' && $id) {
    // Dependency Check: Is renter in a room?
    $check = $conn->prepare("SELECT room_id FROM rooms WHERE renter_id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete: Renter is still assigned to a room.']);
        exit;
    }

    // Get name for audit log
    $nameStmt = $conn->prepare("SELECT renter_name FROM renters WHERE renter_id = ?");
    $nameStmt->bind_param("i", $id);
    $nameStmt->execute();
    $name = $nameStmt->get_result()->fetch_assoc()['renter_name'] ?? 'Unknown';

    $stmt = $conn->prepare("DELETE FROM renters WHERE renter_id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'DELETE_RENTER', ?)");
        $desc = "Removed renter: $name (ID: $id)";
        $log->bind_param("is", $userId, $desc);
        $log->execute();

        echo json_encode(['success' => true, 'message' => 'Renter profile deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error during deletion.']);
    }
    exit;
}

// --- 2. SAVE LOGIC (CREATE & UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = $_POST['name'] ?? '';
    $gender   = $_POST['gender'] ?? '';
    $mobile   = $_POST['mobile'] ?? '';
    $telegram = $_POST['telegram'] ?? '';
    $address  = $_POST['address'] ?? '';
    $nat_id   = $_POST['nat_id'] ?? null;

    if (empty($name) || empty($nat_id)) {
        echo json_encode(['success' => false, 'message' => 'Name and Nationality are required.']);
        exit;
    }

    if ($action === 'edit' && $id) {
        // UPDATE
        $stmt = $conn->prepare("UPDATE renters SET renter_name=?, gender=?, mobile_phone=?, telegram=?, renter_address=?, nat_id=? WHERE renter_id=?");
        $stmt->bind_param("sssssii", $name, $gender, $mobile, $telegram, $address, $nat_id, $id);
        $logAction = "UPDATE_RENTER";
        $logDesc = "Updated details for: $name (ID: $id)";
    } else {
        // CREATE
        $stmt = $conn->prepare("INSERT INTO renters (renter_name, gender, mobile_phone, telegram, renter_address, nat_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $name, $gender, $mobile, $telegram, $address, $nat_id);
        $logAction = "ADD_RENTER";
        $logDesc = "Added new renter: $name";
    }

    if ($stmt->execute()) {
        $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
        $logStmt->bind_param("iss", $userId, $logAction, $logDesc);
        $logStmt->execute();

        echo json_encode(['success' => true, 'message' => ($action === 'edit' ? 'Update successful!' : 'Registration successful!')]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

// If no valid action is hit
echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);