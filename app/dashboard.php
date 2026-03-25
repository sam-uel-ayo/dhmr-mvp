<?php
// app/dashboard.php
require_once __DIR__ . '/../api/middleware/auth_check.php';
require_once __DIR__ . '/../db_connect.php';

$user_id = $_SESSION['user_id'];

// 1. Check for Active Alert & Case Status
$stmt = $pdo->prepare("
    SELECT alerts.id, alerts.risk_score, ir.case_id, ir.dispatch_status, a.agency_name, a.agency_type
    FROM alerts 
    LEFT JOIN incident_reports ir ON alerts.id = ir.alert_id
    LEFT JOIN authorities a ON ir.authority_id = a.id
    WHERE alerts.user_id = ? AND alerts.status = 'active'
    ORDER BY alerts.created_at DESC LIMIT 1
");
$user_id = (int)$_SESSION['user_id'];
$stmt->execute([$user_id]);
$active_alert = $stmt->fetch();

// 2. Fetch device status
$stmt = $pdo->prepare("SELECT * FROM devices WHERE user_id = ?");
$stmt->execute([$user_id]);
$device = $stmt->fetch();

$risk_score = $active_alert ? $active_alert['risk_score'] : 20;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHMR | Safety Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f0f7; }
        .phone-container { width: 375px; height: 812px; background: white; border-radius: 40px; box-shadow: 0 50px 100px rgba(0,0,0,0.1); overflow: hidden; position: relative; border: 8px solid #1a1a1a; }
        .notch { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 150px; height: 30px; background: #1a1a1a; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; z-index: 100; }
        .risk-gauge { transition: stroke-dashoffset 1s ease-out; }
        .alert-active-bg { background: linear-gradient(180deg, #FF3B30 0%, #8E1F19 100%); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

    <div class="phone-container flex flex-col <?php echo $active_alert ? 'alert-active-bg text-white' : ''; ?>">
        <div class="notch"></div>
        
        <?php if (!$active_alert): ?>
        <!-- STANDARD DASHBOARD -->
        <div class="px-6 pt-12 pb-6 flex justify-between items-center">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold">Good Evening,</p>
                <h1 class="text-xl font-bold text-gray-800"><?php echo explode(' ', $_SESSION['name'])[0]; ?></h1>
            </div>
            <a href="history.php" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </a>
        </div>

        <div class="flex flex-col items-center justify-center py-10 relative">
            <svg class="w-64 h-64 transform -rotate-90">
                <circle cx="128" cy="128" r="110" stroke="#f0f0f7" stroke-width="12" fill="transparent" />
                <circle cx="128" cy="128" r="110" stroke="#007AFF" stroke-width="12" fill="transparent" stroke-dasharray="691" stroke-dashoffset="<?php echo 691 - (691 * $risk_score / 100); ?>" class="risk-gauge" stroke-linecap="round" />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="text-5xl font-bold tracking-tighter text-gray-800"><?php echo $risk_score; ?></span>
                <span class="text-[10px] uppercase font-bold tracking-[0.2em] text-gray-400 mt-1">Risk Score</span>
            </div>
        </div>

        <div class="px-6 grid grid-cols-2 gap-4">
            <div class="bg-blue-50 p-4 rounded-3xl border border-blue-100 text-blue-900">
                <p class="text-[10px] uppercase font-bold text-blue-400 mb-2">Device</p>
                <div class="flex items-end justify-between">
                    <span class="text-lg font-bold"><?php echo $device ? $device['battery_level'] : '--'; ?>%</span>
                    <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M7 2v20h10V2H7zm8 18H9V4h6v16z"/></svg>
                </div>
            </div>
            <div class="bg-gray-50 p-4 rounded-3xl border border-gray-100 text-gray-800">
                <p class="text-[10px] uppercase font-bold text-gray-400 mb-2">Safe Zones</p>
                <div class="flex items-end justify-between">
                    <span class="text-lg font-bold">Active</span>
                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                </div>
            </div>
        </div>

        <div class="px-6 mt-8">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <button onclick="window.location.href='../user/settings.php'" class="w-full flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl shadow-sm">
                    <div class="flex items-center text-gray-700">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center mr-3 text-indigo-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>
                        <span class="text-sm font-semibold">Settings</span>
                    </div>
                </button>
            </div>
        </div>

        <div class="absolute bottom-0 w-full p-6 bg-gradient-to-t from-white via-white to-transparent">
            <button id="sos-trigger" class="w-full bg-red-500 py-4 rounded-2xl text-white font-bold shadow-lg">SILENT SOS</button>
        </div>

        <?php else: ?>
        <!-- ACTIVE SOS VIEW -->
        <div class="px-8 pt-24 text-center">
            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-8 animate-pulse">
                <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            </div>
            <h2 class="text-3xl font-bold tracking-tighter mb-2">SOS ACTIVE</h2>
            <p class="text-white/60 text-sm uppercase tracking-widest font-bold mb-10"><?php echo $active_alert['case_id']; ?></p>

            <div class="space-y-6">
                <div class="bg-white/10 rounded-3xl p-6 border border-white/10">
                    <p class="text-[10px] uppercase tracking-widest font-bold opacity-60 mb-2">Protocol Status</p>
                    <p class="text-xl font-bold"><?php echo strtoupper($active_alert['dispatch_status']); ?></p>
                </div>

                <?php if ($active_alert['agency_name']): ?>
                <div class="bg-blue-400/20 rounded-3xl p-6 border border-blue-400/30">
                    <p class="text-[10px] uppercase tracking-widest font-bold opacity-60 mb-2">Help is on the way</p>
                    <p class="text-lg font-bold leading-tight"><?php echo $active_alert['agency_name']; ?></p>
                    <p class="text-xs mt-2 opacity-80"><?php echo $active_alert['agency_type']; ?> unit dispatched.</p>
                </div>
                <?php else: ?>
                <div class="bg-white/5 rounded-3xl p-6 border border-white/5">
                    <p class="text-sm italic opacity-60">Responder Command Center is analyzing your live path...</p>
                </div>
                <?php endif; ?>
            </div>

            <p class="mt-20 text-[10px] uppercase tracking-widest opacity-40 font-bold">Discreet Mode Active. Keep your device close.</p>
        </div>
        <?php endif; ?>

    </div>

    <script>
        if (document.getElementById('sos-trigger')) {
            document.getElementById('sos-trigger').addEventListener('click', async () => {
                if (!confirm("Confirm SOS?")) return;
                navigator.geolocation.getCurrentPosition(async (pos) => {
                    const res = await fetch('../api/trigger_alert.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: <?php echo $user_id; ?>, lat: pos.coords.latitude, lng: pos.coords.longitude })
                    });
                    if ((await res.json()).success) location.reload();
                });
            });
        }
        
        // Auto-refresh when SOS is active
        <?php if ($active_alert): ?>
        setInterval(() => location.reload(), 5000);
        <?php endif; ?>
    </script>
</body>
</html>