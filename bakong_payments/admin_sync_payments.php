<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Only allow Admins to sync
requireAuth();
if ($_SESSION['user']['role'] !== 'admin') {
    die("Unauthorized");
}

/* ============================================================
    1. CONFIGURATION (Must match your KHQR Receipt settings)
   ============================================================ */
$api_token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJkYXRhIjp7ImlkIjoiMzhhMjQ1MmM2MTJjNGMyZiJ9LCJpYXQiOjE3Njc5NzE3NDksImV4cCI6MTc3NTc0Nzc0OX0.5v4K0qct-55-2PzQuVhEXziHtsUd7mpfhnirNeH-9rw"; 
$bakong_id = "chan_vannak@bkrt"; 
$merchant_name = "ROOM RENTAL SYSTEM";
$merchant_city = "PHNOM PENH";

/* ============================================================
    2. HELPER FUNCTIONS
   ============================================================ */
function crc16_khqr($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
        $x ^= $x >> 4;
        $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function formatTag($id, $value) {
    return $id . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

/* ============================================================
    3. FETCH UNPAID BILLS
   ============================================================ */
// SQL FIXED: Pluralized all table names
$sql = "SELECT mb.*, rt.base_room_fee, ur.electric_rate, ur.water_rate 
        FROM monthly_bills mb
        JOIN rooms rm ON mb.room_id = rm.room_id
        JOIN room_types rt ON rm.room_type_id = rt.room_type_id
        JOIN utility_rates ur ON mb.rate_id = ur.rate_id
        WHERE mb.paid = 0";

$result = $conn->query($sql);

if (!$result) {
    die("Query Failed: " . $conn->error);
}

$updatedCount = 0;
$errors = [];

if ($result->num_rows > 0) {
    while ($bill = $result->fetch_assoc()) {
        
        // --- A. RE-CALCULATE TOTAL ---
        $eUsage = max(0, (float)$bill['new_electric'] - (float)$bill['old_electric']);
        $grandTotal = (float)$bill['base_room_fee'] + ($eUsage * (float)$bill['electric_rate']) + ((float)$bill['water_units'] * (float)$bill['water_rate']);
        $amount = number_format($grandTotal, 2, '.', '');
        $bill_id = (string)$bill['bill_id'];

        // --- B. RE-GENERATE QR STRING & MD5 HASH ---
        $tag29 = formatTag("29", formatTag("00", $bakong_id));
        $tag62 = formatTag("62", formatTag("01", $bill_id));
        $payload = "000201010212" . $tag29 . "520459995303840" . formatTag("54", $amount) . "5802KH" . formatTag("59", $merchant_name) . formatTag("60", $merchant_city) . $tag62 . "6304";
        $qr_string = $payload . crc16_khqr($payload);
        $md5_hash = md5($qr_string);

        // --- C. CALL BAKONG API ---
        $ch = curl_init("https://api-bakong.nbc.gov.kh/v1/check_transaction_by_md5");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $api_token,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['md5' => $md5_hash]));
        
        $resBody = curl_exec($ch);
        $data = json_decode($resBody, true);
        curl_close($ch);

        // --- D. IF PAID: UPDATE BOTH TABLES ---
        if (isset($data['responseCode']) && ($data['responseCode'] === 0 || $data['responseCode'] === "0")) {
            
            $conn->begin_transaction();

            try {
                // 1. SQL FIXED: Pluralized monthly_bills
                $updateBill = $conn->prepare("UPDATE monthly_bills SET paid = 1 WHERE bill_id = ?");
                $updateBill->bind_param("i", $bill['bill_id']);
                $updateBill->execute();

                // 2. Insert record into payments table
                // Note: Ensure your payments table exists as plural if others are
                $insertPay = $conn->prepare("INSERT INTO payments (bill_id, amount, method, status, bakong_hash, created_at) VALUES (?, ?, 'BAKONG', 'SUCCESS', ?, NOW())");
                
                // FIXED: bill_id (i), amount (d), md5_hash (s)
                $insertPay->bind_param("ids", $bill['bill_id'], $amount, $md5_hash);
                $insertPay->execute();

                $conn->commit();
                $updatedCount++;
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "Error processing Bill #$bill_id: " . $e->getMessage();
            }
        }
    }
}

/* ============================================================
    4. RESPONSE
   ============================================================ */
header('Content-Type: text/plain');
if ($updatedCount > 0) {
    echo "Success! $updatedCount payments verified and added to history.";
} else {
    if (!empty($errors)) {
        echo "Sync complete with errors: " . implode(", ", $errors);
    } else {
        echo "No new payments found. All records are up to date.";
    }
}