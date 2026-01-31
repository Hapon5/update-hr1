<?php
session_start();
require_once "../Database/Connections.php";

// Require admin
// Require login
if (!isset($_SESSION['Email'])) {
    header('Location: ../login.php');
    exit();
}
$admin_email = $_SESSION['Email'];

// --- AJAX HANDLER para sa pag-fetch ng data ---
if (isset($_GET['action']) && $_GET['action'] == 'get_interview' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $conn->prepare("SELECT * FROM interviews WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $interview = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($interview) {
            // Format dates for HTML datetime-local input
            $interview['start_time_formatted'] = date('Y-m-d\TH:i', strtotime($interview['start_time']));
            $interview['end_time_formatted'] = date('Y-m-d\TH:i', strtotime($interview['end_time']));
            echo json_encode(['status' => 'success', 'data' => $interview]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Interview not found.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit(); // Stop script execution
}

// Handle form submissions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
            $fields = [
                trim($_POST['candidate_name']), trim($_POST['email']), trim($_POST['position']),
                trim($_POST['interviewer']), $_POST['start_time'], $_POST['end_time'],
                trim($_POST['location']), $_POST['status'] ?? 'scheduled', $_POST['notes'] ?? ''
            ];
            if ($_POST['action'] === 'add') {
                $sql = 'INSERT INTO interviews (candidate_name, email, position, interviewer, start_time, end_time, location, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
                $message = 'Interview scheduled successfully';
            } else {
                $sql = 'UPDATE interviews SET candidate_name=?, email=?, position=?, interviewer=?, start_time=?, end_time=?, location=?, status=?, notes=? WHERE id=?';
                $fields[] = $_POST['id'];
                $message = 'Interview updated successfully';
            }
            $stmt = $conn->prepare($sql);
            $stmt->execute($fields);
            echo json_encode(['status' => 'success', 'message' => $message]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit(); // Stop script execution
}

// Handle Delete (full page reload is fine here)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM interviews WHERE id=?");
        $stmt->execute([$_POST['id']]);
        $_SESSION['message'] = "Interview deleted successfully!";
    } catch(Exception $e) {
        $_SESSION['error'] = "Failed to delete interview.";
    }
    header("Location: Interviewschedule.php");
    exit();
}


// Fetch all interviews for page load
try {
    $filter = isset($_GET['status']) ? $_GET['status'] : '';
    $q = 'SELECT * FROM interviews';
    $params = [];
    if ($filter !== '' && $filter !== 'all') {
        $q .= ' WHERE status = ?';
        $params[] = $filter;
    }
    $q .= ' ORDER BY start_time DESC';
    $stmt = $conn->prepare($q);
    $stmt->execute($params);
    $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $interviews = [];
    $error = 'Failed to load interviews: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Interview Schedule - HR1</title>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Poppins','ui-sans-serif','system-ui'] }, colors: { brand: {500:'#d37a15',600:'#b8650f'} } } }
        }
    </script>
    <style>
        :root { 
            --primary-color: #000; 
            --secondary-color: #0a0a0a; 
            --background-light: #f8f9fa; 
            --text-light: #f4f4f4; 
            --text-dark: #333; 
        }
        body { background-color: var(--background-light); display: flex; min-height: 100vh; font-family: "Poppins", sans-serif; }
        .logout-item { margin-top: auto; }
        .main-content { margin-left: 260px; flex-grow: 1; padding: 20px 30px; transition: margin-left 0.3s ease; }
        .menu-toggle { font-size: 1.5rem; cursor: pointer; color: #333; }
    </style>
  </head>
<body class="bg-gray-50 font-sans">
    <!-- Sidebar -->
    <!-- Sidebar -->
    <?php include '../Components/sidebar_admin.php'; ?>


    <div class="main-content">
        <?php include '../Components/header_admin.php'; ?>
        <div class="mb-8 flex justify-between items-center">
             <h1 class="text-3xl font-bold text-gray-800">Interview Scheduling</h1>
        </div>

        <div class="p-0">
            <!-- Action Bar -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <button id="scheduleBtn" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Schedule Interview
                    </button>
                    <!-- Filter... -->
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <!-- Table Head -->
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Candidate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Interviewer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <!-- Table Body -->
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($interviews)): ?>
                                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No interviews scheduled.</td></tr>
                            <?php else: foreach ($interviews as $iv): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($iv['candidate_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($iv['position']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($iv['interviewer']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= date('M d, Y g:i A', strtotime($iv['start_time'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                         <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?= $iv['status']==='scheduled'?'bg-blue-100 text-blue-800':($iv['status']==='completed'?'bg-green-100 text-green-800':($iv['status']==='cancelled'?'bg-red-100 text-red-800':'bg-yellow-100 text-yellow-800')); ?>">
                                            <?= ucfirst(str_replace('_',' ',$iv['status'])); ?>
                                         </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex gap-2">
                                            <button class="text-brand-500 hover:text-brand-600" onclick="openEditModal(<?= (int)$iv['id']; ?>)"><i class="fas fa-edit"></i></button>
                                            <button class="text-red-500 hover:text-red-600" onclick="confirmDelete(<?= (int)$iv['id']; ?>)"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="interviewModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 ">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="flex items-center justify-between p-5 border-b">
                <h3 id="modalTitle" class="text-lg text-gray-800 font-semibold">Schedule Interview</h3>
                <button id="closeModalBtn" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form id="interviewForm" method="POST" class="p-6" ">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="interviewId" value="">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Candidate Name*</label>
                        <input type="text" name="candidate_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Candidate Email*</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Position*</label>
                        <input type="text" name="position" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Interviewer*</label>
                        <input type="text" name="interviewer" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Time*</label>
                        <input type="datetime-local" name="start_time" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Time*</label>
                        <input type="datetime-local" name="end_time" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                 <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location*</label>
                    <input type="text" name="location" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelBtn" class="px-4 py-2 text-white-700 bg-gray-200 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Hidden Delete Form -->
    <form id="deleteForm" method="POST" class="hidden">
        <input type="hidden" name="action_delete" value="1">
        <input type="hidden" name="id" id="deleteId">
    </form>
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('interviewModal');
            const form = document.getElementById('interviewForm');
            const scheduleBtn = document.getElementById('scheduleBtn');
            const closeModalBtn = document.getElementById('closeModalBtn');
            const cancelBtn = document.getElementById('cancelBtn');

            const openModal = () => modal.classList.remove('hidden');
            const closeModal = () => modal.classList.add('hidden');

            scheduleBtn.addEventListener('click', () => {
                form.reset();
                document.getElementById('modalTitle').textContent = 'Schedule New Interview';
                document.getElementById('formAction').value = 'add';
                document.getElementById('interviewId').value = '';
                openModal();
            });

            closeModalBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('Interviewschedule.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                }).catch(err => console.error(err));
            });
        });

        function openEditModal(id) {
            fetch(`Interviewschedule.php?action=get_interview&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const iv = data.data;
                    const form = document.getElementById('interviewForm');
                    form.querySelector('[name=candidate_name]').value = iv.candidate_name;
                    form.querySelector('[name=email]').value = iv.email;
                    form.querySelector('[name=position]').value = iv.position;
                    form.querySelector('[name=interviewer]').value = iv.interviewer;
                    form.querySelector('[name=start_time]').value = iv.start_time_formatted;
                    form.querySelector('[name=end_time]').value = iv.end_time_formatted;
                    form.querySelector('[name=location]').value = iv.location;
                    
                    document.getElementById('modalTitle').textContent = 'Edit Interview Schedule';
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('interviewId').value = id;
                    document.getElementById('interviewModal').classList.remove('hidden');
                } else {
                    alert('Error fetching data: ' + data.message);
                }
            });
        }
        
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this interview?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

    function updateDateTime() {
        const now = new Date();
        const options = {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        };
        document.getElementById("liveDateTime").textContent =
            now.toLocaleDateString("en-US", options);
    }

    // run immediately + update every second
    updateDateTime();
    setInterval(updateDateTime, 1000);

    </script>
  </body>
</html>
