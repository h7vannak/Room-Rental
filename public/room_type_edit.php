<?php
include '../includes/db.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM room_type WHERE room_type_id=?");
$stmt->execute([$id]);
$type = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE room_type
        SET room_type_name=?, base_room_fee=?
        WHERE room_type_id=?
    ");
    $stmt->execute([
        $_POST['name'],
        $_POST['fee'],
        $id
    ]);
    header('Location: room_types.php');
    exit;
}

include '../includes/header.php';
?>

<h1 class="text-2xl font-bold mb-4">Edit Room Type</h1>

<form method="POST" class="bg-white p-6 rounded shadow max-w-lg">
    <input name="name" value="<?= $type['room_type_name'] ?>" class="input">
    <input name="fee" value="<?= $type['base_room_fee'] ?>"
           type="number" step="0.01" class="input">

    <button class="bg-green-600 text-white px-4 py-2 rounded mt-3">
        Update
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
