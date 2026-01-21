<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
include '../includes/header.php';

requireAuth();

$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

/* ===============================
    OVERDUE / UNPAID ALERTS
================================ */
$currentMonth = date('m');
$currentYear = date('Y');

// Fixed: Changed 'room' to 'rooms' and 'renter' to 'renters'
$overdueSql = "
SELECT rm.room_number, r.renter_name, r.mobile_phone, mb.paid
FROM rooms rm
JOIN renters r ON rm.renter_id = r.renter_id
LEFT JOIN monthly_bills mb ON rm.room_id = mb.room_id 
    AND MONTH(mb.bill_month) = ? 
    AND YEAR(mb.bill_month) = ?
WHERE rm.status = 'ACTIVE' 
AND (mb.paid = 0 OR mb.paid IS NULL)
";

$oStmt = $conn->prepare($overdueSql);
if (!$oStmt) { die("Overdue SQL Error: " . $conn->error); }
$oStmt->bind_param("ii", $currentMonth, $currentYear);
$oStmt->execute();
$overdueRooms = $oStmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ===============================
    READ FILTERS
================================ */
$selectedYear = $_GET['year'] ?? '';
$selectedMonth = $_GET['month'] ?? '';

/* ===============================
    BUILD WHERE CLAUSE
================================ */
$where = ["mb.paid = 1"];
$params = [];
$types = "";

if ($selectedYear) {
    $where[] = "YEAR(mb.bill_month) = ?";
    $params[] = (int)$selectedYear;
    $types .= "i";
}

if ($selectedMonth) {
    $where[] = "MONTH(mb.bill_month) = ?";
    $params[] = (int)$selectedMonth;
    $types .= "i";
}

$whereSql = "WHERE " . implode(" AND ", $where);

/* ===============================
    MONTHLY INCOME QUERY
================================ */
// Fixed: Table names pluralized to match schema
$sql = "
SELECT 
    DATE_FORMAT(mb.bill_month, '%Y-%m') AS month,
    COALESCE(SUM(
        rt.base_room_fee +
        ((mb.new_electric - mb.old_electric) * ur.electric_rate) +
        (mb.water_units * ur.water_rate)
    ), 0) AS income
FROM monthly_bills mb
JOIN rooms rm ON mb.room_id = rm.room_id
JOIN room_types rt ON rm.room_type_id = rt.room_type_id
JOIN utility_rates ur ON mb.rate_id = ur.rate_id
$whereSql
GROUP BY month
ORDER BY month
";

$stmt = $conn->prepare($sql);
if (!$stmt) { die("Income SQL Error: " . $conn->error); }
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

$months = [];
$income = [];
foreach ($data as $row) {
    $months[] = date('M Y', strtotime($row['month'] . "-01"));
    $income[] = round($row['income'], 2);
}

$totalIncome = array_sum($income);
// Fixed: Changed 'room' to 'rooms'
$totalRoomsRes = $conn->query("SELECT COUNT(*) as count FROM rooms");
$totalRooms = $totalRoomsRes->fetch_assoc()['count'];
$activeRoomsRes = $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'ACTIVE'");
$activeRooms = $activeRoomsRes->fetch_assoc()['count'];
$occupancyRate = $totalRooms > 0 ? round(($activeRooms / $totalRooms) * 100, 1) : 0;
?>

<div class="p-8 max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black tracking-tight">Financial Dashboard</h1>
            <p class="text-sm opacity-50 italic">Overview of rentals, occupancy, and revenue.</p>
        </div>

        <div class="flex gap-2">
            <a href="income_report.php?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>" target="_blank"
                class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl font-bold transition flex items-center gap-2 shadow-lg shadow-green-500/20">
                <i class="fa-solid fa-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    <div
        class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> border p-5 rounded-2xl shadow-sm mb-8">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[150px]">
                <label class="block text-[10px] font-bold uppercase opacity-50 mb-1 ml-1">Year</label>
                <select name="year" class="custom-select dark:bg-gray-800">
                    <option value="">All Years</option>
                    <?php
                    $yearsRes = $conn->query("SELECT DISTINCT YEAR(bill_month) AS y FROM monthly_bills ORDER BY y DESC");
                    while ($y = $yearsRes->fetch_assoc()) {
                        $sel = ($selectedYear == $y['y']) ? 'selected' : '';
                        echo "<option value='{$y['y']}' $sel>{$y['y']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="flex-1 min-w-[150px]">
                <label class="block text-[10px] font-bold uppercase opacity-50 mb-1 ml-1">Month</label>
                <select name="month" class="custom-select dark:bg-gray-800">
                    <option value="">All Months</option>
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $sel = ($selectedMonth == $m) ? 'selected' : '';
                        echo "<option value='$m' $sel>" . date('F', mktime(0, 0, 0, $m)) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl font-bold transition">
                Apply Filters
            </button>
            <a href="index.php" class="bg-gray-500/10 hover:bg-gray-500/20 px-6 py-2.5 rounded-xl font-bold transition">
                Reset
            </a>
        </form>
    </div>

    <?php if (!empty($overdueRooms)): ?>
        <div class="mb-8 p-6 rounded-3xl border-2 border-red-500/20 bg-red-500/5 animate-pulse-subtle">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-red-600 dark:text-red-400 font-black flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    OVERDUE PAYMENTS - <?= date('F Y') ?>
                </h2>
                <span class="bg-red-500 text-white text-[10px] px-2 py-1 rounded-lg">
                    <?= count($overdueRooms) ?> ROOMS PENDING
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($overdueRooms as $overdue): ?>
                    <div
                        class="<?= $isDark ? 'bg-gray-800' : 'bg-white' ?> p-4 rounded-2xl shadow-sm border <?= $isDark ? 'border-gray-700' : 'border-gray-100' ?> flex items-center justify-between">
                        <div>
                            <p class="text-lg font-bold">Room <?= $overdue['room_number'] ?></p>
                            <p class="text-xs opacity-60"><?= htmlspecialchars($overdue['renter_name']) ?></p>
                        </div>
                        <div class="text-right">
                            <a href="https://wa.me/<?= $overdue['mobile_phone'] ?>"
                                class="text-green-500 hover:text-green-600 transition text-xl" title="Contact Tenant">
                                <i class="fa-brands fa-whatsapp"></i>
                            </a>
                            <p class="text-[10px] font-bold text-red-500 uppercase mt-1">Unpaid</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="stat-card border-l-4 border-green-500 shadow-green-500/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] uppercase font-bold opacity-50 tracking-widest">Total Income (Paid)</p>
                    <p class="text-3xl font-black mt-1">$<?= number_format($totalIncome, 2) ?></p>
                </div>
                <i class="fa-solid fa-sack-dollar text-3xl opacity-20 text-green-500"></i>
            </div>
        </div>

        <div class="stat-card border-l-4 border-blue-500 shadow-blue-500/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] uppercase font-bold opacity-50 tracking-widest">Active Rooms</p>
                    <p class="text-3xl font-black mt-1"><?= $activeRooms ?></p>
                </div>
                <i class="fa-solid fa-door-open text-3xl opacity-20 text-blue-500"></i>
            </div>
        </div>

        <div class="stat-card border-l-4 border-purple-500 shadow-purple-500/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[10px] uppercase font-bold opacity-50 tracking-widest">Occupancy Rate</p>
                    <p class="text-3xl font-black mt-1"><?= $occupancyRate ?>%</p>
                </div>
                <i class="fa-solid fa-chart-pie text-3xl opacity-20 text-purple-500"></i>
            </div>
        </div>
    </div>

    <div
        class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> border p-8 rounded-3xl shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold tracking-tight">Revenue Trends</h2>
            <i class="fa-solid fa-chart-line opacity-30"></i>
        </div>

        <?php if (count($months) === 0): ?>
            <div class="h-64 flex flex-col items-center justify-center opacity-40 italic">
                <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                <p>No paid data found for this period.</p>
            </div>
        <?php else: ?>
            <div class="relative h-80">
                <canvas id="incomeChart"></canvas>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    <?php if (count($months) > 0): ?>
        const ctx = document.getElementById('incomeChart').getContext('2d');
        const isDark = <?= $isDark ? 'true' : 'false' ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    data: <?= json_encode($income) ?>,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 4,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#2563eb',
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: isDark ? '#374151' : '#f3f4f6' },
                        ticks: { color: isDark ? '#9ca3af' : '#4b5563' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: isDark ? '#9ca3af' : '#4b5563' }
                    }
                }
            }
        });
    <?php endif; ?>
</script>

<style>
    @keyframes pulse-subtle {
        0% { border-color: rgba(239, 68, 68, 0.2); }
        50% { border-color: rgba(239, 68, 68, 0.5); }
        100% { border-color: rgba(239, 68, 68, 0.2); }
    }
    .animate-pulse-subtle { animation: pulse-subtle 3s infinite ease-in-out; }
    .custom-select {
        width: 100%;
        padding: 10px;
        background-color: transparent;
        border: 1px solid <?= $isDark ? '#4b5563' : '#e5e7eb' ?>;
        border-radius: 12px;
        color: inherit;
        outline: none;
    }
    .stat-card {
        padding: 1.5rem;
        border-radius: 1.5rem;
        background-color: <?= $isDark ? '#1f2937' : '#ffffff' ?>;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
</style>

<?php include '../includes/footer.php'; ?>