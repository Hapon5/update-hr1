<?php
/**
 * API Debug Receiver & Viewer
 * Path: Debug/api_debug.php
 * 
 * This script logs incoming POST requests for debugging purposes
 * and provides a UI to view the captured data.
 */

$logFile = __DIR__ . '/api_debug_log.json';
if (!file_exists($logFile)) {
    file_put_contents($logFile, json_encode([]));
    @chmod($logFile, 0666);
}

// --- HANDLE INCOMING POST REQUESTS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true) ?? $_POST;
    
    $entry = [
        'id' => uniqid(),
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'method' => $_SERVER['REQUEST_METHOD'],
        'headers' => getallheaders(),
        'body' => $data,
        'raw_body' => $input
    ];

    $log = [];
    if (file_exists($logFile)) {
        $log = json_decode(file_get_contents($logFile), true) ?: [];
    }

    array_unshift($log, $entry); // Newest first
    $log = array_slice($log, 0, 50); // Keep last 50
    
    file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT));
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Data captured']);
    exit;
}

// --- HANDLE LOG CLEARING ---
if (isset($_GET['clear'])) {
    file_put_contents($logFile, json_encode([]));
    header('Location: api_debug.php');
    exit;
}

// --- RENDER VIEWER UI ---
$log = [];
if (file_exists($logFile)) {
    $log = json_decode(file_get_contents($logFile), true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Debug Receiver</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        pre { font-family: 'JetBrains Mono', monospace; }
        .entry-card:hover { border-color: #6366f1; }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen p-4 sm:p-8">

    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-8 gap-4 bg-gray-800 p-6 rounded-2xl shadow-xl">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-600 rounded-xl flex items-center justify-center text-2xl shadow-lg shadow-indigo-900/20">
                    <i class="fas fa-bug"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">API Debug Receiver</h1>
                    <p class="text-gray-400 text-sm">Monitoring incoming integration requests</p>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="?clear=1" class="px-4 py-2 bg-red-500/10 text-red-400 border border-red-500/20 rounded-lg hover:bg-red-500 hover:text-white transition-all text-sm font-medium">
                    <i class="fas fa-trash-alt mr-2"></i>Clear Logs
                </a>
                <button onclick="location.reload()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all text-sm font-medium shadow-lg shadow-indigo-900/20">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>

        <div class="mb-4 text-xs font-mono text-gray-500 flex items-center gap-2">
            <span class="inline-block w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            Listening for POST requests at: <span class="text-indigo-400"><?php echo "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></span>
        </div>

        <!-- Logs -->
        <div class="space-y-6">
            <?php if (empty($log)): ?>
                <div class="bg-gray-800 rounded-2xl p-12 text-center border border-gray-700">
                    <i class="fas fa-inbox text-5xl text-gray-700 mb-4"></i>
                    <h2 class="text-xl font-semibold text-gray-400">No requests captured yet</h2>
                    <p class="text-gray-500 mt-2">Send a POST request to this URL to see it here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($log as $entry): ?>
                    <div class="bg-gray-800 rounded-2xl overflow-hidden border border-gray-700 entry-card transition-all duration-300">
                        <!-- Card Header -->
                        <div class="p-4 bg-gray-800/50 flex justify-between items-center border-b border-gray-700">
                            <div class="flex items-center gap-4">
                                <span class="px-2 py-1 bg-green-500/10 text-green-400 text-[10px] font-bold rounded border border-green-500/20 uppercase"><?php echo $entry['method']; ?></span>
                                <span class="text-gray-400 text-xs font-mono"><?php echo $entry['timestamp']; ?></span>
                                <span class="text-gray-500 text-[10px] hidden sm:inline">IP: <?php echo $entry['ip']; ?></span>
                            </div>
                            <button onclick="toggleEntry('<?php echo $entry['id']; ?>')" class="text-gray-500 hover:text-indigo-400 transition-colors">
                                <i class="fas fa-chevron-down" id="icon-<?php echo $entry['id']; ?>"></i>
                            </button>
                        </div>
                        
                        <!-- Card Content -->
                        <div id="content-<?php echo $entry['id']; ?>" class="p-6 space-y-4">
                            <div>
                                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Payload Body</h3>
                                <pre class="p-4 bg-gray-900 rounded-xl border border-gray-700 overflow-auto max-h-96 text-sm text-indigo-300"><?php echo htmlspecialchars(json_encode($entry['body'], JSON_PRETTY_PRINT)); ?></pre>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Headers</h3>
                                    <pre class="p-4 bg-gray-900/50 rounded-xl border border-gray-700 overflow-auto max-h-48 text-[11px] text-gray-400"><?php echo htmlspecialchars(json_encode($entry['headers'], JSON_PRETTY_PRINT)); ?></pre>
                                </div>
                                <div>
                                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Raw Body</h3>
                                    <pre class="p-4 bg-gray-900/50 rounded-xl border border-gray-700 overflow-auto max-h-48 text-[11px] text-gray-500"><?php echo htmlspecialchars($entry['raw_body']); ?></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleEntry(id) {
            const content = document.getElementById('content-' + id);
            const icon = document.getElementById('icon-' + id);
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            } else {
                content.classList.add('hidden');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
            }
        }
    </script>
</body>
</html>
