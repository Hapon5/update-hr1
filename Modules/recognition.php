<?php
session_start();

// Include database connection
// Include database connection
include("../Database/Connections.php");

// Check if user is logged in
if (!isset($_SESSION['Email'])) {
    header('Location: ../login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Handle recognition submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_recognition'])) {
    $from_employee_id = $_SESSION['LoginID'] ?? 1;
    $to_employee_id = $_POST['to_employee_id'];
    $category_id = $_POST['category_id'];
    $title = $_POST['title'];
    $message = $_POST['message'];

    // Validate inputs
    if (empty($to_employee_id) || empty($category_id) || empty($title) || empty($message)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO recognitions (from_employee_id, to_employee_id, category_id, title, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$from_employee_id, $to_employee_id, $category_id, $title, $message]);
            $success_message = "Recognition submitted successfully!";
        } catch (Exception $e) {
            $error_message = "Failed to submit recognition: " . $e->getMessage();
        }
    }
}

// Handle like/unlike functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_like') {
    $recognition_id = $_POST['recognition_id'];
    $employee_id = $_SESSION['LoginID'] ?? 1;

    try {
        // Check if already liked
        $stmt = $conn->prepare("SELECT id FROM recognition_likes WHERE recognition_id = ? AND employee_id = ?");
        $stmt->execute([$recognition_id, $employee_id]);

        if ($stmt->rowCount() > 0) {
            // Unlike
            $stmt = $conn->prepare("DELETE FROM recognition_likes WHERE recognition_id = ? AND employee_id = ?");
            $stmt->execute([$recognition_id, $employee_id]);
        } else {
            // Like
            $stmt = $conn->prepare("INSERT INTO recognition_likes (recognition_id, employee_id) VALUES (?, ?)");
            $stmt->execute([$recognition_id, $employee_id]);
        }

        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Fetch employees for dropdown
try {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $employees = $stmt->fetchAll();
} catch (Exception $e) {
    $employees = [];
    $error_message = "Failed to load employees: " . $e->getMessage();
}

// Fetch recognition categories
try {
    $stmt = $conn->prepare("SELECT * FROM recognition_categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    $error_message = "Failed to load categories: " . $e->getMessage();
}

// Fetch recent recognitions
try {
    // Check if tables exist first
    $tables_check = $conn->query("SHOW TABLES LIKE 'recognitions'");
    if ($tables_check->rowCount() == 0) {
        $recognitions = [];
        $error_message = "Recognition tables not found. Please run the database setup script first.";
    } else {
        $stmt = $conn->prepare("
            SELECT r.*, 
                   e1.name as from_name, e1.photo_path as from_photo,
                   e2.name as to_name, e2.photo_path as to_photo,
                   rc.name as category_name, rc.icon as category_icon, rc.color as category_color,
                   COUNT(rl.id) as like_count,
                   CASE WHEN EXISTS(SELECT 1 FROM recognition_likes WHERE recognition_id = r.id AND employee_id = ?) THEN 1 ELSE 0 END as is_liked
            FROM recognitions r
            JOIN employees e1 ON r.from_employee_id = e1.id
            JOIN employees e2 ON r.to_employee_id = e2.id
            JOIN recognition_categories rc ON r.category_id = rc.id
            LEFT JOIN recognition_likes rl ON r.id = rl.recognition_id
            WHERE r.is_public = 1
            GROUP BY r.id
            ORDER BY r.recognition_date DESC
            LIMIT 20
        ");
        $stmt->execute([$_SESSION['LoginID'] ?? 1]);
        $recognitions = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $recognitions = [];
    $error_message = "Failed to load recognitions: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Recognition - HR Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap");

        :root {
            --primary-color: #000;
            --secondary-color: #0a0a0a;
            --background-light: #f8f9fa;
            --background-card: #ffffff;
            --text-dark: #333;
            --text-light: #f4f4f4;
            --shadow-subtle: 0 4px 12px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            background-color: var(--background-light);
            min-height: 100vh;
            color: var(--text-dark);
        }

        /* Sidebar styles handled by component */

        /* Main Content */
        .main-content {
            margin-left: 16rem;
            /* Match w-64 of sidebar */
            min-height: 100vh;
            transition: all 0.3s ease;
            position: relative;
            padding: 110px 2.5rem 2.5rem;
            /* 110px top padding (70px header + 40px gap) */
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }

        /* Header */
        .dashboard-header {
            background: var(--background-card);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-subtle);
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            color: var(--text-dark);
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .dashboard-header p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Recognition Form */
        .recognition-form {
            background: var(--background-card);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-subtle);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .submit-button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .submit-button:hover {
            background: #b8650f;
        }

        .submit-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Recognition Cards */
        .recognitions-container {
            display: grid;
            gap: 20px;
        }

        .recognition-card {
            background: var(--background-card);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-subtle);
            border-left: 5px solid var(--primary-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .recognition-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .recognition-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .recognition-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid var(--primary-color);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .recognition-header:hover img {
            transform: scale(1.1);
        }

        .recognition-info h3 {
            color: var(--text-dark);
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .recognition-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .recognition-category {
            display: inline-flex;
            align-items: center;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .recognition-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .recognition-message {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .recognition-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        .recognition-date {
            color: #888;
            font-size: 0.9rem;
        }

        .recognition-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .like-button {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            border-radius: 20px;
            transition: background-color 0.3s ease;
        }

        .like-button:hover {
            background-color: #f8f9fa;
        }

        .like-button.liked {
            color: #e74c3c;
        }

        .like-button i {
            font-size: 16px;
        }

        .like-count {
            font-size: 0.9rem;
            color: #666;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #999;
        }

        /* Loading Spinner */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .notification-success {
            background: #28a745;
        }

        .notification-error {
            background: #dc3545;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 15px;
            }

            .sidebar-nav {
                display: none;
            }

            .sidebar-header {
                border-bottom: none;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .dashboard-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../Components/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../Components/header_admin.php'; ?>

        <header class="dashboard-header">

            <h1><i class="fas fa-trophy"></i> Employee Recognition</h1>
            <p>Recognize and celebrate outstanding employee contributions</p>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
        </header>

        <!-- Recognition Form -->
        <div class="recognition-form">
            <h2><i class="fas fa-plus-circle"></i> Give Recognition</h2>
            <form id="recognitionForm">
                <div class="form-group">
                    <label for="to_employee_id">Recognize:</label>
                    <select name="to_employee_id" id="to_employee_id" required>
                        <option value="">Select an employee...</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['name'] . ' - ' . $employee['position']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="category_id">Recognition Type:</label>
                    <select name="category_id" id="category_id" required>
                        <option value="">Select a category...</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" data-color="<?php echo $category['color']; ?>"
                                data-icon="<?php echo $category['icon']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Recognition Title:</label>
                    <input type="text" name="title" id="title" placeholder="Enter a title for this recognition..."
                        required>
                </div>

                <div class="form-group">
                    <label for="message">Recognition Message:</label>
                    <textarea name="message" id="message"
                        placeholder="Write a detailed message about their contribution..." required></textarea>
                </div>

                <button type="submit" class="submit-button" id="submitRecognition">
                    <i class="fas fa-paper-plane"></i> Submit Recognition
                </button>
            </form>
        </div>

        <!-- Recent Recognitions -->
        <div class="recognitions-container">
            <h2><i class="fas fa-history"></i> Recent Recognitions</h2>

            <?php if (empty($recognitions)): ?>
                <div class="empty-state">
                    <i class="fas fa-trophy"></i>
                    <h3>No Recognitions Yet</h3>
                    <p>Be the first to recognize someone's great work!</p>
                </div>
            <?php else: ?>
                <?php foreach ($recognitions as $recognition): ?>
                    <div class="recognition-card">
                        <div class="recognition-header">
                            <img src="../<?php echo htmlspecialchars($recognition['from_photo']); ?>" alt="From"
                                onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAiIGhlaWdodD0iNTAiIHZpZXdCb3g9IjAgMCA1MCA1MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjUiIGN5PSIyNSIgcj0iMjUiIGZpbGw9IiNGM0Y0RjYiLz4KPGNpcmNsZSBjeD0iMjUiIGN5PSIxNy41IiByPSI3LjUiIGZpbGw9IiM5Q0EzQUYiLz4KPHBhdGggZD0iTTEwIDQwQzEwIDMyLjgyMDMgMTUuODIwMyAyNyAyMyAyN0gyN0MzNC4xNzk3IDI3IDQwIDMyLjgyMDMgNDAgNDBWNTBIMTBWNDBaIiBmaWxsPSIjOUNBM0FGIi8+Cjwvc3ZnPgo='">
                            <div class="recognition-info">
                                <h3><?php echo htmlspecialchars($recognition['from_name']); ?></h3>
                                <p>recognized <strong><?php echo htmlspecialchars($recognition['to_name']); ?></strong></p>
                            </div>
                        </div>

                        <div class="recognition-category"
                            style="background-color: <?php echo $recognition['category_color']; ?>20; color: <?php echo $recognition['category_color']; ?>;">
                            <i class="<?php echo $recognition['category_icon']; ?>"></i>
                            <?php echo htmlspecialchars($recognition['category_name']); ?>
                        </div>

                        <div class="recognition-title"><?php echo htmlspecialchars($recognition['title']); ?></div>
                        <div class="recognition-message"><?php echo nl2br(htmlspecialchars($recognition['message'])); ?></div>

                        <div class="recognition-footer">
                            <div class="recognition-date">
                                <i class="fas fa-clock"></i>
                                <?php echo date('M j, Y g:i A', strtotime($recognition['recognition_date'])); ?>
                            </div>
                            <div class="recognition-actions">
                                <button class="like-button <?php echo $recognition['is_liked'] ? 'liked' : ''; ?>"
                                    data-recognition-id="<?php echo $recognition['id']; ?>">
                                    <i class="fas fa-heart"></i>
                                    <span class="like-count"><?php echo $recognition['like_count']; ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Sidebar toggle functionality handled by header_admin.php

        // Logout functionality
        document.getElementById("logout-link").addEventListener("click", function (e) {
            e.preventDefault();
            localStorage.clear();
            window.location.href = "../logout.php";
        });

        // AJAX form submission
        document.getElementById('recognitionForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('submit_recognition', '1');

            const submitBtn = document.getElementById('submitRecognition');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(response => response.text())
                .then(data => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;

                    showNotification('Recognition submitted successfully!', 'success');

                    // Reset form
                    document.getElementById('recognitionForm').reset();

                    // Reload page to show new recognition
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                })
                .catch(error => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;

                    showNotification('Error submitting recognition. Please try again.', 'error');
                    console.error('Error:', error);
                });
        });

        // Like functionality
        document.querySelectorAll('.like-button').forEach(button => {
            button.addEventListener('click', function () {
                const recognitionId = this.getAttribute('data-recognition-id');
                const formData = new FormData();
                formData.append('action', 'toggle_like');
                formData.append('recognition_id', recognitionId);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Toggle like state
                            this.classList.toggle('liked');

                            // Update like count
                            const likeCount = this.querySelector('.like-count');
                            const currentCount = parseInt(likeCount.textContent);
                            if (this.classList.contains('liked')) {
                                likeCount.textContent = currentCount + 1;
                            } else {
                                likeCount.textContent = currentCount - 1;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });

        // Notification system
        function showNotification(message, type) {
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => notification.remove());

            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Sidebar toggle and time handled by header_admin.php


    </script>
</body>

</html>