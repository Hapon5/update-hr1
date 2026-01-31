<?php
session_start();
if (!isset($_SESSION['Email']) || $_SESSION['Account_type'] !== '1') {
    header("Location: login.php");
    exit();
}
$admin_email = $_SESSION['Email'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HR1 - Interview Scheduling</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap");

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        :root {
            --primary-color: #1c1c1c;
            /* Main black color */
            --secondary-color: #2a2a2a;
            /* Slightly lighter black for accents */
            --hover-color: rgba(255, 255, 255, 0.1);
            --text-color: #fff;
        }

        body {
            background: #e7f2fd;
        }

        .sidebar {
            position: fixed;
            height: 100%;
            width: 260px;
            background: var(--primary-color);
            padding: 15px;
            z-index: 99;
        }

        #h2 {
            color: var(--text-color);
            margin-top: 20px;
            margin-right: 5px;
            margin-left: 10px;
            display: flex;
            align-items: center;
        }

        .sidebar a {
            color: var(--text-color);
            text-decoration: none;
        }

        .menu-content {
            position: relative;
            height: 80%;
            width: 100%;
            margin-top: 40px;
            overflow-y: scroll;
        }

        .menu-content::-webkit-scrollbar {
            display: none;
        }

        .menu-items {
            height: 100%;
            width: 100%;
            list-style: none;
            transition: all 0.4s ease;
        }

        .submenu-active .menu-items {
            transform: translateX(-56%);
        }

        .menu-title {
            color: whitesmoke;
            font-size: 18px;
            padding: 15px 20px;
        }

        .item a,
        .submenu-item {
            padding: 20px;
            display: inline-block;
            width: 100%;
            border-radius: 12px;
        }

        .item i {
            font-size: 12px;
        }

        .item {
            display: flex;
            align-items: center;
        }

        .icon {
            color: var(--text-color);
        }

        .icon i {
            font-size: 24px;
            margin-right: 10px;
        }

        .item a:hover,
        .submenu-item:hover,
        .submenu .menu-title:hover {
            background: var(--hover-color);
        }

        .submenu-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-color);
            cursor: pointer;
        }

        .submenu {
            position: absolute;
            height: 100%;
            width: 100%;
            top: 0;
            right: calc(-100% - 26px);
            height: calc(100% + 100vh);
            background: var(--primary-color);
            /* Changed from blue */
            display: none;
        }

        .show-submenu~.submenu {
            display: block;
        }

        .submenu .menu-title {
            border-radius: 12px;
            cursor: pointer;
        }

        .submenu .menu-title i {
            margin-right: 10px;
        }

        .navbar,
        .main {
            left: 260px;
            width: calc(100% - 260px);
            transition: all 0.5s ease;
            z-index: 1000;
        }

        .sidebar.close~.navbar,
        .sidebar.close~.main {
            left: 0;
            width: 100%;
        }

        .navbar {
            position: fixed;
            color: var(--text-color);
            padding: 15px 20px;
            font-size: 25px;
            background: var(--primary-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar #sidebar-close {
            cursor: pointer;
        }

        #live-datetime {
            font-size: 16px;
            font-weight: 500;
            text-align: center;
        }

        .navbar .user-info {
            font-size: 14px;
        }

        .main {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            /* Align content to the top */
            align-items: center;
            /* Center horizontally */
            min-height: 100vh;
            padding-top: 100px;
            /* Add padding to avoid navbar overlap */
            z-index: 100;
            background: #e7f2fd;
        }

        .dropdown-content {
            background-color: var(--secondary-color);
            /* Changed from blue */
        }

        /* Form Styles */
        .applicant-form {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: calc(50% - 10px);
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
        }

        .form-group textarea {
            resize: vertical;
            height: 80px;
        }

        .applicant-form button[type="submit"] {
            padding: 12px;
            background-color: #333;
            /* Changed from blue */
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            align-self: flex-end;
            transition: background-color 0.3s ease;
        }

        .applicant-form button[type="submit"]:hover {
            background-color: #555;
            /* Darker hover */
        }
    </style>
</head>

<body>
    <nav class="sidebar">
        <div class="menu-content">
            <ul class="menu-items">
                <li class="item">
                    <a href="admin.php">
                        <span class="icon"><i class='bx bxs-dashboard'></i></span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="item">
                    <div class="submenu-item">
                        <span>
                            <span class="icon"><i class='bx bx-group'></i></span>
                            <span>Management</span>
                        </span>
                        <i class="fa-solid fa-chevron-right"></i>
                    </div>
                    <ul class="menu-items submenu">
                        <div class="menu-title"><i class='bx bx-arrow-back'></i>Back</div>
                        <li class="item1"><a>EMPLOYEE MANAGEMENT</a>
                            <div class="dropdown-content"><a href="employee_database.php"><small>EMPLOYEE
                                        DATABASE</small></a><a href="performance_and_appraisals.php"><small>PERFORMANCE
                                        & APPRAISALS</small></a></div>
                        </li>
                        <li class="item1"><a>RECRUITMENT</a>
                            <div class="dropdown-content"><a href="job_posting.php"><small>JOB POSTING</small></a><a
                                    href="candidate_sourcing_&_tracking.php"><small>CANDIDATE SOURCING &
                                        TRACKING</small></a><a href="interview_scheduling.php"><small>INTERVIEW
                                        SCHEDULING</small></a><a href="assessment_&_screening.php"><small>ASSESSMENT &
                                        SCREENING</small></a></div>
                        </li>
                        <li class="item1"><a>APPLICANT MANAGEMENT</a>
                            <div class="dropdown-content"><a href="#"><small>RESUME PARSING & STORAGE</small></a><a
                                    href="#"><small>COMMUNICATION & NOTIFICATIONS</small></a><a href="#"><small>DOCUMENT
                                        MANAGEMENT</small></a></div>
                        </li>
                        <li class="item1"><a>NEW HIRED ONBOARDING SYSTEM</a>
                            <div class="dropdown-content"><a href="#"><small>DIGITAL ONBOARDING PROCESS</small></a><a
                                    href="#"><small>WELCOME KIT & ORIENTATION</small></a><a href="#"><small>USER ACCOUNT
                                        & SETUP</small></a></div>
                        </li>
                        <li class="item1"><a>RECRUITING ANALYTIC & REPORTING</a>
                            <div class="dropdown-content"><a href="#"><small>HIRING METRICS DASHBOARD</small></a><a
                                    href="#"><small>RECRUITMENT FUNNEL & ANALYSIS</small></a><a
                                    href="#"><small>RECRUITER PERFORMANCE TRACKING</small></a><a
                                    href="#"><small>DIVERSITY & COMPLIANCE REPORT</small></a><a href="#"><small>COST &
                                        BUDGET ANALYSIS</small></a></div>
                        </li>
                    </ul>
                </li>
                <li class="item">
                    <a href="#">
                        <span class="icon"><i class="fa-solid fa-gear"></i></span>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <main class="main">
        <?php include '../Components/header_admin.php'; ?>
        <form action="submit_applicant.php" method="POST" enctype="multipart/form-data" class="applicant-form">
            <h2 style="text-align: center; color: #333;">Interview Scheduling</h2>
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="job">Start Time:</label>
                    <input type="time" id="job" name="job" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="position">End Time:</label>
                    <input type="time" id="position" name="position" required>
                </div>
                <div class="form-group">
                    <label for="experience">Applicant Number:</label>
                    <input type="text" id="experience" name="experience" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="age">Position:</label>
                    <input type="text" id="age" name="age" required>
                </div>
                <div class="form-group">
                    <label for="contact">Interviewer:</label>
                    <input type="text" id="contact" name="contact" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="address">Address:</label>
                    <input type="text" id="address" name="address" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="width: 100%;">
                    <label for="resume">Upload Police/NBI Clearance:</label>
                    <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                </div>
            </div>
            <button type="submit">Submit</button>
        </form>
    </main>

    <script>
        const sidebar = document.querySelector(".sidebar");
        const menu = document.querySelector(".menu-content");
        const menuItems = document.querySelectorAll(".submenu-item");
        const subMenuTitles = document.querySelectorAll(".submenu .menu-title");

        // Toggle logic handled by header_admin.php for sidebar close
        // But this file has custom sidebar structure (menu-content, submenu), so we keep submenu logic.

        menuItems.forEach((item, index) => {
            item.addEventListener("click", () => {
                menu.classList.add("submenu-active");
                item.classList.add("show-submenu");
                menuItems.forEach((item2, index2) => {
                    if (index !== index2) {
                        item2.classList.remove("show-submenu");
                    }
                });
            });
        });

        subMenuTitles.forEach((title) => {
            title.addEventListener("click", () => {
                menu.classList.remove("submenu-active");
            });
        });

        // Date and Time handled by header_admin.php
    </script>
</body>

</html>