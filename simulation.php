<?php
// simulation.php
require_once __DIR__ . '/db_connect.php';
session_start();

// Ensure we have a demo user logged in if not already
if (!isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = 'user@dhmr.com' LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = 'user';
    }
}

$user_id = $_SESSION['user_id'] ?? 2; // Default to demo user
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHMR | Unified Simulation Environment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0a0a0a; color: #fff; overflow-x: hidden; }
        
        /* Phone Frame */
        .phone-wrapper {
            position: relative;
            width: 380px;
            height: 780px;
            background: #1a1a1a;
            border-radius: 50px;
            padding: 12px;
            box-shadow: 0 0 0 4px #333, 0 30px 60px rgba(0,0,0,0.8);
            margin: 20px auto;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .phone-screen {
            position: relative;
            width: 100%;
            height: 100%;
            background: #000;
            border-radius: 40px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .phone-notch {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 160px;
            height: 30px;
            background: #1a1a1a;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            z-index: 100;
        }

        /* Glassmorphism */
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .glass-dark { background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(20px); border-top: 1px solid rgba(255, 255, 255, 0.1); }

        /* Animations */
        .fade-enter { opacity: 0; transform: translateY(10px); }
        .fade-enter-active { transition: all 0.3s ease-out; opacity: 1; transform: translateY(0); }
        
        /* Risk Gauge */
        .risk-circle { transition: stroke-dashoffset 1s ease-in-out; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

        #map-settings { height: 200px; width: 100%; border-radius: 20px; filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%); }

        /* SOS Pulse */
        @keyframes sos-pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .pulse-red { animation: sos-pulse 2s infinite; }
    </style>
</head>
<body x-data="simulation()" x-init="init()">

    <div class="flex flex-col md:flex-row min-h-screen items-center justify-center p-6 space-y-12 md:space-y-0 md:space-x-20">
        
        <!-- Simulation Controls (Left Side) -->
        <div class="w-full max-w-xs space-y-6">
            <div class="glass p-6 rounded-3xl">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center font-black italic tracking-tighter text-xs">DH</div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-white">Simulation Hub</h2>
                </div>
                <div class="space-y-3">
                    <button @click="view = 'lock'" :class="view === 'lock' ? 'bg-white text-black' : 'bg-white/5'" class="w-full py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition">Lock Screen</button>
                    <button @click="view = 'home'" :class="view === 'home' ? 'bg-white text-black' : 'bg-white/5'" class="w-full py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition">Home Screen</button>
                    <button @click="openApp('dashboard')" :class="view === 'app' && activeApp === 'dashboard' ? 'bg-white text-black' : 'bg-white/5'" class="w-full py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition">DHMR Application</button>
                </div>
            </div>

            <div class="glass p-6 rounded-3xl">
                <h2 class="text-xs font-bold uppercase tracking-widest text-red-500 mb-4">Emergency Trigger</h2>
                <p class="text-[10px] text-gray-500 mb-6 leading-relaxed opacity-60">This simulates the physical interaction with the DHMR wearable jewelry.</p>
                <button @click="triggerSOS()" class="w-full bg-red-600/10 border border-red-500/20 text-red-500 py-4 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] hover:bg-red-600 hover:text-white transition active:scale-95 pulse-red">PHYSICAL TRIGGER</button>
            </div>

            <div class="glass p-6 rounded-3xl" x-show="sosActive">
                <h2 class="text-xs font-bold uppercase tracking-widest text-green-500 mb-2">Satellite Sync</h2>
                <p class="text-[10px] text-gray-400">GPS polling active (10s window)</p>
                <div class="mt-4 flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-[10px] font-mono text-gray-300" x-text="`${lat.toFixed(4)}, ${lng.toFixed(4)}`"></span>
                </div>
            </div>
        </div>

        <!-- Phone Simulation -->
        <div class="phone-wrapper" :class="sosActive ? 'scale-105 border-red-500/50' : ''">
            <div class="phone-notch"></div>
            
            <div class="phone-screen relative shadow-inner">
                
                <!-- Status Bar -->
                <div class="px-8 pt-4 pb-2 flex justify-between items-center z-50 text-[10px] font-bold" :class="view === 'app' && activeApp === 'dashboard' && !sosActive ? 'text-gray-900' : 'text-white'">
                    <span x-text="time"></span>
                    <div class="flex items-center space-x-1.5">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M3 5a2 2 0 012-2h10a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V5zm2 1h10V5H5v1z"/></svg>
                    </div>
                </div>

                <!-- VIEW: LOCK SCREEN -->
                <div x-show="view === 'lock'" x-transition class="absolute inset-0 flex flex-col items-center pt-24 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1620121692029-d088224efc74?auto=format&fit=crop&q=80&w=375&h=812');">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                    <div class="relative z-10 text-center flex flex-col h-full w-full">
                        <div class="mt-8">
                            <h1 class="text-7xl font-light mb-2 text-white/90" x-text="time"></h1>
                            <p class="text-white/60 text-lg tracking-wide" x-text="date"></p>
                        </div>
                        
                        <div class="flex-1"></div>

                        <div @click="unlock()" class="mb-32 flex flex-col items-center space-y-4 cursor-pointer group">
                            <div class="w-16 h-16 rounded-full glass flex items-center justify-center group-hover:bg-white/20 transition duration-500">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 00-2 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                            <span class="text-[10px] uppercase tracking-[0.4em] font-black text-white/40">Swipe up to unlock</span>
                        </div>
                    </div>

                    <!-- Lock Screen SOS Button (Discreet) -->
                    <div class="absolute bottom-12 left-0 right-0 flex justify-center z-20">
                        <div @click="triggerSOS()" class="w-14 h-14 rounded-full glass flex items-center justify-center active:scale-90 transition shadow-2xl border-red-500/20">
                             <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                        </div>
                    </div>
                </div>

                <!-- VIEW: HOME SCREEN -->
                <div x-show="view === 'home'" x-transition class="absolute inset-0 flex flex-col items-center pt-20 px-8 bg-cover bg-center" style="background-image: url('https://images.unsplash.com/photo-1620121692029-d088224efc74?auto=format&fit=crop&q=80&w=375&h=812'); filter: brightness(0.7);">
                    <div class="grid grid-cols-4 gap-6 w-full">
                        <div @click="openApp('dashboard')" class="flex flex-col items-center space-y-2 cursor-pointer transition active:scale-90 group">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl flex items-center justify-center shadow-2xl group-hover:scale-105 transition">
                                <span class="text-lg font-black italic tracking-tighter text-white">DH</span>
                            </div>
                            <span class="text-[10px] font-bold text-white shadow-sm">DHMR</span>
                        </div>
                        <!-- Other Dummy Apps -->
                        <template x-for="i in 11">
                            <div class="flex flex-col items-center space-y-2 opacity-30">
                                <div class="w-14 h-14 bg-white/20 rounded-2xl"></div>
                                <div class="w-8 h-1.5 bg-white/20 rounded-full"></div>
                            </div>
                        </template>
                    </div>

                    <!-- Dock -->
                    <div class="absolute bottom-10 left-6 right-6 h-20 glass rounded-[2.5rem] flex items-center justify-around px-4">
                        <div class="w-12 h-12 bg-green-500 rounded-2xl shadow-lg"></div>
                        <div class="w-12 h-12 bg-blue-400 rounded-2xl shadow-lg"></div>
                        <div class="w-12 h-12 bg-white/20 rounded-2xl shadow-lg"></div>
                        <div class="w-12 h-12 bg-gray-600 rounded-2xl shadow-lg"></div>
                    </div>
                </div>

                <!-- VIEW: DHMR APP -->
                <div x-show="view === 'app'" x-transition class="absolute inset-0 bg-[#f8f8fb] text-gray-900 flex flex-col">
                    
                    <!-- App Navigation -->
                    <div class="px-6 pt-12 pb-4 flex justify-between items-center z-10" :class="sosActive ? 'bg-red-600 text-white' : ''">
                        <h2 class="font-black tracking-tighter text-xl italic">DHMR</h2>
                        <div @click="activeApp = 'settings'; initMap();" class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 cursor-pointer shadow-sm hover:bg-gray-200 transition" :class="sosActive ? 'bg-white/20 text-white' : ''">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                        </div>
                    </div>

                    <!-- App Content: Dashboard -->
                    <div x-show="activeApp === 'dashboard'" class="flex-1 overflow-y-auto px-6 pb-24">
                        
                        <!-- SOS ACTIVE VIEW -->
                        <template x-if="sosActive">
                            <div class="mt-8 space-y-6">
                                <div class="bg-red-500 p-8 rounded-[2.5rem] text-white text-center shadow-2xl pulse-red">
                                    <h3 class="text-4xl font-black tracking-tighter mb-2">SOS ACTIVE</h3>
                                    <p class="text-[10px] uppercase font-black tracking-[0.2em] opacity-70" x-text="activeCaseId"></p>
                                </div>
                                
                                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100">
                                    <p class="text-[10px] uppercase tracking-widest font-black text-gray-400 mb-2">Protocol Status</p>
                                    <p class="text-xl font-bold text-gray-800" x-text="dispatchStatus"></p>
                                    <div class="mt-4 flex space-x-2">
                                        <div class="flex-1 h-1.5 bg-green-500 rounded-full"></div>
                                        <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-500 animate-pulse w-full"></div>
                                        </div>
                                        <div class="flex-1 h-1.5 bg-gray-100 rounded-full"></div>
                                    </div>
                                </div>

                                <div class="bg-blue-600 p-6 rounded-[2rem] text-white shadow-xl relative overflow-hidden">
                                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full"></div>
                                    <p class="text-[10px] uppercase tracking-widest font-black opacity-60 mb-2">Security Response</p>
                                    <p class="text-lg font-bold leading-tight" x-text="responderAgency || 'Analyzing movement path...'"></p>
                                </div>
                            </div>
                        </template>

                        <!-- NORMAL VIEW -->
                        <template x-if="!sosActive">
                            <div class="mt-4">
                                <div class="flex items-center justify-between mb-8">
                                    <div>
                                        <p class="text-[10px] uppercase tracking-widest font-black text-gray-400 mb-1">Welcome back,</p>
                                        <h3 class="text-xl font-bold tracking-tight"><?php echo explode(' ', $_SESSION['name'])[0]; ?></h3>
                                    </div>
                                    <div class="w-10 h-10 rounded-2xl bg-white shadow-sm flex items-center justify-center text-green-500 border border-gray-50">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    </div>
                                </div>

                                <!-- Risk Gauge -->
                                <div class="flex flex-col items-center justify-center py-10 relative">
                                    <svg class="w-56 h-56 transform -rotate-90">
                                        <circle cx="112" cy="112" r="95" stroke="#f0f0f7" stroke-width="16" fill="transparent" />
                                        <circle cx="112" cy="112" r="95" stroke="#3b82f6" stroke-width="16" fill="transparent" stroke-dasharray="597" :stroke-dashoffset="597 - (597 * riskScore / 100)" class="risk-circle" stroke-linecap="round" />
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                        <span class="text-5xl font-black tracking-tighter text-gray-800" x-text="riskScore"></span>
                                        <span class="text-[10px] uppercase font-black tracking-[0.2em] text-gray-400 mt-1">Risk Factor</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-50">
                                        <p class="text-[10px] font-black text-gray-400 uppercase mb-3 tracking-widest">Battery</p>
                                        <div class="flex items-end justify-between">
                                            <span class="text-xl font-bold">94%</span>
                                            <div class="w-5 h-2.5 bg-green-500 rounded-[2px] mb-1"></div>
                                        </div>
                                    </div>
                                    <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-50">
                                        <p class="text-[10px] font-black text-gray-400 uppercase mb-3 tracking-widest">Safe Zones</p>
                                        <div class="flex items-end justify-between">
                                            <span class="text-xl font-bold text-green-600 tracking-tight">Active</span>
                                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8 bg-black text-white p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden group cursor-pointer" @click="triggerSOS()">
                                    <div class="absolute right-0 top-0 w-40 h-40 bg-red-600/30 rounded-full blur-3xl -mr-10 -mt-10 group-hover:scale-150 transition duration-700"></div>
                                    <h4 class="text-sm font-black uppercase tracking-[0.2em] mb-2">SILENT SOS</h4>
                                    <p class="text-[10px] opacity-50 mb-6 leading-relaxed">Direct connection to command center.</p>
                                    <div class="flex items-center text-[10px] font-black text-red-500 uppercase tracking-[0.3em]">
                                        HOLD TO TRIGGER
                                        <svg class="w-3 h-3 ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- App Content: Settings -->
                    <div x-show="activeApp === 'settings'" class="flex-1 overflow-y-auto px-6 pb-24 bg-white" x-transition>
                        <div class="flex items-center mt-6 mb-8">
                            <button @click="activeApp = 'dashboard'" class="mr-4 text-gray-400 p-2 hover:bg-gray-100 rounded-full transition"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button>
                            <h3 class="text-xl font-black tracking-tight">Configuration</h3>
                        </div>

                        <div class="space-y-10">
                            <section>
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-6">Trusted Contacts</h4>
                                <div class="space-y-4">
                                    <template x-for="contact in contacts">
                                        <div class="flex justify-between items-center bg-gray-50 p-5 rounded-[1.5rem] border border-gray-100">
                                            <div>
                                                <p class="text-sm font-bold text-gray-800" x-text="contact.contact_name"></p>
                                                <p class="text-[10px] text-gray-400 font-mono mt-0.5" x-text="contact.contact_phone"></p>
                                            </div>
                                            <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-blue-500 shadow-sm">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                                            </div>
                                        </div>
                                    </template>
                                    <button class="w-full py-5 border-2 border-dashed border-gray-100 rounded-[1.5rem] text-[10px] font-black uppercase tracking-[0.2em] text-gray-300 hover:border-blue-500 hover:text-blue-500 transition">Add New Record</button>
                                </div>
                            </section>

                            <section>
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-6">Safe Zones</h4>
                                <div class="rounded-[2rem] overflow-hidden border border-gray-100 mb-6">
                                    <div id="map-settings"></div>
                                </div>
                                <div class="space-y-4">
                                    <template x-for="zone in zones">
                                        <div class="bg-gray-50 p-5 rounded-[1.5rem] border border-gray-100">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-sm font-bold text-gray-800" x-text="zone.zone_name"></span>
                                                <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest" x-text="`${zone.radius_km}km`"></span>
                                            </div>
                                            <p class="text-[10px] text-gray-400 font-mono" x-text="`${zone.latitude}, ${zone.longitude}`"></p>
                                        </div>
                                    </template>
                                </div>
                            </section>
                        </div>
                    </div>

                    <!-- App Content: History -->
                    <div x-show="activeApp === 'history'" class="flex-1 overflow-y-auto px-6 pb-24 bg-white" x-transition>
                        <div class="mt-12 mb-8">
                            <h3 class="text-xl font-black tracking-tight">Security Timeline</h3>
                        </div>
                        <div class="space-y-4">
                            <template x-for="item in history">
                                <div class="bg-gray-50 p-6 rounded-[2rem] border border-gray-100 shadow-sm">
                                    <div class="flex justify-between items-start mb-3">
                                        <span class="text-[10px] font-black text-blue-600 uppercase tracking-widest" x-text="item.case_id"></span>
                                        <span class="text-[8px] px-2.5 py-1 rounded-full bg-green-100 text-green-700 font-black uppercase tracking-widest" x-text="item.status"></span>
                                    </div>
                                    <p class="text-sm font-bold text-gray-800" x-text="item.agency_name || 'Protocol Analyzed'"></p>
                                    <p class="text-[10px] text-gray-400 mt-1 font-medium" x-text="formatDate(item.created_at)"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Bottom Nav -->
                    <div class="absolute bottom-0 left-0 right-0 h-20 glass-dark flex items-center justify-around px-10 z-[70] border-t border-gray-100" :class="sosActive ? 'bg-red-900 border-red-800 text-white' : 'bg-white/80 backdrop-blur-xl'">
                        <div @click="activeApp = 'dashboard'" class="flex flex-col items-center space-y-1.5 cursor-pointer" :class="activeApp === 'dashboard' ? (sosActive ? 'text-white' : 'text-blue-600') : 'text-gray-300'">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                            <span class="text-[8px] uppercase font-black tracking-[0.2em]">Dash</span>
                        </div>
                        <div @click="activeApp = 'history'" class="flex flex-col items-center space-y-1.5 cursor-pointer" :class="activeApp === 'history' ? (sosActive ? 'text-white' : 'text-blue-600') : 'text-gray-300'">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/></svg>
                            <span class="text-[8px] uppercase font-black tracking-[0.2em]">History</span>
                        </div>
                    </div>
                </div>

                <!-- Home Bar -->
                <div class="absolute bottom-1.5 left-1/2 -translate-x-1/2 w-32 h-1 bg-white/30 rounded-full z-[80]" :class="view === 'app' && activeApp !== 'dashboard' && !sosActive ? 'bg-black/10' : ''" @click="goHome()"></div>
            </div>
        </div>

        <!-- System Logs (Right Side) -->
        <div class="w-full max-w-xs space-y-6 hidden lg:block">
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-500 flex items-center">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse"></span>
                Live Infrastructure Logs
            </h2>
            <div class="space-y-3 h-[680px] overflow-y-auto pr-2 custom-scrollbar">
                <template x-for="log in logs">
                    <div class="glass p-4 rounded-2xl border-l-2 border-blue-500 transition hover:bg-white/10">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-[8px] font-mono text-blue-400" x-text="log.time"></span>
                            <span class="text-[8px] font-bold uppercase text-gray-500" x-text="log.tag"></span>
                        </div>
                        <p class="text-[10px] text-gray-300 font-medium leading-relaxed" x-text="log.msg"></p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function simulation() {
            return {
                view: 'lock',
                activeApp: 'dashboard',
                isLoggedIn: true,
                sosActive: false,
                activeAlertId: null,
                activeCaseId: '',
                dispatchStatus: 'Pending',
                responderAgency: '',
                riskScore: 20,
                time: '',
                date: '',
                lat: 6.5244,
                lng: 3.3792,
                logs: [],
                contacts: [],
                zones: [],
                history: [],
                trackingInterval: null,
                map: null,

                init() {
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);
                    this.fetchAppData();
                    this.addLog('SYSTEM', 'DHMR Unified Simulator v2.2 active.');
                    this.addLog('AUTH', 'Session persistent for: <?php echo $_SESSION['name']; ?>');
                },

                updateClock() {
                    const now = new Date();
                    this.time = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
                    this.date = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
                },

                addLog(tag, msg) {
                    this.logs.unshift({ time: new Date().toLocaleTimeString(), tag, msg });
                    if (this.logs.length > 30) this.logs.pop();
                },

                async fetchAppData() {
                    try {
                        this.contacts = [
                            { contact_name: 'Seyi Adebowale', contact_phone: '+234 803 123 4567' },
                            { contact_name: 'Tunde Phillips', contact_phone: '+234 812 987 6543' }
                        ];
                        this.zones = [
                            { zone_name: 'Home Base', latitude: 6.5244, longitude: 3.3792, radius_km: 0.5 },
                            { zone_name: 'Workplace', latitude: 6.4281, longitude: 3.4219, radius_km: 0.3 }
                        ];
                        
                        this.history = [
                            { case_id: 'DHMR-20260328-X9R2', status: 'resolved', agency_name: 'Lagos State Police HQ', created_at: '2026-03-28 22:15:00' },
                            { case_id: 'DHMR-20260325-A1B2', status: 'resolved', agency_name: 'Red Line Security', created_at: '2026-03-25 10:30:00' }
                        ];
                    } catch (e) {
                        this.addLog('ERROR', 'Data sync failed.');
                    }
                },

                unlock() {
                    this.view = 'home';
                    this.addLog('OS', 'Biometric unlock successful.');
                },

                goHome() {
                    if (this.view === 'app') {
                        this.view = 'home';
                    } else if (this.view === 'home') {
                        this.view = 'lock';
                    }
                },

                openApp(app) {
                    this.view = 'app';
                    this.activeApp = app;
                    this.addLog('APP', `Accessing DHMR Intelligence...`);
                },

                initMap() {
                    if (this.map) return;
                    setTimeout(() => {
                        this.map = L.map('map-settings', { zoomControl: false }).setView([6.5244, 3.3792], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);
                        this.zones.forEach(z => {
                            L.circle([z.latitude, z.longitude], { radius: z.radius_km * 1000, color: '#3b82f6', fillOpacity: 0.1 }).addTo(this.map);
                        });
                    }, 100);
                },

                async triggerSOS() {
                    if (this.sosActive) return;
                    
                    this.addLog('HARDWARE', 'SOS event detected via wearable capacitive trigger.');
                    
                    if (navigator.vibrate) navigator.vibrate([300, 100, 300]);

                    navigator.geolocation.getCurrentPosition(async (pos) => {
                        this.lat = pos.coords.latitude;
                        this.lng = pos.coords.longitude;
                        
                        this.addLog('GPS', `High-precision coordinates: ${this.lat.toFixed(5)}, ${this.lng.toFixed(5)}`);

                        try {
                            const res = await fetch('/api/trigger_alert.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ user_id: <?php echo $user_id; ?>, lat: this.lat, lng: this.lng })
                            });
                            const result = await res.json();
                            
                            if (result.success) {
                                this.sosActive = true;
                                this.activeAlertId = result.alert_id;
                                this.activeCaseId = result.case_id;
                                this.riskScore = result.risk_score;
                                this.view = 'app';
                                this.activeApp = 'dashboard';
                                this.addLog('CLOUD', `Protocol Initialized. Case ID: ${result.case_id}`);
                                this.startTracking();
                                this.pollStatus();
                            }
                        } catch (e) {
                            this.addLog('ERROR', 'Transmission failed. Retrying via local mesh...');
                        }
                    });
                },

                startTracking() {
                    this.trackingInterval = setInterval(() => {
                        navigator.geolocation.getCurrentPosition(async (pos) => {
                            this.lat = pos.coords.latitude;
                            this.lng = pos.coords.longitude;
                            
                            await fetch('/api/update_location.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ alert_id: this.activeAlertId, lat: this.lat, lng: this.lng })
                            });
                            this.addLog('GPS', 'Breadcrumb transmitted.');
                        });
                    }, 10000);
                },

                async pollStatus() {
                    const poll = setInterval(async () => {
                        if (!this.sosActive) {
                            clearInterval(poll);
                            return;
                        }

                        try {
                            const res = await fetch('/api/user/get_alert_status.php');
                            const result = await res.json();
                            
                            if (result.success && result.hasActiveAlert) {
                                const myAlert = result.data;
                                this.dispatchStatus = (myAlert.dispatch_status || 'Pending').toUpperCase();
                                this.responderAgency = myAlert.agency_name;
                            } else if (result.success && !result.hasActiveAlert && result.status === 'resolved') {
                                this.sosActive = false;
                                clearInterval(this.trackingInterval);
                                this.addLog('SYSTEM', 'Protocol resolved by command center.');
                                alert("Responder has resolved the incident. Status: SECURE.");
                                clearInterval(poll);
                            }
                        } catch (e) {}
                    }, 5000);
                },

                formatDate(dateStr) {
                    const d = new Date(dateStr);
                    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
            }
        }
    </script>
</body>
</html>