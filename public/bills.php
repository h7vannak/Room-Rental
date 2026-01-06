<?php
include '../includes/db.php';
include '../includes/header.php';

$sql = "
SELECT 
    mb.bill_id,
    r.renter_name,
    rm.room_id,
    mb.bill_month,
    rt.base_room_fee,
    (mb.new_electric - mb.old_electric) * ur.electric_rate AS electric_fee,
    mb.water_units * ur.water_rate AS water_fee,
    rt.base_room_fee +
    ((mb.new_electric - mb.old_electric) * ur.electric_rate) +
    (mb.water_units * ur.water_rate) AS total
FROM monthly_bill mb
JOIN rental rl ON mb.rental_id = rl.rental_id
JOIN renter r ON rl.renter_id = r.renter_id
JOIN room rm ON rl.room_id = rm.room_id
JOIN room_type rt ON rm.room_type_id = rt.room_type_id
JOIN utility_rate ur ON mb.rate_id = ur.rate_id
ORDER BY mb.bill_month DESC
";

$bills = $pdo->query($sql)->fetchAll();
?>

<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Monthly Bills</h1>
    <a href="bill_create.php"
       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        + Add Bill
    </a>
</div>

<table class="min-w-full bg-white rounded shadow">
<thead class="bg-gray-200">
<tr>
    <th class="px-3 py-2">Bill ID</th>
    <th class="px-3 py-2">Renter</th>
    <th class="px-3 py-2">Room</th>
    <th class="px-3 py-2">Month</th>
    <th class="px-3 py-2">Room Fee</th>
    <th class="px-3 py-2">Electric</th>
    <th class="px-3 py-2">Water</th>
    <th class="px-3 py-2">Total</th>
    <th class="px-3 py-2">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($bills as $b): ?>
<tr class="border-t hover:bg-gray-50">
    <td class="px-3 py-2"><?= $b['bill_id'] ?></td>
    <td class="px-3 py-2"><?= $b['renter_name'] ?></td>
    <td class="px-3 py-2"><?= $b['room_id'] ?></td>
    <td class="px-3 py-2"><?= date('d/M/Y', strtotime($b['bill_month'])) ?></td>
    <td class="px-3 py-2">$<?= number_format($b['base_room_fee'],2) ?></td>
    <td class="px-3 py-2">$<?= number_format($b['electric_fee'],2) ?></td>
    <td class="px-3 py-2">$<?= number_format($b['water_fee'],2) ?></td>
    <td class="px-3 py-2 font-bold">$<?= number_format($b['total'],2) ?></td>
    <td class="px-3 py-2 space-x-2">
        <a href="bill_edit.php?id=<?= $b['bill_id'] ?>" class="text-blue-600">Edit</a>
        <a href="bill_delete.php?id=<?= $b['bill_id'] ?>"
           onclick="return confirm('Delete bill?')"
           class="text-red-600">Delete</a>
        <a href="invoice.php?id=<?= $b['bill_id'] ?>"
            target="_blank"
            class="text-green-600 hover:underline">
            PDF
        </a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php include '../includes/footer.php'; ?>
