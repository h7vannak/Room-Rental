<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$id = $_POST['id'] ?? null;
$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;

if ($action === 'create' || $action === 'edit') {
    $name = $_POST['name'] ?? '';
    $fee = $_POST['fee'] ?? 0;

    if ($action === 'edit' && $id) {
        $stmt = $conn->prepare("UPDATE room_types SET room_type_name=?, base_room_fee=? WHERE room_type_id=?");
        $stmt->bind_param("sdi", $name, $fee, $id);
        $msg = "Category updated!";
    } else {
        $stmt = $conn->prepare("INSERT INTO room_types (room_type_name, base_room_fee) VALUES (?, ?)");
        $stmt->bind_param("sd", $name, $fee);
        $msg = "Category created!";
    }

    if ($stmt->execute()) {
        // Simple audit log
        $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'ROOM_TYPE_ACTION', ?)");
        $desc = "$action category: $name";
        $log->bind_param("is", $userId, $desc);
        $log->execute();

        echo json_encode(['success' => true, 'message' => $msg]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}
$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;

// --- 2. DELETE LOGIC ---
if ($action === 'delete' && $id) {
    // Check if rooms are using this type
    $check = $conn->prepare("SELECT room_id FROM rooms WHERE room_type_id = ? LIMIT 1");
    $check->bind_param("i", $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete: Rooms are still assigned to this category.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM room_types WHERE room_type_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'DELETE_ROOM_TYPE', ?)");
        $desc = "Removed Room Type ID: $id";
        $log->bind_param("is", $userId, $desc);
        $log->execute();
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    exit;
}