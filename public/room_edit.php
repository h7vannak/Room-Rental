<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAuth();

// 1. Get ID and Validate
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: rooms.php');
    exit;
}

// 2. Fetch current room details (Using plural table 'rooms')
$stmt = $conn->prepare("SELECT * FROM rooms WHERE room_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    die("Error: Room not found.");
}

// 3. Fetch dropdown options (Using plural tables 'room_types' and 'renters')
$types = $conn->query("SELECT * FROM room_types ORDER BY room_type_name ASC")->fetch_all(MYSQLI_ASSOC);
$renters = $conn->query("SELECT * FROM renters ORDER BY renter_name ASC")->fetch_all(MYSQLI_ASSOC);

// 4. Handle Update Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number  = $_POST['room_number'];
    $room_type_id = $_POST['room_type_id'];
    $renter_id    = ($_POST['renter_id'] == "0") ? null : $_POST['renter_id'];
    $rent_date    = !empty($_POST['rent_date']) ? $_POST['rent_date'] : null;
    $qty_person   = $_POST['qty_person'];
    
    // Safety check: Don't let POST overwrite status if it's already ACTIVE
    $status = ($room['status'] === 'ACTIVE') ? 'ACTIVE' : $_POST['status'];

    $updateStmt = $conn->prepare("
        UPDATE rooms
        SET room_number=?, room_type_id=?, renter_id=?, rent_date=?, qty_person=?, status=?
        WHERE room_id=?
    ");

    // "siisssi" -> string, int, int, string, string, string, int
    // Adjust types if qty_person is an integer in your DB: "siisisi"
    $updateStmt->bind_param("siisssi", $room_number, $room_type_id, $renter_id, $rent_date, $qty_person, $status, $id);

    if ($updateStmt->execute()) {
        // 5. Log the action
        $userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0;
        $action = "UPDATE_ROOM";
        $desc = "Modified Room #$room_number (Type ID: $room_type_id, Status: $status)";
        
        $logStmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)");
        $logStmt->bind_param("iss", $userId, $action, $desc);
        $logStmt->execute();

        header('Location: rooms.php?updated=1');
        exit;
    } else {
        $error = "Update failed: " . $conn->error;
    }
}

include '../includes/header.php';
$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';
?>

<div class="max-w-2xl mx-auto p-4">
    <div class="flex items-center gap-4 mb-6">
        <a href="rooms.php" class="text-blue-600 hover:text-blue-700 transition">
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </a>
        <h1 class="text-2xl font-bold">Edit Room Details</h1>
    </div>

    <?php if(isset($error)): ?>
        <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> p-8 rounded-2xl shadow-sm border">
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div class="md:col-span-1">
                    <label class="block text-[10px] font-bold uppercase opacity-60 mb-1">Room Number</label>
                    <input type="text" name="room_number" value="<?= htmlspecialchars($room['room_number']) ?>" required class="custom-input">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-[10px] font-bold uppercase opacity-60 mb-1">Room Type</label>
                    <select name="room_type_id" class="custom-input">
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t['room_type_id'] ?>" <?= $room['room_type_id'] == $t['room_type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['room_type_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-[10px] font-bold uppercase opacity-60 mb-1">Assigned Tenant</label>
                    <select name="renter_id" class="custom-input">
                        <option value="0">-- No Tenant (Available) --</option>
                        <?php foreach ($renters as $r): ?>
                            <option value="<?= $r['renter_id'] ?>" <?= $room['renter_id'] == $r['renter_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['renter_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-[10px] font-bold uppercase opacity-60 mb-1">Occupants (Qty)</label>
                    <input type="number" name="qty_person" value="<?= $room['qty_person'] ?>" class="custom-input">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-[10px] font-bold uppercase opacity-60 mb-1">Check-in Date</label>
                    <input type="date" name="rent_date" value="<?= $room['rent_date'] ?>" class="custom-input">
                </div>

                <div class="md:col-span-1">
                    <label class="block text-[10px] font-bold uppercase opacity-60 mb-1">Status</label>
                    <select name="status" class="custom-input <?= $room['status'] === 'ACTIVE' ? 'bg-gray-700/50 opacity-50' : '' ?>" <?= $room['status'] === 'ACTIVE' ? 'disabled' : '' ?>>
                        <option value="AVAILABLE" <?= $room['status'] === 'AVAILABLE' ? 'selected' : '' ?>>AVAILABLE</option>
                        <option value="ACTIVE" <?= $room['status'] === 'ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                    </select>
                    <?php if ($room['status'] === 'ACTIVE'): ?>
                        <p class="text-[10px] text-orange-500 mt-1 font-bold italic">
                            * Use "Checkout" on the list page to change status.
                        </p>
                    <?php endif; ?>
                </div>

            </div>

            <div class="pt-6 flex gap-3">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-blue-500/30">
                    <i class="fa-solid fa-save mr-2"></i> Update Room
                </button>
                <a href="rooms.php" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 rounded-xl transition text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.custom-input {
    width: 100%;
    padding: 10px 12px;
    background-color: transparent;
    border: 1px solid <?= $isDark ? '#4b5563' : '#e5e7eb' ?>;
    border-radius: 10px;
    color: <?= $isDark ? '#fff' : '#000' ?>;
    outline: none;
    font-size: 0.875rem;
}
.custom-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
.custom-input:disabled {
    cursor: not-allowed;
}
</style>

<?php include '../includes/footer.php'; ?>