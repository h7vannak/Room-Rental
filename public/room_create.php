<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAuth();

/* ===============================
   SAVE ROOM LOGIC
================================ */
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Collect and Clean Data
    $room_number  = $_POST['room_number'] ?? '';
    $room_type_id = $_POST['room_type_id'] ?? '';
    $renter_id    = !empty($_POST['renter_id']) ? $_POST['renter_id'] : null;
    $rent_date    = !empty($_POST['rent_date']) ? $_POST['rent_date'] : null;
    $qty_person   = !empty($_POST['qty_person']) ? $_POST['qty_person'] : 0;
    $status       = $_POST['status'] ?? 'Available';

    // 2. Prepare Insertion
    // Verify your table is named 'rooms' or 'room' here:
    $stmt = $conn->prepare("
        INSERT INTO rooms (room_number, room_type_id, renter_id, rent_date, qty_person, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        // This stops the Fatal Error and tells you EXACTLY what is wrong with the SQL
        die("Database Structure Error: " . $conn->error . ". <br>Check if columns 'room_number, room_type_id, renter_id, rent_date, qty_person, status' exist in table 'rooms'.");
    }

    // Bind types: s=string, i=int, i=int, s=string, i=int, s=string
    $stmt->bind_param("siisis", $room_number, $room_type_id, $renter_id, $rent_date, $qty_person, $status);
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;

        // 3. Audit Log
        $user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0; 
        $action = 'CREATE_ROOM';
        $description = "Created new room #$room_number (ID: $new_id)";

        $log = $conn->prepare("INSERT INTO audit_log (user_id, action, description) VALUES (?, ?, ?)");
        if ($log) {
            $log->bind_param("iss", $user_id, $action, $description);
            $log->execute();
        }

        header('Location: rooms.php?success=1');
        exit;
    } else {
        $error = "Execution Error: " . $stmt->error;
    }
}

include '../includes/header.php';

/* ===============================
   LOAD DATA FOR DROPDOWNS
================================ */
// Verify table name 'room_types' and column 'room_type_id'
$types_query = $conn->query("SELECT * FROM room_types ORDER BY room_type_name ASC");
$types = ($types_query) ? $types_query->fetch_all(MYSQLI_ASSOC) : [];

$renters_query = $conn->query("SELECT renter_id, renter_name FROM renters ORDER BY renter_name ASC");
$renters = ($renters_query) ? $renters_query->fetch_all(MYSQLI_ASSOC) : [];

$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';
?>

<div class="max-w-3xl mx-auto p-6">
    <div class="flex items-center gap-6 mb-10">
        <a href="rooms.php" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-gray-500/10 hover:bg-blue-600 hover:text-white text-blue-600 transition-all">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-3xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">Register Unit</h1>
            <p class="text-sm font-medium opacity-50">Add a new room to the management system</p>
        </div>
    </div>

    <?php if($error): ?>
        <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 text-red-500 rounded-2xl font-bold text-sm">
            <i class="fa-solid fa-circle-exclamation mr-2"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> p-10 rounded-[2.5rem] shadow-2xl border backdrop-blur-md transition-all">
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <div class="md:col-span-1">
                <label class="block text-[10px] font-black uppercase mb-3 tracking-[0.2em] opacity-60">
                    <i class="fa-solid fa-door-closed mr-2 text-blue-500"></i> Room Number
                </label>
                <input type="text" name="room_number" placeholder="e.g. A-101" required class="custom-input">
            </div>

            <div class="md:col-span-1">
                <label class="block text-[10px] font-black uppercase mb-3 tracking-[0.2em] opacity-60">
                    <i class="fa-solid fa-layer-group mr-2 text-purple-500"></i> Category
                </label>
                <div class="relative">
                    <select name="room_type_id" required class="custom-input appearance-none cursor-pointer">
                        <option value="">-- Select Type --</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t['room_type_id'] ?>">
                                <?= htmlspecialchars($t['room_type_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 opacity-30 text-[10px] pointer-events-none"></i>
                </div>
            </div>

            <div class="md:col-span-1">
                <label class="block text-[10px] font-black uppercase mb-3 tracking-[0.2em] opacity-60">
                    <i class="fa-solid fa-user-tag mr-2 text-emerald-500"></i> Initial Tenant
                </label>
                <div class="relative">
                    <select name="renter_id" class="custom-input appearance-none cursor-pointer">
                        <option value="">-- No Tenant (Vacant) --</option>
                        <?php foreach ($renters as $r): ?>
                            <option value="<?= $r['renter_id'] ?>">
                                <?= htmlspecialchars($r['renter_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 opacity-30 text-[10px] pointer-events-none"></i>
                </div>
            </div>

            <div class="md:col-span-1">
                <label class="block text-[10px] font-black uppercase mb-3 tracking-[0.2em] opacity-60">
                    <i class="fa-solid fa-users mr-2 text-orange-500"></i> Max Capacity
                </label>
                <input type="number" name="qty_person" placeholder="e.g. 2" class="custom-input">
            </div>

            <div class="md:col-span-1">
                <label class="block text-[10px] font-black uppercase mb-3 tracking-[0.2em] opacity-60">
                    <i class="fa-solid fa-calendar-check mr-2 text-rose-500"></i> Lease Start Date
                </label>
                <input type="date" name="rent_date" class="custom-input">
            </div>

            <div class="md:col-span-1">
                <label class="block text-[10px] font-black uppercase mb-3 tracking-[0.2em] opacity-60">
                    <i class="fa-solid fa-toggle-on mr-2 text-sky-500"></i> Initial Status
                </label>
                <div class="relative">
                    <select name="status" class="custom-input appearance-none cursor-pointer">
                        <option value="Available" selected>Available</option>
                        <option value="Occupied">Occupied (Active Lease)</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 opacity-30 text-[10px] pointer-events-none"></i>
                </div>
            </div>

            <div class="md:col-span-2 pt-6 flex flex-col md:flex-row gap-4">
                <button type="submit" class="flex-[2] bg-blue-600 hover:bg-blue-500 text-white font-black py-5 rounded-2xl transition shadow-xl shadow-blue-500/20 active:scale-95 flex items-center justify-center gap-3">
                    <i class="fa-solid fa-circle-plus"></i> Initialize Room
                </button>
                <a href="rooms.php" class="flex-1 bg-gray-500/10 hover:bg-gray-500/20 text-center font-black py-5 rounded-2xl transition flex items-center justify-center">
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