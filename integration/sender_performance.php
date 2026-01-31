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
            background: radial-gradient(circle at top left, #f3f4f6, #e5e7eb);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05);
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
            <div class="inline-block p-4 bg-indigo-600 rounded-2xl shadow-2xl shadow-indigo-200 mb-6 animate-float">
                <i class="fas fa-microchip text-3xl text-white"></i>
            </div>
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight uppercase">Performance API</h1>
            <p class="text-gray-400 text-xs font-bold uppercase tracking-[0.2em] mt-2">Remote Integration System</p>
        </div>

        <!-- Main Card -->
        <div class="glass-card rounded-[32px] p-10 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/5 rounded-full -mr-16 -mt-16 blur-3xl"></div>
            
            <div class="relative z-10">
                <h2 class="text-lg font-black text-gray-800 mb-6 flex items-center gap-2 uppercase tracking-tight">
                    <span class="w-2 h-2 bg-indigo-500 rounded-full animate-ping"></span>
                    Test Connection
                </h2>

                <!-- Request Preview -->
                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Request Payload</span>
                        <span class="px-2 py-1 bg-indigo-50 text-indigo-600 text-[8px] font-black uppercase rounded-lg">JSON</span>
                    </div>
                    <pre class="text-[11px] text-gray-600 font-mono leading-relaxed" id="payloadPreview"></pre>
                </div>

                <!-- Action Button -->
                <button onclick="executeAPIRequest()" id="sendBtn"
                    class="group w-full py-5 bg-gray-900 text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-black transition-all shadow-xl hover:shadow-2xl active:scale-95 flex items-center justify-center gap-3">
                    <span id="btnText">Execute Fetch Request</span>
                    <i class="fas fa-arrow-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                </button>

                <!-- Status Feedback -->
                <div id="statusContainer" class="hidden mt-8 pt-8 border-t border-gray-100/50">
                    <div id="statusIndicator" class="flex items-center gap-3 mb-4">
                        <div id="statusIcon" class="w-8 h-8 rounded-full flex items-center justify-center"></div>
                        <div>
                            <p id="statusHeading" class="text-xs font-black uppercase tracking-tight"></p>
                            <p id="statusSub" class="text-[9px] font-bold text-gray-400 uppercase"></p>
                        </div>
                    </div>
                    
                    <div id="responseView" class="bg-gray-900 rounded-2xl p-6 text-[10px] font-mono whitespace-pre-wrap overflow-x-auto border border-gray-800 text-indigo-300 max-scroll-60 shadow-inner"></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-8">
            Antigravity Integration Engine &copy; 2026
        </p>
    </div>

    <script>
        const data = {
            employee_id: 1,
            review_date: new Date().toISOString().split('T')[0],
            review_type: "Monthly Integration",
            kpi_score: 96.5,
            attendance_score: 100,
            supervisor_quality_rating: 5,
            productivity_score: 98,
            promotion_recommended: 1,
            comments: "Sent using premium UI with JS Fetch."
        };

        // Initialize preview
        document.getElementById('payloadPreview').innerText = JSON.stringify(data, null, 4);

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
            btnText.innerText = "Processing Integration...";
            statusContainer.classList.add('hidden');

            fetch("performance.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP Error (${response.status})`);
                return response.json();
            })
            .then(result => {
                console.log("Success:", result);
                
                // Show Success
                statusContainer.classList.remove('hidden');
                statusIcon.innerHTML = '<i class="fas fa-check text-green-600"></i>';
                statusIcon.className = "w-8 h-8 rounded-full bg-green-50 border border-green-100 flex items-center justify-center";
                statusHeading.innerText = "Request Successful";
                statusHeading.className = "text-xs font-black text-green-600 uppercase tracking-tight";
                statusSub.innerText = "Data synchronized with performance module";
                
                responseView.innerHTML = JSON.stringify(result, null, 4);
                responseView.className = "bg-gray-900 rounded-2xl p-6 text-[10px] font-mono whitespace-pre-wrap overflow-x-auto border border-gray-800 text-green-400 shadow-inner";
            })
            .catch(error => {
                console.error("Error:", error);
                
                // Show Error
                statusContainer.classList.remove('hidden');
                statusIcon.innerHTML = '<i class="fas fa-exclamation text-red-600"></i>';
                statusIcon.className = "w-8 h-8 rounded-full bg-red-50 border border-red-100 flex items-center justify-center";
                statusHeading.innerText = "Integration Failed";
                statusHeading.className = "text-xs font-black text-red-600 uppercase tracking-tight";
                statusSub.innerText = error.message;
                
                responseView.innerHTML = `ERROR: ${error.message}\nEnsure performance.php exists and returns valid JSON.`;
                responseView.className = "bg-gray-900 rounded-2xl p-6 text-[10px] font-mono whitespace-pre-wrap overflow-x-auto border border-gray-800 text-red-400 shadow-inner";
            })
            .finally(() => {
                btn.disabled = false;
                btnText.innerText = "Execute Fetch Request";
            });
        }
    </script>
</body>
</html>
