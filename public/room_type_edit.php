<?php
require_once '../includes/db.php'; 
require_once '../includes/auth.php';

requireAuth();

// 1. Get ID safely from the URL
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: room_types.php');
    exit;
}

// 2. Fetch current data (Using plural table name 'room_types')
$stmt = $conn->prepare("SELECT * FROM room_types WHERE room_type_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$type = $result->fetch_assoc();

if (!$type) {
    die("Error: Room Type not found.");
}

// 3. Handle the Update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $fee = $_POST['fee'];

    $updateStmt = $conn->prepare("
        UPDATE room_types
        SET room_type_name = ?, 
            base_room_fee = ?
        WHERE room_type_id = ?
    ");

    // Use "di" if fee is a decimal/float
    $updateStmt->bind_param("sdi", $name, $fee, $id);

    if ($updateStmt->execute()) {
        // 4. Log the action to audit_log
        $userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;
        $action = "UPDATE_ROOM_TYPE";
        $desc = "Updated room type #$id: $name (New Fee: $$fee)";
        
        $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
        $logStmt->bind_param("iss", $userId, $action, $desc);
        $logStmt->execute();

        $_SESSION['msg'] = "Room type '$name' updated successfully!";
        $_SESSION['msg_type'] = "success";

        header('Location: room_types.php');
        exit;
    } else {
        $error = "Update failed: " . $conn->error;
    }
}

include '../includes/header.php';
$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';
?>

<div class="max-w-xl mx-auto p-4">
    <div class="flex items-center gap-6 mb-10">
        <a href="room_types.php" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-gray-500/10 hover:bg-emerald-600 hover:text-white text-emerald-600 transition-all shadow-sm">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">Edit Category</h1>
            <p class="text-sm font-bold text-emerald-500 uppercase tracking-widest"><?= htmlspecialchars($type['room_type_name']) ?></p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 flex items-center gap-3">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span class="text-sm"><?= $error ?></span>
        </div>
    <?php endif; ?>

    <div class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> p-8 rounded-[2rem] shadow-2xl border backdrop-blur-md">
        <form method="POST" class="space-y-6">
            
            <div>
                <label class="block text-[10px] font-black uppercase opacity-60 mb-2 tracking-[0.2em]">Category Name</label>
                <input name="name" value="<?= htmlspecialchars($type['room_type_name']) ?>" required 
                       class="custom-input font-bold" placeholder="e.g. Luxury Suite">
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase opacity-60 mb-2 tracking-[0.2em]">Base Monthly Fee (USD)</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 opacity-40 font-bold">$</span>
                    <input name="fee" type="number" step="0.01" value="<?= $type['base_room_fee'] ?>" required 
                           class="custom-input pl-8 font-mono text-lg">
                </div>
                <div class="mt-3 p-3 bg-blue-500/5 rounded-lg border border-blue-500/10">
                    <p class="text-[10px] text-blue-500 leading-relaxed font-medium">
                        <i class="fa-solid fa-circle-info mr-1"></i>
                        Note: Changing this fee will not affect existing active bills. It only applies to new invoices created from this point forward.
                    </p>
                </div>
            </div>

            <div class="pt-4 flex flex-col md:flex-row gap-3">
                <button type="submit" class="flex-[2] bg-emerald-600 hover:bg-emerald-500 text-white font-black py-4 rounded-2xl transition shadow-xl shadow-emerald-500/20 active:scale-95 flex items-center justify-center gap-3">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
                <a href="room_types.php" class="flex-1 bg-gray-500/10 hover:bg-gray-500/20 text-center font-bold py-4 rounded-2xl transition flex items-center justify-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.custom-input {
    width: 100%;
    padding: 14px;
    background-color: <?= $isDark ? 'rgba(15, 23, 42, 0.5)' : 'rgba(248, 250, 252, 1)' ?>;
    border: 2px solid <?= $isDark ? '#374151' : '#f1f5f9' ?>;
    border-radius: 14px;
    color: inherit;
    outline: none;
    transition: all 0.2s;
}
.custom-input:focus {
    border-color: #10b981; 
    background-color: <?= $isDark ? 'rgba(15, 23, 42, 0.8)' : '#fff' ?>;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}
</style>

<?php include '../includes/footer.php'; ?>