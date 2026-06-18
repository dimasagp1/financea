<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? \App\Models\Setting::get('app_name', 'Finance Monitoring') }}</title>
    @if(\App\Models\Setting::get('app_favicon'))
        <link rel="icon" href="{{ Storage::url(\App\Models\Setting::get('app_favicon')) }}" type="image/x-icon">
    @endif
    <!-- Google Fonts for Premium Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-800">
    @php
        $isDepartemen = auth()->user()?->isDepartemen();
        $deptSection = request()->string('section')->toString() ?: 'overview';
        $menuBaseClass = 'block rounded-xl px-4 py-3 transition';
        $menuActiveClass = $isDepartemen
            ? 'bg-blue-50 text-blue-700 font-semibold ring-1 ring-blue-100'
            : 'bg-white/20 text-white font-semibold ring-1 ring-white/20';
        $menuInactiveClass = $isDepartemen
            ? 'text-slate-600 hover:bg-slate-100'
            : 'text-slate-200 hover:bg-white/10';
        $sidebarClass = $isDepartemen
            ? 'bg-slate-100 text-slate-700 border-r border-slate-200 shadow-lg'
            : 'bg-gradient-to-b from-slate-900 to-slate-800 text-slate-100 shadow-2xl';
        $mainClass = $isDepartemen
            ? 'bg-slate-50'
            : 'bg-gradient-to-br from-slate-50 via-white to-indigo-50/40';
    @endphp

    <div class="min-h-screen flex relative">
        <div id="sidebar-backdrop" class="fixed inset-0 z-30 bg-slate-900/50 hidden lg:hidden"></div>

        <aside id="app-sidebar"
            class="fixed lg:sticky lg:top-0 inset-y-0 left-0 z-40 h-screen w-72 lg:w-72 lg:max-w-72 min-w-0 p-6 lg:p-6 opacity-100 translate-x-0 {{ $sidebarClass }} transition-all duration-300 overflow-y-auto overflow-x-hidden">
            <div class="flex items-start justify-between gap-2 mb-8">
                <div class="flex items-center gap-3">
                    @if(\App\Models\Setting::get('app_logo'))
                        <img src="{{ Storage::url(\App\Models\Setting::get('app_logo')) }}" alt="Logo"
                            class="h-10 w-auto object-contain shrink-0">
                    @endif
                    <div>
                        <h1 class="text-xl font-bold {{ $isDepartemen ? 'text-slate-900' : 'text-white' }}">
                            {{ \App\Models\Setting::get('app_name', 'Finance Monitoring') }}
                        </h1>
                        <p class="text-sm {{ $isDepartemen ? 'text-slate-500' : 'text-slate-300' }}">Anggaran &
                            Pengeluaran
                        </p>
                    </div>
                </div>
                <button type="button" data-sidebar-close
                    class="lg:hidden rounded-lg {{ $isDepartemen ? 'bg-slate-200 text-slate-700 hover:bg-slate-300' : 'bg-white/10 text-white hover:bg-white/20' }} px-2 py-1.5 transition">✕</button>
            </div>

            @if($isDepartemen)
                <div class="mb-6 rounded-xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-sm font-semibold text-slate-800">{{ auth()->user()?->name }}</p>
                    <p class="text-xs text-slate-500">{{ auth()->user()?->department?->name ?? 'Kepala Departemen' }}</p>
                </div>
            @endif

            <nav class="space-y-2 text-sm">
                @if($isDepartemen)
                    <a href="{{ route('departemen.dashboard', ['section' => 'overview']) }}"
                        class="{{ $menuBaseClass }} {{ $deptSection === 'overview' ? $menuActiveClass : $menuInactiveClass }}">Ringkasan</a>
                    <a href="{{ route('monitoring.index') }}"
                        class="{{ $menuBaseClass }} {{ request()->routeIs('monitoring.*') ? $menuActiveClass : $menuInactiveClass }}">Realisasi
                        Budget</a>
                    <a href="{{ route('fat.forecasts.index') }}"
                        class="{{ $menuBaseClass }} {{ request()->routeIs('fat.forecasts.*') ? $menuActiveClass : $menuInactiveClass }}">Forecast</a>
                    <a href="{{ route('fat.staging.index') }}"
                        class="{{ $menuBaseClass }} {{ request()->routeIs('fat.staging.*') ? $menuActiveClass : $menuInactiveClass }}">📦
                        Staging Pengeluaran Pagu</a>
                @else
                    @if(auth()->user()?->isManager())
                        <a href="{{ route('manager.dashboard') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('manager.*') ? $menuActiveClass : $menuInactiveClass }}">Dashboard
                            Manager</a>
                        <a href="{{ route('fat.staging.index') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('fat.staging.*') ? $menuActiveClass : $menuInactiveClass }}">📦
                            Staging Pengeluaran Pagu</a>
                    @endif
                    @if(!(auth()->user()?->isFAT() || auth()->user()?->isSuperAdmin() || auth()->user()?->isManager()))
                        <a href="{{ route('dashboard.index') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('dashboard.*') || request()->routeIs('departemen.dashboard') ? $menuActiveClass : $menuInactiveClass }}">Dashboard</a>
                    @endif
                    @if(auth()->user()?->isFAT() || auth()->user()?->isSuperAdmin())
                        <a href="{{ route('fat.departments.index') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('fat.departments.*') ? $menuActiveClass : $menuInactiveClass }}">Standar
                            Budget</a>
                        <a href="{{ route('monitoring.index') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('monitoring.*') ? $menuActiveClass : $menuInactiveClass }}">Realisasi
                            Budget</a>
                        <a href="{{ route('fat.forecasts.index') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('fat.forecasts.*') ? $menuActiveClass : $menuInactiveClass }}">Forecast</a>
                        <a href="{{ route('fat.laporan.index') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('fat.laporan.*') ? $menuActiveClass : $menuInactiveClass }}">📋
                            Laporan</a>
                        <a href="{{ route('fat.activity-logs.index') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('fat.activity-logs.*') ? $menuActiveClass : $menuInactiveClass }}">🔍
                            Audit Trail</a>
                        <a href="{{ route('fat.odoo.croscheck') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('fat.odoo.croscheck') ? $menuActiveClass : $menuInactiveClass }}">🔄
                            Kroscek Odoo</a>
                        <a href="{{ route('fat.odoo.coa-mapping') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('fat.odoo.coa-mapping') ? $menuActiveClass : $menuInactiveClass }}">📂
                            Pemetaan COA Odoo</a>
                        <a href="{{ route('fat.staging.index') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('fat.staging.*') ? $menuActiveClass : $menuInactiveClass }}">📦
                            Staging Pengeluaran Pagu</a>
                    @endif
                    @if(auth()->user()?->isSuperAdmin())
                        <a href="{{ route('fat.users.index') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('fat.users.*') ? $menuActiveClass : $menuInactiveClass }}">Manajemen
                            User</a>
                        <a href="{{ route('fat.settings.index') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('fat.settings.*') ? $menuActiveClass : $menuInactiveClass }}">Pengaturan
                            Aplikasi</a>
                    @endif
                @endif


                <div
                    class="rounded-xl px-4 py-3 text-xs {{ $isDepartemen ? 'bg-white border border-slate-200 text-slate-600' : 'bg-white/5 text-slate-200' }}">
                    <p class="font-medium text-sm {{ $isDepartemen ? 'text-slate-800' : 'text-white' }}">
                        {{ auth()->user()?->name }}
                    </p>
                    <p>{{ auth()->user()?->email }}</p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        class="w-full rounded-xl px-4 py-2.5 {{ $isDepartemen ? 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' : 'bg-rose-500/90 hover:bg-rose-500 text-white' }} transition">Keluar</button>
                </form>
            </nav>
        </aside>

        <main class="flex-1 w-full min-w-0 p-8 {{ $mainClass }}">
            <div class="mb-6">
                <button type="button" data-sidebar-toggle aria-expanded="true"
                    class="inline-flex items-center gap-2 rounded-xl {{ $isDepartemen ? 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50' : 'bg-slate-900 text-white hover:bg-slate-800' }} px-4 py-2.5 transition">
                    <span data-sidebar-toggle-icon class="text-sm transition-transform duration-200">☰</span>
                    <span data-sidebar-toggle-label>Menu</span>
                </button>
            </div>

            @if (session('success'))
                <div id="flash-success"
                    class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 shadow-lg transition-all duration-500">
                    ✅ {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div id="flash-error"
                    class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-lg transition-all duration-500">
                    ❌ {{ session('error') }}
                </div>
            @endif

            @if (session('warning'))
                <div id="flash-warning"
                    class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 shadow-lg transition-all duration-500">
                    ⚠️ {{ session('warning') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800">
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

        <!-- Luxury Gold & Cashflow Wave Loader -->
    <div id="global-loading-overlay" class="active">
        <div class="luxury-loader-container">
            <!-- Subtle Gold Aura -->
            <div class="gold-aura"></div>
            
            <!-- Cashflow Liquid Graph -->
            <div class="cashflow-visual">
                <!-- Golden Sparkles -->
                <div class="gold-sparkles">
                    <span class="sparkle s1">&#10022;</span>
                    <span class="sparkle s2">&#10022;</span>
                    <span class="sparkle s3">&#10022;</span>
                </div>
                
                <!-- The Liquid Container (Vault Vessel) -->
                <div class="liquid-vessel">
                    <!-- Liquid Waves (Gold & Emerald) -->
                    <div class="luxury-wave gold-wave-1"></div>
                    <div class="luxury-wave gold-wave-2"></div>
                    
                    <!-- Floating Gold Coin Buoy -->
                    <div class="floating-luxury-coin">
                        <div class="coin-inner">Rp</div>
                    </div>
                </div>
            </div>
            
            <!-- Elegant Brand/Title -->
            <div class="luxury-meta">
                <div class="brand-badge">FINANCE SYSTEM</div>
                <h3 class="luxury-title loader-title">MEMUAT DATA KEUANGAN</h3>
                
                <!-- Minimalist Luxury Progress Line -->
                <div class="luxury-progress-wrapper">
                    <div class="luxury-progress-bar loader-bar" style="width: 25%;"></div>
                </div>
                
                <!-- Elegant Description -->
                <p class="luxury-desc loader-desc">Mengoptimalkan likuiditas kas...</p>
            </div>
        </div>
    </div>

    <style>
        #global-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #090a0f; /* Matte black */
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }

        #global-loading-overlay.active {
            opacity: 1 !important;
            visibility: visible !important;
            display: flex !important;
        }

        .luxury-loader-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 90%;
            max-width: 400px;
            padding: 3.5rem 2.5rem;
            border-radius: 32px;
            background: rgba(18, 20, 29, 0.6);
            border: 1px solid rgba(234, 179, 8, 0.12);
            box-shadow: 
                0 30px 70px rgba(0, 0, 0, 0.8),
                0 0 40px rgba(234, 179, 8, 0.03);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            position: relative;
            overflow: hidden;
        }

        /* Gold aura glow */
        .gold-aura {
            position: absolute;
            top: -20%;
            left: -20%;
            width: 140%;
            height: 140%;
            background: radial-gradient(circle at center, rgba(234, 179, 8, 0.04), transparent 55%);
            pointer-events: none;
            animation: aura-pulse 6s ease-in-out infinite;
        }

        @keyframes aura-pulse {
            0%, 100% { opacity: 0.5; transform: scale(0.95); }
            50% { opacity: 1; transform: scale(1.05); }
        }

        /* Cashflow Visual */
        .cashflow-visual {
            position: relative;
            width: 140px;
            height: 140px;
            margin-bottom: 2.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Liquid Vessel (Glass Vault) */
        .liquid-vessel {
            position: relative;
            width: 110px;
            height: 110px;
            border-radius: 28px;
            background: linear-gradient(135deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
            border: 1px solid rgba(234, 179, 8, 0.25);
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.5),
                inset 0 0 15px rgba(234, 179, 8, 0.05);
            overflow: hidden;
            z-index: 2;
        }

        /* Luxury Waves */
        .luxury-wave {
            position: absolute;
            left: -50%;
            width: 200%;
            height: 200%;
            border-radius: 42%;
        }

        /* Rich Liquid Gold Wave */
        .gold-wave-1 {
            top: 52%;
            background: linear-gradient(180deg, rgba(234, 179, 8, 0.75) 0%, rgba(146, 64, 14, 0.95) 100%);
            animation: rotate-wave 8s infinite linear;
            z-index: 3;
        }

        /* Deep Emerald Cash Flow Wave */
        .gold-wave-2 {
            top: 48%;
            background: linear-gradient(180deg, rgba(16, 185, 129, 0.4) 0%, rgba(4, 120, 87, 0.7) 100%);
            animation: rotate-wave 12s infinite linear reverse;
            z-index: 2;
        }

        @keyframes rotate-wave {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Floating Luxury Coin */
        .floating-luxury-coin {
            position: absolute;
            top: 30%;
            left: 50%;
            transform: translateX(-50%);
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fef08a, #eab308);
            border: 2px solid #ca8a04;
            box-shadow: 
                0 8px 20px rgba(0, 0, 0, 0.4),
                0 0 15px rgba(234, 179, 8, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 4;
            animation: coin-bob 3s ease-in-out infinite;
        }

        .coin-inner {
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            font-weight: 800;
            color: #78350f;
            text-shadow: 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        @keyframes coin-bob {
            0%, 100% { transform: translate(-50%, 0) rotate(0deg); }
            50% { transform: translate(-50%, -12px) rotate(15deg); }
        }

        /* Sparkles */
        .gold-sparkles {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 1;
        }

        .sparkle {
            position: absolute;
            color: #fef08a;
            font-size: 0.9rem;
            opacity: 0;
            text-shadow: 0 0 5px #eab308;
        }

        .s1 {
            top: 10%;
            left: 10%;
            animation: sparkle-pulse 3s infinite 0.5s;
        }

        .s2 {
            top: 20%;
            right: 15%;
            animation: sparkle-pulse 2.5s infinite 1.2s;
        }

        .s3 {
            bottom: 15%;
            left: 20%;
            animation: sparkle-pulse 3.5s infinite;
        }

        @keyframes sparkle-pulse {
            0%, 100% { opacity: 0; transform: scale(0.6) rotate(0deg); }
            50% { opacity: 0.8; transform: scale(1.1) rotate(45deg); }
        }

        /* Typography & Progress */
        .luxury-meta {
            width: 100%;
            text-align: center;
        }

        .brand-badge {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 700;
            color: #eab308;
            letter-spacing: 3px;
            padding: 4px 12px;
            border-radius: 99px;
            border: 1px solid rgba(234, 179, 8, 0.2);
            background: rgba(234, 179, 8, 0.04);
            margin-bottom: 0.85rem;
            text-transform: uppercase;
        }

        .luxury-title {
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            text-transform: uppercase;
        }

        /* Elegant Progress Line */
        .luxury-progress-wrapper {
            width: 100%;
            height: 2px;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 1.25rem;
            position: relative;
            overflow: hidden;
        }

        .luxury-progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #eab308, #fbbf24, #10b981);
            transition: width 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 0 8px rgba(234, 179, 8, 0.6);
        }

        .luxury-desc {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.825rem;
            color: #94a3b8;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }
    </style>

    <script>
        // Toggle to globally disable the "Detail Realisasi" popup without deleting code
        window.DISABLE_REALISASI_POPUP = true;

        let descInterval = null;
        const financialDescs = [
            "Menyelaraskan portofolio anggaran...",
            "Mengoptimalkan likuiditas kas...",
            "Melakukan sinkronisasi data transaksi...",
            "Menganalisis performa anggaran departemen...",
            "Menghitung proyeksi forecast finansial...",
            "Mengamankan otorisasi pagu anggaran...",
            "Menyusun pembukuan arus kas...",
            "Menghubungkan integrasi data Odoo..."
        ];

        // Global loading animation functions
        window.showGlobalLoading = function(title, desc) {
            const overlay = document.getElementById('global-loading-overlay');
            if (overlay) {
                const titleEl = overlay.querySelector('.loader-title');
                const descEl = overlay.querySelector('.loader-desc');
                const barEl = overlay.querySelector('.loader-bar');
                
                if (titleEl) titleEl.textContent = title || 'MEMUAT DATA';
                if (descEl) descEl.textContent = desc || financialDescs[0];
                
                if (barEl) {
                    barEl.style.width = '15%';
                    setTimeout(() => {
                        barEl.style.width = '85%';
                    }, 100);
                }
                
                overlay.classList.add('active');
                
                // Rotate description texts
                if (descInterval) clearInterval(descInterval);
                let descIndex = 1;
                descInterval = setInterval(() => {
                    if (descEl && !desc) {
                        descEl.style.opacity = '0';
                        setTimeout(() => {
                            descEl.textContent = financialDescs[descIndex];
                            descEl.style.opacity = '1';
                            descIndex = (descIndex + 1) % financialDescs.length;
                        }, 300);
                    }
                }, 2500);
            }
        };

        window.hideGlobalLoading = function() {
            const overlay = document.getElementById('global-loading-overlay');
            if (overlay) {
                const barEl = overlay.querySelector('.loader-bar');
                if (barEl) {
                    barEl.style.width = '100%';
                }
                if (descInterval) {
                    clearInterval(descInterval);
                    descInterval = null;
                }
                setTimeout(() => {
                    overlay.classList.remove('active');
                }, 400);
            }
        };

        // Intercept link clicks for navigation loading only
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a');
                if (!link) return;

                const href = link.getAttribute('href');
                const target = link.getAttribute('target');
                const download = link.getAttribute('download');

                // Skip non-navigation links
                if (!href || 
                    href.startsWith('#') || 
                    href.startsWith('javascript:') || 
                    target === '_blank' || 
                    download !== null ||
                    e.metaKey || 
                    e.ctrlKey || 
                    e.shiftKey || 
                    e.altKey) {
                    return;
                }

                // Check same domain
                if (link.hostname !== window.location.hostname) {
                    return;
                }

                // Get page title for loading text
                let pageText = link.textContent.trim().replace(/[\r\n\t]+/g, ' ');
                // Remove icons/emojis
                pageText = pageText.replace(/[\u2700-\u27BF]|[\uE000-\uF8FF]|\uD83C[\uDC00-\uDFFF]|\uD83D[\uDC00-\uDFFF]|[\u2011-\u26FF]|\uD83E[\uDD10-\uDDFF]/g, '').trim();
                
                let title = 'MEMUAT DATA';
                if (pageText) {
                    title = `MEMUAT ${pageText.toUpperCase()}`;
                }
                
                window.showGlobalLoading(title, 'Mengunduh laporan finansial terenkripsi...');
            });
        });

        // Hide loading screen once page fully loads
        if (document.readyState === 'complete') {
            const barEl = document.querySelector('.loader-bar');
            if (barEl) barEl.style.width = '100%';
            setTimeout(() => {
                window.hideGlobalLoading();
            }, 300);
        } else {
            window.addEventListener('load', () => {
                const barEl = document.querySelector('.loader-bar');
                if (barEl) barEl.style.width = '100%';
                setTimeout(() => {
                    window.hideGlobalLoading();
                }, 400);
            });
        }

        // Handle browser back-forward cache loaded pages
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.hideGlobalLoading();
            }
        });
    </script>
    @stack('scripts')
</body>

</html>