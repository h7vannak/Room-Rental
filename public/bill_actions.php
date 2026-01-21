<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = $_SESSION['user']['id'];

try {
    $conn->begin_transaction();

    if ($action === 'save') {
        $bill_id = $_POST['bill_id'] ?? null;
        $room_id = $_POST['room_id'];
        $rate_id = $_POST['rate_id'];
        $bill_month = $_POST['bill_month'];
        $old_electric = $_POST['old_electric'];
        $new_electric = $_POST['new_electric'];
        $water_units = $_POST['water_units'];

        if ($bill_id) {
            // 1. Update Existing Bill
            $stmt = $conn->prepare("UPDATE monthly_bills SET bill_month=?, old_electric=?, new_electric=?, water_units=?, rate_id=? WHERE bill_id=? AND paid=0");
            $stmt->bind_param("sdddii", $bill_month, $old_electric, $new_electric, $water_units, $rate_id, $bill_id);
            $stmt->execute();

            // 2. Fetch the New Total directly from the source tables
            $sql_calc = "
        SELECT (rt.base_room_fee + (GREATEST(0, ? - ?) * ur.electric_rate) + (? * ur.water_rate)) AS calculated_total
        FROM monthly_bills mb
        JOIN rooms rm ON mb.room_id = rm.room_id
        JOIN room_types rt ON rm.room_type_id = rt.room_type_id
        JOIN utility_rates ur ON mb.rate_id = ur.rate_id
        WHERE mb.bill_id = ?
    ";
            $calcStmt = $conn->prepare($sql_calc);
            $calcStmt->bind_param("dddi", $new_electric, $old_electric, $water_units, $bill_id);
            $calcStmt->execute();
            $calcResult = $calcStmt->get_result()->fetch_assoc();

            if ($calcResult) {
                $new_total = $calcResult['calculated_total'];

                // 3. Sync with Payments Table
                // Check if a payment record already exists for this bill
                $checkPay = $conn->prepare("SELECT id FROM payments WHERE bill_id = ?");
                $checkPay->bind_param("i", $bill_id);
                $checkPay->execute();
                $payExists = $checkPay->get_result()->num_rows > 0;

                if ($payExists) {
                    // Update the existing amount if it's not yet successful
                    $payUpdate = $conn->prepare("UPDATE payments SET amount = ?, status = 'PENDING' WHERE bill_id = ? AND status != 'SUCCESS'");
                    $payUpdate->bind_param("di", $new_total, $bill_id);
                    $payUpdate->execute();
                } else {
                    // If the user hasn't clicked "Pay Online" yet, we don't necessarily need 
                    // a payment record, but you can create one now to be safe:
                    $payInsert = $conn->prepare("INSERT INTO payments (bill_id, amount, method, status) VALUES (?, ?, 'BAKONG', 'PENDING')");
                    $payInsert->bind_param("id", $bill_id, $new_total);
                    $payInsert->execute();
                }
            }
            $msg = "Bill updated and payment amount synchronized.";
        } else {
            // Create New Logic (Remains the same)
            $year = date('Y');
            $last = $conn->query("SELECT bill_id FROM monthly_bills ORDER BY bill_id DESC LIMIT 1")->fetch_assoc();
            $nextId = ($last['bill_id'] ?? 0) + 1;
            $invoice = "INV-$year-" . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO monthly_bills (invoice_number, room_id, rate_id, bill_month, old_electric, new_electric, water_units) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisddi", $invoice, $room_id, $rate_id, $bill_month, $old_electric, $new_electric, $water_units);
            $stmt->execute();
            $msg = "Invoice $invoice generated.";
        }
    } elseif ($action === 'delete') {
        // Delete Logic (Remains the same)
        if ($_SESSION['user']['role'] !== 'admin')
            throw new Exception("Unauthorized");
        $id = $_POST['id'];

        // Also delete any pending payments associated with this bill
        $conn->query("DELETE FROM payments WHERE bill_id = $id AND status != 'SUCCESS'");

        $stmt = $conn->prepare("DELETE FROM monthly_bills WHERE bill_id = ? AND paid = 0");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows === 0)
            throw new Exception("Cannot delete paid or missing bill.");
        $msg = "Record deleted successfully.";
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => $msg]);
    exit;
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}