<?php
require_once '../includes/db.php';
include '../includes/header.php';

requireAuth();

$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';
$role = $_SESSION['user']['role'];

/* Filters */
$search = $_GET['search'] ?? '';
$monthFilter = $_GET['month'] ?? '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

/* ===============================
    BUILD WHERE CLAUSE
================================ */
$whereClauses = [];
$bindTypes = "";
$bindParams = [];

if ($search) {
    // Fixed table aliases for search
    $whereClauses[] = "(r.renter_name LIKE ? OR rm.room_number LIKE ?)";
    $searchTerm = "%$search%";
    $bindTypes .= "ss";
    $bindParams[] = $searchTerm;
    $bindParams[] = $searchTerm;
}

if ($monthFilter) {
    $whereClauses[] = "DATE_FORMAT(mb.bill_month, '%Y-%m') = ?";
    $bindTypes .= "s";
    $bindParams[] = $monthFilter;
}

$whereSQL = $whereClauses ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

/* ===============================
    FETCH SUMMARY STATS
================================ */
$summaryMonth = $monthFilter ?: date('Y-m');
// FIXED: Pluralized rooms, room_types, utility_rates
$summarySQL = "
    SELECT 
        SUM(CASE WHEN mb.is_paid = 1 THEN 1 ELSE 0 END) as paid_count,
        SUM(CASE WHEN mb.is_paid = 0 THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN mb.is_paid = 1 THEN (
            rt.base_room_fee + 
            (GREATEST(0, mb.new_electric - mb.old_electric) * ur.electric_rate) + 
            (mb.water_units * ur.water_rate)
        ) ELSE 0 END) as collected_amt 
    FROM monthly_bills mb
    JOIN rooms rm ON mb.room_id = rm.room_id
    JOIN room_types rt ON rm.room_type_id = rt.room_type_id
    JOIN utility_rates ur ON mb.rate_id = ur.rate_id
    WHERE DATE_FORMAT(mb.bill_month, '%Y-%m') = ?
";
$sStmt = $conn->prepare($summarySQL);
$sStmt->bind_param("s", $summaryMonth);
$sStmt->execute();
$summary = $sStmt->get_result()->fetch_assoc();

/* ===============================
    FETCH BILLS (Pagination)
================================ */
// FIXED: Pluralized rooms, renters
$countSQL = "SELECT COUNT(*) as total FROM monthly_bills mb JOIN rooms rm ON mb.room_id = rm.room_id LEFT JOIN renters r ON rm.renter_id = r.renter_id $whereSQL";
$cStmt = $conn->prepare($countSQL);
if ($whereClauses) {
    $cStmt->bind_param($bindTypes, ...$bindParams);
}
$cStmt->execute();
$totalBills = $cStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalBills / $limit);

// FIXED: Pluralized rooms, renters, room_types, utility_rates
$sql = "
SELECT mb.*, r.renter_name, rm.room_number, rt.base_room_fee, ur.electric_rate, ur.water_rate,
(rt.base_room_fee + (GREATEST(0, mb.new_electric - mb.old_electric) * ur.electric_rate) + (mb.water_units * ur.water_rate)) AS total
FROM monthly_bills mb
JOIN rooms rm ON mb.room_id = rm.room_id
LEFT JOIN renters r ON rm.renter_id = r.renter_id
JOIN room_types rt ON rm.room_type_id = rt.room_type_id
JOIN utility_rates ur ON mb.rate_id = ur.rate_id
$whereSQL ORDER BY mb.bill_id DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$finalBindTypes = $bindTypes . "ii";
$finalParams = array_merge($bindParams, [$limit, $offset]);
$stmt->bind_param($finalBindTypes, ...$finalParams);
$stmt->execute();
$bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 1. Get the Next Invoice ID
$lastBillResult = $conn->query("SELECT bill_id FROM monthly_bills ORDER BY bill_id DESC LIMIT 1");
$lastBill = $lastBillResult->fetch_assoc();
$nextInvoiceId = ($lastBill['bill_id'] ?? 0) + 1;
$formattedInvoice = "INV-" . date('Y') . "-" . str_pad($nextInvoiceId, 4, '0', STR_PAD_LEFT);

// 2. Prepare Rooms and Rates data (Same as before)
$roomsQuery = $conn->query("
    SELECT rm.room_id, rm.room_number, r.renter_name, 
    (SELECT new_electric FROM monthly_bills mb WHERE mb.room_id = rm.room_id ORDER BY bill_month DESC LIMIT 1) as last_e
    FROM rooms rm
    JOIN renters r ON rm.renter_id = r.renter_id
    WHERE rm.status = 'ACTIVE'
");
$roomsData = $roomsQuery ? $roomsQuery->fetch_all(MYSQLI_ASSOC) : [];

$ratesQuery = $conn->query("SELECT * FROM utility_rates ORDER BY rate_id DESC");
$ratesData = $ratesQuery ? $ratesQuery->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="px-8 max-w-full mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-4xl font-black tracking-tight">Financial Records</h1>
            <p class="text-sm opacity-60 font-medium">Manage monthly invoices and track property revenue.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button onclick="syncPayments()"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold transition flex items-center gap-2 shadow-lg shadow-emerald-500/20">
                <i class="fa-solid fa-sync-alt" id="sync-icon"></i> Sync Bakong
            </button>
            <button onclick="openBillModal()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold transition flex items-center gap-2 shadow-lg shadow-blue-500/20">
                <i class="fa-solid fa-plus"></i>Create Bill
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="stat-card border-b-4 border-green-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-[10px] uppercase font-black opacity-50 tracking-widest mb-1">Total Revenue
                        (<?= date('M', strtotime($summaryMonth . "-01")) ?>)</p>
                    <p class="text-3xl font-black tracking-tighter text-green-600">
                        $<?= number_format($summary['collected_amt'] ?? 0, 2) ?></p>
                </div>
                <div class="p-3 bg-green-500/10 text-green-500 rounded-2xl">
                    <i class="fa-solid fa-sack-dollar text-xl"></i>
                </div>
            </div>
        </div>
        <div class="stat-card border-b-4 border-blue-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-[10px] uppercase font-black opacity-50 tracking-widest mb-1">Paid Status</p>
                    <p class="text-3xl font-black tracking-tighter text-blue-600"><?= $summary['paid_count'] ?? 0 ?></p>
                </div>
                <div class="p-3 bg-blue-500/10 text-blue-500 rounded-2xl">
                    <i class="fa-solid fa-circle-check text-xl"></i>
                </div>
            </div>
        </div>
        <div class="stat-card border-b-4 border-rose-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-[10px] uppercase font-black opacity-50 tracking-widest mb-1">Awaiting Payment</p>
                    <p class="text-3xl font-black tracking-tighter text-rose-500"><?= $summary['pending_count'] ?? 0 ?>
                    </p>
                </div>
                <div class="p-3 bg-rose-500/10 text-rose-500 rounded-2xl">
                    <i class="fa-solid fa-clock text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div
        class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> border p-5 rounded-3xl shadow-sm mb-6 backdrop-blur-sm">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[250px]">
                <label class="block text-[10px] font-black uppercase opacity-50 mb-2 ml-1 tracking-widest">Search
                    Directory</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute right-4 top-1/2 -translate-y-1/2 opacity-30"></i>
                    <input name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search name or room number..." class="custom-input">
                </div>
            </div>
            <div class="w-full md:w-auto">
                <label class="block text-[10px] font-black uppercase opacity-50 mb-2 ml-1 tracking-widest">Filter
                    Month</label>
                <input type="month" name="month" value="<?= htmlspecialchars($monthFilter) ?>" class="custom-input">
            </div>
            <div class="flex gap-2 w-full md:w-auto">
                <button
                    class="flex-1 md:flex-none bg-slate-900 hover:bg-black text-white px-8 py-3 rounded-2xl font-bold transition shadow-lg shadow-slate-900/20">Apply</button>
                <a href="bills.php"
                    class="flex-1 md:flex-none bg-gray-100 hover:bg-gray-200 text-gray-600 px-6 py-3 rounded-2xl font-bold transition text-center">Reset</a>
            </div>
        </form>
    </div>

    <div
        class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2rem] overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="<?= $isDark ? 'bg-gray-900/50' : 'bg-gray-50/50' ?> text-[10px] uppercase font-black tracking-widest opacity-60">
                        <th class="px-8 py-5">Tenant / Room</th>
                        <th class="px-6 py-5">Billing Period</th>
                        <th class="px-6 py-5 text-right">Amount Due</th>
                        <th class="px-6 py-5 text-center">Payment Status</th>
                        <th class="px-8 py-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y <?= $isDark ? 'divide-gray-700' : 'divide-gray-100' ?>">
                    <?php if (empty($bills)): ?>
                        <tr>
                            <td colspan="5" class="p-20 text-center opacity-30 font-bold italic">No billing records found
                                matching your criteria.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($bills as $b): ?>
                        <tr class="hover:bg-blue-500/[0.02] transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center font-black text-slate-500 text-xs">
                                        <?= $b['room_number'] ?>
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-300 leading-tight">
                                            <?= htmlspecialchars($b['renter_name'] ?? 'Vacant Room') ?>
                                        </p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">ID:
                                            #<?= str_pad($b['bill_id'], 5, '0', STR_PAD_LEFT) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-6 font-bold text-slate-500">
                                <?= date('d/M/Y', strtotime($b['bill_month'])) ?>
                            </td>
                            <td class="px-6 py-6 text-right">
                                <p class="font-black text-blue-600 text-lg">$<?= number_format($b['total'], 2) ?></p>
                            </td>
                            <td class="px-6 py-6 text-center">
                                <?php if ($b['is_paid']): ?>
                                    <span
                                        class="inline-flex items-center gap-1.5 bg-emerald-100 text-emerald-700 text-[10px] font-black px-4 py-1.5 rounded-full ring-1 ring-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> PAID
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="inline-flex items-center gap-1.5 bg-rose-100 text-rose-700 text-[10px] font-black px-4 py-1.5 rounded-full ring-1 ring-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> UNPAID
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="bill_receipt.php?id=<?= $b['bill_id'] ?>" target="_blank"
                                        class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-2">
                                        <i class="fa-solid fa-file-invoice"></i> Receipt
                                    </a>

                                    <?php if (!$b['is_paid']): ?>
                                        <div class="relative group/menu">
                                            <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors text-gray-400">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>

                                            <div
                                                class="absolute right-0 mt-2 w-56 bg-white border border-gray-100 rounded-2xl shadow-2xl opacity-0 invisible group-hover/menu:opacity-100 group-hover/menu:visible transition-all z-50 p-2 text-left">
                                                <a href="../bakong_payments/bakong_pay.php?bill_id=<?= $b['bill_id'] ?>"
                                                    class="flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-xl transition">
                                                    <i class="fa-solid fa-qrcode w-4"></i> Bakong Pay
                                                </a>

                                                <button type="button"
                                                    onclick="handleCashPayment(<?= $b['bill_id'] ?>, <?= $b['total'] ?>, this.closest('tr'))"
                                                    class="w-full flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-green-600 hover:bg-green-50 rounded-xl transition">
                                                    <i class="fa-solid fa-money-bill-wave w-4"></i> Pay with Cash
                                                </button>
                                                <hr class="my-1 border-gray-50">

                                                <button onclick='openBillModal(<?= json_encode($b) ?>)'
                                                    class="flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50 rounded-xl transition">
                                                    <i class="fa-solid fa-pen w-4"></i> Edit Bill
                                                </button>

                                                <button
                                                    onclick="ajaxDelete(<?= $b['bill_id'] ?>, 'bill_actions.php', this.closest('tr'))"
                                                    class="flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-red-500 hover:bg-red-50 rounded-xl transition">
                                                    <i class="fa-solid fa-trash w-4"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="mt-8 flex justify-center items-center gap-2">
            <p class="text-[10px] font-black uppercase opacity-40 mr-4">Page <?= $page ?> of <?= $totalPages ?></p>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= $search ?>&month=<?= $monthFilter ?>"
                    class="w-10 h-10 flex items-center justify-center rounded-xl font-black transition-all <?= $i == $page ? 'bg-blue-600 shadow-lg shadow-blue-500/30 text-white' : 'bg-white text-slate-400 hover:bg-gray-100' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script src="assets/js/alerts.js"></script>
<script id="roomsJson" type="application/json"><?php echo json_encode($roomsData); ?></script>
<script id="ratesJson" type="application/json"><?php echo json_encode($ratesData); ?></script>
<script id="nextInvoiceJson" type="application/json"><?php echo json_encode($formattedInvoice); ?></script>

<style>
    .custom-input {
        width: 100%;
        padding: 12px 16px;
        background-color:
            <?= $isDark ? 'rgba(255,255,255,0.03)' : '#ffffff' ?>
        ;
        border: 2px solid
            <?= $isDark ? '#374151' : '#f1f5f9' ?>
        ;
        border-radius: 16px;
        color: inherit;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .custom-input:focus {
        border-color: #3b82f6;
        background-color: white;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        outline: none;
    }

    .stat-card {
        padding: 2rem;
        border-radius: 2rem;
        background-color:
            <?= $isDark ? '#1f2937' : '#ffffff' ?>
        ;
        box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.05);
    }
</style>

<?php include '../includes/footer.php'; ?>