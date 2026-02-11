<?php
session_start();
include("../Database/Connections.php");

if (!isset($_SESSION['Email'])) {
    header('Location: ../login.php');
    exit;
}

// Report data: all recognitions grouped by employee (who received)
$report = [];
try {
    $tables_check = $conn->query("SHOW TABLES LIKE 'recognitions'");
    if ($tables_check->rowCount() > 0) {
        $sql = "
            SELECT r.id, r.to_employee_id, r.title, r.message, r.recognition_date,
                   e1.name as from_name,
                   e2.name as to_name, e2.position as to_position,
                   rc.name as category_name, rc.icon as category_icon, rc.color as category_color
            FROM recognitions r
            JOIN employees e1 ON r.from_employee_id = e1.id
            JOIN employees e2 ON r.to_employee_id = e2.id
            JOIN recognition_categories rc ON r.category_id = rc.id
            ORDER BY e2.name ASC, r.recognition_date DESC
        ";
        $stmt = $conn->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as $row) {
            $id = $row['to_employee_id'];
            if (!isset($report[$id])) {
                $report[$id] = [
                    'employee_name' => $row['to_name'],
                    'position'     => $row['to_position'] ?? '—',
                    'recognitions' => []
                ];
            }
            $report[$id]['recognitions'][] = $row;
        }
    }
} catch (Exception $e) {
    $report = [];
}
$report = array_values($report);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recognition Report by Employee - HR1</title>
    <link rel="icon" type="image/x-icon" href="../Image/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .print-break { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Actions (hidden when printing) -->
        <div class="no-print flex flex-wrap items-center justify-between gap-4 mb-6">
            <a href="recognition.php" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 font-medium">
                <i class="fas fa-arrow-left"></i> Back to Recognition
            </a>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 text-white rounded-lg hover:bg-black font-medium">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>

        <!-- Report content -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-8 border-b border-gray-100">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-trophy text-amber-500"></i>
                    Employee Recognition Report
                </h1>
                <p class="text-gray-500 text-sm mt-1">Generated on <?= date('F j, Y \a\t g:i A') ?></p>
            </div>

            <div class="p-8">
                <?php if (empty($report)): ?>
                    <p class="text-gray-500 text-center py-12">No recognition data to display. Give recognitions first.</p>
                <?php else: ?>
                    <?php foreach ($report as $group): ?>
                        <div class="print-break mb-10 last:mb-0">
                            <div class="flex items-center gap-3 mb-4 pb-2 border-b-2 border-gray-900">
                                <div class="w-10 h-10 rounded-full bg-gray-900 text-white flex items-center justify-center font-bold text-sm">
                                    <?= strtoupper(mb_substr($group['employee_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($group['employee_name']) ?></h2>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($group['position']) ?></p>
                                </div>
                                <span class="ml-auto text-sm font-semibold text-gray-600">
                                    <?= count($group['recognitions']) ?> recognition<?= count($group['recognitions']) !== 1 ? 's' : '' ?>
                                </span>
                            </div>

                            <ul class="space-y-4">
                                <?php foreach ($group['recognitions'] as $rec): ?>
                                    <li class="pl-4 border-l-4 border-gray-200 print-break">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-semibold"
                                                  style="background-color: <?= htmlspecialchars($rec['category_color'] ?? '#333') ?>20; color: <?= htmlspecialchars($rec['category_color'] ?? '#333') ?>;">
                                                <i class="<?= htmlspecialchars($rec['category_icon'] ?? 'fa-star') ?>"></i>
                                                <?= htmlspecialchars($rec['category_name']) ?>
                                            </span>
                                            <span class="text-xs text-gray-400">
                                                <?= date('M j, Y', strtotime($rec['recognition_date'])) ?>
                                                · from <?= htmlspecialchars($rec['from_name']) ?>
                                            </span>
                                        </div>
                                        <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($rec['title']) ?></h3>
                                        <p class="text-sm text-gray-600 mt-1"><?= nl2br(htmlspecialchars($rec['message'])) ?></p>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
