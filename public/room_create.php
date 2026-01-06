<?php
include '../includes/db.php';

$types = $pdo->query("SELECT * FROM room_type")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO room (room_id, room_type_id, capacity)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $_POST['room_id'],
        $_POST['room_type_id'],
        $_POST['capacity']
    ]);
    header('Location: rooms.php');
    exit;
}

include '../includes/header.php';
?>

<h1 class="text-2xl font-bold mb-4">Add Room</h1>

<form method="POST" class="bg-white p-6 rounded shadow max-w-lg">
    <input name="room_id" placeholder="Room Number" required
           class="input">

    <select name="room_type_id" class="input" required>
        <option value="">Select Room Type</option>
        <?php foreach ($types as $t): ?>
            <option value="<?= $t['room_type_id'] ?>">
                <?= $t['room_type_name'] ?> ($<?= $t['base_room_fee'] ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <input name="capacity" placeholder="Capacity"
           type="number" class="input">

    <button class="bg-blue-600 text-white px-4 py-2 rounded mt-3">
        Save Room
    </button>
</form>

<style>
.input {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
}
</style>

<?php include '../includes/footer.php'; ?>
