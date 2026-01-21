<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: ../public/auth/login.php');
    exit;
}

$sessionUser = $_SESSION['user'];
$username = $sessionUser['username'] ?? 'User';
$role = $sessionUser['role'] ?? 'Staff';
$userTheme = $sessionUser['theme'] ?? 'light';
$userLang = $sessionUser['lang'] ?? 'en';
$isDark = ($userTheme === 'dark');

/* ===============================
   HANDLE UPDATES
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $sessionUser['id'];
    $success = false;

    // Handle Theme Update
    if (isset($_POST['theme'])) {
        $theme = $_POST['theme'];
        $stmt = $conn->prepare("UPDATE users SET theme = ? WHERE user_id = ?");
        $stmt->bind_param("si", $theme, $userId);
        if ($stmt->execute()) {
            $_SESSION['user']['theme'] = $theme;
            $success = true;
        }
    }

    // Handle Language Update
    if (isset($_POST['language'])) {
        $lang = $_POST['language'];
        $stmt = $conn->prepare("UPDATE users SET language = ? WHERE user_id = ?");
        $stmt->bind_param("si", $lang, $userId);
        if ($stmt->execute()) {
            $_SESSION['user']['lang'] = $lang;
            $success = true;
        }
    }

    header("Location: preferences.php?success=1");
    exit;
}

include '../includes/header.php';
?>

<div class="p-8 max-w-7xl mx-auto flex flex-col items-center justify-center">
    <div class="w-full max-w-md">
        
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                Account Settings
            </h1>
            <p class="text-sm opacity-60 font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                Personalize your dashboard experience
            </p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 flex items-center gap-3 bg-emerald-500/10 text-emerald-500 p-4 rounded-2xl border border-emerald-500/20 text-xs font-black uppercase tracking-widest animate-in fade-in slide-in-from-top-2">
                <i class="fa-solid fa-circle-check"></i> Preferences updated
            </div>
        <?php endif; ?>

        <div class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] overflow-hidden shadow-2xl backdrop-blur-md">
            
            <div class="<?= $isDark ? 'bg-gray-900/40' : 'bg-slate-50' ?> p-8 border-b <?= $isDark ? 'border-gray-700' : 'border-gray-100' ?> flex items-center gap-5">
                <div class="w-20 h-20 rounded-[2rem] bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center text-white text-3xl font-black shadow-lg shadow-blue-500/20">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <div>
                    <h2 class="text-xl font-black <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                        <?= htmlspecialchars($username) ?>
                    </h2>
                    <span class="inline-block mt-1 px-3 py-1 bg-blue-500 text-white rounded-lg text-[10px] font-black uppercase tracking-widest">
                        <?= htmlspecialchars($role) ?>
                    </span>
                </div>
            </div>

            <form method="post" class="p-10 space-y-8">
                
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest mb-3 opacity-60 <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">
                        System Language
                    </label>
                    <div class="relative">
                        <select name="language"
                            class="w-full appearance-none border-2 <?= $isDark ? 'bg-gray-900/50 border-gray-700 text-white' : 'bg-gray-50 border-gray-100' ?> p-4 rounded-2xl outline-none focus:border-blue-500 transition-all font-bold">
                            <option value="en" <?= $userLang === 'en' ? 'selected' : '' ?>>English (Global)</option>
                            <option value="km" <?= $userLang === 'km' ? 'selected' : '' ?>>Khmer (Local)</option>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 opacity-30 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest mb-3 opacity-60 <?= $isDark ? 'text-slate-300' : 'text-slate-600' ?>">
                        Visual Mode
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="group cursor-pointer">
                            <input type="radio" name="theme" value="light" class="hidden peer" <?= $userTheme === 'light' ? 'checked' : '' ?>>
                            <div class="p-4 text-center rounded-2xl border-2 transition-all font-black text-xs uppercase tracking-widest
                                <?= $isDark ? 'border-gray-700 bg-gray-900/30 text-slate-500' : 'border-gray-100 bg-gray-50 text-slate-400' ?>
                                peer-checked:border-blue-500 peer-checked:bg-blue-500 peer-checked:text-white peer-checked:shadow-lg peer-checked:shadow-blue-500/20">
                                <i class="fa-solid fa-sun mb-2 text-lg block"></i> Light
                            </div>
                        </label>
                        <label class="group cursor-pointer">
                            <input type="radio" name="theme" value="dark" class="hidden peer" <?= $userTheme === 'dark' ? 'checked' : '' ?>>
                            <div class="p-4 text-center rounded-2xl border-2 transition-all font-black text-xs uppercase tracking-widest
                                <?= $isDark ? 'border-gray-700 bg-gray-900/30 text-slate-500' : 'border-gray-100 bg-gray-50 text-slate-400' ?>
                                peer-checked:border-indigo-500 peer-checked:bg-indigo-500 peer-checked:text-white peer-checked:shadow-lg peer-checked:shadow-indigo-500/20">
                                <i class="fa-solid fa-moon mb-2 text-lg block"></i> Dark
                            </div>
                        </label>
                    </div>
                </div>

                <button class="w-full bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-2xl transition-all shadow-xl shadow-blue-500/20 active:scale-95 flex items-center justify-center gap-3">
                    Save Preferences <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>
        </div>
        
        <div class="mt-8 text-center">
            <a href="../public/index.php" class="text-[10px] font-black uppercase tracking-[0.2em] opacity-40 hover:opacity-100 transition-opacity <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                <i class="fa-solid fa-chevron-left mr-2"></i> Return to Dashboard
            </a>
        </div>
    </div>
</div>

<style>
    body { background-color: <?= $isDark ? '#0f172a' : '#f8fafc' ?>; transition: background-color 0.3s ease; }
</style>

<?php include '../includes/footer.php'; ?>