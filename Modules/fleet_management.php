<?php
session_start();

// Use a relative path to go up one directory to the root 'hr1' folder
include("../Database/Connections.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['Email']) || (isset($_SESSION['Account_type']) && $_SESSION['Account_type'] !== '1')) {
    header("Location: ../login.php");
    exit();
}

// Ensure fleet_management table exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS fleet_management (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        vehicle_unit VARCHAR(255) NOT NULL,
        v_model VARCHAR(255) NOT NULL,
        plate_number VARCHAR(100) NOT NULL,
        driver_name VARCHAR(255) NOT NULL,
        contact_no VARCHAR(100),
        status ENUM('Available', 'On Trip', 'Under Maintenance', 'Out of Service') DEFAULT 'Available',
        last_destination VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Initial dummy data if table is empty
    $check = $conn->query("SELECT COUNT(*) FROM fleet_management")->fetchColumn();
    if ($check == 0) {
        $conn->exec("INSERT INTO fleet_management (vehicle_unit, v_model, plate_number, driver_name, contact_no, status, last_destination) VALUES 
            ('Tower Crane 01', 'Zoomlion TC6012', 'TC-01-CALI', 'Juan Dela Cruz', '09123456789', 'Available', 'Warehouse A'),
            ('Mobile Crane 05', 'Sany STC500', 'MC-05-CALI', 'Pedro Penduko', '09223334444', 'On Trip', 'QC Site Project'),
            ('Transport Truck 02', 'Isuzu Giga', 'TT-02-CALI', 'Ricardo Dalisay', '09556667777', 'Under Maintenance', 'Cali Crane Yard')");
    }
} catch (Exception $e) {
    // Silent fail or log
}

// --- AJAX HANDLER: Get Fleet Details ---
if (isset($_GET['action']) && $_GET['action'] === 'get_fleet_info' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $conn->prepare("SELECT * FROM fleet_management WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $fleet = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $fleet]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// --- AJAX HANDLER: Add / Update Fleet ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
        if ($action === 'save_fleet') {
            $id = $_POST['fleet_id'] ?? null;
            $unit = $_POST['vehicle_unit'];
            $model = $_POST['v_model'];
            $plate = $_POST['plate_number'];
            $driver = $_POST['driver_name'];
            $contact = $_POST['contact_no'];
            $status = $_POST['status'];
            $dest = $_POST['last_destination'];

            if ($id) {
                $stmt = $conn->prepare("UPDATE fleet_management SET vehicle_unit=?, v_model=?, plate_number=?, driver_name=?, contact_no=?, status=?, last_destination=? WHERE id=?");
                $stmt->execute([$unit, $model, $plate, $driver, $contact, $status, $dest, $id]);
                $msg = "Fleet record updated!";
            } else {
                $stmt = $conn->prepare("INSERT INTO fleet_management (vehicle_unit, v_model, plate_number, driver_name, contact_no, status, last_destination) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$unit, $model, $plate, $driver, $contact, $status, $dest]);
                $msg = "New fleet record added!";
            }
            echo json_encode(['status' => 'success', 'message' => $msg]);
        } elseif ($action === 'delete_fleet') {
            $stmt = $conn->prepare("DELETE FROM fleet_management WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            echo json_encode(['status' => 'success', 'message' => 'Record deleted!']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit();
}

// Fetch all records
$fleets = $conn->query("SELECT * FROM fleet_management ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Management - HR Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary-color: #111827;
            --background-dark: #111827;
            --card-bg: #1f2937;
            --text-light: #f3f4f6;
            --text-medium: #9ca3af;
            --border-color: #374151;
        }

        body {
            background-color: #f8fafc;
            display: flex;
            font-family: "Poppins", sans-serif;
            color: #1e293b;
        }

        .main-content {
            margin-left: 16rem; /* fixed sidebar width */
            width: calc(100% - 16rem);
        }

        .status-badge {
            font-size: 10px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 100px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .status-available { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bcf0da; }
        .status-ontrip { background-color: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
        .status-maintenance { background-color: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
        .status-out { background-color: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

        .fleet-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .fleet-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            color: white;
            z-index: 9999;
            transition: all 0.5s ease;
            opacity: 0;
            transform: translateX(100%);
            backdrop-filter: blur(8px);
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>

<body class="bg-slate-50">
    <?php
    $root_path = '../';
    include '../Components/sidebar_admin.php';
    include '../Components/header_admin.php';
    ?>

    <div class="main-content min-h-screen pt-24 pb-12 px-8">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
            <div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">Fleet <span class="text-indigo-600">Command</span></h1>
                <p class="text-xs text-slate-500 mt-1 font-medium tracking-wide">Manage driver logs and vehicle deployment for Cali Crane</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="hidden lg:flex items-center gap-2 px-4 py-2 bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span id="liveClock" class="text-[11px] font-bold text-slate-600 uppercase tracking-widest"></span>
                </div>
                <button onclick="openFleetModal()" class="flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-2xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 text-xs font-bold uppercase tracking-widest">
                    <i class="fas fa-plus"></i>
                    Add Vehicle
                </button>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <?php
            $stats = [
                ['label' => 'Total Fleet', 'value' => count($fleets), 'icon' => 'fa-truck-loading', 'color' => 'indigo'],
                ['label' => 'Active On Trip', 'value' => 0, 'icon' => 'fa-route', 'color' => 'blue'],
                ['label' => 'Available', 'value' => 0, 'icon' => 'fa-check-circle', 'color' => 'emerald'],
                ['label' => 'Maintenance', 'value' => 0, 'icon' => 'fa-tools', 'color' => 'amber']
            ];
            
            foreach($fleets as $f) {
                if($f['status'] == 'On Trip') $stats[1]['value']++;
                if($f['status'] == 'Available') $stats[2]['value']++;
                if($f['status'] == 'Under Maintenance') $stats[3]['value']++;
            }
            
            foreach($stats as $stat): ?>
                <div class="bg-white p-6 rounded-[24px] border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1"><?= $stat['label'] ?></p>
                        <h3 class="text-2xl font-black text-slate-900"><?= $stat['value'] ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-<?= $stat['color'] ?>-50 text-<?= $stat['color'] ?>-600 flex items-center justify-center text-xl">
                        <i class="fas <?= $stat['icon'] ?>"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Fleet Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($fleets)): ?>
                <div class="col-span-full py-20 text-center">
                    <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-truck-monster text-slate-300 text-3xl"></i>
                    </div>
                    <h3 class="text-slate-500 font-bold uppercase tracking-widest text-sm">No vehicles tracked yet</h3>
                    <p class="text-slate-400 text-xs mt-1">Start by adding a new vehicle to the fleet.</p>
                </div>
            <?php else: foreach ($fleets as $fleet): ?>
                <div class="fleet-card bg-white rounded-[32px] p-6 border border-slate-100 shadow-sm group">
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300 shadow-inner">
                                <i class="fas fa-truck-moving"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 group-hover:text-indigo-600 transition-colors uppercase tracking-tight"><?= htmlspecialchars($fleet['vehicle_unit']) ?></h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($fleet['plate_number']) ?></p>
                            </div>
                        </div>
                        <?php 
                        $statusClass = 'status-available';
                        if($fleet['status'] == 'On Trip') $statusClass = 'status-ontrip';
                        if($fleet['status'] == 'Under Maintenance') $statusClass = 'status-maintenance';
                        if($fleet['status'] == 'Out of Service') $statusClass = 'status-out';
                        ?>
                        <span class="status-badge <?= $statusClass ?>"><?= $fleet['status'] ?></span>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-bold uppercase tracking-widest">Model</span>
                            <span class="text-slate-900 font-bold"><?= htmlspecialchars($fleet['v_model']) ?></span>
                        </div>
                        <div class="flex items-center justify-between text-xs border-t border-slate-50 pt-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center overflow-hidden border border-slate-200 shadow-sm">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($fleet['driver_name']) ?>&background=random" alt="Driver">
                                </div>
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Assigned Driver</p>
                                    <p class="text-slate-900 font-bold"><?= htmlspecialchars($fleet['driver_name']) ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Contact</p>
                                <p class="text-slate-900 font-bold italic"><?= htmlspecialchars($fleet['contact_no']) ?></p>
                            </div>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 flex items-center gap-1">
                                <i class="fas fa-location-dot text-indigo-500"></i> Current Location / Dest.
                            </p>
                            <p class="text-slate-700 font-bold text-xs truncate uppercase tracking-tight"><?= $fleet['last_destination'] ?: 'Not Specified' ?></p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button onclick="editFleet(<?= $fleet['id'] ?>)" class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-900 hover:text-white transition-all text-[10px] font-black uppercase tracking-[0.2em] border border-slate-200">
                            Manage Log
                        </button>
                        <button onclick="deleteFleet(<?= $fleet['id'] ?>)" class="w-12 h-12 flex items-center justify-center bg-red-50 text-red-500 rounded-xl hover:bg-red-500 hover:text-white transition-all border border-red-100">
                            <i class="fas fa-trash-alt text-xs"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="fleetModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden z-[9999] flex items-center justify-center p-4">
        <div class="bg-white rounded-[40px] shadow-2xl max-w-2xl w-full border border-slate-100 overflow-hidden transform transition-all duration-300 scale-90 opacity-0" id="modalContainer">
            <div class="flex items-center justify-between p-8 border-b border-slate-50">
                <div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight uppercase" id="modalTitle">Vehicle Registry</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Cali Crane Fleet System</p>
                </div>
                <button onclick="closeFleetModal()" class="w-10 h-10 rounded-full hover:bg-slate-50 text-slate-400 transition-colors flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="fleetForm" class="p-8">
                <input type="hidden" name="fleet_id" id="fleet_id">
                <input type="hidden" name="action" value="save_fleet">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Vehicle Unit / Identification</label>
                        <input type="text" name="vehicle_unit" id="vehicle_unit" required class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-4 focus:ring-indigo-100 transition-all font-bold text-slate-700 placeholder:text-slate-300" placeholder="e.g., Tower Crane 01">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Vehicle Model / Specs</label>
                        <input type="text" name="v_model" id="v_model" required class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-4 focus:ring-indigo-100 transition-all font-bold text-slate-700 placeholder:text-slate-300" placeholder="e.g., Zoomlion TC6012">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Plate Number / Tracking ID</label>
                        <input type="text" name="plate_number" id="plate_number" required class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-4 focus:ring-indigo-100 transition-all font-bold text-slate-700 placeholder:text-slate-300" placeholder="e.g., ABC 1234">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Current Status</label>
                        <select name="status" id="status" class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-4 focus:ring-indigo-100 transition-all font-bold text-slate-700 appearance-none">
                            <option value="Available">🟢 Available / Yard</option>
                            <option value="On Trip">🔵 On Trip / Deployed</option>
                            <option value="Under Maintenance">🟠 Maintenance</option>
                            <option value="Out of Service">🔴 Out of Service</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Assigned Driver</label>
                        <input type="text" name="driver_name" id="driver_name" required class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-4 focus:ring-indigo-100 transition-all font-bold text-slate-700 placeholder:text-slate-300" placeholder="Full Name">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Driver Contact #</label>
                        <input type="text" name="contact_no" id="contact_no" class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-4 focus:ring-indigo-100 transition-all font-bold text-slate-700 placeholder:text-slate-300" placeholder="Phone Number">
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Destination / Current Site</label>
                    <input type="text" name="last_destination" id="last_destination" class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-4 focus:ring-indigo-100 transition-all font-bold text-slate-700 placeholder:text-slate-300" placeholder="e.g., QC Housing Project B">
                </div>

                <div class="flex gap-3 justify-end pt-4">
                    <button type="button" onclick="closeFleetModal()" class="px-8 py-4 text-slate-400 font-bold uppercase tracking-widest text-[10px] hover:text-slate-900 transition-colors">Discard</button>
                    <button type="submit" class="px-10 py-4 bg-indigo-600 text-white rounded-2xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 font-black uppercase tracking-widest text-[10px]">Confirm Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('fleetModal');
        const container = document.getElementById('modalContainer');
        const form = document.getElementById('fleetForm');

        function openFleetModal() {
            document.getElementById('fleet_id').value = '';
            form.reset();
            document.getElementById('modalTitle').textContent = 'Add New Vehicle';
            modal.classList.remove('hidden');
            setTimeout(() => {
                container.classList.remove('scale-90', 'opacity-0');
            }, 10);
        }

        function closeFleetModal() {
            container.classList.add('scale-90', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function editFleet(id) {
            fetch(`?action=get_fleet_info&id=${id}`)
                .then(res => res.json())
                .then(res => {
                    if(res.status === 'success') {
                        const d = res.data;
                        document.getElementById('fleet_id').value = d.id;
                        document.getElementById('vehicle_unit').value = d.vehicle_unit;
                        document.getElementById('v_model').value = d.v_model;
                        document.getElementById('plate_number').value = d.plate_number;
                        document.getElementById('driver_name').value = d.driver_name;
                        document.getElementById('contact_no').value = d.contact_no;
                        document.getElementById('status').value = d.status;
                        document.getElementById('last_destination').value = d.last_destination;
                        
                        document.getElementById('modalTitle').textContent = 'Edit Fleet Log';
                        modal.classList.remove('hidden');
                        setTimeout(() => {
                            container.classList.remove('scale-90', 'opacity-0');
                        }, 10);
                    }
                });
        }

        form.onsubmit = (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            fetch('', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    showNotification(res.message, res.status);
                    if(res.status === 'success') {
                        setTimeout(() => location.reload(), 1500);
                    }
                });
        };

        function deleteFleet(id) {
            if(confirm('Archive this vehicle record?')) {
                const formData = new FormData();
                formData.append('action', 'delete_fleet');
                formData.append('id', id);
                fetch('', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(res => {
                        showNotification(res.message, res.status);
                        if(res.status === 'success') {
                            setTimeout(() => location.reload(), 1500);
                        }
                    });
            }
        }

        function showNotification(message, type = 'success') {
            const notif = document.createElement('div');
            notif.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center bg-white/20">
                        <i class="fas ${type === 'success' ? 'fa-check' : 'fa-exclamation-triangle'}"></i>
                    </div>
                    <span class="font-bold text-xs uppercase tracking-widest">${message}</span>
                </div>
            `;
            notif.className = `notification ${type === 'success' ? 'bg-indigo-600' : 'bg-red-500'} show shadow-2xl`;
            document.body.appendChild(notif);
            setTimeout(() => {
                notif.classList.remove('show');
                setTimeout(() => notif.remove(), 500);
            }, 3000);
        }

        function updateClock() {
            const now = new Date();
            document.getElementById('liveClock').textContent = now.toLocaleString('en-US', { 
                weekday: 'long', 
                month: 'short', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit' 
            });
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
