<?php
include("./Database/Connections.php");

// Fetch active jobs
try {
    $stmt = $conn->prepare("SELECT * FROM job_postings WHERE status = 'active' ORDER BY created_at DESC");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $jobs = [];
}

// Handle application submission (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply') {
    header('Content-Type: application/json');
    try {
        $job_id = (int)$_POST['job_id'];
        $name = htmlspecialchars($_POST['name']);
        $email = htmlspecialchars($_POST['email']);
        $phone = htmlspecialchars($_POST['phone']);
        
        $resume_path = null;
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/resumes/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName = time() . '_' . basename($_FILES['resume']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
                $resume_path = 'uploads/resumes/' . $fileName;
            }
        }

        $stmt = $conn->prepare("INSERT INTO applications (job_id, applicant_name, email, phone, resume_path, application_type, status) VALUES (?, ?, ?, ?, ?, 'Online', 'Pending')");
        $stmt->execute([$job_id, $name, $email, $phone, $resume_path]);

        echo json_encode(['success' => true, 'message' => 'Application submitted successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers | Crane Cali</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .gradient-text {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-gradient {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.1), transparent),
                        radial-gradient(circle at bottom left, rgba(168, 85, 247, 0.05), transparent);
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen hero-gradient">
    <!-- Navbar -->
    <nav class="sticky top-0 z-40 w-full glass-card border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200">
                        <i class="fas fa-crane text-white"></i>
                    </div>
                    <span class="text-xl font-extrabold tracking-tight text-slate-800">CRANE <span class="text-indigo-600">CALI</span></span>
                </div>
                <div class="flex items-center gap-6">
                    <a href="landing.php" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 transition-colors">Home</a>
                    <a href="#" class="text-sm font-semibold text-indigo-600 border-b-2 border-indigo-600 pb-1">Careers</a>
                    <a href="login.php" class="px-5 py-2.5 bg-slate-900 text-white text-sm font-bold rounded-xl hover:bg-slate-800 transition-all">Sign In</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="pt-20 pb-16 px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-5xl md:text-6xl font-black text-slate-900 mb-6 tracking-tight">
                Build the future of <span class="gradient-text">Logistics</span>
            </h1>
            <p class="text-xl text-slate-600 mb-10 leading-relaxed max-w-2xl mx-auto">
                Join our crew of innovators and help us redefine crane and trucking management systems worldwide.
            </p>
            
            <div class="flex flex-col md:flex-row gap-3 p-2 bg-white rounded-2xl shadow-xl shadow-indigo-100 max-w-2xl mx-auto border border-indigo-50">
                <div class="flex-grow flex items-center px-4 gap-3">
                    <i class="fas fa-search text-slate-400"></i>
                    <input type="text" placeholder="Search roles (e.g. Developer, HR, Driver)" 
                        class="w-full py-3 bg-transparent outline-none text-slate-700 font-medium">
                </div>
                <button class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-all">
                    Find Jobs
                </button>
            </div>
        </div>
    </header>

    <!-- Job Listings -->
    <main class="max-w-5xl mx-auto px-4 pb-24">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-bold text-slate-800">Latest Openings</h2>
            <span class="text-sm font-semibold text-slate-400 uppercase tracking-widest"><?php echo count($jobs); ?> Positions</span>
        </div>

        <div class="grid gap-6">
            <?php foreach ($jobs as $job): ?>
            <div class="group bg-white p-6 rounded-3xl border border-slate-100 hover:border-indigo-200 hover:shadow-2xl hover:shadow-indigo-100/50 transition-all duration-300">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex items-start gap-5">
                        <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">
                            <i class="fas fa-briefcase text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 mb-1 group-hover:text-indigo-600 transition-colors"><?php echo htmlspecialchars($job['title']); ?></h3>
                            <div class="flex flex-wrap gap-4 text-sm font-medium text-slate-500">
                                <span class="flex items-center gap-1.5"><i class="fas fa-map-marker-alt text-slate-300"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                <span class="flex items-center gap-1.5"><i class="fas fa-clock text-slate-300"></i> Full-time</span>
                                <span class="flex items-center gap-1.5"><i class="fas fa-layer-group text-slate-300"></i> <?php echo htmlspecialchars($job['position']); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 shrink-0">
                        <button onclick='openJobDetails(<?php echo json_encode($job); ?>)'
                            class="px-6 py-3 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-all">
                            View Details
                        </button>
                        <button onclick='openApplyForm(<?php echo json_encode($job); ?>)'
                            class="px-8 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all">
                            Apply Now
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($jobs)): ?>
            <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-slate-300">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300 text-2xl">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">No active openings right now</h3>
                <p class="text-slate-500">Check back later or follow us for updates!</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Job Details Modal -->
    <div id="jobModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto transform transition-all scale-100">
            <div class="relative">
                <div class="h-32 bg-indigo-600"></div>
                <button onclick="closeModal('jobModal')" class="absolute top-4 right-4 w-10 h-10 bg-white/20 hover:bg-white/40 text-white rounded-full flex items-center justify-center backdrop-blur-md transition-all">
                    <i class="fas fa-times"></i>
                </button>
                <div class="absolute -bottom-10 left-8 p-4 bg-white rounded-3xl shadow-xl border border-slate-100">
                    <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600">
                        <i class="fas fa-briefcase text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="px-8 pt-16 pb-8">
                <h3 id="modalTitle" class="text-3xl font-black text-slate-900 mb-2"></h3>
                <div class="flex flex-wrap gap-4 text-sm font-bold text-indigo-600 mb-8 uppercase tracking-wider">
                    <span id="modalLocation"></span> • <span id="modalPosition"></span>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <h4 class="text-lg font-bold text-slate-800 mb-3">About the role</h4>
                        <div id="modalRequirements" class="text-slate-600 leading-relaxed whitespace-pre-line"></div>
                    </div>
                    
                    <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                        <h4 class="text-slate-800 font-bold mb-4">Ready to join us?</h4>
                        <button id="applyNowBtn" class="w-full py-4 bg-indigo-600 text-white font-black rounded-2xl hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition-all uppercase tracking-widest">
                            Apply for this position
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Form Modal -->
    <div id="applyModal" class="hidden fixed inset-0 z-[110] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="p-8 border-b border-slate-100">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900">Application</h3>
                        <p id="applyJobTitle" class="text-indigo-600 font-bold text-sm uppercase tracking-wider">Software Engineer</p>
                    </div>
                    <button onclick="closeModal('applyModal')" class="text-slate-400 hover:text-slate-600 text-xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <form id="applyForm" class="p-8 space-y-5">
                <input type="hidden" name="action" value="apply">
                <input type="hidden" name="job_id" id="applyJobId">
                
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Your Full Name</label>
                    <input type="text" name="name" required placeholder="John Doe"
                        class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-medium">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Email</label>
                        <input type="email" name="email" required placeholder="john@example.com"
                            class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Phone</label>
                        <input type="tel" name="phone" required placeholder="09123456789"
                            class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all font-medium">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Upload Resume (PDF/DOC)</label>
                    <div class="relative group">
                        <input type="file" name="resume" required accept=".pdf,.doc,.docx"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div class="w-full py-8 border-2 border-dashed border-slate-200 rounded-2xl flex flex-col items-center justify-center group-hover:bg-slate-50 transition-all group-hover:border-indigo-300">
                            <i class="fas fa-cloud-upload-alt text-slate-300 text-3xl mb-2 group-hover:text-indigo-400 transition-colors"></i>
                            <span class="text-sm font-bold text-slate-500">Tap to upload your file</span>
                        </div>
                    </div>
                </div>
                
                <button type="submit" id="submitBtn" class="w-full py-4 bg-indigo-600 text-white font-black rounded-2xl hover:bg-indigo-700 shadow-xl shadow-indigo-100 transition-all uppercase tracking-widest mt-4">
                    Send My Application
                </button>
            </form>
        </div>
    </div>

    <script>
        let currentJob = null;
        const jobs = <?php echo json_encode($jobs); ?>;

        // Auto-open job if ID in URL
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const jobId = urlParams.get('job_id');
            if (jobId) {
                const job = jobs.find(j => j.id == jobId);
                if (job) openJobDetails(job);
            }
        });

        function openJobDetails(job) {
            currentJob = job;
            document.getElementById('modalTitle').textContent = job.title;
            document.getElementById('modalLocation').textContent = job.location;
            document.getElementById('modalPosition').textContent = job.position;
            document.getElementById('modalRequirements').textContent = job.requirements || 'No detailed requirements provided.';
            
            document.getElementById('jobModal').classList.remove('hidden');
            document.getElementById('applyNowBtn').onclick = () => openApplyForm(job);
        }

        function openApplyForm(job) {
            document.getElementById('jobModal').classList.add('hidden');
            document.getElementById('applyJobId').value = job.id;
            document.getElementById('applyJobTitle').textContent = job.title;
            document.getElementById('applyModal').classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        document.getElementById('applyForm').onsubmit = async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.textContent;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
            btn.disabled = true;

            const formData = new FormData(e.target);
            try {
                const res = await fetch('careers.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert('✅ ' + data.message);
                    closeModal('applyModal');
                    e.target.reset();
                } else {
                    alert('❌ Error: ' + data.message);
                }
            } catch (err) {
                alert('An error occurred. Please try again.');
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        };

        // Close on outside click
        window.onclick = (e) => {
            if (e.target.id === 'jobModal' || e.target.id === 'applyModal') closeModal(e.target.id);
        };
    </script>
</body>
</html>
