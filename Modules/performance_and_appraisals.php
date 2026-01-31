<?php
session_start();

// Use a relative path to go up one directory to the root 'hr1' folder
include("../Database/Connections.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['Email']) || (isset($_SESSION['Account_type']) && $_SESSION['Account_type'] !== '1')) {
    header("Location: ../login.php");
    exit();
}

// --- AJAX HANDLER: Get Employee Details and LATEST Appraisal ---
if (isset($_GET['action']) && $_GET['action'] === 'get_employee_appraisal' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    try {
        // Fetch employee details
        $stmt_emp = $conn->prepare("SELECT id, name, position, photo_path FROM employees WHERE id = ?");
        $stmt_emp->execute([$_GET['id']]);
        $employee = $stmt_emp->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
            exit();
        }

        // Fetch the MOST RECENT appraisal for this employee
        $stmt_app = $conn->prepare("SELECT id, rating, comment FROM appraisals WHERE employee_id = ? ORDER BY appraisal_date DESC LIMIT 1");
        $stmt_app->execute([$_GET['id']]);
        $appraisal = $stmt_app->fetch(PDO::FETCH_ASSOC);

        // Combine data
        $data = [
            'employee' => $employee,
            'appraisal' => $appraisal // This will be null if no appraisal exists
        ];

        echo json_encode(['status' => 'success', 'data' => $data]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}


// --- AJAX HANDLER: Add / Update / Delete Appraisals ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
        if ($action === 'submit_appraisal' || $action === 'update_appraisal') {
            $employee_id = $_POST['employee_id'] ?? null;
            $rating = $_POST['rating'] ?? null;
            $comment = trim($_POST['comment'] ?? '');
            $rater_email = $_SESSION['Email'];
            $appraisal_id = $_POST['appraisal_id'] ?? null;

            if (empty($employee_id) || empty($rating)) {
                echo json_encode(['status' => 'error', 'message' => 'Rating is required.']);
                exit();
            }

            if ($action === 'update_appraisal' && !empty($appraisal_id)) {
                $stmt = $conn->prepare("UPDATE appraisals SET rating = ?, comment = ?, appraisal_date = NOW() WHERE id = ?");
                $stmt->execute([$rating, $comment, $appraisal_id]);
                $message = 'Appraisal updated successfully!';
            } else {
                // Prevent duplicate appraisal for the same user on the same day by the same rater
                $checkStmt = $conn->prepare("SELECT id FROM appraisals WHERE employee_id = ? AND rater_email = ? AND DATE(appraisal_date) = CURDATE()");
                $checkStmt->execute([$employee_id, $rater_email]);
                if ($checkStmt->fetch()) {
                    echo json_encode(['status' => 'error', 'message' => 'An appraisal for this employee was already submitted today.']);
                    exit();
                }
                $stmt = $conn->prepare("INSERT INTO appraisals (employee_id, rater_email, rating, comment, appraisal_date) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$employee_id, $rater_email, $rating, $comment]);
                $message = 'Appraisal submitted successfully!';
            }
            echo json_encode(['status' => 'success', 'message' => $message]);

        } elseif ($action === 'delete_appraisal') {
            $appraisal_id = $_POST['appraisal_id'] ?? null;
            if (empty($appraisal_id)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid Appraisal ID.']);
                exit();
            }
            $stmt = $conn->prepare("DELETE FROM appraisals WHERE id = ?");
            $stmt->execute([$appraisal_id]);
            echo json_encode(['status' => 'success', 'message' => 'Appraisal deleted successfully!']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database operation failed: ' . $e->getMessage()]);
    }
    exit();
}


// --- INITIAL PAGE LOAD ---
try {
    // Corrected query to get the latest appraisal rating for each employee
    $stmt = $conn->query("
        SELECT e.*, a.rating as last_rating
        FROM employees e
        LEFT JOIN appraisals a ON a.id = (
            SELECT id FROM appraisals 
            WHERE employee_id = e.id 
            ORDER BY appraisal_date DESC 
            LIMIT 1
        )
        WHERE e.status = 'active'
        ORDER BY e.name
    ");
    $employees = $stmt->fetchAll();
} catch (Exception $e) {
    $employees = [];
    $error_message = "Failed to load employees: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance & Appraisals - HR Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary-color: #111827;
            /* Black */
            --background-dark: #111827;
            /* Black */
            --card-bg: #1f2937;
            /* Dark Gray */
            --text-light: #f3f4f6;
            --text-medium: #9ca3af;
            --border-color: #374151;
        }

        body {
            background-color: var(--background-dark);
            display: flex;
            font-family: "Poppins", sans-serif;
        }

        /* Sidebar styles removed - using component */

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
        }

        .star-rating input[type="radio"] {
            display: none;
        }

        .star-rating label {
            font-size: 2rem;
            color: #4b5563;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star-rating input[type="radio"]:checked~label,
        .star-rating label:hover,
        .star-rating label:hover~label {
            color: #f59e0b;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            color: white;
            z-index: 1000;
            transition: all 0.5s ease;
            opacity: 0;
            transform: translateX(100%);
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }
    </style>
</head>

<body class="bg-gray-900">
    <?php
    $root_path = '../';
    include '../Components/sidebar_admin.php';
    include '../Components/header_admin.php';
    ?>

    <div class="main-content min-h-screen pt-24 pb-8 px-4 sm:px-8 ml-64 transition-all duration-300">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-200">Performance & Appraisals</h1>
            <div id="datetime" class="text-gray-400 font-medium"></div>
        </div>
        <p class="text-gray-400 mb-8">Review and rate employee performance</p>

        <div class="bg-gray-800 rounded-lg shadow-sm overflow-hidden border border-gray-700 flex-grow">
            <div class="overflow-x-auto h-full">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Position</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase">Last Rating
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-800 divide-y divide-gray-700">
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-10 text-gray-500">No active employees found.</td>
                            </tr>
                        <?php else:
                            foreach ($employees as $employee): ?>
                                <tr class="hover:bg-gray-700/50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <?php
                                            $photo = $employee['photo_path'];
                                            if (empty($photo)) {
                                                $photo = 'Profile/default.png';
                                            } else {
                                                // Fix case sensitivity: replace lowercase 'profile/' with 'Profile/'
                                                // This ensures it works on Linux servers where "Profile" != "profile"
                                                $photo = str_ireplace('profile/', 'Profile/', $photo);

                                                // Double check: if it doesn't start with Profile/, prepend it (handling bare filenames)
                                                if (strpos($photo, 'Profile/') !== 0) {
                                                    $photo = 'Profile/' . $photo;
                                                }
                                            }
                                            // Ensure we point to parent dir
                                            $photoUrl = "../" . htmlspecialchars($photo);
                                            ?>
                                            <img class="h-10 w-10 rounded-full object-cover border-2 border-gray-600"
                                                src="<?= $photoUrl ?>?v=<?= time() ?>"
                                                onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($employee['name']) ?>&background=random&color=fff';"
                                                alt="<?= htmlspecialchars($employee['name']) ?>">
                                            <div class="ml-4 font-medium text-gray-200">
                                                <?= htmlspecialchars($employee['name']) ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-300"><?= htmlspecialchars($employee['position']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-center text-yellow-400 text-lg">
                                        <?= $employee['last_rating'] ? str_repeat('★', $employee['last_rating']) . str_repeat('☆', 5 - $employee['last_rating']) : '<span class="text-gray-500 text-sm">Not Rated</span>' ?>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <button onclick="openRateModal(<?= $employee['id'] ?>)"
                                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-500">Rate /
                                            View</button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="rateModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gray-800 rounded-lg shadow-xl max-w-lg w-full border border-gray-700">
                <div class="flex items-center justify-between p-5 border-b border-gray-700">
                    <h3 id="modalTitle" class="text-xl font-semibold text-gray-200">Rate Performance</h3>
                    <button id="closeModalBtn" class="text-gray-400 hover:text-white">&times;</button>
                </div>
                <form id="appraisalForm" class="p-6">
                    <input type="hidden" name="employee_id" id="modalEmployeeId">
                    <input type="hidden" name="appraisal_id" id="modalAppraisalId">

                    <div class="text-center mb-6">
                        <img id="modalPhoto"
                            class="h-24 w-24 rounded-full object-cover mx-auto mb-4 border-4 border-gray-600" src=""
                            alt="Employee">
                        <h4 id="modalName" class="font-bold text-lg text-gray-200"></h4>
                        <p id="modalPosition" class="text-gray-400"></p>
                    </div>

                    <div class="mb-4">
                        <div class="star-rating">
                            <input type="radio" name="rating" value="5" id="star5"><label for="star5">★</label>
                            <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
                            <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
                            <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
                            <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
                        </div>
                    </div>

                    <div class="mb-6">
                        <textarea name="comment" id="comment" rows="4"
                            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-200 placeholder-gray-500 focus:ring-yellow-500 focus:border-yellow-500"
                            placeholder="Provide feedback..."></textarea>
                    </div>

                    <div class="flex justify-between items-center pt-5 border-t border-gray-700">
                        <div>
                            <button type="button" id="deleteBtn"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 hidden">Delete</button>
                        </div>
                        <div class="space-x-3">
                            <button type="button" id="cancelBtn"
                                class="px-5 py-2 text-gray-200 bg-gray-600 rounded-lg hover:bg-gray-500">Cancel</button>
                            <button type="submit" id="submitBtn"
                                class="px-5 py-2 text-white rounded-lg transition-colors">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('rateModal');
            const form = document.getElementById('appraisalForm');
            const closeModalBtn = document.getElementById('closeModalBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const deleteBtn = document.getElementById('deleteBtn');
            const submitBtn = document.getElementById('submitBtn');

            const openModal = () => modal.classList.remove('hidden');
            const closeModal = () => modal.classList.add('hidden');

            closeModalBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);

            // --- Live Date and Time Script ---
            const datetimeElement = document.getElementById('datetime');
            function updateDateTime() {
                const now = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                datetimeElement.textContent = now.toLocaleString('en-US', options);
            }
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // --- Notification Function ---
            function showNotification(message, type = 'success') {
                const notif = document.createElement('div');
                notif.textContent = message;
                notif.className = `notification ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
                document.body.appendChild(notif);
                setTimeout(() => {
                    notif.classList.add('show');
                    setTimeout(() => {
                        notif.classList.remove('show');
                        setTimeout(() => notif.remove(), 500);
                    }, 3000);
                }, 10);
            }

            window.openRateModal = function (employeeId) {
                fetch(`?action=get_employee_appraisal&id=${employeeId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const emp = data.data.employee;
                            const appraisal = data.data.appraisal;

                            form.reset();
                            document.getElementById('modalEmployeeId').value = emp.id;
                            document.getElementById('modalName').textContent = emp.name;
                            document.getElementById('modalPosition').textContent = emp.position;
                            let photoPath = emp.photo_path || '';

                            // 1. Force lowercase 'profile/' to 'Profile/' (fixing case sensitivity)
                            photoPath = photoPath.replace(/^profile\//i, 'Profile/');

                            // 2. If it is just a filename (e.g. "Viloria.jpeg"), prepend 'Profile/'
                            if (photoPath && !photoPath.startsWith('Profile/')) {
                                photoPath = 'Profile/' + photoPath;
                            }

                            // 3. Fallback if empty
                            if (!photoPath) photoPath = 'Profile/default.png';

                            document.getElementById('modalPhoto').src = `../${photoPath}`;
                            // Add error handler for modal image dynamically or ensure it exists
                            document.getElementById('modalPhoto').onerror = function () {
                                this.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(emp.name)}&background=random&color=fff`;
                            };
                            document.getElementById('modalAppraisalId').value = '';

                            submitBtn.className = "px-5 py-2 text-white rounded-lg transition-colors"; // Reset classes

                            if (appraisal) { // If appraisal exists, populate form
                                document.getElementById('modalAppraisalId').value = appraisal.id;
                                document.getElementById('comment').value = appraisal.comment;
                                const starInput = form.querySelector(`input[name="rating"][value="${appraisal.rating}"]`);
                                if (starInput) starInput.checked = true;
                                submitBtn.textContent = 'Update';
                                submitBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                                deleteBtn.classList.remove('hidden');
                            } else { // No appraisal yet
                                submitBtn.textContent = 'Submit';
                                submitBtn.classList.add('bg-gray-600', 'hover:bg-gray-500');
                                deleteBtn.classList.add('hidden');
                            }
                            openModal();
                        } else {
                            showNotification(data.message, 'error');
                        }
                    });
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const rating = form.rating.value;
                if (!rating) {
                    showNotification('Please select a star rating.', 'error');
                    return;
                }

                const action = form.appraisal_id.value ? 'update_appraisal' : 'submit_appraisal';
                const formData = new FormData(this);
                formData.append('action', action);

                fetch('', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        showNotification(data.message, data.status);
                        if (data.status === 'success') {
                            setTimeout(() => window.location.reload(), 1500);
                        }
                    });
            });

            deleteBtn.addEventListener('click', function () {
                const appraisalId = document.getElementById('modalAppraisalId').value;
                if (!appraisalId || !confirm('Are you sure you want to delete this appraisal?')) {
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'delete_appraisal');
                formData.append('appraisal_id', appraisalId);

                fetch('', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        showNotification(data.message, data.status);
                        if (data.status === 'success') {
                            setTimeout(() => window.location.reload(), 1500);
                        }
                    });
            });
        });
    </script>
</body>

</html>