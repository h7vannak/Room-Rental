<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAuth();

$id = $_GET['id'] ?? null;

if ($id) {
    // 1. Check dependency: Are any rooms still assigned to this type?
    // Using plural 'rooms' table
    $checkStmt = $conn->prepare("SELECT COUNT(*) as total FROM rooms WHERE room_type_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $count = $checkStmt->get_result()->fetch_assoc()['total'];

    if ($count == 0) {
        // 2. Get name for the log BEFORE deleting (Using plural 'room_types')
        $nameStmt = $conn->prepare("SELECT room_type_name FROM room_types WHERE room_type_id = ?");
        $nameStmt->bind_param("i", $id);
        $nameStmt->execute();
        $typeData = $nameStmt->get_result()->fetch_assoc();
        $typeName = $typeData['room_type_name'] ?? 'Unknown';

        // 3. Delete the record
        $deleteStmt = $conn->prepare("DELETE FROM room_types WHERE room_type_id = ?");
        $deleteStmt->bind_param("i", $id);

        if ($deleteStmt->execute()) {
            // 4. Audit Log Block
            $userId = $_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? null;
            $action = "DELETE_ROOM_TYPE";
            $desc = "Deleted category: $typeName (ID: $id)";

            if ($userId) {
                $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
                $log->bind_param("iss", $userId, $action, $desc);
                if (!$log->execute()) {
                    error_log("Audit Log Failed: " . $conn->error);
                }
            }

            $_SESSION['msg'] = "Category '$typeName' deleted successfully.";
            $_SESSION['msg_type'] = "success";
        }
    } else {
        // Prevent deletion if rooms are linked
        $_SESSION['msg'] = "Action Denied: $count rooms are currently assigned to '$typeName'. Reassign those rooms before deleting this category.";
        $_SESSION['msg_type'] = "error";
    }
}

header('Location: room_types.php');
exit;