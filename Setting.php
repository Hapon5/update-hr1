<?php
session_start();
$root_path = './'; // Define root path for includes

if (!isset($_SESSION['Email']) || !isset($_SESSION['Account_type'])) {
    // header("Location: index.php");
    // exit();
}

require_once "Database/Connections.php";

if (isset($_POST['update_pin'])) {
    $new_pin = $_POST['resume_pin'];
    $email = $_SESSION['Email'];

    // Validate 4 digits
    if (preg_match('/^\d{4}$/', $new_pin)) {
        try {
            $conn->exec("ALTER TABLE logintbl ADD COLUMN resume_pin VARCHAR(10) DEFAULT '1234'");
        } catch (Exception $e) {
        }

        $stmt = $conn->prepare("UPDATE logintbl SET resume_pin = ? WHERE Email = ?");
        if ($stmt->execute([$new_pin, $email])) {
            $msg = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Resume Access PIN updated successfully.</div>";
        } else {
            $msg = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Failed to update PIN.</div>";
        }
    } else {
        $msg = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>PIN must be exactly 4 digits.</div>";
    }
}

// Ensure column exists to avoid "Column not found" error on first load
try {
    // Attempt dummy select to check existence, or just catch the error on real select
    $conn->query("SELECT email_notify FROM logintbl LIMIT 1");
} catch (PDOException $e) {
    // If column missing, add it
    $conn->exec("ALTER TABLE logintbl ADD COLUMN email_notify TINYINT DEFAULT 1");
}

// Handle Preferences Update
if (isset($_POST['update_preferences'])) {
    $email_notify = isset($_POST['email_notify']) ? 1 : 0;
    $email = $_SESSION['Email'];

    $stmt = $conn->prepare("UPDATE logintbl SET email_notify = ? WHERE Email = ?");
    if ($stmt->execute([$email_notify, $email])) {
        $pref_msg = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Preferences updated.</div>";
    }
}

// Fetch Current Preferences
try {
    $stmt = $conn->prepare("SELECT email_notify FROM logintbl WHERE Email = ?");
    $stmt->execute([$_SESSION['Email']]);
    $user_pref = $stmt->fetch(PDO::FETCH_ASSOC);
    $notify_checked = ($user_pref && $user_pref['email_notify'] == 1) ? 'checked' : '';
} catch (Exception $e) {
    $notify_checked = 'checked'; // Default to checked if fetch fails despite our best efforts
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | HR1 Admin</title>
    <link rel="icon" type="image/x-icon" href="Image/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 16rem;
            padding: 100px 2.5rem 2.5rem;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 100px 1rem 1rem;
            }
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 font-sans">

    <?php include 'Components/sidebar_admin.php'; ?>
    <?php include 'Components/header_admin.php'; ?>

    <div class="main-content">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">Settings</h2>

            <div class="space-y-8">
                <?php if (isset($msg))
                    echo $msg; ?>

                <section>
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Recruitment Access PIN</h3>
                    <p class="text-sm text-gray-500 mb-4">Set a 4-digit PIN to secure resume viewing functionality.</p>

                    <form method="POST" class="max-w-md">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">New PIN</label>
                            <input type="text" name="resume_pin" pattern="\d{4}" maxlength="4" placeholder="1234"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                                required>
                        </div>
                        <button type="submit" name="update_pin"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition-colors">
                            Update PIN
                        </button>
                    </form>
                </section>

                <!-- Notifications Section -->
                <section class="border-t pt-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Preferences</h3>
                    <?php if (isset($pref_msg))
                        echo $pref_msg; ?>

                    <form method="POST">
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="email_notify" value="1" <?= $notify_checked ?>
                                    onchange="this.form.submit()" name="update_preferences"
                                    class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                                <span class="text-gray-700">Receive email notifications for new applications</span>
                            </label>

                            <!-- Hidden submit for JS trigger -->
                            <input type="hidden" name="update_preferences" value="1">

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500"
                                    disabled>
                                <span class="text-gray-700">Enable dark mode (Beta)</span>
                            </label>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>

</body>

</html>