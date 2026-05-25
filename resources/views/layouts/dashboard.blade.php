<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? \App\Models\Setting::get('app_name', 'Finance Monitoring') }}</title>
    @if(\App\Models\Setting::get('app_favicon'))
        <link rel="icon" href="{{ Storage::url(\App\Models\Setting::get('app_favicon')) }}" type="image/x-icon">
    @endif
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
                            {{ \App\Models\Setting::get('app_name', 'Finance Monitoring') }}</h1>
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
                @else
                    @if(auth()->user()?->isManager())
                        <a href="{{ route('manager.dashboard') }}"
                            class="{{ $menuBaseClass }} {{ request()->routeIs('manager.*') ? $menuActiveClass : $menuInactiveClass }}">Dashboard
                            Manager</a>
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

    <script>
        // Toggle to globally disable the "Detail Realisasi" popup without deleting code
        window.DISABLE_REALISASI_POPUP = true;
    </script>
    @stack('scripts')
</body>

</html>