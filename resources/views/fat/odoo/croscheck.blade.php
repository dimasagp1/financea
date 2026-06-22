@extends('layouts.dashboard', ['title' => 'Kroscek Data Odoo'])

@php
    $odooAnalyticMap = [
        '1' => 'Finance Accounting Dept',
        '2' => 'PPIC Dept',
        '3' => 'Procurement Dept',
        '4' => 'Production Dept',
        '5' => 'HR Dept',
        '6' => 'GA Dept',
        '7' => 'QC Dept',
        '8' => 'QA Dept',
        '9' => 'RnD Dept',
        '10' => 'BoD Dept',
        '11' => 'Warehouse',
    ];
@endphp

@section('content')
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Kroscek Data Odoo</h2>
            <p class="text-slate-500 mt-1">Daftar semua transaksi realisasi pengeluaran yang berhasil diimpor dari modul
                accounting Odoo.</p>
        </div>
        <div
            class="bg-indigo-50 border border-indigo-100 rounded-2xl px-5 py-3 flex items-center gap-3 self-start md:self-auto shadow-sm">
            <div class="w-10 h-10 bg-indigo-500/10 text-indigo-600 rounded-xl flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 00-2 2h10a2 2 0 002-2v-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-wider">Total Synced</p>
                <p class="text-xl font-black text-indigo-900 leading-tight">{{ $expenses->total() }} Transaksi</p>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500 mt-0.5 shrink-0" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500 mt-0.5 shrink-0" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
            </svg>
            <p class="text-sm font-medium text-rose-800">{{ session('error') }}</p>
        </div>
    @endif
    @if(session('warning'))
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500 mt-0.5 shrink-0" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
            </svg>
            <p class="text-sm font-medium text-amber-800">{{ session('warning') }}</p>
        </div>
    @endif

    <!-- Filters Card -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-md shadow-slate-200/50 p-6 mb-8">
        <form method="GET" action="{{ route('fat.odoo.croscheck') }}"
            class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label for="search" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Cari
                    Deskripsi / Ref / COA</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all shadow-sm"
                    placeholder="Contoh: ATK, 63110160...">
            </div>
            <div>
                <label for="department_id"
                    class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Filter Departemen</label>
                <select name="department_id" id="department_id"
                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all shadow-sm">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected(request('department_id') == $dept->id)>{{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="month" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Filter
                    Bulan</label>
                <input type="month" name="month" id="month" value="{{ $month }}"
                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all shadow-sm">
            </div>
            <div class="relative" id="coa-prefix-container">
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Awalan COA (Prefix)</label>
                <button type="button" onclick="toggleCoaPrefixDropdown()" 
                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-800 text-left focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all shadow-sm flex items-center justify-between">
                    <span id="coa-prefix-label">Semua Awalan COA</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div id="coa-prefix-menu" class="hidden absolute left-0 right-0 mt-1 bg-white rounded-xl border border-slate-200 shadow-xl z-50 p-3 max-h-60 overflow-y-auto">
                    <div class="space-y-2">
                        @php
                            $selectedPrefixes = request('coa_prefixes', []);
                            if (!is_array($selectedPrefixes)) {
                                $selectedPrefixes = [$selectedPrefixes];
                            }
                        @endphp
                        @foreach([
                            '1' => '1 - Aset / Persediaan',
                            '2' => '2 - Kewajiban / Hutang',
                            '3' => '3 - Ekuitas / Modal',
                            '4' => '4 - Pendapatan',
                            '5' => '5 - HPP (Harga Pokok Penjualan)',
                            '6' => '6 - Beban / Biaya Operasional',
                            '7' => '7 - Pendapatan Lainnya',
                            '8' => '8 - Beban Lainnya'
                        ] as $val => $label)
                            <label class="flex items-center gap-2.5 p-1 rounded hover:bg-slate-50 cursor-pointer text-xs text-slate-700">
                                <input type="checkbox" name="coa_prefixes[]" value="{{ $val }}" 
                                    @checked(in_array($val, $selectedPrefixes))
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 coa-prefix-checkbox"
                                    onchange="updateCoaPrefixLabel()">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800 focus:outline-none transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'department_id', 'month', 'coa_prefixes']))
                    <a href="{{ route('fat.odoo.croscheck') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 focus:outline-none transition-all shadow-sm">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-md shadow-slate-200/50 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-6 py-4 w-12 text-center">Detail</th>
                        <th class="px-6 py-4">Akun COA Odoo (General)</th>
                        <th class="px-6 py-4 text-center">Jumlah Transaksi</th>
                        <th class="px-6 py-4 text-right">Total Pengeluaran (Odoo)</th>
                        <th class="px-6 py-4">Pemetaan FAT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 align-middle">
                    @forelse($expenses as $coa)
                        @php
                            // Check if this COA has manual mapping with targets
                            $mapping = $coaMappings[$coa['id']] ?? null;
                            $targets = $mapping ? $mapping->targets : collect();
                            $isMultiMapped = $targets->count() > 1;
                            $isSingleMapped = $targets->count() === 1;

                            $deptName = null;
                            $catName = null;
                            if ($isSingleMapped) {
                                $singleTarget = $targets->first();
                                $deptName = $singleTarget->department?->name;
                                $catName = $singleTarget->budgetCategory?->name;
                            }

                            if ($isMultiMapped) {
                                $mappingStatusText = "Multi-Pemetaan ({$targets->count()} kategori) — pilih per transaksi di kroscek";
                                $mappingStatusClass = "bg-violet-50 text-violet-700 border border-violet-200";
                            } elseif ($isSingleMapped && $catName) {
                                $mappingStatusText = "Terpetakan ke {$deptName} ({$catName})";
                                $mappingStatusClass = "bg-emerald-50 text-emerald-700 border border-emerald-200";
                            } elseif ($isSingleMapped) {
                                $mappingStatusText = "Kategori Belum Dipetakan untuk {$deptName}";
                                $mappingStatusClass = "bg-amber-50 text-amber-700 border border-amber-200";
                            } else {
                                $mappingStatusText = "Pencocokan Otomatis";
                                $mappingStatusClass = "bg-slate-50 text-slate-500 border border-slate-200";
                            }
                        @endphp
                        <!-- Header Row for COA -->
                        <tr class="hover:bg-slate-50/50 transition cursor-pointer font-medium text-slate-900"
                            onclick="toggleCoaRow('coa-details-{{ $coa['id'] }}', this)">
                            <td class="px-6 py-4 text-center">
                                <button type="button" class="focus:outline-none transition-transform duration-200 chevron-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-800 text-base">{{ $coa['name'] }}</div>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-700">
                                {{ count($coa['items']) }} Transaksi
                            </td>
                            <td class="px-6 py-4 text-right font-extrabold text-slate-950 text-base">
                                Rp {{ number_format($coa['total_amount'], 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $mappingStatusClass }}">
                                    {{ $mappingStatusText }}
                                </span>
                            </td>
                        </tr>

                        <!-- Details Sub-Table (Collapsible Row) -->
                        <tr id="coa-details-{{ $coa['id'] }}" class="hidden bg-slate-50/50">
                            <td colspan="5" class="px-8 py-4 border-t border-slate-100">
                                <div class="bg-white rounded-xl border border-slate-200/60 shadow-inner overflow-hidden my-2">
                                    <table class="min-w-full divide-y divide-slate-100 text-xs text-left">
                                        <thead>
                                            <tr class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                                                <th class="px-4 py-3">Tanggal</th>
                                                <th class="px-4 py-3">Deskripsi / Ref Odoo</th>
                                                <th class="px-4 py-3">Departemen Odoo (Analytic)</th>
                                                <th class="px-4 py-3 text-right">Nominal</th>
                                                <th class="px-4 py-3">Status di FAT</th>
                                                <th class="px-4 py-3 text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 font-medium">
                                            @foreach($coa['items'] as $odooData)
                                                @php
                                                    $date = $odooData['date'] ?? null;

                                                    // Resolve Analytic Account
                                                    $analyticId = null;
                                                    $analyticName = 'N/A';
                                                    $analyticDistribution = $odooData['analytic_distribution'] ?? null;
                                                    if (is_string($analyticDistribution)) {
                                                        $analyticDistribution = json_decode($analyticDistribution, true);
                                                    }
                                                    if (is_array($analyticDistribution) && !empty($analyticDistribution)) {
                                                        $keys = array_keys($analyticDistribution);
                                                        $analyticId = $keys[0] ?? null;
                                                        $analyticName = $odooAnalyticMap[$analyticId] ?? "Analytic ID: #{$analyticId}";
                                                    }

                                                    // Local expense status checking
                                                    $localExpense = $existingLocalExpenses[$odooData['id']] ?? null;

                                                    $statusText = '';
                                                    $statusClass = '';
                                                    $statusDescription = '';

                                                    $accountCodeStr = $coa['code'];

                                                    if ($localExpense) {
                                                        $statusText = 'Terimpor';
                                                        $statusClass = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                                        $statusDescription = $mapping && $mapping->department_id
                                                            ? 'Terimpor via Pemetaan COA manual.'
                                                            : 'Transaksi terpetakan & masuk ke FAT.';
                                                    } else {
                                                        if ($mapping && $mapping->department_id) {
                                                            if ($mapping->budget_category_id) {
                                                                $statusText = 'Belum Sinkron';
                                                                $statusClass = 'bg-slate-50 text-slate-600 border border-slate-200';
                                                                $statusDescription = 'Memenuhi syarat pemetaan manual tapi belum disinkronkan.';
                                                            } else {
                                                                $statusText = 'Dilewati';
                                                                $statusClass = 'bg-amber-50 text-amber-700 border border-amber-200';
                                                                $statusDescription = 'Kategori anggaran belum dipetakan secara manual.';
                                                            }
                                                        } else {
                                                            // Fallback / Auto-matching reasons
                                                            if (!$analyticId) {
                                                                $statusText = 'Dilewati';
                                                                $statusClass = 'bg-rose-50 text-rose-700 border border-rose-200';
                                                                $statusDescription = 'Tidak ada Analytic Account di Odoo.';
                                                            } elseif (!in_array((string) $analyticId, $localAnalyticIds)) {
                                                                $statusText = 'Dilewati';
                                                                $statusClass = 'bg-amber-50 text-amber-700 border border-amber-200';
                                                                $statusDescription = "Analytic ID #{$analyticId} tidak terdaftar di FAT.";
                                                            } elseif (!$accountCodeStr || !in_array($accountCodeStr, $localCategoryCodes)) {
                                                                $statusText = 'Dilewati';
                                                                $statusClass = 'bg-amber-50 text-amber-700 border border-amber-200';
                                                                $statusDescription = "Kode COA '{$accountCodeStr}' tidak terdaftar di FAT.";
                                                            } else {
                                                                $statusText = 'Belum Sinkron';
                                                                $statusClass = 'bg-slate-50 text-slate-600 border border-slate-200';
                                                                $statusDescription = 'Memenuhi syarat tapi belum disinkronkan.';
                                                            }
                                                        }
                                                    }

                                                    $description = $odooData['name'] ?? '-';
                                                    $reference = $odooData['ref'] ?? null;
                                                    $amount = (float) (($odooData['debit'] ?? 0) - ($odooData['credit'] ?? 0));

                                                    // Check per-transaction assignment (for multi-mapped COAs)
                                                    $txMapping = $txMappings[$odooData['id']] ?? null;
                                                    $txAssigned = $txMapping !== null;
                                                @endphp
                                                <tr class="hover:bg-slate-50/60 transition">
                                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600 font-medium">
                                                        {{ $date ? \Carbon\Carbon::parse($date)->translatedFormat('d M Y') : 'N/A' }}
                                                    </td>
                                                    <td class="px-4 py-3 max-w-xs truncate">
                                                        <div class="font-medium text-slate-900 truncate" title="{{ $description }}">
                                                            {{ $description }}
                                                        </div>
                                                        @if($reference)
                                                            <span class="text-[10px] text-slate-400 font-mono">Ref:
                                                                {{ $reference }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap">
                                                        <span
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700">
                                                            {{ $analyticName }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-right font-bold text-slate-950">
                                                        Rp {{ number_format($amount, 0, ',', '.') }}
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap">
                                                        <div class="inline-flex flex-col">
                                                            <span
                                                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $statusClass }} self-start">
                                                                {{ $statusText }}
                                                            </span>
                                                            <span
                                                                class="text-[9px] text-slate-400 mt-0.5 max-w-[180px] leading-snug">{{ $statusDescription }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                                        <div class="flex items-center gap-1 justify-center">
                                                            @if($isMultiMapped && !$localExpense)
                                                                {{-- Multi-mapped: show target assignment dropdown --}}
                                                                <select
                                                                    class="tx-target-select rounded border border-violet-300 bg-violet-50 px-2 py-1 text-[10px] text-violet-800 focus:outline-none"
                                                                    data-move-line-id="{{ $odooData['id'] }}"
                                                                    onchange="assignTransaction(this)">
                                                                    <option value="">
                                                                        {{ $txAssigned ? ($txMapping->department?->name . ' → ' . $txMapping->budgetCategory?->name) : '-- Pilih kategori --' }}
                                                                    </option>
                                                                    @foreach($targets as $tgt)
                                                                        <option value="{{ $tgt->id }}" {{ $txAssigned && $txMapping->odoo_coa_mapping_target_id == $tgt->id ? 'selected' : '' }}>
                                                                            {{ $tgt->department?->name }} →
                                                                            {{ $tgt->budgetCategory?->name ?? '(no cat)' }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                @if($txAssigned)
                                                                    <span
                                                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-violet-100 text-violet-700">✓</span>
                                                                @endif
                                                            @else
                                                                <button type="button"
                                                                    onclick="openPayloadModal({{ $odooData['id'] }}, {{ json_encode($odooData) }})"
                                                                    class="inline-flex items-center gap-1 rounded border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                                                                    JSON
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <div class="max-w-md mx-auto flex flex-col items-center justify-center">
                                    <div
                                        class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v4.586a1 1 0 01-.293.707l-2.828 2.828a1 1 0 01-.707.293H2" />
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Tidak Ada Data Transaksi di Odoo</h3>
                                    <p class="text-sm text-slate-400 mt-1">Tidak ditemukan jurnal pengeluaran di Odoo untuk
                                        kriteria filter periode ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>

    <!-- JSON Payload Modal -->
    <div id="json-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 sm:p-6"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 transition-opacity" aria-hidden="true" onclick="closePayloadModal()">
        </div>

        <!-- Modal Box (Float Centered & Flexible Size) -->
        <div
            class="relative bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all border border-slate-100 flex flex-col max-h-[85vh] w-full max-w-3xl md:w-auto md:min-w-[600px] md:max-w-4xl z-10">
            <div class="bg-slate-900 px-6 py-4 flex items-center justify-between shrink-0">
                <h3 class="text-lg font-bold text-white" id="modal-title">
                    Raw Metadata Odoo (ID: #<span id="modal-expense-id">-</span>)
                </h3>
                <button type="button" class="text-slate-400 hover:text-white transition" onclick="closePayloadModal()">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <p class="text-sm text-slate-500 mb-4">Berikut adalah raw payload XML-RPC dari record `account.move.line`
                    yang disinkronkan ke dalam sistem FAT:</p>
                <div class="bg-slate-950 rounded-2xl p-5 border border-slate-800 shadow-inner">
                    <pre id="json-code"
                        class="text-xs text-emerald-400 font-mono leading-relaxed whitespace-pre-wrap break-all"></pre>
                </div>
            </div>
            <div class="bg-slate-50 px-6 py-4 flex justify-end border-t border-slate-100 shrink-0">
                <button type="button" onclick="closePayloadModal()"
                    class="rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm px-6 py-2.5 shadow-sm transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function openPayloadModal(id, data) {
                document.getElementById('modal-expense-id').textContent = id;
                document.getElementById('json-code').textContent = JSON.stringify(data, null, 4);
                const modal = document.getElementById('json-modal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }

            function closePayloadModal() {
                const modal = document.getElementById('json-modal');
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }

            function toggleCoaRow(rowId, headerRow) {
                const detailRow = document.getElementById(rowId);
                const chevronBtn = headerRow.querySelector('.chevron-btn');

                if (detailRow.classList.contains('hidden')) {
                    detailRow.classList.remove('hidden');
                    if (chevronBtn) {
                        chevronBtn.style.transform = 'rotate(180deg)';
                    }
                } else {
                    detailRow.classList.add('hidden');
                    if (chevronBtn) {
                        chevronBtn.style.transform = 'rotate(0deg)';
                    }
                }
            }

            function assignTransaction(selectEl) {
                const targetId = selectEl.value;
                const moveLineId = selectEl.dataset.moveLineId;

                if (!targetId) return;

                selectEl.disabled = true;

                fetch('{{ route("fat.odoo.transaction-mapping") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({
                        odoo_move_line_id: String(moveLineId),
                        odoo_coa_mapping_target_id: targetId,
                    })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Gagal menyimpan assignment.');
                            selectEl.disabled = false;
                        }
                    })
                    .catch(() => {
                        alert('Terjadi kesalahan jaringan.');
                        selectEl.disabled = false;
                    });
            }

            // Custom COA Prefix Multi-Select Dropdown
            window.toggleCoaPrefixDropdown = function() {
                const menu = document.getElementById('coa-prefix-menu');
                if (menu) {
                    menu.classList.toggle('hidden');
                }
            };

            window.updateCoaPrefixLabel = function() {
                const checkboxes = document.querySelectorAll('.coa-prefix-checkbox:checked');
                const label = document.getElementById('coa-prefix-label');
                if (!label) return;

                if (checkboxes.length === 0) {
                    label.textContent = 'Semua Awalan COA';
                } else if (checkboxes.length === 1) {
                    label.textContent = checkboxes[0].parentElement.querySelector('span').textContent;
                } else {
                    label.textContent = checkboxes.length + ' Awalan Terpilih';
                }
            };

            document.addEventListener('click', function(e) {
                const container = document.getElementById('coa-prefix-container');
                const menu = document.getElementById('coa-prefix-menu');
                if (container && !container.contains(e.target) && menu) {
                    menu.classList.add('hidden');
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                updateCoaPrefixLabel();
            });
        </script>
    @endpush
@endsection