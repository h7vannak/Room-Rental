<?php
include '../includes/db.php';
include '../includes/header.php';

/* Read filters */
$selectedYear = $_GET['year'] ?? '';
$selectedMonth = $_GET['month'] ?? '';

/* Build WHERE clause */
$where = [];
$params = [];

if ($selectedYear) {
    $where[] = "YEAR(mb.bill_month) = ?";
    $params[] = $selectedYear;
}

if ($selectedMonth) {
    $where[] = "MONTH(mb.bill_month) = ?";
    $params[] = $selectedMonth;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* Monthly income calculation */
$sql = "
SELECT 
    DATE_FORMAT(mb.bill_month, '%Y-%m') AS month,
    SUM(
        rt.base_room_fee +
        ((mb.new_electric - mb.old_electric) * ur.electric_rate) +
        (mb.water_units * ur.water_rate)
    ) AS income
FROM monthly_bill mb
JOIN rental rl ON mb.rental_id = rl.rental_id
JOIN room rm ON rl.room_id = rm.room_id
JOIN room_type rt ON rm.room_type_id = rt.room_type_id
JOIN utility_rate ur ON mb.rate_id = ur.rate_id
$whereSql
GROUP BY month
ORDER BY month
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

/* Prepare data for Chart.js */
$months = [];
$income = [];

foreach ($data as $row) {
    $months[] = $row['month'];
    $income[] = round($row['income'], 2);
}
?>

<h1 class="text-2xl font-bold mb-4">Dashboard</h1>

<!-- FILTER FORM -->
<form method="GET" class="bg-white p-4 rounded shadow mb-6 flex flex-wrap gap-4">

    <select name="year" class="border rounded px-3 py-2">
        <option value="">All Years</option>
        <?php
        $years = $pdo->query("
            SELECT DISTINCT YEAR(bill_month) AS y
            FROM monthly_bill
            ORDER BY y DESC
        ")->fetchAll();

        foreach ($years as $y) {
            $sel = ($selectedYear == $y['y']) ? 'selected' : '';
            echo "<option value='{$y['y']}' $sel>{$y['y']}</option>";
        }
        ?>
    </select>

    <select name="month" class="border rounded px-3 py-2">
        <option value="">All Months</option>
        <?php
        for ($m = 1; $m <= 12; $m++) {
            $sel = ($selectedMonth == $m) ? 'selected' : '';
            echo "<option value='$m' $sel>" . date('F', mktime(0, 0, 0, $m)) . "</option>";
        }
        ?>
    </select>

    <button class="bg-blue-600 text-white px-4 py-2 rounded">
        Filter
    </button>

    <a href="index.php" class="px-4 py-2 border rounded">
        Reset
    </a>
</form>

<a href="income_report.php?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>"
   target="_blank"
   class="bg-green-600 text-white px-4 py-2 rounded inline-block mb-4">
   Export PDF
</a>


<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- Total Income Card -->
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-gray-500">Total Income</h2>
        <p class="text-3xl font-bold mt-2">
            $<?= number_format(array_sum($income), 2) ?>
        </p>
    </div>

    <!-- Occupancy Card -->
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-gray-500">Active Rentals</h2>
        <p class="text-3xl font-bold mt-2">
            <?= $pdo->query("SELECT COUNT(*) FROM rental")->fetchColumn(); ?>
        </p>
    </div>

</div>

<!-- Chart -->
<div class="bg-white p-6 rounded shadow mt-8">
    <h2 class="text-lg font-semibold mb-4">Monthly Income</h2>
    <canvas id="incomeChart"></canvas>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('incomeChart');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Income ($)',
                data: <?= json_encode($income) ?>,
                backgroundColor: '#2563eb'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>