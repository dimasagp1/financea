@extends('layouts.dashboard', ['title' => 'Dashboard Finance'])

@section('content')
<div class="flex items-start justify-between gap-4 mb-8">
    <div>
        <h2 class="text-2xl font-bold text-slate-900">Dashboard Monitoring Finance</h2>
        <p class="text-slate-500 mt-1">
            @if($activeFiscalYear)
                Fiscal Year {{ $activeFiscalYear->year }}
            @else
                Fiscal year aktif belum tersedia
            @endif
        </p>
    </div>

    @if($activeFiscalYear)
        <form action="{{ route('dashboard.index') }}" method="GET"
            class="flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200 shadow-sm">
            <select name="month"
                class="text-sm border-none focus:ring-0 text-slate-700 font-medium bg-transparent cursor-pointer"
                onchange="this.form.submit()">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ (isset($selectedMonth) ? $selectedMonth : now()->month) == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
            <span class="text-slate-300">|</span>
            <select name="year"
                class="text-sm border-none focus:ring-0 text-slate-700 font-medium bg-transparent cursor-pointer"
                onchange="this.form.submit()">
                {{-- Assuming we only care about Active Fiscal Year for now, but allow year selection in case history needed
                --}}
                <option value="{{ $activeFiscalYear->year }}" selected>{{ $activeFiscalYear->year }}</option>
            </select>
        </form>
    @endif
</div>

@php
    $statusColor = 'text-slate-900';
    $statusBg = 'bg-white';
    $statusBorder = 'border-slate-100';
    
    if ($summary['status'] === 'danger') {
        $statusColor = 'text-rose-700';
        $statusBorder = 'border-rose-100';
        $statusBg = 'bg-rose-50/30';
    } elseif ($summary['status'] === 'success') {
        $statusColor = 'text-emerald-600';
        $statusBorder = 'border-emerald-100';
        $statusBg = 'bg-emerald-50/30';
    } elseif ($summary['status'] === 'warning') {
        $statusColor = 'text-amber-600';
        $statusBorder = 'border-amber-100';
        $statusBg = 'bg-amber-50/30';
    }
 @endphp

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="rounded-2xl bg-white p-5 shadow-lg shadow-slate-200/70 border border-slate-100">
        <p class="text-sm text-slate-500">Omset Bulanan ({{ $currentMonthName }})</p>
        <p class="text-2xl font-bold mt-2">Rp {{ number_format($summary['allocated'], 0, ',', '.') }}</p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-lg shadow-slate-200/70 border border-slate-100">
        <p class="text-sm text-slate-500">Forecast Omset ({{ $currentMonthName }})</p>
        <p class="text-2xl font-bold mt-2">Rp {{ number_format($summary['global_budget'], 0, ',', '.') }}</p>
    </div>
    <div class="rounded-2xl {{ $statusBg }} p-5 shadow-lg shadow-slate-200/70 border {{ $statusBorder }}">
        <p class="text-sm text-slate-500">Total Pengeluaran (Actual)</p>
        <p class="text-2xl font-bold mt-2 {{ $statusColor }}">Rp {{ number_format($summary['used'], 0, ',', '.') }}</p>
        <div class="mt-1 text-xs {{ $statusColor }} opacity-80 font-medium">
            {{ round($summary['utilization'], 1) }}% dari Omset Bulanan
        </div>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-lg shadow-slate-200/70 border border-slate-100">
        <p class="text-sm text-slate-500">Sisa Budget (Available)</p>
        <p class="text-2xl font-bold mt-2 text-slate-700">Rp {{ number_format($summary['remaining'], 0, ',', '.') }}</p>
    </div>
</div>



@if($activeFiscalYear && ($userRole === 'fat' || $userRole === 'superadmin'))
    <div class="mb-8 p-1">
        <h3 class="text-xl font-bold text-slate-900 mb-6 flex items-center gap-2">
            <span class="p-2 bg-slate-900 text-white rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
                </svg>
            </span>
            Resume Analisis Anggaran (Global)
        </h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- 1. Alokasi per Departemen (Line Chart) --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <h4 class="text-sm font-bold text-slate-700 mb-4 uppercase tracking-wider">Jatah Budget per Dept</h4>
                <div class="h-64 relative">
                    <canvas id="deptAllocationChart"></canvas>
                </div>
            </div>

            {{-- 2. Analisis Kategori Top Dept (Line Chart) --}}
            <div class="rounded-2xl bg-white p-6 shadow-sm border border-slate-200">
                <h4 class="text-sm font-bold text-slate-700 mb-4 uppercase tracking-wider">
                    Kategori Top Dept ({{ $topDept['name'] ?? '-' }})
                </h4>
                <div class="h-64 relative">
                    <canvas id="deptContributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Line chart: % Standar vs % Actual per Kategori --}}
<div class="rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/70 border border-slate-100 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h3 class="text-lg font-semibold text-slate-900">% Standar vs % Actual per Kategori</h3>
        <div class="flex items-center gap-3">
            <select id="catDeptFilter"
                class="text-sm rounded-lg border border-slate-200 px-3 py-1.5 text-slate-700 bg-white focus:ring-2 focus:ring-indigo-300 focus:outline-none shadow-sm">
                @foreach($departments as $dept)
                    <option value="{{ $dept['name'] }}">{{ $dept['name'] }}</option>
                @endforeach
            </select>
            <span class="text-xs font-medium px-2 py-1 bg-slate-100 rounded text-slate-500">{{ $currentMonthName }}</span>
        </div>
    </div>
    <div class="h-72">
        <canvas id="categoryPctChart"></canvas>
    </div>
</div>

{{-- Table: Kategori breakdown (collapsible) --}}
<div class="rounded-2xl bg-white shadow-lg shadow-slate-200/70 border border-slate-100 mb-8 overflow-hidden">
    <button onclick="const t=document.getElementById('cat-table-body');t.classList.toggle('hidden');this.querySelector('.toggle-icon').textContent=t.classList.contains('hidden')?'▼':'▲'"
        class="w-full flex items-center justify-between px-5 py-4 text-sm font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 transition border-b border-slate-100">
        <span>📋 Detail % Standar vs % Actual per Kategori</span>
        <span class="toggle-icon text-indigo-600 text-xs font-bold">▼</span>
    </button>
    <div id="cat-table-body" class="hidden overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500 tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Kategori</th>
                    <th class="px-4 py-3 text-left">Departemen</th>
                    <th class="px-4 py-3 text-right">% Standar (Global)</th>
                    <th class="px-4 py-3 text-right">% Actual (Global)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($categoryChartData as $row)
                    @php
                        $diff = $row['actual_pct'] - $row['standard_pct'];
                        $diffColor = $diff > 0 ? 'text-rose-600' : ($diff < 0 ? 'text-emerald-600' : 'text-slate-500');
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $row['label'] }}</td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $row['department'] }}</td>
                        <td class="px-4 py-3 text-right font-mono text-indigo-600 font-semibold">
                            {{ number_format($row['standard_pct'], 2, ',', '.') }}%
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="font-mono font-bold {{ $row['actual_pct'] > $row['standard_pct'] ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ number_format($row['actual_pct'], 2, ',', '.') }}%
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>{{-- /cat-table-body --}}
</div>


<div class="rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/70 border border-slate-100 overflow-hidden">
    <h3 class="text-lg font-semibold mb-4">Detail Alokasi vs Pengeluaran ({{ $currentMonthName }})</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b border-slate-200">
                    <th class="py-3 pr-4">Departemen</th>
                    <th class="py-3 pr-4">Alokasi ({{ $currentMonthName }})</th>
                    <th class="py-3 pr-4">Bulan Ini (Std vs Real)</th>
                    <th class="py-3 pr-4">Realisasi ({{ $currentMonthName }})</th>
                    <th class="py-3 pr-4">% Terpakai</th>
                    <th class="py-3 pr-4">Progress</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $department)
                    @php
                        $barClass = match ($department['status']) {
                            'danger' => 'from-rose-600 to-rose-800',
                            'warning' => 'from-amber-400 to-amber-600',
                            default => 'from-emerald-500 to-emerald-700',
                        };
                        $utilTextClass = $department['utilization'] > 100
                            ? 'text-rose-700'
                            : ($department['utilization'] <= 20
                                ? 'text-amber-600'
                                : ($department['utilization'] <= 80 ? 'text-emerald-600' : 'text-amber-600'));
                        $currStd = $department['current_month']['standard'];
                        $currAct = $department['current_month']['actual'];
                        $currDiff = $department['current_month']['diff'];
                    @endphp
                    <tr class="border-b border-slate-100 cursor-pointer hover:bg-slate-50 transition"
                        onclick="document.getElementById('row-detail-{{ $department['id'] }}').classList.toggle('hidden')">
                        <td class="py-4 pr-4 font-medium">
                            {{ $department['name'] }}
                            <span class="text-xs text-slate-400 block">Klik untuk detail ▾</span>
                        </td>
                        <td class="py-4 pr-4">Rp {{ number_format($department['allocated'], 0, ',', '.') }}</td>
                        <td class="py-4 pr-4">
                            <div class="text-xs text-slate-500">Std: Rp {{ number_format($currStd, 0, ',', '.') }}</div>
                            <div class="font-semibold {{ $currDiff < 0 ? 'text-rose-600' : 'text-slate-700' }}">
                                Real: Rp {{ number_format($currAct, 0, ',', '.') }}
                            </div>
                        </td>
                        <td class="py-4 pr-4">Rp {{ number_format($department['used'], 0, ',', '.') }}</td>
                        <td class="py-4 pr-4 text-center">
                            <span class="font-bold {{ $utilTextClass }}">
                                {{ number_format($department['percent_of_global'], 2, ',', '.') }}%
                            </span>
                        </td>
                        <td class="py-4 pr-4 w-48">
                            <div class="h-2.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-2.5 rounded-full bg-gradient-to-r {{ $barClass }}"
                                    style="width: {{ min($department['utilization'], 100) }}%"></div>
                            </div>
                        </td>
                    </tr>

                    {{-- DROPDOWN DETAIL --}}
                    <tr id="row-detail-{{ $department['id'] }}" class="{{ (int) ($expandDepartmentId ?? 0) === (int) $department['id'] ? '' : 'hidden' }} bg-slate-50 border-b border-slate-200">
                        <td colspan="6" class="p-4">
                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

                                {{-- 1. LEFT COLUMN: SUMMARY & BAR CHART --}}
                                <div class="space-y-6">
                                    {{-- RINGKASAN PERFORMA --}}
                                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                        @php
                                            $qMap = [1 => ['Q1', 'Jan - Mar'], 2 => ['Q2', 'Apr - Jun'], 3 => ['Q3', 'Jul - Sep'], 4 => ['Q4', 'Oct - Dec']];
                                            $selMonth = (isset($selectedMonth) ? $selectedMonth : now()->month);
                                            $curQ = (int) ceil($selMonth / 3);
                                            [$qLabel, $qRange] = $qMap[$curQ] ?? ['Q1', 'Jan - Mar'];

                                            $badgeClass = match ($department['status']) {
                                                'danger' => 'bg-rose-100 text-rose-800 font-bold',
                                                'warning' => 'bg-amber-100 text-amber-700',
                                                default => 'bg-emerald-100 text-emerald-700',
                                            };
                                            $badgeText = match ($department['status']) {
                                                'danger' => 'Overbudget / Kritis',
                                                'warning' => ($department['utilization'] <= 20 ? 'Penyerapan Rendah' : 'Mendekati Limit'),
                                                'success' => 'Ideal / Aman',
                                                default => 'Aman',
                                            };
                                            $progressBarClass = match ($department['status']) {
                                                'danger' => 'bg-rose-600',
                                                'warning' => 'bg-amber-500',
                                                default => 'bg-emerald-500',
                                            };
                                        @endphp

                                        <div class="flex items-start justify-between gap-3 mb-6">
                                            <div>
                                                <h3 class="text-2xl font-bold text-slate-900">{{ $qLabel }} Ringkasan Anggaran</h3>
                                            </div>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                                {{ $badgeText }}
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-6">
                                            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-3">
                                                <p class="text-[8px] uppercase text-slate-400 font-bold tracking-widest">Total Alokasi</p>
                                                <p class="text-lg font-bold text-slate-900 mt-0.5 whitespace-nowrap overflow-hidden text-ellipsis">Rp {{ number_format($department['allocated'], 0, ',', '.') }}</p>
                                            </div>
                                            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-3">
                                                <p class="text-[8px] uppercase text-slate-400 font-bold tracking-widest">Total Pengeluaran</p>
                                                <p class="text-lg font-bold text-slate-900 mt-0.5 whitespace-nowrap overflow-hidden text-ellipsis">Rp {{ number_format($department['used'], 0, ',', '.') }}</p>
                                            </div>
                                            <div class="rounded-2xl bg-blue-50 border border-blue-100 p-3">
                                                <p class="text-[8px] uppercase text-blue-400 font-bold tracking-widest">Sisa Anggaran</p>
                                                <p class="text-lg font-bold text-blue-600 mt-0.5 whitespace-nowrap overflow-hidden text-ellipsis">Rp {{ number_format($department['remaining'], 0, ',', '.') }}</p>
                                            </div>
                                        </div>

                                        <div class="px-1">
                                            <div class="flex items-center justify-between text-sm mb-3">
                                                <p class="font-medium text-slate-700">Porsi Terpakai (Omset)</p>
                                                <p class="font-bold text-slate-900">{{ number_format($department['percent_of_global'], 2, ',', '.') }}%</p>
                                            </div>
                                            <div class="h-3 bg-slate-100 rounded-full overflow-hidden shadow-inner">
                                                <div class="h-full {{ $progressBarClass }} rounded-full transition-all duration-1000 shadow-sm" 
                                                     style="width: {{ min($department['utilization'], 100) }}%">
                                                </div>
                                            </div>
                                            <div class="flex justify-between text-[11px] text-slate-400 mt-3 font-medium">
                                                <span>Start: {{ $qRange }}</span>
                                                <span>FY {{ $activeFiscalYear?->year }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- BAR CHART: CATEGORY USAGE --}}
                                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                        <h3 class="text-xl font-semibold text-slate-900 mb-6">Penggunaan Budget per Kategori</h3>
                                        <div class="h-72 w-full">
                                            <canvas id="bar-dept-{{ $department['id'] }}"></canvas>
                                        </div>
                                    </div>
                                </div>

                                {{-- 2. RIGHT COLUMN: DONUT & ALERTS --}}
                                <div class="space-y-6">
                                    {{-- DONUT CHART --}}
                                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                        <h3 class="text-xl font-semibold text-slate-900 mb-6 text-center">Rincian Pengeluaran</h3>
                                        <div class="mx-auto max-w-[200px] mb-6">
                                            <canvas id="donut-dept-{{ $department['id'] }}" height="200"></canvas>
                                        </div>
                                        <div class="text-center mb-4">
                                            <p class="text-xs text-slate-400">Pengeluaran Tertinggi</p>
                                            <p class="font-semibold text-slate-800">{{ $department['top_category'] }}</p>
                                        </div>
                                        <div class="space-y-2 mt-4">
                                            @foreach($department['categories'] as $cat)
                                                <div class="flex items-center justify-between text-xs">
                                                    <div class="flex items-center gap-2 text-slate-600">
                                                        <span class="inline-flex h-2 w-2 rounded-full bg-indigo-500"></span>
                                                        <span>{{ $cat['name'] }}</span>
                                                    </div>
                                                    <span class="font-bold text-slate-800">{{ $department['used'] > 0 ? number_format(($cat['used'] / $department['used']) * 100, 1, ',', '.') : '0,0' }}%</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- ALERTS --}}
                                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                                        <h3 class="text-xl font-semibold text-slate-900 mb-4">Peringatan Anggaran</h3>
                                        <div class="space-y-3">
                                            @forelse($department['item_alerts'] as $alert)
                                                <div class="rounded-xl border p-4 {{ $alert['status'] === 'danger' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50' }}">
                                                    <p class="text-sm font-semibold {{ $alert['status'] === 'danger' ? 'text-rose-700' : 'text-amber-700' }}">
                                                        {{ $alert['item'] }} · {{ number_format($alert['share_percent'], 1) }}%
                                                    </p>
                                                    <p class="text-[10px] {{ $alert['status'] === 'danger' ? 'text-rose-600' : 'text-amber-600' }} mt-1 italic">
                                                        {{ $alert['category'] }} dengan pengeluaran Rp {{ number_format($alert['used'], 0, ',', '.') }}.
                                                    </p>
                                                </div>
                                            @empty
                                                <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4">
                                                    <p class="text-sm font-semibold text-emerald-700">Aman</p>
                                                    <p class="text-xs text-emerald-600 mt-1">Belum ada item dengan pengeluaran tinggi.</p>
                                                </div>
                                            @endforelse
                                        </div>

                                        {{-- Management Tools for FAT/Superadmin --}}
                                        @if($userRole === 'fat' || $userRole === 'superadmin')
                                            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col gap-4">
                                                <form method="POST" action="{{ route('fat.departments.update-ratio', $department['id']) }}" class="flex flex-col gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Update Rasio Dept</label>
                                                    <div class="flex gap-2">
                                                        <input type="number" name="budget_ratio_percent" step="0.01" min="0" max="100" value="{{ $department['ratio'] }}" class="rounded-xl border-slate-200 text-xs flex-1" placeholder="Ratio %">
                                                        <button class="rounded-xl bg-slate-900 text-white px-4 py-2 text-xs font-bold">Update</button>
                                                    </div>
                                                </form>

                                                <form method="POST" action="{{ route('fat.departments.override-monthly-budget', $department['id']) }}" class="flex flex-col gap-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Override Nominal Bulanan</label>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <input type="month" name="month" class="rounded-xl border-slate-200 text-xs" required>
                                                        <input type="number" name="amount" placeholder="Nominal Rp" class="rounded-xl border-slate-200 text-xs" required>
                                                    </div>
                                                    <button class="rounded-xl bg-indigo-600 text-white px-4 py-2 text-xs font-bold w-full">Set Override</button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-5 text-center text-slate-500">Data departemen belum tersedia.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($userRole === 'departemen' && $departments->count() > 0)
@php($department = $departments->first())
<div class="rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/70 border border-slate-100 overflow-hidden mt-8">
    <h3 class="text-lg font-semibold mb-4">Detail Penggunaan per Kategori & Item - {{ $department['name'] }}</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-slate-500 border-b border-slate-200">
                    <th class="py-3 pr-4">Kategori</th>
                    <th class="py-3 pr-4">Deskripsi</th>
                    <th class="py-3 pr-4">Ref/Tgl</th>
                    <th class="py-3 pr-4">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($department['categories'] as $category)
                    @forelse($category['expenses'] as $expense)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4">{{ $category['name'] }}</td>
                            <td class="py-3 pr-4">{{ $expense['name'] }}</td>
                            <td class="py-3 pr-4">
                                {{ $expense['reference'] ? $expense['reference'] : '-' }}
                                <span
                                    class="text-xs text-slate-400 block">{{ \Carbon\Carbon::parse($expense['date'])->format('d/m/Y') }}</span>
                            </td>
                            <td class="py-3 pr-4 font-medium">Rp {{ number_format($expense['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4">{{ $category['name'] }}</td>
                            <td class="py-3 pr-4 text-slate-500" colspan="3">Belum ada pengeluaran pada kategori ini.</td>
                        </tr>
                    @endforelse
                @empty
                    <tr>
                        <td colspan="4" class="py-5 text-center text-slate-500">Belum ada data kategori dan item pada
                            departemen ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($activeFiscalYear && ($userRole === 'fat' || $userRole === 'superadmin'))
    <div class="mt-12 mb-8 bg-slate-900 rounded-3xl p-8 text-white shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h3 class="text-2xl font-bold mb-2">Pusat Kontrol & Sinkronisasi</h3>
                <p class="text-slate-400 text-sm">Kelola budget global dan sinkronisasi data Odoo di satu tempat.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="px-4 py-2 bg-slate-800 rounded-xl border border-slate-700">
                    <span class="text-xs text-slate-500 block uppercase font-bold tracking-widest">Target Aktif</span>
                    <span class="text-lg font-bold">Rp {{ number_format($summary['global_budget'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            {{-- Quick Command 1: Budget Management --}}
            <div class="bg-slate-800/50 rounded-2xl p-6 border border-slate-700 hover:bg-slate-800 transition group">
                <div class="w-12 h-12 bg-blue-500/20 text-blue-400 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h4 class="text-lg font-bold mb-4">Internal Budgeting</h4>
                <p class="text-sm text-slate-400 mb-6">Sesuaikan target pengeluaran global untuk bulan ini.</p>
                <a href="{{ route('fat.global-budgets.index') }}"
                    class="block w-full text-center rounded-xl bg-white text-slate-900 py-3 font-bold hover:bg-slate-200 transition">
                    Kelola Budget Global
                </a>
            </div>

            {{-- Quick Command 2: Odoo Sync --}}
            <form method="POST" action="{{ route('fat.odoo.sync-expenses') }}" class="bg-slate-800/50 rounded-2xl p-6 border border-slate-700 hover:bg-slate-800 transition group">
                @csrf
                <div class="w-12 h-12 bg-indigo-500/20 text-indigo-400 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <h4 class="text-lg font-bold mb-4">Odoo Full Sync</h4>
                <div class="space-y-4">
                    <select name="department_id" class="w-full rounded-xl bg-slate-900 border-slate-700 text-slate-300 focus:ring-blue-500 text-sm">
                        <option value="">Semua Departemen</option>
                        @foreach($departments as $department)
                            <option value="{{ $department['id'] }}">{{ $department['name'] }}</option>
                        @endforeach
                    </select>
                    <button data-loading-text="Mencadangkan..."
                        class="w-full rounded-xl bg-indigo-600 text-white py-3 font-bold hover:bg-indigo-500 transition shadow-lg shadow-indigo-900/40">
                        Sinkronisasi Real-time
                    </button>
                </div>
            </form>

            {{-- Quick Command 3: Date Sync --}}
            <form method="POST" action="{{ route('fat.odoo.sync-expenses') }}" class="bg-slate-800/50 rounded-2xl p-6 border border-slate-700 hover:bg-slate-800 transition group">
                @csrf
                <div class="w-12 h-12 bg-emerald-500/20 text-emerald-400 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h4 class="text-lg font-bold mb-4">Custom Date Sync</h4>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="date_from" class="rounded-xl bg-slate-900 border-slate-700 text-slate-300 text-xs py-2">
                        <input type="date" name="date_to" class="rounded-xl bg-slate-900 border-slate-700 text-slate-300 text-xs py-2">
                    </div>
                    <button data-loading-text="Memproses..."
                        class="w-full rounded-xl bg-emerald-600 text-white py-3 font-bold hover:bg-emerald-500 transition shadow-lg shadow-emerald-900/40">
                        Sync Periode Tertentu
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
@endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Auto-refresh session setiap 15 menit untuk mencegah Page Expired (419)
        setInterval(function() {
            fetch(window.location.href, { method: 'HEAD' })
                .catch(e => console.log('Keep-alive failed', e));
        }, 1000 * 60 * 15);

        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#64748b';

        const fatPalette = [
            'rgba(59, 130, 246, 0.8)',   // blue
            'rgba(16, 185, 129, 0.8)',   // emerald
            'rgba(245, 158, 11, 0.8)',   // amber
            'rgba(99, 102, 241, 0.8)',   // indigo
            'rgba(244, 63, 94, 0.8)',    // rose
            'rgba(139, 92, 246, 0.8)',   // violet
            'rgba(20, 184, 166, 0.8)',   // teal
            'rgba(236, 72, 153, 0.8)'    // pink
        ];

        const currencyFormatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        });

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: { size: 10 }
                    }
                },
                tooltip: {
                    padding: 12,
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleFont: { size: 13 },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.parsed.y !== undefined) label += currencyFormatter.format(context.parsed.y);
                            else if (context.parsed !== undefined) label += currencyFormatter.format(context.parsed);
                            return label;
                        }
                    }
                }
            }
        };

        // Helper to shorten labels for X-axis
        const shortenLabel = (label) => {
            if (!label) return '';
            const map = {
                'Finance & Accounting': 'FA',
                'Marketing': 'MKT',
                'Operations': 'OPS',
                'Operational Finance': 'OpFin',
                'Compliance & Audit': 'Audit',
                'General & Administrative': 'G&A',
                'Human Resources': 'HR'
            };
            if (map[label]) return map[label];
            // If not in map, truncate if too long
            return label.length > 10 ? label.substring(0, 8) + '..' : label;
        };

        // 1. Alokasi Budget per Dept (Line Chart)
        const allocCtx = document.getElementById('deptAllocationChart');
        if (allocCtx) {
            const data = @json($deptAllocationData ?? []);
            new Chart(allocCtx, {
                type: 'line',
                data: {
                    labels: data.map(d => shortenLabel(d.name)),
                    datasets: [
                        {
                            label: 'Jatah (Budget)',
                            data: data.map(d => d.allocated),
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: '#6366f1'
                        },
                        {
                            label: 'Realisasi',
                            data: data.map(d => d.used),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: '#10b981',
                            borderDash: [4, 4]
                        }
                    ]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        x: {
                            ticks: { font: { size: 9 } }
                        },
                        y: { 
                            beginAtZero: true,
                            ticks: {
                                callback: value => {
                                    if (value >= 1000000) return 'Rp ' + (value / 1000000) + 'JT';
                                    return 'Rp ' + (value / 1000) + 'K';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Kategori Cost per Departemen Penggunaan Tertinggi (Line Chart)
        const contribCtx = document.getElementById('deptContributionChart');
        if (contribCtx) {
            const data = @json($topDeptCategoryData ?? []);
            new Chart(contribCtx, {
                type: 'line',
                data: {
                    labels: data.map(d => shortenLabel(d.name)),
                    datasets: [
                        {
                            label: 'Jatah',
                            data: data.map(d => d.allocated),
                            borderColor: 'rgba(148, 163, 184, 0.5)',
                            borderDash: [5, 5],
                            borderWidth: 2,
                            pointRadius: 3,
                            fill: false
                        },
                        {
                            label: 'Realisasi',
                            data: data.map(d => d.used),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: '#10b981'
                        }
                    ]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        x: {
                            ticks: { font: { size: 9 } }
                        },
                        y: { 
                            beginAtZero: true,
                            ticks: {
                                callback: value => {
                                    if (value >= 1000000) return 'Rp ' + (value / 1000000) + 'JT';
                                    return 'Rp ' + (value / 1000) + 'K';
                                }
                            }
                        }
                    }
                }
            });
        }

        // 3. Tren Global Bulanan
        const trendCtx = document.getElementById('globalTrendChart');
        if (trendCtx) {
            const trendData = @json($globalTrendData ?? []);
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trendData.map(d => d.month),
                    datasets: [
                        {
                            label: 'Target',
                            data: trendData.map(d => d.target),
                            borderColor: 'rgba(148, 163, 184, 0.5)',
                            borderDash: [5, 5],
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false,
                            tension: 0.1
                        },
                        {
                            label: 'Actual',
                            data: trendData.map(d => d.actual),
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: '#6366f1'
                        }
                    ]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: { 
                            beginAtZero: true,
                            ticks: {
                                callback: value => {
                                    if (value >= 1000000) return 'Rp ' + (value / 1000000) + 'JT';
                                    if (value >= 1000) return 'Rp ' + (value / 1000) + 'K';
                                    return 'Rp ' + value;
                                }
                            }
                        }
                    }
                }
            });
        }

        // 4. Category % Standar vs % Actual Line Chart (per-dept filter)
        const catPctCtx = document.getElementById('categoryPctChart');
        const catDeptFilter = document.getElementById('catDeptFilter');
        if (catPctCtx) {
            const allCatData = @json($categoryChartData ?? []);

            const buildDatasets = (filtered) => ({
                labels: filtered.map(d => d.label),
                datasets: [
                    {
                        label: '% Standar (Rasio Global)',
                        data: filtered.map(d => d.standard_pct),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.08)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5,
                        pointBackgroundColor: '#6366f1',
                        borderDash: [5, 4]
                    },
                    {
                        label: '% Actual (Realisasi Global)',
                        data: filtered.map(d => d.actual_pct),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.08)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.3,
                        pointRadius: 5,
                        pointBackgroundColor: '#10b981'
                    }
                ]
            });

            const formatRp = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n));

            const catChartOptions = {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    tooltip: {
                        callbacks: {
                            title: (items) => items[0]?.label ?? '',
                            label: (item) => {
                                const d = allCatData.filter(x => x.department === (catDeptFilter?.value ?? ''))[item.dataIndex];
                                if (!d) return '';
                                if (item.datasetIndex === 0) {
                                    return [
                                        `  % Standar  : ${d.standard_pct.toFixed(2).replace('.', ',')}%`,
                                        `  Budget      : ${formatRp(d.category_budget)}`
                                    ];
                                } else {
                                    return [
                                        `  % Actual   : ${d.actual_pct.toFixed(2).replace('.', ',')}%`,
                                        `  Pengeluaran : ${formatRp(d.used)}`
                                    ];
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { font: { size: 10 }, maxRotation: 30 } },
                    y: { beginAtZero: true, ticks: { callback: v => v.toFixed(1) + '%' } }
                }
            };

            const firstDept = catDeptFilter ? catDeptFilter.value : (allCatData[0]?.department ?? '');
            const catChart = new Chart(catPctCtx, {
                type: 'line',
                data: buildDatasets(allCatData.filter(d => d.department === firstDept)),
                options: catChartOptions
            });

            if (catDeptFilter) {
                catDeptFilter.addEventListener('change', () => {
                    const selected = catDeptFilter.value;
                    const filtered = allCatData.filter(d => d.department === selected);
                    catChart.data = buildDatasets(filtered);
                    catChart.update();
                });
            }
        }
        // 5. Initialize Charts for Each Department (Detail Rows)
        @foreach($departments as $dept)
            // Bar Chart for Dept Kategori
            const barCtx_{{ $dept['id'] }} = document.getElementById('bar-dept-{{ $dept['id'] }}');
            if (barCtx_{{ $dept['id'] }}) {
                const labels = @json($dept['categories']->pluck('name')->values());
                const usedData = @json($dept['categories']->pluck('used')->values());
                new Chart(barCtx_{{ $dept['id'] }}, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Pengeluaran',
                            data: usedData,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#6366f1'
                        }]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            x: {
                                ticks: { font: { size: 9 } }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: value => {
                                        if (value >= 1000000) return 'Rp ' + (value / 1000000) + 'JT';
                                        return 'Rp ' + (value / 1000) + 'K';
                                    },
                                    font: { size: 9 }
                                }
                            }
                        }
                    }
                });
            }

            // Donut Chart for Dept Breakdown
            const donutCtx_{{ $dept['id'] }} = document.getElementById('donut-dept-{{ $dept['id'] }}');
            if (donutCtx_{{ $dept['id'] }}) {
                const labels = @json($dept['categories']->where('used', '>', 0)->pluck('name')->values());
                const usedData = @json($dept['categories']->where('used', '>', 0)->pluck('used')->values());
                const donutHasData = Array.isArray(usedData) && usedData.length > 0;
                new Chart(donutCtx_{{ $dept['id'] }}, {
                    type: 'doughnut',
                    data: {
                        labels: donutHasData ? labels : ['Belum ada realisasi'],
                        datasets: [{
                            data: donutHasData ? usedData : [1],
                            backgroundColor: donutHasData ? fatPalette : ['#cbd5e1'],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        ...commonOptions,
                        cutout: '70%',
                        plugins: {
                            ...commonOptions.plugins,
                            legend: { display: false } // Custom legend in HTML
                        }
                    }
                });
            }

            // Line Chart in Rincian Pengeluaran card
            const expenseLineCtx_{{ $dept['id'] }} = document.getElementById('expense-line-dept-{{ $dept['id'] }}');
            if (expenseLineCtx_{{ $dept['id'] }}) {
                const labels = @json($dept['categories']->pluck('name')->values());
                const usedData = @json($dept['categories']->pluck('used')->values());
                new Chart(expenseLineCtx_{{ $dept['id'] }}, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Pengeluaran',
                            data: usedData,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.12)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointBackgroundColor: '#6366f1'
                        }]
                    },
                    options: {
                        ...commonOptions,
                        scales: {
                            x: {
                                ticks: {
                                    font: { size: 9 },
                                    maxRotation: 0,
                                    autoSkip: true
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    font: { size: 9 },
                                    callback: value => {
                                        if (value >= 1000000) return 'Rp ' + (value / 1000000) + 'JT';
                                        return 'Rp ' + (value / 1000) + 'K';
                                    }
                                }
                            }
                        },
                        plugins: {
                            ...commonOptions.plugins,
                            legend: { display: false }
                        }
                    }
                });
            }
        @endforeach
    </script>
@endpush