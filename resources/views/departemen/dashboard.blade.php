@extends('layouts.dashboard', ['title' => 'Dashboard Anggaran'])

@section('content')
    @php
        $activeSection = request()->string('section')->toString() ?: 'overview';
        $quarterMap = [
            1 => ['Q1', 'Jan - Mar'],
            2 => ['Q2', 'Apr - Jun'],
            3 => ['Q3', 'Jul - Sep'],
            4 => ['Q4', 'Oct - Dec'],
        ];
        $currentQuarter = (int) ceil(now()->month / 3);
        [$quarterLabel, $quarterRange] = $quarterMap[$currentQuarter] ?? ['Q1', 'Jan - Mar'];
        $statusBadgeClass = match ($summary['status']) {
            'danger' => 'bg-rose-100 text-rose-800 font-bold',
            'warning' => 'bg-amber-100 text-amber-700',
            'success' => 'bg-emerald-100 text-emerald-700',
            default => 'bg-emerald-100 text-emerald-700',
        };
        $statusBadgeText = match ($summary['status']) {
            'danger' => 'Overbudget / Kritis',
            'warning' => ($summary['utilization'] <= 20 ? 'Penyerapan Rendah' : 'Mendekati Limit'),
            'success' => 'Ideal / Aman',
            default => 'Aman',
        };
        $progressBarClass = match ($summary['status']) {
            'danger' => 'bg-rose-600',
            'warning' => 'bg-amber-500',
            default => 'bg-emerald-500',
        };
        $donutPalette = [
            'rgba(37, 99, 235, 0.95)',
            'rgba(59, 130, 246, 0.85)',
            'rgba(147, 197, 253, 0.9)',
            'rgba(191, 219, 254, 0.9)',
            'rgba(226, 232, 240, 0.95)',
        ];
        $budgetColumnCount = 1 + $myBudgetCategoryColumns->count() + 3;
    @endphp

    @if($activeSection === 'overview')
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <div>
                    <p class="text-sm text-slate-400">Kepala Departemen <span class="mx-1">›</span>
                        {{ $department?->name ?? 'Departemen IT' }}</p>
                    <h2 class="text-3xl font-bold text-slate-900 mt-1">Dashboard Anggaran</h2>
                </div>

                <div class="rounded-2xl bg-white p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-start justify-between gap-3 mb-5">
                        <div>
                            <h3 class="text-2xl font-bold text-slate-900">{{ $quarterLabel }} Ringkasan Anggaran</h3>
                            <p class="text-sm text-slate-500">Tahun Fiskal {{ $activeFiscalYear?->year ?? now()->year }} ·
                                {{ $quarterRange }}</p>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusBadgeClass }}">{{ $statusBadgeText }}</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                        <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 flex flex-col justify-between min-h-[120px] shadow-sm">
                            <p class="text-[10px] uppercase text-slate-400 font-bold tracking-widest">Total Alokasi</p>
                            <div class="mt-2">
                                <p class="text-xl font-bold text-slate-900 leading-none">Rp</p>
                                <p class="text-3xl lg:text-4xl font-black text-slate-900 mt-1 tracking-tighter whitespace-nowrap overflow-hidden">
                                    {{ number_format($summary['allocated'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 flex flex-col justify-between min-h-[120px] shadow-sm">
                            <p class="text-[10px] uppercase text-slate-400 font-bold tracking-widest">Total Pengeluaran</p>
                            <div class="mt-2">
                                <p class="text-xl font-bold text-slate-900 leading-none">Rp</p>
                                <p class="text-3xl lg:text-4xl font-black text-slate-900 mt-1 tracking-tighter whitespace-nowrap overflow-hidden">
                                    {{ number_format($summary['used'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-blue-50 border border-blue-100 p-4 flex flex-col justify-between min-h-[120px] shadow-sm">
                            <p class="text-[10px] uppercase text-blue-400 font-bold tracking-widest">Sisa Anggaran</p>
                            <div class="mt-2">
                                <p class="text-xl font-bold text-blue-600 leading-none">Rp</p>
                                <p class="text-3xl lg:text-4xl font-black text-blue-600 mt-1 tracking-tighter whitespace-nowrap overflow-hidden">
                                    {{ number_format($summary['remaining'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-sm mb-2">
                            <p class="font-medium text-slate-700">Utilisasi Anggaran</p>
                            <p class="font-semibold text-slate-700">{{ number_format($summary['percent_of_global'], 2, ',', '.') }}%</p>
                        </div>
                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-3 {{ $progressBarClass }} rounded-full" style="width: {{ min($summary['utilization'], 100) }}%">
                            </div>
                        </div>
                        <div class="flex justify-between text-xs text-slate-400 mt-2">
                            <span>Start: {{ $quarterRange }}</span>
                            <span>FY {{ $activeFiscalYear?->year ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Penggunaan Budget per Kategori removed for departemen role -->
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl bg-white p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-xl font-semibold text-slate-900 mb-4">Rincian Pengeluaran</h3>
                    <div class="mx-auto max-w-[220px]">
                        <canvas id="departmentCategoryDonut" height="220"></canvas>
                    </div>
                    <div class="mt-4 flex items-start justify-between gap-3 px-1">
                        <div class="text-left">
                            <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Tertinggi</p>
                            <p class="font-bold text-slate-800">{{ $topCategoryName }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">Aktual | Standar</div>
                            <div class="text-xs inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold hidden js-dept-std-percent">0%</div>
                        </div>
                    </div>
                    <div class="mt-5 space-y-2">
                        @forelse($categoryExpenseSummary as $category)
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 text-slate-600">
                                    <span class="inline-flex h-2.5 w-2.5 rounded-full"
                                        style="background-color: {{ $donutPalette[$loop->index % count($donutPalette)] }}"></span>
                                    <span>{{ $category['name'] }}</span>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800">
                                        <span class="js-category-share" data-index="{{ $loop->index }}">{{ number_format($category['share_percent'], 0) }}</span>%
                                        <span class="text-slate-400 font-normal ml-1">| {{ number_format($category['budget_ratio_percent'], 0) }}%</span>
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Belum ada data breakdown kategori.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-xl font-semibold text-slate-900 mb-4">Wawasan Anggaran Cerdas</h3>

                    @if($myBudgetForecast['variance_percent'] > 0)
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 mb-4">
                            <p class="text-sm font-semibold text-rose-700">⚠️ Risiko Overbudget Akhir Tahun</p>
                            <p class="text-xs text-rose-700 mt-1">
                                Berdasarkan tren saat ini, rata-rata pengeluaran departemen berisiko melebihi batas total hingga <strong>{{ number_format($myBudgetForecast['variance_percent'], 1) }}%</strong>. Evaluasi kembali pengeluaran tak terduga bulan depan untuk menurunkan <em>run-rate</em>.
                            </p>
                        </div>
                    @else
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 mb-4">
                            <p class="text-sm font-semibold text-emerald-700">✅ Proyeksi Akhir Tahun Aman</p>
                            <p class="text-xs text-emerald-700 mt-1">
                                Tren pengeluaran saat ini stabil. Anda diproyeksikan berpotensi menghemat <strong>{{ number_format(abs($myBudgetForecast['variance_percent']), 1) }}%</strong> dari total alokasi yang disetujui.
                            </p>
                        </div>
                    @endif

                    @if($itemExpenseAlerts->isNotEmpty())
                        @php
                            $alert = $itemExpenseAlerts->first();
                        @endphp
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-semibold text-amber-700">💡 Fokus Penghematan</p>
                            <p class="text-xs text-amber-700 mt-1">Item <strong>{{ $alert['item'] }}</strong> (di kategori {{ $alert['category'] }}) menyerap porsi tertinggi yakni <strong>{{ number_format($alert['share_percent'], 2) }}%</strong> pengeluaran. Coba efisiensikan area ini untuk mengamankan limit.</p>
                        </div>
                    @else
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                            <p class="text-sm font-semibold text-blue-700">💡 Distribusi Anggaran Sehat</p>
                            <p class="text-xs text-blue-700 mt-1">Tidak ada item pengeluaran yang mendominasi budget Anda secara tidak wajar. Pertahankan struktur biaya ini!</p>
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl bg-blue-600 p-6 text-white shadow-sm">
                    <h3 class="text-xl font-semibold">Butuh Bantuan?</h3>
                    <p class="text-sm text-blue-100 mt-2">Hubungi tim finance jika perlu bantuan verifikasi pengeluaran
                        departemen.</p>
                </div>
            </div>
        </div>
    @elseif($activeSection === 'my-budget')
        <div class="space-y-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-slate-400">Anggaran Saya <span class="mx-1">›</span> Rincian Bulanan</p>
                    <h2 class="text-3xl font-bold text-slate-900 mt-1">Anggaran Bulanan Departemen</h2>
                    <p class="text-sm text-slate-500">Tampilan detail performa fiskal
                        {{ $department?->name ?? 'Departemen IT' }} tahun {{ $activeFiscalYear?->year ?? now()->year }}.</p>
                </div>
                <div class="flex items-center gap-2">
                    <form method="GET" action="{{ route('departemen.dashboard') }}" class="flex items-center gap-2">
                        <input type="hidden" name="section" value="my-budget">
                        <input type="hidden" name="budget_category" value="{{ $myBudgetFilters['budget_category'] }}">
                        <input type="text" name="search" value="{{ $myBudgetFilters['search'] }}"
                            placeholder="Cari bulan atau kategori..."
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 w-56">
                        <select name="month"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600">
                            <option value="">Semua Bulan</option>
                            @foreach($myBudgetAvailableMonths as $monthOption)
                                <option value="{{ $monthOption['key'] }}"
                                    @selected($myBudgetFilters['month'] === $monthOption['key'])>{{ $monthOption['label'] }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700">Filter</button>
                    </form>
                    <button type="button" onclick="window.print()"
                        class="rounded-xl bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700 transition">Ekspor
                        CSV Detail</button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Target Anggaran (Bulan Ini)</p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">Rp
                        {{ number_format($myBudgetKpis['annual_budget'], 0, ',', '.') }}</p>
                    <p class="text-xs text-emerald-600 mt-1">Sesuai Filter / Periode Aktif</p>
                </div>
                <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Total Pengeluaran Aktual</p>
                    <p class="text-3xl font-bold text-slate-900 mt-2">Rp
                        {{ number_format($myBudgetKpis['actual_spending'], 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ number_format($myBudgetKpis['actual_percent'], 1) }}% dari
                        anggaran tahunan terpakai</p>
                </div>
                <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Variansi Bulanan</p>
                    <p
                        class="text-3xl font-bold mt-2 {{ $myBudgetKpis['monthly_variance'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ $myBudgetKpis['monthly_variance'] > 0 ? '-' : '+' }}Rp
                        {{ number_format(abs($myBudgetKpis['monthly_variance']), 0, ',', '.') }}
                    </p>
                    <p class="text-xs mt-1 {{ $myBudgetKpis['monthly_variance'] > 0 ? 'text-rose-500' : 'text-emerald-600' }}">
                        {{ $myBudgetKpis['variance_note'] }}</p>
                </div>
                <div class="rounded-2xl bg-white p-5 border border-slate-200 shadow-sm">
                    <p class="text-xs uppercase text-slate-500">Sisa Kapasitas Anggaran</p>
                    <p class="text-3xl font-bold text-blue-600 mt-2">Rp
                        {{ number_format($myBudgetKpis['remaining_runway'], 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 mt-1">Proyeksi aman sampai akhir FY</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 border border-slate-200 shadow-sm">
                @php
                    $baseFilterParams = [
                        'section' => 'my-budget',
                        'search' => $myBudgetFilters['search'],
                        'month' => $myBudgetFilters['month'],
                    ];
                @endphp
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-500 min-w-[300px]">
                        {{ $myBudgetFilters['search'] !== '' ? 'Kata kunci: ' . $myBudgetFilters['search'] : 'Cari bulan atau kategori...' }}
                    </div>
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
                        @foreach($myBudgetAvailableCategories as $categoryName)
                            @php
                                $isActiveCategory = $myBudgetFilters['budget_category'] === $categoryName;
                            @endphp
                            <a href="{{ route('departemen.dashboard', array_merge($baseFilterParams, ['budget_category' => $categoryName])) }}"
                                class="rounded-lg px-3 py-1.5 {{ $isActiveCategory ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600' }}">
                                {{ $categoryName === 'all' ? 'Semua Kategori' : $categoryName }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-3">Bulan Fiskal</th>
                                @foreach($myBudgetCategoryColumns as $categoryName)
                                                    <th class="py-2 pr-3">{{ strtoupper(
                                        \Illuminate\Support\Str::limit($categoryName, 10, '')
                                    ) }}</th>
                                @endforeach
                                <th class="py-2 pr-3">Rencana</th>
                                <th class="py-2 pr-3">Aktual</th>
                                <th class="py-2 pr-3">Variansi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($myBudgetMonthlyRows as $row)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-3 font-semibold text-slate-800">{{ $row['month_label'] }}</td>
                                    @foreach($myBudgetCategoryColumns as $categoryName)
                                        <td class="py-3 pr-3 text-slate-600">Rp
                                            {{ number_format($row['categories'][$categoryName] ?? 0, 0, ',', '.') }}</td>
                                    @endforeach
                                    <td class="py-3 pr-3 text-slate-700">Rp {{ number_format($row['planned'], 0, ',', '.') }}</td>
                                    <td class="py-3 pr-3 font-semibold text-slate-800">Rp
                                        {{ number_format($row['actual'], 0, ',', '.') }}</td>
                                    <td
                                        class="py-3 pr-3 font-semibold {{ $row['variance'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $row['variance'] > 0 ? '+' : '-' }}Rp
                                        {{ number_format(abs($row['variance']), 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $budgetColumnCount }}" class="py-4 text-center text-slate-500">Belum ada data
                                        rincian bulanan sesuai filter.</td>
                                </tr>
                            @endforelse
                            <tr class="bg-slate-50 font-semibold text-slate-800">
                                <td class="py-3 pr-3">Total FYTD</td>
                                @foreach($myBudgetCategoryColumns as $categoryName)
                                    <td class="py-3 pr-3">Rp
                                        {{ number_format($myBudgetTotals['categories'][$categoryName] ?? 0, 0, ',', '.') }}</td>
                                @endforeach
                                <td class="py-3 pr-3">Rp {{ number_format($myBudgetTotals['planned'], 0, ',', '.') }}</td>
                                <td class="py-3 pr-3">Rp {{ number_format($myBudgetTotals['actual'], 0, ',', '.') }}</td>
                                <td
                                    class="py-3 pr-3 {{ $myBudgetTotals['variance'] > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ $myBudgetTotals['variance'] > 0 ? '+' : '-' }}Rp
                                    {{ number_format(abs($myBudgetTotals['variance']), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-6 border border-slate-200 shadow-sm mb-6">
                <h3 class="text-xl font-semibold text-slate-900 mb-4">Tren Penggunaan Dana per Kategori</h3>
                <div class="w-full relative min-h-[300px]">
                    @if($myBudgetMonthlyRows->isEmpty())
                        <div class="absolute inset-0 flex items-center justify-center text-slate-400">
                            Belum ada data bulanan untuk ditampilkan.
                        </div>
                    @else
                        <canvas id="myBudgetCategoryTrendChart" height="300"></canvas>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="rounded-2xl bg-white p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-xl font-semibold text-slate-900 mb-4">Pendorong Pengeluaran Tertinggi (FYTD)</h3>
                    <div class="space-y-4">
                        @forelse($myBudgetTopDrivers as $driver)
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span class="text-slate-700 font-medium">{{ $driver['label'] }}</span>
                                    <span class="text-slate-900 font-semibold">Rp
                                        {{ number_format($driver['amount'], 0, ',', '.') }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-2 bg-blue-600 rounded-full"
                                        style="width: {{ min($driver['share_percent'], 100) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Belum ada driver pengeluaran.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-xl font-semibold text-slate-900 mb-4">Analisis Proyeksi Anggaran</h3>
                    <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                        <p class="text-sm font-semibold text-slate-800">Proyeksi untuk Q4</p>
                        <p class="text-xs text-slate-600 mt-1">
                            Berdasarkan tren saat ini, proyeksi pengeluaran
                            <span
                                class="font-semibold {{ $myBudgetForecast['status'] === 'danger' ? 'text-rose-600' : 'text-emerald-600' }}">
                                {{ $myBudgetForecast['variance_percent'] > 0 ? number_format($myBudgetForecast['variance_percent'], 1) . '% melebihi anggaran' : abs(number_format($myBudgetForecast['variance_percent'], 1)) . '% di bawah anggaran' }}
                            </span>
                            dengan total akhir tahun sekitar
                            <span class="font-semibold text-slate-800">Rp
                                {{ number_format($myBudgetForecast['projected_year_end'], 0, ',', '.') }}</span>.
                        </p>
                    </div>
                    <p class="text-xs text-blue-600 font-semibold mt-4">JALANKAN PEMBANGUN SKENARIO →</p>
                </div>
            </div>
        </div>
    @elseif($activeSection === 'team-expenses')
        <div class="space-y-6">
            <div>
                <p class="text-sm text-slate-400">Kepala Departemen <span class="mx-1">›</span>
                    {{ $department?->name ?? 'Departemen IT' }}</p>
                <h2 class="text-3xl font-bold text-slate-900 mt-1">Pengeluaran Tim</h2>
            </div>

            <div class="rounded-2xl bg-white p-6 border border-slate-200 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-900 mb-4">Pengeluaran Terbaru Departemen</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-3">Deskripsi</th>
                                <th class="py-2 pr-3">Kategori</th>
                                <th class="py-2 pr-3">Tanggal</th>
                                <th class="py-2 pr-3">Status</th>
                                <th class="py-2 pr-3">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentDepartmentSpending as $row)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-3 text-slate-800 font-medium">{{ $row['description'] }}</td>
                                    <td class="py-3 pr-3 text-slate-600">{{ $row['category'] }}</td>
                                    <td class="py-3 pr-3 text-slate-500">{{ optional($row['date'])->format('M d, Y') }}</td>
                                    <td class="py-3 pr-3">
                                        <span
                                            class="px-2 py-1 rounded-md text-xs font-semibold {{ $row['status'] === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $row['status'] === 'approved' ? 'Disetujui' : 'Menunggu' }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-3 font-semibold text-slate-800">Rp
                                        {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-slate-500">Belum ada data pengeluaran tim.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif($activeSection === 'reports')
        <div class="space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-400">Kepala Departemen <span class="mx-1">›</span>
                        {{ $department?->name ?? 'Departemen IT' }}</p>
                    <h2 class="text-3xl font-bold text-slate-900 mt-1">Laporan</h2>
                </div>
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                    <span>⇩</span>
                    <span>Ekspor Laporan</span>
                </button>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="rounded-2xl bg-white p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-xl font-semibold text-slate-900 mb-4">Rincian Pengeluaran</h3>
                    <canvas id="departmentCategoryDonut" height="250"></canvas>
                </div>
                <div class="rounded-2xl bg-white p-6 border border-slate-200 shadow-sm">
                    <h3 class="text-xl font-semibold text-slate-900 mb-4">Tren Pengeluaran Bulanan</h3>
                    <canvas id="departmentMonthlyChart" height="250"></canvas>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-6">
            <div>
                <p class="text-sm text-slate-400">Kepala Departemen <span class="mx-1">›</span>
                    {{ $department?->name ?? 'Departemen IT' }}</p>
                <h2 class="text-3xl font-bold text-slate-900 mt-1">Notifikasi</h2>
            </div>

            <div class="rounded-2xl bg-white p-6 border border-slate-200 shadow-sm space-y-3">
                @if($itemExpenseAlerts->isEmpty())
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="font-semibold text-emerald-700">Tidak Ada Alert Kritis</p>
                        <p class="text-sm text-emerald-700 mt-1">Semua item masih dalam kondisi pengeluaran aman.</p>
                    </div>
                @else
                    @foreach($itemExpenseAlerts as $alert)
                        <div
                            class="rounded-xl border p-4 {{ $alert['status'] === 'danger' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50' }}">
                            <p class="font-semibold {{ $alert['status'] === 'danger' ? 'text-rose-700' : 'text-amber-700' }}">
                                {{ $alert['item'] }} · {{ number_format($alert['share_percent'], 2) }}%</p>
                            <p class="text-sm {{ $alert['status'] === 'danger' ? 'text-rose-700' : 'text-amber-700' }} mt-1">Kategori
                                {{ $alert['category'] }} dengan pengeluaran Rp {{ number_format($alert['used'], 0, ',', '.') }}.</p>
                        </div>
                    @endforeach
                @endif

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="font-semibold text-slate-800">{{ $pendingApprovals }} Menunggu Persetujuan</p>
                    <p class="text-sm text-slate-500 mt-1">Terdapat request yang menunggu review finance.</p>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const monthlyLabels = @json($monthlyLabels->values());
        const monthlyUsageData = @json($monthlyUsageData->values());
        const categoryExpenseSummary = @json($categoryExpenseSummary->values());
        const deptAllocated = @json($summary['allocated'] ?? 0);
        const donutPalette = [
            'rgba(37, 99, 235, 0.95)',
            'rgba(59, 130, 246, 0.85)',
            'rgba(147, 197, 253, 0.9)',
            'rgba(191, 219, 254, 0.9)',
            'rgba(226, 232, 240, 0.95)'
        ];

        const monthlyCanvas = document.getElementById('departmentMonthlyChart');
        if (monthlyCanvas) {
            new Chart(monthlyCanvas, {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [
                        {
                            label: 'Pengeluaran Bulanan',
                            data: monthlyUsageData,
                            borderColor: 'rgb(37, 99, 235)',
                            backgroundColor: 'rgba(37, 99, 235, 0.15)',
                            tension: 0.3,
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        const donutCanvas = document.getElementById('departmentCategoryDonut');
        if (donutCanvas) {
            new Chart(donutCanvas, {
                type: 'doughnut',
                data: {
                    labels: categoryExpenseSummary.map(item => item.name),
                    datasets: [
                        {
                            data: categoryExpenseSummary.map(item => item.used),
                            backgroundColor: categoryExpenseSummary.map((_, index) => donutPalette[index % donutPalette.length]),
                            borderWidth: 0,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    cutout: '67%',
                    plugins: {
                        legend: { display: false }
                    }
                }
            });

            // Update the right-hand percentage labels dynamically based on current data
                    (function updateCategoryShareLabels() {
                try {
                    const data = categoryExpenseSummary.map(item => Number(item.used) || 0);
                    const total = data.reduce((s, v) => s + v, 0);
                    const nodes = document.querySelectorAll('.js-category-share');
                    nodes.forEach(node => {
                        const idx = Number(node.getAttribute('data-index')) || 0;
                        const value = total > 0 ? (data[idx] / total) * 100 : 0;
                        node.textContent = Math.round(value).toString();
                    });
                    const stdNodes = document.querySelectorAll('.js-category-std');
                    stdNodes.forEach(node => {
                        const idx = Number(node.getAttribute('data-index')) || 0;
                        const allocated = Number(categoryExpenseSummary[idx]?.allocated || 0);
                        const ratio = categoryExpenseSummary[idx] && categoryExpenseSummary[idx].budget_ratio_percent != null ? Number(categoryExpenseSummary[idx].budget_ratio_percent) : null;
                        let percentStd = 0;
                        if (ratio !== null) {
                            percentStd = ratio;
                        } else if (deptAllocated > 0 && allocated > 0) {
                            percentStd = (allocated / deptAllocated) * 100;
                        } else {
                            percentStd = 0;
                        }
                        node.textContent = Math.round(percentStd).toString();
                    });
                    // also compute total allocated across categories and set Std% badge + allocated labels
                    try {
                        const totalAllocated = categoryExpenseSummary.reduce((s, it) => s + (Number(it.allocated || 0)), 0);
                        const stdBadge = document.querySelector('.js-dept-std-percent');
                        if (stdBadge) {
                            // prefer explicit budget_ratio_percent if categories provide it
                            const hasRatio = categoryExpenseSummary.some(it => it && (it.budget_ratio_percent != null));
                            let stdPercent = 0;
                            if (hasRatio) {
                                stdPercent = categoryExpenseSummary.reduce((s, it) => s + (Number(it?.budget_ratio_percent || 0)), 0);
                            } else {
                                stdPercent = deptAllocated > 0 ? (totalAllocated / deptAllocated) * 100 : 0;
                            }
                            stdBadge.textContent = Math.round(stdPercent) + '%';
                        }
                    } catch (e) {}
                } catch (e) {
                    // silent
                }
            })();
        }

        const categoryCurrentMonthCanvas = document.getElementById('categoryCurrentMonthChart');
        if (categoryCurrentMonthCanvas) {
            const categoryCurrentMonthData = @json($categoryCurrentMonthData ?? []);
            
            new Chart(categoryCurrentMonthCanvas, {
                type: 'bar',
                data: {
                    labels: categoryCurrentMonthData.map(item => {
                        let text = item.label;
                        return text.length > 12 ? text.substring(0, 10) + '...' : text;
                    }),
                    datasets: [{
                        label: 'Pengeluaran Bulan Ini',
                        data: categoryCurrentMonthData.map(item => item.used),
                        backgroundColor: categoryCurrentMonthData.map((_, index) => donutPalette[index % donutPalette.length].replace(/[\d\.]+\)$/g, '0.8)')),
                        borderColor: categoryCurrentMonthData.map((_, index) => donutPalette[index % donutPalette.length].replace(/[\d\.]+\)$/g, '1)')),
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItems) {
                                    // Get original full label from the data array based on the index
                                    const index = tooltipItems[0].dataIndex;
                                    return categoryCurrentMonthData[index].label;
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value / 1000000) + 'JT';
                                    } else if (value >= 1000) {
                                        return 'Rp ' + (value / 1000) + 'K';
                                    }
                                    return 'Rp ' + value;
                                }
                            }
                        }
                    },
                    interaction: {
                        mode: 'index',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }

        const myBudgetCategoryTrendCanvas = document.getElementById('myBudgetCategoryTrendChart');
        if (myBudgetCategoryTrendCanvas) {
            const trendRows = @json($myBudgetMonthlyRows->reverse()->values() ?? []);
            const categoryColumns = @json($myBudgetCategoryColumns->values() ?? []);
            
            // Generate labels from smallest month to current month
            const labels = trendRows.map(row => row.month_label);
            
            const datasets = categoryColumns.map((col, index) => {
                const color = donutPalette[index % donutPalette.length];
                return {
                    label: col,
                    data: trendRows.map(row => {
                        return row.categories && row.categories[col] ? row.categories[col] : 0;
                    }),
                    borderColor: color.replace(/[\d\.]+\)$/g, '1)'),
                    backgroundColor: color.replace(/[\d\.]+\)$/g, '0.1)'),
                    tension: 0.3,
                    fill: false,
                    borderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: color.replace(/[\d\.]+\)$/g, '1)'),
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                };
            });

            new Chart(myBudgetCategoryTrendCanvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value / 1000000) + 'JT';
                                    } else if (value >= 1000) {
                                        return 'Rp ' + (value / 1000) + 'K';
                                    }
                                    return 'Rp ' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }
    </script>
@endpush