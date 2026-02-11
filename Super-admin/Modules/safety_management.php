<?php
session_start();
include(__DIR__ . '/../../Database/Connections.php');

// Handle Form Submission (Report Incident)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'report_incident') {
    try {
        $stmt = $conn->prepare("INSERT INTO safety_incidents 
            (employee_name, incident_details, incident_type, severity, location, incident_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Open')");

        $stmt->execute([
            $_POST['employee_name'],
            $_POST['incident_details'],
            $_POST['incident_type'],
            $_POST['severity'],
            $_POST['location'],
            $_POST['incident_date']
        ]);

        $_SESSION['success_msg'] = "Incident reported successfully.";
        header("Location: safety_management.php");
        exit;
    } catch (PDOException $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

// Fetch Incidents
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM safety_incidents WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (employee_name LIKE ? OR incident_details LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY incident_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$totalIncidents = count($incidents);
$openCases = 0;
$severeIncidents = 0;
$daysSafe = 0;

if ($totalIncidents > 0) {
    foreach ($incidents as $inc) {
        if ($inc['status'] === 'Open')
            $openCases++;
        if ($inc['severity'] === 'High' || $inc['severity'] === 'Critical')
            $severeIncidents++;
    }

    // Calculate days since last incident
    $lastIncident = strtotime($incidents[0]['incident_date']);
    $today = time();
    $daysSafe = floor(($today - $lastIncident) / (60 * 60 * 24));
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safety Management | HR Super Admin</title>
    <!-- Tailwind & Icons -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }

        .modal {
            z-index: 50;
        }

        /* Left border colors for cards with subtle background */
        .border-l-Critical {
            border-left-color: #ef4444;
            background-color: rgba(239, 68, 68, 0.02);
        }
 
        .border-l-High {
            border-left-color: #f97316;
            background-color: rgba(249, 115, 22, 0.02);
        }
 
        .border-l-Medium {
            border-left-color: #eab308;
            background-color: rgba(234, 179, 8, 0.02);
        }
 
        .border-l-Low {
            border-left-color: #22c55e;
            background-color: rgba(34, 197, 94, 0.02);
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

<body class="bg-[#f8f9fa] text-gray-800">

    <?php
    $root_path = '../../';
    include '../Components/sidebar.php';
    include '../Components/header.php';
    ?>

    <div class="main-content min-h-screen pt-24 pb-8 px-4 sm:px-8 ml-64 transition-all duration-300">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Safety Management</h1>
                <p class="text-[10px] text-gray-400 mt-1 uppercase font-bold tracking-widest">Track incidents, monitor risks, and ensure workplace safety.</p>
            </div>
            <div class="flex gap-3">
                <div class="flex bg-white rounded-lg p-1 border border-gray-100">
                    <button onclick="switchView('feed')" id="viewFeed"
                        class="px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-md bg-indigo-600 text-white shadow-md transition-all">
                        <i class="fas fa-th-list mr-1"></i> Feed
                    </button>
                    <button onclick="switchView('table')" id="viewTable"
                        class="px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-md text-gray-400 hover:text-gray-600 transition-all">
                        <i class="fas fa-table mr-1"></i> Table View
                    </button>
                </div>
                <button onclick="openModal('reportModal')"
                    class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest shadow-lg shadow-red-950/40 transition-all flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i> Report Incident
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-100 text-green-600 rounded-xl text-xs font-bold uppercase tracking-widest flex items-center gap-3 animate-bounce">
                <i class="fas fa-check-circle text-lg"></i>
                <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-600 rounded-xl text-xs font-bold uppercase tracking-widest flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <?= $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-green-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Safe Days Streak</h3>
                    <div class="p-2 bg-green-50 text-green-500 rounded-lg"><i class="fas fa-calendar-check"></i></div>
                </div>
                <p class="text-3xl font-black text-gray-900">
                    <?= $daysSafe ?>
                </p>
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Days since last incident</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-blue-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Open Cases</h3>
                    <div class="p-2 bg-blue-50 text-blue-500 rounded-lg"><i class="fas fa-folder-open"></i></div>
                </div>
                <p class="text-3xl font-black text-gray-900">
                    <?= $openCases ?>
                </p>
                <span class="text-[10px] text-blue-500 font-bold uppercase tracking-widest">Active investigations</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-red-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Severe Incidents</h3>
                    <div class="p-2 bg-red-50 text-red-500 rounded-lg"><i class="fas fa-biohazard"></i></div>
                </div>
                <p class="text-3xl font-black text-gray-900">
                    <?= $severeIncidents ?>
                </p>
                <span class="text-[10px] text-red-500 font-bold uppercase tracking-widest">High/Critical priority</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 border-l-4 border-l-gray-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Total Incidents</h3>
                    <div class="p-2 bg-gray-50 text-gray-500 rounded-lg"><i class="fas fa-history"></i></div>
                </div>
                <p class="text-3xl font-black text-gray-900">
                    <?= $totalIncidents ?>
                </p>
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Recorded All-time</span>
            </div>
        </div>

        <!-- PPE Safety Standards Section -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl shadow-sm border border-indigo-100">
                    <i class="fas fa-hard-hat text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-gray-900 uppercase tracking-tight">PPE Safety Standards</h2>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Mandatory Basic Safety Tools & Equipment</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <!-- Helmet -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-lg hover:shadow-indigo-500/5 transition-all group hover:-translate-y-1 cursor-default">
                    <div class="w-16 h-16 mx-auto bg-yellow-50 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform border border-yellow-100">
                        <i class="fas fa-hard-hat text-3xl text-yellow-500"></i>
                    </div>
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-900 mb-1">Safety Helmet</h3>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Head Protection</p>
                </div>

                <!-- Vest -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-lg hover:shadow-orange-500/5 transition-all group hover:-translate-y-1 cursor-default">
                    <div class="w-16 h-16 mx-auto bg-orange-50 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform border border-orange-100">
                        <i class="fas fa-vest text-3xl text-orange-500"></i>
                    </div>
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-900 mb-1">High-Vis Vest</h3>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Visibility</p>
                </div>

                <!-- Gloves -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-lg hover:shadow-blue-500/5 transition-all group hover:-translate-y-1 cursor-default">
                    <div class="w-16 h-16 mx-auto bg-blue-50 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform border border-blue-100">
                        <i class="fas fa-hands text-3xl text-blue-500"></i>
                    </div>
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-900 mb-1">Safety Gloves</h3>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Hand Protection</p>
                </div>

                <!-- Goggles -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-lg hover:shadow-purple-500/5 transition-all group hover:-translate-y-1 cursor-default">
                    <div class="w-16 h-16 mx-auto bg-purple-50 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform border border-purple-100">
                        <i class="fas fa-glasses text-3xl text-purple-500"></i>
                    </div>
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-900 mb-1">Safety Goggles</h3>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Eye Protection</p>
                </div>

                <!-- Boots -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-lg hover:shadow-gray-500/5 transition-all group hover:-translate-y-1 cursor-default">
                    <div class="w-16 h-16 mx-auto bg-gray-50 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform border border-gray-100">
                        <i class="fas fa-shoe-prints text-3xl text-gray-600"></i>
                    </div>
                    <h3 class="text-[10px] font-black uppercase tracking-widest text-gray-900 mb-1">Safety Boots</h3>
                    <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Foot Protection</p>
                </div>
            </div>

        <!-- Recent Incidents Feed -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center bg-gray-50 gap-4">
                <h2 class="text-lg font-black text-gray-900 flex items-center gap-2 uppercase tracking-tight">
                    <i class="fas fa-history text-indigo-500"></i> Recent Safety Incidents
                </h2>
                <form class="relative w-full sm:w-64">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search incidents..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-800 placeholder-gray-400">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </form>
            </div>

            <div id="feedLayout" class="divide-y divide-gray-100">
                <?php if (count($incidents) > 0): ?>
                    <?php foreach ($incidents as $inc): ?>
                        <div class="p-6 hover:bg-gray-50 transition-colors border-l-4 border-l-<?= $inc['severity'] ?>">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-black text-gray-900 text-lg uppercase tracking-tight">
                                    <?= htmlspecialchars($inc['employee_name']) ?>
                                </h3>
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest bg-gray-50 border border-gray-200 px-2.5 py-1 rounded">
                                    <?= date('M d, Y h:i A', strtotime($inc['incident_date'])) ?>
                                </span>
                            </div>
                            <p class="text-gray-400 text-sm mb-4 leading-relaxed font-light">
                                <?= htmlspecialchars($inc['incident_details']) ?>
                            </p>
                            <div class="flex flex-wrap gap-2 text-[10px] font-black uppercase tracking-widest">
                                <!-- Incident Type Badge -->
                                <span class="px-2.5 py-1 rounded bg-blue-50 text-blue-500 border border-blue-100">
                                    <?= htmlspecialchars($inc['incident_type']) ?>
                                </span>

                                <!-- Severity Badge -->
                                <?php
                                $sevClass = match ($inc['severity']) {
                                    'Low' => 'bg-green-50 text-green-500 border-green-100',
                                    'Medium' => 'bg-yellow-50 text-yellow-600 border-yellow-100',
                                    'High' => 'bg-orange-50 text-orange-600 border-orange-100',
                                    'Critical' => 'bg-red-50 text-red-500 border-red-100',
                                    default => 'bg-gray-100 text-gray-500'
                                };
                                ?>
                                <span class="px-2.5 py-1 rounded border <?= $sevClass ?>">
                                    <?= htmlspecialchars($inc['severity']) ?> Priority
                                </span>

                                <!-- Location Badge -->
                                <span class="px-2.5 py-1 rounded bg-purple-50 text-purple-500 border border-purple-100">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    <?= htmlspecialchars($inc['location']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-10 text-center text-gray-400">
                        <i class="fas fa-shield-alt text-4xl mb-3 text-gray-300"></i>
                        <p>No incidents found. Stay safe!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Table Layout -->
            <div id="tableLayout" class="hidden overflow-x-auto">
                <table class="w-full text-left text-xs uppercase tracking-widest">
                    <thead class="bg-gray-50 text-gray-400 font-bold border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4">Employee</th>
                            <th class="px-6 py-4">Date</th>
                            <th class="px-6 py-4">Type</th>
                            <th class="px-6 py-4">Severity</th>
                            <th class="px-6 py-4">Location</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($incidents as $inc): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-black text-gray-900"><?= htmlspecialchars($inc['employee_name']) ?></td>
                            <td class="px-6 py-4 text-gray-400"><?= date('M d, Y', strtotime($inc['incident_date'])) ?></td>
                            <td class="px-6 py-4 text-indigo-500"><?= htmlspecialchars($inc['incident_type']) ?></td>
                            <td class="px-6 py-4 italic"><?= htmlspecialchars($inc['severity']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($inc['location']) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-[9px] font-bold <?= $inc['status'] === 'Open' ? 'bg-blue-50 text-blue-500' : 'bg-gray-50 text-gray-500' ?>">
                                    <?= $inc['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- REPORT MODAL -->
    <div id="reportModal"
        class="flex fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 border border-gray-50">
            <div class="px-6 py-4 border-b border-gray-50 flex justify-between items-center bg-gray-50">
                <h3 class="font-black text-lg text-gray-900 uppercase tracking-tight">Report New Incident</h3>
                <button onclick="closeModal('reportModal')" class="text-gray-400 hover:text-gray-600 transition-colors"><i
                        class="fas fa-times"></i></button>
            </div>

            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="report_incident">

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Employee Involved</label>
                    <input type="text" name="employee_name" required
                        class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:bg-white transition-all outline-none text-xs text-gray-900 placeholder-gray-400"
                        placeholder="e.g. John Doe">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Date & Time</label>
                        <input type="datetime-local" name="incident_date" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 transition-all outline-none text-xs text-gray-900">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Location</label>
                        <input type="text" name="location" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 transition-all outline-none text-xs text-gray-900 placeholder-gray-400"
                            placeholder="e.g. Warehouse A">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Type</label>
                        <select name="incident_type"
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 transition-all outline-none text-xs text-gray-900">
                            <option value="Injury">Injury</option>
                            <option value="Near Miss">Near Miss</option>
                            <option value="Property Damage">Property Damage</option>
                            <option value="Hazard">Hazard</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Severity</label>
                        <select name="severity"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:bg-white transition-all outline-none text-xs text-gray-900">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Incident Details</label>
                    <textarea name="incident_details" rows="3" required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-white transition-all outline-none text-xs text-gray-900 placeholder-gray-400 resize-none"
                        placeholder="Describe what happened..."></textarea>
                </div>

                <div class="pt-6 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 -mx-6 -mb-6 p-6 mt-6">
                    <button type="button" onclick="closeModal('reportModal')"
                        class="px-6 py-2.5 rounded-lg text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-100 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-8 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-red-600 text-white hover:bg-red-700 shadow-md transition-all">Submit
                        Report</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('hidden');
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

        function switchView(view) {
            const feed = document.getElementById('feedLayout');
            const table = document.getElementById('tableLayout');
            const btnFeed = document.getElementById('viewFeed');
            const btnTable = document.getElementById('viewTable');

            if (view === 'feed') {
                feed.classList.remove('hidden');
                table.classList.add('hidden');
                btnFeed.className = "px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-md bg-indigo-600 text-white shadow-md transition-all";
                btnTable.className = "px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-md text-gray-400 hover:text-gray-600 transition-all";
            } else {
                feed.classList.add('hidden');
                table.classList.remove('hidden');
                btnTable.className = "px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-md bg-indigo-600 text-white shadow-md transition-all";
                btnFeed.className = "px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-md text-gray-400 hover:text-gray-600 transition-all";
            }
        }
    </script>
</body>

</html>