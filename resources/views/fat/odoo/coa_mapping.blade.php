@extends('layouts.dashboard', ['title' => 'Pemetaan COA Odoo'])

@section('content')
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Pemetaan COA Odoo</h2>
            <p class="text-slate-500 mt-1">Petakan Akun COA Odoo ke Departemen dan Kategori Anggaran FAT secara manual. Satu COA bisa dipetakan ke banyak kategori.</p>
        </div>
        <div class="flex items-center gap-3 flex-shrink-0">
            {{-- Unsync button for current selected month --}}
            <form method="POST" action="{{ route('fat.odoo.unsync-month') }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan sinkronisasi dan mengosongkan data transaksi bulan {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}? Semua realisasi transaksi Odoo di bulan ini akan dihapus dari FAT.')" class="flex-shrink-0">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 font-bold px-6 py-2.5 text-sm shadow-sm transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Kosongkan Data {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}
                </button>
            </form>

            {{-- Sync button for current selected month --}}
            <form method="POST" action="{{ route('fat.odoo.sync-month') }}" class="flex-shrink-0">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold px-6 py-2.5 text-sm shadow-md shadow-indigo-200 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sinkronkan Data {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm font-medium text-rose-800">{{ session('error') }}</p>
        </div>
    @endif
    @if(session('warning'))
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm font-medium text-amber-800">{{ session('warning') }}</p>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-md shadow-slate-200/50 p-6 mb-6">
        <form method="GET" action="{{ route('fat.odoo.coa-mapping') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div class="md:col-span-2">
                <label for="search" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Cari Kode / Nama COA</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" 
                       class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all shadow-sm"
                       placeholder="Contoh: 63110160, Beban ATK...">
            </div>
            <div>
                <label for="month" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Bulan & Tahun Realisasi</label>
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
                            <label class="flex items-center gap-2.5 p-1 rounded hover:bg-slate-50 cursor-pointer text-xs text-slate-700 font-medium">
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
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2 mb-2">
                    <input type="checkbox" name="only_active" id="only_active" value="1" @checked($showOnlyWithTransactions)
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" onchange="this.form.submit()">
                    <label for="only_active" class="text-xs text-slate-600 font-semibold cursor-pointer">Hanya COA dengan Transaksi</label>
                </div>
                <div class="flex gap-2">
                    <button type="submit" 
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800 focus:outline-none transition-all shadow-sm">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search', 'month', 'coa_prefixes']) || !$showOnlyWithTransactions)
                        <a href="{{ route('fat.odoo.coa-mapping') }}" 
                           class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 focus:outline-none transition-all shadow-sm">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Workflow Info -->
    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 mb-6 flex items-start gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500 mt-0.5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>
        <div class="text-sm text-indigo-800">
            <p class="font-bold mb-1">Cara Kerja Pemetaan COA (Multi-Target)</p>
            <ol class="list-decimal list-inside space-y-1 text-indigo-700">
                <li>Pilih bulan/tahun, lalu tambahkan satu atau lebih pasangan <strong>Departemen + Kategori</strong> untuk setiap COA.</li>
                <li>Jika hanya <strong>1 pasangan</strong> → transaksi otomatis masuk ke kategori itu saat sinkronisasi.</li>
                <li>Jika <strong>lebih dari 1 pasangan</strong> → di halaman Kroscek, setiap transaksi harus dipilih secara manual masuk ke kategori mana.</li>
                <li>Setelah selesai, klik <strong class="text-indigo-900">"Sinkronkan Data [Bulan Tahun]"</strong> di atas.</li>
            </ol>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-md shadow-slate-200/50 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-5 py-4">Kode COA Odoo</th>
                        <th class="px-5 py-4">Nama COA Odoo</th>
                        <th class="px-5 py-4 text-center">Transaksi</th>
                        <th class="px-5 py-4 text-right">Total Realisasi</th>
                        <th class="px-5 py-4" style="min-width:520px">Pemetaan Kategori (Dept → Kategori)</th>
                        <th class="px-5 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 align-top">
                    @forelse($coas as $coa)
                        @php
                            $mapping = $existingMappings[$coa['id']] ?? null;
                            $targets = $mapping ? $mapping->targets : collect();
                            $targetCount = $targets->count();

                            if ($targetCount === 0) {
                                $badgeClass = 'bg-slate-50 text-slate-500 border border-slate-200';
                                $badgeText  = 'Otomatis';
                            } elseif ($targetCount === 1 && $targets->first()?->budget_category_id) {
                                $badgeClass = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                $badgeText  = 'Terpetakan';
                            } elseif ($targetCount === 1) {
                                $badgeClass = 'bg-amber-50 text-amber-700 border border-amber-200';
                                $badgeText  = 'Perlu Kategori';
                            } else {
                                $badgeClass = 'bg-violet-50 text-violet-700 border border-violet-200';
                                $badgeText  = "Multi ({$targetCount})";
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition"
                            data-coa-id="{{ $coa['id'] }}"
                            data-coa-code="{{ $coa['code'] }}"
                            data-coa-name="{{ $coa['name'] }}"
                            data-mapping-id="{{ $mapping?->id }}">

                            <td class="px-5 py-4 whitespace-nowrap text-slate-600 font-mono font-bold text-xs">
                                {{ $coa['code'] }}
                            </td>
                            <td class="px-5 py-4 text-slate-900 font-medium">
                                {{ $coa['name'] }}
                            </td>
                            <td class="px-5 py-4 text-center font-bold text-slate-600">
                                {{ $coa['transaction_count'] ?? 0 }}
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-slate-950 font-bold whitespace-nowrap text-xs">
                                Rp {{ number_format($coa['total_amount'] ?? 0, 0, ',', '.') }}
                            </td>

                            {{-- Multi-Target Mapping Column --}}
                            <td class="px-5 py-4">
                                @php
                                    $coaId = $coa['id'];
                                    $myMoveLines = array_filter($odooMoveLines ?? [], function($line) use ($coaId) {
                                        $actId = is_array($line['account_id']) ? ($line['account_id'][0] ?? null) : ($line['account_id'] ?? null);
                                        return $actId == $coaId;
                                    });
                                @endphp
                                {{-- Existing targets list --}}
                                <div class="targets-list space-y-1.5 mb-3" id="targets-{{ $coa['id'] }}">
                                    @forelse($targets as $target)
                                        <div class="target-item bg-slate-50 rounded-lg p-2 border border-slate-200"
                                             data-target-id="{{ $target->id }}">
                                            <div class="flex items-center gap-2 justify-between flex-wrap">
                                                <span class="text-xs font-semibold text-slate-700 flex-1">
                                                    <span class="text-indigo-600">{{ $target->department?->name ?? '?' }}</span>
                                                    @if($target->budgetCategory)
                                                        <span class="text-slate-400 mx-1">→</span>
                                                        <span class="text-emerald-700">[{{ $target->budgetCategory->code }}] {{ $target->budgetCategory->name }}</span>
                                                    @else
                                                        <span class="text-amber-600 ml-1">(belum ada kategori)</span>
                                                    @endif
                                                </span>
                                                <div class="flex items-center gap-1.5">
                                                    {{-- Dropdown Pilihan Transaksi --}}
                                                    @if($targetCount > 1 && count($myMoveLines) > 0)
                                                        <div class="relative inline-block text-left tx-dropdown">
                                                            <button type="button" 
                                                                    onclick="toggleTxDropdown(this)"
                                                                    class="inline-flex items-center gap-1 rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-bold text-slate-600 hover:bg-slate-50 transition shadow-xs">
                                                                + Petakan...
                                                            </button>
                                                            <div class="hidden absolute right-0 mt-1 w-72 bg-white rounded-xl border border-slate-200 shadow-xl z-50 p-3 tx-dropdown-menu">
                                                                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center justify-between border-b border-slate-100 pb-1.5">
                                                                    <span>Pilih Transaksi</span>
                                                                    <div class="flex gap-1.5">
                                                                        <button type="button" onclick="selectAllTx(this, true)" class="text-indigo-600 hover:underline">Semua</button>
                                                                        <span class="text-slate-300">|</span>
                                                                        <button type="button" onclick="selectAllTx(this, false)" class="text-slate-500 hover:underline">Clear</button>
                                                                    </div>
                                                                </div>
                                                                <div class="mb-2">
                                                                    <input type="text" 
                                                                           placeholder="Cari transaksi..." 
                                                                           class="w-full rounded border border-slate-200 px-2 py-1 text-[11px] focus:outline-none focus:border-indigo-500 tx-search-filter"
                                                                           oninput="filterTxItems(this)">
                                                                </div>
                                                                <div class="max-h-48 overflow-y-auto space-y-1.5 mb-3 pr-1 tx-items-list">
                                                                    @foreach($myMoveLines as $line)
                                                                        @php
                                                                            $lineIdStr = strval($line['id']);
                                                                            $currentMapping = $txMappings->get($lineIdStr);
                                                                            $isMappedToMe = $currentMapping && $currentMapping->odoo_coa_mapping_target_id == $target->id;
                                                                            $isMappedToOther = $currentMapping && $currentMapping->odoo_coa_mapping_target_id != $target->id;
                                                                        @endphp
                                                                        @if(!$isMappedToMe)
                                                                            <label class="flex items-start gap-2 p-1.5 rounded hover:bg-slate-50 cursor-pointer text-left text-[11px] text-slate-700 {{ $isMappedToOther ? 'opacity-50 cursor-not-allowed' : '' }}">
                                                                                <input type="checkbox" 
                                                                                       value="{{ $line['id'] }}" 
                                                                                       {{ $isMappedToOther ? 'disabled' : '' }}
                                                                                       class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 tx-checkbox">
                                                                                <span class="flex-1 leading-tight">
                                                                                    {{ $line['name'] ?: ($line['ref'] ?? 'Transaksi Odoo') }}
                                                                                    <strong class="block text-indigo-600 text-[10px] mt-0.5">
                                                                                        Rp {{ number_format(($line['debit'] ?? 0) - ($line['credit'] ?? 0), 0, ',', '.') }}
                                                                                        @if($isMappedToOther)
                                                                                             <span class="text-amber-600 ml-1">(Kategori Lain)</span>
                                                                                        @endif
                                                                                    </strong>
                                                                                </span>
                                                                            </label>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                                <div class="flex gap-2">
                                                                    <button type="button" 
                                                                            onclick="submitBatchAssign({{ $target->id }}, this)" 
                                                                            class="flex-1 rounded bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-1 text-[10px] transition">
                                                                        Simpan Terpilih
                                                                    </button>
                                                                    <button type="button" 
                                                                            onclick="closeAllTxDropdowns()" 
                                                                            class="rounded border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] text-slate-600 hover:bg-slate-100 transition">
                                                                        Batal
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <button type="button"
                                                            onclick="removeTarget({{ $target->id }}, {{ $coa['id'] }})"
                                                            class="text-rose-400 hover:text-rose-600 transition shrink-0"
                                                            title="Hapus pemetaan ini">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Selected expenditures list next to/under target category --}}
                                            @if($targetCount > 1)
                                                @php
                                                    $assignedLines = $mappedLinesByTarget[$target->id] ?? [];
                                                @endphp
                                                @if(count($assignedLines) > 0)
                                                    <div class="mt-2 pl-3 border-l-2 border-indigo-200 space-y-1">
                                                        <span class="text-[9px] uppercase font-bold text-indigo-500 tracking-wider block">Pengeluaran Terpilih:</span>
                                                        @foreach($assignedLines as $line)
                                                            <div class="flex items-center justify-between text-[11px] text-slate-600 bg-white rounded border border-slate-100 p-1 px-1.5 shadow-xs group">
                                                                <span class="truncate font-medium flex-1 max-w-[160px]" title="{{ $line['name'] }} ({{ $line['ref'] ?? '-' }})">
                                                                    {{ $line['name'] ?: ($line['ref'] ?? 'Transaksi Odoo') }}
                                                                </span>
                                                                <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                                    <span class="font-mono font-bold text-indigo-600 text-[10px]">
                                                                        Rp {{ number_format(($line['debit'] ?? 0) - ($line['credit'] ?? 0), 0, ',', '.') }}
                                                                    </span>
                                                                    <button type="button"
                                                                            onclick="unassignTransaction('{{ $line['id'] }}')"
                                                                            class="text-slate-300 hover:text-rose-500 transition-colors"
                                                                            title="Batalkan pemetaan transaksi ini">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                        </svg>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="mt-1 pl-3 text-[10px] text-slate-400 italic">
                                                        Belum ada pengeluaran terpilih
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @empty
                                        <p class="text-xs text-slate-400 italic empty-msg">Belum ada pemetaan — akan menggunakan pencocokan otomatis.</p>
                                    @endforelse
                                </div>

                                {{-- Unassigned Transactions Block --}}
                                @if($targetCount > 1)
                                    @php
                                        $unassignedLines = $unassignedLinesByCoa[$coa['id']] ?? [];
                                    @endphp
                                    @if(count($unassignedLines) > 0)
                                        <div class="mt-3 bg-amber-50/40 rounded-lg p-2 border border-amber-200/50 mb-3">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <span class="text-[10px] uppercase font-extrabold text-amber-700 tracking-wider flex items-center gap-1">
                                                    ⚠️ Belum Dipetakan ({{ count($unassignedLines) }})
                                                </span>
                                                <a href="{{ route('fat.odoo.croscheck') }}" class="text-[9px] text-indigo-600 hover:text-indigo-800 font-bold transition">
                                                    Kroscek Odoo &rarr;
                                                </a>
                                            </div>
                                            <div class="space-y-1 max-h-32 overflow-y-auto pr-0.5">
                                                @foreach($unassignedLines as $line)
                                                    <div class="flex items-center justify-between text-[11px] text-slate-700 bg-white rounded border border-amber-100/70 p-1 px-1.5 shadow-xs">
                                                        <span class="truncate font-medium max-w-[200px]" title="{{ $line['name'] }} ({{ $line['ref'] ?? '-' }})">
                                                            {{ $line['name'] ?: ($line['ref'] ?? 'Transaksi Odoo') }}
                                                        </span>
                                                        <span class="font-mono font-bold text-amber-700 ml-2 text-[10px] shrink-0">
                                                            Rp {{ number_format(($line['debit'] ?? 0) - ($line['credit'] ?? 0), 0, ',', '.') }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endif

                                {{-- Add Target Form --}}
                                <div class="add-target-form flex flex-wrap gap-2 items-end">
                                    {{-- Custom Searchable Department Select --}}
                                    <div class="relative custom-search-select dept-select-wrapper" style="min-width:180px">
                                        <input type="hidden" class="dept-select" value="">
                                        <div class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-800 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition-all shadow-sm flex items-center justify-between cursor-pointer search-trigger">
                                            <span class="selected-label truncate">-- Pilih Dept --</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                        <div class="hidden absolute left-0 right-0 mt-1 bg-white rounded-lg border border-slate-200 shadow-xl z-50 options-list">
                                            <div class="p-1.5 border-b border-slate-100">
                                                <input type="text" placeholder="Cari departemen..." class="w-full rounded border border-slate-200 px-2 py-1 text-[11px] focus:outline-none focus:border-indigo-500 search-filter">
                                            </div>
                                            <div class="options-items py-1 max-h-48 overflow-y-auto">
                                                <div class="option-item px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 cursor-pointer transition-colors font-semibold border-b border-slate-50" data-value="">
                                                    -- Pilih Dept --
                                                </div>
                                                @foreach($departments as $dept)
                                                    <div class="option-item px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 cursor-pointer transition-colors" data-value="{{ $dept->id }}">
                                                        {{ $dept->name }}
                                                     </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Custom Searchable Category Select --}}
                                    <div class="relative custom-search-select cat-select-wrapper" style="min-width:220px">
                                        <input type="hidden" class="cat-select" value="">
                                        <div class="w-full rounded-lg border border-slate-300 bg-slate-50 text-slate-400 px-2 py-1.5 text-xs focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition-all shadow-sm flex items-center justify-between cursor-not-allowed search-trigger" data-disabled="true">
                                            <span class="selected-label truncate">-- Pilih Kategori --</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                        <div class="hidden absolute left-0 right-0 mt-1 bg-white rounded-lg border border-slate-200 shadow-xl z-50 options-list">
                                            <div class="p-1.5 border-b border-slate-100">
                                                <input type="text" placeholder="Cari kategori..." class="w-full rounded border border-slate-200 px-2 py-1 text-[11px] focus:outline-none focus:border-indigo-500 search-filter">
                                            </div>
                                            <div class="options-items py-1 max-h-48 overflow-y-auto">
                                                <div class="option-item px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 cursor-pointer transition-colors font-semibold border-b border-slate-50" data-value="">
                                                    -- Pilih Kategori --
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button"
                                            onclick="addTarget(this)"
                                            class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold px-3 py-1.5 text-xs transition shadow-sm shrink-0">
                                        + Tambah
                                    </button>
                                </div>
                            </td>

                            {{-- Status Badge --}}
                            <td class="px-5 py-4 text-center">
                                <span class="status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $badgeClass }}">
                                    {{ $badgeText }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <div class="max-w-md mx-auto flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Tidak Ada Akun COA Odoo</h3>
                                    <p class="text-sm text-slate-400 mt-1">Tidak ditemukan akun COA dari Odoo untuk kriteria pencarian ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($coas->hasPages())
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100">
                {{ $coas->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            const categoriesByDept = @json($categoriesByDept);
            const CSRF = '{{ csrf_token() }}';
            const ADD_URL    = '{{ route("fat.odoo.coa-mapping.target.add") }}';
            const REMOVE_URL = '{{ route("fat.odoo.coa-mapping.target.remove") }}';
            const ASSIGN_TX_URL = '{{ route("fat.odoo.transaction-mapping") }}';
            const UNASSIGN_TX_URL = '{{ route("fat.odoo.transaction-mapping.remove") }}';

            // Dept dropdown change → populate category dropdown
            document.addEventListener('change', function(e) {
                if (!e.target.classList.contains('dept-select')) return;

                const wrapper = e.target.closest('.add-target-form');
                const catWrapper = wrapper.querySelector('.cat-select-wrapper');
                const catTrigger = catWrapper.querySelector('.search-trigger');
                const catInput = catWrapper.querySelector('.cat-select');
                const catLabel = catWrapper.querySelector('.selected-label');
                const catItemsContainer = catWrapper.querySelector('.options-items');
                
                const deptId = e.target.value;

                // Reset category selection
                catInput.value = '';
                catLabel.textContent = '-- Pilih Kategori --';
                
                // Clear old items (keep default option)
                catItemsContainer.innerHTML = `
                    <div class="option-item px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 cursor-pointer transition-colors font-semibold border-b border-slate-50" data-value="">
                        -- Pilih Kategori --
                    </div>
                `;

                if (deptId) {
                    // Enable category dropdown
                    catTrigger.classList.remove('bg-slate-50', 'text-slate-400', 'cursor-not-allowed');
                    catTrigger.classList.add('bg-white', 'text-slate-800', 'cursor-pointer');
                    catTrigger.removeAttribute('data-disabled');
                    
                    const cats = categoriesByDept[deptId] || [];
                    cats.forEach(cat => {
                        const item = document.createElement('div');
                        item.className = 'option-item px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 cursor-pointer transition-colors';
                        item.dataset.value = cat.id;
                        item.textContent = `[${cat.code}] ${cat.name}`;
                        catItemsContainer.appendChild(item);
                    });
                } else {
                    // Disable category dropdown
                    catTrigger.classList.add('bg-slate-50', 'text-slate-400', 'cursor-not-allowed');
                    catTrigger.classList.remove('bg-white', 'text-slate-800', 'cursor-pointer');
                    catTrigger.setAttribute('data-disabled', 'true');
                }
            });

            // Toggle dropdown open/close on trigger click
            document.addEventListener('click', function(e) {
                const trigger = e.target.closest('.search-trigger');
                if (trigger) {
                    if (trigger.getAttribute('data-disabled') === 'true') return;
                    
                    const container = trigger.closest('.custom-search-select');
                    const menu = container.querySelector('.options-list');
                    const filterInput = container.querySelector('.search-filter');
                    
                    // Close all other dropdowns first
                    document.querySelectorAll('.custom-search-select .options-list').forEach(m => {
                        if (m !== menu) m.classList.add('hidden');
                    });
                    
                    const isClosed = menu.classList.toggle('hidden');
                    if (!isClosed && filterInput) {
                        filterInput.value = '';
                        // Reset visibility of all items
                        container.querySelectorAll('.option-item').forEach(item => item.classList.remove('hidden'));
                        setTimeout(() => filterInput.focus(), 50);
                    }
                    return;
                }
                
                // Close when clicking outside
                if (!e.target.closest('.custom-search-select')) {
                    document.querySelectorAll('.custom-search-select .options-list').forEach(m => {
                        m.classList.add('hidden');
                    });
                }
            });

            // Handle option selection
            document.addEventListener('click', function(e) {
                const optionItem = e.target.closest('.custom-search-select .option-item');
                if (!optionItem) return;
                
                const container = optionItem.closest('.custom-search-select');
                const input = container.querySelector('input[type="hidden"]');
                const label = container.querySelector('.selected-label');
                const menu = container.querySelector('.options-list');
                
                const value = optionItem.dataset.value;
                const text = optionItem.textContent.trim();
                
                const oldValue = input.value;
                input.value = value;
                label.textContent = text;
                menu.classList.add('hidden');
                
                if (oldValue !== value) {
                    // Trigger change event programmatically
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            // Filter options on search input typing
            document.addEventListener('input', function(e) {
                if (!e.target.classList.contains('search-filter')) return;
                
                const searchVal = e.target.value.toLowerCase().trim();
                const container = e.target.closest('.custom-search-select');
                const items = container.querySelectorAll('.option-item');
                
                items.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    const value = item.dataset.value;
                    // Don't hide the empty option
                    if (value === "") {
                        item.classList.remove('hidden');
                    } else if (text.includes(searchVal)) {
                        item.classList.remove('hidden');
                    } else {
                        item.classList.add('hidden');
                    }
                });
            });

            function addTarget(btn) {
                const tr = btn.closest('tr');
                const deptSelect = tr.querySelector('.dept-select');
                const catSelect  = tr.querySelector('.cat-select');

                const deptId = deptSelect.value;
                if (!deptId) { alert('Pilih Departemen terlebih dahulu.'); return; }

                const catId = catSelect.value || null;

                btn.disabled = true;
                btn.textContent = 'Menyimpan...';

                fetch(ADD_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({
                        odoo_account_id:   tr.dataset.coaId,
                        odoo_account_code: tr.dataset.coaCode,
                        odoo_account_name: tr.dataset.coaName,
                        department_id:     deptId,
                        budget_category_id: catId,
                        month:             document.getElementById('month').value,
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { alert(data.message || 'Gagal menyimpan.'); return; }
                    window.location.reload();
                })
                .catch(() => alert('Terjadi kesalahan jaringan.'))
                .finally(() => { btn.disabled = false; btn.textContent = '+ Tambah'; });
            }

            function removeTarget(targetId, coaId) {
                if (!confirm('Hapus pemetaan ini?')) return;

                fetch(REMOVE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ 
                        target_id: targetId,
                        month:     document.getElementById('month').value,
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { alert('Gagal menghapus.'); return; }
                    window.location.reload();
                })
                .catch(() => alert('Terjadi kesalahan jaringan.'));
            }

            function renderTargets(tr, targets) {
                const list = tr.querySelector('.targets-list');
                list.innerHTML = '';

                if (!targets || targets.length === 0) {
                    list.innerHTML = '<p class="text-xs text-slate-400 italic empty-msg">Belum ada pemetaan — akan menggunakan pencocokan otomatis.</p>';
                } else {
                    targets.forEach(t => {
                        const catText = t.cat_name ? `<span class="text-slate-400 mx-1">→</span><span class="text-emerald-700">[${t.cat_code}] ${t.cat_name}</span>` : '<span class="text-amber-600 ml-1">(belum ada kategori)</span>';
                        list.innerHTML += `
                            <div class="target-item flex items-center gap-2 bg-slate-50 rounded-lg px-3 py-1.5 border border-slate-200" data-target-id="${t.id}">
                                <span class="text-xs font-semibold text-slate-700 flex-1">
                                    <span class="text-indigo-600">${t.dept_name}</span>
                                    ${catText}
                                </span>
                                <button type="button" onclick="removeTarget(${t.id}, ${tr.dataset.coaId})" class="text-rose-400 hover:text-rose-600 transition shrink-0" title="Hapus pemetaan ini">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>`;
                    });
                }

                updateBadge(tr, targets ? targets.length : 0);
            }

            function updateBadge(tr, count) {
                if (count === undefined) {
                    count = tr.querySelectorAll('.target-item').length;
                }
                const badge = tr.querySelector('.status-badge');
                if (!badge) return;

                if (count === 0) {
                    badge.textContent = 'Otomatis';
                    badge.className = 'status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-50 text-slate-500 border border-slate-200';
                } else if (count === 1) {
                    badge.textContent = 'Terpetakan';
                    badge.className = 'status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
                } else {
                    badge.textContent = `Multi (${count})`;
                    badge.className = 'status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-violet-50 text-violet-700 border border-violet-200';
                }
            }

            window.toggleTxDropdown = function(btn) {
                const wrapper = btn.closest('.tx-dropdown');
                const menu = wrapper.querySelector('.tx-dropdown-menu');
                const isOpen = !menu.classList.contains('hidden');
                
                closeAllTxDropdowns();
                
                if (!isOpen) {
                    menu.classList.remove('hidden');
                    
                    // Reset search input and labels visibility
                    const searchInput = menu.querySelector('.tx-search-filter');
                    if (searchInput) {
                        searchInput.value = '';
                        searchInput.focus();
                    }
                    const list = menu.querySelector('.tx-items-list');
                    if (list) {
                        list.querySelectorAll('label').forEach(label => {
                            label.classList.remove('hidden');
                        });
                    }
                }
            };

            window.filterTxItems = function(input) {
                const searchVal = input.value.toLowerCase().trim();
                const menu = input.closest('.tx-dropdown-menu');
                const list = menu.querySelector('.tx-items-list');
                if (!list) return;
                
                const labels = list.querySelectorAll('label');
                labels.forEach(label => {
                    const text = label.textContent.toLowerCase();
                    if (text.includes(searchVal)) {
                        label.classList.remove('hidden');
                    } else {
                        label.classList.add('hidden');
                    }
                });
            };

            window.closeAllTxDropdowns = function() {
                document.querySelectorAll('.tx-dropdown-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            };

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.tx-dropdown')) {
                    closeAllTxDropdowns();
                }
            });

            window.selectAllTx = function(btn, state) {
                const menu = btn.closest('.tx-dropdown-menu');
                menu.querySelectorAll('.tx-checkbox').forEach(cb => {
                    if (!cb.disabled) {
                        cb.checked = state;
                    }
                });
            };

            window.submitBatchAssign = function(targetId, btn) {
                const menu = btn.closest('.tx-dropdown-menu');
                const checkedCheckboxes = menu.querySelectorAll('.tx-checkbox:checked');
                const moveLineIds = Array.from(checkedCheckboxes).map(cb => cb.value);

                if (moveLineIds.length === 0) {
                    alert('Pilih setidaknya satu transaksi.');
                    return;
                }

                btn.disabled = true;
                btn.textContent = 'Menyimpan...';

                fetch(ASSIGN_TX_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({
                        odoo_move_line_ids: moveLineIds,
                        odoo_coa_mapping_target_id: targetId
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { alert(data.message || 'Gagal memetakan transaksi.'); return; }
                    window.location.reload();
                })
                .catch(() => alert('Terjadi kesalahan jaringan.'))
                .finally(() => { 
                    btn.disabled = false; 
                    btn.textContent = 'Simpan Terpilih'; 
                });
            };

            function unassignTransaction(moveLineId) {
                if (!confirm('Batalkan pemetaan transaksi ini?')) return;

                fetch(UNASSIGN_TX_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({
                        odoo_move_line_id: moveLineId
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { alert(data.message || 'Gagal membatalkan pemetaan.'); return; }
                    window.location.reload();
                })
                .catch(() => alert('Terjadi kesalahan jaringan.'));
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
