<?php
session_start();
// Adjust path to root 'hr1-crane' folder
// From Super-admin/Modules/Job_posting.php, we are deep in Super-admin/Modules/
include("../../Database/Connections.php");

// --- PUBLIC ACCESS ALLOWED (No Admin Check needed for Public View usually, but let's see) ---
// If this is strictly public view, we don't need admin check.
// However, the original file had no check.
// But some logic handles apps.
// Let's keep it open or just session check if needed.
// For now, I'll remove the STRICT super admin check so it can be viewed by anyone or Applicants?
// But usually applicants don't login here?
// The prompt implies this is "Picture 2" public view.

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
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (PDOException $e) {
    }

    try {
        if (isset($_POST['action'])) {
            // SUBMIT ONLINE APPLICATION
            if ($_POST['action'] === 'submit_application') {
                $type = 'Online';
                $job_id = filter_input(INPUT_POST, 'job_id', FILTER_SANITIZE_NUMBER_INT);
                $name = filter_input(INPUT_POST, 'applicant_name', FILTER_SANITIZE_STRING);
                $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

                // Handle File Upload (Resume)
                $resume_path = null;
                if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                    // Uploads are in root/uploads
                    $uploadDir = '../../uploads/resumes/';
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

                $response = ['status' => 'success', 'message' => 'Application submitted successfully!'];
            }
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR1 CAREERS | JOB POSTING</title>
    <link rel="icon" type="image/x-icon" href="../../Image/logo.png">
    <!-- Use the same CS as Dashboard -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: { brand: { 500: '#6366f1', 600: '#4f46e5' } } // Indigo branding to match Dashboard
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
    </style>
</head>

<body class="bg-[#f8f9fa] text-gray-800 font-sans">

    <?php
    // Define CSS path for components included from a subdirectory
    $css_path = '../Css/';
    // Define Root Path for links in sidebar
    $root_path = '../../';
    ?>

    <!-- Sidebar -->
    <?php include '../Components/sidebar.php'; ?>

    <!-- Header -->
    <?php include '../Components/header.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-content min-h-screen pt-24 pb-8 px-4 sm:px-8 ml-64 transition-all duration-300" id="mainContent">

        <div class="max-w-7xl mx-auto text-center mb-10">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl uppercase tracking-tight">Current Openings</h2>
            <p class="mt-4 text-gray-500 uppercase text-xs font-bold tracking-widest">Explore the opportunities waiting for you.</p>
        </div>

        <!-- Job Listings Grid -->
        <div class="max-w-7xl mx-auto pb-20">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($job_postings as $job):
                    if ($job['status'] !== 'active')
                        continue;
                    ?>
                    <!-- Job Card -->
                    <div
                        class="bg-white rounded-xl border border-gray-100 shadow-sm hover:border-indigo-500/30 transition-all p-6 flex flex-col h-full relative group">
                        <!-- Top Right Badge (Platform) -->
                        <div class="absolute top-6 right-6">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-blue-50 text-blue-600 border border-blue-100">
                                <?= htmlspecialchars($job['platform'] ?: 'N/A') ?>
                            </span>
                        </div>
 
                        <div class="mb-4">
                            <h3 class="text-xl font-black text-gray-900 pr-16 leading-tight uppercase tracking-tight">
                                <?= htmlspecialchars($job['title']) ?>
                            </h3>
                            <p class="text-[10px] font-bold text-indigo-600 uppercase mt-1 tracking-widest">
                                <?= htmlspecialchars($job['position']) ?>
                            </p>
                        </div>

                        <div class="space-y-3 mb-6 flex-grow">
                            <div class="flex items-center text-sm text-gray-400 font-light">
                                <i class="fas fa-map-marker-alt w-5 text-indigo-500/50"></i>
                                <span><?= htmlspecialchars($job['location']) ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-400 font-light">
                                <i class="far fa-clock w-5 text-indigo-500/50"></i>
                                <span>Posted <?= date('M d, Y', strtotime($job['created_at'])) ?></span>
                            </div>
                            <div class="pt-2 text-sm text-gray-500 line-clamp-2">
                                <?= htmlspecialchars(substr($job['requirements'], 0, 100)) ?>...
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-auto pt-6 border-t border-gray-50">
                            <button onclick="viewJob(<?= $job['id'] ?>)"
                                class="text-indigo-600 hover:text-indigo-700 text-xs font-bold uppercase tracking-widest transition-colors">
                                View Details
                            </button>
                            <button onclick="openApplicationModal(<?= $job['id'] ?>)"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-widest py-2.5 px-6 rounded-lg transition-all shadow-md">
                                Apply Now
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($job_postings)): ?>
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                        <i class="fas fa-briefcase text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">No openings found</h3>
                    <p class="mt-2 text-gray-500">Check back later for new opportunities.</p>
                </div>
            <?php endif; ?>
        </div>
    </div> <!-- End Main Content Wrapper -->

    <!-- MODAL: VIEW JOB DETAILS (Improved UI) -->
    <div id="viewModal"
        class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div
            class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform transition-all scale-100 relative border border-gray-100">
            <button onclick="document.getElementById('viewModal').classList.add('hidden')"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
            <div id="jobDetails" class="p-8"></div>
        </div>
    </div>

    <!-- MODAL: APPLICATION FORM -->
    <div id="applicationModal"
        class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div
            class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto transform transition-all scale-100 relative border border-gray-100">
            <button id="closeAppModal"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition-colors z-10">
                <i class="fas fa-times text-lg"></i>
            </button>

            <div class="px-8 pt-8 pb-2 border-b border-gray-100">
                <h3 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Apply for Position</h3>
                <p class="text-[10px] text-gray-500 mt-1 uppercase font-bold tracking-widest pb-4">Please fill out the form below.</p>
            </div>

            <form id="applicationForm" class="p-8 space-y-5" enctype="multipart/form-data">
                <input type="hidden" name="action" value="submit_application">
                <input type="hidden" name="application_type" value="Online">
                <input type="hidden" name="job_id" id="jobIdInput"> <!-- populated by JS -->

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Full Name</label>
                    <input type="text" name="applicant_name"
                        class="block w-full px-4 py-3 rounded-lg border-gray-200 bg-gray-50 border focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all text-gray-900 placeholder-gray-400"
                        placeholder="John Doe" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Email Address</label>
                    <input type="email" name="email"
                        class="block w-full px-4 py-3 rounded-lg border-gray-200 bg-gray-50 border focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all text-gray-900 placeholder-gray-400"
                        placeholder="you@example.com" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
                    <input type="tel" name="phone"
                        class="block w-full px-4 py-2.5 rounded-lg border-gray-300 bg-gray-50 border focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"
                        placeholder="09123456789"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        pattern="[0-9]{11,12}"
                        minlength="11"
                        maxlength="12"
                        inputmode="numeric"
                        title="Please enter 11 to 12 digits"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Resume / CV</label>
                    <div
                        class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-200 border-dashed rounded-lg hover:border-indigo-500 bg-gray-50 hover:bg-white transition-all">
                        <div class="space-y-1 text-center">
                            <i class="fas fa-cloud-upload-alt text-indigo-300 text-3xl mb-2"></i>
                            <div class="flex text-xs font-bold text-gray-400 uppercase tracking-widest justify-center">
                                <label for="file-upload"
                                    class="relative cursor-pointer bg-transparent rounded-md font-bold text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                    <span>Upload a file</span>
                                    <input id="file-upload" name="resume" type="file" class="sr-only"
                                        accept=".pdf,.doc,.docx" required>
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-[10px] text-gray-400">PDF, DOC, DOCX up to 10MB</p>
                        </div>
                    </div>
                </div>
 
                <div class="pt-6 border-t border-gray-100">
                    <button type="submit"
                        class="w-full flex justify-center py-4 px-4 border border-transparent rounded-lg shadow-lg text-xs font-black uppercase tracking-widest text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all shadow-indigo-950/40">
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Logic
        const appModal = document.getElementById('applicationModal');
        const closeAppModalBtn = document.getElementById('closeAppModal');

        function openApplicationModal(jobId) {
            document.getElementById('jobIdInput').value = jobId;
            document.getElementById('applicationForm').reset();
            appModal.classList.remove('hidden');
        }

        closeAppModalBtn.addEventListener('click', () => {
            appModal.classList.add('hidden');
        });

        // Close on click outside
        window.onclick = function (event) {
            if (event.target == appModal) {
                appModal.classList.add('hidden');
            }
            if (event.target == document.getElementById('viewModal')) {
                document.getElementById('viewModal').classList.add('hidden');
            }
        }

        // Handle Application Submit
        document.getElementById('applicationForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const fd = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerText;

            submitBtn.innerText = 'Submitting...';
            submitBtn.disabled = true;

            fetch('Job_posting.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        appModal.classList.add('hidden');
                    }
                })
                .catch(err => alert('Error submitting form'))
                .finally(() => {
                    submitBtn.innerText = originalText;
                    submitBtn.disabled = false;
                });
        });

        // View Job Details
        window.viewJob = function (id) {
            fetch(`Job_posting.php?action=get_job&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const job = data.data;
                        document.getElementById('jobDetails').innerHTML = `
                        <div class="space-y-6">
                            <div>
                                <h2 class="text-3xl font-black text-gray-900 uppercase tracking-tight">${job.title}</h2>
                                <p class="text-xs text-indigo-600 font-bold uppercase tracking-widest mt-1">${job.position}</p>
                            </div>
                            
                            <div class="flex flex-wrap gap-4 text-xs text-gray-500 border-b border-gray-100 pb-6 uppercase font-bold tracking-widest">
                                <div class="flex items-center gap-1.5 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">
                                    <i class="fas fa-map-marker-alt text-indigo-400"></i> ${job.location}
                                </div>
                                <div class="flex items-center gap-1.5 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">
                                    <i class="far fa-clock text-indigo-400"></i> Posted ${job.date_posted_formatted}
                                </div>
                                <div class="flex items-center gap-1.5 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">
                                    <i class="fas fa-globe text-indigo-400"></i> ${job.platform || 'N/A'}
                                </div>
                            </div>
 
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">About the Role</h4>
                                <div class="prose prose-invert text-gray-600 whitespace-pre-line leading-relaxed text-sm font-light text-justify">
                                    ${job.requirements}
                                </div>
                            </div>
 
                            <div class="pt-8 border-t border-gray-100 flex justify-end gap-3">
                                <button onclick="document.getElementById('viewModal').classList.add('hidden')" class="px-6 py-2.5 bg-gray-100 text-gray-600 font-bold text-xs uppercase tracking-widest rounded-lg hover:bg-gray-200 transition-colors">Close</button>
                                <button onclick="openApplicationModal(${job.id}); document.getElementById('viewModal').classList.add('hidden')" class="px-6 py-2.5 bg-indigo-600 text-white font-bold text-xs uppercase tracking-widest rounded-lg hover:bg-indigo-700 transition-all shadow-md">Apply for this Job</button>
                            </div>
                        </div>
                    `;
                        document.getElementById('viewModal').classList.remove('hidden');
                    }
                });
        }
    </script>
</body>

</html>