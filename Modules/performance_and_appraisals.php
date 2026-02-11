<?php
session_start();

// Use a relative path to go up one directory to the root 'hr1' folder
include("../Database/Connections.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['Email']) || (isset($_SESSION['Account_type']) && $_SESSION['Account_type'] !== '1')) {
    header("Location: ../login.php");
    exit();
}

// Ensure promotion_reviews table exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS promotion_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        current_position VARCHAR(255),
        target_position VARCHAR(255),
        promotion_status ENUM('Pending Review', 'Ready for Promotion', 'Promoted', 'Needs Improvement') DEFAULT 'Pending Review',
        review_notes TEXT,
        reviewed_by VARCHAR(255),
        review_date DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    )");
} catch (Exception $e) {
    // Silent fail if exists
}

// --- AJAX HANDLER: Get Employee Promotion Details ---
if (isset($_GET['action']) && $_GET['action'] === 'get_promotion_details' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    try {
        // Fetch employee details
        $stmt_emp = $conn->prepare("SELECT id, name, position, photo_path FROM employees WHERE id = ?");
        $stmt_emp->execute([$_GET['id']]);
        $employee = $stmt_emp->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            echo json_encode(['status' => 'error', 'message' => 'Employee not found']);
            exit();
        }

        // Fetch the MOST RECENT promotion review
        $stmt_promo = $conn->prepare("SELECT * FROM promotion_reviews WHERE employee_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt_promo->execute([$_GET['id']]);
        $review = $stmt_promo->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => ['employee' => $employee, 'review' => $review]]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// --- AJAX HANDLER: Submit Promotion Review / Promote ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
        if ($action === 'submit_review') {
            $employee_id = $_POST['employee_id'];
            $current_position = $_POST['current_position'];
            $target_position = $_POST['target_position'];
            $review_notes = $_POST['review_notes'];
            $status = $_POST['status']; // 'Ready for Promotion', 'Needs Improvement', etc.
            $reviewed_by = $_SESSION['GlobalName'] ?? 'Admin';

            // Insert Review
            $stmt = $conn->prepare("INSERT INTO promotion_reviews 
                (employee_id, current_position, target_position, promotion_status, review_notes, reviewed_by, review_date) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$employee_id, $current_position, $target_position, $status, $review_notes, $reviewed_by]);

            // If "Promoted" is selected, actually update the employee's position
            if ($status === 'Promoted') {
                $updateStmt = $conn->prepare("UPDATE employees SET position = ? WHERE id = ?");
                $updateStmt->execute([$target_position, $employee_id]);
                $msg = "Employee successfully promoted to " . $target_position . "!";
            } else {
                $msg = "Promotion review submitted successfully.";
            }

            echo json_encode(['status' => 'success', 'message' => $msg]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Fetch Employees with their latest promotion status
try {
    $employees = $conn->query("
        SELECT e.id, e.name, e.position, e.photo_path, 
               pr.promotion_status, pr.target_position, pr.review_date
        FROM employees e
        LEFT JOIN promotion_reviews pr ON pr.id = (
            SELECT id FROM promotion_reviews 
            WHERE employee_id = e.id 
            ORDER BY created_at DESC 
            LIMIT 1
        )
        WHERE e.status = 'active'
        ORDER BY e.name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $employees = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Promotion - HR Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #f8fafc; font-family: "Poppins", sans-serif; }
        .status-badge { font-size: 10px; font-weight: 800; padding: 4px 12px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.05em; }
        .bg-promoted { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .bg-ready { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .bg-pending { background-color: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
        .bg-improvement { background-color: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        
        .notification { position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem; border-radius: 12px; color: white; z-index: 9999; transition: all 0.3s ease; opacity: 0; transform: translateX(100%); }
        .notification.show { opacity: 1; transform: translateX(0); }
    </style>
</head>

<body class="text-slate-800">
    <?php
    $root_path = '../';
    include '../Components/sidebar_admin.php';
    include '../Components/header_admin.php';
    ?>

    <div class="ml-64 p-8 pt-24 min-h-screen">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight uppercase">Career <span class="text-indigo-600">Promotion</span></h1>
                <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Employee Advancement System</p>
            </div>
            <div class="bg-white px-6 py-2 rounded-xl shadow-sm border border-slate-100 flex items-center gap-3">
                <i class="fas fa-calendar-alt text-indigo-500"></i>
                <span class="text-xs font-bold text-slate-600 uppercase tracking-widest"><?= date('F d, Y') ?></span>
            </div>
        </div>

        <div class="bg-white rounded-[24px] shadow-xl shadow-slate-200/50 overflow-hidden border border-slate-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Employee</th>
                            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Current Role</th>
                            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Target Role</th>
                            <th class="px-8 py-6 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                            <th class="px-8 py-6 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if (empty($employees)): ?>
                            <tr><td colspan="5" class="text-center py-12 text-slate-400 text-xs uppercase tracking-widest">No employees found</td></tr>
                        <?php else: foreach ($employees as $emp): 
                            $status = $emp['promotion_status'] ?? 'Pending Review';
                            $statusClass = 'bg-pending';
                            if ($status == 'Promoted') $statusClass = 'bg-promoted';
                            if ($status == 'Ready for Promotion') $statusClass = 'bg-ready';
                            if ($status == 'Needs Improvement') $statusClass = 'bg-improvement';
                            
                            $photo = !empty($emp['photo_path']) ? "../" . htmlspecialchars($emp['photo_path']) : "https://ui-avatars.com/api/?name=" . urlencode($emp['name']) . "&background=random";
                        ?>
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-8 py-4">
                                    <div class="flex items-center gap-4">
                                        <img src="<?= $photo ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shadow-sm" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($emp['name']) ?>&background=random'">
                                        <span class="font-bold text-sm text-slate-900"><?= htmlspecialchars($emp['name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-4 text-xs font-semibold text-slate-500"><?= htmlspecialchars($emp['position']) ?></td>
                                <td class="px-8 py-4 text-xs font-bold text-indigo-600">
                                    <?= $emp['target_position'] ? htmlspecialchars($emp['target_position']) : '<span class="text-slate-300 italic">--</span>' ?>
                                </td>
                                <td class="px-8 py-4 text-center">
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= $status ?>
                                    </span>
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <button onclick="openPromoModal(<?= $emp['id'] ?>)" 
                                        class="px-4 py-2 bg-slate-900 text-white text-[10px] font-bold uppercase tracking-widest rounded-lg hover:bg-indigo-600 transition-all shadow-lg shadow-slate-200">
                                        Evaluate
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="promoModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden z-[9999] flex items-center justify-center p-4">
        <div class="bg-white rounded-[24px] shadow-2xl max-w-lg w-full transform transition-all scale-95 opacity-0" id="modalContent">
            <div class="p-8 border-b border-slate-50 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-black text-slate-900 uppercase tracking-tight">Promotion Review</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Assess career advancement</p>
                </div>
                <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-slate-100 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="promoForm" class="p-8 space-y-6">
                <input type="hidden" name="action" value="submit_review">
                <input type="hidden" name="employee_id" id="modalEmpId">
                <input type="hidden" name="current_position" id="modalCurrentPos">

                <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <img id="modalImg" class="w-16 h-16 rounded-2xl object-cover bg-white border border-slate-200 shadow-sm" src="">
                    <div>
                        <h4 id="modalName" class="text-lg font-black text-slate-900"></h4>
                        <p id="modalPosDisplay" class="text-xs font-bold text-slate-500 uppercase tracking-wide"></p>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Target Promotion Role</label>
                    <input type="text" name="target_position" id="targetPos" list="roles" required
                        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl font-bold text-slate-700 text-sm focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all placeholder:text-slate-300" 
                        placeholder="e.g. Supervisor, Manager...">
                    <datalist id="roles">
                        <option value="Supervisor">
                        <option value="Senior Staff">
                        <option value="Team Leader">
                        <option value="Manager">
                        <option value="Director">
                    </datalist>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Promotion Status</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="Ready for Promotion" class="peer sr-only">
                            <div class="text-center py-3 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-500 peer-checked:bg-blue-50 peer-checked:text-blue-600 peer-checked:border-blue-500 transition-all hover:bg-slate-50">
                                <i class="fas fa-star mr-1"></i> Ready
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="Promoted" class="peer sr-only">
                            <div class="text-center py-3 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-500 peer-checked:bg-green-50 peer-checked:text-green-600 peer-checked:border-green-500 transition-all hover:bg-slate-50">
                                <i class="fas fa-check-double mr-1"></i> Promote Now
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="Needs Improvement" class="peer sr-only">
                            <div class="text-center py-3 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-500 peer-checked:bg-orange-50 peer-checked:text-orange-600 peer-checked:border-orange-500 transition-all hover:bg-slate-50">
                                <i class="fas fa-tools mr-1"></i> Improve
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="Pending Review" class="peer sr-only">
                            <div class="text-center py-3 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-500 peer-checked:bg-slate-100 peer-checked:text-slate-700 peer-checked:border-slate-400 transition-all hover:bg-slate-50">
                                <i class="fas fa-clock mr-1"></i> Pending
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Evaluation Notes</label>
                    <textarea name="review_notes" id="reviewNotes" rows="3" 
                        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl font-medium text-slate-600 text-sm focus:ring-4 focus:ring-indigo-50 focus:border-indigo-500 transition-all placeholder:text-slate-300"
                        placeholder="Justification for promotion..."></textarea>
                </div>

                <button type="submit" class="w-full py-4 bg-slate-900 text-white rounded-xl font-black uppercase tracking-widest text-xs hover:bg-indigo-600 transition-all shadow-xl shadow-indigo-200">
                    Submit Evaluation
                </button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('promoModal');
        const modalContent = document.getElementById('modalContent');
        const form = document.getElementById('promoForm');

        function openPromoModal(id) {
            fetch(`?action=get_promotion_details&id=${id}`)
                .then(res => res.json())
                .then(res => {
                    if(res.status === 'success') {
                        const emp = res.data.employee;
                        const review = res.data.review;

                        document.getElementById('modalEmpId').value = emp.id;
                        document.getElementById('modalCurrentPos').value = emp.position;
                        document.getElementById('modalName').textContent = emp.name;
                        document.getElementById('modalPosDisplay').textContent = emp.position;
                        
                        let photo = emp.photo_path ? "../" + emp.photo_path : `https://ui-avatars.com/api/?name=${encodeURIComponent(emp.name)}&background=random`;
                        document.getElementById('modalImg').src = photo;

                        // Reset Form
                        document.getElementById('targetPos').value = '';
                        document.getElementById('reviewNotes').value = '';
                        const radios = document.getElementsByName('status');
                        for(let r of radios) r.checked = false;

                        // Fill if review exists
                        if(review) {
                            document.getElementById('targetPos').value = review.target_position || '';
                            document.getElementById('reviewNotes').value = review.review_notes || '';
                            for(let r of radios) {
                                if(r.value === review.promotion_status) r.checked = true;
                            }
                        }

                        modal.classList.remove('hidden');
                        setTimeout(() => {
                            modalContent.classList.remove('scale-95', 'opacity-0');
                        }, 10);
                    }
                });
        }

        function closeModal() {
            modalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
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

        function showNotification(message, type = 'success') {
            const notif = document.createElement('div');
            notif.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center bg-white/20">
                        <i class="fas ${type === 'success' ? 'fa-check' : 'fa-exclamation'}"></i>
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
    </script>
</body>
</html>