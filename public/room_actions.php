<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;

try {
    $conn->begin_transaction();

    // --- SAVE / EDIT / CREATE ACTION ---
    if ($action === 'save' || $action === 'edit' || $action === 'create') {
        $room_id = $_POST['room_id'] ?? $_GET['id'] ?? null;
        $room_number = $_POST['room_number'] ?? '';
        $room_type_id = $_POST['room_type_id'] ?? '';
        $renter_id = !empty($_POST['renter_id']) ? (int) $_POST['renter_id'] : null;
        $rent_date = !empty($_POST['rent_date']) ? $_POST['rent_date'] : null;
        $qty_person = !empty($_POST['qty_person']) ? (int) $_POST['qty_person'] : 0;
        $status = $_POST['status'] ?? 'Available';

        if ($room_id) {
            $stmt = $conn->prepare("UPDATE rooms SET room_number=?, room_type_id=?, renter_id=?, rent_date=?, qty_person=?, status=? WHERE room_id=?");
            $stmt->bind_param("siisisi", $room_number, $room_type_id, $renter_id, $rent_date, $qty_person, $status, $room_id);
            $logDesc = "Updated Room #$room_number (ID: $room_id)";
            $msg = "Room updated successfully.";
        } else {
            $stmt = $conn->prepare("INSERT INTO rooms (room_number, room_type_id, renter_id, rent_date, qty_person, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisis", $room_number, $room_type_id, $renter_id, $rent_date, $qty_person, $status);
            $msg = "Room created successfully.";
        }

        if (!$stmt->execute())
            throw new Exception($conn->error);

        // Define Log Description for Create after execute to get insert_id if needed
        if (!$room_id) {
            $new_id = $conn->insert_id;
            $logDesc = "Created New Room #$room_number (ID: $new_id)";
        }

        // INSERT AUDIT LOG
        $logAction = strtoupper($action) . "_ROOM";
        $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
        $logStmt->bind_param("iss", $userId, $logAction, $logDesc);
        $logStmt->execute();

        // --- DELETE ACTION ---
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$id)
            throw new Exception("Missing Room ID");

        // Fetch room number for log before deleting
        $info = $conn->prepare("SELECT room_number FROM rooms WHERE room_id = ?");
        $info->bind_param("i", $id);
        $info->execute();
        $roomData = $info->get_result()->fetch_assoc();
        $roomNum = $roomData['room_number'] ?? 'Unknown';

        // 1. CHECK OCCUPANCY
        $statusCheck = $conn->prepare("SELECT status FROM rooms WHERE room_id = ?");
        $statusCheck->bind_param("i", $id);
        $statusCheck->execute();
        $statusResult = $statusCheck->get_result()->fetch_assoc();

        if ($statusResult && $statusResult['status'] === 'ACTIVE') {
            echo json_encode(['success' => false, 'message' => 'Cannot delete: Tenant is still checked-in (ACTIVE).']);
            exit;
        }

        // 2. DEPENDENCY CHECK
        $billCheck = $conn->prepare("SELECT bill_id FROM monthly_bills WHERE room_id = ? LIMIT 1");
        $billCheck->bind_param("i", $id);
        $billCheck->execute();
        if ($billCheck->get_result()->num_rows > 0) {
            throw new Exception("Cannot delete room: Existing billing records found.");
        }

        // 3. PROCEED WITH DELETE
        $stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute())
            throw new Exception($conn->error);

        // INSERT AUDIT LOG
        $logAction = "DELETE_ROOM";
        $logDesc = "Deleted Room #$roomNum (ID: $id)";
        $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
        $logStmt->bind_param("iss", $userId, $logAction, $logDesc);
        $logStmt->execute();

        $msg = "Room deleted successfully.";

        // --- CHECKOUT ACTION ---
    } elseif ($action === 'checkout') {
        try {
            $id = $_POST['id'] ?? null;

            // 1. Get room details and the fee using YOUR column: base_room_fee
            $stmt = $conn->prepare("
            SELECT r.renter_id, r.rent_date, rt.base_room_fee 
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.room_type_id
            WHERE r.room_id = ?
        ");

            if (!$stmt)
                throw new Exception("Select Prepare Failed: " . $conn->error);

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $room = $stmt->get_result()->fetch_assoc();

            if (!$room || !$room['renter_id']) {
                throw new Exception("This room is already vacant or does not exist.");
            }

            // 2. Pro-rated Calculation
            $checkin = new DateTime($room['rent_date']);
            $checkout = new DateTime();

            // Calculate days stayed
            $interval = $checkin->diff($checkout);
            $daysStayed = $interval->days;
            if ($daysStayed < 1) {
                $daysStayed = 1;
            }

            // Get days in the specific current month
            $daysInThisMonth = (int) date('t');

            // Formula: (Monthly Fee / Days in Month) * Days Stayed
            $totalCharge = ($room['base_room_fee'] / $daysInThisMonth) * $daysStayed;

            // 3. Archive to room_history
            $historySql = "INSERT INTO room_history (room_id, renter_id, checkin_date, checkout_date, final_amount) 
                       VALUES (?, ?, ?, CURDATE(), ?)";

            $histStmt = $conn->prepare($historySql);

            if (!$histStmt) {
                throw new Exception("History Archive Prepare Failed: " . $conn->error);
            }

            // Binding: i=room_id, i=renter_id, s=checkin_date, d=totalCharge
            $histStmt->bind_param("iisd", $id, $room['renter_id'], $room['rent_date'], $totalCharge);
            $histStmt->execute();

            // 4. Reset the Room in the rooms table
            $update = $conn->prepare("UPDATE rooms SET status = 'AVAILABLE', renter_id = NULL, rent_date = NULL WHERE room_id = ?");
            $update->bind_param("i", $id);
            $update->execute();

            echo json_encode([
                'success' => true,
                'message' => "Checkout complete. Charged for $daysStayed days in a $daysInThisMonth-day month. Total: $" . number_format($totalCharge, 2)
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    $conn->commit();
    echo json_encode(['success' => true, 'message' => $msg]);

} catch (Exception $e) {
    if ($conn->connect_errno == 0)
        $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}