<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
include '../includes/header.php';

requireAuth();

$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

// 1. Fetch Rooms (Existing code)
$sql = "
    SELECT 
        rm.*, 
        rt.room_type_name, 
        rt.base_room_fee, 
        r.renter_name
    FROM rooms rm
    INNER JOIN room_types rt ON rm.room_type_id = rt.room_type_id
    LEFT JOIN renters r ON rm.renter_id = r.renter_id
    ORDER BY rm.room_number ASC
";

$result = $conn->query($sql);
$rooms = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];

// 2. Fetch Dropdown Data for the Modal
$types_res = $conn->query("SELECT room_type_id, room_type_name FROM room_types ORDER BY room_type_name ASC");
$types = ($types_res) ? $types_res->fetch_all(MYSQLI_ASSOC) : [];

$renters_res = $conn->query("SELECT renter_id, renter_name FROM renters ORDER BY renter_name ASC");
$renters = ($renters_res) ? $renters_res->fetch_all(MYSQLI_ASSOC) : [];

// 3. Stats Calculation (Using 'ACTIVE' to match your Enum)
$totalRooms = count($rooms);
$occupiedCount = 0;
foreach ($rooms as $r) {
    if (strtoupper($r['status']) === 'ACTIVE')
        $occupiedCount++;
}
$availableCount = $totalRooms - $occupiedCount;
?>

<div class="px-8 max-w-full mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                Property Units
            </h1>
            <p class="text-sm font-medium opacity-50">Manage room availability and occupant assignments</p>
        </div>
        <button onclick="openRoomModal()"
            class="bg-blue-600 hover:bg-blue-500 text-white px-8 py-3 rounded-2xl font-bold text-sm transition-all shadow-lg shadow-blue-500/20 flex items-center gap-2">
            <i class="fa-solid fa-door-open"></i> Add New Room
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        <div
            class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> p-5 rounded-3xl border flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center"><i
                    class="fa-solid fa-hotel"></i></div>
            <div>
                <p class="text-[10px] font-black uppercase opacity-40">Total Units</p>
                <p class="text-xl font-black"><?= $totalRooms ?></p>
            </div>
        </div>
        <div
            class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> p-5 rounded-3xl border flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center"><i
                    class="fa-solid fa-check-to-slot"></i></div>
            <div>
                <p class="text-[10px] font-black uppercase opacity-40">Available</p>
                <p class="text-xl font-black text-emerald-500"><?= $availableCount ?></p>
            </div>
        </div>
        <div
            class="<?= $isDark ? 'bg-gray-800/50 border-gray-700' : 'bg-white border-gray-100' ?> p-5 rounded-3xl border flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center"><i
                    class="fa-solid fa-user-lock"></i></div>
            <div>
                <p class="text-[10px] font-black uppercase opacity-40">Occupied</p>
                <p class="text-xl font-black text-rose-500"><?= $occupiedCount ?></p>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['msg'])): ?>
        <div
            class="mb-6 p-4 rounded-2xl flex items-center gap-3 border shadow-sm <?= $_SESSION['msg_type'] === 'success' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-rose-500/10 border-rose-500/20 text-rose-500' ?>">
            <i
                class="fa-solid <?= $_SESSION['msg_type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
            <span class="text-sm font-bold"><?= $_SESSION['msg']; ?></span>
        </div>
        <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
    <?php endif; ?>

    <div
        class="overflow-hidden rounded-[2rem] border <?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> shadow-xl">
        <table class="w-full text-sm text-left">
            <thead
                class="<?= $isDark ? 'bg-gray-900/50 text-gray-400' : 'bg-gray-50 text-gray-500' ?> uppercase text-[10px] tracking-widest font-black">
                <tr>
                    <th class="px-8 py-5">Room #</th>
                    <th class="px-8 py-5">Type / Price</th>
                    <th class="px-8 py-5">Tenant</th>
                    <th class="px-8 py-5">Status</th>
                    <th class="px-8 py-5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y <?= $isDark ? 'divide-gray-700' : 'divide-gray-100' ?>">
                <?php foreach ($rooms as $room): ?>
                    <tr class="hover:bg-blue-500/5 transition-colors group">
                        <td class="px-8 py-6 font-black text-xl text-blue-500">
                            <?= htmlspecialchars($room['room_number']) ?>
                        </td>
                        <td class="px-8 py-6">
                            <div class="font-bold"><?= htmlspecialchars($room['room_type_name']) ?></div>
                            <div class="text-emerald-500 font-bold">$<?= number_format($room['base_room_fee'], 2) ?></div>
                        </td>
                        <td class="px-8 py-6">
                            <?php if ($room['status'] === 'ACTIVE'): ?>
                                <div class="font-bold"><?= htmlspecialchars($room['renter_name'] ?? 'Unknown') ?></div>
                                <div class="text-[10px] opacity-50 italic">Since:
                                    <?= date('d M Y', strtotime($room['rent_date'])) ?>
                                </div>
                            <?php else: ?>
                                <span class="opacity-30 italic">No Occupant</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-8 py-6">
                            <?php if ($room['status'] === 'ACTIVE'): ?>
                                <span
                                    class="bg-rose-500/10 text-rose-500 border border-rose-500/20 px-3 py-1 rounded-full text-[9px] font-black uppercase">Active</span>
                            <?php else: ?>
                                <span
                                    class="bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 px-3 py-1 rounded-full text-[9px] font-black uppercase">Available</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-8 py-6 text-right">
                            <div class="flex justify-end gap-2">
                                <div class="flex gap-2">
                                    <button onclick='openRoomModal(<?= json_encode($room) ?>)'
                                        class="p-2 hover:bg-blue-500 hover:text-white rounded-lg transition-all text-blue-500 border border-blue-500/10"
                                        title="Edit Tenant">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <?php if ($room['status'] === 'ACTIVE'): ?>
                                        <button onclick="ajaxCheckout(<?= $room['room_id'] ?>, '<?= $room['room_number'] ?>')"
                                            class="p-2 bg-orange-500/10 text-orange-600 hover:bg-orange-600 hover:text-white rounded-lg transition-all border border-orange-500/20"
                                            title="Checkout Tenant">
                                            <i class="fa-solid fa-right-from-bracket"></i>
                                        </button>
                                    <?php else: ?>
                                        <button
                                            onclick="ajaxDelete(<?= $room['room_id'] ?>, 'room_actions.php', this.closest('tr'))"
                                            class="p-2 hover:bg-rose-500 hover:text-white rounded-lg transition-all text-rose-500 border border-rose-500/10"
                                            title="Delete Tenant">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="assets/js/alerts.js"></script>
<script id="typesJson" type="application/json"><?= json_encode($types) ?></script>
<script id="rentersJson" type="application/json"><?= json_encode($renters) ?></script>

<style>
    .custom-input {
        width: 100%;
        padding: 1rem 1.25rem;
        background-color: rgba(15, 23, 42, 0.05);
        /* Light default */
        border: 2px solid transparent;
        border-radius: 1.25rem;
        font-weight: 700;
        transition: all 0.2s ease;
    }

    .dark .custom-input {
        background-color: rgba(15, 23, 42, 0.5);
        border-color: #374151;
        color: white;
    }

    .custom-input:focus {
        border-color: #2563eb;
        background-color: white;
    }
</style>

<?php if (isset($_SESSION['msg'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const msgType = '<?= $_SESSION['msg_type'] ?? "info" ?>';
            const msgText = '<?= addslashes($_SESSION['msg']) ?>';

            Swal.fire({
                icon: msgType === 'success' ? 'success' : 'error',
                title: msgType === 'success' ? 'Successful' : 'Alert',
                text: msgText,
                confirmButtonColor: '#2563eb',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-xl px-6 py-3 font-bold'
                },
                showClass: { popup: 'animate__animated animate__fadeInDown' },
                hideClass: { popup: 'animate__animated animate__fadeOutUp' }
            });
        });
    </script>
    <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>