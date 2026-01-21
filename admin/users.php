<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

/* ===============================
   AUTH CHECK (ADMIN ONLY)
================================ */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../public/auth/login.php');
    exit;
}

include '../includes/header.php';

// Check theme preference
$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';
$current_user_id = $_SESSION['user']['id']; // Get ID of person logged in
$security_error = '';

// Check if returning from a failed delete attempt
if (isset($_GET['error']) && $_GET['error'] === 'self_disable') {
    $security_error = "Security Error: You cannot disable your own account. Please ask another admin to do this.";
}

/* ===============================
   FETCH USERS
================================ */
$sql = "SELECT user_id, username, role, is_active, created_at 
        FROM users 
        ORDER BY created_at DESC";
$result = $conn->query($sql);
$users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="p-8 max-w-7xl mx-auto min-h-screen">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
        <div>
            <h1 class="text-4xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                User Management
            </h1>
            <p class="text-sm opacity-60 font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                Manage staff permissions and system access levels.
            </p>
        </div>
        <a href="user_create.php"
            class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-2xl font-bold transition-all shadow-lg shadow-blue-500/20 flex items-center gap-2 hover:scale-105 active:scale-95">
            <i class="fa-solid fa-user-plus"></i> New User
        </a>
    </div>

    <?php if ($security_error): ?>
        <div
            class="flex items-center gap-4 bg-rose-500/10 text-rose-500 p-6 rounded-[2rem] mb-8 text-sm font-bold border border-rose-500/20 animate-pulse">
            <div class="w-10 h-10 rounded-full bg-rose-500 text-white flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-shield-slash"></i>
            </div>
            <p><?= $security_error ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="mb-6 animate-in fade-in slide-in-from-top-4 duration-300">
            <?php if ($_GET['error'] === 'self_disable'): ?>
                <div
                    class="flex items-center gap-4 bg-rose-500/10 text-rose-500 p-6 rounded-[2rem] border border-rose-500/20 shadow-xl shadow-rose-500/5">
                    <div
                        class="w-12 h-12 rounded-2xl bg-rose-500 text-white flex items-center justify-center flex-shrink-0 shadow-lg shadow-rose-500/30">
                        <i class="fa-solid fa-shield-halved text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-black uppercase tracking-widest text-[10px] mb-1">Security Restriction</h4>
                        <p class="text-sm font-bold opacity-90">Security Error: You cannot disable your own account. Please ask
                            another admin to do this.</p>
                    </div>
                </div>
            <?php elseif ($_GET['error'] === 'not_found'): ?>
                <div
                    class="bg-amber-500/10 text-amber-600 p-4 rounded-2xl border border-amber-500/20 text-sm font-bold flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation"></i> User record was not found.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['disabled'])): ?>
        <div
            class="mb-6 bg-emerald-500/10 text-emerald-500 p-4 rounded-2xl border border-emerald-500/20 text-sm font-bold flex items-center gap-3">
            <i class="fa-solid fa-check-circle"></i> Account has been successfully disabled.
        </div>
    <?php endif; ?>

    <div
        class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] overflow-hidden shadow-2xl backdrop-blur-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="<?= $isDark ? 'bg-gray-900/40' : 'bg-gray-50/50' ?> text-[10px] uppercase font-black tracking-widest opacity-60 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                        <th class="px-8 py-6">Identity</th>
                        <th class="px-6 py-6">Access Role</th>
                        <th class="px-6 py-6">Status</th>
                        <th class="px-6 py-6">Date Created</th>
                        <th class="px-8 py-6 text-right">Control</th>
                    </tr>
                </thead>
                <tbody class="divide-y <?= $isDark ? 'divide-gray-700' : 'divide-gray-100' ?>">
                    <?php foreach ($users as $u): ?>
                        <tr
                            class="hover:bg-blue-500/[0.02] transition-colors group <?= $u['user_id'] == $current_user_id ? 'bg-blue-500/[0.01]' : '' ?>">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-2xl <?= $isDark ? 'bg-gray-700 text-blue-400' : 'bg-blue-50 text-blue-600' ?> flex items-center justify-center font-black text-lg shadow-sm border <?= $isDark ? 'border-gray-600' : 'border-blue-100' ?>">
                                        <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p
                                            class="font-black text-sm <?= $isDark ? 'text-slate-200' : 'text-slate-800' ?> leading-tight">
                                            <?= htmlspecialchars($u['username']) ?>
                                            <?php if ($u['user_id'] == $current_user_id): ?>
                                                <span
                                                    class="ml-2 text-[9px] bg-blue-500 text-white px-2 py-0.5 rounded-full uppercase tracking-tighter">You</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">UID:
                                            <?= $u['user_id'] ?></p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-6">
                                <span
                                    class="<?= $u['role'] === 'admin' ? 'bg-purple-500/10 text-purple-500' : 'bg-blue-500/10 text-blue-500' ?> text-[10px] font-black px-3 py-1.5 rounded-lg ring-1 ring-opacity-20 flex w-fit items-center gap-1.5">
                                    <i
                                        class="fa-solid <?= $u['role'] === 'admin' ? 'fa-shield-halved' : 'fa-user-tie' ?>"></i>
                                    <?= strtoupper($u['role']) ?>
                                </span>
                            </td>

                            <td class="px-6 py-6">
                                <?php if ($u['is_active']): ?>
                                    <span
                                        class="inline-flex items-center gap-1.5 bg-emerald-500/10 text-emerald-500 text-[10px] font-black px-3 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> ACTIVE
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="inline-flex items-center gap-1.5 bg-rose-500/10 text-rose-500 text-[10px] font-black px-3 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> DISABLED
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-6 py-6 font-bold text-sm <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">
                                <?= date('d M, Y', strtotime($u['created_at'])) ?>
                            </td>

                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="user_edit.php?id=<?= $u['user_id'] ?>"
                                        class="p-2 rounded-xl <?= $isDark ? 'hover:bg-blue-500/20 text-blue-400' : 'hover:bg-blue-50 text-blue-600' ?> transition-colors">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>

                                    <?php if ($u['is_active'] && $u['user_id'] != $current_user_id): ?>
                                        <a href="user_delete.php?id=<?= $u['user_id'] ?>"
                                            class="p-2 rounded-xl <?= $isDark ? 'hover:bg-rose-500/20 text-rose-400' : 'hover:bg-rose-50 text-rose-500' ?> transition-colors"
                                            onclick="return confirm('Disable <?= htmlspecialchars($u['username']) ?>?')">
                                            <i class="fa-solid fa-user-slash"></i>
                                        </a>
                                    <?php elseif ($u['user_id'] == $current_user_id): ?>
                                        <span class="p-2 opacity-20 cursor-not-allowed"
                                            title="Security: Cannot disable yourself">
                                            <i class="fa-solid fa-lock text-xs"></i>
                                        </span>
                                    <?php endif; ?>
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

<?php include '../includes/footer.php'; ?>