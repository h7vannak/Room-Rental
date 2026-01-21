<?php
require_once '../includes/db.php'; 
require_once '../includes/auth.php';

requireAuth();

$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

// 1. Handle the reassignment process
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign'])) {
    $from_id = $_POST['from_type_id'];
    $to_id = $_POST['to_type_id'];

    if ($from_id == $to_id) {
        $error = "Source and destination categories cannot be the same.";
    } else {
        // Update all rooms from Old Type to New Type (Using plural 'rooms')
        $stmt = $conn->prepare("UPDATE rooms SET room_type_id = ? WHERE room_type_id = ?");
        $stmt->bind_param("ii", $to_id, $from_id);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            
            // Log it - using safe session key checks
            $userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;
            $action = "REASSIGN_ROOMS";
            $desc = "Moved $affected rooms from Type ID $from_id to $to_id";
            
            $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
            $logStmt->bind_param("iss", $userId, $action, $desc);
            $logStmt->execute();

            $_SESSION['msg'] = "Success! $affected rooms were reassigned.";
            $_SESSION['msg_type'] = "success";
            header('Location: room_types.php');
            exit;
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// 2. Fetch all types (Using plural 'room_types')
$types_query = $conn->query("SELECT * FROM room_types ORDER BY room_type_name ASC");
$types = $types_query ? $types_query->fetch_all(MYSQLI_ASSOC) : [];

include '../includes/header.php';
?>

<div class="max-w-2xl mx-auto p-4">
    <div class="mb-10 mt-6">
        <h1 class="text-3xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?> flex items-center gap-3">
            <i class="fa-solid fa-arrows-turn-to-dots text-blue-500"></i>
            Bulk Reassign Rooms
        </h1>
        <p class="text-sm font-medium opacity-50">Empty a category by moving its rooms to a different tier.</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-500/10 border border-red-500/20 text-red-500 p-4 rounded-2xl mb-6 flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span class="font-bold text-sm"><?= $error ?></span>
        </div>
    <?php endif; ?>

    <div class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> p-10 rounded-[2.5rem] shadow-2xl border backdrop-blur-md">
        <form method="POST" class="space-y-8">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                <div>
                    <label class="block text-[10px] font-black uppercase mb-3 tracking-[0.2em] text-red-500">
                        <i class="fa-solid fa-box-open mr-2"></i> Move Rooms FROM:
                    </label>
                    <div class="relative">
                        <select name="from_type_id" required class="custom-input border-red-500/20 appearance-none cursor-pointer">
                            <option value="">-- Select Source --</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t['room_type_id'] ?>">
                                    <?= htmlspecialchars($t['room_type_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 opacity-30 text-[10px]"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase mb-3 tracking-[0.2em] text-emerald-500">
                        <i class="fa-solid fa-box-archive mr-2"></i> Move Rooms TO:
                    </label>
                    <div class="relative">
                        <select name="to_type_id" required class="custom-input border-emerald-500/20 appearance-none cursor-pointer">
                            <option value="">-- Select Destination --</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= $t['room_type_id'] ?>">
                                    <?= htmlspecialchars($t['room_type_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 opacity-30 text-[10px]"></i>
                    </div>
                </div>
            </div>

            

            <div class="bg-blue-600/5 p-6 rounded-2xl border border-blue-500/10 flex gap-4 items-start">
                <i class="fa-solid fa-circle-info text-blue-500 mt-1"></i>
                <div>
                    <h4 class="text-xs font-black uppercase tracking-wider text-blue-500 mb-1">How it works</h4>
                    <p class="text-xs opacity-70 leading-relaxed font-medium">
                        This operation changes the <code class="font-bold">room_type_id</code> for all rooms associated with the source category. This is a <strong>permanent update</strong> but does not delete any room data.
                    </p>
                </div>
            </div>

            <div class="flex flex-col md:flex-row gap-4 pt-4">
                <button type="submit" name="reassign" class="flex-[2] bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-2xl transition shadow-xl shadow-blue-500/20 active:scale-95 flex items-center justify-center gap-3">
                    <i class="fa-solid fa-bolt"></i> Execute Reassignment
                </button>
                <a href="room_types.php" class="flex-1 bg-gray-500/10 hover:bg-gray-500/20 text-center font-black py-5 rounded-2xl transition flex items-center justify-center">
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
    font-weight: 700;
    transition: all 0.2s ease;
}
.custom-input:focus {
    border-color: #2563eb;
    background-color: <?= $isDark ? 'rgba(15, 23, 42, 0.8)' : '#fff' ?>;
}
</style>

<?php include '../includes/footer.php'; ?>