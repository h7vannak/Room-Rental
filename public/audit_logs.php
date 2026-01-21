<?php
require_once '../includes/db.php'; 
require_once '../includes/auth.php';

// Ensure this function exists in your auth.php to protect this sensitive page
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../public/auth/login.php');
    exit;
}

$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

/* --- PAGINATION LOGIC --- */
$limit = 20; 
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// SQL FIXED: Table name changed to audit_logs
$total_res = $conn->query("SELECT COUNT(*) as total FROM audit_logs");
$total_logs = $total_res->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $limit);

/* --- FETCH LOGS --- */
// SQL FIXED: Table name changed to audit_logs
$sql = "
    SELECT a.*, u.username, u.role
    FROM audit_logs a
    JOIN users u ON a.user_id = u.user_id
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<div class="p-8 max-w-7xl mx-auto min-h-screen">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
        <div>
            <h1 class="text-4xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                Security Audit
            </h1>
            <p class="text-sm opacity-60 font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                Tracking <span class="text-blue-500 font-bold"><?= number_format($total_logs) ?></span> system activities and administrative changes.
            </p>
        </div>
        
        <div class="flex gap-2">
            <button onclick="window.location.reload()" class="p-4 rounded-2xl border <?= $isDark ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-100 hover:bg-white' ?> transition-all shadow-sm">
                <i class="fa-solid fa-rotate-right <?= $isDark ? 'text-white' : 'text-slate-600' ?>"></i>
            </button>
        </div>
    </div>

    <div class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] overflow-hidden shadow-2xl backdrop-blur-md">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="<?= $isDark ? 'bg-gray-900/40 text-slate-400' : 'bg-slate-50 text-slate-500' ?> uppercase text-[10px] tracking-[0.2em] font-black">
                        <th class="px-8 py-6 text-left">Initiator</th>
                        <th class="px-8 py-6 text-left">Action Event</th>
                        <th class="px-8 py-6 text-left">Details</th>
                        <th class="px-8 py-6 text-left">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y <?= $isDark ? 'divide-gray-700/50' : 'divide-gray-100' ?>">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" class="px-8 py-20 text-center">
                                <i class="fa-solid fa-box-open text-4xl opacity-10 mb-4 block"></i>
                                <span class="text-sm font-bold opacity-30 uppercase tracking-widest">Digital silence. No logs recorded.</span>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($logs as $log): 
                        // Color coding based on action type
                        $action = strtoupper($log['action']);
                        $badgeClass = 'bg-slate-500/10 text-slate-500 border-slate-500/20'; // Default
                        
                        if (str_contains($action, 'DELETE') || str_contains($action, 'DISABLE')) $badgeClass = 'bg-rose-500/10 text-rose-500 border-rose-500/20';
                        if (str_contains($action, 'CREATE') || str_contains($action, 'ADD')) $badgeClass = 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
                        if (str_contains($action, 'UPDATE') || str_contains($action, 'EDIT')) $badgeClass = 'bg-amber-500/10 text-amber-500 border-amber-500/20';
                        if (str_contains($action, 'LOGIN')) $badgeClass = 'bg-blue-500/10 text-blue-500 border-blue-500/20';
                    ?>
                        <tr class="hover:bg-blue-500/5 transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br <?= $isDark ? 'from-gray-700 to-gray-800' : 'from-slate-100 to-slate-200' ?> flex items-center justify-center font-black text-xs <?= $isDark ? 'text-white' : 'text-slate-600' ?>">
                                        <?= strtoupper(substr($log['username'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="font-black text-sm <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                                            <?= htmlspecialchars($log['username']) ?>
                                        </div>
                                        <div class="text-[9px] font-black uppercase tracking-tighter <?= $log['role'] === 'admin' ? 'text-rose-500' : 'text-blue-500' ?>">
                                            <?= $log['role'] ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-8 py-6">
                                <span class="px-3 py-1.5 rounded-lg border font-black text-[10px] tracking-widest <?= $badgeClass ?>">
                                    <?= htmlspecialchars($action) ?>
                                </span>
                            </td>

                            <td class="px-8 py-6">
                                <p class="text-sm font-medium leading-relaxed <?= $isDark ? 'text-slate-400' : 'text-slate-600' ?>">
                                    <?= htmlspecialchars($log['description']) ?>
                                </p>
                            </td>

                            <td class="px-8 py-6 whitespace-nowrap">
                                <div class="text-xs font-bold <?= $isDark ? 'text-slate-300' : 'text-slate-700' ?>">
                                    <?= date('d M Y', strtotime($log['created_at'])) ?>
                                </div>
                                <div class="text-[10px] font-medium opacity-40">
                                    <?= date('h:i:s A', strtotime($log['created_at'])) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="mt-10 flex justify-center items-center gap-3">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>"
                    class="w-10 h-10 flex items-center justify-center rounded-xl font-black text-xs transition-all 
                    <?= $page == $i 
                        ? 'bg-blue-600 shadow-lg shadow-blue-500/30 scale-110' 
                        : ($isDark ? 'bg-gray-800 hover:text-white border-gray-700' : 'bg-white text-slate-500 hover:bg-slate-50 border border-slate-100') 
                    ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    body { background-color: <?= $isDark ? '#0f172a' : '#f8fafc' ?>; }
</style>

<?php include '../includes/footer.php'; ?>