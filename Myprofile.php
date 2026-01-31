<?php
session_start();
$root_path = './'; // Define root path for includes

require_once "Database/Connections.php";

// Ensure logic handles POST requests
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_SESSION['Email'];

    if ($new_password !== $confirm_password) {
        $msg = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>New passwords do not match.</div>";
    } else {
        $stmt = $conn->prepare("SELECT Password FROM logintbl WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $verified = ($current_password === $user['Password']) || password_verify($current_password, $user['Password']);

            if ($verified) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE logintbl SET Password = ? WHERE Email = ?");
                if ($update->execute([$hashed_password, $email])) {
                    $msg = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Password updated successfully.</div>";
                } else {
                    $msg = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Failed to update password.</div>";
                }
            } else {
                $msg = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Incorrect current password.</div>";
            }
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | HR1 Admin</title>
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
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-4">My Profile</h2>

            <form action="" method="POST" class="space-y-6">
                <!-- Profile Image Placeholder -->
                <div class="flex items-center gap-6 mb-8">
                    <div
                        class="w-24 h-24 rounded-full bg-indigo-50 border-2 border-indigo-100 flex items-center justify-center text-3xl text-indigo-600 font-bold">
                        <?php
                        $name = $_SESSION['GlobalName'] ?? 'AU';
                        echo strtoupper(substr($name, 0, 2));
                        ?>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">
                            <?= htmlspecialchars($_SESSION['GlobalName'] ?? 'Admin User') ?>
                        </h3>
                        <p class="text-gray-500">Administrator</p>
                        <button type="button"
                            class="mt-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium">Change Photo</button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                        <input type="text" name="full_name"
                            value="<?= htmlspecialchars($_SESSION['GlobalName'] ?? '') ?>"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-gray-50 text-gray-600"
                            readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['Email'] ?? '') ?>"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-gray-50 text-gray-600"
                            readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Account Type</label>
                        <input type="text" value="<?= $_SESSION['Account_type'] == 0 ? 'Super Admin' : 'Admin' ?>"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-gray-50 text-gray-600"
                            readonly>
                    </div>
                </div>
            </form>

            <div class="mt-12 border-t pt-8">
                <?php if (isset($msg))
                    echo $msg; ?>

                <h3 class="text-lg font-bold text-gray-800 mb-6">Security & Access</h3>

                <div class="max-w-xl">
                    <!-- Change Password Section -->
                    <section>
                        <h4 class="text-md font-bold text-gray-700 mb-4 border-b pb-2">Change Password</h4>
                        <form action="" method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Current Password</label>
                                <input type="password" name="current_password" required
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                                <input type="password" name="new_password" required
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm New
                                    Password</label>
                                <input type="password" name="confirm_password" required
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                            </div>
                            <button type="submit" name="update_password"
                                class="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm w-full">
                                Update Password
                            </button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>

</body>

</html>