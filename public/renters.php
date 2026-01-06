<?php
include '../includes/db.php';
include '../includes/header.php';

$stmt = $pdo->query("SELECT * FROM renter ORDER BY renter_id DESC");
$renters = $stmt->fetchAll();
?>

<div class="flex justify-between items-center mb-4">
    <h1 class="text-2xl font-bold">Renters</h1>
    <a href="renter_create.php"
       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
        + Add Renter
    </a>
</div>

<table class="min-w-full bg-white rounded shadow">
<thead class="bg-gray-200">
<tr>
    <th class="px-4 py-2">ID</th>
    <th class="px-4 py-2">Name</th>
    <th class="px-4 py-2">Gender</th>
    <th class="px-4 py-2">Mobile</th>
    <th class="px-4 py-2">Nationality</th>
    <th class="px-4 py-2">Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($renters as $r): ?>
<tr class="border-t hover:bg-gray-50">
    <td class="px-4 py-2"><?= $r['renter_id'] ?></td>
    <td class="px-4 py-2"><?= $r['renter_name'] ?></td>
    <td class="px-4 py-2"><?= $r['gender'] ?></td>
    <td class="px-4 py-2"><?= $r['mobile_number'] ?></td>
    <td class="px-4 py-2"><?= $r['nationality'] ?></td>
    <td class="px-4 py-2 space-x-2">
        <a href="renter_edit.php?id=<?= $r['renter_id'] ?>"
           class="text-blue-600 hover:underline">Edit</a>
        <a href="renter_delete.php?id=<?= $r['renter_id'] ?>"
           class="text-red-600 hover:underline"
           onclick="return confirm('Delete this renter?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php include '../includes/footer.php'; ?>
