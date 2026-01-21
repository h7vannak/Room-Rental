<?php
session_start();
include '../../includes/db.php';
include '../../includes/system.php';

$error_title = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $error_title = 'Account Not Found';
        $error_message = "The username '$username' does not exist in our records.";
    } else {
        if (password_verify($password, $user['password_hash'])) {
            // REGENERATE SESSION ID FOR SECURITY
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => $user['user_id'],
                'name' => $user['username'],
                'role' => $user['role']
            ];

            // 2. LOG THE LOGIN ACTION
            $user_id = $user['user_id'];
            $action = "Login";
            $description = "User " . $user['username'] . " logged in from " . $_SERVER['REMOTE_ADDR'];

            $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
            $log_stmt->bind_param("iss", $user_id, $action, $description);
            $log_stmt->execute();

            header('Location: ../index.php');
            exit;
        } else {
            $error_title = 'Access Denied';
            $error_message = 'The password you entered is incorrect.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <title>Secure Login | <?= $system['system_name'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .dark .glass {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
        }
    </style>
</head>

<body
    class="bg-slate-100 dark:bg-slate-950 min-h-screen flex items-center justify-center p-6 transition-colors duration-500">

    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-400/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-500/20 rounded-full blur-3xl"></div>
    </div>

    <div
        class="w-full max-w-[1000px] flex flex-col md:flex-row glass rounded-[2rem] shadow-2xl border border-white/20 dark:border-slate-700/50 overflow-hidden">

        <div
            class="hidden md:flex w-5/12 bg-gradient-to-br from-indigo-600 to-blue-700 p-12 text-white flex-col justify-between">
            <div>
                <?php if ($system['logo']): ?>
                    <img src="../../uploads/system/<?= $system['logo'] ?>" class="w-20 mb-6 drop-shadow-md">
                <?php endif; ?>
                <h1 class="text-3xl font-bold leading-tight"><?= $system['system_name'] ?></h1>
                <p class="mt-4 text-indigo-100/80">Manage your business operations with security and ease.</p>
            </div>
            <div class="text-sm text-indigo-200/50">
                &copy; 2026 <?= $system['system_name'] ?>. All rights reserved.
            </div>
        </div>

        <div class="w-full md:w-7/12 p-8 md:p-16">
            <div class="mb-10">
                <h2 class="text-3xl font-bold text-slate-800 dark:text-white">Sign In</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-2">Enter your details to access your workspace.</p>
            </div>

            <form id="loginForm" method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Username</label>
                    <div class="relative group">
                        <i
                            class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                        <input type="text" name="username" id="username" required
                            class="w-full pl-12 pr-4 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all shadow-sm"
                            placeholder="username">
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Password</label>
                    </div>
                    <div class="relative group">
                        <i
                            class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                        <input type="password" name="password" id="password" required
                            class="w-full pl-12 pr-12 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all shadow-sm"
                            placeholder="••••••••">

                        <button type="button" id="eyeToggleBtn"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-500">
                            <i id="toggleIcon" class="fa-solid fa-eye"></i>
                        </button>
                    </div>

                    <div class="flex justify-end items-center">
                        <a href="javascript:void(0)" onclick="forgotPassword()"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 transition-colors">
                            Forgot Password?
                        </a>
                    </div>
                </div>

                <button type="submit" id="loginBtn" disabled
                    class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 dark:disabled:bg-slate-800 disabled:text-slate-500 text-white font-bold py-4 rounded-2xl transition-all transform hover:scale-[1.01] active:scale-95 shadow-lg shadow-indigo-200 dark:shadow-none mt-4 flex items-center justify-center space-x-2">
                    <i id="loaderIcon" class="fa-solid fa-circle-notch fa-spin hidden"></i>
                    <span id="btnText">Sign In</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        // 1. Move definitions to the top
        const uInput = document.getElementById('username');
        const pInput = document.getElementById('password');
        const lBtn = document.getElementById('loginBtn');
        const tIcon = document.getElementById('toggleIcon');
        const eyeBtn = document.getElementById('eyeToggleBtn');

        // 2. Use Event Listener instead of onclick attribute
        if (eyeBtn) {
            eyeBtn.addEventListener('click', function () {
                if (pInput.type === 'password') {
                    pInput.type = 'text';
                    tIcon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    pInput.type = 'password';
                    tIcon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        }

        function validate() {
            lBtn.disabled = !(uInput.value.trim() && pInput.value.trim());
        }

        uInput.addEventListener('input', validate);
        pInput.addEventListener('input', validate);

        const loginForm = document.getElementById('loginForm');
        const loaderIcon = document.getElementById('loaderIcon');
        const btnText = document.getElementById('btnText');

        loginForm.addEventListener('submit', function () {
            // 1. Disable the button so they can't click twice
            lBtn.disabled = true;

            // 2. Show the spinner and change text
            loaderIcon.classList.remove('hidden');
            btnText.textContent = 'Authenticating...';

            // 3. Optional: Add a slight fade effect to the button
            lBtn.classList.add('opacity-80', 'cursor-not-allowed');
        });

        // 3.SweetAlerts for Errors
        <?php if ($error_title): ?>
            Swal.fire({
                icon: 'error',
                // addslashes() prevents quotes from breaking the JS
                title: '<?= addslashes($error_title) ?>',
                text: '<?= addslashes($error_message) ?>',
                confirmButtonColor: '#4f46e5',
                customClass: { popup: 'rounded-[1.5rem]' }
            });
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'timeout'): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Logged Out',
                text: 'Your session has expired for your security.',
                confirmButtonColor: '#4f46e5',
                customClass: { popup: 'rounded-[1.5rem]' }
            });
        <?php endif; ?>

        function forgotPassword() {
            const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            Swal.fire({
                title: 'Forgot Password?',
                html: `
                    <div class="text-center p-2">
                        <p class="text-slate-600 dark:text-slate-400 mb-4">
                            For security reasons, password resets must be handled by an administrator.
                        </p>
                        <div class="bg-slate-50 dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 text-left">
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Contact Admin:</p>
                            <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                                <p><i class="fa-solid fa-envelope mr-2 text-indigo-500"></i> vannakchan884@gmail.com</p>
                                <p><i class="fa-solid fa-phone mr-2 text-indigo-500"></i> (+855) 96 26 65 240</p>
                                <p><i class="fa-solid fa-location-dot mr-2 text-indigo-500"></i> IT Department - Room 204</p>
                            </div>
                        </div>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'Got it!',
                confirmButtonColor: '#4f46e5',
                background: isDark ? '#1e293b' : '#fff',
                color: isDark ? '#fff' : '#000',
                customClass: {
                    popup: 'rounded-[1.5rem]'
                }
            });
        }
    </script>
</body>

</html>