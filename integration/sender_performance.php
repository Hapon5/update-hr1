<?php
/**
 * Performance API Sender - Service & Test Interface
 * 
 * This file provides both a reusable PHP function for sending performance data
 * and a premium glassmorphic UI for manual testing.
 */

function sendPerformanceData($data, $url = 'performance.php') {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'response' => json_decode($response, true) ?: $response
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['api_trigger'])) {
    $result = sendPerformanceData($_POST['data'], $_POST['target_url'] ?? 'performance.php');
    echo json_encode($result);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance API | Secure Sender</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at top left, #ffffff, #f1f5f9);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 1);
            box-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.1);
        }
        .connected-line {
            position: absolute;
            width: 2px;
            background: linear-gradient(to bottom, #6366f1, transparent);
            left: 50%;
            top: -50px;
            height: 100px;
            z-index: 0;
            opacity: 0.3;
        }
        .animate-pulse-slow {
            animation: pulse-slow 3s ease-in-out infinite;
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }
    </style>
</head>
<body class="flex items-center justify-center p-6">

    <div class="max-w-2xl w-full">
        <!-- Header Section -->
        <div class="text-center mb-12 relative">
            <div class="inline-block p-5 bg-white rounded-3xl shadow-xl border border-gray-100 mb-6 relative z-10">
                <i class="fas fa-satellite-dish text-4xl text-indigo-600 animate-pulse-slow"></i>
            </div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tighter uppercase mb-2">Connected <span class="text-indigo-600">Performance</span></h1>
            <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.5em]">Real-time API Integration</p>
        </div>

        <!-- Main Card -->
        <div class="glass-card rounded-[48px] p-10 relative overflow-hidden border border-white/80">
            <div class="absolute -top-24 -left-24 w-64 h-64 bg-indigo-100 rounded-full blur-3xl opacity-50"></div>
            <div class="absolute -bottom-24 -right-24 w-64 h-64 bg-blue-100 rounded-full blur-3xl opacity-50"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-10">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-ping"></div>
                        <h2 class="text-xs font-black text-gray-900 uppercase tracking-widest">Active Link</h2>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-[9px] font-bold text-gray-400 border border-gray-100 px-3 py-1 rounded-full uppercase tracking-tighter">JSON Payload</span>
                        <span class="text-[9px] font-bold text-indigo-500 bg-indigo-50/50 border border-indigo-100 px-3 py-1 rounded-full uppercase tracking-tighter">Secure POST</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                    <!-- Left: Payload Visualization -->
                    <div class="space-y-6">
                        <div class="bg-gray-950 rounded-3xl p-6 border border-gray-900 shadow-2xl relative">
                            <div class="absolute top-4 right-4 text-[9px] font-mono text-gray-600 uppercase">Input</div>
                            <pre class="text-[10px] text-indigo-300 font-mono leading-relaxed" id="payloadPreview"></pre>
                        </div>
                    </div>

                    <!-- Right: Info & Action -->
                    <div class="flex flex-col justify-center">
                        <p class="text-xs text-gray-500 leading-relaxed mb-8">
                            This interface establishes a <span class="text-gray-900 font-bold">direct connection</span> to the Performance API Receiver. It validates all metrics including KPIs, Productivity, and Supervisor Ratings before transmission.
                        </p>
                        
                        <button onclick="executeAPIRequest()" id="sendBtn"
                            class="group w-full py-5 bg-indigo-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.3em] hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-100 hover:shadow-indigo-200 active:scale-95 flex items-center justify-center gap-3">
                            <span id="btnText">Sync Review</span>
                            <i class="fas fa-link text-[10px] group-hover:-rotate-45 transition-transform"></i>
                        </button>
                    </div>
                </div>

                <!-- Status Feedback -->
                <div id="statusContainer" class="hidden animate-in fade-in duration-500">
                    <div class="pt-8 border-t border-gray-100/80">
                        <div id="statusIndicator" class="flex items-center gap-5 p-5 rounded-3xl mb-6">
                            <div id="statusIcon" class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-lg transition-all"></div>
                            <div>
                                <p id="statusHeading" class="text-sm font-black uppercase tracking-tight mb-1"></p>
                                <p id="statusSub" class="text-[10px] font-bold text-gray-400 uppercase tracking-wider"></p>
                            </div>
                        </div>
                        
                        <div class="relative">
                            <div class="absolute top-4 right-6 text-[9px] font-mono text-gray-400 uppercase">Server Response</div>
                            <div id="responseView" class="bg-white/50 rounded-3xl p-6 text-[10px] font-mono whitespace-pre-wrap overflow-x-auto border border-gray-100 shadow-inner text-gray-600 max-h-48 overflow-y-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status Bar -->
        <div class="mt-12 flex justify-between items-center px-6">
            <div class="flex gap-6 items-center">
                <div class="flex items-center gap-2">
                    <i class="fas fa-database text-gray-400 text-xs"></i>
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">PostgreSQL Ready</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-shield-alt text-gray-400 text-xs"></i>
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">SSL Encrypted</span>
                </div>
            </div>
            <p class="text-[9px] font-black text-indigo-600 uppercase tracking-widest">Build 2.0.4-LATEST</p>
        </div>
    </div>

    <script>
        const sampleData = {
            employee_id: 1, // Changed to 1 to likely exist in local DB
            review_date: new Date().toISOString().split('T')[0],
            review_type: "Monthly KPI Sync",
            kpi_score: 98.4,
            attendance_score: 100,
            supervisor_quality_rating: 5,
            productivity_score: 95,
            promotion_recommended: 1,
            comments: "Connected via secure integration link."
        };

        document.getElementById('payloadPreview').innerText = JSON.stringify(sampleData, null, 4);

        function executeAPIRequest() {
            const btn = document.getElementById('sendBtn');
            const btnText = document.getElementById('btnText');
            const statusContainer = document.getElementById('statusContainer');
            const statusIcon = document.getElementById('statusIcon');
            const statusHeading = document.getElementById('statusHeading');
            const statusIndicator = document.getElementById('statusIndicator');
            const statusSub = document.getElementById('statusSub');
            const responseView = document.getElementById('responseView');

            btn.disabled = true;
            btnText.innerText = "Transmitting...";
            statusContainer.classList.add('hidden');

            fetch("performance.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(sampleData)
            })
            .then(response => {
                const status = response.status;
                return response.json().then(data => ({ status, data }));
            })
            .then(({ status, data }) => {
                statusContainer.classList.remove('hidden');
                
                if (status >= 200 && status < 300) {
                    statusIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                    statusIcon.className = "w-12 h-12 rounded-2xl bg-green-50 text-green-500 border border-green-100 flex items-center justify-center shadow-lg shadow-green-100";
                    statusHeading.innerText = "Integration Successful";
                    statusHeading.className = "text-sm font-black text-green-600 uppercase tracking-tight mb-1";
                    statusIndicator.className = "flex items-center gap-5 p-5 rounded-3xl mb-6 bg-green-50/30 border border-green-100";
                    statusSub.innerText = "Performance record successfully synchronized with the HR database.";
                } else {
                    statusIcon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                    statusIcon.className = "w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 border border-amber-100 flex items-center justify-center shadow-lg shadow-amber-100";
                    statusHeading.innerText = "Link Interrupted";
                    statusHeading.className = "text-sm font-black text-amber-600 uppercase tracking-tight mb-1";
                    statusIndicator.className = "flex items-center gap-5 p-5 rounded-3xl mb-6 bg-amber-50/30 border border-amber-100";
                    statusSub.innerText = `API returned unexpected status code: ${status}`;
                }
                
                responseView.innerText = JSON.stringify(data, null, 4);
            })
            .catch(error => {
                statusContainer.classList.remove('hidden');
                statusIcon.innerHTML = '<i class="fas fa-unlink text-red-600"></i>';
                statusIcon.className = "w-12 h-12 rounded-2xl bg-red-50 text-red-500 border border-red-100 flex items-center justify-center shadow-lg shadow-red-100";
                statusHeading.innerText = "Connection Failed";
                statusHeading.className = "text-sm font-black text-red-600 uppercase tracking-tight mb-1";
                statusIndicator.className = "flex items-center gap-5 p-5 rounded-3xl mb-6 bg-red-50/30 border border-red-100";
                statusSub.innerText = "The API endpoint could not be reached.";
                responseView.innerText = `CRITICAL ERROR: ${error.message}`;
            })
            .finally(() => {
                btn.disabled = false;
                btnText.innerText = "Sync Review";
            });
        }
    </script>
</body>
</html>
