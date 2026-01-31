<?php
session_start();
include '../../Database/Connections.php';

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
        $mail->Password = 'bivb opss calj bfsd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('linbilcelestre31@gmail.com', 'HR1-CRANE Recruitment');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
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

// Stats
$total_interviews = getCount($conn, "SELECT COUNT(*) FROM interviews");
$scheduled_interviews = getCount($conn, "SELECT COUNT(*) FROM interviews WHERE status='scheduled'");
$completed_interviews = getCount($conn, "SELECT COUNT(*) FROM interviews WHERE status='completed'");
$cancelled_interviews = getCount($conn, "SELECT COUNT(*) FROM interviews WHERE status='cancelled'");

// Fetch Candidates for dropdown
$candidates = $conn->query("SELECT id, full_name, email, job_title FROM candidates ORDER BY full_name ASC")->fetchAll();

// Fetch Employees for Interviewer dropdown
$employees = $conn->query("SELECT id, name, position FROM employees ORDER BY name ASC")->fetchAll();

// AJAX Handlers
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'get_events') {
        $stmt = $conn->query("SELECT id, candidate_name as title, start_time as start, end_time as end, status, interview_type FROM interviews");
        $events = $stmt->fetchAll();
        foreach ($events as &$event) {
            $event['color'] = $event['status'] === 'completed' ? '#10b981' : ($event['status'] === 'cancelled' ? '#ef4444' : '#6366f1');
        }
        echo json_encode($events);
        exit();
    }

    if ($_GET['action'] === 'get_interview' && isset($_GET['id'])) {
        $stmt = $conn->prepare("SELECT * FROM interviews WHERE id = ?");
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
            $stmt = $conn->prepare("DELETE FROM interviews WHERE id = ?");
            $stmt->execute([$data['id']]);
            echo json_encode(['status' => 'success', 'message' => 'Interview deleted']);
            exit();
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}
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
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        /* Global Modern Smooth Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #6366f1;
        }

        /* Modal Specific adjustment */
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #6366f1 transparent;
        }

        #calendar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        .fc-toolbar-title {
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            color: #1f2937;
        }

        .fc-button-primary {
            background-color: #6366f1 !important;
            border-color: #6366f1 !important;
        }

        .fc-button-primary:hover {
            background-color: #4f46e5 !important;
            border-color: #4f46e5 !important;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 font-sans">

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
                    <h1 class="text-2xl font-bold text-gray-800">Interview Management</h1>
                    <p class="text-sm text-gray-500">Schedule, track, and evaluate candidate interviews.</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="openScheduleModal()"
                        class="bg-brand-600 text-white px-4 py-2 rounded-lg shadow-sm hover:bg-brand-700 transition-all flex items-center gap-2">
                        <i class="fas fa-plus"></i> Schedule Interview
                    </button>
                    <div class="flex bg-white rounded-lg p-1 border border-gray-200">
                        <button onclick="switchView('calendar')" id="viewCal"
                            class="px-4 py-1.5 text-xs font-medium rounded-md bg-brand-50 text-brand-600 shadow-sm transition-all">
                            <i class="fas fa-calendar-alt mr-1"></i> Calendar
                        </button>
                        <button onclick="switchView('list')" id="viewList"
                            class="px-4 py-1.5 text-xs font-medium rounded-md text-gray-500 hover:text-gray-700 transition-all">
                            <i class="fas fa-list mr-1"></i> List View
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-indigo-500">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Total Interviews</p>
                    <h3 class="text-2xl font-bold mt-1"><?= $total_interviews ?></h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-yellow-500">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Scheduled</p>
                    <h3 class="text-2xl font-bold mt-1 text-yellow-600"><?= $scheduled_interviews ?></h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Completed</p>
                    <h3 class="text-2xl font-bold mt-1 text-green-600"><?= $completed_interviews ?></h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-red-500">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Cancelled</p>
                    <h3 class="text-2xl font-bold mt-1 text-red-600"><?= $cancelled_interviews ?></h3>
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
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50/50 border-b">
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
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900">
                                                <?= htmlspecialchars($row['candidate_name']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($row['email']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($row['position']) ?></td>
                                        <td class="px-6 py-4 text-gray-600">
                                            <div><?= date('M d, Y', strtotime($row['start_time'])) ?></div>
                                            <div class="text-xs text-gray-400">
                                                <?= date('h:i A', strtotime($row['start_time'])) ?> -
                                                <?= date('h:i A', strtotime($row['end_time'])) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="px-2 py-1 rounded text-xs font-medium <?= $row['interview_type'] === 'Online' ? 'bg-blue-50 text-blue-600' : 'bg-gray-100 text-gray-600' ?>">
                                                <?= $row['interview_type'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="px-2 py-1 rounded-full text-xs font-bold 
                                            <?= $row['status'] === 'completed' ? 'bg-green-100 text-green-700' :
                                                ($row['status'] === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') ?>">
                                                <?= ucfirst($row['status']) ?>
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-4 font-bold <?= ($row['score'] ?? 0) >= 70 ? 'text-green-600' : 'text-orange-500' ?>">
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
    <div id="scheduleModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 id="modalTitle" class="text-xl font-bold text-gray-800">Schedule Interview</h3>
                <button onclick="closeModal('scheduleModal')" class="text-gray-400 hover:text-gray-600"><i
                        class="fas fa-times"></i></button>
            </div>
            <form id="scheduleForm" class="p-6 space-y-4">
                <input type="hidden" name="action" value="save_interview">
                <input type="hidden" name="id" id="intId">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Select Candidate</label>
                        <select name="candidate_id" id="candSelect" onchange="autoFillCandidate()"
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none">
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
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Candidate Name</label>
                        <input type="text" name="candidate_name" id="candName" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Candidate Email</label>
                        <input type="email" name="email" id="candEmail" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Job Position</label>
                        <input type="text" name="position" id="candPosition" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Interviewer(s)</label>
                    <input type="text" name="interviewer" id="intInterviewer" placeholder="Select or type name..."
                        list="employeeList" required
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none">
                    <datalist id="employeeList">
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= htmlspecialchars($emp['name']) ?>">
                            <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Start Time</label>
                        <input type="datetime-local" name="start_time" id="intStart" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">End Time</label>
                        <input type="datetime-local" name="end_time" id="intEnd" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Interview Type</label>
                        <select name="interview_type" id="intType" onchange="toggleMeetingLink()"
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none">
                            <option value="Onsite">Onsite</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Location / Room</label>
                        <input type="text" name="location" id="intLocation" required
                            class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none">
                    </div>
                </div>

                <div id="meetingLinkGroup" class="hidden">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Meeting Link (Zoom/Google
                        Meet)</label>
                    <input type="url" name="meeting_link" id="intLink"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
                    <select name="status" id="intStatus"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No Show</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Internal Notes</label>
                    <textarea name="notes" id="intNotes" rows="3"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none resize-none"></textarea>
                </div>

                <div class="flex items-center gap-2 py-2">
                    <input type="checkbox" name="send_notification" value="1" id="sendNotif"
                        class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                    <label for="sendNotif" class="text-sm font-medium text-gray-700">Send Email Notification to
                        Candidate</label>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('scheduleModal')"
                        class="px-6 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-all">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 bg-brand-600 text-white rounded-lg shadow-sm hover:bg-brand-700 transition-all font-semibold">Save
                        Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-xl font-bold text-gray-800">Interview Evaluation</h3>
                <button onclick="closeModal('feedbackModal')" class="text-gray-400 hover:text-gray-600"><i
                        class="fas fa-times"></i></button>
            </div>
            <form id="feedbackForm" class="p-6 space-y-4">
                <input type="hidden" name="action" value="save_feedback">
                <input type="hidden" name="id" id="feedIntId">

                <div class="text-center mb-6">
                    <p class="text-sm text-gray-500 mb-2">Overall Candidate Score</p>
                    <div class="flex items-center justify-center gap-4">
                        <input type="range" name="score" id="feedScore" min="0" max="100" value="70"
                            oninput="updateScoreVal(this.value)"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-brand-600">
                        <span id="scoreVal" class="text-2xl font-bold text-brand-600 w-16">70%</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Detailed Feedback</label>
                    <textarea name="feedback" id="feedText" rows="5" required
                        placeholder="Observations, strengths, weaknesses..."
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none resize-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Final Result</label>
                    <select name="status" id="feedStatus"
                        class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all outline-none font-bold">
                        <option value="completed" class="text-green-600">Pass / Next Round</option>
                        <option value="rejected" class="text-red-600">Reject</option>
                        <option value="hired" class="text-indigo-600">Hired (Move to Onboarding)</option>
                    </select>
                </div>

                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="closeModal('feedbackModal')"
                        class="px-6 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-all">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2 bg-brand-600 text-white rounded-lg shadow-sm hover:bg-brand-700 transition-all font-semibold">Submit
                        Evaluation</button>
                </div>
            </form>
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
            const formData = new FormData(this);
            fetch('', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire('Success', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
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
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const fd = new FormData();
                    fd.append('action', 'delete_interview');
                    fd.append('id', id);
                    fetch('', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(res => {
                            if (res.status === 'success') {
                                Swal.fire('Deleted!', 'Interview has been deleted.', 'success').then(() => location.reload());
                            }
                        });
                }
            });
        }
    </script>
</body>

</html>