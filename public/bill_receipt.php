<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAuth();

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die("Invalid Invoice ID");
}

/* ===============================
    1. FETCH COMPREHENSIVE DATA
================================ */
// SQL FIXED: Pluralized all table names
$sql = "SELECT mb.*, 
               rm.room_number, 
               r.renter_name, 
               rt.base_room_fee, 
               ur.electric_rate, 
               ur.water_rate
        FROM monthly_bills mb
        JOIN rooms rm ON mb.room_id = rm.room_id
        JOIN renters r ON rm.renter_id = r.renter_id
        JOIN room_types rt ON rm.room_type_id = rt.room_type_id
        JOIN utility_rates ur ON mb.rate_id = ur.rate_id
        WHERE mb.bill_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database Error: " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$bill = $stmt->get_result()->fetch_assoc();

if (!$bill) {
    die("Invoice not found in system.");
}

/* ===============================
    2. CALCULATIONS
================================ */
$eUsage = max(0, (float) $bill['new_electric'] - (float) $bill['old_electric']);
$eTotal = $eUsage * (float) $bill['electric_rate'];
$wTotal = (float) $bill['water_units'] * (float) $bill['water_rate'];
$grandTotal = (float) $bill['base_room_fee'] + $eTotal + $wTotal;

$issuedDate = isset($bill['created_at']) ? $bill['created_at'] : $bill['bill_month'];

/* ===============================
    3. VALID KHQR GENERATION
================================ */
$bakong_id = "chan_vannak@bkrt"; 
$merchant_name = "ROOM RENTAL SYSTEM";
$merchant_city = "PHNOM PENH";
$amount = number_format($grandTotal, 2, '.', ''); 
$currency = "840"; // USD
$bill_no = $id;

// KHQR Formatting Functions
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

// Build Payload (EMVCo Standard)
$merchant_info = formatTag("00", $bakong_id);
$tag29 = formatTag("29", $merchant_info);
$bill_tag = formatTag("01", $bill_no);
$tag62 = formatTag("62", $bill_tag);

$payload = "000201010212" . $tag29 . "520459995303" . $currency . formatTag("54", $amount) . "5802KH" . formatTag("59", $merchant_name) . formatTag("60", $merchant_city) . $tag62 . "6304";
$final_qr_string = $payload . crc16_khqr($payload);

// Generate the Image URL using the EMVCo string
$receiptQrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($final_qr_string);

// Link for the "Pay Now" button to use your polling logic
$paymentPageUrl = "../bakong_payments/bakong_pay.php?bill_id=" . $id;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice_<?= $bill['invoice_number'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            .receipt-card { border: none !important; box-shadow: none !important; margin: 0 !important; width: 100% !important; }
        }
    </style>
</head>
<body class="bg-gray-100 p-4 md:p-10 text-slate-800">

    <div class="max-w-3xl mx-auto no-print mb-6 flex justify-between items-center">
        <a href="bills.php" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Back to Bills
        </a>
        <div class="flex gap-3">
            <?php if (!$bill['is_paid']): ?>
                <a href="<?= $paymentPageUrl ?>" class="bg-red-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-red-500/30 hover:bg-red-700 transition flex items-center gap-2">
                    <i class="fa-solid fa-wallet"></i> Pay Online
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="max-w-3xl mx-auto bg-white rounded-[2rem] shadow-2xl overflow-hidden receipt-card border border-gray-100">
        
        <div class="bg-slate-900 p-8 md:p-12 text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tighter uppercase">Invoice</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="bg-blue-500 text-[10px] px-2 py-0.5 rounded font-bold uppercase">Official Receipt</span>
                    <p class="text-slate-400 text-xs font-mono"><?= $bill['invoice_number'] ?></p>
                </div>
            </div>
            <div class="text-left md:text-right">
                <h2 class="font-bold text-xl">Phnom Penh Property</h2>
                <p class="text-xs text-slate-400 font-medium">Street 123, Toul Tom Poung I</p>
                <p class="text-xs text-slate-400 font-medium">Tel: +855 12 345 678</p>
            </div>
        </div>

        <div class="p-8 md:p-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                <div>
                    <p class="text-[10px] uppercase font-black text-blue-500 mb-2 tracking-widest">Billed To</p>
                    <p class="font-black text-slate-800 text-2xl"><?= htmlspecialchars($bill['renter_name']) ?></p>
                    <p class="text-slate-500 font-bold flex items-center gap-2 mt-1">
                        <i class="fa-solid fa-door-open text-xs text-blue-400"></i> Room <?= $bill['room_number'] ?>
                    </p>
                </div>
                <div class="md:text-right">
                    <p class="text-[10px] uppercase font-black text-slate-400 mb-2 tracking-widest">Payment Period</p>
                    <p class="font-bold text-slate-800 text-lg"><?= date('F Y', strtotime($bill['bill_month'])) ?></p>
                    <p class="text-xs text-slate-500">Issued: <?= date('d M, Y', strtotime($issuedDate)) ?></p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full mb-8">
                    <thead>
                        <tr class="border-b-2 border-slate-100 text-[10px] uppercase font-black text-slate-400">
                            <th class="text-left py-4">Description</th>
                            <th class="text-center py-4 uppercase">Quantity/Usage</th>
                            <th class="text-right py-4 uppercase">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr>
                            <td class="py-6">
                                <p class="font-bold text-slate-800 text-sm">ROOM RENTAL FEE</p>
                                <p class="text-[10px] text-slate-400 mt-0.5 italic">Standard monthly stay</p>
                            </td>
                            <td class="text-center text-slate-400 font-mono text-xs">1 Month</td>
                            <td class="text-right font-black text-slate-800 text-lg">$<?= number_format($bill['base_room_fee'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="py-6">
                                <p class="font-bold text-slate-800 text-sm">ELECTRICITY USAGE</p>
                                <p class="text-[10px] text-slate-400 mt-0.5 italic tracking-tight">Reading: <?= $bill['old_electric'] ?> → <?= $bill['new_electric'] ?></p>
                            </td>
                            <td class="text-center text-sm font-bold text-slate-600"><?= $eUsage ?> kWh</td>
                            <td class="text-right font-black text-slate-800 text-lg">$<?= number_format($eTotal, 2) ?></td>
                        </tr>
                        <tr>
                            <td class="py-6">
                                <p class="font-bold text-slate-800 text-sm">WATER CONSUMPTION</p>
                                <p class="text-[10px] text-slate-400 mt-0.5 italic tracking-tight">Rate: $<?= number_format($bill['water_rate'], 2) ?>/m³</p>
                            </td>
                            <td class="text-center text-sm font-bold text-slate-600"><?= $bill['water_units'] ?> m³</td>
                            <td class="text-right font-black text-slate-800 text-lg">$<?= number_format($wTotal, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-12 pt-8 border-t-4 border-slate-900 flex flex-col md:flex-row justify-between items-center gap-10">
                <div class="text-center md:text-left">
                    <p class="text-[10px] uppercase font-black text-slate-400 mb-1 tracking-widest">Invoice Status</p>
                    <?php if ($bill['is_paid']): ?>
                        <div class="flex items-center gap-2 text-emerald-600">
                            <i class="fa-solid fa-circle-check text-2xl"></i>
                            <span class="font-black text-4xl uppercase italic tracking-tighter">PAID</span>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-2 text-rose-500">
                            <i class="fa-solid fa-clock text-2xl"></i>
                            <span class="font-black text-4xl uppercase italic tracking-tighter">UNPAID</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col md:flex-row items-center gap-8">
                    <?php if (!$bill['is_paid']): ?>
                    <div class="text-center">
                        <div class="p-4 bg-white border-2 border-red-500 rounded-[1.5rem] shadow-xl shadow-red-500/10">
                            <img src="<?= $receiptQrUrl ?>" alt="Scan to Pay with KHQR" class="w-28 h-28">
                        </div>
                        <div class="mt-2">
                             <img src="./assets/KHQR_Logo.png" class="h-3 mx-auto mb-1" onerror="this.style.display='none'">
                             <p class="text-[8px] font-black text-red-600 uppercase tracking-[0.1em]">Scan to Pay (KHQR)</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="text-center md:text-right">
                        <p class="text-[10px] uppercase font-black text-slate-400 mb-1 tracking-widest">Total Amount Due</p>
                        <p class="text-5xl font-black text-blue-600 tracking-tighter">
                            $<?= number_format($grandTotal, 2) ?>
                        </p>
                        <p class="text-[9px] text-slate-400 mt-2 font-bold uppercase tracking-widest italic leading-tight">
                            Thank you for your stay!<br>Please pay by the 5th of the month.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 p-6 text-center border-t border-slate-100">
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em]">This is a computer-generated document. No signature required.</p>
        </div>
    </div>
    
    <p class="text-center text-slate-300 text-[9px] mt-8 no-print uppercase tracking-[0.3em]">
        Rental Management System v2.0
    </p>

</body>
</html>