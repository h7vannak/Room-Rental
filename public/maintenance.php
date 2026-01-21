<?php
require_once '../includes/db.php';
require_once '../includes/system.php'; // Provides the $system array
session_start();

/* 1. CHECK IF MAINTENANCE IS STILL ON */
// If you manually turned it off, this page should redirect back to home
if (!$system['maintenance_mode']) {
    header('Location: ../public/index.php');
    exit;
}

/* 2. ADMIN BYPASS */
$isAdmin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';

/* 3. THEME & BRANDING */
$theme = $_SESSION['user']['theme'] ?? 'light';
$isDark = $theme === 'dark';
$systemName = $system['system_name'] ?? 'Room Rental System';

/* 4. TIME CALCULATION */
// We use the 'updated_at' timestamp from system_info plus the minutes set by admin
$updatedTime = strtotime($system['updated_at'] ?? 'now');
$minutes = (int) ($system['maintenance_minutes'] ?? 15);
$endTime = $updatedTime + ($minutes * 60);
$remaining = $endTime - time();
?>
<!DOCTYPE html>
<html lang="en" class="<?= $isDark ? 'dark' : '' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance | <?= htmlspecialchars($systemName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
</head>

<body
    class="min-h-screen flex items-center justify-center p-4 transition-colors duration-500 <?= $isDark ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-900' ?>">

    <?php if ($isAdmin): ?>
        <div
            class="fixed top-6 px-6 py-2 bg-green-500/10 border border-green-500/50 text-green-500 rounded-full text-xs font-bold uppercase tracking-widest backdrop-blur-md">
            👑 Admin Mode Active
        </div>
    <?php endif; ?>

    <div
        class="max-w-md w-full rounded-3xl shadow-2xl p-10 text-center transition-all border <?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-white' ?>">

        <div class="relative inline-block mb-6">
            <div class="text-7xl animate-pulse">⚙️</div>
            <div class="absolute -bottom-1 -right-1 text-2xl animate-spin-slow">🛠️</div>
        </div>

        <h1 class="text-3xl font-black mb-4 tracking-tight">Scheduled Maintenance</h1>

        <p class="mb-6 text-sm <?= $isDark ? 'text-gray-400' : 'text-gray-500' ?>">
            We're polishing things up to serve you better. The system will be back online shortly.
        </p>

        <?php if (!empty($system['maintenance_message'])): ?>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-4 mb-8">
                <p class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase mb-1">Notice</p>
                <p class="text-sm italic"><?= htmlspecialchars($system['maintenance_message']) ?></p>
            </div>
        <?php endif; ?>

        <div class="space-y-2">
            <p
                class="text-[10px] uppercase font-bold tracking-[0.2em] <?= $isDark ? 'text-gray-500' : 'text-gray-400' ?>">
                Estimated Recovery
            </p>
            <div id="countdown" class="text-4xl font-mono font-bold text-blue-600 tabular-nums">
                --:--
            </div>
        </div>

        <div class="mt-10 pt-6 border-t <?= $isDark ? 'border-gray-700' : 'border-gray-100' ?>">
            <p class="text-[10px] text-gray-400 font-medium">
                © <?= date('Y') ?> <?= htmlspecialchars($systemName) ?>
            </p>
        </div>
    </div>

    <style>
        @keyframes spin-slow {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .animate-spin-slow {
            animation: spin-slow 8s linear infinite;
        }
    </style>

    <script>
        // 1. Get the remaining seconds from PHP
        let seconds = <?= $remaining > 0 ? $remaining : 0 ?>;
        const countdownElement = document.getElementById('countdown');

        function updateCountdown() {
            // 2. Check if time is up
            if (seconds <= 0) {
                countdownElement.innerText = "Redirecting...";

                // Wait 2 seconds so the user can see the "Redirecting" message
                setTimeout(() => {
                    // Redirect to the login page
                    window.location.href = '../public/auth/login.php';
                }, 2000);
                return;
            }

            // 3. Format the time display (MM:SS)
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            countdownElement.innerText =
                `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;

            seconds--;
        }

        // Initialize
        if (seconds > 0) {
            updateCountdown();
            setInterval(updateCountdown, 1000);
        } else {
            // If the page is loaded and time is already expired
            countdownElement.innerText = "Welcome back!";
            setTimeout(() => {
                window.location.href = '../public/auth/login.php';
            }, 1500);
        }
    </script>

</body>

</html>