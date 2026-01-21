<?php
require_once '../includes/auth.php';
require_once 'system.php';
require_once 'helpers.php';
require_once 'lang.php';

requireAuth();

// Active menu detection
$currentPage = basename($_SERVER['PHP_SELF']);

// User Data Fallbacks to prevent "Undefined Index" warnings
$user = $_SESSION['user'] ?? [];
$role = $user['role'] ?? 'staff';
$name = $user['username'] ?? ($user['name'] ?? 'User'); // Handles both username/name keys
$lang = $user['lang'] ?? 'en';
$theme = $user['theme'] ?? 'light';

// 1. Define pages that should NOT be blocked
$bypass_pages = ['maintenance.php', 'login.php', 'logout.php'];

// 2. Maintenance Gatekeeper
$isAdmin = ($role === 'admin');
if (isset($system['maintenance_mode']) && $system['maintenance_mode'] == 1) {
    // Check if current page is not in bypass list and user is not admin
    if (!$isAdmin && !in_array($currentPage, $bypass_pages)) {
        header('Location: ../public/maintenance.php');
        exit;
    }
}

// 3. Language Loader
$langFile = "../lang/$lang.php";
$T = file_exists($langFile) ? include $langFile : include "../lang/en.php";
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" class="<?= $theme ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($system['system_name']) ?></title>
    <?php if ($system['favicon']): ?>
        <link rel="icon" href="../uploads/system/<?= $system['favicon'] ?>">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../public/assets/css/tailwind.css">
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Alpine Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <!-- Alpine Core -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

</head>

<body class="transition-colors duration-300 dark:bg-gray-900 dark:text-white bg-gray-100 text-black">

    <?php if (isset($system['maintenance_mode']) && $system['maintenance_mode'] == 1 && $isAdmin): ?>
        <div class="bg-red-600 text-white py-2 px-4 flex justify-between items-center shadow-lg sticky top-0 z-[100]">
            <div class="flex items-center gap-2 text-xs md:text-sm font-bold">
                <span class="animate-pulse">⚠️</span>
                <span>MAINTENANCE MODE ACTIVE</span>
            </div>
            <a href="../admin/system_settings.php"
                class="bg-white text-red-600 px-3 py-1 rounded text-xs font-bold hover:bg-gray-100">
                SETTINGS
            </a>
        </div>
    <?php endif; ?>

    <div class="flex min-h-screen" x-data="{ sidebarExpanded: true }">
        <nav :class="sidebarExpanded ? 'w-1/6' : 'w-20'"
            class="sticky top-0 h-screen overflow-y-auto text-white transition-all duration-300 z-50 dark:bg-gray-800 dark:border-r dark:border-gray-700 bg-blue-700 flex flex-col">
            <div class="p-4 flex flex-col gap-6">
                <div :class="sidebarExpanded ? 'flex-row justify-between' : 'flex-col gap-y-2'"
                    class="flex items-center">
                    <a href="../public/index.php"
                        class="bg-white dark:bg-gray-700 p-2 flex items-center gap-2 rounded-xl text-blue-700 dark:text-white overflow-hidden shrink-0">
                        <?php if ($system['logo']): ?>
                            <img src="../uploads/system/<?= $system['logo'] ?>"
                                class="h-10 w-auto object-contain min-w-[30px]">
                        <?php endif; ?>
                        <span x-show="sidebarExpanded"
                            class="font-bold truncate"><?= $system['system_short_name'] ?></span>
                    </a>
                    <button @click="sidebarExpanded = !sidebarExpanded"
                        class="p-2 hover:bg-white/10 rounded-lg transition">
                        <i class="fa-solid" :class="sidebarExpanded ? 'fa-indent' : 'fa-outdent'"></i>
                    </button>
                </div>

                <div class="flex flex-col space-y-2">
                    <div>
                        <!-- <p x-show="sidebarExpanded"
                            class="px-4 mb-2 text-[10px] uppercase tracking-widest text-white/50 font-bold">Main</p> -->
                        <a href="../public/index.php"
                            class="px-4 py-2 flex items-center gap-3 rounded-lg transition <?= $currentPage == 'index.php' ? 'bg-white text-blue-700 font-bold' : 'hover:bg-white/10' ?>">
                            <i class="fa-solid fa-gauge w-5 text-center"></i>
                            <span x-show="sidebarExpanded">Dashboard</span>
                        </a>
                    </div>

                    <div
                        x-data="{ open: <?= in_array($currentPage, ['nationalities.php', 'renter_documents.php', 'renters.php']) ? 'true' : 'false' ?> }">
                        <button @click="open = !open; sidebarExpanded = true"
                            class="w-full px-4 py-2 flex items-center justify-between rounded-lg hover:bg-white/10 transition <?= in_array($currentPage, ['nationalities.php', 'renter_documents.php', 'renters.php']) ? 'bg-white/5' : '' ?>">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-address-card w-5 text-center"></i>
                                <span x-show="sidebarExpanded">Tenant Info</span>
                            </div>
                            <i x-show="sidebarExpanded"
                                class="fa-solid fa-chevron-down text-[10px] transition-transform"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open && sidebarExpanded" x-collapse class="pl-6 mt-1 space-y-1">
                            <a href="../public/nationalities.php"
                                class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'nationalities.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                <i class="fa-solid fa-earth-asia w-4 text-center text-[12px]"></i>
                                <span>Nationalities</span>
                            </a>
                            <a href="../public/renter_documents.php"
                                class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'renter_documents.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                <i class="fa-solid fa-folder-tree w-4 text-center text-[12px]"></i>
                                <span>Digital Archive</span>
                            </a>
                            <a href="../public/renters.php"
                                class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'renters.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                <i class="fa-solid fa-users-viewfinder w-4 text-center text-[12px]"></i>
                                <span>Renters List</span>
                            </a>
                        </div>
                    </div>

                    <div
                        x-data="{ open: <?= in_array($currentPage, ['rooms.php', 'room_types.php', 'room_history.php', 'room_type_reassign.php']) ? 'true' : 'false' ?> }">
                        <button @click="open = !open; sidebarExpanded = true"
                            class="w-full px-4 py-2 flex items-center justify-between rounded-lg hover:bg-white/10 transition <?= in_array($currentPage, ['rooms.php', 'room_types.php', 'room_history.php']) ? 'bg-white/5' : '' ?>">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-door-open w-5 text-center"></i>
                                <span x-show="sidebarExpanded"><?= $T['rooms_management'] ?? 'Rooms' ?></span>
                            </div>
                            <i x-show="sidebarExpanded"
                                class="fa-solid fa-chevron-down text-[10px] transition-transform"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open && sidebarExpanded" x-collapse class="pl-6 mt-1 space-y-1">
                            <a href="../public/rooms.php"
                                class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'rooms.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                <i class="fa-solid fa-list-check w-4 text-center text-[12px]"></i>
                                <span>All Rooms</span>
                            </a>
                            <?php if ($isAdmin): ?>
                                <a href="../public/room_types.php"
                                    class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'room_types.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                    <i class="fa-solid fa-tags w-4 text-center text-[12px]"></i>
                                    <span>Room Types</span>
                                </a>

                                <a href="../public/room_type_reassign.php"
                                    class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'room_type_reassign.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                    <i class="fa-solid fa-right-left w-4 text-center text-[12px]"></i>
                                    <span>Room Type Reassign</span>
                                </a>
                            <?php endif; ?>
                            <a href="../public/room_history.php"
                                class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'room_history.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                <i class="fa-solid fa-clock-rotate-left w-4 text-center text-[12px]"></i>
                                <span>History</span>
                            </a>
                        </div>
                    </div>

                    <div
                        x-data="{ open: <?= in_array($currentPage, ['utility_rates.php', 'bills.php', 'payments.php']) ? 'true' : 'false' ?> }">
                        <button @click="open = !open; sidebarExpanded = true"
                            class="w-full px-4 py-2 flex items-center justify-between rounded-lg hover:bg-white/10 transition <?= in_array($currentPage, ['utility_rates.php', 'bills.php', 'payments.php']) ? 'bg-white/5' : '' ?>">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid fa-dollar-sign w-5 text-center"></i>
                                <span x-show="sidebarExpanded">Finance</span>
                            </div>
                            <i x-show="sidebarExpanded"
                                class="fa-solid fa-chevron-down text-[10px] transition-transform"
                                :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open && sidebarExpanded" x-collapse class="pl-6 mt-1 space-y-1">
                            <?php if ($isAdmin): ?>
                                <a href="../public/utility_rates.php"
                                    class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'utility_rates.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                    <i class="fa-solid fa-earth-asia w-4 text-center text-[12px]"></i>
                                    <span>Utility Rates</span>
                                </a>
                            <?php endif; ?>
                            <a href="../public/bills.php"
                                class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'bills.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                <i class="fa-solid fa-folder-tree w-4 text-center text-[12px]"></i>
                                <span><?= $T['bills'] ?? 'Invoices' ?></span>
                            </a>
                            <a href="../public/payments.php"
                                class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'payments.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                <i class="fa-solid fa-users-viewfinder w-4 text-center text-[12px]"></i>
                                <span><?= $T['payment_history'] ?? 'Payments' ?></span>
                            </a>
                        </div>
                    </div>

                    <?php if ($isAdmin): ?>
                        <div
                            x-data="{ open: <?= in_array($currentPage, ['users.php', 'system_settings.php', 'audit_logs.php', 'preferences.php']) ? 'true' : 'false' ?> }">
                            <button @click="open = !open; sidebarExpanded = true"
                                class="w-full px-4 py-2 flex items-center justify-between rounded-lg hover:bg-white/10 transition <?= in_array($currentPage, ['users.php', 'system_settings.php']) ? 'bg-white/5' : '' ?>">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid fa-user-shield w-5 text-center"></i>
                                    <span x-show="sidebarExpanded">Administration</span>
                                </div>
                                <i x-show="sidebarExpanded"
                                    class="fa-solid fa-chevron-down text-[10px] transition-transform"
                                    :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="open && sidebarExpanded" x-collapse class="pl-6 mt-1 space-y-1">
                                <a href="../admin/users.php"
                                    class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'users.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                    <i class="fa-solid fa-users w-4 text-center text-[12px]"></i>
                                    <span>Users</span>
                                </a>
                                <a href="../admin/system_settings.php"
                                    class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'system_settings.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                    <i class="fa-solid fa-gears w-4 text-center text-[12px]"></i>
                                    <span>Settings</span>
                                </a>
                                <a href="../admin/preferences.php"
                                    class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'preferences.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                    <i class="fa-solid fa-user-gear w-4 text-center text-[12px]"></i>
                                    <span>Preferences</span>
                                </a>
                                <a href="../public/audit_logs.php"
                                    class="flex items-center gap-3 px-4 py-2 text-sm transition-all <?= $currentPage == 'audit_logs.php' ? 'text-white font-bold opacity-100' : 'opacity-60 hover:opacity-100 hover:translate-x-1' ?>">
                                    <i class="fa-solid fa-clipboard-list w-4 text-center text-[12px]"></i>
                                    <span>Audit Logs</span>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div x-show="sidebarExpanded" class="mt-auto p-4 border-t border-white/20 text-[10px] opacity-60">
                &copy; 2026 <?= htmlspecialchars($system['system_name']) ?>
            </div>
        </nav>

        <main :class="sidebarExpanded ? 'w-5/6' : 'flex-grow'" class="p-6 transition-all duration-300">
            <div
                class="sticky top-6 z-40 dark:bg-gray-800 dark:border-r dark:border-gray-700 bg-blue-700 text-white flex justify-between items-center px-8 py-4 mb-8 rounded-2xl shadow-lg">
                <div class="font-bold text-lg flex items-center gap-2">
                    <i class="fa-solid fa-circle-chevron-right text-blue-300"></i>
                    <?= str_replace('.php', '', ucfirst($currentPage)) ?>
                </div>

                <div class="flex items-center gap-6">
                    <div class="flex items-center bg-black/10 rounded-lg p-1">
                        <button onclick="updatePref('language', 'en')"
                            class="p-2 text-xs rounded transition <?= ($lang === 'en') ? 'bg-white text-blue-600 font-bold shadow-sm' : 'hover:bg-white/20 text-white' ?>">
                            EN
                        </button>

                        <button onclick="updatePref('language', 'km')"
                            class="p-2 text-xs rounded transition <?= ($lang === 'km') ? 'bg-white text-blue-600 font-bold shadow-sm' : 'hover:bg-white/20 text-white' ?>">
                            KM
                        </button>
                    </div>
                    <div>
                        <button onclick="updatePref('theme', '<?= $theme === 'light' ? 'dark' : 'light' ?>')"
                            class="p-2 rounded-lg transition <?= $theme === 'dark' ? 'bg-yellow-400 text-gray-900' : 'bg-black/10 text-white' ?> hover:scale-110">
                            <?= $theme === 'light' ? '🌙' : '☀️' ?>
                        </button>
                    </div>
                    <div class="h-8 w-[1px] bg-white/20"></div>

                    <div class="flex items-center gap-3">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-bold leading-none"><?= htmlspecialchars($name) ?></p>
                            <p class="text-[10px] opacity-70 uppercase"><?= $role ?></p>
                        </div>
                        <a href="javascript:void(0)" onclick="confirmLogout('<?= $_SESSION['user']['name'] ?>')"
                            class="bg-red-500 hover:bg-red-600 px-3 py-2 rounded-lg transition" title="Logout">
                            <i class="fa-solid fa-power-off"></i>
                        </a>
                    </div>
                </div>
            </div>

            <script>
                function updatePref(type, value) {
                    const formData = new FormData();
                    formData.append(type, value);
                    fetch('../admin/preferences.php', {
                        method: 'POST',
                        body: formData
                    }).then(() => location.reload());
                }
            </script>