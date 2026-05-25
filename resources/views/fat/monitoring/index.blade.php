@extends('layouts.dashboard', ['title' => 'Monitoring Budget'])

@section('content')
    <div class="mx-auto max-w-7xl">
        {{-- Header & Month Filter --}}
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    @if(!$is_fat_or_superadmin && $departmentsData->first())
                        Anggaran 
                        @if(auth()->user()->isManager() && isset($managerDepartmentsList) && $managerDepartmentsList->count() > 1)
                            <div class="relative inline-block text-left" id="manager-dept-dropdown-wrapper">
                                <button type="button" onclick="document.getElementById('manager-dept-menu').classList.toggle('hidden')" class="inline-flex w-full justify-center items-center gap-1 bg-transparent text-2xl font-bold text-indigo-600 border-b-2 border-indigo-600 border-dashed hover:text-indigo-800 hover:border-indigo-800 transition-colors cursor-pointer focus:outline-none pb-[2px]">
                                    {{ $departmentsData->first()->name }}
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div id="manager-dept-menu" class="hidden absolute left-0 z-50 mt-2 w-72 origin-top-left rounded-xl bg-white shadow-xl ring-1 ring-slate-200 focus:outline-none overflow-hidden">
                                    <div class="max-h-60 overflow-y-auto py-1">
                                        @foreach($managerDepartmentsList as $mDept)
                                            <a href="{{ route('monitoring.index', ['dept_id' => $mDept->id, 'month' => request('month', $selectedMonth), 'year' => request('year', $activeFiscalYear->year)]) }}" class="{{ $departmentsData->first()->id == $mDept->id ? 'bg-indigo-50 text-indigo-700 font-bold border-l-4 border-indigo-600' : 'text-slate-700 hover:bg-slate-50 hover:text-indigo-600 font-medium border-l-4 border-transparent' }} block px-4 py-2.5 text-base transition-colors">
                                                {{ $mDept->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            {{ $departmentsData->first()->name }}
                        @endif
                    @else
                        Monitoring Budget
                    @endif
                </h2>
                <div class="mt-1 flex items-center gap-2 text-sm text-slate-500">
                    <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                        Periode: {{ $currentMonthName }}
                    </span>
                    <span>• Pantau anggaran & pengeluaran bulan ini.</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <form action="{{ route('monitoring.index') }}" method="GET" class="flex items-center gap-2 bg-white p-1.5 rounded-lg border border-slate-300 shadow-sm">
                    @if(request()->has('dept_id'))
                        <input type="hidden" name="dept_id" value="{{ request('dept_id') }}">
                    @endif
                    <select name="month" onchange="this.form.submit()" class="rounded-md border-0 bg-transparent text-sm font-medium text-slate-600 py-1.5 pl-3 focus:ring-0 cursor-pointer">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                    <select name="year" onchange="this.form.submit()" class="rounded-md border-0 bg-transparent text-sm font-medium text-slate-600 py-1.5 focus:ring-0 cursor-pointer">
                         <option value="{{ $activeFiscalYear->year }}" selected>{{ $activeFiscalYear->year }}</option>
                    </select>
                </form>
            </div>
        </div>

        {{-- Summary Cards --}}
        @php
            $grandUtil = $grandSummary['total_budget'] > 0 ? ($grandSummary['total_used'] / $grandSummary['total_budget']) * 100 : 0;
            if ($grandUtil > 100) $grandColor = 'text-rose-700';
            elseif ($grandUtil <= 20) $grandColor = 'text-amber-600';
            elseif ($grandUtil <= 80) $grandColor = 'text-emerald-600';
            else $grandColor = 'text-amber-600';
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Pagu (Bulan Ini)</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">Rp {{ number_format($grandSummary['total_budget'], 0, ',', '.') }}</p>
                <div class="mt-2 text-xs text-slate-400">
                    {{ !$is_fat_or_superadmin && $departmentsData->first() ? 'Alokasi Pagu Anda' : 'Semua departemen' }}
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Realisasi</p>
                @php
                    $isOverBudget = $grandSummary['total_used'] > $grandSummary['total_budget'];
                @endphp
                <div class="flex items-center justify-between gap-2">
                    <p class="mt-2 text-2xl font-bold {{ $grandColor }}">Rp {{ number_format($grandSummary['total_used'], 0, ',', '.') }}</p>
                    @if(!$is_fat_or_superadmin && $departmentsData->first())
                        <span class="mt-2 inline-flex items-center rounded-md px-2 py-1 text-[10px] font-bold ring-1 ring-inset {{ $isOverBudget ? 'bg-rose-50 text-rose-700 ring-rose-600/20' : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' }}">
                            {{ $isOverBudget ? 'OVER BUDGET' : 'ON BUDGET' }}
                        </span>
                    @endif
                </div>
                <div class="mt-2">
                    <div class="flex items-center justify-between text-[10px] mb-1">
                        <span class="text-slate-400">{{ round($grandUtil, 1) }}% dari pagu</span>
                        <span class="font-bold {{ $grandColor }}">{{ number_format($grandSummary['total_used'], 0, ',', '.') }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full transition-all {{ $isOverBudget ? 'bg-rose-600' : ($grandUtil <= 20 ? 'bg-amber-500' : ($grandUtil <= 80 ? 'bg-emerald-500' : 'bg-amber-500')) }}" 
                             style="width: {{ min($grandUtil, 100) }}%"></div>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm font-bold">
                <p class="text-xs uppercase tracking-wide text-slate-500">Surplus / Defisit</p>
                @php $grandIsSurplus = $grandSummary['total_remaining'] >= 0; @endphp
                <p class="mt-2 text-2xl font-bold {{ $grandIsSurplus ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $grandIsSurplus ? '' : '-' }}Rp {{ number_format(abs($grandSummary['total_remaining']), 0, ',', '.') }}
                </p>
                <div class="mt-2 text-xs {{ $grandIsSurplus ? 'text-emerald-400' : 'text-rose-400' }} uppercase">TOTAL {{ $grandIsSurplus ? 'Surplus' : 'Defisit' }}</div>
            </div>
        </div>

        @if(!$is_fat_or_superadmin && $departmentsData->first())
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-indigo-600 rounded-full"></span>
                        Status Cost Center (Kategori)
                    </h3>
                    <div class="text-xs text-slate-400 italic">Dipantau real-time</div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($departmentsData->first()->enriched_categories as $cat)
                        @php
                            $catUtil = $cat->utilization;
                            $catIsOver = $catUtil > 100;
                            $catBarColor = $catIsOver ? 'bg-rose-500' : ($catUtil <= 20 ? 'bg-amber-400' : ($catUtil <= 80 ? 'bg-emerald-500' : 'bg-amber-400'));
                            $catTextColor = $catIsOver ? 'text-rose-600' : ($catUtil <= 20 ? 'text-amber-600' : ($catUtil <= 80 ? 'text-emerald-600' : 'text-amber-600'));
                        @endphp
                        <a href="{{ route('monitoring.categories.show', $cat->id) }}" class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all">
                            <div class="flex items-start justify-between mb-3">
                                <div class="min-w-0">
                                    <h4 class="text-sm font-bold text-slate-800 truncate group-hover:text-indigo-600 transition-colors">{{ $cat->name }}</h4>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ $cat->code }}</p>
                                </div>
                                <div class="text-[10px] font-bold {{ $catTextColor }} bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100">
                                    {{ round($catUtil, 1) }}%
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="text-slate-400">Pagu:</span>
                                    <span class="font-semibold text-slate-700">Rp {{ number_format($cat->calculated_budget, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="text-slate-400">Realisasi:</span>
                                    <span class="font-bold {{ $catTextColor }}">Rp {{ number_format($cat->total_used, 0, ',', '.') }}</span>
                                </div>
                                <div class="pt-1">
                                    <div class="w-full bg-slate-100 rounded-full h-1 overflow-hidden">
                                        <div class="h-1 {{ $catBarColor }} rounded-full" style="width: {{ min($catUtil, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- SETUP ALERTS --}}
        @if($is_fat_or_superadmin && !$globalMonthly)
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 flex items-start gap-3">
                <span class="text-rose-500 text-lg mt-0.5">🚨</span>
                <div>
                    <p class="text-sm font-bold text-rose-800">Budget Global bulan {{ $currentMonthName }} belum diisi!</p>
                    <p class="text-xs text-rose-600 mt-0.5">Semua pagu departemen akan bernilai Rp 0. Silakan input budget global terlebih dahulu.</p>
                    <a href="{{ route('fat.global-budgets.create') }}"
                        class="inline-flex items-center gap-1.5 mt-2 text-xs font-bold text-rose-700 bg-rose-100 hover:bg-rose-200 px-3 py-1.5 rounded-lg transition">
                        ＋ Input Budget Global →
                    </a>
                </div>
            </div>
        @endif

        {{-- Department Table with Expandable Rows (FAT only) --}}
        @if($is_fat_or_superadmin)
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Monitoring Per Departemen</h3>
                    <span class="text-xs text-slate-500 bg-white px-2 py-1 rounded border border-slate-200">{{ $departmentsData->count() }} Departemen</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-3 w-8"></th>
                                <th class="px-3 py-3">Departemen</th>
                                <th class="px-3 py-3 text-right">Pagu (Bulan Ini)</th>
                                <th class="px-3 py-3 text-right">Realisasi</th>
                                <th class="px-3 py-3 text-center" style="min-width:100px">Penggunaan</th>
                                <th class="px-3 py-3 text-center">Status Realisasi</th>
                                <th class="px-3 py-3 text-right">Surplus / Defisit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentsData as $dept)
                                @php
                                    $deptUtil = $dept->monthly_utilization;
                                    if ($deptUtil > 100) $deptStatusColor = 'text-rose-700';
                                    elseif ($deptUtil <= 20) $deptStatusColor = 'text-amber-600';
                                    elseif ($deptUtil <= 80) $deptStatusColor = 'text-emerald-600';
                                    else $deptStatusColor = 'text-amber-600';

                                    $barPct = min($deptUtil, 100);
                                    $barColor = $deptUtil > 100 ? 'bg-rose-600'
                                        : ($deptUtil <= 20 ? 'bg-amber-500'
                                        : ($deptUtil <= 80 ? 'bg-emerald-500' : 'bg-amber-500'));

                                    $deptIsSurplus = $dept->monthly_remaining >= 0;
                                    $deptIsOverBudget = (float) $dept->monthly_used > (float) $dept->monthly_budget;
                                @endphp
                                <tr class="border-b border-slate-100 hover:bg-slate-50/50 cursor-pointer transition-colors"
                                    onclick="toggleDeptRow({{ $dept->id }})">
                                    <td class="px-3 py-4 text-center">
                                        <span id="arrow-dept-{{ $dept->id }}" class="text-slate-400 transition-transform inline-block text-xs">▶</span>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="font-semibold text-slate-800">{{ $dept->name }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $dept->enriched_categories->count() }} kategori</div>
                                    </td>
                                    <td class="px-3 py-4 text-right font-mono text-slate-600">Rp {{ number_format($dept->monthly_budget, 0, ',', '.') }}</td>
                                    <td class="px-3 py-4 text-right font-mono {{ $deptStatusColor }} font-medium">
                                        <button type="button"
                                            onclick="event.stopPropagation(); openRealisasiPopup({{ $dept->id }})"
                                            class="rounded-md px-1.5 py-0.5 hover:bg-slate-100 transition">
                                            Rp {{ number_format($dept->monthly_used, 0, ',', '.') }}
                                        </button>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="text-[10px] font-bold {{ $deptStatusColor }}">
                                                {{ number_format($deptUtil, 1, ',', '.') }}%
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-2" style="min-width:80px">
                                                <div class="{{ $barColor }} h-2 rounded-full transition-all" style="width: {{ $barPct }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-center">
                                        <div class="inline-flex flex-col items-center">
                                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $deptIsOverBudget ? 'bg-rose-50 text-rose-700 ring-rose-600/20' : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' }}">
                                                {{ $deptIsOverBudget ? 'Over Budget' : 'On Budget' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-right font-mono font-medium">
                                        <span class="{{ $deptIsSurplus ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $deptIsSurplus ? '' : '-' }}Rp {{ number_format(abs($dept->monthly_remaining), 0, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>

                                {{-- Category Detail --}}
                                <tr id="detail-dept-{{ $dept->id }}" class="hidden">
                                    <td colspan="7" class="px-0 py-0">
                                        <div class="bg-slate-50/80 border-y border-slate-100 px-8 py-4">
                                            <table class="w-full text-sm text-left">
                                                <thead class="text-[10px] text-slate-500 uppercase">
                                                    <tr class="border-b border-slate-200">
                                                        <th class="py-2 pr-3 text-left">Nama Kategori</th>
                                                        <th class="py-2 px-3 text-center">Penggunaan</th>
                                                        <th class="py-2 px-3 text-right">Pagu</th>
                                                        <th class="py-2 px-3 text-right">Realisasi</th>
                                                        <th class="py-2 px-3 text-right">Sisa</th>
                                                        <th class="py-2 pl-3 text-center">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @foreach($dept->enriched_categories as $cat)
                                                        @php
                                                            $catUtil = $cat->utilization;
                                                            $catIsOver = $catUtil > 100;
                                                            $catBarColor = $catIsOver ? 'bg-rose-500' : ($catUtil <= 20 ? 'bg-amber-400' : ($catUtil <= 80 ? 'bg-emerald-500' : 'bg-amber-400'));
                                                            $catTextColor = $catIsOver ? 'text-rose-600' : ($catUtil <= 20 ? 'text-amber-600' : ($catUtil <= 80 ? 'text-emerald-600' : 'text-amber-600'));
                                                        @endphp
                                                        <tr class="hover:bg-white/60 transition-colors">
                                                            <td class="py-3 pr-3">
                                                                <div class="font-medium text-slate-800">{{ $cat->name }}</div>
                                                                <div class="text-[10px] text-slate-400">{{ $cat->code }}</div>
                                                            </td>
                                                            <td class="py-3 px-3">
                                                                <div class="flex flex-col items-center gap-1">
                                                                    <div class="text-[10px] font-bold {{ $catTextColor }}">{{ number_format($catUtil, 1, ',', '.') }}%</div>
                                                                    <div class="w-full bg-slate-200 rounded-full h-1.5" style="min-width:60px">
                                                                        <div class="{{ $catBarColor }} h-1.5 rounded-full" style="width: {{ min($catUtil, 100) }}%"></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="py-3 px-3 text-right font-mono text-xs text-slate-600">Rp {{ number_format($cat->calculated_budget, 0, ',', '.') }}</td>
                                                            <td class="py-3 px-3 text-right font-mono text-xs {{ $catTextColor }} font-medium">Rp {{ number_format($cat->total_used, 0, ',', '.') }}</td>
                                                            <td class="py-3 px-3 text-right font-mono text-xs font-medium">
                                                                <span class="{{ $cat->remaining >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                                                    Rp {{ number_format(abs($cat->remaining), 0, ',', '.') }}
                                                                </span>
                                                            </td>
                                                            <td class="py-3 pl-3 text-center">
                                                                <a href="{{ route('monitoring.categories.show', $cat->id) }}" class="p-1 text-slate-600 hover:bg-slate-100 rounded transition text-xs">📄</a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    <div id="modal-realisasi-dept" class="hidden fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeRealisasiPopup()"></div>
        <div class="relative flex w-[750px] max-w-[90vw] max-h-[75vh] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-2xl">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-5 py-3 text-slate-900">
                <div>
                    <h3 id="realisasi-title" class="text-lg font-bold text-slate-900">Detail Realisasi Departemen</h3>
                    <p class="text-xs text-slate-500">Periode: {{ $currentMonthName }}</p>
                </div>
                <button type="button" onclick="closeRealisasiPopup()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Tutup</button>
            </div>

            <div class="flex-1 overflow-y-auto">
            <div class="grid grid-cols-1 gap-3 p-3 sm:p-4 lg:grid-cols-2">
                <div class="space-y-3">
                    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <h4 class="text-lg font-bold text-slate-900">Q1 Ringkasan Anggaran</h4>
                            <span id="realisasi-status" class="rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-700">-</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                                <p class="text-[10px] uppercase tracking-wide text-slate-500">Total Alokasi</p>
                                <p id="realisasi-allocated" class="mt-1 text-lg leading-tight font-bold text-slate-900 tracking-tight break-words">Rp 0</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                                <p class="text-[10px] uppercase tracking-wide text-slate-500">Total Pengeluaran</p>
                                <p id="realisasi-used" class="mt-1 text-lg leading-tight font-bold text-slate-900 tracking-tight break-words">Rp 0</p>
                            </div>
                            <div class="rounded-xl bg-blue-50 border border-blue-100 p-3 sm:col-span-2">
                                <p class="text-[10px] uppercase tracking-wide text-blue-500">Sisa Anggaran</p>
                                <p id="realisasi-remaining" class="mt-1 text-xl leading-tight font-bold text-blue-600 tracking-tight break-words">Rp 0</p>
                            </div>
                        </div>
                        <div class="mb-2 flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-700">Porsi Terpakai (Omset)</span>
                            <span id="realisasi-utilization" class="font-semibold text-slate-900">0%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div id="realisasi-progress" class="h-2 rounded-full bg-amber-500" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- 'Penggunaan Budget per Kategori' removed from popup as requested -->

                    @if($is_fat_or_superadmin)
                    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
                        <div class="space-y-3">
                            <form id="realisasi-ratio-form" method="POST" action="" class="space-y-1.5">
                                @csrf
                                @method('PATCH')
                                <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Update Rasio Dept</label>
                                <div class="flex gap-2">
                                    <input id="realisasi-ratio-input" type="number" name="budget_ratio_percent" step="0.01" min="0" max="100" class="rounded-xl border-slate-200 py-1.5 text-xs flex-1" placeholder="Ratio %" required>
                                    <button class="rounded-xl bg-slate-900 text-white px-3 py-1.5 text-xs font-bold">Update</button>
                                </div>
                            </form>

                            <form id="realisasi-override-form" method="POST" action="" class="space-y-1.5">
                                @csrf
                                @method('PATCH')
                                <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Override Nominal Bulanan</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <input id="realisasi-override-month" type="month" name="month" class="rounded-xl border-slate-200 py-1.5 text-xs" required>
                                    <input type="number" name="amount" placeholder="Nominal Rp" class="rounded-xl border-slate-200 py-1.5 text-xs" required>
                                </div>
                                <button class="rounded-xl bg-indigo-600 text-white px-3 py-1.5 text-xs font-bold w-full">Set Override</button>
                            </form>
                        </div>
                    </div>
                    @endif

                    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
                        <h4 class="text-lg font-semibold text-slate-900 mb-2">Peringatan Anggaran</h4>
                        <div id="realisasi-alerts" class="space-y-1.5"></div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
                        <h4 class="text-lg font-semibold text-slate-900 mb-3 text-center">Rincian Pengeluaran</h4>
                        <div class="mx-auto max-w-[180px]">
                            <div class="mx-auto flex h-[140px] w-[140px] items-center justify-center relative">
                                <canvas id="realisasi-donut-chart"></canvas>
                            </div>
                        </div>
                        <div class="text-center mb-3 mt-2">
                            <p class="text-[10px] text-slate-400">Pengeluaran Tertinggi</p>
                            <p id="realisasi-top-category" class="text-sm font-semibold text-slate-800">-</p>
                        </div>
                        <div class="flex items-center justify-end mb-2 gap-2">
                            <div class="text-xs text-slate-500 font-semibold">{{ $is_fat_or_superadmin ? 'Real | Std' : 'Realisasi' }}</div>
                            <div class="text-xs inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-semibold js-realisasi-std-percent {{ $is_fat_or_superadmin ? '' : 'hidden' }}">0%</div>
                        </div>
                        <div id="realisasi-donut-legend" class="space-y-1"></div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT CATEGORY --}}
    <div id="modal-edit-category" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
            onclick="document.getElementById('modal-edit-category').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-800">Edit Kategori</h3>
                <button onclick="document.getElementById('modal-edit-category').classList.add('hidden')"
                    class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form id="form-edit-category" action="" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kode Kategori</label>
                    <input type="text" id="edit-cat-code-mon"
                        class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-500 text-sm" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Kategori</label>
                    <input type="text" name="name" id="edit-cat-name-mon"
                        class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Rasio Budget (%)</label>
                    <input type="number" name="budget_ratio_percent" id="edit-cat-ratio-mon" step="0.01" min="0" max="100"
                        class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                    <textarea name="description" id="edit-cat-desc-mon" rows="2"
                        class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm"></textarea>
                </div>
                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-edit-category').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-md shadow-indigo-200">Update</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL ADD EXPENSE --}}
    <div id="modal-add-expense" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
            onclick="document.getElementById('modal-add-expense').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-in">
            <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4 flex justify-between items-center text-white">
                <div>
                    <h3 class="font-bold text-lg">Catat Pengeluaran</h3>
                    <p id="expense-cat-name" class="text-sm text-emerald-100 opacity-90">Kategori: -</p>
                </div>
                <button onclick="document.getElementById('modal-add-expense').classList.add('hidden')"
                    class="text-emerald-100 hover:text-white text-xl">✕</button>
            </div>
            <form action="{{ route('monitoring.expenses.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="budget_category_id" id="expense-cat-id">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}"
                            class="w-full rounded-lg border-slate-300 focus:ring-emerald-500 focus:border-emerald-500 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nominal (Rp)</label>
                        <input type="number" name="amount" min="0"
                            class="w-full rounded-lg border-slate-300 focus:ring-emerald-500 focus:border-emerald-500 text-sm font-mono"
                            placeholder="0" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi / Keterangan</label>
                    <textarea name="description" rows="3"
                        class="w-full rounded-lg border-slate-300 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                        placeholder="Contoh: Pembelian ATK bulan Februari..." required></textarea>
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modal-add-expense').classList.add('hidden')"
                        class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Batal</button>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-200">Simpan Pengeluaran</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Auto-refresh session setiap 15 menit untuk mencegah Page Expired (419)
            setInterval(function() {
                fetch(window.location.href, { method: 'HEAD' })
                    .catch(e => console.log('Keep-alive failed', e));
            }, 1000 * 60 * 15);

            const departmentPopupData = @json($departmentPopupData ?? []);
            const isFatOrSuperAdmin = @json($is_fat_or_superadmin);
            let realisasiLineChart = null;
            let realisasiDonutChart = null;

            const formatCurrency = (value) => 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Math.round(Number(value || 0)));

            function closeRealisasiPopup() {
                document.getElementById('modal-realisasi-dept')?.classList.add('hidden');
            }

            function renderRealisasiLineChart(categories) {
                const canvas = document.getElementById('realisasi-line-chart');
                if (!canvas || typeof Chart === 'undefined') return;

                if (realisasiLineChart) {
                    realisasiLineChart.destroy();
                }

                realisasiLineChart = new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: categories.map(c => c.name ?? '-'),
                        datasets: [{
                            label: 'Pengeluaran',
                            data: categories.map(c => Number(c.used || 0)),
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99,102,241,0.12)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (v) => {
                                        if (v >= 1000000) return 'Rp ' + (v / 1000000) + 'JT';
                                        return 'Rp ' + (v / 1000) + 'K';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            function renderRealisasiDonut(categories, deptAllocated = 0) {
                const canvas = document.getElementById('realisasi-donut-chart');
                const legend = document.getElementById('realisasi-donut-legend');
                if (!canvas || typeof Chart === 'undefined') return;

                if (realisasiDonutChart) {
                    realisasiDonutChart.destroy();
                }

                const nonZero = categories.filter(c => Number(c.used || 0) > 0);
                const labels = nonZero.length ? nonZero.map(c => c.name ?? '-') : ['Belum ada realisasi'];
                const values = nonZero.length ? nonZero.map(c => Number(c.used || 0)) : [1];
                const colors = nonZero.length
                    ? ['#6366F1', '#3B82F6', '#06B6D4', '#10B981', '#F59E0B', '#F97316', '#EF4444', '#8B5CF6']
                    : ['#CBD5E1'];

                realisasiDonutChart = new Chart(canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 0 }] },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: { legend: { display: false } }
                    }
                });

                if (legend) {
                    const total = nonZero.reduce((sum, row) => sum + Number(row.used || 0), 0);
                    const totalAllocated = categories.reduce((s, r) => s + Number(r.allocated || 0), 0);
                    const ranked = [...categories].sort((a, b) => Number(b.used || 0) - Number(a.used || 0));

                    legend.innerHTML = ranked.map((row, idx) => {
                        const used = Number(row.used || 0);
                        const allocated = Number(row.allocated || 0);
                        const share = total > 0 ? (used / total) * 100 : 0;
                        // Prefer explicit stored ratio (jatah) when available; otherwise fallback to allocated/deptAllocated
                        let percentOfDept = 0;
                        if (row && row.budget_ratio_percent != null && row.budget_ratio_percent !== '') {
                            percentOfDept = Number(row.budget_ratio_percent);
                        } else if (allocated > 0 && deptAllocated > 0) {
                            percentOfDept = (allocated / deptAllocated) * 100;
                        } else {
                            percentOfDept = 0;
                        }
                        const color = nonZero.length ? colors[nonZero.findIndex(x => x.name === row.name) % colors.length] : '#94A3B8';
                        const isOverBudget = allocated > 0 && used > allocated;
                        return `
                            <div class="flex items-center justify-between rounded-md px-1.5 py-0.5 text-xs ${isOverBudget ? 'bg-rose-50 border border-rose-200' : ''}">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full" style="background:${color}"></span>
                                    <div class="min-w-0">
                                        <div class="truncate ${isOverBudget ? 'text-rose-700 font-semibold' : 'text-slate-600'}">${row.name ?? '-'}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold ${isOverBudget ? 'text-rose-700' : 'text-slate-700'}">${Math.round(share)}% ${isFatOrSuperAdmin ? '| ' + Math.round(percentOfDept) + '%' : ''}</div>
                                </div>
                            </div>
                        `;
                    }).join('');
                    // update standard percentage badge (sum allocated vs deptAllocated)
                    try {
                        const stdBadge = document.querySelector('.js-realisasi-std-percent');
                        if (stdBadge) {
                            const hasRatio = categories.some(c => c && (c.budget_ratio_percent != null));
                            let stdPercent = 0;
                            if (hasRatio) {
                                stdPercent = categories.reduce((s, it) => s + (Number(it?.budget_ratio_percent || 0)), 0);
                            } else {
                                stdPercent = deptAllocated > 0 ? (totalAllocated / deptAllocated) * 100 : 0;
                            }
                            stdBadge.textContent = Math.round(stdPercent) + '%';
                        }
                    } catch (e) {
                        // ignore
                    }
                }
            }

            function openRealisasiPopup(deptId) {
                if (window.DISABLE_REALISASI_POPUP) {
                    console.debug('Realisasi popup disabled by global flag');
                    return;
                }
                const data = departmentPopupData?.[deptId];
                if (!data) return;

                const deptBasePath = "{{ url('fat/departments') }}";
                const ratioFormEl = document.getElementById('realisasi-ratio-form');
                const ratioInputEl = document.getElementById('realisasi-ratio-input');
                const overrideFormEl = document.getElementById('realisasi-override-form');
                const overrideMonthEl = document.getElementById('realisasi-override-month');

                if (ratioFormEl) {
                    ratioFormEl.action = `${deptBasePath}/${data.department_id}/ratio`;
                }
                if (ratioInputEl) {
                    ratioInputEl.value = Number(data.department_ratio || 0).toFixed(2);
                }
                if (overrideFormEl) {
                    overrideFormEl.action = `${deptBasePath}/${data.department_id}/monthly-budget`;
                }
                if (overrideMonthEl) {
                    overrideMonthEl.value = data.override_month || '';
                }

                document.getElementById('realisasi-title').textContent = 'Detail Realisasi - ' + (data.department_name ?? 'Departemen');
                document.getElementById('realisasi-allocated').textContent = formatCurrency(data.allocated);
                document.getElementById('realisasi-used').textContent = formatCurrency(data.used);
                document.getElementById('realisasi-remaining').textContent = formatCurrency(data.remaining);
                document.getElementById('realisasi-utilization').textContent = Number(data.utilization || 0).toFixed(2).replace('.', ',') + '%';

                const statusEl = document.getElementById('realisasi-status');
                const isOver = String(data.status_label || '').toLowerCase().includes('over');
                statusEl.textContent = data.status_label || '-';
                statusEl.className = 'rounded-full px-3 py-1 text-xs font-semibold ' + (isOver ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700');

                const rawUtil = Number(data.utilization || 0);
                const progressEl = document.getElementById('realisasi-progress');
                progressEl.style.width = Math.max(0, Math.min(100, rawUtil)) + '%';
                progressEl.className = 'h-2 rounded-full ' + (rawUtil > 100 ? 'bg-rose-600' : (rawUtil <= 20 ? 'bg-amber-500' : (rawUtil <= 80 ? 'bg-emerald-500' : 'bg-amber-500')));

                const categories = Array.isArray(data.categories) ? data.categories : [];
                document.getElementById('realisasi-top-category').textContent = data.top_category || '-';
                renderRealisasiLineChart(categories);
                renderRealisasiDonut(categories, Number(data.allocated || 0));

                const alerts = Array.isArray(data.alerts) ? data.alerts : [];
                const alertsEl = document.getElementById('realisasi-alerts');
                alertsEl.innerHTML = alerts.length
                    ? alerts.map((a) => {
                        const rawUtil = Number(a.utilization || 0);
                        const isOver = rawUtil >= 100;
                        const bgColor = isOver ? 'bg-rose-50 border-rose-200' : 'bg-amber-50 border-amber-200';
                        const textColor = isOver ? 'text-rose-700' : 'text-amber-700';
                        const labelText = isOver ? 'Overbudget' : 'Kritis (>90%)';

                        return `
                        <div class="rounded-lg border ${bgColor} p-3">
                            <div class="flex items-start gap-3">
                                <div class="pt-0.5">
                                    <span class="inline-block h-3.5 w-3.5 rounded-full" style="background:${isOver ? '#FDE8E8' : '#FFF7ED'}"></span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold ${textColor}">${a.name ?? '-'}</p>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-sm ${isOver ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800'}">${labelText}</span>
                                    </div>
                                    <p class="text-sm ${textColor} mt-1">Terpakai ${formatCurrency(a.used)} dari pagu ${formatCurrency(a.allocated)}</p>
                                </div>
                            </div>
                        </div>
                        `;
                    }).join('')
                    : `
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                            <div class="inline-flex items-center gap-3">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-emerald-100 text-emerald-700 font-semibold">✓</span>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-700">Aman</p>
                                    <p class="text-sm text-emerald-600 mt-1">Semua pengeluaran kategori masih sesuai pagu.</p>
                                </div>
                            </div>
                        </div>
                    `;

                document.getElementById('modal-realisasi-dept')?.classList.remove('hidden');
            }

            function toggleDeptRow(deptId) {
                const detail = document.getElementById('detail-dept-' + deptId);
                const arrow = document.getElementById('arrow-dept-' + deptId);
                if (detail.classList.contains('hidden')) {
                    detail.classList.remove('hidden');
                    arrow.style.transform = 'rotate(90deg)';
                } else {
                    detail.classList.add('hidden');
                    arrow.style.transform = 'rotate(0deg)';
                }
            }

            function openEditCategory(id, code, name, ratio, desc, deptId) {
                const basePath = "{{ url('monitoring/categories') }}".replace(/^https?:\/\/[^\/]+/, '');
                document.getElementById('form-edit-category').action = window.location.origin + basePath + '/' + id;
                document.getElementById('edit-cat-code-mon').value = code;
                document.getElementById('edit-cat-name-mon').value = name;
                document.getElementById('edit-cat-ratio-mon').value = ratio;
                document.getElementById('edit-cat-desc-mon').value = desc;
                document.getElementById('modal-edit-category').classList.remove('hidden');
            }

            function openExpenseModal(id, name, deptId) {
                document.getElementById('expense-cat-id').value = id;
                document.getElementById('expense-cat-name').textContent = 'Kategori: ' + name;
                document.getElementById('modal-add-expense').classList.remove('hidden');
            }

            async function deleteCategoryMonitoring(catId, catName) {
                if (!confirm('Hapus kategori ' + catName + '?')) return;
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('_method', 'DELETE');
                try {
                    const basePath = "{{ url('monitoring/categories') }}".replace(/^https?:\/\/[^\/]+/, '');
                    const targetUrl = window.location.origin + basePath + '/' + catId;

                    const res = await fetch(targetUrl, {
                        method: 'POST',
                        body: formData
                    });
                    if (res.status < 400) {
                        location.reload();
                    } else {
                        alert('Gagal menghapus kategori. Mungkin sudah ada pengeluaran.');
                    }
                } catch(e) {
                    console.error('Fetch error:', e);
                    alert('Gagal menghubungi server. Error: ' + e.message);
                }
            }
            
            // Close the manager department dropdown if clicked outside
            document.addEventListener('click', function(event) {
                const wrapper = document.getElementById('manager-dept-dropdown-wrapper');
                const menu = document.getElementById('manager-dept-menu');
                if (wrapper && menu && !wrapper.contains(event.target)) {
                    menu.classList.add('hidden');
                }
            });
        </script>
        <style>
            .animate-in {
                animation: popIn 0.2s ease-out;
            }
            @keyframes popIn {
                from { opacity: 0; transform: scale(0.95); }
                to { opacity: 1; transform: scale(1); }
            }
        </style>
    @endpush
@endsection