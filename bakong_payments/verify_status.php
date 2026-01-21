<?php
// 1. DATABASE CONNECTION
require_once '../includes/db.php'; // Uses $conn
include '../includes/telegram.php'; // Uncomment if file exists

header('Content-Type: application/json');

$md5 = $_GET['md5'] ?? '';
$bill = $_GET['bill'] ?? '';

if (!$md5 || !$bill) {
    echo json_encode(['status' => 'ERROR', 'msg' => 'Invalid request params']);
    exit;
}

/* ===============================
    1. FETCH BILL + PAYMENT INFO
================================ */
// SQL FIXED: Changed monthly_bill to monthly_bills
$stmt = $conn->prepare("
    SELECT p.status, p.amount, m.paid 
    FROM payments p 
    JOIN monthly_bills m ON m.bill_id = p.bill_id 
    WHERE p.bill_id = ?
");

if (!$stmt) {
    echo json_encode(['status' => 'ERROR', 'msg' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $bill);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    echo json_encode(['status' => 'ERROR', 'msg' => 'Payment record not found']);
    exit;
}

// If already marked as success in our DB, tell the frontend immediately
if ($payment['status'] === 'SUCCESS') {
    echo json_encode(['status' => 'SUCCESS']);
    exit;
}

/* ===============================
    2. CALL BAKONG API (NBC)
================================ */
$token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJkYXRhIjp7ImlkIjoiMzhhMjQ1MmM2MTJjNGMyZiJ9LCJpYXQiOjE3Njc5NzE3NDksImV4cCI6MTc3NTc0Nzc0OX0.5v4K0qct-55-2PzQuVhEXziHtsUd7mpfhnirNeH-9rw";
$url = "https://api-bakong.nbc.gov.kh/v1/check_transaction_by_md5";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode(['md5' => $md5])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);

/* ===============================
    3. VALIDATE & UPDATE DATABASE
================================ */
if (
    isset($data['responseCode']) &&
    $data['responseCode'] === 0 &&
    isset($data['data']['amount']) &&
    floatval($data['data']['amount']) == floatval($payment['amount'])
) {
    $bakong_hash = $data['data']['hash'];

    $conn->begin_transaction();

    try {
        // Re-check status inside transaction to prevent race conditions
        $check = $conn->prepare("SELECT status FROM payments WHERE bill_id = ? FOR UPDATE");
        $check->bind_param("i", $bill);
        $check->execute();
        $currentStatus = $check->get_result()->fetch_assoc()['status'];

        if ($currentStatus === 'SUCCESS') {
            $conn->commit();
            echo json_encode(["status" => "SUCCESS"]);
            exit;
        }

        // Update Payment Table
        $updatePay = $conn->prepare("UPDATE payments SET status='SUCCESS', bakong_hash=? WHERE bill_id=? AND status='PENDING'");
        $updatePay->bind_param("si", $bakong_hash, $bill);
        $updatePay->execute();

        // SQL FIXED: Changed monthly_bill to monthly_bills
        $updateBill = $conn->prepare("UPDATE monthly_bills SET paid=1 WHERE bill_id=?");
        if (!$updateBill) throw new Exception("Bill update prepare failed: " . $conn->error);
        
        $updateBill->bind_param("i", $bill);
        $updateBill->execute();

        $conn->commit();

        // 🔔 TELEGRAM NOTIFICATION
        $currentTime = date('Y-m-d H:i:s');
        $msg = "💰 <b>Payment Successful</b>\n\n🧾 Bill ID: <b>$bill</b>\n💵 Amount: <b>{$payment['amount']} USD</b>\n🔐 Hash: <code>$bakong_hash</code>\n⏰ Time: <b>$currentTime</b>";

        if (function_exists('sendTelegram')) {
            sendTelegram($msg);
        }

        echo json_encode(['status' => 'SUCCESS']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'ERROR', 'msg' => $e->getMessage()]);
    }

} else {
    echo json_encode(['status' => 'PENDING']);
}