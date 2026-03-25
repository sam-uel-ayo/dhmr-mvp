<?php
// user/settings.php
require_once __DIR__ . '/../api/middleware/auth_check.php';
require_once __DIR__ . '/../db_connect.php';

// Fetch current user data
$stmt = $pdo->prepare("SELECT contact_name, contact_phone FROM trusted_contacts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$contacts = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT zone_name, latitude, longitude, radius_km FROM safe_zones WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$zones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHMR User Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">

    <nav class="bg-white shadow-sm p-4 flex justify-between items-center mb-8">
        <h1 class="text-xl font-bold text-gray-800">DHMR Settings</h1>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
            <button onclick="logout()" class="text-sm text-red-600 underline">Logout</button>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 p-4">
        
        <!-- Trusted Contacts -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-lg font-bold mb-4 border-b pb-2">Trusted Contacts</h2>
            <ul class="space-y-3 mb-6">
                <?php foreach($contacts as $c): ?>
                <li class="text-sm flex justify-between bg-gray-50 p-2 rounded">
                    <span><b><?php echo htmlspecialchars($c['contact_name']); ?>:</b> <?php echo htmlspecialchars($c['contact_phone']); ?></span>
                </li>
                <?php endforeach; ?>
                <?php if(empty($contacts)): ?>
                <li class="text-sm text-gray-400 italic">No contacts added yet.</li>
                <?php endif; ?>
            </ul>

            <form id="contact-form" class="space-y-3 border-t pt-4">
                <input type="text" id="contact-name" placeholder="Contact Name" required class="w-full px-3 py-2 border rounded text-sm">
                <input type="text" id="contact-phone" placeholder="Phone Number" required class="w-full px-3 py-2 border rounded text-sm">
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded text-sm hover:bg-blue-700">Add Contact</button>
            </form>
        </div>

        <!-- Safe Zones -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-lg font-bold mb-4 border-b pb-2">Safe Zones</h2>
            <ul class="space-y-3 mb-6">
                <?php foreach($zones as $z): ?>
                <li class="text-xs flex flex-col bg-gray-50 p-2 rounded">
                    <span class="font-bold"><?php echo htmlspecialchars($z['zone_name']); ?></span>
                    <span class="text-gray-500">Radius: <?php echo $z['radius_km']; ?>km | <?php echo $z['latitude']; ?>, <?php echo $z['longitude']; ?></span>
                </li>
                <?php endforeach; ?>
                <?php if(empty($zones)): ?>
                <li class="text-sm text-gray-400 italic">No safe zones defined yet.</li>
                <?php endif; ?>
            </ul>

            <div class="border-t pt-4">
                <p class="text-xs text-gray-500 mb-2 italic">Click on the map to set zone center</p>
                <div id="map" class="h-48 w-full rounded mb-3 border"></div>
                <form id="zone-form" class="space-y-3">
                    <input type="text" id="zone-name" placeholder="Zone Name (Home, Work, etc)" required class="w-full px-3 py-2 border rounded text-sm">
                    <div class="flex space-x-2">
                        <input type="text" id="zone-lat" placeholder="Lat" readonly required class="w-1/2 px-3 py-2 border rounded text-xs bg-gray-100">
                        <input type="text" id="zone-lng" placeholder="Lng" readonly required class="w-1/2 px-3 py-2 border rounded text-xs bg-gray-100">
                    </div>
                    <input type="number" id="zone-radius" step="0.1" placeholder="Radius (km)" required class="w-full px-3 py-2 border rounded text-sm">
                    <button type="submit" class="w-full bg-green-600 text-white py-2 rounded text-sm hover:bg-green-700">Save Zone</button>
                </form>
            </div>
        </div>

    </div>

    <script>
        // Map Setup
        const map = L.map('map').setView([9.0820, 8.6753], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let activeMarker = null;

        map.on('click', (e) => {
            const { lat, lng } = e.latlng;
            document.getElementById('zone-lat').value = lat.toFixed(6);
            document.getElementById('zone-lng').value = lng.toFixed(6);

            if (activeMarker) map.removeLayer(activeMarker);
            activeMarker = L.marker([lat, lng]).addTo(map);
        });

        // Contact Form
        document.getElementById('contact-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = document.getElementById('contact-name').value;
            const phone = document.getElementById('contact-phone').value;

            const res = await fetch('/api/user/add_contact.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, phone })
            });
            const result = await res.json();
            if (result.success) location.reload();
            else alert(result.error);
        });

        // Zone Form
        document.getElementById('zone-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = document.getElementById('zone-name').value;
            const lat = document.getElementById('zone-lat').value;
            const lng = document.getElementById('zone-lng').value;
            const radius = document.getElementById('zone-radius').value;

            const res = await fetch('/api/user/add_zone.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, lat, lng, radius })
            });
            const result = await res.json();
            if (result.success) location.reload();
            else alert(result.error);
        });

        async function logout() {
            await fetch('/api/auth/logout.php');
            window.location.href = '/login.html';
        }
    </script>
</body>
</html>