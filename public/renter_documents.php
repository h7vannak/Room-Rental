<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Auth check
if (!isset($_SESSION['user'])) {
    header('Location: ../public/auth/login.php');
    exit;
}

$isDark = ($_SESSION['user']['theme'] ?? 'light') === 'dark';
$current_page = basename($_SERVER['PHP_SELF']);

/* --- UPDATE LOGIC (AJAX) --- */
if (isset($_GET['action']) && $_GET['action'] === 'update') {
    header('Content-Type: application/json');
    
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    $doc_number = trim($_POST['doc_number'] ?? '');
    $doc_type = trim($_POST['doc_type'] ?? '');

    if ($doc_id > 0 && !empty($doc_number) && !empty($doc_type)) {
        $stmt = $conn->prepare("UPDATE renter_documents SET doc_number = ?, doc_type = ? WHERE doc_id = ?");
        $stmt->bind_param("ssi", $doc_number, $doc_type, $doc_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Document updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    }
    exit;
}

/* --- DELETE LOGIC --- */
if (isset($_GET['delete'])) {
    $doc_id = (int) $_GET['delete'];
    
    // Get file path to delete from storage
    $get_path = $conn->prepare("SELECT doc_path FROM renter_documents WHERE doc_id = ?");
    $get_path->bind_param("i", $doc_id);
    $get_path->execute();
    $path_res = $get_path->get_result()->fetch_assoc();

    if ($path_res) {
        $full_path = "../" . $path_res['doc_path'];
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }

    $stmt = $conn->prepare("DELETE FROM renter_documents WHERE doc_id = ?");
    $stmt->bind_param("i", $doc_id);
    
    if($stmt->execute()) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'File deleted successfully.'];
    }
    header("Location: renter_documents.php");
    exit;
}

/* --- FETCH DOCUMENTS --- */
$sql = "SELECT d.*, r.renter_name FROM renter_documents d 
        JOIN renters r ON d.renter_id = r.renter_id 
        ORDER BY d.uploaded_at DESC";
$result = $conn->query($sql);
$documents = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

include '../includes/header.php';
?>

<div class="p-8 max-w-7xl mx-auto min-h-screen">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-12">
        <div>
            <h1 class="text-4xl font-black tracking-tight <?= $isDark ? 'text-white' : 'text-slate-900' ?>">Digital Archive</h1>
            <p class="text-sm opacity-60 font-medium <?= $isDark ? 'text-slate-400' : 'text-slate-500' ?>">Manage renter identification and legal files.</p>
        </div>

        <div class="flex flex-col sm:flex-row w-full lg:w-auto gap-4">
            <div class="relative flex-grow min-w-[300px]">
                <i class="fa-solid fa-magnifying-glass absolute left-5 top-1/2 -translate-y-1/2 opacity-30"></i>
                <input type="text" id="archiveSearch" placeholder="Search name, ID, or type..."
                    class="w-full pl-12 pr-6 py-4 rounded-2xl font-bold outline-none border-none focus:ring-2 focus:ring-blue-500 transition-all <?= $isDark ? 'bg-gray-800 text-white' : 'bg-white text-slate-900 shadow-sm' ?>">
            </div>

            <a href="renter_documents_create.php"
                class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-2xl font-black text-sm shadow-xl shadow-blue-500/30 transition-all hover:scale-105 flex items-center justify-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up"></i> UPLOAD
            </a>
        </div>
    </div>

    <div class="mb-6 px-2 flex items-center justify-between">
        <span class="text-[10px] font-black uppercase tracking-widest opacity-40">Showing <span id="visibleCount"><?= count($documents) ?></span> Files</span>
    </div>

    <div id="documentGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <?php foreach ($documents as $doc):
            $filePath = "../" . $doc['doc_path'];
            $ext = strtolower(pathinfo($doc['doc_path'], PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
            $searchMeta = strtolower($doc['renter_name'] . ' ' . $doc['doc_number'] . ' ' . $doc['doc_type']);
        ?>
            <div class="doc-card group relative <?= $isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-100' ?> border rounded-[2.5rem] p-6 shadow-xl hover:shadow-2xl transition-all duration-300"
                data-search="<?= $searchMeta ?>">

                <div class="flex justify-between items-start mb-6">
                    <div class="h-16 w-16 rounded-2xl overflow-hidden <?= $isDark ? 'bg-gray-900' : 'bg-slate-50' ?> border <?= $isDark ? 'border-gray-700' : 'border-slate-100' ?> flex items-center justify-center">
                        <?php if ($isImage && file_exists($filePath)): ?>
                            <img src="<?= $filePath ?>" class="h-full w-full object-cover group-hover:scale-110 transition-transform" alt="Doc">
                        <?php else: ?>
                            <i class="fa-solid fa-file-pdf text-2xl text-rose-500"></i>
                        <?php endif; ?>
                    </div>

                    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a href="<?= $filePath ?>" target="_blank" class="p-2.5 bg-blue-500/10 text-blue-500 rounded-xl hover:bg-blue-500 hover:text-white transition"><i class="fa-solid fa-eye text-xs"></i></a>

                        <button type="button" onclick='openEditDocModal(<?= json_encode($doc) ?>)'
                            class="p-2.5 bg-amber-500/10 text-amber-500 rounded-xl hover:bg-amber-500 hover:text-white transition">
                            <i class="fa-solid fa-pen text-xs"></i>
                        </button>

                        <a href="<?= $filePath ?>" download class="p-2.5 bg-emerald-500/10 text-emerald-500 rounded-xl hover:bg-emerald-500 hover:text-white transition"><i class="fa-solid fa-download text-xs"></i></a>

                        <button type="button" onclick="confirmDelete(<?= $doc['doc_id'] ?>)"
                            class="p-2.5 bg-rose-500/10 text-rose-500 rounded-xl hover:bg-rose-500 hover:text-white transition"><i class="fa-solid fa-trash-can text-xs"></i></button>
                    </div>
                </div>

                <div class="space-y-1 mb-6">
                    <h3 class="font-black text-sm truncate <?= $isDark ? 'text-white' : 'text-slate-900' ?>">
                        <?= htmlspecialchars($doc['doc_number']) ?>
                    </h3>
                    <p class="text-[11px] font-bold opacity-60 flex items-center gap-1.5">
                        <i class="fa-solid fa-user-circle text-blue-500"></i> <?= htmlspecialchars($doc['renter_name']) ?>
                    </p>
                </div>

                <div class="flex items-center justify-between pt-5 border-t <?= $isDark ? 'border-gray-700/50' : 'border-slate-50' ?>">
                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter bg-blue-500/10 text-blue-500"><?= $doc['doc_type'] ?></span>
                    <span class="text-[9px] font-bold opacity-30 uppercase"><?= date('d M Y', strtotime($doc['uploaded_at'])) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="noResults" class="hidden py-32 text-center border-4 border-dashed <?= $isDark ? 'border-gray-800' : 'border-slate-100' ?> rounded-[4rem]">
        <p class="font-black opacity-30 uppercase tracking-[0.3em] text-sm text-center w-full">No matching documents found</p>
    </div>
</div>

<script src="../assets/js/alerts.js"></script>
<script>
    // 1. Live Search Logic
    document.getElementById('archiveSearch').addEventListener('input', function (e) {
        const searchTerm = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.doc-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const meta = card.getAttribute('data-search');
            if (meta.includes(searchTerm)) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        document.getElementById('visibleCount').textContent = visibleCount;
        document.getElementById('noResults').classList.toggle('hidden', visibleCount > 0);
    });

    // 2. Edit Document Modal Logic
    function openEditDocModal(doc) {
        const isDark = document.documentElement.classList.contains('dark');

        Swal.fire({
            title: 'Edit Document Info',
            background: isDark ? "#1f2937" : "#ffffff",
            color: isDark ? "#ffffff" : "#1f2937",
            customClass: { popup: 'rounded-[2.5rem]' },
            html: `
            <div class="text-left p-2">
                <label class="text-[10px] font-black uppercase tracking-widest opacity-40">Document Number</label>
                <input type="text" id="swal_doc_number" class="w-full mt-2 mb-4 p-4 rounded-2xl border ${isDark ? 'bg-gray-900 border-gray-700 text-white' : 'bg-slate-50 border-slate-100'}" value="${doc.doc_number}">
                
                <label class="text-[10px] font-black uppercase tracking-widest opacity-40">Document Type</label>
                <select id="swal_doc_type" class="w-full mt-2 p-4 rounded-2xl border ${isDark ? 'bg-gray-900 border-gray-700 text-white' : 'bg-slate-50 border-slate-100'}">
                    <option value="National ID" ${doc.doc_type === 'National ID' ? 'selected' : ''}>National ID</option>
                    <option value="PASSPORT" ${doc.doc_type === 'PASSPORT' ? 'selected' : ''}>PASSPORT</option>
                    <option value="Family Book" ${doc.doc_type === 'Family Book' ? 'selected' : ''}>Family Book</option>
                    <option value="OTHER" ${doc.doc_type === 'OTHER' ? 'selected' : ''}>OTHER</option>
                </select>
            </div>
        `,
            showCancelButton: true,
            confirmButtonText: 'Save Changes',
            confirmButtonColor: '#3b82f6',
            preConfirm: async () => {
                const num = document.getElementById('swal_doc_number').value.trim();
                const type = document.getElementById('swal_doc_type').value;

                if (!num) return Swal.showValidationMessage('Document number is required');
                if (!type) return Swal.showValidationMessage('Document type is required');

                const formData = new FormData();
                formData.append('doc_id', doc.doc_id);
                formData.append('doc_number', num);
                formData.append('doc_type', type);

                try {
                    const response = await fetch('?action=update', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.message);
                    return result;
                } catch (error) {
                    Swal.showValidationMessage(`Update failed: ${error.message}`);
                }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value.success) {
                // Using the global window.toast from your alerts.js
                if (typeof window.toast === "function") {
                    window.toast(result.value.message, "success");
                }
                setTimeout(() => location.reload(), 1000);
            }
        });
    }

    // 3. Professional Delete Confirmation
    function confirmDelete(id) {
        const isDark = document.documentElement.classList.contains('dark');
        Swal.fire({
            title: 'Delete this file?',
            text: "This will remove the digital record and physical file.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, Delete',
            background: isDark ? "#1f2937" : "#ffffff",
            color: isDark ? "#ffffff" : "#1f2937",
            customClass: { popup: 'rounded-[2rem]' }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?delete=' + id;
            }
        });
    }

    // 4. Handle Deletion Flash Messages
    <?php if (isset($_SESSION['flash'])): ?>
        window.addEventListener('load', () => {
            if (typeof window.toast === "function") {
                window.toast("<?= $_SESSION['flash']['msg'] ?>", "<?= $_SESSION['flash']['type'] ?>");
            }
        });
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>