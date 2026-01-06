<?php
include '../includes/db.php';

$rentals = $pdo->query("
    SELECT rl.rental_id, r.renter_name, rm.room_id
    FROM rental rl
    JOIN renter r ON rl.renter_id = r.renter_id
    JOIN room rm ON rl.room_id = rm.room_id
")->fetchAll();

$rates = $pdo->query("SELECT * FROM utility_rate")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO monthly_bill
        (rental_id, rate_id, bill_month, old_electric, new_electric, water_units)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['rental_id'],
        $_POST['rate_id'],
        $_POST['bill_month'],
        $_POST['old_electric'],
        $_POST['new_electric'],
        $_POST['water_units']
    ]);
    header('Location: bills.php');
    exit;
}

include '../includes/header.php';
?>

<h1 class="text-2xl font-bold mb-4">Add Monthly Bill</h1>

<form method="POST" class="bg-white p-6 rounded shadow max-w-lg">

<select name="rental_id" class="input" required>
    <option value="">Select Renter</option>
    <?php foreach ($rentals as $r): ?>
        <option value="<?= $r['rental_id'] ?>">
            <?= $r['renter_name'] ?> (Room <?= $r['room_id'] ?>)
        </option>
    <?php endforeach; ?>
</select>

<select name="rate_id" class="input" required>
    <?php foreach ($rates as $rate): ?>
        <option value="<?= $rate['rate_id'] ?>">
            Electric $<?= $rate['electric_rate'] ?> | Water $<?= $rate['water_rate'] ?>
        </option>
    <?php endforeach; ?>
</select>

<input type="date" name="bill_month" class="input" required>
<input type="number" step="0.01" name="old_electric" placeholder="Old Electric" class="input">
<input type="number" step="0.01" name="new_electric" placeholder="New Electric" class="input">
<input type="number" step="0.01" name="water_units" placeholder="Water Units" class="input">

<button class="bg-blue-600 text-white px-4 py-2 rounded mt-3">
    Save Bill
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
