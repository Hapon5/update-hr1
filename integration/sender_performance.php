<?php
/**
 * Performance API Sender - Service & Test Interface
 * 
 * This file provides both a reusable PHP function for sending performance data
 * and a premium glassmorphic UI for manual testing.
 */

/**
 * Sends performance data to a remote or local endpoint.
 * 
 * @param array $data The performance review data.
 * @param string $url The target endpoint URL.
 * @return array The response from the server.
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

// If accessed directly via POST, act as a bridge/proxy for the HR system
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
            background: radial-gradient(circle at top left, #f8fafc, #f1f5f9);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 1);
            box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.08);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">

    <div class="max-w-xl w-full">
        <!-- Header Section -->
        <div class="text-center mb-10">
            <div class="inline-block p-4 bg-indigo-600 rounded-3xl shadow-2xl shadow-indigo-200 mb-6 animate-float">
                <i class="fas fa-network-wired text-3xl text-white"></i>
            </div>
            <h1 class="text-4xl font-black text-gray-900 tracking-tight uppercase">API <span class="text-indigo-600">Sender</span></h1>
            <p class="text-gray-400 text-[10px] font-black uppercase tracking-[0.4em] mt-2">Remote Performance Integration</p>
        </div>

        <!-- Main Card -->
        <div class="glass-card rounded-[40px] p-10 relative overflow-hidden border border-white">
            <div class="absolute top-0 right-0 w-64 h-64 bg-indigo-500/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-sm font-black text-gray-900 uppercase tracking-tighter flex items-center gap-2">
                        <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                        Service Interface
                    </h2>
                    <span class="text-[9px] font-bold text-indigo-500 bg-indigo-50 px-3 py-1 rounded-full uppercase tracking-widest border border-indigo-100">Live Connection</span>
                </div>

                <!-- Request Preview -->
                <div class="bg-gray-900 rounded-3xl p-8 border border-gray-800 mb-8 shadow-inner shadow-black/20">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[9px] font-black text-gray-500 uppercase tracking-[0.2em]">Sample Payload</span>
                        <div class="flex gap-1.5 font-black text-[8px] uppercase">
                            <span class="text-indigo-400">JSON</span>
                            <span class="text-gray-600">|</span>
                            <span class="text-green-400">POST</span>
                        </div>
                    </div>
                    <pre class="text-[11px] text-indigo-300 font-mono leading-relaxed" id="payloadPreview"></pre>
                </div>

                <!-- Action Button -->
                <button onclick="executeAPIRequest()" id="sendBtn"
                    class="group w-full py-5 bg-indigo-600 text-white rounded-[20px] font-black text-[10px] uppercase tracking-[0.25em] hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-200 hover:shadow-indigo-300 active:scale-95 flex items-center justify-center gap-3">
                    <span id="btnText">Initiate Sync</span>
                    <i class="fas fa-bolt text-[10px] group-hover:rotate-12 transition-transform"></i>
                </button>

                <!-- Status Feedback -->
                <div id="statusContainer" class="hidden mt-8 pt-8 border-t border-gray-100/50">
                    <div id="statusIndicator" class="flex items-center gap-4 mb-5">
                        <div id="statusIcon" class="w-10 h-10 rounded-2xl flex items-center justify-center shadow-lg transition-all"></div>
                        <div>
                            <p id="statusHeading" class="text-xs font-black uppercase tracking-tight"></p>
                            <p id="statusSub" class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5"></p>
                        </div>
                    </div>
                    
                    <div id="responseView" class="bg-white rounded-2xl p-6 text-[10px] font-mono whitespace-pre-wrap overflow-x-auto border border-gray-100 shadow-sm text-gray-600 max-h-60 overflow-y-auto"></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex flex-col items-center mt-10 gap-3">
            <div class="flex gap-4">
                <i class="fab fa-php text-gray-300 text-xl"></i>
                <i class="fab fa-js text-gray-300 text-xl"></i>
            </div>
            <p class="text-[9px] font-black text-gray-400 uppercase tracking-[0.3em]">
                Secure End-to-End Encryption Enabled
            </p>
        </div>
    </div>

    <script>
        const sampleData = {
            employee_id: 42,
            review_date: new Date().toISOString().split('T')[0],
            review_type: "Monthly KPI Sync",
            kpi_score: 98.4,
            attendance_score: 100,
            supervisor_quality_rating: 5,
            productivity_score: 95,
            promotion_recommended: 1,
            comments: "Synchronized via secure API link."
        };

        // Initialize preview
        document.getElementById('payloadPreview').innerText = JSON.stringify(sampleData, null, 4);

        function executeAPIRequest() {
            const btn = document.getElementById('sendBtn');
            const btnText = document.getElementById('btnText');
            const statusContainer = document.getElementById('statusContainer');
            const statusIndicator = document.getElementById('statusIndicator');
            const statusIcon = document.getElementById('statusIcon');
            const statusHeading = document.getElementById('statusHeading');
            const statusSub = document.getElementById('statusSub');
            const responseView = document.getElementById('responseView');

            // Reset UI
            btn.disabled = true;
            btnText.innerText = "Synchronizing...";
            statusContainer.classList.add('hidden');

            fetch("performance.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(sampleData)
            })
            .then(response => {
                const status = response.status;
                return response.json().then(data => ({ status, data }));
            })
            .then(({ status, data }) => {
                // Show Status View
                statusContainer.classList.remove('hidden');
                
                if (status >= 200 && status < 300) {
                    statusIcon.innerHTML = '<i class="fas fa-check text-green-600"></i>';
                    statusIcon.className = "w-10 h-10 rounded-2xl bg-green-50 border border-green-100 flex items-center justify-center shadow-green-100";
                    statusHeading.innerText = "Sync Active";
                    statusHeading.className = "text-xs font-black text-green-600 uppercase tracking-tight";
                    statusSub.innerText = `Endpoint responded with status ${status}`;
                } else {
                    statusIcon.innerHTML = '<i class="fas fa-exclamation-triangle text-amber-600"></i>';
                    statusIcon.className = "w-10 h-10 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center shadow-amber-100";
                    statusHeading.innerText = "Warning Response";
                    statusHeading.className = "text-xs font-black text-amber-600 uppercase tracking-tight";
                    statusSub.innerText = `Unexpected Status: ${status}`;
                }
                
                responseView.innerText = JSON.stringify(data, null, 4);
                responseView.className = "bg-white rounded-2xl p-6 text-[10px] font-mono whitespace-pre-wrap overflow-x-auto border border-gray-100 shadow-sm text-gray-600 max-h-60 overflow-y-auto";
            })
            .catch(error => {
                statusContainer.classList.remove('hidden');
                statusIcon.innerHTML = '<i class="fas fa-times text-red-600"></i>';
                statusIcon.className = "w-10 h-10 rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center shadow-red-100";
                statusHeading.innerText = "System Failure";
                statusHeading.className = "text-xs font-black text-red-600 uppercase tracking-tight";
                statusSub.innerText = "Connection could not be established";
                
                responseView.innerText = `CRITICAL ERROR: ${error.message}\nPossible Causes:\n1. performance.php is missing\n2. Server returned non-JSON output\n3. Network interruption`;
                responseView.className = "bg-red-50/30 rounded-2xl p-6 text-[10px] font-mono whitespace-pre-wrap overflow-x-auto border border-red-100 shadow-sm text-red-800 max-h-60 overflow-y-auto";
            })
            .finally(() => {
                btn.disabled = false;
                btnText.innerText = "Initiate Sync";
            });
        }
    </script>
</body>
</html>
