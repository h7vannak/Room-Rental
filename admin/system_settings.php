<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

/* ===============================
   AUTH & ADMIN CHECK
================================ */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../public/auth/login.php');
    exit;
}

// Theme Check
$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

// Fetch current system info
$sys_res = $conn->query("SELECT * FROM system_info WHERE system_id = 1");
$system = $sys_res->fetch_assoc();

/* ===============================
   FILE UPLOAD HELPER
================================ */
function uploadFile($file, $oldFile = null)
{
    if ($file['error'] !== UPLOAD_ERR_OK)
        return $oldFile;

    $allowed = ['png', 'jpg', 'jpeg', 'webp', 'ico'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed))
        return $oldFile;

    $targetDir = "../uploads/system/";
    if (!is_dir($targetDir))
        mkdir($targetDir, 0777, true);

    $filename = uniqid() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
        if ($oldFile && file_exists($targetDir . $oldFile))
            unlink($targetDir . $oldFile);
        return $filename;
    }
    return $oldFile;
}

/* ===============================
   HANDLE POST REQUEST
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logo = uploadFile($_FILES['logo'], $system['logo']);
    $favicon = uploadFile($_FILES['favicon'], $system['favicon']);
    $m_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

    $stmt = $conn->prepare("
        UPDATE system_info SET
            system_name = ?, system_short_name = ?, currency = ?, 
            currency_symbol = ?, contact_email = ?, contact_phone = ?, 
            address = ?, logo = ?, favicon = ?, maintenance_mode = ?, 
            maintenance_message = ?, maintenance_minutes = ?
        WHERE system_id = 1
    ");

    $stmt->bind_param(
        "sssssssssisi",
        $_POST['system_name'],
        $_POST['system_short_name'],
        $_POST['currency'],
        $_POST['currency_symbol'],
        $_POST['contact_email'],
        $_POST['contact_phone'],
        $_POST['address'],
        $logo,
        $favicon,
        $m_mode,
        $_POST['maintenance_message'],
        $_POST['maintenance_minutes']
    );

    if ($stmt->execute()) {
        header("Location: system_settings.php?success=1");
        exit;
    }
}

include '../includes/header.php';
?>

<div class="px-8 max-w-full mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
        <div>
            <h1 class="text-4xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                System Configuration
            </h1>
            <p class="text-sm opacity-60 font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                Manage global branding, currency, and maintenance settings.
            </p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div
                class="bg-emerald-500/10 text-emerald-500 px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest border border-emerald-500/20 animate-bounce">
                <i class="fa-solid fa-circle-check mr-2"></i> Settings Saved
            </div>
        <?php endif; ?>
    </div>

    <form method="POST" enctype="multipart/form-data" class="space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-8">
                <div
                    class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] p-8 shadow-xl">
                    <h3 class="text-xs font-black uppercase tracking-widest mb-6 opacity-40">Visual Identity</h3>

                    <div class="space-y-6">
                        <div
                            class="text-center p-6 rounded-[2rem] border-2 border-dashed <?= $isDark ? 'border-gray-700 bg-gray-900/30' : 'border-gray-100 bg-gray-50' ?>">
                            <label class="block text-[10px] font-black uppercase tracking-tighter mb-4 opacity-50">Main
                                System Logo</label>
                            <?php if ($system['logo']): ?>
                                <img src="../uploads/system/<?= $system['logo'] ?>"
                                    class="h-16 mx-auto mb-4 drop-shadow-lg">
                            <?php endif; ?>
                            <input type="file" name="logo"
                                class="text-[10px] block w-full text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-blue-500 file:text-white hover:file:bg-blue-600 cursor-pointer">
                        </div>

                        <div
                            class="text-center p-6 rounded-[2rem] border-2 border-dashed <?= $isDark ? 'border-gray-700 bg-gray-900/30' : 'border-gray-100 bg-gray-50' ?>">
                            <label
                                class="block text-[10px] font-black uppercase tracking-tighter mb-4 opacity-50">Favicon
                                (ICO/PNG)</label>
                            <?php if ($system['favicon']): ?>
                                <img src="../uploads/system/<?= $system['favicon'] ?>" class="h-8 mx-auto mb-4">
                            <?php endif; ?>
                            <input type="file" name="favicon"
                                class="text-[10px] block w-full text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-purple-500 file:text-white hover:file:bg-purple-600 cursor-pointer">
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-8">

                <div
                    class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] p-10 shadow-xl">
                    <h3 class="text-xs font-black uppercase tracking-widest mb-8 opacity-40">General Information</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60">Full
                                System Name</label>
                            <input name="system_name" value="<?= htmlspecialchars($system['system_name']) ?>" required
                                class="w-full border-2 <?= $isDark ? 'bg-gray-900/50 border-gray-700 text-white' : 'bg-gray-50 border-gray-100' ?> p-4 rounded-2xl outline-none focus:border-blue-500 transition-all font-bold">
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60">Short
                                Name</label>
                            <input name="system_short_name"
                                value="<?= htmlspecialchars($system['system_short_name']) ?>"
                                class="w-full border-2 <?= $isDark ? 'bg-gray-900/50 border-gray-700 text-white' : 'bg-gray-50 border-gray-100' ?> p-4 rounded-2xl outline-none focus:border-blue-500 transition-all font-bold">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60">Currency
                                    Code</label>
                                <input name="currency" value="<?= htmlspecialchars($system['currency']) ?>"
                                    placeholder="USD"
                                    class="w-full border-2 <?= $isDark ? 'bg-gray-900/50 border-gray-700 text-white' : 'bg-gray-50 border-gray-100' ?> p-4 rounded-2xl outline-none focus:border-blue-500 transition-all font-bold">
                            </div>
                            <div>
                                <label
                                    class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60">Symbol</label>
                                <input name="currency_symbol"
                                    value="<?= htmlspecialchars($system['currency_symbol']) ?>" placeholder="$"
                                    class="w-full border-2 <?= $isDark ? 'bg-gray-900/50 border-gray-700 text-white' : 'bg-gray-50 border-gray-100' ?> p-4 rounded-2xl outline-none focus:border-blue-500 transition-all font-bold text-center">
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] p-10 shadow-xl">
                    <h3 class="text-xs font-black uppercase tracking-widest mb-8 opacity-40">Contact & Localization</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label
                                class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60">Contact
                                Email</label>
                            <input type="email" name="contact_email"
                                value="<?= htmlspecialchars($system['contact_email'] ?? '') ?>"
                                class="w-full border-2 <?= $isDark ? 'bg-gray-900/50 border-gray-700 text-white' : 'bg-gray-50 border-gray-100' ?> p-4 rounded-2xl outline-none focus:border-blue-500 font-bold">
                        </div>
                        <div>
                            <label
                                class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60">Contact
                                Phone</label>
                            <input type="text" name="contact_phone"
                                value="<?= htmlspecialchars($system['contact_phone'] ?? '') ?>"
                                class="w-full border-2 <?= $isDark ? 'bg-gray-900/50 border-gray-700 text-white' : 'bg-gray-50 border-gray-100' ?> p-4 rounded-2xl outline-none focus:border-blue-500 font-bold">
                        </div>
                        <div class="md:col-span-2">
                            <label
                                class="block text-[10px] font-black uppercase tracking-widest mb-2 opacity-60">Physical
                                Address</label>
                            <textarea name="address" rows="2"
                                class="w-full border-2 <?= $isDark ? 'bg-gray-900/50 border-gray-700 text-white' : 'bg-gray-50 border-gray-100' ?> p-4 rounded-2xl outline-none focus:border-blue-500 font-bold"><?= htmlspecialchars($system['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-rose-500/5 border border-rose-500/20 rounded-[2.5rem] p-10">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-rose-500 text-xs font-black uppercase tracking-widest">Maintenance Mode</h3>
                            <p class="text-[10px] text-rose-500/60 font-bold uppercase">Restricts non-admin access
                                during updates</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer scale-125">
                            <input type="checkbox" name="maintenance_mode" class="sr-only peer"
                                <?= $system['maintenance_mode'] ? 'checked' : '' ?>>
                            <div
                                class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-500">
                            </div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <input type="text" name="maintenance_message" placeholder="Maintenance Message..."
                                value="<?= htmlspecialchars($system['maintenance_message']) ?>"
                                class="w-full border-2 <?= $isDark ? 'bg-gray-900/50 border-rose-500/20 text-white' : 'bg-white border-rose-100' ?> p-4 rounded-2xl outline-none focus:border-rose-500 font-bold text-sm">
                        </div>
                        <div>
                            <input type="number" name="maintenance_minutes" placeholder="Minutes"
                                value="<?= (int) $system['maintenance_minutes'] ?>"
                                class="w-full border-2 <?= $isDark ? 'bg-gray-900/50 border-rose-500/20 text-white' : 'bg-white border-rose-100' ?> p-4 rounded-2xl outline-none focus:border-rose-500 font-bold text-sm text-center">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button
                class="group bg-blue-600 hover:bg-blue-500 text-white px-12 py-5 rounded-[2rem] font-black text-sm transition-all shadow-xl shadow-blue-500/20 hover:scale-105 active:scale-95 flex items-center gap-4">
                Deploy Configuration <i
                    class="fa-solid fa-bolt-lightning group-hover:text-yellow-400 transition-colors"></i>
            </button>
        </div>
    </form>
</div>

<style>
    body {
        background-color:
            <?= $isDark ? '#0f172a' : '#f8fafc' ?>
        ;
    }
</style>

<?php include '../includes/footer.php'; ?>