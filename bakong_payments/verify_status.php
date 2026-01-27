<?php
// 1. DATABASE CONNECTION
require_once '../includes/db.php'; // Uses $conn
include '../includes/telegram.php'; // Ensure sendTelegram() is defined here

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
// Fixed: Using 'is_paid' and 'monthly_bills' to match your SQL dump
$stmt = $conn->prepare("
    SELECT p.status, p.amount, m.is_paid 
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

// If already marked as success, stop here
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
// Logic for Dual Currency Check
$expected_usd = floatval($payment['amount']);
$expected_khr = round($expected_usd * 4100, -2); // Your exchange rate logic

if (isset($data['responseCode']) && $data['responseCode'] === 0) {

    $received_amount = floatval($data['data']['amount']);

    // Check if received matches either USD amount OR Riel amount
    if ($received_amount == $expected_usd || $received_amount == $expected_khr) {

        $bakong_hash = $data['data']['hash'];
        $currency_received = ($received_amount > $expected_usd) ? "KHR" : "USD";

        $conn->begin_transaction();

        try {
            // Re-check status inside transaction
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

            // Update monthly_bills table (using 'is_paid' to match your DB)
            $updateBill = $conn->prepare("UPDATE monthly_bills SET is_paid=1 WHERE bill_id=?");
            $updateBill->bind_param("i", $bill);
            $updateBill->execute();

            $conn->commit();

            // 🔔 TELEGRAM NOTIFICATION
            $currentTime = date('Y-m-d H:i:s');
            $msg = "💰 <b>Payment Successful</b>\n\n";
            $msg .= "🧾 Bill ID: <b>$bill</b>\n";
            $msg .= "💵 Amount Received: <b>" . number_format($received_amount, ($currency_received == "USD" ? 2 : 0)) . " $currency_received</b>\n";
            $msg .= "🔐 Hash: <code>$bakong_hash</code>\n";
            $msg .= "⏰ Time: <b>$currentTime</b>";

            if (function_exists('sendTelegram')) {
                sendTelegram($msg);
            }

            echo json_encode(['status' => 'SUCCESS']);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'ERROR', 'msg' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'PENDING', 'msg' => 'Amount mismatch']);
    }
} else {
    echo json_encode(['status' => 'PENDING']);
}