<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
include '../includes/header.php';

requireAuth();

// Detect Dark Mode
$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

// 1. Fetch all room types
$result = $conn->query("SELECT * FROM room_types ORDER BY room_type_id ASC");
$types = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="p-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-blue-500"></i>
                Room Categories
            </h1>
            <p class="text-sm opacity-60">Manage pricing and classification for your rooms</p>
        </div>
        <a href="room_type_create.php"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl transition shadow-lg shadow-blue-500/30 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Add Category
        </a>
    </div>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="mb-6 p-4 rounded-xl flex items-center gap-3 border shadow-sm 
            <?= $_SESSION['msg_type'] === 'success'
                ? 'bg-green-500/10 text-green-500'
                : 'bg-red-500/10 border-red-500/20 text-red-500' ?>">
            <i
                class="fa-solid <?= $_SESSION['msg_type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
            <span class="text-sm font-medium"><?= $_SESSION['msg']; ?></span>
        </div>
        <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
    <?php endif; ?>

    <div
        class="overflow-hidden rounded-2xl border <?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead
                    class="<?= $isDark ? 'bg-gray-700/50 text-gray-400' : 'bg-gray-50 text-gray-500' ?> uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Category Name</th>
                        <th class="px-6 py-4 text-right">Base Monthly Fee</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y <?= $isDark ? 'divide-gray-700' : 'divide-gray-100' ?>">
                    <?php if (empty($types)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center opacity-50 italic">No categories defined.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($types as $t): ?>
                        <tr class="hover:bg-blue-50/5 transition-colors">
                            <td class="px-6 py-4 opacity-50 font-mono">
                                #<?= $t['room_type_id'] ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold"><?= htmlspecialchars($t['room_type_name']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-green-500">
                                $<?= number_format($t['base_room_fee'], 2) ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">
                                    <a href="room_type_edit.php?id=<?= $t['room_type_id'] ?>"
                                        class="p-2 hover:bg-blue-500/20 text-blue-500 rounded-lg transition"
                                        title="Edit Settings">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="room_type_delete.php?id=<?= $t['room_type_id'] ?>"
                                        onclick="return confirm('Deleting this type will affect all rooms assigned to it. Proceed?')"
                                        class="p-2 hover:bg-red-500/20 text-red-500 rounded-lg transition"
                                        title="Delete Category">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>