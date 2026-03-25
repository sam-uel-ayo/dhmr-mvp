<?php
// app/dashboard.php
require_once __DIR__ . '/../api/middleware/auth_check.php';
require_once __DIR__ . '/../db_connect.php';

// Fetch user data, device status, and latest risk score
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM devices WHERE user_id = ?");
$stmt->execute([$user_id]);
$device = $stmt->fetch();

$stmt = $pdo->prepare("SELECT score FROM risk_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$latest_risk = $stmt->fetch();
$risk_score = $latest_risk ? $latest_risk['score'] : 20;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHMR Mobile App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f0f7; }
        .phone-container { width: 375px; height: 812px; background: white; border-radius: 40px; box-shadow: 0 50px 100px rgba(0,0,0,0.1); overflow: hidden; position: relative; border: 8px solid #1a1a1a; }
        .notch { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 150px; height: 30px; background: #1a1a1a; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; z-index: 100; }
        .risk-gauge { transition: stroke-dashoffset 1s ease-out; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

    <div class="phone-container">
        <div class="notch"></div>
        
        <!-- App Header -->
        <div class="px-6 pt-12 pb-6 flex justify-between items-center">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold">Good Evening,</p>
                <h1 class="text-xl font-bold text-gray-800"><?php echo explode(' ', $_SESSION['name'])[0]; ?></h1>
            </div>
            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
        </div>

        <!-- Risk Gauge Simulation -->
        <div class="flex flex-col items-center justify-center py-10 relative">
            <svg class="w-64 h-64 transform -rotate-90">
                <circle cx="128" cy="128" r="110" stroke="#f0f0f7" stroke-width="12" fill="transparent" />
                <circle id="risk-circle" cx="128" cy="128" r="110" stroke="<?php echo $risk_score > 60 ? '#FF3B30' : ($risk_score > 30 ? '#FFCC00' : '#007AFF'); ?>" stroke-width="12" fill="transparent" stroke-dasharray="691" stroke-dashoffset="<?php echo 691 - (691 * $risk_score / 100); ?>" class="risk-gauge" stroke-linecap="round" />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="text-5xl font-bold tracking-tighter" id="risk-value"><?php echo $risk_score; ?></span>
                <span class="text-[10px] uppercase font-bold tracking-[0.2em] text-gray-400 mt-1">Risk Score</span>
            </div>
        </div>

        <!-- Status Cards -->
        <div class="px-6 grid grid-cols-2 gap-4">
            <div class="bg-blue-50 p-4 rounded-3xl border border-blue-100">
                <p class="text-[10px] uppercase font-bold text-blue-400 mb-2">Device</p>
                <div class="flex items-end justify-between">
                    <span class="text-lg font-bold text-blue-900"><?php echo $device ? $device['battery_level'] : '--'; ?>%</span>
                    <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M7 2v20h10V2H7zm8 18H9V4h6v16z"/></svg>
                </div>
            </div>
            <div class="bg-gray-50 p-4 rounded-3xl border border-gray-100">
                <p class="text-[10px] uppercase font-bold text-gray-400 mb-2">Signal</p>
                <div class="flex items-end justify-between">
                    <span class="text-lg font-bold text-gray-800"><?php echo ($device && $device['is_connected']) ? 'Strong' : 'Offline'; ?></span>
                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3C6.48 3 2 7.48 2 13s4.48 10 10 10 10-4.48 10-10S17.52 3 12 3zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="px-6 mt-8">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <button onclick="window.location.href='../user/settings.php'" class="w-full flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl shadow-sm hover:bg-gray-50 transition">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <span class="text-sm font-semibold text-gray-700">Safety Configuration</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </div>

        <!-- SOS Bottom Action -->
        <div class="absolute bottom-0 w-full p-6 bg-gradient-to-t from-white via-white to-transparent">
            <button id="sos-trigger" class="w-full bg-red-500 py-4 rounded-2xl text-white font-bold shadow-[0_10px_30px_rgba(255,59,48,0.3)] hover:bg-red-600 active:scale-95 transition flex items-center justify-center space-x-2">
                <svg class="w-5 h-5 animate-pulse" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                <span>SILENT SOS</span>
            </button>
        </div>

    </div>

    <script>
        document.getElementById('sos-trigger').addEventListener('click', async () => {
            if (!confirm("This will trigger a silent SOS. Proceed?")) return;
            
            // Simulation: Trigger from user app context
            navigator.geolocation.getCurrentPosition(async (pos) => {
                const response = await fetch('../api/trigger_alert.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: <?php echo $_SESSION['user_id']; ?>,
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude
                    })
                });
                const result = await response.json();
                if (result.success) {
                    alert("SOS Triggered. Monitoring system active.");
                    location.reload();
                }
            });
        });

        // Simple animation logic for the gauge
        function updateRiskGauge(score) {
            const circle = document.getElementById('risk-circle');
            const offset = 691 - (691 * score / 100);
            circle.style.strokeDashoffset = offset;
        }
    </script>
</body>
</html>