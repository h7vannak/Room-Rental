<?php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO room_type (room_type_name, base_room_fee)
        VALUES (?, ?)
    ");
    $stmt->execute([
        $_POST['name'],
        $_POST['fee']
    ]);
    header('Location: room_types.php');
    exit;
}

include '../includes/header.php';
?>

<h1 class="text-2xl font-bold mb-4">Add Room Type</h1>

<form method="POST" class="bg-white p-6 rounded shadow max-w-lg">
    <input name="name" placeholder="Room Type Name" required class="input">
    <input name="fee" type="number" step="0.01"
           placeholder="Base Room Fee" required class="input">

    <button class="bg-blue-600 text-white px-4 py-2 rounded mt-3">
        Save
    </button>
</form>

<style>
.input {
    width:100%;
    padding:8px;
    margin-bottom:10px;
    border:1px solid #ddd;
    border-radius:6px;
}
</style>

<?php include '../includes/footer.php'; ?>
