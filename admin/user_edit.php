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

// Check theme preference
$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';
$loggedInUserId = $_SESSION['user']['id'];

/* ===============================
   FETCH USER DATA (STRICT CHECK)
================================ */
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// 1. Redirect if ID is missing or invalid
if ($id <= 0) {
    header('Location: users.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// 2. CRITICAL FIX: If user is NULL, STOP immediately.
// This prevents line 92 from trying to read a username that doesn't exist.
if (!$user) {
    header('Location: users.php?error=not_found');
    exit;
}

// 3. Define helper variables ONLY after we are sure $user exists
$isEditingSelf = ($loggedInUserId == $user['user_id']);
$displayUsername = htmlspecialchars($user['username']);

/* ===============================
   HANDLE UPDATE
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $isActive = $isEditingSelf ? $user['is_active'] : (int) $_POST['is_active'];

    $conn->begin_transaction();
    try {
        $update = $conn->prepare("UPDATE users SET username = ?, role = ?, is_active = ? WHERE user_id = ?");
        $update->bind_param("ssii", $username, $role, $isActive, $id);
        $update->execute();

        if (!empty($_POST['password'])) {
            $newPass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $updatePass = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $updatePass->bind_param("si", $newPass, $id);
            $updatePass->execute();
        }

        $conn->commit();
        header('Location: users.php?updated=1');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Update failed: " . $e->getMessage());
    }
}

include '../includes/header.php';
?>

<div class="p-8 max-w-7xl mx-auto flex flex-col items-center justify-center">
    <div class="w-full max-w-md">
        <div
            class="flex items-center gap-2 mb-6 text-xs font-black uppercase tracking-widest opacity-60 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
            <a href="users.php" class="hover:text-blue-500 transition-colors">User Management</a>
            <i class="fa-solid fa-chevron-right text-[8px]"></i>
            <span class="<?= $isDark ? 'text-white' : 'text-slate-900' ?>">Edit Profile</span>
        </div>

        <div
            class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] p-10 shadow-2xl backdrop-blur-md">
            <div class="mb-8">
                <h1 class="text-3xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                    Edit Account
                </h1>
                <p class="text-sm opacity-60 font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                    Updating credentials for <span class="text-blue-500 font-bold">@<?= $displayUsername ?></span>
                </p>
            </div>

            <form method="POST" class="space-y-6">
                <div>
                    <label
                        class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60 <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">
                        Username
                    </label>
                    <input name="username" value="<?= $displayUsername ?>" required
                        class="w-full border-2 <?= $isDark ? 'bg-gray-900/50 text-white focus:border-blue-500' : 'bg-gray-50 border-gray-100 focus:border-blue-500' ?> p-4 rounded-2xl outline-none transition-all font-bold shadow-inner">
                </div>

                <div>
                    <label
                        class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60 <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">
                        Access Role
                    </label>
                    <div class="relative">
                        <select name="role"
                            class="w-full appearance-none border-2 <?= $isDark ? 'bg-gray-900/50 text-white focus:border-blue-500' : 'bg-gray-50 border-gray-100 focus:border-blue-500' ?> p-4 rounded-2xl outline-none transition-all font-bold shadow-inner cursor-pointer">
                            <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>ADMIN</option>
                            <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>STAFF</option>
                        </select>
                        <i
                            class="fa-solid fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 opacity-30 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div>
                    <label
                        class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60 <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">
                        Account Status
                    </label>
                    <div class="relative">
                        <?php
                        // FIX: Ensure is_active exists, default to 1 if missing
                        $userStatus = $user['is_active'] ?? 1;
                        ?>
                        <select name="is_active" <?= $isEditingSelf ? 'disabled' : '' ?>
                            class="w-full appearance-none border-2 <?= $isDark ? 'bg-gray-900/50 text-white focus:border-blue-500' : 'bg-gray-50 border-gray-100 focus:border-blue-500' ?> p-4 rounded-2xl outline-none transition-all font-bold shadow-inner <?= $isEditingSelf ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' ?>">
                            <option value="1" <?= $userStatus == 1 ? 'selected' : '' ?>>ACTIVE</option>
                            <option value="0" <?= $userStatus == 0 ? 'selected' : '' ?>>DISABLED</option>
                        </select>
                    </div>
                    <?php if ($isEditingSelf): ?>
                        <input type="hidden" name="is_active" value="<?= $userStatus ?>">
                        <p
                            class="text-[9px] font-black text-rose-500 mt-2 uppercase tracking-tighter italic leading-relaxed">
                            <i class="fa-solid fa-shield-slash mr-1"></i> Security Error: You cannot disable your own
                            account.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="pt-4 border-t <?= $isDark ? 'border-gray-700' : 'border-gray-100' ?>">
                    <label
                        class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60 <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">
                        New Password (Optional)
                    </label>
                    <input type="password" name="password" placeholder="Leave blank to keep current"
                        class="w-full border-2 <?= $isDark ? 'bg-gray-900/50 text-white focus:border-blue-500' : 'bg-gray-50 border-gray-100 focus:border-blue-500' ?> p-4 rounded-2xl outline-none transition-all font-bold shadow-inner">
                </div>

                <div class="flex items-center justify-between pt-4">
                    <a href="users.php"
                        class="text-xs font-black uppercase tracking-widest opacity-40 hover:opacity-100 transition-opacity <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                        Cancel
                    </a>
                    <button
                        class="bg-blue-600 hover:bg-blue-500 text-white px-10 py-4 rounded-2xl font-black text-sm transition-all shadow-lg shadow-blue-500/20 hover:scale-105 active:scale-95 flex items-center gap-3">
                        Update User <i class="fa-solid fa-check"></i>
                    </button>
                </div>
            </form>
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