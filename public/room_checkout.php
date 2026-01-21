<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// auth.php usually contains requireAuth() which handles the session check more cleanly
requireAuth();

$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;
$roomId = $_GET['room_id'] ?? null;

if (!$roomId) {
    header('Location: rooms.php?error=missing_id');
    exit;
}

// 1. Start Transaction (Ensures all steps succeed or none do)
$conn->begin_transaction();

try {
    /* ===============================
        GET CURRENT ROOM INFO
    ================================ */
    // Using plural 'rooms'. "FOR UPDATE" prevents other processes from touching this row.
    $stmt = $conn->prepare("SELECT renter_id, rent_date FROM rooms WHERE room_id = ? FOR UPDATE");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();

    if (!$room || !$room['renter_id']) {
        throw new Exception('Room is not currently occupied or does not exist.');
    }

    $renterId = $room['renter_id'];
    $checkinDate = $room['rent_date'];
    $checkoutDate = date('Y-m-d');

    /* ===============================
        STEP 1: INSERT INTO ROOM HISTORY
    ================================ */
    // This archives the stay for future reporting
    $rh = $conn->prepare("
        INSERT INTO room_history (room_id, renter_id, checkin_date, checkout_date)
        VALUES (?, ?, ?, ?)
    ");

    if (!$rh) {
        throw new Exception("History Table Error: " . $conn->error);
    }

    $rh->bind_param("iiss", $roomId, $renterId, $checkinDate, $checkoutDate);
    $rh->execute();

    /* ===============================
        STEP 2: UPDATE ROOM (RESET TO VACANT)
    ================================ */
    $stmtUpdate = $conn->prepare("
        UPDATE rooms 
        SET status = 'AVAILABLE', 
            rent_date = NULL, 
            renter_id = NULL, 
            qty_person = 0 
        WHERE room_id = ?
    ");
    $stmtUpdate->bind_param("i", $roomId);
    $stmtUpdate->execute();

    /* ===============================
        STEP 3: AUDIT LOG
    ================================ */
    $description = "Checked out room ID #$roomId (Renter ID #$renterId)";
    $action = 'CHECKOUT';

    $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
    $log->bind_param("iss", $userId, $action, $description);
    $log->execute();

    // 2. All steps finished successfully - save changes to disk
    $conn->commit();

    $_SESSION['msg'] = "Checkout successful. Room history archived and unit is now available.";
    $_SESSION['msg_type'] = "success";
    header("Location: rooms.php");
    exit;

} catch (Exception $e) {
    // 3. If anything failed, undo everything (rollback)
    $conn->rollback();

    $_SESSION['msg'] = "Checkout failed: " . $e->getMessage();
    $_SESSION['msg_type'] = "error";
    header("Location: rooms.php");
    exit;
}