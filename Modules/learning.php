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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_name = htmlspecialchars($_POST['employee']);
    $incident_details = htmlspecialchars($_POST['incident']);
    $incident_type = htmlspecialchars($_POST['incident_type']);
    $severity = htmlspecialchars($_POST['severity']);
    $location = htmlspecialchars($_POST['location']);
    $reported_by = $_SESSION['Email'] ?? 'Unknown';

    try {
        $stmt = $conn->prepare("INSERT INTO safety_incidents (employee_name, incident_details, incident_type, severity, location, reported_by, incident_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$employee_name, $incident_details, $incident_type, $severity, $location, $reported_by]);
        $success_message = "Safety incident report submitted successfully!";
    } catch (Exception $e) {
        $error_message = "Failed to submit incident report: " . $e->getMessage();
    }
}

// Fetch recent incidents
try {
    // Check if safety_incidents table exists
    $tables_check = $conn->query("SHOW TABLES LIKE 'safety_incidents'");
    if ($tables_check->rowCount() == 0) {
        $incidents = [];
        $error_message = "Safety database table not found. Please run the database setup script first.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM safety_incidents ORDER BY incident_date DESC LIMIT 10");
        $stmt->execute();
        $incidents = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $incidents = [];
    $error_message = "Failed to load incidents: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safety & Compliance - HR Admin</title>
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
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --success-color: #28a745;
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
            padding: 40px;
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

        /* Incident Report Form */
        .incident-form {
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
            min-height: 120px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .submit-button {
            background: #000;
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
            background: #c82333;
        }

        /* --- NEW STYLES START HERE --- */
        /* Safety Committee Members Section */
        .team-container {
            background: var(--background-card);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-subtle);
            margin-bottom: 30px;
        }

        .team-container h2 {
            color: var(--text-dark);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .member-card {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .member-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .member-card img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid var(--primary-color);
        }

        .member-card h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .member-card p {
            color: #666;
            font-size: 0.9rem;
        }

        /* Safety Guidelines Section */
        .safety-guidelines {
            background: var(--background-card);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-subtle);
            margin-bottom: 30px;
        }

        .safety-guidelines h2 {
            color: var(--text-dark);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .safety-guidelines ol {
            margin-top: 20px;
            padding-left: 25px;
            list-style-type: decimal;
        }

        .safety-guidelines li {
            margin-bottom: 12px;
            line-height: 1.7;
            color: #555;
        }

        /* --- NEW STYLES END HERE --- */

        /* Incident History */
        .incidents-container {
            background: var(--background-card);
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-subtle);
        }

        .incidents-container h3 {
            color: var(--text-dark);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .incident-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid var(--danger-color);
            transition: transform 0.3s ease;
        }

        .incident-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .incident-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .incident-employee {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        .incident-date {
            color: #666;
            font-size: 0.9rem;
        }

        .incident-details {
            color: #555;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .incident-meta {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .incident-type,
        .incident-severity,
        .incident-location {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .incident-type {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .incident-severity.high {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .incident-severity.medium {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .incident-severity.low {
            background-color: #e8f5e8;
            color: #388e3c;
        }

        .incident-location {
            background-color: #f3e5f5;
            color: #7b1fa2;
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

        @media (max-width: 768px) {

            .form-row {
                grid-template-columns: 1fr;
            }

            .members-grid {
                grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            }
        }
    </style>
</head>

<body>
    <?php include '../Components/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../Components/header_admin.php'; ?>

        <header class="dashboard-header">

            <h1><i class="fas fa-shield-alt"></i> Safety & Compliance</h1>
            <p>Report and track workplace safety incidents to ensure employee well-being</p>

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

        <div class="incident-form">
            <h2><i class="fas fa-plus-circle"></i> Report Safety Incident</h2>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="employee">Employee Name:</label>
                        <input type="text" id="employee" name="employee" required placeholder="Enter employee name">
                    </div>
                    <div class="form-group">
                        <label for="incident_type">Incident Type:</label>
                        <select id="incident_type" name="incident_type" required>
                            <option value="">Select incident type...</option>
                            <option value="injury">Workplace Injury</option>
                            <option value="near_miss">Near Miss</option>
                            <option value="equipment_failure">Equipment Failure</option>
                            <option value="safety_violation">Safety Violation</option>
                            <option value="environmental">Environmental Hazard</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="severity">Severity Level:</label>
                        <select id="severity" name="severity" required>
                            <option value="">Select severity...</option>
                            <option value="low">Low - Minor incident</option>
                            <option value="medium">Medium - Moderate impact</option>
                            <option value="high">High - Serious incident</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" required placeholder="Enter incident location">
                    </div>
                </div>

                <div class="form-group">
                    <label for="incident">Incident Details:</label>
                    <textarea id="incident" name="incident" required
                        placeholder="Provide detailed description of the incident, including what happened, when, and any immediate actions taken..."></textarea>
                </div>

                <button type="submit" class="submit-button">
                    <i class="fas fa-paper-plane"></i> Submit Incident Report
                </button>
            </form>
        </div>

        <div class="safety-guidelines">
            <h2><i class="fas fa-list-check"></i> Workplace Safety Guidelines</h2>
            <ol>
                <li>Report all accidents, injuries, and unsafe conditions to your supervisor.</li>
                <li>Never operate any equipment unless you are trained and authorized.</li>
                <li>Use the right tool for the job.</li>
                <li>Always wear required Personal Protective Equipment (PPE).</li>
                <li>Keep your work area clean and organized.</li>
                <li>Do not engage in horseplay or practical jokes.</li>
                <li>Know the location of fire extinguishers and first aid kits.</li>
                <li>Obey all safety signs and warnings.</li>
                <li>Lift properly: use your legs, not your back.</li>
                <li>Be aware of your surroundings and the actions of others.</li>
            </ol>
        </div>

        <div class="incidents-container">
            <h3><i class="fas fa-history"></i> Recent Safety Incidents</h3>

            <?php if (empty($incidents)): ?>
                <div class="empty-state">
                    <i class="fas fa-shield-alt"></i>
                    <h3>No Incidents Reported</h3>
                    <p>Great job maintaining a safe workplace!</p>
                </div>
            <?php else: ?>
                <?php foreach ($incidents as $incident): ?>
                    <div class="incident-item">
                        <div class="incident-header">
                            <div class="incident-employee"><?php echo htmlspecialchars($incident['employee_name']); ?></div>
                            <div class="incident-date">
                                <?php echo date('M j, Y g:i A', strtotime($incident['incident_date'])); ?>
                            </div>
                        </div>

                        <div class="incident-details">
                            <?php echo nl2br(htmlspecialchars($incident['incident_details'])); ?>
                        </div>

                        <div class="incident-meta">
                            <span
                                class="incident-type"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $incident['incident_type']))); ?></span>
                            <span
                                class="incident-severity <?php echo $incident['severity']; ?>"><?php echo ucfirst($incident['severity']); ?>
                                Severity</span>
                            <span class="incident-location"><?php echo htmlspecialchars($incident['location']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Sidebar toggle and time handled by header_admin.php

        // Logout functionality
        document.getElementById("logout-link").addEventListener("click", function (e) {
            e.preventDefault();
            localStorage.clear();
            window.location.href = "../logout.php";
        });

    </script>
</body>

</html>