<?php
include '../includes/db.php';

$id = $_GET['id'];

$roomStmt = $pdo->prepare("SELECT * FROM room WHERE room_id=?");
$roomStmt->execute([$id]);
$room = $roomStmt->fetch();

$types = $pdo->query("SELECT * FROM room_type")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE room
        SET room_type_id=?, capacity=?
        WHERE room_id=?
    ");
    $stmt->execute([
        $_POST['room_type_id'],
        $_POST['capacity'],
        $id
    ]);
    header('Location: rooms.php');
    exit;
}

include '../includes/header.php';
?>

<h1 class="text-2xl font-bold mb-4">Edit Room</h1>

<form method="POST" class="bg-white p-6 rounded shadow max-w-lg">
    <p class="mb-2 font-semibold">Room #: <?= $room['room_id'] ?></p>

    <select name="room_type_id" class="input">
        <?php foreach ($types as $t): ?>
            <option value="<?= $t['room_type_id'] ?>"
                <?= $room['room_type_id'] == $t['room_type_id'] ? 'selected' : '' ?>>
                <?= $t['room_type_name'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input name="capacity"
           value="<?= $room['capacity'] ?>"
           class="input">

    <button class="bg-green-600 text-white px-4 py-2 rounded mt-3">
        Update Room
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
