<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DHMR | Discreet. Intelligent. Silent.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #000; color: #fff; }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .hero-gradient { background: radial-gradient(circle at center, #111 0%, #000 100%); }
        .text-glow { text-shadow: 0 0 20px rgba(0, 122, 255, 0.5); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade { animation: fadeIn 1s ease-out forwards; }
    </style>
</head>
<body class="overflow-x-hidden">

    <!-- Navigation -->
    <nav class="fixed top-0 w-full z-50 p-6 flex justify-between items-center glass border-none">
        <div class="text-xl font-bold tracking-tighter">DHMR <span class="text-[10px] uppercase font-light tracking-[0.2em] ml-2 opacity-50">System</span></div>
        <div class="space-x-8 text-xs font-medium tracking-widest uppercase opacity-70">
            <a href="#hardware" class="hover:opacity-100 transition">Hardware</a>
            <a href="#intelligence" class="hover:opacity-100 transition">Intelligence</a>
            <a href="login.html" class="bg-white text-black px-4 py-2 rounded-full hover:bg-gray-200 transition">Get Started</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="h-screen flex flex-col items-center justify-center text-center px-6 hero-gradient relative">
        <div class="animate-fade opacity-0" style="animation-delay: 0.2s;">
            <h1 class="text-6xl md:text-8xl font-bold tracking-tighter mb-6">Silent Safety. <br> <span class="text-blue-500 text-glow">Redefined.</span></h1>
            <p class="max-w-2xl text-gray-400 text-lg md:text-xl font-light leading-relaxed mb-10">
                DHMR is the world's first AI-powered personal safety ecosystem designed to detect risk before it escalates. 
                Discreet wearables. Intelligent detection. Instant response.
            </p>
            <div class="flex space-x-4 justify-center">
                <a href="simulation.php" class="bg-blue-600 text-white px-8 py-4 rounded-full hover:bg-blue-700 transition text-sm font-bold tracking-widest uppercase shadow-[0_0_30px_rgba(0,122,255,0.4)]">Launch Simulator</a>
                <a href="hardware/wearable.php" class="border border-white/20 px-8 py-4 rounded-full hover:bg-white/5 transition text-sm font-medium">Core Vision</a>
            </div>
        </div>
        
        <!-- Subtle Scroll Indicator -->
        <div class="absolute bottom-10 animate-bounce opacity-30">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
        </div>
    </section>

    <!-- Hardware Section -->
    <section id="hardware" class="py-32 px-6 max-w-7xl mx-auto">
        <div class="grid md:grid-cols-2 gap-20 items-center">
            <div>
                <span class="text-blue-500 font-semibold tracking-widest uppercase text-xs">The Wearable</span>
                <h2 class="text-5xl font-bold tracking-tighter mt-4 mb-8 text-white">Beautifully Invisible.</h2>
                <p class="text-gray-400 text-lg font-light leading-relaxed mb-8">
                    Crafted as high-end jewelry—necklaces, bangles, and keyholders. 
                    Embedded with BLE 5.0, a capacitive touch SOS trigger, and haptic feedback. 
                    Safety has never been this elegant.
                </p>
                <ul class="space-y-4 text-sm text-gray-300">
                    <li class="flex items-center"><span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-3"></span> 48-Hour Battery Life</li>
                    <li class="flex items-center"><span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-3"></span> Water Resistant</li>
                    <li class="flex items-center"><span class="w-1.5 h-1.5 bg-blue-500 rounded-full mr-3"></span> Hidden SOS Trigger</li>
                </ul>
            </div>
            <div class="relative group">
                <!-- Visual Placeholder for Hardware -->
                <div class="aspect-square glass rounded-3xl flex items-center justify-center overflow-hidden">
                    <div class="w-48 h-48 rounded-full border-4 border-white/10 relative group-hover:scale-110 transition duration-700">
                        <div class="absolute inset-0 bg-blue-500/20 blur-3xl opacity-0 group-hover:opacity-100 transition duration-700"></div>
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-4 h-4 bg-blue-500 rounded-full animate-pulse shadow-[0_0_20px_rgba(0,122,255,1)]"></div>
                    </div>
                </div>
                <div class="absolute -bottom-6 -right-6 glass p-6 rounded-2xl">
                    <p class="text-xs font-bold tracking-widest uppercase opacity-50">Model 01: The Bangle</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Intelligence Section -->
    <section id="intelligence" class="py-32 bg-white text-black">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <span class="text-blue-600 font-semibold tracking-widest uppercase text-xs">The Risk Engine</span>
            <h2 class="text-5xl md:text-7xl font-bold tracking-tighter mt-4 mb-12">Intelligence that protects.</h2>
            <div class="grid md:grid-cols-3 gap-12 text-left mt-20">
                <div class="p-8 border border-gray-100 rounded-3xl hover:shadow-2xl transition duration-500">
                    <h3 class="text-xl font-bold mb-4">Geofence Intelligence</h3>
                    <p class="text-gray-500 font-light">Automatically monitors safe zones. Alerts are weighted by proximity to your trusted environments.</p>
                </div>
                <div class="p-8 border border-gray-100 rounded-3xl hover:shadow-2xl transition duration-500">
                    <h3 class="text-xl font-bold mb-4">Temporal Analysis</h3>
                    <p class="text-gray-500 font-light">Risk scores adjust dynamically based on local time and environmental danger levels.</p>
                </div>
                <div class="p-8 border border-gray-100 rounded-3xl hover:shadow-2xl transition duration-500">
                    <h3 class="text-xl font-bold mb-4">Movement Profiling</h3>
                    <p class="text-gray-500 font-light">Detects abnormal stationary patterns or sudden frantic movement using device accelerometry.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-20 border-t border-white/10 px-6">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center opacity-50 text-xs tracking-widest uppercase">
            <div>&copy; 2026 DHMR Systems. All Rights Reserved.</div>
            <div class="mt-6 md:mt-0 space-x-8">
                <a href="#">Privacy</a>
                <a href="#">Security</a>
                <a href="#">Infrastructure</a>
            </div>
        </div>
    </footer>

</body>
</html>