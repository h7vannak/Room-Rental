<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

// 1. DATABASE FETCHING
$bill_id = $_GET['bill_id'] ?? null;
if (!$bill_id)
    die("Bill ID missing.");

// Updated query to ensure accurate total calculation
$sql = "
SELECT 
    mb.bill_id,
    mb.invoice_number,
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
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$bill_data = $stmt->get_result()->fetch_assoc();

if (!$bill_data)
    die("Bill not found.");

// 2. CONFIGURATION & DUAL CURRENCY CALCULATION
$bakong_id = "chan_vannak@bkrt";
$merchant_name = "ROOM RENTAL SYSTEM";
$merchant_city = "PHNOM PENH";

// Primary Currency: USD (840)
$amount_usd = number_format($bill_data['total'], 2, '.', '');
$currency_usd = "840";

// Alternative Currency: KHR (116)
$exchange_rate = 4100;
$amount_khr = (string) round($bill_data['total'] * $exchange_rate, -2); // Round to nearest 100 Riel

// 3. KHQR FORMATTING FUNCTIONS
function crc16_khqr($data)
{
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $x = (($crc >> 8) ^ ord($data[$i])) & 0xFF;
        $x ^= $x >> 4;
        $crc = (($crc << 8) ^ ($x << 12) ^ ($x << 5) ^ $x) & 0xFFFF;
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function formatTag($id, $value)
{
    return $id . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

// 4. BUILD THE KHQR STRING
$merchant_info = formatTag("00", $bakong_id);
$tag29 = formatTag("29", $merchant_info);

// Tag 62: Additional Data (Reference Label / Bill ID)
$bill_tag = formatTag("01", (string) $bill_id);
$tag62 = formatTag("62", $bill_tag);

// Tag 55: Alternative Currency (KHR 116 + Amount)
// This allows the banking app to offer the KHR payment option
$tag55_val = "116" . $amount_khr;
$tag55 = formatTag("55", $tag55_val);

/**
 * PAYLOAD CONSTRUCTION
 * 010212: Point of Initiation Method set to 12 (Dynamic QR)
 * 5303840: Currency set to USD
 * 54: Amount in USD
 * 55: Secondary Currency/Amount (KHR)
 */
$payload = "000201" .
    "010212" .
    $tag29 .
    "52045999" .
    "5303" . $currency_usd .
    formatTag("54", $amount_usd) .
    $tag55 .
    "5802KH" .
    formatTag("59", $merchant_name) .
    formatTag("60", $merchant_city) .
    $tag62 .
    "6304";

$final_qr_string = $payload . crc16_khqr($payload);
$md5_hash = md5($final_qr_string);

// 5. UPDATE PAYMENTS TABLE
$pay_stmt = $conn->prepare("
    INSERT INTO payments (bill_id, amount, method, status, qr_string) 
    VALUES (?, ?, 'BAKONG', 'PENDING', ?) 
    ON DUPLICATE KEY UPDATE qr_string = ?
");
$pay_stmt->bind_param("idss", $bill_id, $amount_usd, $final_qr_string, $final_qr_string);
$pay_stmt->execute();
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-sm rounded-[2.5rem] shadow-2xl border border-gray-100 overflow-hidden">
        <div class="bg-[#E1232E] p-8 flex justify-center">
            <img src="./assets/KHQR_Logo.png" class="w-28" alt="KHQR">
        </div>

        <div class="px-10 py-8 text-center bg-white">
            <p class="text-[11px] text-gray-400 uppercase font-black tracking-widest mb-2">
                <?= htmlspecialchars($merchant_name); ?>
            </p>
            <div class="flex flex-col gap-2">
                <div class="flex justify-center items-baseline gap-1">
                    <span class="text-xl font-bold text-gray-900">$</span>
                    <h1 class="text-5xl font-black text-gray-900"><?= number_format($amount_usd, 2); ?></h1>
                </div>
                <div class="inline-flex items-center justify-center gap-2 px-4 py-1 bg-emerald-50 rounded-full mx-auto">
                    <span class="text-emerald-700 font-bold text-lg">៛ <?= number_format($amount_khr); ?></span>
                </div>
                <p class="text-[10px] text-gray-400 font-bold mt-2 italic">Exchange Rate: $1 =
                    <?= number_format($exchange_rate); ?> Riel
                </p>
            </div>
        </div>

        <div class="px-10 pb-10 text-center">
            <div class="relative group">
                <div
                    class="absolute -inset-1 bg-gradient-to-r from-red-500 to-red-700 rounded-3xl blur opacity-10 group-hover:opacity-20 transition duration-1000">
                </div>
                <div class="relative bg-white p-5 border-2 border-gray-50 rounded-[2rem] shadow-sm mb-8">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?= urlencode($final_qr_string); ?>&size=300x300"
                        class="w-full aspect-square" alt="Scan to Pay">
                </div>
            </div>

            <div id="status-display"
                class="flex items-center justify-center gap-3 px-6 py-4 bg-blue-50 text-blue-700 text-xs font-black rounded-2xl border border-blue-100 shadow-sm">
                <div class="w-2 h-2 bg-blue-600 rounded-full animate-ping"></div>
                <span>SCAN TO PAY (USD OR KHR)</span>
            </div>

            <p class="mt-6 text-[10px] text-gray-400 font-medium">Invoice: <span
                    class="text-gray-600 font-bold"><?= $bill_data['invoice_number']; ?></span></p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Polling to check if payment is successful
    let polling = setInterval(() => {
        fetch('verify_status.php?md5=<?= $md5_hash ?>&bill=<?= $bill_id ?>')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'SUCCESS') {
                    clearInterval(polling);
                    Swal.fire({
                        title: 'Payment Received!',
                        text: 'Thank you. Your invoice has been updated.',
                        icon: 'success',
                        confirmButtonColor: '#E1232E',
                        confirmButtonText: 'View Bills',
                        customClass: {
                            popup: 'rounded-[2rem]'
                        }
                    }).then(() => {
                        location.href = '../public/bills.php';
                    });
                }
            })
            .catch(err => console.error("Polling error:", err));
    }, 3000);
</script>

<?php include '../includes/footer.php'; ?>