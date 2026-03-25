<?php
// app/history.php
require_once __DIR__ . '/../api/middleware/auth_check.php';
require_once __DIR__ . '/../db_connect.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT alerts.created_at, alerts.risk_score, alerts.status, ir.case_id, ir.responder_notes, a.agency_name
    FROM alerts 
    LEFT JOIN incident_reports ir ON alerts.id = ir.alert_id
    LEFT JOIN authorities a ON ir.authority_id = a.id
    WHERE alerts.user_id = ?
    ORDER BY alerts.created_at DESC
");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHMR | Safety History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f0f0f7; }
        .phone-container { width: 375px; height: 812px; background: white; border-radius: 40px; box-shadow: 0 50px 100px rgba(0,0,0,0.1); overflow: hidden; position: relative; border: 8px solid #1a1a1a; }
        .notch { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 150px; height: 30px; background: #1a1a1a; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; z-index: 100; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

    <div class="phone-container flex flex-col">
        <div class="notch"></div>
        
        <div class="px-6 pt-12 pb-6 flex items-center">
            <a href="dashboard.php" class="mr-4 text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"></path></svg>
            </a>
            <h1 class="text-xl font-bold text-gray-800">Safety History</h1>
        </div>

        <div class="flex-1 overflow-y-auto px-6 pb-10">
            <?php if (empty($history)): ?>
                <div class="text-center mt-20">
                    <p class="text-gray-400 text-sm">No incidents logged.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($history as $item): ?>
                    <div class="bg-gray-50 border border-gray-100 rounded-3xl p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest"><?php echo $item['case_id']; ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?php echo date('M d, Y • h:i A', strtotime($item['created_at'])); ?></p>
                            </div>
                            <span class="px-2 py-1 rounded-full text-[8px] font-bold <?php echo $item['status'] === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                <?php echo strtoupper($item['status']); ?>
                            </span>
                        </div>
                        
                        <?php if ($item['agency_name']): ?>
                        <div class="flex items-center text-gray-600 mb-3">
                            <svg class="w-3 h-3 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                            <p class="text-[10px] font-semibold">Dispatched: <?php echo $item['agency_name']; ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($item['responder_notes']): ?>
                        <div class="bg-white rounded-2xl p-3 border border-gray-100 mt-2">
                            <p class="text-[10px] text-gray-500 italic">"<?php echo htmlspecialchars($item['responder_notes']); ?>"</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>