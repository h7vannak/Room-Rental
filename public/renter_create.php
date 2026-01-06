<?php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO renter (renter_name, gender, mobile_number, telegram, renter_address, nationality)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['name'],
        $_POST['gender'],
        $_POST['mobile'],
        $_POST['telegram'],
        $_POST['address'],
        $_POST['nationality']
    ]);
    header('Location: renters.php');
    exit;
}

include '../includes/header.php';
?>

<h1 class="text-2xl font-bold mb-4">Add Renter</h1>

<form method="POST" class="bg-white p-6 rounded shadow max-w-lg">
    <input name="name" placeholder="Name" required class="input" />
    <select name="gender" class="input">
        <option>Male</option>
        <option>Female</option>
    </select>
    <input name="mobile" placeholder="Mobile" class="input" />
    <input name="telegram" placeholder="Telegram" class="input" />
    <input name="address" placeholder="Address" class="input" />
    <input name="nationality" placeholder="Nationality" class="input" />

    <button class="bg-blue-600 text-white px-4 py-2 rounded mt-3">
        Save
    </button>
</form>

<style>
.input { width:100%; padding:8px; margin-bottom:10px; border:1px solid #ddd; border-radius:6px; }
</style>

<?php include '../includes/footer.php'; ?>
