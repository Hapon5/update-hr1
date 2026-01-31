<?php
session_start();
include(__DIR__ . '/../../Database/Connections.php');

// Ensure the performance_reviews table exists to prevent errors
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS performance_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        review_date DATE NOT NULL,
        review_type VARCHAR(50),
        kpi_score DECIMAL(5,2),
        attendance_score DECIMAL(5,2),
        supervisor_quality_rating INT,
        productivity_score DECIMAL(5,2),
        promotion_recommended TINYINT(1) DEFAULT 0,
        comments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // If table creation fails, the subsequent queries will likely fail too, 
    // but we can catch this to prevent a hard crash on the create step if needed.
}

// function to relay data to external API
function relayToExternalAPI($data) {
    $url = 'https://hr4.cranecali-ms.com/api/performance.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// Handle Form Submission (Add Review)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    try {
        $stmt = $conn->prepare("INSERT INTO performance_reviews 
            (employee_id, review_date, review_type, kpi_score, attendance_score, supervisor_quality_rating, productivity_score, promotion_recommended, comments) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $_POST['employee_id'],
            $_POST['review_date'],
            $_POST['review_type'],
            $_POST['kpi_score'],
            $_POST['attendance_score'],
            $_POST['supervisor_quality_rating'],
            $_POST['productivity_score'],
            isset($_POST['promotion_recommended']) ? 1 : 0,
            $_POST['comments']
        ]);

        // Relay data to external API
        $relayData = [
            'employee_id' => $_POST['employee_id'],
            'review_date' => $_POST['review_date'],
            'review_type' => $_POST['review_type'],
            'kpi_score' => $_POST['kpi_score'],
            'attendance_score' => $_POST['attendance_score'],
            'supervisor_quality_rating' => $_POST['supervisor_quality_rating'],
            'productivity_score' => $_POST['productivity_score'],
            'promotion_recommended' => isset($_POST['promotion_recommended']) ? 1 : 0,
            'comments' => $_POST['comments']
        ];
        relayToExternalAPI($relayData);

        $_SESSION['success_msg'] = "Performance review added and synchronized successfully!";
        header("Location: performance_and_appraisals.php");
        exit;
    } catch (PDOException $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

// Fetch Employees with their latest average scores
$sql = "SELECT e.*, 
        (SELECT AVG(kpi_score) FROM performance_reviews pr WHERE pr.employee_id = e.id) as avg_kpi,
        (SELECT AVG(productivity_score) FROM performance_reviews pr WHERE pr.employee_id = e.id) as avg_prod
        FROM employees e";
$employees = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Fetch History
$historySql = "SELECT pr.*, e.name as full_name, e.photo_path as image_path 
               FROM performance_reviews pr 
               JOIN employees e ON pr.employee_id = e.id 
               ORDER BY pr.review_date DESC LIMIT 10";
$history = $conn->query($historySql)->fetchAll(PDO::FETCH_ASSOC);

// Calculate Dashboard Stats
$totalEmployees = count($employees);
$avgCompanyKPI = 0;
$topPerformer = 'N/A';
$topKpi = 0;

if ($totalEmployees > 0) {
    $sumKpi = 0;
    foreach ($employees as $emp) {
        $sumKpi += $emp['avg_kpi'];
        if ($emp['avg_kpi'] > $topKpi) {
            $topKpi = $emp['avg_kpi'];
            $topPerformer = $emp['name']; // Fixed column name
        }
    }
    $avgCompanyKPI = $totalEmployees > 0 ? round($sumKpi / $totalEmployees, 1) : 0;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance & Appraisals | HR Super Admin</title>
    <!-- Tailwind & Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .modal {
            z-index: 50;
        }

        /* Custom Scroll */
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
    </style>
</head>

<body class="bg-white text-gray-800">

    <?php
    $root_path = '../../';
    include '../Components/sidebar.php';
    include '../Components/header.php';
    ?>

    <div class="main-content min-h-screen pt-24 pb-8 px-4 sm:px-8 ml-64 transition-all duration-300">

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight">Performance <span class="text-indigo-600">Sync</span></h1>
                <p class="text-[10px] text-gray-400 mt-1 uppercase font-black tracking-[0.3em]">Connected Evaluation System</p>
            </div>
            <div class="flex gap-3">
                <button onclick="openModal('evaluateModal')"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest shadow-lg shadow-indigo-100 transition-all flex items-center gap-2">
                    <i class="fas fa-plus"></i> New Evaluation
                </button>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-green-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Avg. Company KPI</h3>
                    <div class="p-2 bg-green-50 text-green-500 rounded-lg"><i class="fas fa-chart-line"></i></div>
                </div>
                <p class="text-3xl font-black text-gray-900"><?= $avgCompanyKPI ?>%</p>
                <span class="text-[10px] text-green-500 font-bold uppercase tracking-widest">+2.5% from last month</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-yellow-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Top Performer</h3>
                    <div class="p-2 bg-yellow-50 text-yellow-500 rounded-lg"><i class="fas fa-trophy"></i></div>
                </div>
                <p class="text-lg font-black text-gray-900 uppercase tracking-tight truncate"><?= $topPerformer ?></p>
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Consistent Excellence</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-orange-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Reviews Pending</h3>
                    <div class="p-2 bg-orange-50 text-orange-500 rounded-lg"><i class="fas fa-clock"></i></div>
                </div>
                <p class="text-3xl font-black text-gray-900">5</p>
                <span class="text-[10px] text-orange-500 font-bold uppercase tracking-widest">Due this week</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-purple-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Promotions Recom.</h3>
                    <div class="p-2 bg-purple-50 text-purple-500 rounded-lg"><i class="fas fa-user-plus"></i></div>
                </div>
                <p class="text-3xl font-black text-gray-900">2</p>
                <span class="text-[10px] text-purple-500 font-bold uppercase tracking-widest">Based on recent data</span>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Employee List (Left - 2cols) -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Employees Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h2 class="font-black text-gray-900 uppercase tracking-tight">Employee Performance Overview</h2>
                        <div class="relative">
                            <input type="text" placeholder="Search..."
                                class="pl-8 pr-4 py-1.5 text-xs bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-800 placeholder-gray-400">
                            <i
                                class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-[10px] text-gray-400 uppercase font-bold tracking-widest">
                                <tr>
                                    <th class="px-6 py-3">Employee</th>
                                    <th class="px-6 py-3 text-center">Avg. KPI</th>
                                    <th class="px-6 py-3 text-center">Incentive Score</th>
                                    <th class="px-6 py-3 text-center">Status</th>
                                    <th class="px-6 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($employees as $emp): ?>
                                    <tr class="hover:bg-white/[0.02] transition-colors group">
                                        <td class="px-6 py-4">
                                            <?php
                                            // Fix image path case sensitivity
                                            $photo = $emp['photo_path'];
                                            if (!empty($photo)) {
                                                // Convert 'profile/' to 'Profile/'
                                                $photo = str_ireplace('profile/', 'Profile/', $photo);
                                                // If just filename, prepend
                                                if (strpos($photo, 'Profile/') !== 0) {
                                                    $photo = 'Profile/' . $photo;
                                                }
                                            } else {
                                                $photo = 'Profile/default.png';
                                            }
                                            ?>
                                            <div class="flex items-center gap-3">
                                                <img src="../../<?= htmlspecialchars($photo) ?>?v=<?= time() ?>"
                                                    onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($emp['name']) ?>&background=random&color=fff';"
                                                    class="w-9 h-9 rounded-full object-cover border border-gray-100">
                                                <div>
                                                    <p class="font-bold text-sm text-gray-900 uppercase tracking-tight"><?= $emp['name'] ?>
                                                    </p>
                                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest"><?= $emp['position'] ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ($emp['avg_kpi']): ?>
                                                <span
                                                    class="inline-block px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest
                                                <?= $emp['avg_kpi'] >= 90 ? 'bg-green-500/10 text-green-400 border border-green-500/20' :
                                                    ($emp['avg_kpi'] >= 75 ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20' : 'bg-red-500/10 text-red-500 border border-red-500/20') ?>">
                                                    <?= number_format($emp['avg_kpi'], 1) ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-400 font-light">
                                            <?= $emp['avg_prod'] ? number_format($emp['avg_prod'], 1) : '-' ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span
                                                class="text-[10px] font-bold text-green-400 bg-green-500/10 px-2 py-1 rounded-full uppercase tracking-widest border border-green-500/20">Active</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <button
                                                onclick="openEvaluationModal('<?= $emp['id'] ?>', '<?= $emp['name'] ?>')"
                                                class="text-indigo-400 hover:text-indigo-300 text-[10px] font-bold uppercase tracking-widest hover:underline">
                                                Evaluate
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Productivy Chart -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="font-black text-gray-900 uppercase tracking-tight mb-4">Department Productivity Trends</h2>
                    <canvas id="productivityChart" height="100"></canvas>
                </div>

            </div>

            <!-- Sidebar Info (Right - 1col) -->
            <div class="space-y-6">

                <!-- Recent Reviews -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="font-black text-gray-900 uppercase tracking-tight mb-4">Recent Evaluations</h2>
                    <div class="space-y-4">
                        <?php if (count($history) > 0): ?>
                            <?php foreach ($history as $rec): ?>
                                <div
                                    class="flex gap-3 items-start p-3 hover:bg-gray-50 rounded-xl transition-colors border border-transparent hover:border-gray-50">
                                    <?php
                                    $photo = $rec['image_path'];
                                    if (!empty($photo)) {
                                        $photo = str_ireplace('profile/', 'Profile/', $photo);
                                        if (strpos($photo, 'Profile/') !== 0) {
                                            $photo = 'Profile/' . $photo;
                                        }
                                    } else {
                                        $photo = 'Profile/default.png';
                                    }
                                    ?>
                                    <img src="../../<?= htmlspecialchars($photo) ?>?v=<?= time() ?>"
                                        onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($rec['full_name']) ?>&background=random&color=fff';"
                                        class="w-8 h-8 rounded-full object-cover border border-gray-100">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-900 uppercase tracking-tight truncate"><?= $rec['full_name'] ?></p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest"><?= $rec['review_type'] ?> Review •
                                            <?= date('M d', strtotime($rec['review_date'])) ?>
                                        </p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="text-[10px] font-black px-1.5 py-0.5 bg-gray-50 rounded text-gray-400 border border-gray-100 uppercase tracking-tighter">
                                                KPI: <?= $rec['kpi_score'] ?></div>
                                            <?php if ($rec['promotion_recommended']): ?>
                                                <div
                                                    class="text-[10px] font-black px-1.5 py-0.5 bg-purple-500/10 text-purple-400 rounded border border-purple-500/20 uppercase tracking-tighter">
                                                    <i class="fas fa-star mr-1"></i>Promote
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-sm text-gray-400 text-center py-4">No reviews yet.</p>
                        <?php endif; ?>
                    </div>
                    <button
                        class="w-full mt-4 text-center text-[10px] text-indigo-400 font-bold uppercase tracking-widest hover:text-indigo-300">View
                        All History</button>
                </div>

                <!-- Top Skills / Metrics -->
                <div class="bg-gradient-to-br from-indigo-600 to-indigo-900 rounded-2xl shadow-2xl p-6 text-white border border-indigo-500/20">
                    <h2 class="font-black text-lg mb-2 uppercase tracking-tight">Evaluation Criteria</h2>
                    <p class="text-indigo-300 text-[10px] font-bold uppercase tracking-widest mb-4">Weightage for score calculation</p>

                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-xs mb-1 font-medium">
                                <span>KPI Goals</span>
                                <span>40%</span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-1.5">
                                <div class="bg-white h-1.5 rounded-full" style="width: 40%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1 font-medium">
                                <span>Productivity</span>
                                <span>30%</span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-1.5">
                                <div class="bg-white h-1.5 rounded-full" style="width: 30%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1 font-medium">
                                <span>Attendance</span>
                                <span>20%</span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-1.5">
                                <div class="bg-white h-1.5 rounded-full" style="width: 20%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1 font-medium">
                                <span>Supervisor Rating</span>
                                <span>10%</span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-1.5">
                                <div class="bg-white h-1.5 rounded-full" style="width: 10%"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- EVALUATION MODAL -->
    <div id="evaluateModal"
        class="flex fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform scale-95 transition-transform duration-300 border border-gray-50">
            <div class="px-6 py-4 border-b border-gray-50 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-lg text-gray-900 uppercase tracking-tight">New Performance Evaluation</h3>
                <button onclick="closeModal('evaluateModal')" class="text-gray-400 hover:text-gray-600 transition-colors"><i
                        class="fas fa-times"></i></button>
            </div>

            <form method="POST" class="p-6 space-y-6">
                <input type="hidden" name="action" value="add_review">

                <!-- Employee Select -->
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Employee</label>
                        <select name="employee_id" id="eval_employee_id"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none text-xs text-gray-900 font-bold">
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>"><?= $emp['name'] ?> - <?= $emp['position'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Review Period</label>
                        <select name="review_type"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none text-xs text-gray-900 font-bold">
                            <option value="Monthly">Monthly Review</option>
                            <option value="Quarterly">Quarterly Review</option>
                            <option value="Annual">Annual Appraisal</option>
                        </select>
                    </div>
                </div>

                <!-- Review Date field (Hidden/Auto or Manual) -->
                <input type="hidden" name="review_date" value="<?= date('Y-m-d') ?>">

                <div class="grid grid-cols-2 gap-6">
                    <!-- KPI Score -->
                    <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                        <label class="block text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2">KPI Score (0-100)</label>
                        <input type="number" name="kpi_score" min="0" max="100" required
                            class="w-full px-3 py-2.5 bg-white border border-blue-200 rounded md:text-xl font-black text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 text-center"
                            placeholder="85">
                        <p class="text-[10px] text-blue-400 mt-2 font-bold uppercase tracking-tighter">Based on role targets</p>
                    </div>

                    <!-- Productivity -->
                    <div class="bg-green-50 p-4 rounded-xl border border-green-100">
                        <label class="block text-[10px] font-black text-green-600 uppercase tracking-widest mb-2">Productivity (0-100)</label>
                        <input type="number" name="productivity_score" min="0" max="100" required
                            class="w-full px-3 py-2.5 bg-white border border-green-200 rounded md:text-xl font-black text-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 text-center"
                            placeholder="92">
                        <p class="text-[10px] text-green-400 mt-2 font-bold uppercase tracking-tighter">Output / Efficiency metrics</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <!-- Attendance -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Attendance Score %</label>
                        <input type="number" name="attendance_score" min="0" max="100"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none text-xs text-gray-900 placeholder-gray-400"
                            placeholder="e.g. 98">
                    </div>
                    <!-- Supervisor Rating -->
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Supervisor Rating (1-5)</label>
                        <div class="flex gap-4 mt-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="supervisor_quality_rating" value="<?= $i ?>"
                                        class="peer sr-only" required>
                                    <div
                                        class="w-9 h-9 rounded-full flex items-center justify-center border border-gray-200 bg-gray-50 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 hover:bg-white transition-all font-black text-sm text-gray-400">
                                        <?= $i ?>
                                    </div>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Recommendation -->
                <div class="flex items-center gap-3 p-4 border border-purple-100 bg-purple-50 rounded-xl">
                    <input type="checkbox" id="promo" name="promotion_recommended"
                        class="w-5 h-5 text-purple-600 bg-white border-gray-200 rounded focus:ring-purple-500">
                    <label for="promo" class="text-[10px] font-black text-purple-600 cursor-pointer uppercase tracking-widest">Recommend for
                        Promotion / Raise</label>
                </div>

                <!-- Comments -->
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Feedback / Notes</label>
                    <textarea name="comments" rows="3"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all outline-none resize-none text-xs text-gray-900 placeholder-gray-400"
                        placeholder="Enter strengths, areas for improvement..."></textarea>
                </div>

                <div class="pt-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 -mx-6 -mb-6 p-6 mt-6">
                    <button type="button" onclick="closeModal('evaluateModal')"
                        class="px-6 py-2.5 rounded-lg text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-100 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-8 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-indigo-600 text-white hover:bg-indigo-700 shadow-md transition-all">Submit
                        Evaluation</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
            // Small delay to allow display:flex to apply before opacity transition
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.firstElementChild.classList.remove('scale-95');
                modal.firstElementChild.classList.add('scale-100');
            }, 10);
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            modal.classList.add('opacity-0');
            modal.firstElementChild.classList.remove('scale-100');
            modal.firstElementChild.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function openEvaluationModal(empId, empName) {
            const select = document.getElementById('eval_employee_id');
            select.value = empId;
            openModal('evaluateModal');
        }

        // --- Charts ---
        const ctx = document.getElementById('productivityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Overall Efficiency',
                    data: [78, 82, 80, 85, 88, 91],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#000',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { 
                        beginAtZero: false, 
                        min: 60, max: 100, 
                        grid: { color: '#f1f1f1', borderDash: [2, 4] },
                        ticks: { color: '#9ca3af', font: { size: 10, weight: 'bold' } }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { color: '#9ca3af', font: { size: 10, weight: 'bold' } }
                    }
                }
            }
        });
    </script>
</body>

</html>