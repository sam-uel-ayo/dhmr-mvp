<?php
// admin/dashboard.php
require_once __DIR__ . '/../api/middleware/admin_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHMR Responder Dashboard (Secure)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        #map { height: 100vh; width: 100%; }
        .pulse-red { animation: pulse 1.5s infinite; }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <div class="w-1/3 bg-white shadow-xl z-10 flex flex-col">
        <div class="p-6 bg-gray-900 text-white flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold tracking-widest">DHMR ADMIN</h1>
                <p class="text-xs text-gray-400">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            </div>
            <div class="flex flex-col items-end">
                <span class="text-xs bg-green-500 px-2 py-1 rounded animate-pulse mb-2">LIVE MONITOR</span>
                <button onclick="logout()" class="text-[10px] text-gray-400 hover:text-white underline">Logout</button>
            </div>
        </div>
        <div id="alert-list" class="flex-1 overflow-y-auto p-4 space-y-4">
            <p class="text-gray-400 text-center mt-10" id="no-alerts">No active alerts. System monitoring.</p>
        </div>
    </div>

    <div class="w-2/3 relative">
        <div id="map"></div>
    </div>

    <script>
        // --- CONFIGURATION ---
        const GET_ALERTS_URL = '/api/admin/get_active_alerts.php';
        const RESOLVE_ALERT_URL = '/api/admin/resolve_alert.php';

        // Initialize Map
        const map = L.map('map').setView([9.0820, 8.6753], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(map);

        let activeAlerts = {}; // Store { id: { marker, polyline, last_pos } }

        // Custom Red Icon
        const redIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
        });

        async function fetchAlerts() {
            try {
                const response = await fetch(GET_ALERTS_URL);
                const result = await response.json();
                
                if (result.success) {
                    renderAlerts(result.data);
                }
            } catch (error) {
                console.error("Failed to fetch alerts:", error);
            }
        }

        function renderAlerts(alerts) {
            const list = document.getElementById('alert-list');
            const noAlerts = document.getElementById('no-alerts');
            
            // Track which alert IDs are currently active in this fetch
            const currentAlertIds = new Set(alerts.map(a => a.id));

            // Clear old markers/polylines for alerts that were resolved
            for (let id in activeAlerts) {
                if (!currentAlertIds.has(parseInt(id))) {
                    map.removeLayer(activeAlerts[id].marker);
                    if (activeAlerts[id].polyline) map.removeLayer(activeAlerts[id].polyline);
                    delete activeAlerts[id];
                }
            }

            if (alerts.length === 0) {
                list.innerHTML = '';
                list.appendChild(noAlerts);
                noAlerts.style.display = 'block';
                return;
            }

            noAlerts.style.display = 'none';
            let html = '';

            alerts.forEach(alert => {
                const pathCoords = alert.location_history.map(loc => [parseFloat(loc.latitude), parseFloat(loc.longitude)]);
                const latestPos = pathCoords[pathCoords.length - 1];

                // Update or Create Map Objects
                if (!activeAlerts[alert.id]) {
                    // Create new
                    const marker = L.marker(latestPos, {icon: redIcon})
                        .addTo(map)
                        .bindPopup(`<b>${alert.name}</b><br>Risk: ${alert.risk_score}`);
                    
                    const polyline = L.polyline(pathCoords, {color: 'red', weight: 4, opacity: 0.6})
                        .addTo(map);

                    activeAlerts[alert.id] = { marker, polyline, last_pos: latestPos };
                    
                    // First time seeing this alert? Center it.
                    map.setView(latestPos, 14);
                } else {
                    // Update existing
                    activeAlerts[alert.id].marker.setLatLng(latestPos);
                    activeAlerts[alert.id].polyline.setLatLngs(pathCoords);
                    activeAlerts[alert.id].last_pos = latestPos;
                }

                // Render Sidebar Card
                const isHighRisk = alert.risk_score > 50;
                let contactInfo = alert.contacts.map(c => `<p><b>${c.contact_name}:</b> ${c.contact_phone}</p>`).join('');

                html += `
                    <div class="p-4 rounded-lg border-l-4 ${isHighRisk ? 'border-red-500 bg-red-50 pulse-red' : 'border-yellow-500 bg-yellow-50'} shadow-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-bold text-gray-800">${alert.name}</h3>
                                <p class="text-xs text-gray-500">${new Date(alert.created_at).toLocaleTimeString()}</p>
                            </div>
                            <span class="px-2 py-1 rounded text-xs font-bold text-white ${isHighRisk ? 'bg-red-500' : 'bg-yellow-500'}">
                                Risk: ${alert.risk_score}
                            </span>
                        </div>
                        <div class="mt-3 text-sm text-gray-600">
                            ${contactInfo}
                            <p class="mt-1 text-[10px] text-gray-400">Current: ${latestPos[0]}, ${latestPos[1]}</p>
                        </div>
                        <button onclick="resolveAlert(${alert.id})" class="mt-3 w-full bg-gray-800 text-white text-xs py-2 rounded hover:bg-gray-700 transition">
                            Mark as Resolved
                        </button>
                    </div>
                `;
            });

            list.innerHTML = html;
        }

        async function resolveAlert(id) {
            try {
                await fetch(RESOLVE_ALERT_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ alert_id: id })
                });
                fetchAlerts(); 
            } catch (error) {
                console.error("Failed to resolve alert:", error);
            }
        }

        async function logout() {
            await fetch('/api/auth/logout.php');
            window.location.href = '/login.html'; // We need to create a login page
        }

        setInterval(fetchAlerts, 3000);
        fetchAlerts();
    </script>
</body>
</html>