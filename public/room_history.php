<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
include '../includes/header.php';

requireAuth();

// Detect Dark Mode
$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

/* ===============================
    SQL LOGIC
================================ */
// We use LEFT JOIN for rooms and renters just in case a room/renter 
// was deleted but the history remains (though constraints usually prevent this).
$sql = "
    SELECT 
        rm.room_number, 
        r.renter_name, 
        rh.checkin_date, 
        rh.checkout_date 
    FROM room_history rh
    JOIN rooms rm ON rh.room_id = rm.room_id
    JOIN renters r ON rh.renter_id = r.renter_id
    ORDER BY rh.checkout_date DESC, rh.checkin_date DESC
";

$result = $conn->query($sql);
$history = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];

/**
 * Helper to calculate stay duration
 */
function getDuration($start, $end)
{
    $startDate = new DateTime($start);
    $endDate = $end ? new DateTime($end) : new DateTime();
    $interval = $startDate->diff($endDate);

    if ($interval->y > 0)
        return $interval->y . 'y ' . $interval->m . 'm';
    if ($interval->m > 0)
        return $interval->m . 'm ' . $interval->d . 'd';
    if ($interval->d == 0)
        return 'Same day';
    return $interval->d . ' days';
}
?>



<div class="p-8 max-w-7xl mx-auto">
    <div class="mb-8 flex justify-between items-end">
        <div>
            <h1
                class="text-3xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?> flex items-center gap-3">
                <i class="fa-solid fa-clock-rotate-left text-blue-500"></i>
                Rental History
            </h1>
            <p class="text-sm font-medium opacity-50 mt-1">Archived logs of all previous and current room occupancies
            </p>
        </div>

        <div class="flex gap-2">
            <button onclick="window.print()"
                class="px-4 py-2 rounded-xl bg-gray-500/10 hover:bg-gray-500/20 transition font-bold text-xs uppercase tracking-widest flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <div
        class="overflow-hidden rounded-[2rem] border <?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> shadow-2xl backdrop-blur-md">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left border-collapse">
                <thead
                    class="<?= $isDark ? 'bg-gray-900/50 text-gray-400' : 'bg-gray-50 text-gray-500' ?> uppercase text-[10px] font-black tracking-[0.2em]">
                    <tr>
                        <th class="px-8 py-5">Room</th>
                        <th class="px-8 py-5">Tenant</th>
                        <th class="px-8 py-5 text-center">Check-in</th>
                        <th class="px-8 py-5 text-center">Check-out</th>
                        <th class="px-8 py-5">Duration</th>
                        <th class="px-8 py-5 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y <?= $isDark ? 'divide-gray-700' : 'divide-gray-100' ?>">
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="6" class="px-8 py-20 text-center">
                                <i class="fa-solid fa-folder-open text-4xl opacity-10 mb-4 block"></i>
                                <span class="opacity-40 italic font-medium">No rental history records found.</span>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($history as $h): ?>
                        <tr class="hover:bg-blue-500/5 transition-colors group">
                            <td class="px-8 py-5">
                                <span
                                    class="w-10 h-10 rounded-lg bg-blue-500/10 text-blue-500 flex items-center justify-center font-black text-xs border border-blue-500/20">
                                    <?= htmlspecialchars($h['room_number']) ?>
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <div class="font-bold <?= $isDark ? 'text-gray-200' : 'text-gray-900' ?>">
                                    <?= htmlspecialchars($h['renter_name']) ?>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <div
                                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gray-500/5 border border-gray-500/10 text-[11px] font-bold">
                                    <i class="fa-regular fa-calendar-check opacity-40"></i>
                                    <?= date('d M Y', strtotime($h['checkin_date'])) ?>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <?php if ($h['checkout_date']): ?>
                                    <div
                                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gray-500/5 border border-gray-500/10 text-[11px] font-bold">
                                        <i class="fa-regular fa-calendar-xmark opacity-40"></i>
                                        <?= date('d M Y', strtotime($h['checkout_date'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span
                                        class="text-[10px] font-black uppercase tracking-widest text-emerald-500 animate-pulse">
                                        Current Resident
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-5">
                                <span class="font-mono text-xs opacity-60 bg-gray-500/5 px-2 py-1 rounded">
                                    <?= getDuration($h['checkin_date'], $h['checkout_date']) ?>
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <?php if ($h['checkout_date']): ?>
                                    <span
                                        class="inline-block px-3 py-1 rounded-lg text-[9px] font-black bg-gray-500/10 text-gray-500 border border-gray-500/20 uppercase tracking-tighter">
                                        Archived
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="inline-block px-3 py-1 rounded-lg text-[9px] font-black bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 uppercase tracking-tighter">
                                        Active
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        body {
            background: white !important;
            color: black !important;
        }

        .no-print,
        button,
        a {
            display: none !important;
        }

        .rounded-[2rem] {
            border-radius: 0 !important;
            border: none !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>