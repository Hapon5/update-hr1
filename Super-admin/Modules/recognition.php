<?php
session_start();
include(__DIR__ . '/../../Database/Connections.php');

// Initialize tables if empty (Optional, mostly for first run if not using existing seed)
// Assuming 'recognitions' and 'recognition_categories' exist based on previous inspection.

// Handle Recognition Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'give_recognition') {
        try {


            $fromId = $_SESSION['EmployeeID'] ?? 0;

            $stmt = $conn->prepare("INSERT INTO recognitions 
                (from_employee_id, to_employee_id, category_id, title, message, recognition_date, is_public) 
                VALUES (?, ?, ?, ?, ?, NOW(), 1)");


            $stmt->execute([
                1, // Hardcoded '1' for Admin/System to simplify demo, assuming ID 1 exists (Andy Ferrer usually)
                $_POST['to_employee_id'],
                $_POST['category_id'],
                $_POST['title'],
                $_POST['message']
            ]);

            $_SESSION['success_msg'] = "Recognition sent successfully!";
            header("Location: recognition.php");
            exit;
        } catch (PDOException $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

// Fetch Data for Form
$employees = $conn->query("SELECT id, name, position, photo_path FROM employees WHERE status='Active'")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Categories (Create if none)
$categories = $conn->query("SELECT * FROM recognition_categories")->fetchAll(PDO::FETCH_ASSOC);
if (count($categories) == 0) {
    $conn->exec("CREATE TABLE IF NOT EXISTS `recognition_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `icon` varchar(50) DEFAULT 'fa-star',
        `color` varchar(20) DEFAULT 'blue',
        PRIMARY KEY (`id`)
    )");

    // Seed
    $seedCats = [
        ['Outstanding Performance', 'fa-trophy', 'yellow'],
        ['Team Player', 'fa-users', 'blue'],
        ['Innovation', 'fa-lightbulb', 'purple'],
        ['Customer Hero', 'fa-heart', 'red'],
        ['Leadership', 'fa-crown', 'orange']
    ];
    $stmt = $conn->prepare("INSERT INTO recognition_categories (name, icon, color) VALUES (?, ?, ?)");
    foreach ($seedCats as $c)
        $stmt->execute($c);
    $categories = $conn->query("SELECT * FROM recognition_categories")->fetchAll(PDO::FETCH_ASSOC);
}


// Fetch Recent Recognitions
$sqlRecog = "SELECT r.*, 
            e_to.name as to_name, e_to.photo_path as to_photo, e_to.position as to_pos,
            e_from.name as from_name, e_from.photo_path as from_photo,
            c.name as category_name, c.icon as cat_icon, c.color as cat_color
            FROM recognitions r
            JOIN employees e_to ON r.to_employee_id = e_to.id
            LEFT JOIN employees e_from ON r.from_employee_id = e_from.id
            JOIN recognition_categories c ON r.category_id = c.id
            ORDER BY r.created_at DESC LIMIT 10";
$recognitions = $conn->query($sqlRecog)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recognition | HR Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #000;
        }
 
        /* Custom Scroll */
        ::-webkit-scrollbar {
            width: 6px;
        }
 
        ::-webkit-scrollbar-track {
            background: #000;
        }
 
        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 10px;
        }
 
        ::-webkit-scrollbar-thumb:hover {
            background: #444;
        }
    </style>
</head>

<body class="bg-black text-gray-200">

    <?php
    $root_path = '../../';
    include '../Components/sidebar.php';
    include '../Components/header.php';
    ?>

    <div class="main-content min-h-screen pt-24 pb-8 px-4 sm:px-8 ml-64 transition-all duration-300">

        <!-- Welcome / Header-like section -->
        <div class="bg-gray-900 rounded-3xl shadow-2xl border border-gray-800 p-8 mb-8">
            <div class="flex items-center gap-4">
                <div class="p-4 bg-yellow-500/10 rounded-2xl border border-yellow-500/20 shadow-lg shadow-yellow-950/20">
                    <i class="fas fa-trophy text-4xl text-yellow-500"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-white uppercase tracking-tight">Employee Recognition</h1>
                    <p class="text-gray-500 mt-1 text-[10px] font-bold uppercase tracking-widest">Recognize and celebrate outstanding employee contributions</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Left Column: Give Recognition Form -->
            <div class="lg:col-span-2">
                <div class="bg-gray-900 rounded-3xl shadow-2xl border border-gray-800 p-8">
                    <h2 class="text-sm font-black text-white mb-6 flex items-center gap-2 uppercase tracking-widest">
                        <i class="fas fa-plus-circle text-indigo-500"></i> Give Recognition
                    </h2>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="give_recognition">

                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 mb-2 uppercase tracking-widest">Recognize:</label>
                            <select name="to_employee_id" required
                                class="w-full px-4 py-3.5 bg-gray-950 border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-black outline-none transition-all text-sm font-bold text-white">
                                <option value="" disabled selected>Select an employee...</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp['id'] ?>">
                                        <?= $emp['name'] ?> (
                                        <?= $emp['position'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 mb-2 uppercase tracking-widest">Recognition Type:</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <?php foreach ($categories as $cat): ?>
                                    <label class="cursor-pointer relative group">
                                        <input type="radio" name="category_id" value="<?= $cat['id'] ?>"
                                            class="peer sr-only" required>
                                        <div
                                            class="p-4 rounded-xl border border-gray-800 bg-gray-950 hover:bg-black peer-checked:border-<?= $cat['color'] ?>-500 peer-checked:bg-<?= $cat['color'] ?>-500/10 transition-all text-center h-full flex flex-col items-center justify-center gap-2 shadow-lg">
                                            <i
                                                class="fas <?= $cat['icon'] ?> text-<?= $cat['color'] ?>-500 text-2xl group-hover:scale-110 transition-transform"></i>
                                            <span class="text-[10px] font-black text-gray-400 group-hover:text-white uppercase tracking-tighter">
                                                <?= $cat['name'] ?>
                                            </span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 mb-2 uppercase tracking-widest">Recognition Title:</label>
                            <input type="text" name="title" required
                                class="w-full px-4 py-3.5 bg-gray-950 border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-black outline-none transition-all text-sm font-bold text-white placeholder-gray-700"
                                placeholder="Enter a title for this recognition...">
                        </div>
 
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 mb-2 uppercase tracking-widest">Recognition Message:</label>
                            <textarea name="message" rows="4" required
                                class="w-full px-4 py-3.5 bg-gray-950 border border-gray-800 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-black outline-none transition-all text-sm font-bold text-white placeholder-gray-700 resize-none"
                                placeholder="Write a detailed message about their contribution..."></textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                class="w-full py-4 bg-indigo-600 text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-950/40 active:scale-[0.99]">
                                Send Recognition
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Recent Wall -->
            <div class="space-y-6">
                <!-- Stats / Featured -->
                <div class="bg-gradient-to-br from-indigo-600 to-indigo-900 rounded-3xl p-6 text-white shadow-2xl border border-indigo-500/20">
                    <h3 class="font-black text-lg mb-1 uppercase tracking-tight">Impact Summary</h3>
                    <p class="text-indigo-300 text-[10px] font-bold uppercase tracking-widest mb-4">This month's engagement</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white/10 rounded-2xl p-3 backdrop-blur-sm">
                            <div class="text-2xl font-bold">
                                <?= count($recognitions) ?>
                            </div>
                            <div class="text-xs text-indigo-100">Total Shoutouts</div>
                        </div>
                        <div class="bg-white/10 rounded-2xl p-3 backdrop-blur-sm">
                            <div class="text-2xl font-bold">5</div>
                            <div class="text-xs text-indigo-100">Top Categories</div>
                        </div>
                    </div>
                </div>

                <!-- Feed -->
                <div>
                    <h3 class="font-black text-gray-500 text-[10px] mb-4 px-2 uppercase tracking-widest">Recent Callouts</h3>
                    <div class="space-y-4">
                        <?php if (count($recognitions) > 0): ?>
                            <?php foreach ($recognitions as $rec): ?>
                                <div class="bg-gray-900 p-5 rounded-2xl shadow-2xl border border-gray-800">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center gap-3">
                                            <img src="../../<?= $rec['to_photo'] ?>"
                                                class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm">
                                            <div>
                                                <p class="text-sm font-black text-white uppercase tracking-tight">
                                                    <?= $rec['to_name'] ?>
                                                </p>
                                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">
                                                    <?= $rec['to_pos'] ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div
                                            class="w-8 h-8 rounded-full bg-<?= $rec['cat_color'] ?>-500/10 flex items-center justify-center text-<?= $rec['cat_color'] ?>-500 border border-<?= $rec['cat_color'] ?>-500/20">
                                            <i class="fas <?= $rec['cat_icon'] ?> text-sm"></i>
                                        </div>
                                    </div>
                                    <h4 class="font-black text-white text-sm mb-1 uppercase tracking-tight">
                                        <?= $rec['title'] ?>
                                    </h4>
                                    <p class="text-xs text-gray-400 leading-relaxed mb-3 font-light">"
                                        <?= $rec['message'] ?>"
                                    </p>
                                    <div
                                        class="flex items-center justify-between pt-3 border-t border-gray-800 text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                        <span>From:
                                            <?= $rec['from_name'] ?: 'System Admin' ?>
                                        </span>
                                        <span>
                                            <?= date('M d', strtotime($rec['recognition_date'])) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div
                                class="text-center py-8 text-gray-600 bg-gray-950 rounded-2xl border border-dashed border-gray-800">
                                <i class="far fa-star text-2xl mb-2"></i>
                                <p class="text-xs font-bold uppercase tracking-widest">No recognitions yet. Be the first!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>