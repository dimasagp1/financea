<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | {{ \App\Models\Setting::get('app_name', 'Finance Monitoring') }}</title>
    @if(\App\Models\Setting::get('app_favicon'))
        <link rel="icon" href="{{ Storage::url(\App\Models\Setting::get('app_favicon')) }}" type="image/x-icon">
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; }
        
        /* Background & Grid */
        .bg-finance {
            background: radial-gradient(circle at 100% 100%, #1e1b4b 0%, #0f172a 60%, #020617 100%);
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            overflow: hidden;
            z-index: -1;
        }
        .bg-grid {
            position: absolute;
            top: -50px; left: 0; right: 0; bottom: -50px;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: panGrid 20s linear infinite;
        }
        @keyframes panGrid {
            0% { transform: translateY(0); }
            100% { transform: translateY(50px); }
        }

        /* Animated Chart Line */
        .chart-line {
            fill: none;
            stroke: url(#chartGradient);
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 3000;
            stroke-dashoffset: 3000;
            animation: drawChart 10s ease-in-out infinite alternate;
            filter: drop-shadow(0 0 12px rgba(16, 185, 129, 0.6));
        }
        @keyframes drawChart {
            0% { stroke-dashoffset: 3000; }
            40% { stroke-dashoffset: 0; }
            100% { stroke-dashoffset: 0; } /* Hold full line */
        }

        /* Floating Elements */
        .float-fast { animation: float 5s ease-in-out infinite; }
        .float-med { animation: float 8s ease-in-out infinite; }
        .float-slow { animation: float 12s ease-in-out infinite; }
        
        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }

        /* Glass Card */
        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        /* Input Styling */
        input {
            background: rgba(30, 41, 59, 0.5) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
        }
        input:focus {
            background: rgba(30, 41, 59, 0.8) !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2) !important;
        }
        input::placeholder { color: #64748b !important; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 text-slate-300">

    {{-- Financial Animated Background --}}
    <div class="bg-finance">
        <div class="bg-grid"></div>
        
        {{-- Animated Chart SVG --}}
        <svg class="absolute bottom-0 left-0 w-full h-2/3 opacity-30" preserveAspectRatio="none" viewBox="0 0 1000 300" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="chartGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="#3b82f6" />
                    <stop offset="50%" stop-color="#10b981" />
                    <stop offset="100%" stop-color="#34d399" />
                </linearGradient>
                <linearGradient id="fillGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#10b981" stop-opacity="0.3"/>
                    <stop offset="100%" stop-color="#0f172a" stop-opacity="0"/>
                </linearGradient>
            </defs>
            <path d="M0,300 L0,250 C150,220 200,280 350,180 C500,80 550,230 700,100 C850,-30 900,120 1000,50 L1000,300 Z" fill="url(#fillGradient)" class="opacity-50" />
            <path d="M0,250 C150,220 200,280 350,180 C500,80 550,230 700,100 C850,-30 900,120 1000,50" class="chart-line" />
        </svg>

        {{-- Floating Icons --}}
        <div class="absolute top-1/4 left-[10%] w-16 h-16 bg-emerald-500/10 rounded-2xl border border-emerald-500/20 backdrop-blur-md float-med flex items-center justify-center text-emerald-400" style="animation-delay: 0s;">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
        </div>
        <div class="absolute bottom-1/4 right-[15%] w-20 h-20 bg-blue-500/10 rounded-full border border-blue-500/20 backdrop-blur-md float-slow flex items-center justify-center text-blue-400" style="animation-delay: -2s;">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="absolute top-1/3 right-[20%] w-12 h-12 bg-indigo-500/10 rounded-xl border border-indigo-500/20 backdrop-blur-md float-fast flex items-center justify-center text-indigo-400" style="animation-delay: -5s;">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
        </div>
        <div class="absolute bottom-1/3 left-[20%] w-14 h-14 bg-purple-500/10 rounded-lg border border-purple-500/20 backdrop-blur-md float-slow flex items-center justify-center text-purple-400" style="animation-delay: -3s;">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
        </div>
        <div class="absolute top-1/2 right-[10%] w-10 h-10 bg-teal-500/10 rounded-full border border-teal-500/20 backdrop-blur-md float-fast flex items-center justify-center text-teal-400" style="animation-delay: -7s;">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
        </div>
    </div>
    
    <div class="w-full max-w-md relative z-10">
        <div class="glass-card rounded-3xl p-10 mt-8 mb-6">
            <div class="text-center mb-10">
                @if(\App\Models\Setting::get('app_logo'))
                    <div class="inline-flex items-center justify-center mb-6 float-med">
                        <img src="{{ Storage::url(\App\Models\Setting::get('app_logo')) }}" alt="Logo" class="h-20 w-auto object-contain drop-shadow-lg">
                    </div>
                @else
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white mb-6 shadow-lg shadow-emerald-500/30 float-med">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                @endif
                <h1 class="text-3xl font-bold text-white tracking-tight">{{ \App\Models\Setting::get('app_name', 'Finance Portal') }}</h1>
                <p class="text-slate-400 mt-2 text-sm font-medium">Access your budget insights securely.</p>
            </div>

            @if ($errors->any())
                <div class="mb-8 rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200 backdrop-blur-md">
                    <div class="flex gap-3 items-center">
                        <div class="p-1.5 bg-rose-500/20 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-rose-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span>{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}" class="space-y-6">
                @csrf
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-300 ml-1">Email <span class="text-emerald-400">*</span></label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition-colors group-focus-within:text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" 
                               class="w-full pl-11 pr-4 py-3.5 rounded-xl transition-all text-sm focus:ring-emerald-500 focus:border-emerald-500"
                               placeholder="Masukkan email" required>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-300 ml-1">Password <span class="text-emerald-400">*</span></label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition-colors group-focus-within:text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </span>
                        <input type="password" name="password" 
                               class="w-full pl-11 pr-4 py-3.5 rounded-xl transition-all text-sm focus:ring-emerald-500 focus:border-emerald-500"
                               placeholder="Masukkan kata sandi" required>
                    </div>
                </div>

                <div class="pt-6">
                    <button type="submit" 
                            class="w-full py-4 bg-emerald-600 text-white rounded-xl font-bold text-sm tracking-wide hover:bg-emerald-500 transition-all focus:ring-4 focus:ring-emerald-500/30 flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20 transform active:scale-[0.98]">
                        Sign In
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
        
        <p class="text-center text-slate-500 text-xs mt-6 font-medium">
            &copy; {{ date('Y') }} {{ \App\Models\Setting::get('app_name', 'Finance Monitoring') }}. Core Systems.
        </p>
    </div>

</body>
</html>
