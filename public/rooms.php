<?php
include '../includes/db.php';
include '../includes/header.php';

$sql = "
SELECT r.room_id, rt.room_type_name, rt.base_room_fee, r.capacity
FROM room r
JOIN room_type rt ON r.room_type_id = rt.room_type_id
ORDER BY r.room_id
";
$stmt = $pdo->query($sql);
$rooms = $stmt->fetchAll();
?>

<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Rooms</h1>
    <a href="room_create.php"
       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        + Add Room
    </a>
</div>

<table class="min-w-full bg-white rounded shadow">
<thead class="bg-gray-200">
<tr>
    <th class="px-4 py-2">Room ID</th>
    <th class="px-4 py-2">Type</th>
    <th class="px-4 py-2">Room Fee</th>
    <th class="px-4 py-2">Capacity</th>
    <th class="px-4 py-2">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($rooms as $room): ?>
<tr class="border-t hover:bg-gray-50">
    <td class="px-4 py-2"><?= $room['room_id'] ?></td>
    <td class="px-4 py-2"><?= $room['room_type_name'] ?></td>
    <td class="px-4 py-2">$<?= number_format($room['base_room_fee'], 2) ?></td>
    <td class="px-4 py-2"><?= $room['capacity'] ?></td>
    <td class="px-4 py-2 space-x-2">
        <a href="room_edit.php?id=<?= $room['room_id'] ?>"
           class="text-blue-600 hover:underline">Edit</a>
        <a href="room_delete.php?id=<?= $room['room_id'] ?>"
           class="text-red-600 hover:underline"
           onclick="return confirm('Delete this room?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php include '../includes/footer.php'; ?>
