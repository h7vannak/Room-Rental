<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

$sql = "SELECT * FROM utility_rates ORDER BY effective_date DESC";
$result = $conn->query($sql);
$rates = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="p-8 max-w-7xl mx-auto min-h-screen">
    <div class="flex justify-between items-center mb-10">
        <div>
            <h1 class="text-4xl font-black <?= $isDark ? 'text-white' : 'text-slate-900' ?>">Utility Rates</h1>
            <p class="opacity-60 text-sm font-medium">Manage global electricity and water pricing.</p>
        </div>
        <button onclick="openUtilityRateModal()"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-2xl font-black text-sm shadow-lg shadow-blue-500/30 transition-all hover:scale-105">
            <i class="fa-solid fa-plus mr-2"></i> NEW RATE
        </button>
    </div>

    <div
        class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] overflow-hidden shadow-xl">
        <table class="w-full text-left">
            <thead>
                <tr
                    class="text-[10px] font-black opacity-50 uppercase tracking-[0.2em] <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                    <th class="px-8 py-6">Effective Date</th>
                    <th class="px-8 py-6">Electric ($/kWh)</th>
                    <th class="px-8 py-6">Water ($/m³)</th>
                    <th class="px-8 py-6 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y <?= $isDark ? 'divide-gray-700' : 'divide-gray-100' ?>">
                <?php foreach ($rates as $index => $r): ?>
                    <tr class="hover:bg-blue-500/5 transition-colors">
                        <td class="px-8 py-6 font-bold">
                            <?= date('d M, Y', strtotime($r['effective_date'])) ?>
                            <?php if ($index === 0): ?>
                                <span
                                    class="ml-2 text-[9px] bg-emerald-500/10 text-emerald-500 px-2 py-0.5 rounded-full border border-emerald-500/20">ACTIVE</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-8 py-6 font-black text-amber-500">$<?= number_format($r['electric_rate'], 2) ?></td>
                        <td class="px-8 py-6 font-black text-blue-500">$<?= number_format($r['water_rate'], 2) ?></td>
                        <td class="px-8 py-6 text-right space-x-2">
                            <button onclick='openUtilityRateModal(<?= json_encode($r) ?>)'
                                class="p-2 text-blue-500 hover:bg-blue-500/10 rounded-lg transition">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button
                                onclick="ajaxDelete(<?= $r['rate_id'] ?>, 'utility_rate_actions.php', this.closest('tr'))"
                                class="p-2 text-rose-500 hover:bg-rose-500/10 rounded-lg transition"
                                title="Delete Utility Rate">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/alerts.js"></script>

<?php include '../includes/footer.php'; ?>