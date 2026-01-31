<?php
session_start();
// Adjust path to root 'hr1-crane' folder
// From Modules/job_posting.php, we need to go up one level to 'hr1-crane'
include("../Database/Connections.php");

// --- 1. ACCESS RIGHTS (Super Admin/Admin Only) ---
if (!isset($_SESSION['Email']) || (isset($_SESSION['Account_type']) && $_SESSION['Account_type'] !== '1')) {
    // header("Location: ../login.php");
    // exit();
}
$isAdmin = true; // Always admin in this context

// --- 2. HANDLE POST REQUESTS (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = [];

    // Ensure applications table exists (Quick check)
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT,
            applicant_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            resume_path VARCHAR(255),
            application_type ENUM('Online', 'Walk-in') DEFAULT 'Online',
            status VARCHAR(50) DEFAULT 'Pending',
            status VARCHAR(50) DEFAULT 'Pending',
            profile_image VARCHAR(255),
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Ensure profile_image column exists (for existing tables)
        try {
            $conn->query("SELECT profile_image FROM applications LIMIT 1");
        } catch (Exception $e) {
            $conn->exec("ALTER TABLE applications ADD COLUMN profile_image VARCHAR(255)");
        }
    } catch (PDOException $e) {
    }

    try {
        if (isset($_POST['action'])) {
            // Check PIN Action
            if ($_POST['action'] === 'check_pin') {
                $pin = $_POST['pin'];
                $email = $_SESSION['Email'];

                // Fetch user's PIN
                $stmt = $conn->prepare("SELECT resume_pin FROM logintbl WHERE Email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Default PIN is 1234 if not set
                $correct_pin = $user['resume_pin'] ?? '1234';

                if ($pin === $correct_pin) {
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Incorrect PIN']);
                }
                exit;
            }

            // A. ADD/EDIT JOB
            if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
                $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
                $position = filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING);
                $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
                $requirements = filter_input(INPUT_POST, 'requirements', FILTER_SANITIZE_STRING);
                $contact = filter_input(INPUT_POST, 'contact', FILTER_SANITIZE_STRING);
                $platform = filter_input(INPUT_POST, 'platform', FILTER_SANITIZE_STRING);
                $date_posted = $_POST['date_posted'];
                $status = $_POST['status'];

                if ($_POST['action'] === 'add') {
                    $stmt = $conn->prepare("INSERT INTO job_postings (title, position, location, requirements, contact, platform, date_posted, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $position, $location, $requirements, $contact, $platform, $date_posted, $status]);
                    $response = ['status' => 'success', 'message' => 'Job posting added successfully!'];
                } elseif ($_POST['action'] === 'edit' && !empty($_POST['id'])) {
                    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                    $stmt = $conn->prepare("UPDATE job_postings SET title=?, position=?, location=?, requirements=?, contact=?, platform=?, date_posted=?, status=? WHERE id=?");
                    $stmt->execute([$title, $position, $location, $requirements, $contact, $platform, $date_posted, $status, $id]);
                    $response = ['status' => 'success', 'message' => 'Job posting updated successfully!'];
                }
            }

            // B. SUBMIT WALK-IN APPLICATION
            elseif ($_POST['action'] === 'submit_application') {
                $type = 'Walk-in'; // Explicitly Walk-in for Admin Module
                $job_id = filter_input(INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT);
                $name = filter_input(INPUT_POST, 'applicant_name', FILTER_SANITIZE_STRING);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

                // Handle File Upload (Resume)
                $resume_path = null;
                if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/resumes/';
                    if (!is_dir($uploadDir))
                        mkdir($uploadDir, 0777, true);

                    $fileName = time() . '_' . basename($_FILES['resume']['name']);
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
                        $resume_path = $targetPath;
                    }
                }

                $stmt = $conn->prepare("INSERT INTO applications (job_id, applicant_name, email, phone, resume_path, application_type, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
                $stmt->execute([$job_id, $name, $email, $phone, $resume_path, $type]);

                $response = ['status' => 'success', 'message' => 'Walk-in application submitted successfully!'];
            }

            // B.2 EDIT APPLICATION
            elseif ($_POST['action'] === 'edit_application' && !empty($_POST['id'])) {
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                $job_id = filter_input(INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT);
                $name = filter_input(INPUT_POST, 'applicant_name', FILTER_SANITIZE_STRING);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
                $status = $_POST['status'];

                $sql = "UPDATE applications SET job_id=?, applicant_name=?, email=?, phone=?, status=? WHERE id=?";
                $params = [$job_id, $name, $email, $phone, $status, $id];

                // Handle Resume Update if file uploaded
                if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/resumes/';
                    if (!is_dir($uploadDir))
                        mkdir($uploadDir, 0777, true);
                    $fileName = time() . '_' . basename($_FILES['resume']['name']);
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
                        $sql = "UPDATE applications SET job_id=?, applicant_name=?, email=?, phone=?, status=?, resume_path=? WHERE id=?";
                        $params = [$job_id, $name, $email, $phone, $status, $targetPath, $id];
                    }
                }

                // Handle Profile Image Update if file uploaded
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../Main/uploads/resume_images/';
                    if (!is_dir($uploadDir))
                        mkdir($uploadDir, 0777, true);
                    $fileName = 'profile_' . time() . '_' . basename($_FILES['profile_image']['name']);
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                        // If params already has resume path (7 items), update sql for profile_image too
                        if (count($params) === 7) {
                            $sql = "UPDATE applications SET job_id=?, applicant_name=?, email=?, phone=?, status=?, resume_path=?, profile_image=? WHERE id=?";
                            $params = [$job_id, $name, $email, $phone, $status, $params[5], $fileName, $id];
                        } else {
                            // No resume update
                            $sql = "UPDATE applications SET job_id=?, applicant_name=?, email=?, phone=?, status=?, profile_image=? WHERE id=?";
                            $params = [$job_id, $name, $email, $phone, $status, $fileName, $id];
                        }
                    }
                }

                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $response = ['status' => 'success', 'message' => 'Application updated successfully!'];
            }
        }

        // C. DELETE JOB
        if (isset($_POST['action_delete']) && $_POST['action_delete'] === 'delete' && !empty($_POST['id'])) {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $conn->prepare("DELETE FROM job_postings WHERE id=?");
            $stmt->execute([$id]);
            $response = ['status' => 'success', 'message' => 'Job posting deleted!'];
        }

        // D. DELETE APPLICATION
        if (isset($_POST['action_delete_app']) && $_POST['action_delete_app'] === 'delete' && !empty($_POST['id'])) {
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $conn->prepare("DELETE FROM applications WHERE id=?");
            $stmt->execute([$id]);
            $response = ['status' => 'success', 'message' => 'Application deleted!'];
        }

    } catch (PDOException $e) {
        $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }

    echo json_encode($response);
    exit();
}

// --- 3. GET DATA (AJAX or Page Load) ---
if (isset($_GET['action']) && $_GET['action'] == 'get_job' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $conn->prepare("SELECT *, DATE_FORMAT(date_posted, '%Y-%m-%d') as date_posted_formatted FROM job_postings WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($job ? ['status' => 'success', 'data' => $job] : ['status' => 'error', 'message' => 'Job not found.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch all jobs
try {
    $stmt = $conn->prepare("SELECT * FROM job_postings ORDER BY created_at DESC");
    $stmt->execute();
    $job_postings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $job_postings = [];
}

// Fetch Applications
try {
    $stmt = $conn->prepare("SELECT a.*, j.title as job_title FROM applications a LEFT JOIN job_postings j ON a.job_id = j.id ORDER BY a.applied_at DESC");
    $stmt->execute();
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $applications = [];
}

// Get Application Details (AJAX)
if (isset($_GET['action']) && $_GET['action'] == 'get_application' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $conn->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($app ? ['status' => 'success', 'data' => $app] : ['status' => 'error', 'message' => 'Application not found.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Posting & Applications - HR Admin</title>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <!-- Use Tailwind for content styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

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
    <style>
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

        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Layout Fixes for Admin Components */
        body {
            display: flex;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Main Content Container to work with Sidebar */
        .main-content {
            flex-grow: 1;
            padding-top: 100px;
            /* Space for Fixed Header */
            padding-right: 30px;
            padding-bottom: 30px;
            padding-left: 30px;

            /* Critical Fixed Sidebar Adjustment */
            margin-left: 260px;
            /* Must match sidebar width */
            width: calc(100% - 260px);

            transition: margin-left 0.3s ease, width 0.3s ease;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding-left: 20px;
                padding-right: 20px;
            }
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 font-sans">

    <!-- Sidebar -->
    <?php include '../Components/sidebar_admin.php'; ?>

    <!-- Header -->
    <?php include '../Components/header_admin.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-content min-h-screen px-8 pb-8" id="mainContent">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Job Posting & Applications</h1>
            <p class="text-gray-500 mt-1">Manage job postings and walk-in applications</p>
        </div>

        <!-- Action Bar: Buttons Left, Search Right -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <div class="flex gap-3">
                <button id="addJobBtn"
                    class="flex items-center gap-2 bg-gray-800 text-white px-5 py-2.5 rounded-lg hover:bg-gray-900 transition-all shadow-sm font-medium">
                    <i class="fas fa-briefcase"></i> Add Job Posting
                </button>
                <button id="walkInBtn"
                    class="flex items-center gap-2 bg-emerald-500 text-white px-5 py-2.5 rounded-lg hover:bg-emerald-600 transition-all shadow-sm font-medium">
                    <i class="fas fa-user-plus"></i> Walk-In Application
                </button>
            </div>

            <div class="relative w-full sm:w-72">
                <input type="text" placeholder="Search postings..."
                    class="w-full px-4 py-2.5 pl-10 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-gray-800 focus:border-transparent outline-none transition-all text-sm">
                <i class="fas fa-search absolute left-3.5 top-3.5 text-gray-400"></i>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex gap-8 border-b border-gray-200 mb-6">
            <button id="tabJobs" onclick="switchTab('jobs')"
                class="pb-3 px-1 text-gray-800 font-bold border-b-2 border-gray-800 transition-colors">
                Job Postings
            </button>
            <button id="tabApps" onclick="switchTab('apps')"
                class="pb-3 px-1 text-gray-500 font-medium hover:text-gray-800 border-b-2 border-transparent transition-colors">
                Applications
            </button>
        </div>

        <!-- JOBS TABLE -->
        <div id="jobsTableContainer" class="bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-900 text-white text-xs uppercase font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 text-left">Title & Position</th>
                            <th class="px-6 py-4 text-left">Location</th>
                            <th class="px-6 py-4 text-left">Date Posted</th>
                            <th class="px-6 py-4 text-center">Platform</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php if (empty($job_postings)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No job postings found.</td>
                            </tr>
                        <?php else:
                            foreach ($job_postings as $job): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-800"><?= htmlspecialchars($job['title']) ?></div>
                                        <div class="text-xs text-gray-500 font-medium mt-0.5">
                                            <?= htmlspecialchars($job['position']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <?= htmlspecialchars($job['location']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        <?= date('M d, Y', strtotime($job['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2.5 py-1 rounded-md">
                                            <?= htmlspecialchars($job['platform'] ?: 'General') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold <?= $job['status'] == 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' ?>">
                                            <?= ucfirst($job['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center items-center gap-3">
                                            <button onclick="viewJob(<?= $job['id'] ?>)"
                                                class="text-gray-400 hover:text-gray-800 transition-colors"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="editJob(<?= $job['id'] ?>)"
                                                class="text-gray-400 hover:text-blue-600 transition-colors" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteJob(<?= $job['id'] ?>)"
                                                class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- APPLICATIONS TABLE -->
        <div id="appsTableContainer" class="hidden bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-900 text-white text-xs uppercase font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 text-left">Applicant</th>
                            <th class="px-6 py-4 text-left">Job Applied</th>
                            <th class="px-6 py-4 text-center">Type</th>
                            <th class="px-6 py-4 text-left">Contact</th>
                            <th class="px-6 py-4 text-left">Date</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-center">Resume</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">No applications found.</td>
                            </tr>
                        <?php else:
                            foreach ($applications as $app): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($app['applicant_name']) ?>
                                        </div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($app['email']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 font-medium">
                                        <?= htmlspecialchars($app['job_title'] ?? 'General Application') ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-block px-2.5 py-1 text-xs font-medium rounded bg-purple-100 text-purple-800">
                                            <?= htmlspecialchars($app['application_type']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <?= htmlspecialchars($app['phone']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <?= date('M d, Y', strtotime($app['applied_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800">
                                            <?= htmlspecialchars($app['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($app['resume_path']): ?>
                                            <button
                                                onclick="showPinModal(<?= $app['id'] ?>, '<?= htmlspecialchars($app['resume_path']) ?>')"
                                                class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs flex items-center justify-center gap-1 mx-auto transition-colors">
                                                <i class="fas fa-lock text-[10px]"></i> View Resume
                                            </button>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs italic">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center items-center gap-3">
                                            <button onclick="viewProfile(<?= $app['id'] ?>)"
                                                class="text-gray-400 hover:text-indigo-600 transition-colors"
                                                title="View Profile">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="editApplication(<?= $app['id'] ?>)"
                                                class="text-gray-400 hover:text-blue-600 transition-colors" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteApplication(<?= $app['id'] ?>)"
                                                class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL: ADD/EDIT JOB -->
    <div id="jobModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm z-50">
        <div
            class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform transition-all scale-100">
            <div class="flex items-center justify-between p-6 border-b border-gray-100">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-800">Add New Job Posting</h3>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <form id="jobForm" class="p-6 space-y-5">
                <input type="hidden" id="formAction" name="action" value="add">
                <input type="hidden" id="jobId" name="id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Job
                            Title</label>
                        <input type="text" id="jobTitle" name="title"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                            required>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Position</label>
                        <input type="text" id="jobPosition" name="position"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                            required>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Location</label>
                        <input type="text" id="jobLocation" name="location"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                            required>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Platform</label>
                        <input type="text" id="jobPlatform" name="platform"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Contact</label>
                        <input type="tel" id="jobContact" name="contact" maxlength="11"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Date
                            Posted</label>
                        <input type="date" id="jobDate" name="date_posted"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                            required>
                    </div>
                    <div class="sm:col-span-2">
                        <label
                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
                        <select id="jobStatus" name="status"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label
                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Requirements</label>
                        <textarea id="jobRequirements" name="requirements" rows="4"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all"></textarea>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100 gap-3">
                    <button type="button" id="cancelBtn"
                        class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition-colors shadow-sm">Save
                        Posting</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: WALK-IN APPLICATION (Shared Logic) -->
    <!-- PIN Verification Modal -->
    <div id="pinModal"
        class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl max-w-xs w-full overflow-hidden transform transition-all scale-100">
            <div class="p-6 text-center">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock text-indigo-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-1">Enter PIN</h3>
                <p class="text-xs text-gray-500 mb-6">Please enter the 4-digit security PIN to view this resume.</p>

                <div class="flex gap-2 justify-center mb-6">
                    <input type="password" maxlength="1"
                        class="w-10 h-12 text-center text-xl font-bold border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 outline-none transition-all modal-pin-input"
                        data-index="0">
                    <input type="password" maxlength="1"
                        class="w-10 h-12 text-center text-xl font-bold border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 outline-none transition-all modal-pin-input"
                        data-index="1">
                    <input type="password" maxlength="1"
                        class="w-10 h-12 text-center text-xl font-bold border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 outline-none transition-all modal-pin-input"
                        data-index="2">
                    <input type="password" maxlength="1"
                        class="w-10 h-12 text-center text-xl font-bold border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 outline-none transition-all modal-pin-input"
                        data-index="3">
                </div>

                <div class="flex gap-3">
                    <button onclick="closePinModal()"
                        class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">Cancel</button>
                    <!-- Button logic handled by auto-submit on 4th digit or manual click if needed, but auto is better -->
                </div>
            </div>
        </div>
    </div>

    <div id="applicationModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-start justify-center pt-24 p-4 backdrop-blur-sm z-50">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100">
                <h3 id="appModalTitle" class="text-xl font-bold text-gray-800">Walk-In Application</h3>
                <button id="closeAppModal" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <form id="applicationForm" class="p-6 space-y-4" enctype="multipart/form-data">
                <input type="hidden" name="action" id="appFormAction" value="submit_application">
                <input type="hidden" name="id" id="appId">
                <input type="hidden" name="application_type" id="appType" value="Walk-in">

                <!-- Job Selection -->
                <div id="jobSelectContainer">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Select
                        Job</label>
                    <select name="job_id" id="jobSelect"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        <?php foreach ($job_postings as $j): ?>
                            <option value="<?= $j['id'] ?>"><?= htmlspecialchars($j['title']) ?>
                                (<?= htmlspecialchars($j['position']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Applicant
                        Name</label>
                    <input type="text" name="applicant_name"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Email
                        Address</label>
                    <input type="email" name="email"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Profile
                        Image</label>
                    <input type="file" name="profile_image" accept="image/*"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>
                <div>
                    <div class="mb-1.5">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Phone
                            Number</label>
                        <input type="tel" name="phone" pattern="^(09|\+639)\d{9}$" maxlength="11" placeholder="09..."
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all"
                            required>
                    </div>
                </div>
                <div>
                    <div class="mb-1.5">
                        <a id="viewResumeLink" href="#" target="_blank"
                            class="hidden text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1 uppercase tracking-wider">
                            <i class="fas fa-external-link-alt"></i> View Resume
                        </a>
                        <span id="defaultResumeLabel"
                            class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Resume</span>
                    </div>
                    <input type="file" id="resumeInput" name="resume" accept=".pdf,.doc,.docx"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                </div>

                <div id="appStatusContainer" class="hidden">
                    <label
                        class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" id="appStatus"
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        <option value="Pending">Pending</option>
                        <option value="Active">Active</option>
                        <option value="Interview">Interview</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Hired">Hired</option>
                    </select>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100 gap-3">
                    <button type="button" id="cancelAppBtn"
                        class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition-colors shadow-sm">Submit
                        Application</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: VIEW PROFILE -->
    <div id="profileModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm z-50">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-800">Applicant Profile</h3>
                <button onclick="document.getElementById('profileModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div class="p-6 flex flex-col items-center text-center">
                <div
                    class="w-32 h-32 rounded-full bg-gray-100 border-4 border-white shadow-md mb-4 flex items-center justify-center overflow-hidden">
                    <img id="profileImage" src="" alt="Profile" class="w-full h-full object-cover hidden">
                    <span id="profilePlaceholder" class="text-3xl font-bold text-gray-300"></span>
                </div>
                <h4 id="profileName" class="text-xl font-bold text-gray-900"></h4>
                <p id="profileEmail" class="text-sm text-gray-500 mb-1"></p>
                <p id="profileRole"
                    class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-1 rounded-md uppercase tracking-wide">
                </p>
            </div>
            <div class="bg-gray-50 p-4 flex justify-center">
                <button onclick="document.getElementById('profileModal').classList.add('hidden')"
                    class="px-6 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">Close</button>
            </div>
        </div>
    </div>

    <!-- MODAL: VIEW JOB DETAILS -->
    <div id="viewModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm z-50">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100">
                <h3 class="text-xl font-bold text-gray-800">Job Details</h3>
                <button onclick="document.getElementById('viewModal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div id="jobDetails" class="p-6"></div>
            <!-- Close button removed as requested -->
        </div>
    </div>

    <script>
        // --- Sidebar Logic (Adapted for Components/sidebar_admin.php) ---
        // Need to ensure the sidebar behaves correctly with the content

        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector(".sidebar");
            const toggleBtn = document.getElementById("sidebar-toggle");
            const mainContent = document.getElementById("mainContent");

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener("click", () => {
                    // The sidebar toggle is handled in header_admin.php's script
                    // But we might need to adjust mainContent margin here
                    sidebar.classList.toggle("close");

                    if (sidebar.classList.contains("close")) {
                        // Assuming sidebar width shrinks when closed
                        mainContent.style.marginLeft = "78px"; // Adjust based on collapsed width
                        mainContent.style.width = "calc(100% - 78px)";
                    } else {
                        mainContent.style.marginLeft = "260px";
                        mainContent.style.width = "calc(100% - 260px)";
                    }
                });
            }
        });

        // --- Tab Switching Logic ---
        function switchTab(tabName) {
            const jobsTable = document.getElementById('jobsTableContainer');
            const appsTable = document.getElementById('appsTableContainer');
            const tabJobs = document.getElementById('tabJobs');
            const tabApps = document.getElementById('tabApps');
            const addJobBtn = document.getElementById('addJobBtn');
            const walkInBtn = document.getElementById('walkInBtn');

            if (tabName === 'jobs') {
                jobsTable.classList.remove('hidden');
                appsTable.classList.add('hidden');

                tabJobs.className = "pb-3 px-2 text-indigo-600 font-semibold border-b-2 border-indigo-600 transition-colors";
                tabApps.className = "pb-3 px-2 text-gray-500 font-medium hover:text-indigo-600 border-b-2 border-transparent transition-colors";

                addJobBtn.classList.remove('hidden');
            } else {
                jobsTable.classList.add('hidden');
                appsTable.classList.remove('hidden');

                tabApps.className = "pb-3 px-2 text-indigo-600 font-semibold border-b-2 border-indigo-600 transition-colors";
                tabJobs.className = "pb-3 px-2 text-gray-500 font-medium hover:text-indigo-600 border-b-2 border-transparent transition-colors";

                addJobBtn.classList.add('hidden');
            }
        }

        // --- Modals Logic ---
        const jobModal = document.getElementById('jobModal');
        const jobForm = document.getElementById('jobForm');

        // Add Job
        document.getElementById('addJobBtn').addEventListener('click', () => {
            jobForm.reset();
            document.getElementById('modalTitle').textContent = 'Add New Job Posting';
            document.getElementById('formAction').value = 'add';
            document.getElementById('jobId').value = '';
            // Set default date to today
            document.getElementById('jobDate').valueAsDate = new Date();
            jobModal.classList.remove('hidden');
        });

        // Close/Cancel Add Job
        document.getElementById('closeModal').addEventListener('click', () => jobModal.classList.add('hidden'));
        document.getElementById('cancelBtn').addEventListener('click', () => jobModal.classList.add('hidden'));

        // Handle Add/Edit Job Submit
        jobForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(jobForm);
            fetch('job_posting.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') location.reload();
                });
        });

        // Edit Job (Fetch & Populate)
        window.editJob = function (id) {
            fetch(`job_posting.php?action=get_job&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const job = data.data;
                        document.getElementById('modalTitle').textContent = 'Edit Job Posting';
                        document.getElementById('formAction').value = 'edit';
                        document.getElementById('jobId').value = job.id;

                        document.getElementById('jobTitle').value = job.title;
                        document.getElementById('jobPosition').value = job.position;
                        document.getElementById('jobLocation').value = job.location;
                        document.getElementById('jobPlatform').value = job.platform;
                        document.getElementById('jobContact').value = job.contact;
                        document.getElementById('jobDate').value = job.date_posted_formatted; // Ensure YYYY-MM-DD
                        document.getElementById('jobStatus').value = job.status;
                        document.getElementById('jobRequirements').value = job.requirements;

                        jobModal.classList.remove('hidden');
                    } else alert(data.message);
                });
        }

        // Delete Job
        window.deleteJob = function (id) {
            if (confirm('Are you sure you want to delete this job posting? This cannot be undone.')) {
                const fd = new FormData();
                fd.append('action_delete', 'delete');
                fd.append('id', id);
                fetch('job_posting.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        if (data.status === 'success') location.reload();
                    });
            }
        }

        // Walk-in Application Logic
        const appModal = document.getElementById('applicationModal');
        const appFormAction = document.getElementById('appFormAction');
        const appStatusContainer = document.getElementById('appStatusContainer');
        const viewResumeLink = document.getElementById('viewResumeLink');
        const defaultResumeLabel = document.getElementById('defaultResumeLabel');
        const resumeInput = document.getElementById('resumeInput');

        document.getElementById('walkInBtn').addEventListener('click', () => {
            document.getElementById('applicationForm').reset();
            document.getElementById('appModalTitle').innerText = 'Walk-In Application';
            appFormAction.value = 'submit_application';
            document.getElementById('appId').value = '';
            appStatusContainer.classList.add('hidden'); // Hide status on new application

            // Reset Resume Label/Input
            if (viewResumeLink) viewResumeLink.classList.add('hidden');
            if (defaultResumeLabel) defaultResumeLabel.classList.remove('hidden');
            if (resumeInput) resumeInput.classList.remove('hidden');

            appModal.classList.remove('hidden');
        });
        document.getElementById('closeAppModal').addEventListener('click', () => appModal.classList.add('hidden'));
        document.getElementById('cancelAppBtn').addEventListener('click', () => appModal.classList.add('hidden'));

        // Edit Application
        window.editApplication = function (id) {
            fetch(`job_posting.php?action=get_application&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const app = data.data;
                        document.getElementById('appModalTitle').innerText = 'Edit Application';
                        appFormAction.value = 'edit_application';
                        document.getElementById('appId').value = app.id;

                        document.getElementById('jobSelect').value = app.job_id;
                        document.getElementsByName('applicant_name')[0].value = app.applicant_name;
                        document.getElementsByName('email')[0].value = app.email;
                        document.getElementsByName('phone')[0].value = app.phone;

                        // Show and set status
                        appStatusContainer.classList.remove('hidden');
                        document.getElementById('appStatus').value = app.status;

                        // Handle View Resume Link & Input
                        if (viewResumeLink && defaultResumeLabel && resumeInput) {
                            if (app.resume_path) {
                                viewResumeLink.href = app.resume_path;
                                viewResumeLink.classList.remove('hidden');
                                defaultResumeLabel.classList.add('hidden');
                                resumeInput.classList.add('hidden');
                            } else {
                                viewResumeLink.classList.add('hidden');
                                defaultResumeLabel.classList.remove('hidden');
                                resumeInput.classList.remove('hidden');
                            }
                        }

                        appModal.classList.remove('hidden');
                    } else alert(data.message);
                });
        }

        // View Profile (Secured)
        window.viewProfile = function (id) {
            showPinModalForProfile(id);
        }

        window.openProfileModal = function (id) {
            fetch(`job_posting.php?action=get_application&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const app = data.data;
                        document.getElementById('profileName').innerText = app.applicant_name;
                        document.getElementById('profileEmail').innerText = app.email;
                        document.getElementById('profileRole').innerText = app.application_type;

                        const img = document.getElementById('profileImage');
                        const ph = document.getElementById('profilePlaceholder');

                        if (app.profile_image) {
                            img.src = `../Main/uploads/resume_images/${app.profile_image}`;
                            img.classList.remove('hidden');
                            ph.classList.add('hidden');
                        } else {
                            img.classList.add('hidden');
                            ph.classList.remove('hidden');
                            ph.innerText = app.applicant_name.charAt(0).toUpperCase();
                        }

                        document.getElementById('profileModal').classList.remove('hidden');
                    } else alert(data.message);
                });
        }

        // Delete Application
        window.deleteApplication = function (id) {
            if (confirm('Delete this application record?')) {
                const fd = new FormData();
                fd.append('action_delete_app', 'delete');
                fd.append('id', id);
                fetch('job_posting.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        if (data.status === 'success') location.reload();
                    });
            }
        }

        // Handle Application Submit
        document.getElementById('applicationForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const fd = new FormData(this);
            fetch('job_posting.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        appModal.classList.add('hidden');
                        // location.reload(); // Optional
                        switchTab('apps'); // Switch to apps tab to see new entry
                        setTimeout(() => location.reload(), 500);
                    }
                });
        });

        // View Job Details (Secured)
        window.viewJob = function (id) {
            showPinModalForJob(id);
        };

        window.openJobModal = function (id) {
            fetch(`job_posting.php?action=get_job&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const job = data.data;
                        document.getElementById('jobDetails').innerHTML = `
                        <div class="space-y-4 text-gray-700">
                             <div class="flex justify-between items-start">
                                <h2 class="text-2xl font-bold text-gray-900">${job.title}</h2>
                                <span class="bg-indigo-100 text-indigo-700 text-xs px-2.5 py-1 rounded-full font-semibold uppercase tracking-wide">${job.status}</span>
                             </div>
                             <div class="grid grid-cols-2 gap-y-2 gap-x-4 text-sm mt-4">
                                <div><span class="block text-xs font-semibold text-gray-500 uppercase">Position</span> <span class="font-medium">${job.position}</span></div>
                                <div><span class="block text-xs font-semibold text-gray-500 uppercase">Location</span> <span class="font-medium">${job.location}</span></div>
                                <div><span class="block text-xs font-semibold text-gray-500 uppercase">Platform</span> <span class="font-medium">${job.platform || 'N/A'}</span></div>
                                <div><span class="block text-xs font-semibold text-gray-500 uppercase">Contact</span> <span class="font-medium">${job.contact || 'N/A'}</span></div>
                                <div class="col-span-2"><span class="block text-xs font-semibold text-gray-500 uppercase">Date Posted</span> <span class="font-medium">${job.date_posted_formatted}</span></div>
                             </div>
                             <div class="pt-4 border-t border-gray-100">
                                <h4 class="text-sm font-bold text-gray-800 uppercase mb-2">Requirements</h4>
                                <div class="bg-gray-50 p-4 rounded-lg text-sm leading-relaxed whitespace-pre-wrap text-gray-600 border border-gray-100">${job.requirements}</div>
                             </div>
                        </div>
                    `;
                        document.getElementById('viewModal').classList.remove('hidden');
                    }
                });
        }


        // --- PIN MODAL LOGIC ---
        let currentResumePath = '';
        let currentAppId = null;
        let currentJobId = null;
        let currentProfileId = null;

        window.showPinModal = function (id, path) {
            currentResumePath = path;
            currentAppId = id;
            currentJobId = null;
            currentProfileId = null;
            document.getElementById('pinModal').classList.remove('hidden');

            // Clear inputs
            const inputs = document.querySelectorAll('.modal-pin-input');
            inputs.forEach(i => {
                i.value = '';
                i.classList.remove('border-red-500', 'ring-red-500');
            });
            setTimeout(() => inputs[0].focus(), 100);
        };

        window.showPinModalForJob = function (id) {
            currentJobId = id;
            currentResumePath = '';
            currentAppId = null;
            currentProfileId = null;
            document.getElementById('pinModal').classList.remove('hidden');

            const inputs = document.querySelectorAll('.modal-pin-input');
            inputs.forEach(i => {
                i.value = '';
                i.classList.remove('border-red-500', 'ring-red-500');
            });
            setTimeout(() => inputs[0].focus(), 100);
        };

        window.showPinModalForProfile = function (id) {
            currentProfileId = id;
            currentJobId = null;
            currentResumePath = '';
            currentAppId = null;
            document.getElementById('pinModal').classList.remove('hidden');

            const inputs = document.querySelectorAll('.modal-pin-input');
            inputs.forEach(i => {
                i.value = '';
                i.classList.remove('border-red-500', 'ring-red-500');
            });
            setTimeout(() => inputs[0].focus(), 100);
        };

        window.closePinModal = function () {
            document.getElementById('pinModal').classList.add('hidden');
            currentResumePath = '';
            currentAppId = null;
            currentJobId = null;
            currentProfileId = null;
        };

        // Handle Modal PIN Inputs
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('.modal-pin-input');

            inputs.forEach((input, index) => {
                input.addEventListener('input', function (e) {
                    if (this.value.length === 1) {
                        // Move to next
                        if (index < inputs.length - 1) inputs[index + 1].focus();
                    }

                    // Check if all filled
                    let pin = '';
                    let filled = true;
                    inputs.forEach(i => {
                        pin += i.value;
                        if (i.value === '') filled = false;
                    });

                    if (filled) {
                        // Verify PIN via AJAX
                        const formData = new FormData();
                        formData.append('action', 'check_pin');
                        formData.append('pin', pin);

                        fetch('job_posting.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(res => res.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    // Success
                                    if (currentResumePath) {
                                        window.open(currentResumePath, '_blank');
                                    } else if (currentJobId) {
                                        openJobModal(currentJobId);
                                    } else if (currentProfileId) {
                                        openProfileModal(currentProfileId);
                                    }
                                    closePinModal();
                                } else {
                                    // Error
                                    inputs.forEach(i => {
                                        i.value = '';
                                        i.classList.add('border-red-500', 'ring-red-500');
                                        setTimeout(() => i.classList.remove('border-red-500', 'ring-red-500'), 1000);
                                    });
                                    inputs[0].focus();
                                }
                            })
                            .catch(err => console.error(err));
                    }
                });


                // Backspace
                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Backspace' && this.value.length === 0) {
                        if (index > 0) inputs[index - 1].focus();
                    }
                });
            });
        });

    </script>
</body>

</html>