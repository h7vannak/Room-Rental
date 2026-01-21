<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bill_id = $_POST['bill_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    
    try {
        $conn->begin_transaction();
        
        // Update bill status
        $stmt1 = $conn->prepare("UPDATE monthly_bills SET paid = 1 WHERE bill_id = ?");
        $stmt1->bind_param("i", $bill_id);
        $stmt1->execute();

        // Insert payment record
        $stmt2 = $conn->prepare("INSERT INTO payments (bill_id, amount, method, status) VALUES (?, ?, 'CASH', 'SUCCESS') ON DUPLICATE KEY UPDATE status='SUCCESS'");
        $stmt2->bind_param("id", $bill_id, $amount);
        $stmt2->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Cash payment recorded!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}