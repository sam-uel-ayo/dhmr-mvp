<?php
// user/settings.php
require_once __DIR__ . '/../api/middleware/auth_check.php';
require_once __DIR__ . '/../db_connect.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT contact_name, contact_phone FROM trusted_contacts WHERE user_id = ?");
$stmt->execute([$user_id]);
$contacts = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT zone_name, latitude, longitude, radius_km FROM safe_zones WHERE user_id = ?");
$stmt->execute([$user_id]);
$zones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHMR | Configuration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #000; color: #fff; }
        .glass-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); }
        input { background: rgba(255, 255, 255, 0.05) !important; border: 1px solid rgba(255, 255, 255, 0.1) !important; color: white !important; }
        input:focus { border-color: #007AFF !important; outline: none; }
        #map { height: 250px; border-radius: 1.5rem; filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%); }
    </style>
</head>
<body class="min-h-screen pb-20">

    <nav class="p-8 flex justify-between items-center border-b border-white/5 mb-12">
        <div>
            <h1 class="text-xl font-bold tracking-tighter">DHMR <span class="font-light opacity-30 text-[10px] uppercase tracking-widest ml-2">Settings</span></h1>
        </div>
        <div class="flex items-center space-x-6">
            <a href="../app/dashboard.php" class="text-[10px] uppercase tracking-widest text-gray-400 hover:text-white transition">App Dashboard</a>
            <button onclick="logout()" class="text-[10px] uppercase tracking-widest text-red-500 hover:text-red-400 transition">Sign Out</button>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-2 gap-12">
        
        <!-- Trusted Contacts -->
        <div class="space-y-8">
            <section class="glass-card p-8 rounded-[2.5rem]">
                <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-blue-500 mb-8">Trusted Contacts</h2>
                <div class="space-y-4 mb-10">
                    <?php foreach($contacts as $c): ?>
                    <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/5">
                        <span class="text-sm font-semibold"><?php echo htmlspecialchars($c['contact_name']); ?></span>
                        <span class="text-xs opacity-50"><?php echo htmlspecialchars($c['contact_phone']); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($contacts)): ?>
                    <p class="text-xs text-gray-500 italic">No contacts registered.</p>
                    <?php endif; ?>
                </div>

                <form id="contact-form" class="space-y-4">
                    <input type="text" id="contact-name" placeholder="Full Name" required class="w-full px-5 py-4 rounded-2xl text-sm">
                    <input type="text" id="contact-phone" placeholder="Phone Number" required class="w-full px-5 py-4 rounded-2xl text-sm">
                    <button type="submit" class="w-full bg-white text-black py-4 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-gray-200 transition">Add Contact</button>
                </form>
            </section>
        </div>

        <!-- Safe Zones -->
        <div class="space-y-8">
            <section class="glass-card p-8 rounded-[2.5rem]">
                <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-blue-500 mb-8">Dynamic Safe Zones</h2>
                <div class="space-y-4 mb-10">
                    <?php foreach($zones as $z): ?>
                    <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-semibold"><?php echo htmlspecialchars($z['zone_name']); ?></span>
                            <span class="text-[10px] opacity-50 uppercase tracking-widest"><?php echo $z['radius_km']; ?>km Radius</span>
                        </div>
                        <p class="text-[10px] opacity-30 font-mono"><?php echo $z['latitude']; ?>, <?php echo $z['longitude']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="space-y-4">
                    <p class="text-[10px] uppercase tracking-widest font-bold opacity-30 mb-2">Set New Zone on Map</p>
                    <div id="map" class="mb-6"></div>
                    <form id="zone-form" class="space-y-4">
                        <input type="text" id="zone-name" placeholder="Zone Identifier (e.g. Workspace)" required class="w-full px-5 py-4 rounded-2xl text-sm">
                        <div class="flex space-x-3">
                            <input type="text" id="zone-lat" placeholder="Lat" readonly required class="w-1/2 px-5 py-4 rounded-2xl text-xs opacity-50">
                            <input type="text" id="zone-lng" placeholder="Lng" readonly required class="w-1/2 px-5 py-4 rounded-2xl text-xs opacity-50">
                        </div>
                        <input type="number" id="zone-radius" step="0.1" placeholder="Radius in KM (e.g. 2.5)" required class="w-full px-5 py-4 rounded-2xl text-sm">
                        <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-blue-700 transition">Save Safe Zone</button>
                    </form>
                </div>
            </section>
        </div>

    </div>

    <script>
        const map = L.map('map', { zoomControl: false }).setView([6.5244, 3.3792], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let activeMarker = null;

        map.on('click', (e) => {
            const { lat, lng } = e.latlng;
            document.getElementById('zone-lat').value = lat.toFixed(6);
            document.getElementById('zone-lng').value = lng.toFixed(6);
            if (activeMarker) map.removeLayer(activeMarker);
            activeMarker = L.marker([lat, lng]).addTo(map);
        });

        document.getElementById('contact-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const res = await fetch('../api/user/add_contact.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: document.getElementById('contact-name').value, phone: document.getElementById('contact-phone').value })
            });
            if ((await res.json()).success) location.reload();
        });

        document.getElementById('zone-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const res = await fetch('../api/user/add_zone.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    name: document.getElementById('zone-name').value, 
                    lat: document.getElementById('zone-lat').value, 
                    lng: document.getElementById('zone-lng').value, 
                    radius: document.getElementById('zone-radius').value 
                })
            });
            if ((await res.json()).success) location.reload();
        });

        async function logout() {
            await fetch('/api/auth/logout.php'); window.location.href = '/login.html';
        }
    </script>
</body>
</html>