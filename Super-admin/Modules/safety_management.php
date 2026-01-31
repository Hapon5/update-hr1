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
            background-color: #f9fafb;
        }

        .modal {
            z-index: 50;
        }

        /* Left border colors for cards */
        .border-l-Critical {
            border-left-color: #ef4444;
        }

        /* Red */
        .border-l-High {
            border-left-color: #f97316;
        }

        /* Orange */
        .border-l-Medium {
            border-left-color: #eab308;
        }

        /* Yellow */
        .border-l-Low {
            border-left-color: #22c55e;
        }

        /* Green */
    </style>
</head>

<body class="bg-gray-50 text-gray-800">

    <?php
    $root_path = '../../';
    include '../Components/sidebar.php';
    include '../Components/header.php';
    ?>

    <div class="main-content min-h-screen pt-24 pb-8 px-4 sm:px-8 ml-64 transition-all duration-300">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Safety Management</h1>
                <p class="text-sm text-gray-500 mt-1">Track incidents, monitor risks, and ensure workplace safety.</p>
            </div>
            <button onclick="openModal('reportModal')"
                class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-sm transition-all flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i> Report Incident
            </button>
        </div>

        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-500 text-sm font-medium">Safe Days Streak</h3>
                    <div class="p-2 bg-green-50 text-green-600 rounded-lg"><i class="fas fa-calendar-check"></i></div>
                </div>
                <p class="text-3xl font-bold text-gray-800">
                    <?= $daysSafe ?>
                </p>
                <span class="text-xs text-gray-400">Days since last incident</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-500 text-sm font-medium">Open Cases</h3>
                    <div class="p-2 bg-blue-50 text-blue-600 rounded-lg"><i class="fas fa-folder-open"></i></div>
                </div>
                <p class="text-3xl font-bold text-gray-800">
                    <?= $openCases ?>
                </p>
                <span class="text-xs text-blue-500 font-medium font-medium">Active investigations</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-500 text-sm font-medium">Severe Incidents</h3>
                    <div class="p-2 bg-red-50 text-red-600 rounded-lg"><i class="fas fa-biohazard"></i></div>
                </div>
                <p class="text-3xl font-bold text-gray-800">
                    <?= $severeIncidents ?>
                </p>
                <span class="text-xs text-red-500 font-medium">High/Critical priority</span>
            </div>
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-500 text-sm font-medium">Total Incidents</h3>
                    <div class="p-2 bg-gray-50 text-gray-600 rounded-lg"><i class="fas fa-history"></i></div>
                </div>
                <p class="text-3xl font-bold text-gray-800">
                    <?= $totalIncidents ?>
                </p>
                <span class="text-xs text-gray-400">Recorded All-time</span>
            </div>
        </div>

        <!-- Recent Incidents Feed -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-history text-gray-400"></i> Recent Safety Incidents
                </h2>
                <form class="relative w-full sm:w-64">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search incidents..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </form>
            </div>

            <div class="divide-y divide-gray-50">
                <?php if (count($incidents) > 0): ?>
                    <?php foreach ($incidents as $inc): ?>
                        <div class="p-6 hover:bg-gray-50 transition-colors border-l-4 border-l-<?= $inc['severity'] ?>">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-gray-900 text-lg">
                                    <?= htmlspecialchars($inc['employee_name']) ?>
                                </h3>
                                <span class="text-xs text-gray-500 font-medium bg-gray-100 px-2 py-1 rounded">
                                    <?= date('M d, Y h:i A', strtotime($inc['incident_date'])) ?>
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm mb-4 leading-relaxed">
                                <?= htmlspecialchars($inc['incident_details']) ?>
                            </p>
                            <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide">
                                <!-- Incident Type Badge -->
                                <span class="px-2.5 py-1 rounded bg-blue-50 text-blue-700 border border-blue-100">
                                    <?= htmlspecialchars($inc['incident_type']) ?>
                                </span>

                                <!-- Severity Badge -->
                                <?php
                                $sevClass = match ($inc['severity']) {
                                    'Low' => 'bg-green-50 text-green-700 border-green-100',
                                    'Medium' => 'bg-yellow-50 text-yellow-700 border-yellow-100',
                                    'High' => 'bg-orange-50 text-orange-700 border-orange-100',
                                    'Critical' => 'bg-red-50 text-red-700 border-red-100',
                                    default => 'bg-gray-50 text-gray-600'
                                };
                                ?>
                                <span class="px-2.5 py-1 rounded border <?= $sevClass ?>">
                                    <?= htmlspecialchars($inc['severity']) ?> Priority
                                </span>

                                <!-- Location Badge -->
                                <span class="px-2.5 py-1 rounded bg-purple-50 text-purple-700 border border-purple-100">
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
        </div>

    </div>

    <!-- REPORT MODAL -->
    <div id="reportModal"
        class="flex fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4 backdrop-blur-sm opacity-0 transition-opacity duration-300">
        <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-lg text-gray-800">Report New Incident</h3>
                <button onclick="closeModal('reportModal')" class="text-gray-400 hover:text-gray-600"><i
                        class="fas fa-times"></i></button>
            </div>

            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="report_incident">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee Involved</label>
                    <input type="text" name="employee_name" required
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 outline-none text-sm"
                        placeholder="e.g. John Doe">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date & Time</label>
                        <input type="datetime-local" name="incident_date" required
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <input type="text" name="location" required
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 outline-none text-sm"
                            placeholder="e.g. Warehouse A">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select name="incident_type"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 outline-none text-sm">
                            <option value="Injury">Injury</option>
                            <option value="Near Miss">Near Miss</option>
                            <option value="Property Damage">Property Damage</option>
                            <option value="Hazard">Hazard</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Severity</label>
                        <select name="severity"
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 outline-none text-sm">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Incident Details</label>
                    <textarea name="incident_details" rows="3" required
                        class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500 outline-none text-sm resize-none"
                        placeholder="Describe what happened..."></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('reportModal')"
                        class="px-5 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-lg text-sm font-medium bg-red-600 text-white hover:bg-red-700 shadow-lg shadow-red-200 transition-all">Submit
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
    </script>
</body>

</html>