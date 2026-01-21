<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAuth(); // Ensure only authorized users can delete

// 1. Get the Room ID from the URL safely
$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // 2. Prepare the DELETE statement (Using plural table name 'rooms')
        $stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
        
        // 3. Bind the parameter (i = integer)
        $stmt->bind_param("i", $id);
        
        // 4. Execute the command
        if ($stmt->execute()) {
            // Success: Add an audit log entry
            $userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;
            $action = "DELETE_ROOM";
            $desc = "Deleted Room ID: $id";
            
            $logStmt = $conn->prepare("INSERT INTO audit_log (user_id, action, description) VALUES (?, ?, ?)");
            $logStmt->bind_param("iss", $userId, $action, $desc);
            $logStmt->execute();

            header('Location: rooms.php?deleted=1');
            exit;
        }
    } catch (mysqli_sql_exception $e) {
        // Handle Foreign Key Constraint (e.g., room has existing bills)
        header('Location: rooms.php?error=cannot_delete_active_room');
        exit;
    }
}

// 5. Always redirect back to the rooms list if ID is missing
header('Location: rooms.php');
exit;