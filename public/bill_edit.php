<?php
include '../includes/db.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM monthly_bill WHERE bill_id=?");
$stmt->execute([$id]);
$bill = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE monthly_bill
        SET old_electric=?, new_electric=?, water_units=?
        WHERE bill_id=?
    ");
    $stmt->execute([
        $_POST['old_electric'],
        $_POST['new_electric'],
        $_POST['water_units'],
        $id
    ]);
    header('Location: bills.php');
    exit;
}

include '../includes/header.php';
?>

<h1 class="text-2xl font-bold mb-4">Edit Monthly Bill</h1>

<form method="POST" class="bg-white p-6 rounded shadow max-w-lg">

<input value="<?= $bill['old_electric'] ?>" name="old_electric" class="input">
<input value="<?= $bill['new_electric'] ?>" name="new_electric" class="input">
<input value="<?= $bill['water_units'] ?>" name="water_units" class="input">

<button class="bg-green-600 text-white px-4 py-2 rounded mt-3">
    Update Bill
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
