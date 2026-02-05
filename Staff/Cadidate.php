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
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .candidate-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .candidate-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.1);
            border-color: #6366f1;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #c7c7c7;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a0a0a0;
        }
    </style>
</head>
<body class="bg-[#f8f9fa] text-gray-800 flex overflow-hidden">
    
    <?php include 'Components/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen ml-64">
        <!-- Topbar -->
        <header class="bg-white border-b border-gray-100 py-4 px-8 flex justify-between items-center shrink-0">
            <div>
                <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Candidate Pipeline</h2>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Manage and track your recruitment flow</p>
            </div>
            <div class="flex items-center gap-6">
                <a href="https://admin.cranecali-ms.com/api/hr/employee" target="_blank" 
                   class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest bg-indigo-50 text-indigo-600 px-4 py-2 rounded-xl hover:bg-indigo-100 transition-all border border-indigo-100 shadow-sm">
                    <i class="fas fa-external-link-alt"></i> Admin HR Link
                </a>
                <div class="flex items-center gap-3 pl-6 border-l border-gray-100">
                    <div class="text-right">
                        <p class="text-xs font-black text-gray-900 uppercase leading-none"><?php echo explode('@', $email)[0]; ?></p>
                        <p class="text-[9px] font-bold text-gray-400 uppercase tracking-tighter mt-1">Staff Member</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="p-8 overflow-y-auto flex-1 bg-[#f8f9fa]">
            <!-- Stats & Tools -->
            <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-6">
                <div class="flex flex-col gap-4 w-full md:w-auto">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1">Filter Pipeline</h3>
                    <form class="flex gap-3 w-full sm:w-[450px]">
                        <div class="relative flex-1">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or position..." 
                                   class="w-full bg-white border border-gray-100 pl-10 pr-4 py-3 rounded-2xl text-xs font-bold text-gray-700 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all shadow-sm">
                        </div>
                        <select name="status" onchange="this.form.submit()" 
                                class="bg-white border border-gray-100 px-4 py-3 rounded-2xl text-xs font-bold text-gray-600 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none transition-all shadow-sm cursor-pointer">
                            <option value="All">All Status</option>
                            <option value="Applied" <?= $status == 'Applied' ? 'selected' : '' ?>>Applied</option>
                            <option value="Hired" <?= $status == 'Hired' ? 'selected' : '' ?>>Hired</option>
                        </select>
                    </form>
                </div>
                <button onclick="document.getElementById('addModal').classList.remove('hidden')" 
                        class="bg-indigo-600 text-white px-8 py-3.5 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 active:scale-[0.98] flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> New Candidate
                </button>
            </div>

            <!-- Content Grid Replacing Table -->
            <div class="grid grid-cols-1 gap-4">
                <div class="flex items-center justify-between px-6 mb-2">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Candidate Details</span>
                    <div class="flex gap-20 pr-40">
                         <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest w-32">Target Position</span>
                         <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest w-24">Current Status</span>
                    </div>
                </div>

                <?php if (empty($candidates)): ?>
                    <div class="bg-white rounded-[32px] border border-dashed border-gray-200 py-20 text-center">
                        <div class="bg-gray-50 w-16 h-16 rounded-3xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                             <i class="fas fa-user-slash text-gray-300 text-xl"></i>
                        </div>
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-400">No matching candidates found.</p>
                    </div>
                <?php else: foreach ($candidates as $c): ?>
                    <div class="candidate-card bg-white rounded-3xl p-6 border border-gray-100 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 border border-indigo-100 shadow-sm font-black text-lg">
                                <?= strtoupper(substr($c['full_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h4 class="font-black text-gray-900 uppercase tracking-tight"><?= htmlspecialchars($c['full_name']) ?></h4>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"><?= htmlspecialchars($c['email']) ?></p>
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row items-center gap-10 md:gap-20">
                            <div class="w-32">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block md:hidden mb-1">Position</span>
                                <p class="text-xs font-bold text-gray-700 uppercase"><?= htmlspecialchars($c['position']) ?></p>
                            </div>

                            <div class="w-24 flex items-center justify-center md:justify-start">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block md:hidden mb-1">Status</span>
                                <span class="px-4 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border shadow-sm
                                    <?= $c['status'] == 'Applied' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-green-50 text-green-600 border-green-100' ?>">
                                    <?= $c['status'] ?>
                                </span>
                            </div>

                            <div class="flex gap-2">
                                <?php if ($c['resume_path']): ?>
                                    <a href="../<?= htmlspecialchars($c['resume_path']) ?>" target="_blank" 
                                       class="w-10 h-10 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 hover:border-indigo-100 transition-all">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                <?php endif; ?>
                                <button onclick="deleteCandidate(<?= $c['id'] ?>)" 
                                        class="w-10 h-10 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 hover:border-red-100 transition-all">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </main>
    </div>

    <!-- MODAL: ADD CANDIDATE -->
    <div id="addModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-md z-50 flex items-center justify-center p-4 transition-all">
        <div class="bg-white rounded-[40px] w-full max-w-xl shadow-2xl overflow-hidden border border-white/20">
            <div class="px-10 py-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Add Candidate</h3>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Submit new talent to technical pipeline</p>
                </div>
                <button onclick="document.getElementById('addModal').classList.add('hidden')" 
                        class="w-10 h-10 rounded-2xl bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-900 transition-all shadow-sm">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="addForm" class="p-10 space-y-8">
                <input type="hidden" name="action" value="add">
                <div class="grid grid-cols-2 gap-8">
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Full Name</label>
                        <input type="text" name="full_name" required placeholder="Enter full candidate name"
                               class="w-full bg-gray-50 border border-gray-100 px-6 py-4 rounded-2xl text-xs font-bold text-gray-700 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all placeholder:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Email Address</label>
                        <input type="email" name="email" required placeholder="email@address.com"
                               class="w-full bg-gray-50 border border-gray-100 px-6 py-4 rounded-2xl text-xs font-bold text-gray-700 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all placeholder:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Target Position</label>
                        <input type="text" name="position" required placeholder="e.g. Senior Developer"
                               class="w-full bg-gray-50 border border-gray-100 px-6 py-4 rounded-2xl text-xs font-bold text-gray-700 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 focus:bg-white outline-none transition-all placeholder:text-gray-300">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Resume Attachment</label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-100 border-dashed rounded-3xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-all">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fas fa-cloud-upload-alt text-gray-300 text-2xl mb-2"></i>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Click to upload or drag and drop</p>
                                </div>
                                <input type="file" name="resume" class="hidden" />
                            </label>
                        </div>
                    </div>
                </div>
                <div class="pt-4 flex gap-4">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" 
                            class="flex-1 py-4 border border-gray-100 text-gray-400 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-50 transition-all">
                        Discard
                    </button>
                    <button type="submit" 
                            class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-200">
                        Submit Talent
                    </button>
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
