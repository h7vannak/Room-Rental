<?php
include '../includes/db.php';
include '../includes/header.php';

$types = $pdo->query("SELECT * FROM room_type ORDER BY room_type_id")->fetchAll();
?>

<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Room Types</h1>
    <a href="room_type_create.php"
       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        + Add Room Type
    </a>
</div>

<table class="min-w-full bg-white rounded shadow">
<thead class="bg-gray-200">
<tr>
    <th class="px-4 py-2">ID</th>
    <th class="px-4 py-2">Type Name</th>
    <th class="px-4 py-2">Base Fee</th>
    <th class="px-4 py-2">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($types as $t): ?>
<tr class="border-t hover:bg-gray-50">
    <td class="px-4 py-2"><?= $t['room_type_id'] ?></td>
    <td class="px-4 py-2"><?= $t['room_type_name'] ?></td>
    <td class="px-4 py-2">$<?= number_format($t['base_room_fee'], 2) ?></td>
    <td class="px-4 py-2 space-x-2">
        <a href="room_type_edit.php?id=<?= $t['room_type_id'] ?>"
           class="text-blue-600 hover:underline">Edit</a>
        <a href="room_type_delete.php?id=<?= $t['room_type_id'] ?>"
           class="text-red-600 hover:underline"
           onclick="return confirm('Delete this room type?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php include '../includes/footer.php'; ?>
