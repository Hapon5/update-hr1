<?php
session_start();
include '../../Database/Connections.php';

// Migration for interviews table
try { $conn->exec("ALTER TABLE interviews ADD COLUMN is_archived TINYINT(1) DEFAULT 0"); } catch (Exception $e) {}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../../PHPMailer/src/Exception.php';
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';

function sendInterviewEmail($toEmail, $toName, $subject, $body)
{
    $mail = new PHPMailer(true);
    try {
        // Note: Update these with real SMTP credentials
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'linbilcelestre31@gmail.com';
        $mail->Password = 'oothfogbgznnfkdp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Bypassing SSL verification
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('linbilcelestre31@gmail.com', 'HR1-CRANE Recruitment');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        $_SESSION['last_mail_error'] = $mail->ErrorInfo;
        return false;
    }
}

// Helper for safe counts
function getCount($conn, $sql, $params = [])
{
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// Stats & Dropdown queries moved to bottom to optimize AJAX performance

// AJAX Handlers
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'get_events') {
        $stmt = $conn->query("SELECT id, candidate_name as title, start_time as start, end_time as end, status, interview_type FROM interviews WHERE is_archived = 0");
        $events = $stmt->fetchAll();
        foreach ($events as &$event) {
            $event['color'] = $event['status'] === 'completed' ? '#10b981' : ($event['status'] === 'cancelled' ? '#ef4444' : '#6366f1');
        }
        echo json_encode($events);
        exit();
    }

    if ($_GET['action'] === 'get_interview' && isset($_GET['id'])) {
        $stmt = $conn->prepare("SELECT * FROM interviews WHERE id = ? AND is_archived = 0");
        $stmt->execute([$_GET['id']]);
        echo json_encode($stmt->fetch());
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $data = $_POST;

    try {
        if (isset($data['action']) && $data['action'] === 'save_interview') {
            $id = $data['id'] ?? null;
            $candidate_id = $data['candidate_id'] ?: null;
            $candidate_name = $data['candidate_name'];
            $email = $data['email'];
            $position = $data['position'];
            $interviewer = $data['interviewer']; // Could be multiple comma separated
            $start_time = $data['start_time'];
            $end_time = $data['end_time'];
            $location = $data['location'];
            $interview_type = $data['interview_type'];
            $meeting_link = $data['meeting_link'] ?? '';
            $status = $data['status'] ?? 'scheduled';
            $notes = $data['notes'] ?? '';

            if ($id) {
                $sql = "UPDATE interviews SET candidate_id=?, candidate_name=?, email=?, position=?, interviewer=?, start_time=?, end_time=?, location=?, interview_type=?, meeting_link=?, status=?, notes=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$candidate_id, $candidate_name, $email, $position, $interviewer, $start_time, $end_time, $location, $interview_type, $meeting_link, $status, $notes, $id]);
                $msg = 'Interview updated successfully';
            } else {
                $sql = "INSERT INTO interviews (candidate_id, candidate_name, email, position, interviewer, start_time, end_time, location, interview_type, meeting_link, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$candidate_id, $candidate_name, $email, $position, $interviewer, $start_time, $end_time, $location, $interview_type, $meeting_link, $status, $notes]);
                $msg = 'Interview scheduled successfully';
            }

            // Send Notification if checked
            if (isset($data['send_notification']) && $data['send_notification'] == '1') {
                $date = date('M d, Y', strtotime($start_time));
                $time = date('h:i A', strtotime($start_time));
                $subject = "Interview Invitation: " . $position;
                $body = "
                    <h1>Interview Invitation</h1>
                    <p>Dear {$candidate_name},</p>
                    <p>We are pleased to invite you for an interview for the <strong>{$position}</strong> position.</p>
                    <p><strong>Date:</strong> {$date}<br>
                    <strong>Time:</strong> {$time}<br>
                    <strong>Type:</strong> {$interview_type}<br>
                    <strong>Location/Link:</strong> " . ($interview_type === 'Online' ? $meeting_link : $location) . "</p>
                    <p>Please be ready 5 minutes before the scheduled time.</p>
                    <br>
                    <p>Best Regards,<br>HR1-CRANE Recruitment Team</p>
                ";
                sendInterviewEmail($email, $candidate_name, $subject, $body);
            }

            echo json_encode(['status' => 'success', 'message' => $msg]);
            exit();
        }

        if (isset($data['action']) && $data['action'] === 'save_feedback') {
            $id = $data['id'];
            $score = $data['score'];
            $feedback = $data['feedback'];
            $status = $data['status']; // Usually sets to 'completed' or 'rejected'

            $sql = "UPDATE interviews SET score=?, feedback=?, status=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$score, $feedback, $status, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Feedback saved successfully']);
            exit();
        }

        if (isset($data['action']) && $data['action'] === 'delete_interview') {
            $stmt = $conn->prepare("UPDATE interviews SET is_archived = 1 WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(['status' => 'success', 'message' => 'Interview archived']);
            exit();
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}
// Stats
$total_interviews = getCount($conn, "SELECT COUNT(*) FROM interviews WHERE is_archived = 0");
$scheduled_interviews = getCount($conn, "SELECT COUNT(*) FROM interviews WHERE status='scheduled' AND is_archived = 0");
$completed_interviews = getCount($conn, "SELECT COUNT(*) FROM interviews WHERE status='completed' AND is_archived = 0");
$cancelled_interviews = getCount($conn, "SELECT COUNT(*) FROM interviews WHERE status='cancelled' AND is_archived = 0");

// Fetch Candidates for dropdown
$candidates = $conn->query("SELECT id, full_name, email, job_title FROM candidates ORDER BY full_name ASC")->fetchAll();

// Fetch Employees for Interviewer dropdown
$employees = $conn->query("SELECT id, name, position FROM employees ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Scheduling | HR1-CRANE</title>
    <link rel="icon" type="image/x-icon" href="../../Image/logo.png">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: {
                        brand: { 500: '#6366f1', 600: '#4f46e5', 50: '#eef2ff' },
                        success: '#10b981',
                        warning: '#f59e0b',
                        danger: '#ef4444'
                    }
                }
            }
        }
    </script>

    <!-- FullCalendar -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        html {
            scroll-behavior: smooth;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* Global Modern Smooth Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
 
        ::-webkit-scrollbar-thumb {
            background: #c7c7c7;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
 
        ::-webkit-scrollbar-thumb:hover {
            background: #a0a0a0;
        }

        /* Modal Specific adjustment */
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #6366f1 transparent;
        }

        #calendar {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #f1f1f1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
 
        .fc-toolbar-title {
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            color: #111827 !important;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
 
        /* FullCalendar Light Overrides */
        .fc-daygrid-day { background-color: #ffffff !important; border-color: #f3f4f6 !important; }
        .fc-col-header-cell { background-color: #f9fafb !important; color: #6b7280 !important; border-color: #f3f4f6 !important; padding: 10px 0 !important; font-size: 10px !important; text-transform: uppercase !important; letter-spacing: 0.1em !important; }
        .fc-day-today { background-color: rgba(99, 102, 241, 0.05) !important; }
        .fc-theme-standard th, .fc-theme-standard td { border-color: #f3f4f6 !important; }
        .fc-list { background-color: #ffffff !important; border-color: #f3f4f6 !important; }
        .fc-list-day-cushion { background-color: #f9fafb !important; }
        .fc-list-event:hover td { background-color: #f9fafb !important; }
 
        .fc-button-primary {
            background-color: #1f2937 !important;
            border-color: #374151 !important;
            text-transform: uppercase !important;
            font-size: 10px !important;
            font-bold: 700 !important;
            padding: 8px 16px !important;
        }
 
        .fc-button-primary:hover {
            background-color: #374151 !important;
        }
 
        .fc-button-active {
            background-color: #6366f1 !important;
            border-color: #6366f1 !important;
        }
    </style>
</head>

<body class="bg-[#f8f9fa] text-gray-800 font-sans">

    <?php
    $root_path = '../../';
    include '../Components/sidebar.php';
    ?>

    <div class="ml-64 transition-all duration-300 min-h-screen flex flex-col" id="mainContent">
        <?php include '../Components/header.php'; ?>

        <main class="p-8 mt-20 flex-grow">
            <!-- Page Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Interview Management</h1>
                    <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest mt-1">Schedule, track, and evaluate candidate interviews.</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="openScheduleModal()"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-lg shadow-indigo-950/40 hover:bg-indigo-700 transition-all font-bold text-xs uppercase tracking-widest flex items-center gap-2">
                        <i class="fas fa-plus"></i> Schedule Interview
                    </button>
                    <div class="flex bg-white rounded-lg p-1 border border-gray-100">
                        <button onclick="switchView('calendar')" id="viewCal"
                            class="px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-md bg-indigo-600 text-white shadow-md transition-all">
                            <i class="fas fa-calendar-alt mr-1"></i> Calendar
                        </button>
                        <button onclick="switchView('list')" id="viewList"
                            class="px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest rounded-md text-gray-400 hover:text-gray-600 transition-all">
                            <i class="fas fa-list mr-1"></i> List View
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-indigo-500 transition-all hover:translate-y-[-2px]">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Interviews</p>
                    <h3 class="text-3xl font-black mt-2 text-gray-900"><?= $total_interviews ?></h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-yellow-500 transition-all hover:translate-y-[-2px]">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Scheduled</p>
                    <h3 class="text-3xl font-black mt-2 text-yellow-500"><?= $scheduled_interviews ?></h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-green-500 transition-all hover:translate-y-[-2px]">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Completed</p>
                    <h3 class="text-3xl font-black mt-2 text-green-500"><?= $completed_interviews ?></h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-red-500 transition-all hover:translate-y-[-2px]">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Cancelled</p>
                    <h3 class="text-3xl font-black mt-2 text-red-500"><?= $cancelled_interviews ?></h3>
                </div>
            </div>

            <!-- Main Content Area -->
            <div id="calendarView" class="block">
                <div id="calendar"></div>
            </div>

            <div id="listView" class="hidden">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-[10px] text-gray-400 uppercase bg-gray-50 border-b border-gray-100 tracking-widest font-bold">
                                <tr>
                                    <th class="px-6 py-4">Candidate</th>
                                    <th class="px-6 py-4">Position</th>
                                    <th class="px-6 py-4">Date & Time</th>
                                    <th class="px-6 py-4">Type</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">Score</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="interviewTableBody" class="divide-y divide-gray-100">
                                <!-- Populated via AJAX or PHP -->
                                <?php
                                $stmt = $conn->query("SELECT * FROM interviews ORDER BY start_time DESC");
                                while ($row = $stmt->fetch()):
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-900 uppercase tracking-tight">
                                                <?= htmlspecialchars($row['candidate_name']) ?>
                                            </div>
                                            <div class="text-[10px] text-gray-400 uppercase font-bold"><?= htmlspecialchars($row['email']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-400 font-light"><?= htmlspecialchars($row['position']) ?></td>
                                        <td class="px-6 py-4 text-gray-400 font-light text-xs">
                                            <div class="font-bold text-gray-800"><?= date('M d, Y', strtotime($row['start_time'])) ?></div>
                                            <div class="text-[10px] text-gray-400 uppercase tracking-tighter">
                                                <?= date('h:i A', strtotime($row['start_time'])) ?> -
                                                <?= date('h:i A', strtotime($row['end_time'])) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest <?= $row['interview_type'] === 'Online' ? 'bg-blue-50 text-blue-500 border border-blue-100' : 'bg-gray-50 text-gray-400 border border-gray-100' ?>">
                                                <?= $row['interview_type'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest 
                                             <?= $row['status'] === 'completed' ? 'bg-green-50 text-green-500 border border-green-100' :
                                                 ($row['status'] === 'cancelled' ? 'bg-red-50 text-red-500 border border-red-100' : 'bg-yellow-50 text-yellow-600 border border-yellow-100') ?>">
                                                <?= $row['status'] ?>
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-4 font-black uppercase tracking-tighter text-xs <?= ($row['score'] ?? 0) >= 70 ? 'text-green-500' : 'text-orange-500' ?>">
                                            <?= $row['score'] ? $row['score'] . '%' : '-' ?>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-2">
                                            <button onclick="openFeedbackModal(<?= $row['id'] ?>)"
                                                class="text-indigo-600 hover:text-indigo-900" title="Feedback & Score"><i
                                                    class="fas fa-star"></i></button>
                                            <button onclick="openScheduleModal(<?= $row['id'] ?>)"
                                                class="text-gray-400 hover:text-gray-600" title="Edit"><i
                                                    class="fas fa-edit"></i></button>
                                            <button onclick="deleteInterview(<?= $row['id'] ?>)"
                                                class="text-red-400 hover:text-red-600" title="Delete"><i
                                                    class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Schedule Modal -->
    <div id="scheduleModal" class="fixed inset-0 bg-black/60 hidden z-[1050] flex items-center justify-center p-4 backdrop-blur-sm">
         <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col transform transition-all scale-100">
            <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50 flex-shrink-0">
                <h3 id="modalTitle" class="text-xl font-black text-gray-900 uppercase tracking-tight">Schedule Interview</h3>
                <button onclick="closeModal('scheduleModal')" class="text-gray-400 hover:text-gray-600 transition-colors"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto custom-scrollbar p-0">
            <form id="scheduleForm" class="p-6 space-y-4">
                <input type="hidden" name="action" value="save_interview">
                <input type="hidden" name="id" id="intId">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Select Candidate</label>
                        <select name="candidate_id" id="candSelect" onchange="autoFillCandidate()"
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none text-gray-900">
                            <option value="">New Candidate / Manual Entry</option>
                            <?php foreach ($candidates as $cand): ?>
                                <option value="<?= $cand['id'] ?>" data-name="<?= htmlspecialchars($cand['full_name']) ?>"
                                    data-email="<?= htmlspecialchars($cand['email']) ?>"
                                    data-job="<?= htmlspecialchars($cand['job_title']) ?>">
                                    <?= htmlspecialchars($cand['full_name']) ?>
                                    (<?= htmlspecialchars($cand['job_title']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Candidate Name</label>
                            <input type="text" name="candidate_name" id="candName" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none text-gray-900">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Candidate Email</label>
                        <input type="email" name="email" id="candEmail" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none text-gray-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Job Position</label>
                        <input type="text" name="position" id="candPosition" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none text-gray-900">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Interviewer(s)</label>
                    <input type="text" name="interviewer" id="intInterviewer" placeholder="Select or type name..."
                        list="employeeList" required
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none text-gray-900">
                    <datalist id="employeeList">
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= htmlspecialchars($emp['name']) ?>">
                            <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Start Time</label>
                        <input type="datetime-local" name="start_time" id="intStart" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none text-gray-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">End Time</label>
                        <input type="datetime-local" name="end_time" id="intEnd" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none text-gray-900">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Interview Type</label>
                        <select name="interview_type" id="intType" onchange="toggleMeetingLink()"
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none text-gray-900">
                            <option value="Onsite">Onsite</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Location / Room</label>
                        <input type="text" name="location" id="intLocation" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none text-gray-900">
                    </div>
                </div>

                <div id="meetingLinkGroup" class="hidden">
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Meeting Link</label>
                    <input type="url" name="meeting_link" id="intLink"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none text-gray-900">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Status</label>
                    <select name="status" id="intStatus"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none text-gray-900 font-bold">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No Show</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Internal Notes</label>
                    <textarea name="notes" id="intNotes" rows="3"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none resize-none text-gray-900 placeholder-gray-400"></textarea>
                </div>

                <div class="flex items-center gap-2 py-2">
                    <input type="checkbox" name="send_notification" value="1" id="sendNotif"
                        class="w-4 h-4 text-indigo-600 bg-white border-gray-200 rounded focus:ring-indigo-500">
                    <label for="sendNotif" class="text-xs font-bold text-gray-400 uppercase tracking-widest">Send Email Notification to
                        Candidate</label>
                </div>

                <div class="pt-6 border-t border-gray-50 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('scheduleModal')"
                        class="px-6 py-2.5 bg-gray-100 text-gray-500 font-bold text-[10px] uppercase tracking-widest rounded-lg hover:bg-gray-200 transition-colors">Cancel</button>
                    <button type="submit" id="saveScheduleBtn"
                        class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg shadow-md hover:bg-indigo-700 transition-all font-black text-[10px] uppercase tracking-widest flex items-center gap-2">
                        <span id="btnText">Save Schedule</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="fixed inset-0 bg-black/60 hidden z-[1050] flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[85vh] flex flex-col transform transition-all scale-100 border border-gray-50">
            <div class="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50 flex-shrink-0">
                <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight">Interview Evaluation</h3>
                <button onclick="closeModal('feedbackModal')" class="text-gray-400 hover:text-gray-600 transition-colors"><i
                        class="fas fa-times"></i></button>
            </div>
            <div class="overflow-y-auto custom-scrollbar p-0">
            <form id="feedbackForm" class="p-6 space-y-4">
                <input type="hidden" name="action" value="save_feedback">
                <input type="hidden" name="id" id="feedIntId">

                <div class="text-center mb-6">
                    <p class="text-sm text-gray-400 mb-2">Overall Candidate Score</p>
                    <div class="flex items-center justify-center gap-4">
                        <input type="range" name="score" id="feedScore" min="0" max="100" value="70"
                            oninput="updateScoreVal(this.value)"
                            class="w-full h-2 bg-gray-100 rounded-lg appearance-none cursor-pointer accent-brand-600">
                        <span id="scoreVal" class="text-2xl font-bold text-brand-600 w-16">70%</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Detailed Feedback</label>
                    <textarea name="feedback" id="feedText" rows="5" required
                        placeholder="Observations, strengths, weaknesses..."
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none resize-none text-gray-900 placeholder-gray-400"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Final Result</label>
                    <select name="status" id="feedStatus"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none font-bold text-gray-900">
                        <option value="completed" class="text-green-500">Pass / Next Round</option>
                        <option value="rejected" class="text-red-500">Reject</option>
                        <option value="hired" class="text-indigo-600">Hired (Move to Onboarding)</option>
                    </select>
                </div>

                <div class="pt-6 border-t border-gray-50 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('feedbackModal')"
                        class="px-6 py-2.5 bg-gray-100 text-gray-500 font-bold text-[10px] uppercase tracking-widest rounded-lg hover:bg-gray-200 transition-all">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg shadow-md hover:bg-indigo-700 transition-all font-black text-[10px] uppercase tracking-widest">Submit
                        Evaluation</button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: '?action=get_events',
                eventClick: function (info) {
                    openScheduleModal(info.event.id);
                }
            });
            calendar.render();
            window.fullCalendar = calendar;
        });

        function switchView(view) {
            const calView = document.getElementById('calendarView');
            const listView = document.getElementById('listView');
            const btnCal = document.getElementById('viewCal');
            const btnList = document.getElementById('viewList');

            if (view === 'calendar') {
                calView.classList.remove('hidden');
                listView.classList.add('hidden');
                btnCal.className = "px-4 py-1.5 text-xs font-medium rounded-md bg-brand-50 text-brand-600 shadow-sm transition-all";
                btnList.className = "px-4 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all";
                window.fullCalendar.render();
            } else {
                calView.classList.add('hidden');
                listView.classList.remove('hidden');
                btnList.className = "px-4 py-1.5 text-xs font-medium rounded-md bg-brand-50 text-brand-600 shadow-sm transition-all";
                btnCal.className = "px-4 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all";
            }
        }

        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function autoFillCandidate() {
            const select = document.getElementById('candSelect');
            const option = select.options[select.selectedIndex];
            if (option.value) {
                document.getElementById('candName').value = option.dataset.name;
                document.getElementById('candEmail').value = option.dataset.email;
                document.getElementById('candPosition').value = option.dataset.job;
            }
        }

        function toggleMeetingLink() {
            const type = document.getElementById('intType').value;
            const group = document.getElementById('meetingLinkGroup');
            if (type === 'Online') {
                group.classList.remove('hidden');
            } else {
                group.classList.add('hidden');
            }
        }

        function openScheduleModal(id = null) {
            const form = document.getElementById('scheduleForm');
            form.reset();
            document.getElementById('intId').value = '';
            document.getElementById('modalTitle').textContent = 'Schedule Interview';
            document.getElementById('meetingLinkGroup').classList.add('hidden');

            if (id) {
                fetch(`?action=get_interview&id=${id}`)
                    .then(r => r.json())
                    .then(data => {
                        document.getElementById('intId').value = data.id;
                        document.getElementById('candSelect').value = data.candidate_id || '';
                        document.getElementById('candName').value = data.candidate_name;
                        document.getElementById('candEmail').value = data.email;
                        document.getElementById('candPosition').value = data.position;
                        document.getElementById('intInterviewer').value = data.interviewer;
                        document.getElementById('intStart').value = data.start_time.substring(0, 16);
                        document.getElementById('intEnd').value = data.end_time.substring(0, 16);
                        document.getElementById('intType').value = data.interview_type;
                        document.getElementById('intLocation').value = data.location;
                        document.getElementById('intLink').value = data.meeting_link;
                        document.getElementById('intStatus').value = data.status;
                        document.getElementById('intNotes').value = data.notes;

                        document.getElementById('modalTitle').textContent = 'Edit Interview';
                        toggleMeetingLink();
                        openModal('scheduleModal');
                    });
            } else {
                openModal('scheduleModal');
            }
        }

        function openFeedbackModal(id) {
            fetch(`?action=get_interview&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('feedIntId').value = data.id;
                    document.getElementById('feedScore').value = data.score || 70;
                    document.getElementById('scoreVal').textContent = (data.score || 70) + '%';
                    document.getElementById('feedText').value = data.feedback || '';
                    document.getElementById('feedStatus').value = data.status === 'scheduled' ? 'completed' : data.status;
                    openModal('feedbackModal');
                });
        }

        function updateScoreVal(v) {
            document.getElementById('scoreVal').textContent = v + '%';
        }

        // Form Submissions
        document.getElementById('scheduleForm').onsubmit = function (e) {
            e.preventDefault();
            const btn = document.getElementById('saveScheduleBtn');
            const btnText = document.getElementById('btnText');
            const originalHTML = btnText.innerHTML;

            // Loading state
            btn.disabled = true;
            btnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

            const formData = new FormData(this);
            fetch('', { method: 'POST', body: formData })
                .then(r => {
                    if (!r.ok) throw new Error('Network response was not ok');
                    return r.json();
                })
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({
                            title: 'Success',
                            text: res.message,
                            icon: 'success',
                            confirmButtonColor: '#6366f1'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                        btn.disabled = false;
                        btnText.innerHTML = originalHTML;
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    Swal.fire('Error', 'Connect failed or server error. Please try again.', 'error');
                    btn.disabled = false;
                    btnText.innerHTML = originalHTML;
                });
        };

        document.getElementById('feedbackForm').onsubmit = function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire('Saved', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                });
        };

        function deleteInterview(id) {
            Swal.fire({
                title: 'Archive Interview?',
                text: "This record will be moved to archives.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, archive it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('action', 'delete_interview');
                    fd.append('id', id);
                    fetch('', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(res => {
                            if (res.status === 'success') {
                                Swal.fire('Archived!', 'Interview has been moved to archives.', 'success').then(() => location.reload());
                            }
                        });
                }
            });
        }
    </script>
</body>

</html>