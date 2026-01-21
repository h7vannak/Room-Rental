<?php
require_once '../includes/db.php'; 
require_once '../includes/auth.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $fee = $_POST['fee'];

    // 1. Prepare statement (Using plural table 'room_types')
    $stmt = $conn->prepare("INSERT INTO room_types (room_type_name, base_room_fee) VALUES (?, ?)");
    
    if (!$stmt) {
        die("Database Error: " . $conn->error);
    }
    
    $stmt->bind_param("sd", $name, $fee);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;

        // 2. Log action (Using safety check for user session keys)
        $userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;
        $action = "CREATE_ROOM_TYPE";
        $desc = "Created room type: $name with base fee $$fee";
        
        $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
        $logStmt->bind_param("iss", $userId, $action, $desc);
        $logStmt->execute();

        $_SESSION['msg'] = "New room type '$name' created successfully!";
        $_SESSION['msg_type'] = "success";

        header('Location: room_types.php');
        exit;
    } else {
        $error = "Error saving room type: " . $conn->error;
    }
}

include '../includes/header.php';
$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';
?>

<div class="max-w-xl mx-auto p-4">
    <div class="flex items-center gap-6 mb-10">
        <a href="room_types.php" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-gray-500/10 hover:bg-blue-600 hover:text-white text-blue-600 transition-all shadow-sm">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">New Category</h1>
            <p class="text-sm font-medium opacity-50">Define a new pricing tier for your units</p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-xl mb-6 flex items-center gap-3">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span class="text-sm"><?= $error ?></span>
        </div>
    <?php endif; ?>

    <div class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> p-10 rounded-[2.5rem] shadow-2xl border backdrop-blur-md">
        <form method="POST" class="space-y-6">
            
            <div>
                <label class="block text-[10px] font-black uppercase mb-3 tracking-[0.2em] opacity-60">Category Name</label>
                <input name="name" placeholder="e.g. VIP, Studio, Suite" required 
                       class="custom-input font-bold">
                <p class="text-[10px] mt-2 opacity-50 italic">This name will appear in the room registration dropdown.</p>
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase mb-3 tracking-[0.2em] opacity-60">Base Monthly Fee (USD)</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 opacity-40 font-bold">$</span>
                    <input name="fee" type="number" step="0.01" placeholder="0.00" required 
                           class="custom-input pl-8 font-mono text-lg">
                </div>
            </div>

            <div class="pt-6 flex flex-col md:flex-row gap-4">
                <button type="submit" class="flex-[2] bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-2xl transition shadow-xl shadow-blue-500/20 active:scale-95 flex items-center justify-center gap-3">
                    <i class="fa-solid fa-circle-plus"></i> Save Category
                </button>
                <a href="room_types.php" class="flex-1 bg-gray-500/10 hover:bg-gray-500/20 text-center font-bold py-5 rounded-2xl transition flex items-center justify-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.custom-input {
    width: 100%;
    padding: 1rem 1.25rem;
    background-color: <?= $isDark ? 'rgba(15, 23, 42, 0.5)' : 'rgba(248, 250, 252, 1)' ?>;
    border: 2px solid <?= $isDark ? '#374151' : '#f1f5f9' ?>;
    border-radius: 1.25rem;
    color: inherit;
    outline: none;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}
.custom-input:focus {
    border-color: #2563eb;
    background-color: <?= $isDark ? 'rgba(15, 23, 42, 0.8)' : '#fff' ?>;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}
</style>

<?php include '../includes/footer.php'; ?>