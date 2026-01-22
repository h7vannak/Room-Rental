<?php
require_once '../includes/db.php'; 
include '../includes/header.php';

requireAuth();

$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';
$role = $_SESSION['user']['role'];

/* ===============================
    FETCH PAYMENTS (JOINED DATA)
================================ */
// SQL FIXED: Table names pluralized to monthly_bills, rooms, and renters
$sql = "
SELECT 
    p.id, p.amount, p.method, p.status, p.bakong_hash, p.created_at,
    mb.bill_id, rm.room_number, r.renter_name
FROM payments p
JOIN monthly_bills mb ON p.bill_id = mb.bill_id
JOIN rooms rm ON mb.room_id = rm.room_id
LEFT JOIN renters r ON rm.renter_id = r.renter_id
ORDER BY p.created_at DESC
";

$result = $conn->query($sql);
$payments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="px-8 max-w-full mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
        <div>
            <h1 class="text-4xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                Payment History
            </h1>
            <p class="text-sm opacity-60 font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                Review and audit all processed transactions.
            </p>
        </div>
        <div class="flex gap-3">
             <button onclick="window.print()" class="<?= $isDark ? 'bg-gray-800 text-white border-gray-700' : 'bg-white border-gray-200' ?> border px-5 py-2.5 rounded-xl font-bold transition flex items-center gap-2 shadow-sm hover:scale-105">
                <i class="fa-solid fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <div class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] overflow-hidden shadow-2xl backdrop-blur-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="<?= $isDark ? 'bg-gray-900/40' : 'bg-gray-50/50' ?> text-[10px] uppercase font-black tracking-widest opacity-60 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                        <th class="px-8 py-6">Transaction ID</th>
                        <th class="px-6 py-6">Tenant & Room</th>
                        <th class="px-6 py-6 text-center">Method</th>
                        <th class="px-6 py-6 text-right">Amount</th>
                        <th class="px-6 py-6 text-center">Status</th>
                        <th class="px-8 py-6 text-right">Date Processed</th>
                    </tr>
                </thead>
                <tbody class="divide-y <?= $isDark ? 'divide-gray-700' : 'divide-gray-100' ?>">
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="6" class="p-24 text-center">
                                <div class="opacity-20 mb-4"><i class="fa-solid fa-receipt text-6xl"></i></div>
                                <p class="opacity-40 font-bold italic">No transactions found in history.</p>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($payments as $p): ?>
                    <tr class="hover:bg-blue-500/[0.02] transition-colors group">
                        <td class="px-8 py-6">
                            <span class="font-mono text-xs font-bold opacity-50">#TXN-<?= str_pad($p['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </td>

                        <td class="px-6 py-6">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl <?= $isDark ? 'bg-gray-700 text-blue-400' : 'bg-blue-50 text-blue-600' ?> flex items-center justify-center font-black text-xs">
                                    <?= $p['room_number'] ?>
                                </div>
                                <div>
                                    <p class="font-black text-sm <?= $isDark ? 'text-slate-200' : 'text-slate-800' ?> leading-tight">
                                        <?= htmlspecialchars($p['renter_name'] ?? 'System Sync') ?>
                                    </p>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Bill #<?= $p['bill_id'] ?></p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-6 text-center">
                            <?php if ($p['method'] === 'BAKONG'): ?>
                                <span class="bg-rose-500/10 text-rose-500 text-[10px] font-black px-3 py-1 rounded-lg ring-1 ring-rose-500/20">
                                    <i class="fa-solid fa-qrcode mr-1"></i> BAKONG
                                </span>
                            <?php else: ?>
                                <span class="bg-blue-500/10 text-blue-500 text-[10px] font-black px-3 py-1 rounded-lg ring-1 ring-blue-500/20">
                                    <i class="fa-solid fa-money-bill-wave mr-1"></i> CASH
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-6 text-right">
                            <p class="font-black <?= $isDark ? 'text-white' : 'text-slate-900' ?> text-base">
                                $<?= number_format($p['amount'], 2) ?>
                            </p>
                        </td>

                        <td class="px-6 py-6 text-center">
                            <?php if ($p['status'] === 'SUCCESS'): ?>
                                <span class="inline-flex items-center gap-1.5 bg-emerald-500/10 text-emerald-500 text-[10px] font-black px-3 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> COMPLETED
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 bg-amber-500/10 text-amber-500 text-[10px] font-black px-3 py-1 rounded-full">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> <?= $p['status'] ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-8 py-6 text-right">
                            <p class="font-bold text-sm <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">
                                <?= date('d M, Y', strtotime($p['created_at'])) ?>
                            </p>
                            <p class="text-[10px] font-black opacity-40 uppercase tracking-tighter">
                                <?= date('H:i A', strtotime($p['created_at'])) ?>
                            </p>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    body {
        background-color: <?= $isDark ? '#0f172a' : '#f8fafc' ?>;
    }
    /* Custom Scrollbar for modern look */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { 
        background: <?= $isDark ? '#334155' : '#e2e8f0' ?>; 
        border-radius: 10px; 
    }
</style>

<?php include '../includes/footer.php'; ?>