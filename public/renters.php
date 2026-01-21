<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
include '../includes/header.php';

requireAuth();

// Detect Dark Mode
$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

// 1. Fetch normalized data using JOIN
// We join with 'nationalities' to get the actual name instead of just the ID
$sql = "
    SELECT r.*, n.nat_name 
    FROM renters r
    LEFT JOIN nationalities n ON r.nat_id = n.nat_id 
    ORDER BY r.renter_id DESC
";
$result = $conn->query($sql);
$renters = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="p-8 max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
        <div>
            <h1 class="text-4xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                Tenant Directory
            </h1>
            <p class="text-sm opacity-60 font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                Manage profiles and contact details for all occupants
            </p>
        </div>
        <button onclick="openRenterModal()"
            class="upload-btn bg-blue-600 hover:bg-blue-500 text-white px-8 py-4 rounded-2xl font-black text-sm transition-all shadow-xl shadow-blue-500/20 hover:scale-105 active:scale-95 flex items-center gap-3">
            <i class="fa-solid fa-plus text-xs"></i> Add Renter
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div
            class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> p-6 rounded-[2rem] border shadow-xl flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-500">
                <i class="fa-solid fa-id-card text-2xl"></i>
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest opacity-40">Total Registered</p>
                <p class="text-2xl font-black <?= $isDark ? 'text-white' : 'text-slate-900' ?>"><?= count($renters) ?>
                </p>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['msg'])): ?>
        <div
            class="mb-6 p-4 rounded-2xl flex items-center gap-3 animate-pulse <?php echo $_SESSION['msg_type'] === 'success' ? 'bg-green-500/10 text-green-500 border border-green-500/20' : 'bg-red-500/10 border-red-500/20'; ?>">
            <i
                class="fa-solid <?php echo $_SESSION['msg_type'] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <span class="font-bold text-sm"><?php echo $_SESSION['msg'];
            unset($_SESSION['msg']); ?></span>
        </div>
    <?php endif; ?>

    <div
        class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] overflow-hidden shadow-2xl backdrop-blur-md">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left">
                <thead>
                    <tr
                        class="<?= $isDark ? 'bg-gray-900/40 text-slate-400' : 'bg-slate-50 text-slate-500' ?> uppercase text-[10px] tracking-[0.2em] font-black">
                        <th class="px-8 py-6">ID</th>
                        <th class="px-8 py-6">Tenant Profile</th>
                        <th class="px-8 py-6">Contact & Social</th>
                        <th class="px-8 py-6">Origin</th>
                        <th class="px-8 py-6 text-center">Manage</th>
                    </tr>
                </thead>
                <tbody class="divide-y <?= $isDark ? 'divide-gray-700/50' : 'divide-gray-100' ?>">
                    <?php if (empty($renters)): ?>
                        <tr>
                            <td colspan="5" class="px-8 py-20 text-center">
                                <i class="fa-solid fa-users-slash text-4xl opacity-10 mb-4 block"></i>
                                <span class="text-sm font-bold opacity-30 uppercase tracking-widest">No renters found in the
                                    directory.</span>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($renters as $r): ?>
                        <tr class="hover:bg-blue-500/5 transition-colors group">
                            <td class="px-8 py-6 font-mono text-xs opacity-40">#<?= $r['renter_id'] ?></td>

                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-inner <?= $r['gender'] === 'Female' ? 'bg-rose-500/10 text-rose-500' : 'bg-indigo-500/10 text-indigo-500' ?>">
                                        <i
                                            class="fa-solid <?= $r['gender'] === 'Female' ? 'fa-user-nurse' : 'fa-user-tie' ?> text-lg"></i>
                                    </div>
                                    <div>
                                        <div class="font-black text-sm <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                                            <?= htmlspecialchars($r['renter_name']) ?>
                                        </div>
                                        <div class="text-[9px] font-black uppercase tracking-tighter opacity-50">
                                            <?= $r['gender'] ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-8 py-6">
                                <div class="space-y-1">
                                    <div
                                        class="flex items-center gap-2 text-sm font-bold <?= $isDark ? 'text-slate-300' : 'text-slate-700' ?>">
                                        <i class="fa-solid fa-phone text-[10px] text-blue-500"></i>
                                        <?= htmlspecialchars($r['mobile_phone']) ?>
                                    </div>
                                    <?php if ($r['telegram']): ?>
                                        <div class="flex items-center gap-2 text-[10px] font-bold text-sky-500">
                                            <i class="fa-brands fa-telegram"></i>
                                            @<?= htmlspecialchars($r['telegram']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="px-8 py-6">
                                <span
                                    class="px-3 py-1.5 rounded-lg border font-black text-[10px] tracking-widest <?= $isDark ? 'bg-gray-900/50 text-slate-400' : 'bg-gray-50 border-gray-200 text-slate-500' ?>">
                                    <?= htmlspecialchars($r['nat_name'] ?? 'Not Set') ?>
                                </span>
                            </td>

                            <td class="px-8 py-6">
                                <div class="flex justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">

                                    <button onclick='openRenterModal(<?php echo json_encode($r); ?>)'
                                        class="edit-btn w-10 h-10 flex items-center justify-center bg-blue-500/10 text-blue-500 rounded-xl hover:bg-blue-500 hover:text-white transition-all shadow-lg shadow-blue-500/5">
                                        <i class="fa-solid fa-pen-nib text-xs"></i>
                                    </button>

                                    <button
                                        onclick="ajaxDelete(<?= $r['renter_id'] ?>, 'renter_actions.php', this.closest('tr'))"
                                        class="w-10 h-10 flex items-center justify-center bg-rose-500/10 text-rose-500 rounded-xl hover:bg-rose-500 hover:text-white transition-all shadow-lg shadow-rose-500/5">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
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
        background-color:
            <?= $isDark ? '#0f172a' : '#f8fafc' ?>
        ;
    }
</style>

<script id="nationalitiesJson" type="application/json">
    <?php
    $nat_q = $conn->query("SELECT * FROM nationalities ORDER BY nat_name ASC");
    echo json_encode($nat_q->fetch_all(MYSQLI_ASSOC));
    ?>
</script>

<?php include '../includes/footer.php'; ?>