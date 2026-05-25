@extends('layouts.dashboard', ['title' => 'Laporan Anggaran'])

@section('content')
@php
    $fmt = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $pct = fn($n) => number_format($n, 2, ',', '.') . '%';

    $periodButtons = [
        'semua' => ['label' => 'Semua Data', 'icon' => '🗂️', 'active' => '#334155',  'light' => '#f1f5f9', 'textInactive' => '#334155'],
        'all'   => ['label' => 'Tahunan',    'icon' => '📅', 'active' => '#4f46e5',  'light' => '#eef2ff', 'textInactive' => '#4338ca'],
        's1'    => ['label' => 'Semester 1', 'icon' => '🔵', 'active' => '#2563eb',  'light' => '#eff6ff', 'textInactive' => '#1d4ed8'],
        's2'    => ['label' => 'Semester 2', 'icon' => '🟣', 'active' => '#7c3aed',  'light' => '#f5f3ff', 'textInactive' => '#6d28d9'],
        'q1'    => ['label' => 'Triwulan 1', 'icon' => '🟢', 'active' => '#059669',  'light' => '#ecfdf5', 'textInactive' => '#047857'],
        'q2'    => ['label' => 'Triwulan 2', 'icon' => '🟡', 'active' => '#d97706',  'light' => '#fffbeb', 'textInactive' => '#b45309'],
        'q3'    => ['label' => 'Triwulan 3', 'icon' => '🟠', 'active' => '#ea580c',  'light' => '#fff7ed', 'textInactive' => '#c2410c'],
        'q4'    => ['label' => 'Triwulan 4', 'icon' => '🔴', 'active' => '#dc2626',  'light' => '#fff1f2', 'textInactive' => '#b91c1c'],
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-5">

    {{-- ── Header ── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">📋 Laporan Anggaran</h2>
            <p class="mt-1 text-sm text-slate-500">Rincian budget, realisasi, dan rasio per departemen & kategori</p>
        </div>

        {{-- Year + Single Month Filter --}}
        <form method="GET" action="{{ route('fat.laporan.index') }}" class="flex flex-wrap items-center gap-2" id="main-filter-form">
            <input type="hidden" name="period" id="period-input" value="{{ $selectedPeriod }}">

            <select name="year" onchange="this.form.submit()"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                @foreach($fiscalYears as $fy)
                    <option value="{{ $fy->year }}" {{ $selectedYear == $fy->year ? 'selected' : '' }}>
                        FY {{ $fy->year }}
                    </option>
                @endforeach
            </select>

            <select id="month-selector"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="">— Per Bulan —</option>
                @foreach($months as $num => $name)
                    <option value="{{ $num }}" {{ (is_numeric($selectedPeriod) && $selectedPeriod == $num) ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>

            <button type="button" id="btn-apply-month"
                    class="rounded-lg bg-slate-600 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700 transition shadow-sm">
                Lihat Bulan
            </button>

            <a href="{{ route('fat.laporan.index') }}"
               class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-50 transition shadow-sm">
                Reset
            </a>
        </form>
    </div>

    {{-- ── Period Toggle Buttons ── --}}
    <div class="flex flex-wrap items-center gap-2">
        @foreach($periodButtons as $key => $btn)
            @php $isActive = $selectedPeriod === $key; @endphp
            <button type="button"
                    onclick="setPeriod('{{ $key }}')"
                    style="{{ $isActive
                        ? 'background-color:' . $btn['active'] . ';color:#ffffff;border-color:transparent;box-shadow:0 4px 12px rgba(0,0,0,0.15);'
                        : 'background-color:' . $btn['light'] . ';color:' . $btn['textInactive'] . ';border-color:#e2e8f0;' }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border px-4 py-2 text-sm font-semibold transition-all duration-150 hover:opacity-90">
                <span>{{ $btn['icon'] }}</span>
                <span>{{ $btn['label'] }}</span>
                @if($isActive)
                    <span style="font-size:10px;opacity:0.7;">✓</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- ── Period Badge & Omset ── --}}
    <div class="flex items-center gap-3 flex-wrap text-sm">
        <span class="rounded-full bg-indigo-50 px-4 py-1.5 text-indigo-700 font-semibold border border-indigo-100">
            FY {{ $selectedYear }} &mdash; {{ $periodLabel }}
        </span>
        <span class="text-slate-400">
            Total Omset Periode: <strong class="text-slate-700">{{ $fmt($globalTotalOmset) }}</strong>
        </span>
        @if($isDetailedView)
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-500 border border-slate-200">
                📊 Tampilan Rinci Per Bulan
            </span>
        @endif
    </div>

    {{-- ── Summary Cards ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $absorpsi = $totalBudget > 0 ? round($totalUsed / $totalBudget * 100, 2) : 0;
            $cards = [
                ['label' => 'Total Budget',    'value' => $fmt($totalBudget),  'color' => 'indigo',  'sub' => '100% dari alokasi periode'],
                ['label' => 'Total Realisasi', 'value' => $fmt($totalUsed),    'color' => 'emerald', 'sub' => $pct($absorpsi) . ' dari budget'],
                ['label' => $totalSurplus >= 0 ? 'Total Surplus' : 'Total Defisit',
                 'value' => ($totalSurplus < 0 ? '-' : '') . $fmt(abs($totalSurplus)),
                 'color' => $totalSurplus >= 0 ? 'emerald' : 'rose',
                 'sub'   => $totalSurplus >= 0 ? 'Masih dalam anggaran' : 'Melebihi anggaran'],
                ['label' => '% Penyerapan', 'value' => $pct($absorpsi), 'color' => 'amber',
                 'sub'   => $absorpsi >= 80 ? '⚠️ Perhatian' : '✅ Normal'],
            ];
        @endphp
        @foreach($cards as $card)
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $card['label'] }}</p>
            <p class="mt-2 text-2xl font-bold text-{{ $card['color'] }}-600 break-all">{{ $card['value'] }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $card['sub'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         TAMPILAN RINCI (Semua Data) — Matriks 12 bulan + Total
    ══════════════════════════════════════════════════════════════════════ --}}
    @if($isDetailedView)
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-slate-700 flex items-center gap-2">
            <span class="text-white font-semibold text-sm">🗂️ Rincian Per Bulan — Jan s/d Des {{ $selectedYear }}</span>
            <span class="ml-auto text-slate-300 text-xs">Klik baris departemen untuk expand/collapse kategori</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                {{-- Header 2 baris --}}
                <thead class="bg-slate-50 font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left border-r border-slate-200 sticky left-0 bg-slate-50 z-10 min-w-[180px]" rowspan="2">
                            Departemen / Kategori
                        </th>
                        @foreach($months as $mNum => $mName)
                            <th class="px-2 py-2 text-center border-r border-slate-200 whitespace-nowrap" colspan="2">
                                {{ $mName }}
                            </th>
                        @endforeach
                        {{-- Total kolom --}}
                        <th class="px-3 py-2 text-center bg-indigo-50 border-l-2 border-indigo-200 whitespace-nowrap" colspan="4">
                            TOTAL TAHUN
                        </th>
                    </tr>
                    <tr>
                        @foreach($months as $mNum => $mName)
                            <th class="px-2 py-2 text-right bg-slate-100/70 whitespace-nowrap">Budget</th>
                            <th class="px-2 py-2 text-right bg-slate-100/70 border-r border-slate-200 whitespace-nowrap">Real.</th>
                        @endforeach
                        <th class="px-3 py-2 text-right bg-indigo-50/70 border-l-2 border-indigo-200 whitespace-nowrap">Budget</th>
                        <th class="px-3 py-2 text-right bg-indigo-50/70 whitespace-nowrap">Realisasi</th>
                        <th class="px-3 py-2 text-right bg-indigo-50/70 whitespace-nowrap">Surplus/Def.</th>
                        <th class="px-3 py-2 text-right bg-indigo-50/70 whitespace-nowrap">% Serap</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $dept)
                        @php
                            $deptAbsorption = $dept['budget'] > 0 ? round($dept['used'] / $dept['budget'] * 100, 1) : 0;
                            $deptSurplusPos = $dept['surplus'] >= 0;
                        @endphp

                        {{-- Department row --}}
                        <tr class="border-b border-slate-200 bg-indigo-50/50 cursor-pointer hover:bg-indigo-100/50 transition"
                            onclick="toggleRows('dept-{{ $dept['id'] }}')">
                            <td class="px-4 py-2.5 font-bold text-slate-800 border-r border-slate-200 sticky left-0 bg-indigo-50/80 z-10">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-[9px] font-bold uppercase text-indigo-700 min-w-6 shrink-0">
                                        {{ strtoupper(substr($dept['name'], 0, 2)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="truncate max-w-[130px] text-[11px]">{{ $dept['name'] }}</div>
                                        <div class="text-[9px] font-normal text-slate-400">{{ $dept['code'] }}</div>
                                    </div>
                                    <span class="ml-auto text-[10px] text-indigo-400 shrink-0" id="chevron-{{ $dept['id'] }}">▼</span>
                                </div>
                            </td>

                            {{-- Monthly columns --}}
                            @foreach($months as $mNum => $mName)
                                @php
                                    $mDeptBudget = $dept['monthly_data'][$mNum]['budget'] ?? 0;
                                    $mDeptUsed   = $dept['monthly_data'][$mNum]['used'] ?? 0;
                                @endphp
                                <td class="px-2 py-2.5 text-right font-mono text-slate-600 text-[10px] whitespace-nowrap">
                                    {{ $fmt($mDeptBudget) }}
                                </td>
                                <td class="px-2 py-2.5 text-right font-mono text-[10px] whitespace-nowrap border-r border-slate-200
                                           {{ $mDeptUsed > $mDeptBudget ? 'text-rose-600 font-bold' : 'text-emerald-600' }}">
                                    {{ $fmt($mDeptUsed) }}
                                </td>
                            @endforeach

                            {{-- Total columns --}}
                            <td class="px-3 py-2.5 text-right font-mono font-semibold text-slate-700 text-[10px] bg-indigo-50/50 border-l-2 border-indigo-200 whitespace-nowrap">
                                {{ $fmt($dept['budget']) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-semibold text-[10px] whitespace-nowrap bg-indigo-50/50
                                       {{ $dept['used'] > $dept['budget'] ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $fmt($dept['used']) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono font-semibold text-[10px] whitespace-nowrap bg-indigo-50/50
                                       {{ $deptSurplusPos ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $deptSurplusPos ? '' : '-' }}{{ $fmt(abs($dept['surplus'])) }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold text-[10px] whitespace-nowrap bg-indigo-50/50
                                       {{ $deptAbsorption > 100 ? 'text-rose-600' : 'text-slate-600' }}">
                                {{ $deptAbsorption }}%
                            </td>
                        </tr>

                        {{-- Category rows --}}
                        @foreach($dept['categories'] as $cat)
                            @php
                                $catAbsorption = $cat['budget'] > 0 ? round($cat['used'] / $cat['budget'] * 100, 1) : 0;
                                $catSurplusPos = $cat['surplus'] >= 0;
                            @endphp
                            <tr class="dept-{{ $dept['id'] }} border-b border-slate-100 bg-white hover:bg-slate-50/50 transition">
                                <td class="py-2 pl-10 pr-3 border-r border-slate-200 sticky left-0 bg-white z-10">
                                    <div class="text-[9px] font-mono text-slate-400">{{ $cat['code'] }}</div>
                                    <div class="text-[10px] font-medium text-slate-600 truncate max-w-[150px]">{{ $cat['name'] }}</div>
                                </td>

                                @foreach($months as $mNum => $mName)
                                    @php
                                        $mCatBudget = $cat['monthly_data'][$mNum]['budget'] ?? 0;
                                        $mCatUsed   = $cat['monthly_data'][$mNum]['used'] ?? 0;
                                    @endphp
                                    <td class="px-2 py-2 text-right font-mono text-slate-500 text-[10px] whitespace-nowrap">
                                        {{ $fmt($mCatBudget) }}
                                    </td>
                                    <td class="px-2 py-2 text-right font-mono text-[10px] whitespace-nowrap border-r border-slate-100
                                               {{ $mCatUsed > $mCatBudget ? 'text-rose-500 font-bold' : 'text-emerald-600' }}">
                                        {{ $fmt($mCatUsed) }}
                                    </td>
                                @endforeach

                                {{-- Total --}}
                                <td class="px-3 py-2 text-right font-mono text-[10px] text-slate-600 bg-indigo-50/30 border-l-2 border-indigo-100 whitespace-nowrap">
                                    {{ $fmt($cat['budget']) }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono text-[10px] bg-indigo-50/30 whitespace-nowrap
                                           {{ $cat['used'] > $cat['budget'] ? 'text-rose-600 font-bold' : 'text-emerald-600' }}">
                                    {{ $fmt($cat['used']) }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono text-[10px] bg-indigo-50/30 whitespace-nowrap
                                           {{ $catSurplusPos ? 'text-emerald-600' : 'text-rose-600 font-bold' }}">
                                    {{ $catSurplusPos ? '' : '-' }}{{ $fmt(abs($cat['surplus'])) }}
                                </td>
                                <td class="px-3 py-2 text-right font-semibold text-[10px] bg-indigo-50/30 whitespace-nowrap
                                           {{ $catAbsorption > 100 ? 'text-rose-600' : 'text-slate-500' }}">
                                    {{ $catAbsorption }}%
                                </td>
                            </tr>
                        @endforeach

                    @empty
                        <tr>
                            <td colspan="{{ 1 + (count($activeMonths) * 2) + 4 }}"
                                class="px-5 py-10 text-center text-slate-400">
                                Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                {{-- Grand Total Footer --}}
                <tfoot class="border-t-2 border-slate-300 bg-slate-700 text-white font-bold text-[10px]">
                    <tr>
                        <td class="px-4 py-3 border-r border-slate-500 sticky left-0 bg-slate-700 z-10 whitespace-nowrap">
                            GRAND TOTAL
                        </td>
                        @foreach($months as $mNum => $mName)
                            @php
                                $mTotalBudget = $report->sum(fn($d) => $d['monthly_data'][$mNum]['budget'] ?? 0);
                                $mTotalUsed   = $report->sum(fn($d) => $d['monthly_data'][$mNum]['used'] ?? 0);
                            @endphp
                            <td class="px-2 py-3 text-right font-mono whitespace-nowrap text-slate-200">
                                {{ $fmt($mTotalBudget) }}
                            </td>
                            <td class="px-2 py-3 text-right font-mono whitespace-nowrap border-r border-slate-500
                                       {{ $mTotalUsed > $mTotalBudget ? 'text-rose-300' : 'text-emerald-300' }}">
                                {{ $fmt($mTotalUsed) }}
                            </td>
                        @endforeach
                        <td class="px-3 py-3 text-right font-mono whitespace-nowrap border-l-2 border-indigo-400 bg-indigo-700/60">
                            {{ $fmt($totalBudget) }}
                        </td>
                        <td class="px-3 py-3 text-right font-mono whitespace-nowrap bg-indigo-700/60
                                   {{ $totalUsed > $totalBudget ? 'text-rose-300' : 'text-emerald-300' }}">
                            {{ $fmt($totalUsed) }}
                        </td>
                        <td class="px-3 py-3 text-right font-mono whitespace-nowrap bg-indigo-700/60
                                   {{ $totalSurplus >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                            {{ $totalSurplus < 0 ? '-' : '' }}{{ $fmt(abs($totalSurplus)) }}
                        </td>
                        <td class="px-3 py-3 text-right whitespace-nowrap bg-indigo-700/60">
                            {{ $totalBudget > 0 ? number_format($totalUsed / $totalBudget * 100, 1, ',', '.') : '0' }}%
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         TAMPILAN RINGKASAN (Tahunan / Semester / Triwulan / Per Bulan)
    ══════════════════════════════════════════════════════════════════════ --}}
    @else
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3 text-left">Departemen / Kategori</th>
                        <th class="px-5 py-3 text-right">Budget Jatah</th>
                        <th class="px-5 py-3 text-right">Realisasi</th>
                        <th class="px-5 py-3 text-right whitespace-nowrap">Rasio Budget</th>
                        <th class="px-5 py-3 text-right whitespace-nowrap">Rasio Realisasi</th>
                        <th class="px-5 py-3 text-right">Surplus / Defisit</th>
                        <th class="px-5 py-3 text-right">% Penyerapan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $dept)
                        @php
                            $deptAbsorption = $dept['budget'] > 0 ? round($dept['used'] / $dept['budget'] * 100, 1) : 0;
                            $deptSurplusPos = $dept['surplus'] >= 0;
                        @endphp
                        <tr class="border-b border-slate-200 bg-indigo-50/60 cursor-pointer hover:bg-indigo-50 transition"
                            onclick="toggleRows('dept-{{ $dept['id'] }}')">
                            <td class="px-5 py-3 font-bold text-slate-800">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold uppercase text-indigo-700 min-w-7">
                                        {{ strtoupper(substr($dept['name'], 0, 2)) }}
                                    </span>
                                    <div>
                                        <div class="truncate max-w-[220px]">{{ $dept['name'] }}</div>
                                        <div class="text-[10px] font-normal text-slate-400">{{ $dept['code'] }}</div>
                                    </div>
                                    <span class="ml-auto text-xs text-indigo-400" id="chevron-{{ $dept['id'] }}">▼</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-slate-700 font-semibold">{{ $fmt($dept['budget']) }}</td>
                            <td class="px-5 py-3 text-right font-mono font-semibold {{ $dept['used'] > $dept['budget'] ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $fmt($dept['used']) }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-700 whitespace-nowrap">
                                    {{ $pct($dept['budget_ratio']) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-bold whitespace-nowrap
                                    {{ $dept['realisasi_ratio'] > $dept['budget_ratio'] ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $pct($dept['realisasi_ratio']) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-mono font-semibold {{ $deptSurplusPos ? 'text-emerald-600' : 'text-rose-600' }}">
                                <div class="whitespace-nowrap">{{ $deptSurplusPos ? '' : '-' }}{{ $fmt(abs($dept['surplus'])) }}</div>
                                <div class="text-[9px] font-bold {{ $deptSurplusPos ? 'text-emerald-500' : 'text-rose-500' }}">
                                    {{ $deptSurplusPos ? 'Surplus' : 'Defisit' }}
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex flex-col items-end gap-1">
                                    <span class="text-xs font-bold text-slate-700">{{ $deptAbsorption }}%</span>
                                    <div class="w-16 bg-slate-200 rounded-full h-1.5 min-w-[64px]">
                                        <div class="h-1.5 rounded-full {{ $deptAbsorption > 100 ? 'bg-rose-500' : ($deptAbsorption >= 80 ? 'bg-amber-400' : 'bg-emerald-500') }}"
                                             style="width:{{ min($deptAbsorption, 100) }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        @foreach($dept['categories'] as $cat)
                            @php
                                $catAbsorption = $cat['budget'] > 0 ? round($cat['used'] / $cat['budget'] * 100, 1) : 0;
                                $catSurplusPos = $cat['surplus'] >= 0;
                            @endphp
                            <tr class="dept-{{ $dept['id'] }} border-b border-slate-100 bg-white hover:bg-slate-50/50 transition">
                                <td class="py-2.5 pl-14 pr-5 text-slate-700">
                                    <div class="text-xs font-mono text-slate-400">{{ $cat['code'] }}</div>
                                    <div class="font-medium truncate max-w-[220px]">{{ $cat['name'] }}</div>
                                </td>
                                <td class="px-5 py-2.5 text-right font-mono text-slate-600 text-xs">{{ $fmt($cat['budget']) }}</td>
                                <td class="px-5 py-2.5 text-right font-mono text-xs {{ $cat['used'] > $cat['budget'] ? 'text-rose-600 font-bold' : 'text-emerald-600' }}">
                                    {{ $fmt($cat['used']) }}
                                </td>
                                <td class="px-5 py-2.5 text-right text-xs text-slate-500 whitespace-nowrap">{{ $pct($cat['budget_ratio']) }}</td>
                                <td class="px-5 py-2.5 text-right text-xs whitespace-nowrap {{ $cat['realisasi_ratio'] > $cat['budget_ratio'] ? 'text-rose-600 font-bold' : 'text-emerald-600' }}">
                                    {{ $pct($cat['realisasi_ratio']) }}
                                </td>
                                <td class="px-5 py-2.5 text-right font-mono text-xs {{ $catSurplusPos ? 'text-emerald-600' : 'text-rose-600 font-bold' }}">
                                    <div class="whitespace-nowrap">{{ $catSurplusPos ? '' : '-' }}{{ $fmt(abs($cat['surplus'])) }}</div>
                                    <div class="text-[9px] font-semibold">{{ $catSurplusPos ? 'Surplus' : 'Defisit' }}</div>
                                </td>
                                <td class="px-5 py-2.5 text-right">
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="text-[11px] font-semibold {{ $catAbsorption > 100 ? 'text-rose-600' : 'text-slate-600' }}">
                                            {{ $catAbsorption }}%
                                        </span>
                                        <div class="w-16 bg-slate-100 rounded-full h-1 min-w-[64px]">
                                            <div class="h-1 rounded-full {{ $catAbsorption > 100 ? 'bg-rose-500' : ($catAbsorption >= 80 ? 'bg-amber-400' : 'bg-emerald-500') }}"
                                                 style="width:{{ min($catAbsorption, 100) }}%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-slate-400">
                                Tidak ada data untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t-2 border-slate-300 bg-slate-100 font-bold text-slate-800">
                    <tr>
                        <td class="px-5 py-3">TOTAL</td>
                        <td class="px-5 py-3 text-right font-mono">{{ $fmt($totalBudget) }}</td>
                        <td class="px-5 py-3 text-right font-mono {{ $totalUsed > $totalBudget ? 'text-rose-700' : 'text-emerald-700' }}">
                            {{ $fmt($totalUsed) }}
                        </td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">100,00%</td>
                        <td class="px-5 py-3 text-right whitespace-nowrap {{ $globalTotalOmset > 0 && ($totalUsed / $globalTotalOmset * 100) > 100 ? 'text-rose-700' : 'text-emerald-700' }}">
                            {{ $globalTotalOmset > 0 ? $pct(round($totalUsed / $globalTotalOmset * 100, 2)) : '0%' }}
                        </td>
                        <td class="px-5 py-3 text-right font-mono {{ $totalSurplus >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            <div class="whitespace-nowrap">{{ $totalSurplus < 0 ? '-' : '' }}{{ $fmt(abs($totalSurplus)) }}</div>
                        </td>
                        <td class="px-5 py-3 text-right">
                            {{ $totalBudget > 0 ? number_format($totalUsed / $totalBudget * 100, 1, ',', '.') : '0' }}%
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    <p class="text-xs text-slate-400 text-right">
        Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB
    </p>
</div>

@push('scripts')
<script>
    // Category rows: hidden by default
    @foreach($report as $dept)
        document.querySelectorAll('.dept-{{ $dept['id'] }}').forEach(r => r.classList.add('hidden'));
    @endforeach

    function toggleRows(cls) {
        const rows = document.querySelectorAll('.' + cls);
        const chevron = document.getElementById('chevron-' + cls.replace('dept-', ''));
        const isHidden = rows[0]?.classList.contains('hidden') ?? true;
        rows.forEach(r => r.classList.toggle('hidden'));
        if (chevron) chevron.textContent = isHidden ? '▲' : '▼';
    }

    function setPeriod(key) {
        document.getElementById('period-input').value = key;
        document.getElementById('month-selector').value = '';
        document.getElementById('main-filter-form').submit();
    }

    document.getElementById('btn-apply-month').addEventListener('click', function () {
        const monthVal = document.getElementById('month-selector').value;
        if (monthVal) {
            document.getElementById('period-input').value = monthVal;
            document.getElementById('main-filter-form').submit();
        }
    });
</script>
@endpush

@endsection
