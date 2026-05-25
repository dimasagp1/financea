@extends('layouts.dashboard', ['title' => 'Monitoring Forecast'])

@section('content')
    <div class="mx-auto max-w-7xl">
        {{-- Header & Month Filter --}}
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">
                    @if(!$is_fat_or_superadmin && collect($departmentsData)->first())
                        Forecast {{ collect($departmentsData)->first()->name }}
                    @else
                        Monitoring Forecast
                    @endif
                </h2>
                <div class="mt-1 flex items-center gap-2 text-sm text-slate-500">
                    <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-semibold border border-indigo-100">
                        Periode: {{ $currentMonthName }}
                    </span>
                    <span>• Pantau alokasi target dan peramalan pengeluaran komprehensif.</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <form action="{{ route('fat.forecasts.index') }}" method="GET" class="flex items-center gap-2 bg-white p-1.5 rounded-lg border border-slate-300 shadow-sm">
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

                @if($is_fat_or_superadmin)
                <a href="{{ route('fat.global-budgets.create', ['type' => 'forecast']) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition shadow-lg shadow-emerald-200">
                    <span>＋</span>
                    <span>Set Target Global</span>
                </a>
                @endif
            </div>
        </div>

        {{-- Summary Cards --}}
        @php
            $grandUtil = $summary['utilization'];
            if ($grandUtil > 100) $grandColor = 'text-rose-700';
            elseif ($grandUtil <= 20) $grandColor = 'text-amber-600';
            elseif ($grandUtil <= 80) $grandColor = 'text-emerald-600';
            else $grandColor = 'text-amber-600';
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-{{ $is_fat_or_superadmin ? 2 : 3 }} gap-4 mb-6">
            @if(!$is_fat_or_superadmin && collect($departmentsData)->first())
                @php $firstDept = collect($departmentsData)->first(); @endphp
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm relative overflow-hidden">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Target Forecast</p>
                    <p class="mt-2 text-2xl font-bold text-indigo-700">Rp {{ number_format($firstDept->calculated_forecast, 0, ',', '.') }}</p>
                    <div class="mt-2 flex flex-col gap-1">
                        <div class="text-[10px] text-slate-400">Pagu Asli: Rp {{ number_format($firstDept->calculated_pagu, 0, ',', '.') }}</div>
                        @if($firstDept->is_overridden)
                            <div class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                Manual Override
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ $is_fat_or_superadmin ? 'Terserap / Realisasi Terkini' : 'Realisasi Saat Ini' }}</p>
                <p class="mt-2 text-2xl font-bold {{ $grandColor }}">Rp {{ number_format($summary['actual_monthly'], 0, ',', '.') }}</p>
                <div class="mt-2 text-xs text-slate-400">{{ round($grandUtil, 1) }}% dari target forecast</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm font-bold">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ $is_fat_or_superadmin ? 'Sisa Kuota Forecast' : 'Sisa Target (Gap)' }}</p>
                @php $grandIsSurplus = $summary['remaining'] >= 0; @endphp
                <p class="mt-2 text-2xl font-bold {{ $grandIsSurplus ? 'text-blue-600' : 'text-rose-600' }}">
                    {{ $grandIsSurplus ? '' : '-' }}Rp {{ number_format(abs($summary['remaining']), 0, ',', '.') }}
                </p>
                <div class="mt-2 text-xs {{ $grandIsSurplus ? 'text-blue-400' : 'text-rose-400' }} uppercase">{{ $grandIsSurplus ? 'Under Gap' : 'Over Gap' }}</div>
            </div>
        </div>

        @if(!$is_fat_or_superadmin && collect($departmentsData)->first())
            <div class="mb-6">
                 <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-1.5 h-6 bg-indigo-600 rounded-full"></span>
                        Status Target per Cost Center
                    </h3>
                    <div class="text-xs text-slate-400 italic">Target Forecast Bulanan</div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach(collect($departmentsData)->first()->categories as $cat)
                        @php
                            $catUtil = $cat->utilization;
                            $catIsOver = $catUtil > 100;
                            $catBarColor = $catIsOver ? 'bg-rose-500' : ($catUtil <= 20 ? 'bg-amber-400' : ($catUtil <= 80 ? 'bg-indigo-500' : 'bg-amber-400'));
                            $catTextColor = $catIsOver ? 'text-rose-600' : ($catUtil <= 20 ? 'text-amber-600' : ($catUtil <= 80 ? 'text-indigo-600' : 'text-amber-600'));
                        @endphp
                        <div class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:border-indigo-300">
                            <div class="flex items-start justify-between mb-3">
                                <div class="min-w-0">
                                    <h4 class="text-sm font-bold text-slate-800 truncate">{{ $cat->name }}</h4>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ $cat->code }}</p>
                                </div>
                                <div class="text-[10px] font-bold {{ $catTextColor }} bg-slate-50 px-1.5 py-0.5 rounded border border-slate-100">
                                    {{ round($catUtil, 1) }}%
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="text-slate-400">Target:</span>
                                    <span class="font-semibold text-slate-700">Rp {{ number_format($cat->forecast_value, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-[10px]">
                                    <span class="text-slate-400">Aktual:</span>
                                    <span class="font-bold {{ $catTextColor }}">Rp {{ number_format($cat->actual_spending, 0, ',', '.') }}</span>
                                </div>
                                <div class="pt-1">
                                    <div class="w-full bg-slate-100 rounded-full h-1 overflow-hidden">
                                        <div class="h-1 {{ $catBarColor }} rounded-full" style="width: {{ min($catUtil, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- SETUP ALERTS --}}
        @if(!$globalMonthly)
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 flex items-start gap-3">
                <span class="text-amber-500 text-lg mt-0.5">⚠️</span>
                <div>
                    <p class="text-sm font-bold text-amber-800">Target Global (Forecast) bulan {{ $currentMonthName }} belum diisi!</p>
                    <p class="text-xs text-amber-600 mt-0.5">Baseline pagu bulan ini masih bernilai Rp 0.</p>
                    @if($is_fat_or_superadmin)
                        <a href="{{ route('fat.global-budgets.create', ['type' => 'forecast']) }}"
                            class="inline-flex items-center gap-1.5 mt-2 text-xs font-bold text-amber-700 bg-amber-100 hover:bg-amber-200 px-3 py-1.5 rounded-lg transition">
                            ＋ Input Target Global (Forecast) →
                        </a>
                    @else
                        <p class="text-[10px] text-amber-500 mt-1 font-semibold italic">Silakan hubungi tim FAT untuk penentuan arah peramalan global bulan ini.</p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Department Table with Expandable Rows (FAT only) --}}
        @if($is_fat_or_superadmin)
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Monitoring Forecast Per Departemen</h3>
                    <span class="text-xs text-slate-500 bg-white px-2 py-1 rounded border border-slate-200">{{ collect($departmentsData)->count() }} Departemen</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-3 w-8"></th>
                                <th class="px-3 py-3">Departemen</th>
                                <th class="px-3 py-3 text-right">Forecast (Set Target)</th>
                                <th class="px-3 py-3 text-right">Realisasi (Actual)</th>
                                <th class="px-3 py-3 text-center" style="min-width:100px">% Penyerapan</th>
                                <th class="px-3 py-3 text-right">Sisa Target (Gap)</th>
                                <th class="px-3 py-3 text-center">Aksi Override</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departmentsData as $dept)
                                @php
                                    $deptUtil = $dept->utilization;
                                    if ($deptUtil > 100) $deptStatusColor = 'text-rose-700';
                                    elseif ($deptUtil <= 20) $deptStatusColor = 'text-amber-600';
                                    elseif ($deptUtil <= 80) $deptStatusColor = 'text-emerald-600';
                                    else $deptStatusColor = 'text-amber-600';

                                    $barPct = min($deptUtil, 100);
                                    $barColor = $deptUtil > 100 ? 'bg-rose-600'
                                        : ($deptUtil <= 20 ? 'bg-amber-500'
                                        : ($deptUtil <= 80 ? 'bg-emerald-500' : 'bg-amber-500'));

                                    $deptIsSurplus = $dept->remaining >= 0;
                                @endphp
                                <tr class="border-b border-slate-100 hover:bg-slate-50/50 cursor-pointer transition-colors"
                                    onclick="toggleDeptRow({{ $dept->id }})">
                                    <td class="px-3 py-4 text-center">
                                        <span id="arrow-dept-{{ $dept->id }}" class="text-slate-400 transition-transform inline-block text-xs">▶</span>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="font-semibold text-slate-800">{{ $dept->name }}</div>
                                        <div class="text-[10px] text-slate-400">Pagu Asli: Rp {{ number_format($dept->calculated_pagu, 0, ',', '.') }} @if($dept->is_overridden)&bull; <span class="text-amber-600 font-bold">MANUAL OVERRIDE</span>@endif</div>
                                    </td>
                                    <td class="px-3 py-4 text-right font-mono text-indigo-700 font-bold">Rp {{ number_format($dept->calculated_forecast, 0, ',', '.') }}</td>
                                    <td class="px-3 py-4 text-right font-mono text-slate-700 font-medium">Rp {{ number_format($dept->monthly_used, 0, ',', '.') }}</td>
                                    <td class="px-3 py-4">
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="text-[10px] font-bold {{ $deptStatusColor }}">{{ number_format($deptUtil, 1, ',', '.') }}%</div>
                                            <div class="w-full bg-slate-100 rounded-full h-1.5" style="min-width:80px">
                                                <div class="{{ $barColor }} h-1.5 rounded-full transition-all" style="width: {{ $barPct }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-right font-mono font-medium">
                                        <span class="{{ $deptIsSurplus ? 'text-blue-600' : 'text-rose-600' }}">
                                            {{ $deptIsSurplus ? '' : '-' }}Rp {{ number_format(abs($dept->remaining), 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center" onclick="event.stopPropagation()">
                                        <button onclick="openEditForecast({{ $dept->id }}, '{{ addslashes($dept->name) }}', '{{ $selectedYear }}-{{ sprintf('%02d', $selectedMonth) }}', '{{ $currentMonthName }}', {{ $dept->calculated_forecast }}, {{ $dept->is_overridden ? 'true' : 'false' }})" 
                                            class="px-2 py-1 rounded bg-indigo-50 text-indigo-700 text-xs font-semibold hover:bg-indigo-100 transition">
                                            Edit Forecast
                                        </button>
                                    </td>
                                </tr>

                                {{-- Category Detail --}}
                                <tr id="detail-dept-{{ $dept->id }}" class="hidden">
                                    <td colspan="7" class="px-0 py-0">
                                        <div class="bg-indigo-50/20 border-y border-slate-100 px-8 py-4">
                                            <table class="w-full text-sm text-left">
                                                <thead class="text-[10px] text-slate-500 uppercase">
                                                    <tr class="border-b border-slate-200">
                                                        <th class="py-2 pr-3 text-left">Nama Kategori</th>
                                                        <th class="py-2 px-3 text-right">Target Forecast</th>
                                                        <th class="py-2 px-3 text-right">Realisasi</th>
                                                        <th class="py-2 px-3 text-center">% Capaian</th>
                                                        <th class="py-2 px-3 text-right">Sisa (Gap)</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @foreach($dept->categories as $cat)
                                                        @php
                                                            $catUtil = $cat->utilization;
                                                            $catIsOver = $catUtil > 100;
                                                            $catBarColor = $catIsOver ? 'bg-rose-600' : ($catUtil <= 20 ? 'bg-amber-500' : ($catUtil <= 80 ? 'bg-indigo-500' : 'bg-amber-500'));
                                                            $catTextColor = $catIsOver ? 'text-rose-700' : ($catUtil <= 20 ? 'text-amber-600' : ($catUtil <= 80 ? 'text-indigo-600' : 'text-amber-600'));
                                                        @endphp
                                                        <tr class="hover:bg-white/60 transition-colors">
                                                            <td class="py-3 pr-3">
                                                                <div class="font-medium text-slate-800">{{ $cat->name }}</div>
                                                                <div class="text-[10px] text-slate-400">{{ $cat->code }}</div>
                                                            </td>
                                                            <td class="py-3 px-3 text-right font-mono text-xs text-indigo-600 font-semibold">Rp {{ number_format($cat->forecast_value, 0, ',', '.') }}</td>
                                                            <td class="py-3 px-3 text-right font-mono text-xs text-slate-600">Rp {{ number_format($cat->actual_spending, 0, ',', '.') }}</td>
                                                            <td class="py-3 px-3">
                                                                <div class="flex flex-col items-center gap-1">
                                                                    <div class="text-[10px] font-bold {{ $catTextColor }}">{{ number_format($catUtil, 1, ',', '.') }}%</div>
                                                                    <div class="w-full bg-slate-200 rounded-full h-1" style="min-width:60px">
                                                                        <div class="{{ $catBarColor }} h-1 rounded-full transition-all" style="width: {{ min($catUtil, 100) }}%"></div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="py-3 px-3 text-right font-mono text-xs font-medium">
                                                                <span class="{{ $cat->remaining >= 0 ? 'text-blue-600' : 'text-rose-600' }}">
                                                                    Rp {{ number_format(abs($cat->remaining), 0, ',', '.') }}
                                                                </span>
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

    {{-- MODAL EDIT FORECAST --}}
    @if($is_fat_or_superadmin)
    <div id="modal-edit-forecast" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
            onclick="document.getElementById('modal-edit-forecast').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden animate-in">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-slate-800">Override Forecast</h3>
                    <p id="edit-dept-name" class="text-xs text-slate-500 font-semibold"></p>
                </div>
                <button onclick="document.getElementById('modal-edit-forecast').classList.add('hidden')"
                    class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form id="form-edit-forecast" action="" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" name="month" id="edit-month-value">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Bulan Target</label>
                    <input type="text" id="edit-month-name"
                        class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-500 text-sm" readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nominal Target Keseluruhan (Rp)</label>
                    <input type="number" name="amount" id="edit-amount" min="0"
                        class="w-full rounded-lg border-slate-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold"
                        required>
                    <p class="text-[10px] text-slate-500 mt-1">
                        Angka ini akan dipecah secara proporsional ke sub-kategori departemen sesuai dengan Persentase Rasio standar.
                    </p>
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-edit-forecast').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow-md shadow-indigo-200">Simpan Override</button>
                </div>
            </form>
            
            <form id="form-reset-forecast" action="" method="POST" class="hidden px-6 pb-6 border-t border-slate-100 pt-4 mt-2">
                @csrf
                @method('DELETE')
                <input type="hidden" name="month" id="reset-month-value">
                 <button type="submit" 
                    class="w-full text-xs text-rose-600 hover:bg-rose-50 p-2 rounded text-center border border-dashed border-rose-300">
                    ↺ Hapus Override & Kembali ke Baseline
                </button>
            </form>
        </div>
    </div>
    @endif

    <script>
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

        @if($is_fat_or_superadmin)
        function openEditForecast(deptId, deptName, monthValue, monthName, amount, isOverridden) {
            const basePath = "{{ route('fat.forecasts.index') }}";
            document.getElementById('form-edit-forecast').action = basePath + '/' + deptId;
            document.getElementById('form-reset-forecast').action = basePath + '/' + deptId;

            document.getElementById('edit-dept-name').textContent = deptName;
            document.getElementById('edit-month-value').value = monthValue;
            document.getElementById('edit-month-name').value = monthName;
            document.getElementById('edit-amount').value = amount;
            
            const resetForm = document.getElementById('form-reset-forecast');
            document.getElementById('reset-month-value').value = monthValue;
            
            if(isOverridden) {
                resetForm.classList.remove('hidden');
            } else {
                resetForm.classList.add('hidden');
            }
            
            document.getElementById('modal-edit-forecast').classList.remove('hidden');
        }
        @endif
    </script>
    <style>
        .animate-in { animation: popIn 0.2s ease-out; }
        @keyframes popIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
    </style>
@endsection