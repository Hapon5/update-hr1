<?php
session_start();
include('../Database/Connections.php');

if (!isset($_SESSION['Email']) || $_SESSION['Account_type'] != 2) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['Email'];

// --- Class: CandidateManager (Ported from Super-admin) ---
class CandidateManager
{
    private $conn;
    private $uploads_dir = 'uploads/';
    private $base_path;
    private $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    public function __construct($connection)
    {
        $this->conn = $connection;
        // Staff portal is at /Staff/, root is ../
        $this->base_path = __DIR__ . '/../';
        $this->initialize();
    }

    private function initialize(): void
    {
        // Ensure directories exist in root/uploads
        $directories = ['resumes', 'certificates', 'licenses', 'resume_images', 'hr_photos'];
        foreach ($directories as $dir) {
            $path = $this->base_path . $this->uploads_dir . $dir . '/';
            if (!is_dir($path)) @mkdir($path, 0777, true);
        }

        // Table updates handled here or via migration
        try {
            $this->conn->exec("CREATE TABLE IF NOT EXISTS user_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50),
                data LONGTEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        } catch (Exception $e) {}
    }

    public function getCandidates($search = '', $status = '')
    {
        $sql = "SELECT * FROM candidates WHERE 1=1";
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (full_name LIKE ? OR email LIKE ? OR position LIKE ?)";
            $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
        }
        if (!empty($status) && $status !== 'All') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addCandidate($data, $files)
    {
        $resume = $this->uploadFile($files['resume'] ?? null, 'resumes/');
        
        $stmt = $this->conn->prepare("INSERT INTO candidates 
            (full_name, email, position, experience_years, contact_number, resume_path, status, source)
            VALUES (?, ?, ?, ?, ?, ?, 'Applied', 'Staff Portal')");

        $stmt->execute([
            $data['full_name'], $data['email'], $data['position'],
            $data['experience_years'] ?? 0, $data['contact_number'], $resume
        ]);

        // LOG TO NOTIFICATIONS (HR Request Logic)
        $notifData = json_encode(['name' => $data['full_name'], 'email' => $data['email'], 'position' => $data['position'], 'photo' => null]);
        $stmtNotif = $this->conn->prepare("INSERT INTO user_notifications (type, data) VALUES ('hr_request', ?)");
        $stmtNotif->execute([$notifData]);

        return ['status' => 'success', 'message' => 'Candidate added & HR notification sent'];
    }

    public function deleteCandidate($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->execute([$id]);
        return ['status' => 'success', 'message' => 'Candidate removed'];
    }

    private function uploadFile($file, $subdir)
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
        $filename = time() . '_' . basename($file['name']);
        $target = $this->base_path . 'uploads/' . $subdir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) return 'uploads/' . $subdir . $filename;
        return null;
    }
}

$manager = new CandidateManager($conn);

// Handle AJAX Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'add') echo json_encode($manager->addCandidate($_POST, $_FILES));
    if ($_POST['action'] === 'delete') echo json_encode($manager->deleteCandidate($_POST['id']));
    exit;
}

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'All';
$candidates = $manager->getCandidates($search, $status);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates - HR1 Staff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-100 flex">
    
    <?php include 'Components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen">
        <!-- Topbar -->
        <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center shrink-0">
            <h2 class="text-xl font-bold text-gray-800">Candidate Pipeline</h2>
            <div class="flex items-center gap-4">
                <a href="https://admin.cranecali-ms.com/api/hr/employee" target="_blank" class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full hover:bg-indigo-100 transition-colors">
                    <i class="fas fa-external-link-alt mr-1"></i> Admin HR Link
                </a>
                <span class="text-gray-600 font-medium"><?php echo htmlspecialchars($email); ?></span>
            </div>
        </header>

        <main class="p-6 overflow-y-auto flex-1">
            <!-- Stats & Tools -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div class="flex gap-2 w-full md:w-auto">
                    <form class="flex gap-2 grow">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or position..." 
                               class="bg-white border px-4 py-2 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none grow">
                        <select name="status" onchange="this.form.submit()" class="bg-white border px-4 py-2 rounded-lg text-sm">
                            <option value="All">All Status</option>
                            <option value="Applied" <?= $status == 'Applied' ? 'selected' : '' ?>>Applied</option>
                            <option value="Hired" <?= $status == 'Hired' ? 'selected' : '' ?>>Hired</option>
                        </select>
                    </form>
                </div>
                <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700 shadow-md">
                    <i class="fas fa-plus mr-2"></i> New Candidate
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b">
                        <tr class="text-xs font-bold text-gray-500 uppercase">
                            <th class="px-6 py-4">Candidate Information</th>
                            <th class="px-6 py-4">Target Position</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php if (empty($candidates)): ?>
                            <tr><td colspan="4" class="py-12 text-center text-gray-400">No matching candidates found.</td></tr>
                        <?php else: foreach ($candidates as $c): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800"><?= htmlspecialchars($c['full_name']) ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($c['email']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($c['position']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?= $c['status'] == 'Applied' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' ?>">
                                        <?= $c['status'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-3">
                                        <?php if ($c['resume_path']): ?>
                                            <a href="../<?= htmlspecialchars($c['resume_path']) ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900"><i class="fas fa-file-pdf"></i></a>
                                        <?php endif; ?>
                                        <button onclick="deleteCandidate(<?= $c['id'] ?>)" class="text-red-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- MODAL: ADD CANDIDATE -->
    <div id="addModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Add New Candidate</h3>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" class="text-gray-400"><i class="fas fa-times"></i></button>
            </div>
            <form id="addForm" class="p-6 space-y-4">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Full Name</label>
                        <input type="text" name="full_name" required class="w-full border p-2 rounded-lg text-sm bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Email</label>
                        <input type="email" name="email" required class="w-full border p-2 rounded-lg text-sm bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Position</label>
                        <input type="text" name="position" required class="w-full border p-2 rounded-lg text-sm bg-gray-50">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Resume (PDF/Image)</label>
                        <input type="file" name="resume" class="w-full text-sm">
                    </div>
                </div>
                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 bg-gray-100 py-2 rounded-lg font-bold text-gray-500">Cancel</button>
                    <button type="submit" class="flex-1 bg-indigo-600 py-2 rounded-lg font-bold text-white shadow-lg">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function deleteCandidate(id) {
            if (!confirm('Are you sure you want to remove this candidate?')) return;
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', id);
            fetch('', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => { if(res.status==='success') location.reload(); else alert(res.message); });
        }

        document.getElementById('addForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            fetch('', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    alert(res.message);
                    if(res.status==='success') location.reload();
                });
        });
    </script>
</body>
</html>
