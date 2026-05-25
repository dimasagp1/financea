@extends('layouts.dashboard', ['title' => 'Dashboard Manager'])

@section('content')
    <div class="mx-auto max-w-7xl">
    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Dashboard Manager</h2>
            <p class="mt-1 text-sm text-slate-500">
                Monitoring anggaran departemen yang ditugaskan untuk FY{{ $activeFiscalYear?->year ?? now()->year }}.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('monitoring.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <span class="text-slate-500">📊</span>
                <span>Monitoring Budget</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-colors">
            <p class="text-xs uppercase tracking-wide text-slate-500">Departemen Terpantau</p>
            <div class="mt-1 flex items-center justify-between gap-3">
                <p class="text-3xl font-bold text-slate-900">{{ $departments->count() }}</p>
                <div class="h-10 w-10 flex items-center justify-center rounded-full bg-indigo-50 text-indigo-600 font-bold text-lg">🏢</div>
            </div>
            <p class="mt-1 text-xs text-slate-500">Total departemen yang Anda kelola</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total Pagu Bulan Ini ({{ $currentMonthName }})</p>
            @php
                $totalPaguBulanIni = 0;
                $totalRealisasiBulanIni = 0;
                if ($currentMonthBudget) {
                    foreach($departments as $d) {
                        $pagu = ($currentMonthBudget->amount * $d->budget_ratio_percent) / 100;
                        $realisasi = $departmentExpenses->get($d->id, 0);
                        $totalPaguBulanIni += $pagu;
                        $totalRealisasiBulanIni += $realisasi;
                    }
                }
            @endphp
            @if($currentMonthBudget)
                <p class="mt-1 text-3xl font-bold text-indigo-600">Rp {{ number_format($totalPaguBulanIni, 0, ',', '.') }}</p>
                <div class="mt-2 flex flex-col gap-1">
                    <p class="text-xs text-slate-500 font-medium">Akumulasi pagu dari seluruh departemen Anda.</p>
                </div>
            @else
                <p class="mt-1 text-2xl font-bold text-slate-400 italic">Belum Diatur</p>
                <div class="mt-2 flex flex-col gap-1">
                    <p class="text-xs text-rose-500 font-medium">Budget Global belum diset.</p>
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">✓</div>
                <div class="flex-1">
                    <p class="text-xs uppercase tracking-wide text-emerald-700">Mode View Only</p>
                    <p class="mt-2 text-sm font-semibold text-emerald-800">Akses Terbatas</p>
                    <p class="mt-1 text-sm text-emerald-700">Anda dapat melihat rincian pengeluaran tanpa melakukan perubahan data.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-300 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <h3 class="text-lg font-semibold text-slate-900">Daftar Departemen</h3>

            <form method="GET" class="w-full md:w-auto">
                <div class="flex items-center gap-2">
                    <select name="month" class="w-full md:w-32 rounded-xl border-slate-200 text-sm py-2 cursor-pointer" onchange="this.form.submit()">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                    <select name="year" class="w-full md:w-28 rounded-xl border-slate-200 text-sm py-2 cursor-pointer" onchange="this.form.submit()">
                        @if($activeFiscalYear)
                            <option value="{{ $activeFiscalYear->year }}" selected>{{ $activeFiscalYear->year }}</option>
                        @endif
                    </select>
                    <input
                        type="search"
                        name="search"
                        value="{{ $filters['search'] }}"
                        placeholder="Cari departemen..."
                        class="w-full md:w-64 rounded-xl border-slate-200 text-sm py-2"
                    >
                    <button class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cari</button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-300 bg-slate-100 text-left text-[11px] uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3">Nama Departemen</th>
                        <th class="px-5 py-3 text-right">Pagu Bulan Ini</th>
                        <th class="px-5 py-3 text-right">Realisasi Pengeluaran</th>
                        <th class="px-5 py-3">Utilisasi</th>
                        <th class="px-5 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $index => $department)
                        @php
                            $monthlyOmzet = (float) ($currentMonthBudget->amount ?? 0);
                            $monthlyCeiling = ($monthlyOmzet * (float) $department->budget_ratio_percent) / 100;
                            $monthlyExpense = $departmentExpenses->get($department->id, 0);
                            $isOverBudget = $monthlyExpense > $monthlyCeiling;
                            
                            $detailRowId = 'dept-detail-' . $department->id;
                        @endphp
                        <tr class="border-b border-slate-200 last:border-b-0 hover:bg-slate-50/70 cursor-pointer" data-dept-click-row="{{ $detailRowId }}">
                            <td class="px-5 py-3">
                                <button type="button" data-dept-toggle="{{ $detailRowId }}" aria-expanded="false" class="w-full text-left">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold uppercase text-indigo-700">{{ strtoupper(substr($department->name, 0, 2)) }}</span>
                                        <div class="font-semibold text-slate-800">{{ $department->name }}</div>
                                        </div>
                                        <span data-dept-chevron class="inline-flex h-5 w-5 items-center justify-center text-slate-400 transition-transform duration-200">▾</span>
                                    </div>
                                    <div class="text-xs text-slate-500">{{ $department->code }} · {{ $department->odoo_analytic_id ?: '-' }}</div>
                                </button>
                            </td>
                            <td class="px-5 py-3 text-right font-mono text-slate-600 font-medium">
                                Rp {{ number_format($monthlyCeiling, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono font-medium {{ $isOverBudget ? 'text-rose-600' : 'text-emerald-600' }}">
                                Rp {{ number_format($monthlyExpense, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3">
                                @php
                                    $utilization = $monthlyCeiling > 0 ? ($monthlyExpense / $monthlyCeiling) * 100 : 0;
                                    $utilColor = $isOverBudget ? 'bg-rose-500' : ($utilization <= 80 ? 'bg-emerald-500' : 'bg-amber-500');
                                @endphp
                                <div class="flex items-center gap-2">
                                    <div class="w-full bg-slate-200 rounded-full h-2 min-w-[80px]">
                                        <div class="{{ $utilColor }} h-2 rounded-full" style="width: {{ min($utilization, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-slate-600">{{ number_format($utilization, 1) }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex rounded-lg px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ $isOverBudget ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $isOverBudget ? 'Over Budget' : 'On Budget' }}
                                </span>
                            </td>
                        </tr>

                        <tr id="{{ $detailRowId }}" data-dept-detail-row class="hidden border-b border-slate-200 bg-slate-50/70">
                            <td colspan="5" class="px-5 py-4">
                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                                        <h4 class="text-sm font-semibold text-slate-800">Detail Departemen</h4>
                                        <dl class="mt-3 space-y-2 text-sm">
                                            <div class="flex justify-between gap-3">
                                                <dt class="text-slate-500">Kode</dt>
                                                <dd class="font-medium text-slate-800">{{ $department->code }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3">
                                                <dt class="text-slate-500">Odoo Analytic</dt>
                                                <dd class="font-medium text-slate-800">{{ $department->odoo_analytic_id ?: '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3">
                                                <dt class="text-slate-500">Kepala Departemen</dt>
                                                <dd class="font-medium text-slate-800">{{ $department->head_name ?: '-' }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3">
                                                <dt class="text-slate-500">Status</dt>
                                                <dd class="font-medium {{ $department->is_active ? 'text-emerald-700' : 'text-slate-600' }}">{{ $department->is_active ? 'Aktif' : 'Nonaktif' }}</dd>
                                            </div>
                                            <div class="pt-1">
                                                <dt class="text-slate-500">Deskripsi</dt>
                                                <dd class="mt-1 text-slate-700">{{ $department->description ?: '-' }}</dd>
                                            </div>
                                        </dl>
                                        <div class="mt-4 pt-4 border-t border-slate-100">
                                            <button type="button" onclick="openRealisasiPopup({{ $department->id }})" class="w-full rounded-lg bg-indigo-600 py-2 text-xs font-bold text-white hover:bg-indigo-700 transition">Lihat Detail Realisasi & Grafik</button>
                                        </div>
                                    </div>

                                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="text-sm font-semibold text-slate-800">Rincian Pengeluaran Kategori</h4>
                                            <span class="inline-flex items-center justify-center rounded-md bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                                {{ $department->costCategories->count() }} Kategori
                                            </span>
                                        </div>
                                        
                                        <div class="max-h-60 overflow-y-auto pr-3">
                                            <table class="min-w-full text-xs">
                                                <thead class="sticky top-0 bg-white shadow-sm">
                                                    <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wide text-[10px]">
                                                        <th class="py-2 pr-2 text-left font-semibold">Kategori</th>
                                                        <th class="py-2 px-2 text-right font-semibold">Pagu</th>
                                                        <th class="py-2 px-2 text-right font-semibold">Realisasi</th>
                                                        <th class="py-2 pl-2 text-center font-semibold">Utilisasi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $deptCategoriesData = $departmentPopupData[$department->id]['categories'] ?? [];
                                                    @endphp
                                                    @forelse($deptCategoriesData as $catData)
                                                        @php
                                                            $catUtil = $catData['utilization'] ?? 0;
                                                            $catIsOver = $catUtil > 100;
                                                            $catBarColor = $catIsOver ? 'bg-rose-500' : ($catUtil <= 80 ? 'bg-emerald-500' : 'bg-amber-400');
                                                            $catTextColor = $catIsOver ? 'text-rose-600' : ($catUtil <= 80 ? 'text-emerald-600' : 'text-amber-600');
                                                        @endphp
                                                        <tr class="border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition-colors">
                                                            <td class="py-2 pr-2">
                                                                <div class="font-medium text-slate-700 truncate max-w-[120px]" title="{{ $catData['name'] }}">{{ $catData['name'] }}</div>
                                                                <div class="text-[9px] text-slate-400 font-mono">{{ $catData['code'] }}</div>
                                                            </td>
                                                            <td class="py-2 px-2 text-right font-mono text-slate-600">
                                                                Rp {{ number_format($catData['allocated'], 0, ',', '.') }}
                                                            </td>
                                                            <td class="py-2 px-2 text-right font-mono font-medium {{ $catTextColor }}">
                                                                Rp {{ number_format($catData['used'], 0, ',', '.') }}
                                                            </td>
                                                            <td class="py-2 pl-2">
                                                                <div class="flex flex-col items-center gap-1">
                                                                    <div class="text-[9px] font-bold {{ $catTextColor }}">{{ number_format($catUtil, 1, ',', '.') }}%</div>
                                                                    <div class="w-full bg-slate-200 rounded-full h-1.5 min-w-[50px]">
                                                                        <div class="{{ $catBarColor }} h-1.5 rounded-full" style="width: {{ min($catUtil, 100) }}%"></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="py-4 text-center text-slate-500 bg-slate-50 rounded-lg italic">
                                                                Tidak ada data kategori.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-500">Belum ada data departemen yang ditugaskan kepada Anda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>

    {{-- MODAL REALISASI --}}
    <div id="modal-realisasi-dept" class="hidden fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeRealisasiPopup()"></div>
        <div class="relative flex w-[750px] max-w-[90vw] max-h-[90vh] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-2xl">
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
                            <h4 class="text-lg font-bold text-slate-900">Ringkasan Anggaran</h4>
                            <span id="realisasi-status" class="rounded-full px-3 py-1 text-xs font-semibold bg-slate-100 text-slate-700">-</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                                <p class="text-[10px] uppercase tracking-wide text-slate-500">Alokasi</p>
                                <p id="realisasi-allocated" class="text-sm font-bold text-slate-900 mt-1">Rp 0</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                                <p class="text-[10px] uppercase tracking-wide text-slate-500">Pengeluaran</p>
                                <p id="realisasi-used" class="text-sm font-bold text-slate-900 mt-1">Rp 0</p>
                            </div>
                            <div class="rounded-xl bg-blue-50 border border-blue-100 p-3">
                                <p class="text-[10px] uppercase tracking-wide text-blue-500">Sisa</p>
                                <p id="realisasi-remaining" class="text-sm font-bold text-blue-600 mt-1">Rp 0</p>
                            </div>
                        </div>
                        <div class="mb-2 flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-700">Utilisasi</span>
                            <span id="realisasi-utilization" class="font-semibold text-slate-900">0%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div id="realisasi-progress" class="h-2 rounded-full bg-amber-500" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
                        <h4 class="text-sm font-semibold text-slate-900 mb-3">Peringatan Anggaran</h4>
                        <div id="realisasi-alerts" class="space-y-2"></div>
                    </div>
                </div>

                <div class="space-y-3 h-full flex flex-col">
                    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm flex-1">
                        <h4 class="text-lg font-semibold text-slate-900 mb-4 text-center">Rincian Pengeluaran</h4>
                        <div class="mx-auto max-w-[124px]">
                            <div class="mx-auto flex h-[124px] w-[124px] flex-col items-center justify-center relative">
                                <canvas id="realisasi-donut-chart"></canvas>
                            </div>
                        </div>
                        <div class="text-center mb-4 mt-6">
                            <p class="text-[10px] text-slate-400">Pengeluaran Tertinggi</p>
                            <p id="realisasi-top-category" class="font-semibold text-slate-800">-</p>
                        </div>
                        <div id="realisasi-donut-legend" class="space-y-1 text-sm"></div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const departmentPopupData = @json($departmentPopupData ?? []);
            let realisasiDonutChart = null;

            const formatCurrency = (value) => {
                const number = Number(value || 0);
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
            };

            function renderRealisasiDonut(categories, deptAllocated = 0) {
                const canvas = document.getElementById('realisasi-donut-chart');
                const legendHolder = document.getElementById('realisasi-donut-legend');
                if (!canvas) return;

                if (realisasiDonutChart) {
                    realisasiDonutChart.destroy();
                }

                const nonZeroCategories = categories.filter((category) => Number(category.used || 0) > 0);
                const palette = ['#6366F1', '#3B82F6', '#06B6D4', '#10B981', '#F59E0B', '#F97316', '#EF4444', '#8B5CF6'];

                if (nonZeroCategories.length === 0) {
                    realisasiDonutChart = new Chart(canvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: ['Belum ada'],
                            datasets: [{
                                data: [1],
                                backgroundColor: ['#CBD5E1'],
                                borderWidth: 0,
                            }]
                        },
                        options: { cutout: '65%', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { enabled: false } } }
                    });
                    if (legendHolder) legendHolder.innerHTML = '<p class="text-center text-xs text-slate-400">Belum ada realisasi</p>';
                    return;
                }

                const total = nonZeroCategories.reduce((sum, category) => sum + Number(category.used || 0), 0);
                
                realisasiDonutChart = new Chart(canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: nonZeroCategories.map(c => c.name || '-'),
                        datasets: [{
                            data: nonZeroCategories.map(c => Number(c.used || 0)),
                            backgroundColor: nonZeroCategories.map((_, i) => palette[i % palette.length]),
                            borderWidth: 2,
                            borderColor: '#ffffff',
                        }]
                    },
                    options: {
                        cutout: '65%',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return (context.label || '') + ': ' + formatCurrency(context.raw);
                                    }
                                }
                            }
                        }
                    }
                });

                if (legendHolder) {
                    legendHolder.innerHTML = categories.map((category) => {
                        const index = nonZeroCategories.findIndex((row) => row.name === category.name);
                        const color = index >= 0 ? palette[index % palette.length] : '#94A3B8';
                        const share = total > 0 ? ((Number(category.used || 0) / total) * 100) : 0;
                        return `
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full" style="background:${color}"></span>
                                    <div class="truncate text-slate-600">${category.name ?? '-'}</div>
                                </div>
                                <div class="font-semibold text-slate-700">${Math.round(share)}%</div>
                            </div>
                        `;
                    }).join('');
                }
            }

            function closeRealisasiPopup() {
                document.getElementById('modal-realisasi-dept')?.classList.add('hidden');
            }

            function openRealisasiPopup(departmentId) {
                const data = departmentPopupData?.[departmentId];
                if (!data) return;

                document.getElementById('realisasi-title').textContent = 'Detail Realisasi - ' + (data.department_name ?? 'Departemen');
                document.getElementById('realisasi-allocated').textContent = formatCurrency(data.allocated);
                document.getElementById('realisasi-used').textContent = formatCurrency(data.used);
                document.getElementById('realisasi-remaining').textContent = formatCurrency(data.remaining);
                document.getElementById('realisasi-utilization').textContent = (Number(data.utilization || 0)).toFixed(2).replace('.', ',') + '%';

                const utilizationRaw = Number(data.utilization || 0);
                const progressEl = document.getElementById('realisasi-progress');
                progressEl.style.width = Math.min(100, utilizationRaw) + '%';
                progressEl.className = 'h-2 rounded-full ' + (utilizationRaw > 100 ? 'bg-rose-600' : (utilizationRaw <= 80 ? 'bg-emerald-500' : 'bg-amber-500'));

                const statusEl = document.getElementById('realisasi-status');
                statusEl.textContent = data.status_label || '-';
                statusEl.className = 'rounded-full px-3 py-1 text-xs font-semibold ' + (String(data.status_label).toLowerCase().includes('over') ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700');

                document.getElementById('realisasi-top-category').textContent = data.top_category || '-';
                renderRealisasiDonut(data.categories || []);

                const alertsEl = document.getElementById('realisasi-alerts');
                const alerts = Array.isArray(data.alerts) ? data.alerts : [];
                alertsEl.innerHTML = alerts.length ? alerts.map(a => `
                    <div class="rounded-lg border ${Number(a.utilization) >= 100 ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50'} p-3">
                        <p class="text-xs font-bold ${Number(a.utilization) >= 100 ? 'text-rose-700' : 'text-amber-700'}">${a.name}</p>
                        <p class="text-xs mt-1">Pengeluaran: ${formatCurrency(a.used)} (${Math.round(a.utilization)}%)</p>
                    </div>
                `).join('') : '<p class="text-xs text-emerald-600">Anggaran kategori masih aman.</p>';

                document.getElementById('modal-realisasi-dept').classList.remove('hidden');
            }

            document.addEventListener('DOMContentLoaded', () => {
                const toggleButtons = document.querySelectorAll('[data-dept-toggle]');
                toggleButtons.forEach(btn => btn.addEventListener('click', () => {
                    const rowId = btn.getAttribute('data-dept-toggle');
                    const row = document.getElementById(rowId);
                    row.classList.toggle('hidden');
                    btn.querySelector('[data-dept-chevron]')?.classList.toggle('rotate-180');
                }));
            });
        </script>
    @endpush
@endsection
