<?php
require_once '../includes/db.php';
include '../includes/header.php';

// Fix for the Undefined variable $isDark
$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $e_rate = $_POST['electric_rate'];
    $w_rate = $_POST['water_rate'];
    $eff_date = $_POST['effective_date'];

    $stmt = $conn->prepare("INSERT INTO utility_rates (electric_rate, water_rate, effective_date) VALUES (?, ?, ?)");
    $stmt->bind_param("dds", $e_rate, $w_rate, $eff_date);
    
    if ($stmt->execute()) {
        echo "<script>window.location='utility_rates.php';</script>";
        exit;
    }
}
?>

<div class="p-8 max-w-2xl mx-auto min-h-screen">
    <div class="mb-8 flex items-center gap-4">
        <a href="utility_rates.php" class="p-3 rounded-2xl <?= $isDark ? 'bg-gray-800 text-white' : 'bg-slate-100 text-slate-600' ?> hover:scale-110 transition shadow-sm">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black <?= $isDark ? 'text-white' : 'text-slate-900' ?>">Create Rate</h1>
            <p class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em]">New Price Configuration</p>
        </div>
    </div>

    <form method="POST" class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] p-10 shadow-2xl space-y-8">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest opacity-50 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                    Electric Rate ($/kWh)
                </label>
                <div class="relative group">
                    <div class="absolute left-5 top-1/2 -translate-y-1/2 text-amber-500 transition-transform group-focus-within:scale-110">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <input type="number" step="0.001" name="electric_rate" placeholder="0.00" required 
                        class="w-full pl-12 pr-6 py-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all 
                        <?= $isDark ? 'bg-gray-900 placeholder-gray-600' : 'bg-slate-50 text-slate-900 placeholder-slate-300' ?>">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest opacity-50 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                    Water Rate ($/m³)
                </label>
                <div class="relative group">
                    <div class="absolute left-5 top-1/2 -translate-y-1/2 text-blue-500 transition-transform group-focus-within:scale-110">
                        <i class="fa-solid fa-droplet"></i>
                    </div>
                    <input type="number" step="0.001" name="water_rate" placeholder="0.00" required 
                        class="w-full pl-12 pr-6 py-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all 
                        <?= $isDark ? 'bg-gray-900 placeholder-gray-600' : 'bg-slate-50 text-slate-900 placeholder-slate-300' ?>">
                </div>
            </div>
        </div>

        <div class="space-y-2">
            <label class="text-[10px] font-black uppercase tracking-widest opacity-50 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                Effective From
            </label>
            <div class="relative">
                <input type="date" name="effective_date" value="<?= date('Y-m-d') ?>" required 
                    class="w-full px-6 py-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all 
                    <?= $isDark ? 'bg-gray-900 text-white' : 'bg-slate-50 text-slate-900' ?>">
            </div>
            <p class="text-[9px] font-bold text-slate-400 mt-2 px-2 italic">
                * This rate will be applied to all bills generated on or after this date.
            </p>
        </div>

        <div class="pt-4 flex flex-col gap-4">
            <button type="submit" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black tracking-widest hover:bg-blue-700 hover:shadow-blue-500/40 transition-all shadow-xl shadow-blue-500/20 active:scale-[0.98]">
                ACTIVATE NEW RATES
            </button>
            
            <a href="utility_rates.php" class="w-full py-4 text-center font-bold text-xs opacity-50 hover:opacity-100 transition <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                DISCARD AND EXIT
            </a>
        </div>
    </form>
</div>

<style>
    body { background-color: <?= $isDark ? '#0f172a' : '#f8fafc' ?>; }
    /* Custom style to clean up the date picker icon color in dark mode */
    ::-webkit-calendar-picker-indicator {
        filter: <?= $isDark ? 'invert(1)' : 'none' ?>;
        cursor: pointer;
    }
</style>