<?php
session_start();
// Adjust path to root 'hr1-crane' folder
include("../../Database/Connections.php");

// --- Class: CandidateManager ---
class CandidateManager
{
    private $conn;
    private $uploads_dir = 'uploads/';
    private $base_path;
    private $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    private $max_file_size = 10 * 1024 * 1024; // 10MB

    public function __construct($connection)
    {
        $this->conn = $connection;
        // Fix base path resolution
        $this->base_path = __DIR__ . '/../../';
        // Ensure uploads directory structure exists in root
        $this->initialize();
    }

    private function initialize(): void
    {
        // 1. Create Directories if not exist
        $directories = ['resumes', 'certificates', 'licenses', 'resume_images', 'temp'];
        foreach ($directories as $dir) {
            $path = $this->base_path . $this->uploads_dir . $dir . '/';
            if (!is_dir($path)) {
                @mkdir($path, 0777, true);
            }
        }

        // 2. Ensure Table Exists
        $this->createTable();

        // 3. Update Table Schema (Add new columns if missing)
        $this->updateSchema();
    }

    private function createTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS candidates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            job_title VARCHAR(255),
            position VARCHAR(255), -- Target Position
            experience_years INT DEFAULT 0,
            contact_number VARCHAR(50),
            address TEXT,
            resume_path VARCHAR(255),
            extracted_image_path VARCHAR(255),
            status ENUM('Applied', 'Shortlisted', 'Interviewed', 'Passed', 'Failed', 'Hired') DEFAULT 'Applied',
            source VARCHAR(100) DEFAULT 'Direct',
            skills TEXT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->conn->exec($sql);
    }

    private function updateSchema(): void
    {
        $columns = [
            'certificates_path' => "VARCHAR(500) DEFAULT NULL",
            'licenses_path' => "VARCHAR(500) DEFAULT NULL",
            'interview_date' => "DATETIME DEFAULT NULL",
            'skill_rating' => "INT DEFAULT 0", // 1-5
            'background_status' => "ENUM('Pending', 'In Progress', 'Cleared', 'Flagged') DEFAULT 'Pending'",
            'interview_status' => "ENUM('Pending', 'Scheduled', 'Completed', 'Cancelled') DEFAULT 'Pending'"
        ];

        foreach ($columns as $col => $def) {
            try {
                $this->conn->query("SELECT $col FROM candidates LIMIT 1");
            } catch (Exception $e) {
                // Column doesn't exist, add it
                $this->conn->exec("ALTER TABLE candidates ADD COLUMN $col $def");
            }
        }
    }

    // --- CRUD OPERATIONS ---

    public function getCandidates($search = '', $status = '')
    {
        $sql = "SELECT * FROM candidates WHERE 1=1";
        $params = [];
        if (!empty($search)) {
            $sql .= " AND (full_name LIKE ? OR email LIKE ? OR position LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        // Exclude 'Online Registration' entries as requested (Only show actual job applicants)
        $sql .= " AND source != 'Online Registration'";

        if (!empty($status) && $status !== 'All') {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCandidate($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM candidates WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addCandidate($data, $files)
    {
        // Handle File Uploads
        $resume = $this->uploadFile($files['resume'] ?? null, 'resumes/');
        $cert = $this->uploadFile($files['certificates'] ?? null, 'certificates/');
        $license = $this->uploadFile($files['licenses'] ?? null, 'licenses/');

        // Basic Image Extraction Placeholder (if needed, or simplistic default)
        $imagePath = null; // Could implement extraction logic here if needed

        $stmt = $this->conn->prepare("INSERT INTO candidates 
            (full_name, email, position, experience_years, contact_number, resume_path, certificates_path, licenses_path, skills, notes, status, extracted_image_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Applied', ?)");

        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['position'],
            $data['experience_years'] ?? 0,
            $data['contact_number'],
            $resume,
            $cert,
            $license,
            $data['skills'] ?? '',
            $data['notes'] ?? '',
            $imagePath
        ]);

        return ['status' => 'success', 'message' => 'Candidate added successfully'];
    }

    public function updateCandidate($data, $files)
    {
        $id = $data['id'];
        $current = $this->getCandidate($id);

        // Handle Files (Keep old if new not provided)
        $resume = $this->uploadFile($files['resume'] ?? null, 'resumes/') ?: $current['resume_path'];
        $cert = $this->uploadFile($files['certificates'] ?? null, 'certificates/') ?: $current['certificates_path'];
        $license = $this->uploadFile($files['licenses'] ?? null, 'licenses/') ?: $current['licenses_path'];

        $stmt = $this->conn->prepare("UPDATE candidates SET 
            full_name=?, email=?, position=?, experience_years=?, contact_number=?, 
            resume_path=?, certificates_path=?, licenses_path=?, 
            skills=?, skill_rating=?, background_status=?, status=?, notes=?, interview_date=?
            WHERE id=?");

        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['position'],
            $data['experience_years'],
            $data['contact_number'],
            $resume,
            $cert,
            $license,
            $data['skills'],
            $data['skill_rating'] ?? 0,
            $data['background_status'] ?? 'Pending',
            $data['status'],
            $data['notes'],
            !empty($data['interview_date']) ? $data['interview_date'] : null,
            $id
        ]);

        return ['status' => 'success', 'message' => 'Candidate updated successfully'];
    }

    public function deleteCandidate($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM candidates WHERE id = ?");
        $stmt->execute([$id]);
        return ['status' => 'success', 'message' => 'Candidate deleted'];
    }

    private function createDirectories(): void
    {
        $directories = ['resumes', 'resume_images', 'certificates', 'licenses', 'temp'];
        foreach ($directories as $dir) {
            // Point to Main/uploads directory
            $path = $this->base_path . '../../Main/uploads/' . $dir . '/';
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }

    private function uploadFile($file, $subdir)
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK)
            return null;

        $filename = uniqid() . '_' . basename($file['name']);

        // Save to Main/uploads directory: ../../Main/uploads/
        // Base path is inside Super-admin/Modules/
        $target_dir = $this->base_path . '../../Main/uploads/' . $subdir;

        // Ensure directory exists (just in case)
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $target = $target_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            // Return format: uploads/subdir/filename (standard DB format)
            return 'uploads/' . $subdir . $filename;
        }
        return null;
    }
}

// --- HANDLE REQUESTS ---
$manager = new CandidateManager($conn);
$message = '';
$candidates = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    try {
        if ($_POST['action'] === 'add') {
            echo json_encode($manager->addCandidate($_POST, $_FILES));
        } elseif ($_POST['action'] === 'update') {
            echo json_encode($manager->updateCandidate($_POST, $_FILES));
        } elseif ($_POST['action'] === 'delete') {
            echo json_encode($manager->deleteCandidate($_POST['id']));
        } elseif ($_POST['action'] === 'get') {
            $data = $manager->getCandidate($_POST['id']);
            echo json_encode(['status' => 'success', 'data' => $data]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Fetch for Display
$search = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$candidates = $manager->getCandidates($search, $filterStatus);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/x-icon" href="../../Image/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Tracking | Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <!-- Scripts for DOCX Preview -->
    <script src="https://unpkg.com/jszip/dist/jszip.min.js"></script>
    <script src="https://unpkg.com/docx-preview/dist/docx-preview.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: { brand: { 500: '#6366f1', 600: '#4f46e5' } }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans">

    <?php
    $root_path = '../../';
    $css_path = '../Css/';
    include '../Components/sidebar.php';
    include '../Components/header.php';
    ?>

    <div class="main-content min-h-screen pt-24 pb-8 px-4 sm:px-8 ml-64 transition-all duration-300" id="mainContent">

        <!-- Page Header -->
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Candidate Tracking</h1>
                <p class="text-gray-500 text-sm mt-1">Manage and track candidate applications across the pipeline.</p>
            </div>

        </div>

        <!-- Filters -->
        <div
            class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div class="relative w-full md:w-96">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <form method="GET" class="w-full">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search candidates..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm">
                </form>
            </div>
            <div class="flex gap-3 w-full md:w-auto">
                <form method="GET" id="filterForm">
                    <select name="status" onchange="this.form.submit()"
                        class="px-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="All">All Status</option>
                        <?php
                        $statuses = ['Applied', 'Shortlisted', 'Interviewed', 'Passed', 'Failed', 'Hired'];
                        foreach ($statuses as $s) {
                            $sel = $filterStatus === $s ? 'selected' : '';
                            echo "<option value='$s' $sel>$s</option>";
                        }
                        ?>
                    </select>
                </form>
            </div>
        </div>

        <!-- Candidates Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 border-b border-gray-100 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                            <th class="px-6 py-4 text-left">Candidate</th>
                            <th class="px-6 py-4 text-center">Position</th>
                            <th class="px-6 py-4 text-center">Experience</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Rating</th>
                            <th class="px-6 py-4 text-center">Background</th>
                            <th class="px-6 py-4 text-center">Date</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($candidates as $c): ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($c['extracted_image_path'])): ?>
                                            <img src="../../Main/<?= htmlspecialchars($c['extracted_image_path']) ?>"
                                                alt="Profile"
                                                class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                        <?php else: ?>
                                            <div
                                                class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                                <?= strtoupper(substr($c['full_name'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-left">
                                            <div class="font-medium text-gray-900"><?= htmlspecialchars($c['full_name']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($c['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 text-center">
                                    <?= htmlspecialchars($c['position']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 text-center">
                                    <?= $c['experience_years'] ?> Years
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php
                                    $sClass = match ($c['status']) {
                                        'Applied' => 'bg-blue-50 text-blue-700',
                                        'Shortlisted' => 'bg-purple-50 text-purple-700',
                                        'Interviewed' => 'bg-orange-50 text-orange-700',
                                        'Passed' => 'bg-green-50 text-green-700',
                                        'Hired' => 'bg-teal-50 text-teal-700',
                                        'Failed' => 'bg-red-50 text-red-700',
                                        default => 'bg-gray-50 text-gray-600'
                                    };
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium <?= $sClass ?>">
                                        <?= $c['status'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center text-yellow-400 text-xs">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?= $i <= $c['skill_rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $bgClass = match ($c['background_status']) {
                                        'Cleared' => 'text-green-600',
                                        'Flagged' => 'text-red-600',
                                        'In Progress' => 'text-blue-600',
                                        default => 'text-gray-400'
                                    };
                                    ?>
                                    <div
                                        class="text-xs font-medium flex items-center justify-center gap-1.5 <?= $bgClass ?>">
                                        <i class="fas fa-shield-alt"></i> <?= $c['background_status'] ?: 'Pending' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 text-center whitespace-nowrap">
                                    <?= date('M d, Y', strtotime($c['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div
                                        class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button onclick="editCandidate(<?= $c['id'] ?>)"
                                            class="p-2 text-gray-400 hover:text-indigo-600 transition-colors"
                                            title="Edit / View Details">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteCandidate(<?= $c['id'] ?>)"
                                            class="p-2 text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($candidates)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-users mb-3 text-3xl text-gray-300"></i>
                                    <p>No candidates found matching criteria.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL: ADD/EDIT CANDIDATE -->
    <div id="candidateModal"
        class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 backdrop-blur-sm">
        <div
            class="bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto transform transition-all scale-100">
            <!-- Header -->
            <div
                class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between z-10">
                <h3 class="text-xl font-bold text-gray-900" id="modalTitle">Add Candidate</h3>
                <button onclick="closeModal()"
                    class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100"><i
                        class="fas fa-times"></i></button>
            </div>

            <form id="candidateForm" class="p-6 sm:p-8 space-y-8">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="candidateId">

                <!-- Section: Personal Info -->
                <div>
                    <h4
                        class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4 border-l-4 border-indigo-500 pl-3">
                        Personal Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Full Name</label>
                            <div id="display_full_name" class="text-gray-900 font-semibold text-lg"></div>
                            <input type="hidden" name="full_name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email Address</label>
                            <div id="display_email" class="text-gray-900 font-medium break-all"></div>
                            <input type="hidden" name="email">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Phone Number</label>
                            <div id="display_contact_number" class="text-gray-900 font-medium"></div>
                            <input type="hidden" name="contact_number">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Experience (Years)</label>
                            <div id="display_experience_years" class="text-gray-900 font-medium"></div>
                            <input type="hidden" name="experience_years">
                        </div>
                    </div>
                </div>

                <!-- Section: Application Details -->
                <div class="border-t border-gray-100 pt-6">
                    <h4
                        class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4 border-l-4 border-blue-500 pl-3">
                        Application Details</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Position Applied For</label>
                            <input type="text" name="position" required
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="Applied">Applied</option>
                                <option value="Shortlisted">Shortlisted</option>
                                <option value="Interviewed">Interviewed</option>
                                <option value="Passed">Passed</option>
                                <option value="Failed">Failed</option>
                                <option value="Hired">Hired (Convert to Employee)</option>
                            </select>
                        </div>

                        <!-- Interview Scheduling -->
                        <div class="md:col-span-2 bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Interview Schedule</label>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <input type="datetime-local" name="interview_date"
                                    class="flex-1 px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500">Skill Rating (1-5):</span>
                                    <input type="number" name="skill_rating" min="0" max="5"
                                        class="w-20 px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Tracking & Documents -->
                <div class="border-t border-gray-100 pt-6">
                    <h4
                        class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4 border-l-4 border-green-500 pl-3">
                        Documents</h4>

                    <div class="flex items-center gap-4">
                        <a id="viewResumeBtn" href="#" target="_blank"
                            class="hidden bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 hover:border-red-300 font-medium px-4 py-3 rounded-lg flex items-center gap-2 transition-all shadow-sm">
                            <i class="fas fa-file-pdf text-xl"></i>
                            <span>View Resume</span>
                        </a>
                        <span id="noResumeMsg" class="text-sm text-gray-500 italic flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i> No resume uploaded.
                        </span>
                    </div>
                </div>

                <!-- Section: Notes -->
                <div class="border-t border-gray-100 pt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none"
                        placeholder="Add internal notes about this candidate..."></textarea>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeModal()"
                        class="px-6 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Save
                        Candidate</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Modal for DOCX -->
    <div id="previewModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-5xl h-[90vh] flex flex-col">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-xl font-bold text-gray-800">Resume Preview</h3>
                <button onclick="closePreviewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div id="docx-container" class="flex-1 overflow-auto p-8 bg-gray-100">
                <!-- Content renders here -->
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal"
        class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden z-[60] flex items-center justify-center">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-sm p-6 transform transition-all">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Delete Candidate</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500">Are you sure you want to delete this candidate? This action cannot
                        be undone.</p>
                </div>
            </div>
            <div class="mt-5 sm:mt-6 flex gap-3">
                <button type="button" onclick="closeDeleteModal()"
                    class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                    Cancel
                </button>
                <button type="button" onclick="confirmDelete()"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm">
                    Delete
                </button>
            </div>
        </div>
    </div>

    <!-- SCRIPT -->
    <script>
        const modal = document.getElementById('candidateModal');
        const form = document.getElementById('candidateForm');
        let candidateToDeleteId = null;

        // ... [Previous functions] ...

        function deleteCandidate(id) {
            candidateToDeleteId = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            candidateToDeleteId = null;
        }

        function confirmDelete() {
            if (!candidateToDeleteId) return;

            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', candidateToDeleteId);

            fetch('', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        location.reload();
                    } else {
                        alert(res.message);
                    }
                });
        }

        function openModal(action) {
            document.getElementById('formAction').value = action;
            document.getElementById('modalTitle').textContent = action === 'add' ? 'Add Candidate' : 'Edit Candidate';
            form.reset();
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        function editCandidate(id) {
            openModal('update');
            document.getElementById('candidateId').value = id;

            // Fetch Data
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('id', id);

            fetch('', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        const d = res.data;
                        // Populate Form
                        // Populate Form - Hidden Inputs
                        form.full_name.value = d.full_name;
                        form.email.value = d.email;
                        form.contact_number.value = d.contact_number;
                        form.experience_years.value = d.experience_years;

                        // Populate Display Elements (Text Only)
                        document.getElementById('display_full_name').textContent = d.full_name || 'N/A';
                        document.getElementById('display_email').textContent = d.email || 'N/A';
                        document.getElementById('display_contact_number').textContent = d.contact_number || 'N/A';
                        document.getElementById('display_experience_years').textContent = (d.experience_years || '0') + ' Years';

                        form.position.value = d.position;
                        form.status.value = d.status;
                        form.interview_date.value = d.interview_date ? d.interview_date.replace(' ', 'T') : '';
                        form.skill_rating.value = d.skill_rating;
                        if (form.background_status) form.background_status.value = d.background_status || 'Pending';
                        form.notes.value = d.notes;

                        // Handle Resume View Button
                        const viewBtn = document.getElementById('viewResumeBtn');
                        const noMsg = document.getElementById('noResumeMsg');

                        if (d.resume_path) {
                            // Fix path to point to Main/uploads from Super-admin/Modules
                            const fullPath = '../../Main/' + d.resume_path;
                            const ext = fullPath.split('.').pop().toLowerCase();

                            viewBtn.classList.remove('hidden');
                            noMsg.classList.add('hidden');
                            noMsg.classList.remove('flex');

                            // Reset button state
                            viewBtn.onclick = null;
                            viewBtn.removeAttribute('target');
                            viewBtn.href = "#";

                            if (ext === 'docx') {
                                viewBtn.innerHTML = '<i class="fas fa-file-word text-xl"></i><span>Preview Resume</span>';
                                viewBtn.onclick = function (e) {
                                    e.preventDefault();
                                    previewDocument(fullPath);
                                };
                            } else if (ext === 'pdf') {
                                viewBtn.target = '_blank';
                                viewBtn.href = fullPath;
                                viewBtn.innerHTML = '<i class="fas fa-file-pdf text-xl"></i><span>View Resume</span>';
                            } else {
                                // Default/Download for others
                                viewBtn.target = '_blank';
                                viewBtn.href = fullPath;
                                viewBtn.innerHTML = '<i class="fas fa-file-download text-xl"></i><span>Download Resume</span>';
                            }

                        } else {
                            viewBtn.classList.add('hidden');
                            noMsg.classList.remove('hidden');
                            noMsg.classList.add('flex'); // Restore flex when showing
                        }
                    }
                });
        }

        function closePreviewModal() {
            document.getElementById('previewModal').classList.add('hidden');
        }

        function previewDocument(url) {
            const modal = document.getElementById('previewModal');
            const container = document.getElementById('docx-container');

            modal.classList.remove('hidden');
            container.innerHTML = '<div class="text-center py-10"><i class="fas fa-spinner fa-spin text-4xl text-gray-500"></i><p class="mt-4">Loading document...</p></div>';

            fetch(url)
                .then(res => res.blob())
                .then(blob => {
                    container.innerHTML = '';
                    const docxOptions = {
                        className: "docx-wrapper bg-white shadow-sm p-8 min-h-screen",
                        inWrapper: true,
                        ignoreWidth: false,
                        ignoreHeight: false,
                        breakPages: true
                    };
                    docx.renderAsync(blob, container, null, docxOptions)
                        .catch(e => {
                            console.error(e);
                            container.innerHTML = '<div class="text-red-500 text-center p-10">Error rendering document. It might be corrupted or in an old format. <br> <a href="' + url + '" class="text-blue-500 underline mt-4 block">Download File Instead</a></div>';
                        });
                })
                .catch(err => {
                    container.innerHTML = '<div class="text-red-500 text-center p-10">Failed to load file. <br> <a href="' + url + '" class="text-blue-500 underline mt-4 block">Download File Instead</a></div>';
                });
        }



        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const fd = new FormData(this);
            fetch('', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    alert(res.message);
                    if (res.status === 'success') location.reload();
                });
        });

        // Close on outside click
        window.onclick = function (e) {
            if (e.target == modal) closeModal();
        }
    </script>
</body>

</html>