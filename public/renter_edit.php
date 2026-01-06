<?php
include '../includes/db.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM renter WHERE renter_id=?");
$stmt->execute([$id]);
$renter = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        UPDATE renter
        SET renter_name=?, gender=?, mobile_number=?, telegram=?, renter_address=?, nationality=?
        WHERE renter_id=?
    ");
    $stmt->execute([
        $_POST['name'],
        $_POST['gender'],
        $_POST['mobile'],
        $_POST['telegram'],
        $_POST['address'],
        $_POST['nationality'],
        $id
    ]);
    header('Location: renters.php');
    exit;
}

include '../includes/header.php';
?>

<h1 class="text-2xl font-bold mb-4">Edit Renter</h1>

<form method="POST" class="bg-white p-6 rounded shadow max-w-lg">
    <input name="name" value="<?= $renter['renter_name'] ?>" class="input" />
    <input name="gender" value="<?= $renter['gender'] ?>" class="input" />
    <input name="mobile" value="<?= $renter['mobile_number'] ?>" class="input" />
    <input name="telegram" value="<?= $renter['telegram'] ?>" class="input" />
    <input name="address" value="<?= $renter['renter_address'] ?>" class="input" />
    <input name="nationality" value="<?= $renter['nationality'] ?>" class="input" />

    <button class="bg-green-600 text-white px-4 py-2 rounded mt-3">
        Update
    </button>
</form>

<style>
.input { width:100%; padding:8px; margin-bottom:10px; border:1px solid #ddd; border-radius:6px; }
</style>

<?php include '../includes/footer.php'; ?>
