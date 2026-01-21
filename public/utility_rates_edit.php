<?php
require_once '../includes/db.php';
include '../includes/header.php';

// Fix for the Undefined variable $isDark
$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM utility_rates WHERE rate_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$rate = $stmt->get_result()->fetch_assoc();

// If the rate record doesn't exist, stop execution
if (!$rate) {
    echo "<div class='p-8 text-center'><p class='text-rose-500 font-bold'>Rate record not found.</p></div>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $e_rate = $_POST['electric_rate'];
    $w_rate = $_POST['water_rate'];
    $eff_date = $_POST['effective_date'];

    $upd = $conn->prepare("UPDATE utility_rates SET electric_rate=?, water_rate=?, effective_date=? WHERE rate_id=?");
    $upd->bind_param("ddsi", $e_rate, $w_rate, $eff_date, $id);
    $upd->execute();
    echo "<script>window.location='utility_rates.php';</script>";
    exit;
}
?>

<div class="p-8 max-w-2xl mx-auto min-h-screen">
    <div class="mb-8 flex items-center gap-4">
        <a href="utility_rates.php" class="p-3 rounded-2xl <?= $isDark ? 'bg-gray-800 text-white' : 'bg-slate-100 text-slate-600' ?> hover:scale-110 transition">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black <?= $isDark ? 'text-white' : 'text-slate-900' ?>">Edit Utility Rate</h1>
            <p class="text-xs font-bold text-blue-500 uppercase tracking-widest">Record ID: #<?= $id ?></p>
        </div>
    </div>

    <form method="POST" class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] p-10 shadow-2xl space-y-6">
        
        <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest opacity-50 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                    Electric ($/kWh)
                </label>
                <div class="relative">
                    <i class="fa-solid fa-bolt absolute left-5 top-1/2 -translate-y-1/2 text-amber-500"></i>
                    <input type="number" step="0.01" name="electric_rate" value="<?= htmlspecialchars($rate['electric_rate']) ?>" required 
                        class="w-full pl-12 pr-6 py-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500 transition 
                        <?= $isDark ? 'bg-gray-900 text-white' : 'bg-slate-50 text-slate-900' ?>">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest opacity-50 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                    Water ($/m³)
                </label>
                <div class="relative">
                    <i class="fa-solid fa-droplet absolute left-5 top-1/2 -translate-y-1/2 text-blue-500"></i>
                    <input type="number" step="0.01" name="water_rate" value="<?= htmlspecialchars($rate['water_rate']) ?>" required 
                        class="w-full pl-12 pr-6 py-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500 transition 
                        <?= $isDark ? 'bg-gray-900 text-white' : 'bg-slate-50 text-slate-900' ?>">
                </div>
            </div>
        </div>

        <div class="space-y-2">
            <label class="text-[10px] font-black uppercase tracking-widest opacity-50 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                Effective From
            </label>
            <input type="date" name="effective_date" value="<?= $rate['effective_date'] ?>" required 
                class="w-full px-6 py-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500 transition 
                <?= $isDark ? 'bg-gray-900 text-white' : 'bg-slate-50 text-slate-900' ?>">
        </div>

        <div class="pt-4 flex flex-col gap-3">
            <button type="submit" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-500/30 active:scale-95">
                UPDATE CHANGES
            </button>
            <a href="utility_rates.php" class="w-full py-4 text-center font-bold text-xs opacity-50 hover:opacity-100 transition <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                CANCEL AND GO BACK
            </a>
        </div>
    </form>
</div>

<style>
    body { background-color: <?= $isDark ? '#0f172a' : '#f8fafc' ?>; }
</style>