<?php
// hardware/wearable.php
require_once __DIR__ . '/../api/middleware/auth_check.php';
require_once __DIR__ . '/../db_connect.php';

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
    <title>DHMR Hardware Simulator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: radial-gradient(circle at center, #1a1a1a 0%, #000 100%); color: #fff; font-family: 'Inter', sans-serif; }
        .wearable-body { 
            width: 300px; height: 300px; 
            background: linear-gradient(145deg, #222, #000); 
            border-radius: 50%; 
            box-shadow: 20px 20px 60px #050505, -20px -20px 60px #1a1a1a, inset 0 0 20px rgba(255,255,255,0.05);
            position: relative;
            display: flex; items-center; justify-center;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .inner-ring {
            width: 240px; height: 240px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.05);
            display: flex; items-center; justify-center;
        }
        .led-indicator {
            width: 12px; height: 12px;
            border-radius: 50%;
            position: absolute;
            top: 40px;
            transition: all 0.5s ease;
        }
        .led-safe { background: #007AFF; box-shadow: 0 0 20px #007AFF; }
        .led-risk { background: #FF3B30; box-shadow: 0 0 20px #FF3B30; animation: pulse-red 1s infinite; }
        @keyframes pulse-red { 0% { opacity: 0.4; } 50% { opacity: 1; } 100% { opacity: 0.4; } }
        
        .hidden-button {
            width: 100px; height: 100px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            display: flex; items-center; justify-center;
        }
        .hidden-button:active { transform: scale(0.95); background: rgba(255,255,255,0.02); }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-6">

    <div class="mb-12 text-center">
        <h1 class="text-2xl font-bold tracking-tighter mb-2">DHMR <span class="font-light opacity-50 uppercase text-xs tracking-widest ml-2">Wearable Simulation</span></h1>
        <p class="text-gray-500 text-sm max-w-xs">Simulating "The Bangle" hidden interface. Long press the center to trigger a silent SOS.</p>
    </div>

    <div class="wearable-body">
        <div class="inner-ring">
            <div id="led" class="led-indicator <?php echo $risk_score > 60 ? 'led-risk' : 'led-safe'; ?>"></div>
            <div id="sos-trigger" class="hidden-button">
                <span class="text-[8px] uppercase tracking-[0.4em] opacity-10 font-bold">Capacitive Sensor</span>
            </div>
        </div>
    </div>

    <div class="mt-16 grid grid-cols-2 gap-8 max-w-md w-full opacity-50">
        <div class="text-center">
            <p class="text-[10px] uppercase font-bold tracking-widest mb-1">Battery</p>
            <p class="text-xl font-light"><?php echo $device ? $device['battery_level'] : '--'; ?>%</p>
        </div>
        <div class="text-center">
            <p class="text-[10px] uppercase font-bold tracking-widest mb-1">Status</p>
            <p class="text-xl font-light"><?php echo ($device && $device['is_connected']) ? 'Linked' : 'Disconnected'; ?></p>
        </div>
    </div>

    <!-- Navigation Back -->
    <div class="mt-12">
        <a href="../app/dashboard.php" class="text-xs uppercase tracking-widest text-gray-400 hover:text-white transition underline">Open Mobile App</a>
    </div>

    <script>
        let pressTimer;
        const trigger = document.getElementById('sos-trigger');

        trigger.addEventListener('mousedown', () => {
            pressTimer = setTimeout(triggerSOS, 2000); // 2 second hold
        });

        trigger.addEventListener('mouseup', () => {
            clearTimeout(pressTimer);
        });

        async function triggerSOS() {
            if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
            
            // Visual feedback
            document.getElementById('led').className = 'led-indicator led-risk';
            
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
                    alert("Silent SOS Broadcasted to Cloud.");
                }
            });
        }
    </script>

</body>
</html>