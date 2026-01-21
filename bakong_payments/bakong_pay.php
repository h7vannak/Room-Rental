<?php
// 1. DATABASE CONNECTION
require_once '../includes/db.php'; // Uses $conn
require_once '../includes/header.php';

// Fetch the specific bill details from the database
$bill_id = $_GET['bill_id'] ?? null;
if (!$bill_id) {
    die("Bill ID missing.");
}

// SQL UPDATED: Pluralized table names (monthly_bills, rooms, room_types, utility_rates)
$sql = "
SELECT 
    mb.bill_id,
    rt.base_room_fee +
    ((mb.new_electric - mb.old_electric) * ur.electric_rate) +
    (mb.water_units * ur.water_rate) AS total
FROM monthly_bills mb
JOIN rooms rm ON mb.room_id = rm.room_id
JOIN room_types rt ON rm.room_type_id = rt.room_type_id
JOIN utility_rates ur ON mb.rate_id = ur.rate_id
WHERE mb.bill_id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("i", $bill_id);
$stmt->execute();
$result = $stmt->get_result();
$bill_data = $result->fetch_assoc();

if (!$bill_data) {
    die("Bill not found.");
}

// 2. BAKONG CONFIGURATION
$bakong_id = "chan_vannak@bkrt"; 
$merchant_name = "ROOM RENTAL SYSTEM";
$merchant_city = "PHNOM PENH";
$amount = number_format($bill_data['total'], 2, '.', ''); 
$currency = "840"; 
$bill_no = $bill_id;

// 3. CRC16 & TAG FORMATTING (KHQR Standard)
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

// 4. BUILD THE KHQR STRING
$merchant_info = formatTag("00", $bakong_id);
$tag29 = formatTag("29", $merchant_info);
$bill_tag = formatTag("01", $bill_no);
$tag62 = formatTag("62", $bill_tag);

$payload = "000201010212" . $tag29 . "520459995303" . $currency . formatTag("54", $amount) . "5802KH" . formatTag("59", $merchant_name) . formatTag("60", $merchant_city) . $tag62 . "6304";
$final_qr_string = $payload . crc16_khqr($payload);
$md5_hash = md5($final_qr_string);

// 5. UPDATE OR INSERT INTO PAYMENTS TABLE
// Assuming 'payments' table name is correct, but added safety check
$pay_stmt = $conn->prepare("
    INSERT INTO payments (bill_id, amount, method, status, qr_string) 
    VALUES (?, ?, 'BAKONG', 'PENDING', ?) 
    ON DUPLICATE KEY UPDATE qr_string = ?
");

if (!$pay_stmt) {
    die("Payment Table Error: " . $conn->error);
}

$pay_stmt->bind_param("isss", $bill_no, $amount, $final_qr_string, $final_qr_string);
$pay_stmt->execute();
?>

<div class="m-8 flex justify-center">
    <div class="bg-white w-full max-w-sm rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="bg-[#E1232E] p-6 flex justify-center"><img src="./assets/KHQR_Logo.png" class="w-24"></div>
        <div class="px-10 py-6 text-center">
            <p class="text-[10px] text-gray-400 uppercase font-bold mb-1"><?= $merchant_name; ?></p>
            <div class="flex justify-center items-baseline gap-1">
                <span class="text-sm font-bold text-gray-500">$</span>
                <h1 class="text-4xl font-black text-gray-800"><?= number_format($amount, 2); ?></h1>
                <span class="text-xs font-bold text-gray-400">USD</span>
            </div>
        </div>
        <div class="m-10 text-center">
            <div class="inline-block p-4 bg-white border-2 border-gray-100 rounded-2xl mb-6">
                <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($final_qr_string); ?>&size=250x250" class="w-full">
            </div>
            <div id="status-display" class="px-6 py-3 bg-yellow-50 text-yellow-600 text-[10px] font-bold rounded-full animate-pulse border border-yellow-100">
                <i class="fas fa-spinner fa-spin mr-2"></i> WAITING FOR PAYMENT...
            </div>
        </div>
    </div>
</div>

<script>
    let polling = setInterval(() => {
        fetch('verify_status.php?md5=<?= $md5_hash ?>&bill=<?= $bill_id ?>')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'SUCCESS') {
                    clearInterval(polling);
                    Swal.fire({ title: 'Success!', text: 'Payment Updated.', icon: 'success' })
                    .then(() => location.href = '../public/bills.php');
                }
            });
    }, 3000);
</script>
<?php include '../includes/footer.php'; ?>