<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Auth check - admin only
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../public/auth/login.php');
    exit;
}

$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

// --- Handle Deletion (AJAX & JSON Response) ---
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    header('Content-Type: application/json');

    // Auth check again for safety
    if ($_SESSION['user']['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $id = (int) ($_POST['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM nationalities WHERE nat_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Nationality removed.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cannot delete: record is in use.']);
    }
    exit;
}

// --- Fetch Data for Editing ---
$edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$edit_name = '';
if ($edit_id) {
    $stmt = $conn->prepare("SELECT nat_name FROM nationalities WHERE nat_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $edit_name = $res['nat_name'] ?? '';
}

// --- Handle Insertion & Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nat_name'])) {
    $name = trim($_POST['nat_name']);
    $id = isset($_POST['nat_id']) ? (int) $_POST['nat_id'] : null;

    if (!empty($name)) {
        if ($id) {
            // Update Existing
            $stmt = $conn->prepare("UPDATE nationalities SET nat_name = ? WHERE nat_id = ?");
            $stmt->bind_param("si", $name, $id);
            $action_text = "updated";
        } else {
            // Insert New
            $stmt = $conn->prepare("INSERT INTO nationalities (nat_name) VALUES (?) ON DUPLICATE KEY UPDATE nat_name=nat_name");
            $stmt->bind_param("s", $name);
            $action_text = "registered";
        }
        if ($stmt->execute()) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Success!',
                'msg' => "Nationality has been $action_text."
            ];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'title' => 'Error', 'msg' => 'Something went wrong.'];
        }
        header("Location: nationalities.php");
        exit;
    }
}

require_once '../includes/header.php';

// Fetch all nationalities
$sql = "SELECT * FROM nationalities ORDER BY nat_name ASC";
$result = $conn->query($sql);
$nationalities = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="p-8 max-w-6xl mx-auto min-h-screen">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
        <div>
            <h1 class="text-4xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                Nationalities
            </h1>
            <p class="text-sm opacity-60 font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                Manage the origin database for renter profiles.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-5">
            <form method="POST"
                class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] p-8 shadow-2xl sticky top-8">

                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold <?= $isDark ? 'text-white' : 'text-slate-800' ?>">
                        <?= $edit_id ? 'Edit Nationality' : 'Add Nationality' ?>
                    </h2>
                    <?php if ($edit_id): ?>
                        <a href="nationalities.php"
                            class="text-xs font-bold text-slate-400 hover:text-rose-500 transition">Cancel</a>
                    <?php endif; ?>
                </div>

                <div class="space-y-4">
                    <?php if ($edit_id): ?>
                        <input type="hidden" name="nat_id" value="<?= $edit_id ?>">
                    <?php endif; ?>

                    <div class="space-y-2">
                        <label
                            class="text-[10px] font-black uppercase tracking-widest opacity-50 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">
                            Country / Nationality Name
                        </label>
                        <input type="text" name="nat_name" placeholder="e.g. Cambodian" required
                            value="<?= htmlspecialchars($edit_name) ?>"
                            class="w-full px-6 py-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500 transition 
                            <?= $isDark ? 'bg-gray-900 placeholder-gray-700 text-white' : 'bg-slate-50 text-slate-900 placeholder-slate-300' ?>">
                    </div>

                    <button type="submit"
                        class="w-full py-4 <?= $edit_id ? 'bg-emerald-600 shadow-emerald-500/20' : 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20' ?> text-white rounded-2xl font-black tracking-widest transition shadow-lg active:scale-95">
                        <i class="fa-solid <?= $edit_id ? 'fa-check' : 'fa-plus' ?> mr-2"></i>
                        <?= $edit_id ? 'SAVE CHANGES' : 'REGISTER' ?>
                    </button>
                </div>
                <div class="mt-8 p-4 rounded-2xl bg-blue-500/5 border border-blue-500/10">
                    <p class="text-[10px] font-bold text-blue-500 uppercase leading-relaxed">
                        <i class="fa-solid fa-circle-info mr-1"></i> Use standard naming conventions (e.g., French,
                        American) for cleaner reporting.
                    </p>
                </div>
            </form>
        </div>

        <div class="lg:col-span-7">
            <div
                class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] overflow-hidden shadow-xl">
                <div
                    class="px-8 py-6 border-b <?= $isDark ? 'border-gray-700' : 'border-slate-100' ?> flex flex-col sm:flex-row justify-between items-center gap-4 bg-opacity-50">
                    <div>
                        <h3 class="font-black <?= $isDark ? 'text-white' : 'text-slate-800' ?>">Registered origins</h3>
                        <span id="total-count"
                            class="bg-blue-500 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase">
                            <?= count($nationalities) ?> TOTAL
                        </span>
                    </div>
                    <div class="relative w-full sm:w-64">
                        <i
                            class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="natSearch" placeholder="Quick search..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl text-xs font-bold outline-none border transition
                            <?= $isDark ? 'bg-gray-900 focus:border-blue-500' : 'bg-slate-50 border-slate-200 text-slate-900 focus:border-blue-400' ?>">
                    </div>
                </div>

                <div id="natList" class="divide-y <?= $isDark ? 'divide-gray-700' : 'divide-slate-50' ?>">
                    <?php if (empty($nationalities)): ?>
                        <div class="p-20 text-center opacity-20">
                            <i class="fa-solid fa-earth-americas text-6xl mb-4"></i>
                            <p class="font-bold italic">No records found.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($nationalities as $n): ?>
                        <div
                            class="nat-item px-8 py-5 flex items-center justify-between hover:bg-blue-500/5 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-xs <?= $isDark ? 'bg-gray-900 text-blue-400' : 'bg-blue-50 text-blue-600' ?>">
                                    <?= strtoupper(substr($n['nat_name'], 0, 1)) ?>
                                </div>
                                <span class="nat-name font-bold <?= $isDark ? 'text-slate-300' : 'text-slate-700' ?>">
                                    <?= htmlspecialchars($n['nat_name']) ?>
                                </span>
                            </div>
                            <div class="flex gap-3">
                                <a href="?edit=<?= $n['nat_id'] ?>"
                                    class="opacity-0 group-hover:opacity-100 p-2 text-blue-500 hover:bg-blue-500/10 rounded-xl transition-all">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>

                                <button type="button"
                                    onclick="ajaxDelete(<?= $n['nat_id'] ?>, 'nationalities.php', this.closest('.nat-item'))"
                                    class="opacity-0 group-hover:opacity-100 p-2 text-rose-500 hover:bg-rose-500/10 rounded-xl transition-all">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/alerts.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Handle PHP Flash Messages via the toast function in alerts.js
        <?php if (isset($_SESSION['flash'])): ?>
            window.toast("<?= $_SESSION['flash']['msg'] ?>", "<?= $_SESSION['flash']['type'] ?>");
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
    });

    // 2. Updated confirmDelete to use SweetAlert2 directly
    function confirmDelete(id) {
        const isDark = document.documentElement.classList.contains('dark');

        Swal.fire({
            title: 'Are you sure?',
            text: "Renter profiles using this origin might be affected.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it!',
            background: isDark ? "#1f2937" : "#ffffff",
            color: isDark ? "#ffffff" : "#1f2937",
            customClass: { popup: 'rounded-[2rem]' }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?delete=' + id;
            }
        });
    }

    // Live Search Logic (Keep this from before)
    document.getElementById('natSearch').addEventListener('input', function (e) {
        const term = e.target.value.toLowerCase();
        const items = document.querySelectorAll('.nat-item');
        let visibleCount = 0;
        items.forEach(item => {
            const name = item.querySelector('.nat-name').textContent.toLowerCase();
            if (name.includes(term)) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        document.getElementById('total-count').textContent = visibleCount + (term ? ' FOUND' : ' TOTAL');
    });
</script>

<style>
    body {
        background-color:
            <?= $isDark ? '#0f172a' : '#f8fafc' ?>
        ;
    }

    ::-webkit-scrollbar {
        width: 6px;
    }

    ::-webkit-scrollbar-thumb {
        background: #3b82f633;
        border-radius: 10px;
    }
</style>

<?php include '../includes/footer.php'; ?>