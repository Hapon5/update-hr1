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
            background-color: #000;
        }

        .modal {
            z-index: 50;
        }

        /* Left border colors for cards with glow */
        .border-l-Critical {
            border-left-color: #ef4444;
            box-shadow: inset 4px 0 10px -4px rgba(239, 68, 68, 0.3);
        }

        .border-l-High {
            border-left-color: #f97316;
            box-shadow: inset 4px 0 10px -4px rgba(249, 115, 22, 0.3);
        }

        .border-l-Medium {
            border-left-color: #eab308;
            box-shadow: inset 4px 0 10px -4px rgba(234, 179, 8, 0.3);
        }

        .border-l-Low {
            border-left-color: #22c55e;
            box-shadow: inset 4px 0 10px -4px rgba(34, 197, 94, 0.3);
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

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-black text-white uppercase tracking-tight">Safety Management</h1>
                <p class="text-[10px] text-gray-500 mt-1 uppercase font-bold tracking-widest">Track incidents, monitor risks, and ensure workplace safety.</p>
            </div>
            <button onclick="openModal('reportModal')"
                class="bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest shadow-lg shadow-red-950/40 transition-all flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i> Report Incident
            </button>
        </div>

        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-gray-900 p-5 rounded-2xl shadow-2xl border border-gray-800 border-l-4 border-l-green-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-500 text-[10px] font-bold uppercase tracking-widest">Safe Days Streak</h3>
                    <div class="p-2 bg-green-500/10 text-green-400 rounded-lg"><i class="fas fa-calendar-check"></i></div>
                </div>
                <p class="text-3xl font-black text-white">
                    <?= $daysSafe ?>
                </p>
                <span class="text-[10px] text-gray-600 font-bold uppercase tracking-widest">Days since last incident</span>
            </div>
            <div class="bg-gray-900 p-5 rounded-2xl shadow-2xl border border-gray-800 border-l-4 border-l-blue-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-500 text-[10px] font-bold uppercase tracking-widest">Open Cases</h3>
                    <div class="p-2 bg-blue-500/10 text-blue-400 rounded-lg"><i class="fas fa-folder-open"></i></div>
                </div>
                <p class="text-3xl font-black text-white">
                    <?= $openCases ?>
                </p>
                <span class="text-[10px] text-blue-500 font-bold uppercase tracking-widest">Active investigations</span>
            </div>
            <div class="bg-gray-900 p-5 rounded-2xl shadow-2xl border border-gray-800 border-l-4 border-l-red-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-500 text-[10px] font-bold uppercase tracking-widest">Severe Incidents</h3>
                    <div class="p-2 bg-red-500/10 text-red-500 rounded-lg"><i class="fas fa-biohazard"></i></div>
                </div>
                <p class="text-3xl font-black text-white">
                    <?= $severeIncidents ?>
                </p>
                <span class="text-[10px] text-red-500 font-bold uppercase tracking-widest">High/Critical priority</span>
            </div>
            <div class="bg-gray-900 p-5 rounded-2xl shadow-2xl border border-gray-800 border-l-4 border-l-gray-500 transition-all hover:translate-y-[-2px]">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-gray-500 text-[10px] font-bold uppercase tracking-widest">Total Incidents</h3>
                    <div class="p-2 bg-gray-800 text-gray-400 rounded-lg"><i class="fas fa-history"></i></div>
                </div>
                <p class="text-3xl font-black text-white">
                    <?= $totalIncidents ?>
                </p>
                <span class="text-[10px] text-gray-600 font-bold uppercase tracking-widest">Recorded All-time</span>
            </div>
        </div>

        <!-- Recent Incidents Feed -->
        <div class="bg-gray-900 rounded-2xl shadow-2xl border border-gray-800 overflow-hidden">
            <div class="p-6 border-b border-gray-800 flex flex-col sm:flex-row justify-between items-center bg-gray-950/50 gap-4">
                <h2 class="text-lg font-black text-white flex items-center gap-2 uppercase tracking-tight">
                    <i class="fas fa-history text-indigo-500"></i> Recent Safety Incidents
                </h2>
                <form class="relative w-full sm:w-64">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search incidents..."
                        class="w-full pl-9 pr-4 py-2 text-xs bg-gray-950 border border-gray-800 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-white placeholder-gray-600">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-600 text-xs"></i>
                </form>
            </div>

            <div class="divide-y divide-gray-800">
                <?php if (count($incidents) > 0): ?>
                    <?php foreach ($incidents as $inc): ?>
                        <div class="p-6 hover:bg-white/[0.02] transition-colors border-l-4 border-l-<?= $inc['severity'] ?>">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-black text-white text-lg uppercase tracking-tight">
                                    <?= htmlspecialchars($inc['employee_name']) ?>
                                </h3>
                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest bg-gray-950 border border-gray-800 px-2.5 py-1 rounded">
                                    <?= date('M d, Y h:i A', strtotime($inc['incident_date'])) ?>
                                </span>
                            </div>
                            <p class="text-gray-400 text-sm mb-4 leading-relaxed font-light">
                                <?= htmlspecialchars($inc['incident_details']) ?>
                            </p>
                            <div class="flex flex-wrap gap-2 text-[10px] font-black uppercase tracking-widest">
                                <!-- Incident Type Badge -->
                                <span class="px-2.5 py-1 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20">
                                    <?= htmlspecialchars($inc['incident_type']) ?>
                                </span>

                                <!-- Severity Badge -->
                                <?php
                                $sevClass = match ($inc['severity']) {
                                    'Low' => 'bg-green-500/10 text-green-400 border-green-500/20',
                                    'Medium' => 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20',
                                    'High' => 'bg-orange-500/10 text-orange-500 border-orange-500/20',
                                    'Critical' => 'bg-red-500/10 text-red-500 border-red-500/20',
                                    default => 'bg-gray-800 text-gray-400'
                                };
                                ?>
                                <span class="px-2.5 py-1 rounded border <?= $sevClass ?>">
                                    <?= htmlspecialchars($inc['severity']) ?> Priority
                                </span>

                                <!-- Location Badge -->
                                <span class="px-2.5 py-1 rounded bg-purple-500/10 text-purple-400 border border-purple-500/20">
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
        class="flex fixed inset-0 bg-black bg-opacity-80 z-50 hidden items-center justify-center p-4 backdrop-blur-md opacity-0 transition-opacity duration-300">
        <div
            class="bg-gray-900 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-transform duration-300 border border-gray-800">
            <div class="px-6 py-4 border-b border-gray-800 flex justify-between items-center bg-gray-950">
                <h3 class="font-black text-lg text-white uppercase tracking-tight">Report New Incident</h3>
                <button onclick="closeModal('reportModal')" class="text-gray-400 hover:text-white transition-colors"><i
                        class="fas fa-times"></i></button>
            </div>

            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="report_incident">

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Employee Involved</label>
                    <input type="text" name="employee_name" required
                        class="w-full px-4 py-2.5 bg-gray-950 border border-gray-800 rounded-lg focus:ring-2 focus:ring-red-500 focus:bg-black transition-all outline-none text-xs text-white placeholder-gray-700"
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
                            class="w-full px-4 py-2.5 bg-gray-950 border border-gray-800 rounded-lg focus:ring-2 focus:ring-red-500 focus:bg-black transition-all outline-none text-xs text-white">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Incident Details</label>
                    <textarea name="incident_details" rows="3" required
                        class="w-full px-4 py-3 bg-gray-950 border border-gray-800 rounded-xl focus:ring-2 focus:ring-red-500 focus:bg-black transition-all outline-none text-xs text-white placeholder-gray-700 resize-none"
                        placeholder="Describe what happened..."></textarea>
                </div>

                <div class="pt-6 border-t border-gray-800 flex justify-end gap-3 bg-gray-950 -mx-6 -mb-6 p-6 mt-6">
                    <button type="button" onclick="closeModal('reportModal')"
                        class="px-6 py-2.5 rounded-lg text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:bg-gray-800 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-8 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest bg-red-600 text-white hover:bg-red-700 shadow-lg shadow-red-950/40 transition-all">Submit
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