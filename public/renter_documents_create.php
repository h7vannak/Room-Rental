<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';

// 1. Fetch Renters for the Dropdown
$renters = $conn->query("SELECT renter_id, renter_name FROM renters ORDER BY renter_id ASC")->fetch_all(MYSQLI_ASSOC);

// 2. Handle Upload Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $renter_id = $_POST['renter_id'];
    $doc_type = $_POST['doc_type'];
    $doc_number = $_POST['doc_number'];

    // File upload handling
    $target_dir = "../uploads/documents/";
    if (!file_exists($target_dir))
        mkdir($target_dir, 0777, true);

    $file_name = time() . '_' . basename($_FILES["doc_file"]["name"]);
    $target_file = $target_dir . $file_name;
    $db_path = "uploads/documents/" . $file_name; // Path stored in DB

    if (move_uploaded_file($_FILES["doc_file"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO renter_documents (renter_id, doc_type, doc_number, doc_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $renter_id, $doc_type, $doc_number, $db_path);
        $stmt->execute();
        echo "<script>window.location='renter_documents.php';</script>";
    }
}
?>

<div class="p-8 max-w-2xl mx-auto min-h-screen">
    <div class="mb-8 flex items-center gap-4">
        <a href="renter_documents.php"
            class="p-3 rounded-2xl <?= $isDark ? 'bg-gray-800 text-white' : 'bg-slate-100 text-slate-600' ?> hover:scale-110 transition">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <h1 class="text-3xl font-black <?= $isDark ? 'text-white' : 'text-slate-900' ?>">Upload Document</h1>
    </div>

    <form method="POST" enctype="multipart/form-data"
        class="<?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] p-10 shadow-2xl space-y-6">

        <div class="space-y-2">
            <label
                class="text-[10px] font-black uppercase tracking-widest opacity-50 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">Owner
                of Document</label>
            <select name="renter_id" required
                class="w-full p-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500 <?= $isDark ? 'bg-gray-900 text-white' : 'bg-slate-50 text-slate-900' ?>">
                <option value="">Select Renter...</option>
                <?php foreach ($renters as $r): ?>
                    <option value="<?= $r['renter_id'] ?>"><?= $r['renter_name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
                <label
                    class="text-[10px] font-black uppercase tracking-widest opacity-50 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">Type</label>
                <select name="doc_type"
                    class="w-full p-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500 <?= $isDark ? 'bg-gray-900 text-white' : 'bg-slate-50 text-slate-900' ?>">
                    <option>National ID</option>
                    <option>Passport</option>
                    <option>Family Book</option>
                </select>
            </div>
            <div class="space-y-2">
                <label
                    class="text-[10px] font-black uppercase tracking-widest opacity-50 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">ID
                    Number</label>
                <input type="text" name="doc_number" required placeholder="N-0000000"
                    class="w-full p-4 rounded-2xl font-bold outline-none focus:ring-2 focus:ring-blue-500 <?= $isDark ? 'bg-gray-900 text-white' : 'bg-slate-50 text-slate-900' ?>">
            </div>
        </div>

        <div class="space-y-2">
            <label
                class="text-[10px] font-black uppercase tracking-widest opacity-50 <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">Scan
                / Photo</label>
            <div
                class="relative border-2 border-dashed rounded-2xl p-8 text-center <?= $isDark ? 'border-gray-700 hover:border-blue-500' : 'border-slate-200 hover:border-blue-500' ?> transition">
                <input type="file" name="doc_file" required
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                <i class="fa-solid fa-cloud-arrow-up text-3xl text-blue-500 mb-2"></i>
                <p class="text-xs font-bold opacity-50">Click or drag to upload (PDF, JPG, PNG)</p>
            </div>
        </div>

        <button type="submit"
            class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black tracking-widest hover:bg-blue-700 transition shadow-xl shadow-blue-500/30">
            SAVE TO ARCHIVE
        </button>
    </form>
</div>