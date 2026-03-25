<?php
// admin/dashboard.php
require_once __DIR__ . '/../api/middleware/admin_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHMR | Incident Command</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #000; color: #fff; }
        #map { height: 100vh; width: 100%; filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%); }
        .glass-panel { background: rgba(10, 10, 10, 0.8); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); }
        .alert-card { background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s; }
        .alert-card:hover { background: rgba(255, 255, 255, 0.05); transform: translateY(-2px); }
        .pulse-red { animation: pulse 1.5s infinite; border-color: rgba(255, 59, 48, 0.5) !important; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(255, 59, 48, 0.2); } 70% { box-shadow: 0 0 0 15px rgba(255, 59, 48, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 59, 48, 0); } }
        .timeline-line { position: absolute; left: 7px; top: 0; bottom: 0; width: 2px; background: rgba(255,255,255,0.05); }
    </style>
</head>
<body class="flex h-screen overflow-hidden">

    <!-- Sidebar: Incident List -->
    <div class="w-1/3 glass-panel z-10 flex flex-col shadow-2xl border-r border-white/5">
        <div class="p-8 border-b border-white/5 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold tracking-tighter">DHMR <span class="font-light opacity-30 text-[10px] uppercase tracking-widest ml-2">Responder</span></h1>
                <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-1">Incident Command Center</p>
            </div>
            <button onclick="logout()" class="text-[10px] text-gray-500 hover:text-white underline uppercase tracking-widest transition">Sign Out</button>
        </div>
        
        <div id="alert-list" class="flex-1 overflow-y-auto p-6 space-y-4">
            <p class="text-gray-500 text-center text-xs mt-20" id="no-alerts">System state: Operational. No active risks.</p>
        </div>
    </div>

    <!-- Map & Incident Detail Overlay -->
    <div class="w-2/3 relative flex flex-col">
        <div id="map"></div>
        
        <!-- Incident Detail (Bottom Sheet / Sidebar) -->
        <div id="incident-detail" class="hidden absolute right-0 top-0 bottom-0 w-[450px] bg-black/90 backdrop-blur-3xl border-l border-white/10 z-30 p-8 overflow-y-auto">
            <div class="flex justify-between items-start mb-8">
                <div>
                    <span id="detail-case-id" class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">DHMR-CASE-001</span>
                    <h2 id="detail-user-name" class="text-3xl font-bold tracking-tighter mt-1">Seyi Adebowale</h2>
                </div>
                <button onclick="closeIncident()" class="text-gray-500 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Dispatch Action -->
            <div class="mb-10 p-6 rounded-3xl bg-blue-500/10 border border-blue-500/20">
                <h3 class="text-xs font-bold uppercase tracking-widest text-blue-400 mb-4">Authority Dispatch</h3>
                <div class="flex space-x-2">
                    <select id="authority-select" class="flex-1 bg-black/50 border border-white/10 rounded-xl px-4 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                        <!-- Options populated via JS -->
                    </select>
                    <button onclick="dispatchAuthority()" class="bg-blue-600 px-6 py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-blue-700 transition">Dispatch</button>
                </div>
            </div>

            <!-- Timeline -->
            <div class="mb-10">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-6">Incident Timeline</h3>
                <div id="timeline-list" class="relative space-y-8">
                    <div class="timeline-line"></div>
                    <!-- Timeline items populated via JS -->
                </div>
            </div>

            <!-- Responder Notes -->
            <div class="mb-10">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-4">Responder Notes</h3>
                <textarea id="responder-notes" class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-xs text-white focus:outline-none focus:border-white/20 h-24 mb-3" placeholder="Add update..."></textarea>
                <button onclick="updateNotes()" class="w-full border border-white/10 py-3 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-white/5 transition">Save Update</button>
            </div>

            <button onclick="resolveAlertV4()" class="w-full bg-white text-black py-4 rounded-3xl font-bold text-xs uppercase tracking-widest hover:bg-gray-200 transition">Finalize & Resolve Case</button>
        </div>

        <div class="absolute top-6 right-6 z-20 glass-panel px-4 py-2 rounded-full border border-white/10 flex items-center space-x-3">
            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-[10px] uppercase font-bold tracking-widest opacity-70">Infrastructure: Online</span>
        </div>
    </div>

    <script>
        const GET_ALERTS_URL = '/api/admin/get_active_alerts.php';
        const RESOLVE_ALERT_URL = '/api/admin/resolve_alert.php';
        const DISPATCH_URL = '/api/admin/dispatch.php';

        const map = L.map('map', { zoomControl: false }).setView([6.5244, 3.3792], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let activeAlerts = {};
        let selectedAlertId = null;
        let authorities = [];

        const redIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34],
        });

        async function fetchAlerts() {
            try {
                const response = await fetch(GET_ALERTS_URL);
                const result = await response.json();
                if (result.success) {
                    authorities = result.authorities;
                    populateAuthorities();
                    renderAlerts(result.data);
                    if (selectedAlertId) {
                        const active = result.data.find(a => a.id === selectedAlertId);
                        if (active) updateDetailView(active);
                    }
                }
            } catch (error) { console.error("Monitor Sync Error:", error); }
        }

        function populateAuthorities() {
            const select = document.getElementById('authority-select');
            if (select.children.length > 0) return;
            authorities.forEach(auth => {
                const opt = document.createElement('option');
                opt.value = auth.id;
                opt.textContent = `${auth.agency_name} (${auth.agency_type})`;
                select.appendChild(opt);
            });
        }

        function renderAlerts(alerts) {
            const list = document.getElementById('alert-list');
            const noAlerts = document.getElementById('no-alerts');
            const currentAlertIds = new Set(alerts.map(a => a.id));

            for (let id in activeAlerts) {
                if (!currentAlertIds.has(parseInt(id))) {
                    map.removeLayer(activeAlerts[id].marker);
                    if (activeAlerts[id].polyline) map.removeLayer(activeAlerts[id].polyline);
                    delete activeAlerts[id];
                }
            }

            if (alerts.length === 0) {
                list.innerHTML = ''; list.appendChild(noAlerts);
                noAlerts.style.display = 'block';
                return;
            }

            noAlerts.style.display = 'none';
            let html = '';

            alerts.forEach(alert => {
                const pathCoords = alert.location_history.map(loc => [parseFloat(loc.latitude), parseFloat(loc.longitude)]);
                const latestPos = pathCoords[pathCoords.length - 1];

                if (!activeAlerts[alert.id]) {
                    const marker = L.marker(latestPos, {icon: redIcon}).addTo(map);
                    const polyline = L.polyline(pathCoords, {color: '#FF3B30', weight: 4, opacity: 0.6, lineCap: 'round'}).addTo(map);
                    activeAlerts[alert.id] = { marker, polyline };
                    map.setView(latestPos, 14);
                } else {
                    activeAlerts[alert.id].marker.setLatLng(latestPos);
                    activeAlerts[alert.id].polyline.setLatLngs(pathCoords);
                }

                const isHighRisk = alert.risk_score > 60;
                html += `
                    <div onclick="openIncident(${alert.id})" class="alert-card p-6 rounded-3xl cursor-pointer ${isHighRisk ? 'pulse-red' : ''} ${selectedAlertId === alert.id ? 'border-blue-500 bg-white/5' : ''}">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <span class="text-[8px] font-bold text-blue-500 uppercase tracking-widest">${alert.case_id}</span>
                                <h3 class="font-bold text-white text-md tracking-tight mt-1">${alert.name}</h3>
                            </div>
                            <div class="px-2 py-1 rounded-full text-[8px] font-bold ${isHighRisk ? 'bg-red-500 text-white' : 'bg-yellow-500 text-black'}">
                                ${alert.risk_score}%
                            </div>
                        </div>
                        <p class="text-[10px] opacity-40 uppercase tracking-widest">Status: ${alert.dispatch_status}</p>
                    </div>
                `;
            });
            list.innerHTML = html;
        }

        function openIncident(id) {
            selectedAlertId = id;
            document.getElementById('incident-detail').classList.remove('hidden');
            fetchAlerts(); // Instant refresh to get data
        }

        function closeIncident() {
            selectedAlertId = null;
            document.getElementById('incident-detail').classList.add('hidden');
        }

        function updateDetailView(alert) {
            document.getElementById('detail-case-id').textContent = alert.case_id;
            document.getElementById('detail-user-name').textContent = alert.name;
            document.getElementById('responder-notes').value = alert.responder_notes || '';

            const timelineList = document.getElementById('timeline-list');
            timelineList.innerHTML = '<div class="timeline-line"></div>';
            
            alert.timeline.forEach(event => {
                const item = `
                    <div class="relative pl-8">
                        <div class="absolute left-0 top-1.5 w-4 h-4 rounded-full bg-blue-500 border-4 border-black"></div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1">${event.event_type} <span class="ml-2 opacity-50 font-normal">${new Date(event.created_at).toLocaleTimeString()}</span></p>
                        <p class="text-xs text-white leading-relaxed font-light">${event.event_details}</p>
                    </div>
                `;
                timelineList.insertAdjacentHTML('beforeend', item);
            });
        }

        async function dispatchAuthority() {
            const authId = document.getElementById('authority-select').value;
            const res = await fetch(DISPATCH_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ alert_id: selectedAlertId, authority_id: authId })
            });
            const result = await res.json();
            if (result.success) fetchAlerts();
            else alert(result.error);
        }

        async function updateNotes() {
            const notes = document.getElementById('responder-notes').value;
            const res = await fetch('/api/admin/update_notes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ alert_id: selectedAlertId, notes })
            });
            if ((await res.json()).success) alert("Notes updated.");
        }

        async function resolveAlertV4() {
            if (!confirm("Confirm case resolution? This will close the incident.")) return;
            const res = await fetch(RESOLVE_ALERT_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ alert_id: selectedAlertId })
            });
            if ((await res.json()).success) {
                closeIncident();
                fetchAlerts();
            }
        }

        async function logout() {
            await fetch('/api/auth/logout.php'); window.location.href = '/login.html';
        }

        setInterval(fetchAlerts, 3000); fetchAlerts();
    </script>
</body>
</html>