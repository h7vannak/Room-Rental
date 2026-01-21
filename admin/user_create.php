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
$error = '';

/* ===============================
   HANDLE POST REQUEST
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Error: Username '$username' is already taken.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $username, $password_hash, $role);

        if ($stmt->execute()) {
            header('Location: users.php?created=1');
            exit;
        } else {
            $error = "System error: Could not create user.";
        }
    }
}

include '../includes/header.php';
?>

<div class="p-8 max-w-7xl mx-auto flex flex-col items-center justify-center">
    <div class="w-full max-w-md">
        <div class="flex items-center gap-2 mb-6 text-xs font-black uppercase tracking-widest opacity-60 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
            <a href="users.php" class="hover:text-blue-500 transition-colors">User Management</a>
            <i class="fa-solid fa-chevron-right text-[8px]"></i>
            <span class="<?= $isDark ? 'text-white' : 'text-slate-900' ?>">Create Account</span>
        </div>

        <div class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] p-10 shadow-2xl backdrop-blur-md">
            <div class="mb-8">
                <h1 class="text-3xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                    New Account
                </h1>
                <p class="text-sm opacity-60 font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                    Set up credentials for new staff members.
                </p>
            </div>

            <?php if ($error): ?>
                <div class="flex items-center gap-3 bg-rose-500/10 text-rose-500 p-4 rounded-2xl mb-6 text-xs font-bold border border-rose-500/20">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60 <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">
                        Username
                    </label>
                    <input name="username" required placeholder="e.g. vannaks" 
                           class="w-full border-2 <?= $isDark ? 'bg-gray-900/50  text-white focus:border-blue-500' : 'bg-gray-50 border-gray-100 focus:border-blue-500' ?> p-4 rounded-2xl outline-none transition-all font-bold placeholder:opacity-30 shadow-inner">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60 <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">
                        Password
                    </label>
                    <input type="password" name="password" required placeholder="••••••••" minlength="6"
                           class="w-full border-2 <?= $isDark ? 'bg-gray-900/50  text-white focus:border-blue-500' : 'bg-gray-50 border-gray-100 focus:border-blue-500' ?> p-4 rounded-2xl outline-none transition-all font-bold placeholder:opacity-30 shadow-inner">
                    <p class="text-[9px] font-black opacity-40 mt-2 uppercase tracking-tighter">🔒 Security: Minimum 6 characters</p>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60 <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">
                        Access Level
                    </label>
                    <div class="relative">
                        <select name="role" class="w-full appearance-none border-2 <?= $isDark ? 'bg-gray-900/50  text-white focus:border-blue-500' : 'bg-gray-50 border-gray-100 focus:border-blue-500' ?> p-4 rounded-2xl outline-none transition-all font-bold shadow-inner cursor-pointer">
                            <option value="staff">STAFF (Billing & Payments)</option>
                            <option value="admin">ADMIN (Full Control)</option>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 opacity-30 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <a href="users.php" class="text-xs font-black uppercase tracking-widest opacity-40 hover:opacity-100 transition-opacity <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                        Cancel
                    </a>
                    <button class="bg-blue-600 hover:bg-blue-500 text-white px-10 py-4 rounded-2xl font-black text-sm transition-all shadow-lg shadow-blue-500/20 hover:scale-105 active:scale-95 flex items-center gap-3">
                        Save User <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    body {
        background-color: <?= $isDark ? '#0f172a' : '#f8fafc' ?>;
    }
    input::placeholder { font-weight: 500; }
</style>

<?php include '../includes/footer.php'; ?>