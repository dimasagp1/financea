@extends('layouts.dashboard', ['title' => 'Konfigurasi Departemen'])

@section('content')
    <div class="mx-auto max-w-7xl">
    <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Konfigurasi Departemen</h2>
            <p class="mt-1 text-sm text-slate-500">
                Atur bobot alokasi anggaran per departemen untuk FY{{ $activeFiscalYear?->year ?? now()->year }}.
                Total bobot harus tepat 100%.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isFAT())
                <button type="button" onclick="document.getElementById('modal-create-dept').classList.remove('hidden')" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition">
                    <span>＋</span>
                    <span>Tambah Departemen</span>
                </button>
            @endif
            <a href="{{ route('fat.global-budgets.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <span class="text-slate-500">📅</span>
                <span>Omset Bulanan</span>
            </a>
            <a href="{{ route('monitoring.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <span class="text-slate-500">📊</span>
                <span>Monitoring Budget</span>
            </a>
            <button type="submit" form="allocation-form" @disabled(filled($filters['search'])) class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                <span>🔒</span>
                Simpan Alokasi
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-5">
        <div class="rounded-xl border {{ $weightGap < 0 ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-white' }} p-5 shadow-sm transition-colors">
            <p class="text-xs uppercase tracking-wide {{ $weightGap < 0 ? 'text-rose-600' : 'text-slate-500' }}">Total Bobot Dialokasikan</p>
            <div class="mt-1 flex items-center justify-between gap-3">
                <p class="text-3xl font-bold {{ $weightGap < 0 ? 'text-rose-700' : 'text-slate-900' }}">{{ number_format($totalWeightAssigned, 2, ',', '.') }}%</p>
                <div class="h-10 w-10 rounded-full border-[6px] {{ $weightGap < 0 ? 'border-rose-200 border-l-rose-600' : 'border-blue-100 border-l-blue-500' }}"></div>
            </div>
            <p class="mt-1 text-xs {{ $weightGap < 0 ? 'text-rose-500' : 'text-slate-500' }}">Dari target 100%</p>

            @php
                $allocatedBar = max(min($totalWeightAssigned, 100), 0);
                $remainingBar = 100 - $allocatedBar;
            @endphp
            <div class="mt-3 h-2 rounded-full {{ $weightGap < 0 ? 'bg-rose-200' : 'bg-slate-100' }} overflow-hidden">
                <div class="h-full {{ $weightGap < 0 ? 'bg-rose-600' : 'bg-blue-600' }}" style="width: {{ $allocatedBar }}%"></div>
            </div>
            <div class="mt-2 flex items-center justify-between text-xs">
                <span class="font-medium {{ $weightGap < 0 ? 'text-rose-700' : 'text-blue-700' }}">Terpakai</span>
                <span class="font-medium {{ $weightGap > 0 ? 'text-amber-600' : ($weightGap < 0 ? 'text-rose-600 font-bold' : 'text-emerald-600') }}">
                    {{ $weightGap > 0 ? number_format($weightGap, 2, ',', '.') . '% tersisa' : ($weightGap < 0 ? number_format(abs($weightGap), 2, ',', '.') . '% berlebih' : '100% tercapai') }}
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-500">Omset Bulan Ini ({{ $currentMonthName }})</p>
            @if($currentMonthBudget)
                <p class="mt-1 text-3xl font-bold text-emerald-600">Rp {{ number_format($currentMonthBudget->amount, 0, ',', '.') }}</p>
                <div class="mt-2 flex flex-col gap-1">
                    <p class="text-xs text-slate-500 font-medium">Berdasarkan Global Budget bulan ini.</p>
                </div>
            @else
                <p class="mt-1 text-2xl font-bold text-slate-400 italic">Belum Diatur</p>
                <div class="mt-2 flex flex-col gap-1">
                    <p class="text-xs text-rose-500 font-medium">Nilai referensi bulanan belum diset.</p>
                </div>
            @endif
        </div>

        <div class="rounded-xl border {{ abs($weightGap) > 0.01 ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} p-5 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-lg {{ abs($weightGap) > 0.01 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">⚠</div>
                <div class="flex-1">
                    <p class="text-xs uppercase tracking-wide {{ abs($weightGap) > 0.01 ? 'text-amber-700' : 'text-emerald-700' }}">Status Alokasi</p>
            @if(abs($weightGap) > 0.01)
                <p class="mt-2 text-sm font-semibold text-amber-800">Perlu Penyesuaian</p>
                <p class="mt-1 text-sm text-amber-700">
                    @if($weightGap > 0)
                        Masih ada {{ number_format($weightGap, 2, ',', '.') }}% bobot yang belum dialokasikan.
                    @else
                        Bobot melebihi {{ number_format(abs($weightGap), 2, ',', '.') }}%. Kurangi agar kembali ke 100%.
                    @endif
                </p>
            @else
                <p class="mt-2 text-sm font-semibold text-emerald-800">Alokasi Valid</p>
                <p class="mt-1 text-sm text-emerald-700">Total bobot sudah tepat 100% dan siap digunakan.</p>
            @endif
            </div>
            </div>
        </div>
    </div>

    @if(filled($filters['search']))
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
            Mode pencarian aktif. Tombol <strong>Simpan Alokasi</strong> dinonaktifkan agar perhitungan tetap akurat untuk semua departemen.
            <a href="{{ route('fat.departments.index') }}" class="ml-1 font-semibold underline">Reset pencarian</a> untuk menyimpan.
        </div>
    @endif

    <div class="rounded-xl border border-slate-300 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <h3 class="text-lg font-semibold text-slate-900">Breakdown Departemen</h3>

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

        <form id="allocation-form" method="POST" action="{{ route('fat.departments.bulk-update-ratios') }}">
            @csrf
            @method('PATCH')

            @php
                $displayedWeightTotal = (float) $departments->sum('budget_ratio_percent');
                $displayedBudgetTotal = $deptYtdExpenses->sum();
            @endphp

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-300 bg-slate-100 text-left text-[11px] uppercase tracking-wide text-slate-500">
                            <th class="px-5 py-3">Nama Departemen</th>
                            <th class="px-5 py-3">Akumulasi Biaya (YTD)</th>
                            <th class="px-5 py-3">Bobot Rasio (%)</th>
                            <th class="px-5 py-3">Simulasi Pagu Bulanan</th>
                          <!--  <th class="px-5 py-3 text-center">Status Realisasi</th> -->
                            <th class="px-5 py-3 text-right">Aksi</th>
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
                                <td class="px-5 py-3 text-slate-600">Rp {{ number_format((float) ($deptYtdExpenses[$department->id] ?? 0), 0, ',', '.') }}</td>
                                <td class="px-5 py-3">
                                    <input type="hidden" name="ratios[{{ $index }}][department_id]" value="{{ $department->id }}">
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="number"
                                            name="ratios[{{ $index }}][budget_ratio_percent]"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            value="{{ number_format((float) $department->budget_ratio_percent, 2, '.', '') }}"
                                            class="w-24 rounded-md border-slate-300 text-right"
                                            required
                                        >
                                        <span class="text-slate-500">%</span>
                                    </div>
                                </td>
                                 <td class="px-5 py-3">
                                    <span class="inline-flex rounded-lg bg-indigo-50 px-2.5 py-1 font-semibold text-indigo-700">
                                        Rp {{ number_format($monthlyCeiling, 0, ',', '.') }}
                                    </span>
                                    <div class="text-[10px] text-slate-400 mt-0.5">EST. PER BULAN</div>
                                </td>
                             <!--   <td class="px-5 py-3 text-center">
                                    @if($isOverBudget)
                                        <div class="inline-flex flex-col items-center">
                                            <button type="button" onclick="openRealisasiPopup({{ $department->id }})" class="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-rose-300" title="Lihat detail realisasi departemen">
                                                Over Budget
                                            </button>
                                            <span class="text-[10px] text-rose-500 mt-1">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</span>
                                        </div>
                                    @else
                                        <div class="inline-flex flex-col items-center">
                                            <button type="button" onclick="openRealisasiPopup({{ $department->id }})" class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-300" title="Lihat detail realisasi departemen">
                                                On Budget
                                            </button>
                                            <span class="text-[10px] text-emerald-600 mt-1">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                </td> -->
                                <td class="px-5 py-3">
                                    <div class="flex justify-end gap-2">
                                        @if(auth()->user()->isSuperAdmin() || auth()->user()->isFAT())
                                            <button type="button" onclick="openEditModal({{ $department->id }}, '{{ addslashes($department->code) }}', '{{ addslashes($department->name) }}', '{{ $department->budget_ratio_percent }}', '{{ addslashes($department->odoo_analytic_id ?? '') }}', '{{ addslashes($department->head_name ?? '') }}', '{{ addslashes($department->description ?? '') }}', {{ $department->is_active ? 'true' : 'false' }})" title="Edit" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition">
                                                ✏️
                                            </button>
                                            <button type="button" onclick="openDeleteModal({{ $department->id }}, '{{ addslashes($department->name) }}')" title="Hapus" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 transition">
                                                🗑️
                                            </button>
                                        @endif
                                        <span class="rounded-lg border px-2 py-1 text-xs font-semibold {{ $department->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-100 text-slate-600' }}">{{ $department->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </div>
                                </td>
                            </tr>

                            <tr id="{{ $detailRowId }}" data-dept-detail-row class="hidden border-b border-slate-200 bg-slate-50/70">
                                <td colspan="6" class="px-5 py-4">
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
                                        </div>

                                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="text-sm font-semibold text-slate-800">Daftar Kategori Cost</h4>
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center justify-center rounded-md bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                                        <span id="cat-count-{{ $department->id }}">{{ $department->costCategories->count() }}</span>&nbsp;Kategori
                                                    </span>
                                                    <button type="button" onclick="document.getElementById('add-cat-form-{{ $department->id }}').classList.toggle('hidden')" class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition text-xs font-bold" title="Tambah Kategori">+</button>
                                                </div>
                                            </div>

                                            {{-- CREATE FORM (uses JS to avoid nested form issue) --}}
                                            <div id="add-cat-form-{{ $department->id }}" class="hidden mb-3 rounded-lg border border-indigo-200 bg-indigo-50/50 p-3">
                                                <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest mb-2">Tambah Kategori Baru</p>
                                                <div class="grid grid-cols-3 gap-2 mb-2">
                                                    <input type="text" id="new-cat-code-{{ $department->id }}" placeholder="Kode" class="rounded-md border-slate-200 text-xs py-1 px-2">
                                                    <input type="text" id="new-cat-name-{{ $department->id }}" placeholder="Nama Kategori" class="rounded-md border-slate-200 text-xs py-1 px-2">
                                                    <div class="flex items-center gap-1">
                                                        <input type="number" id="new-cat-ratio-{{ $department->id }}" step="0.01" min="0" max="100" placeholder="Rasio" class="w-full rounded-md border-slate-200 text-xs py-1 px-2">
                                                        <span class="text-xs text-slate-400">%</span>
                                                    </div>
                                                </div>
                                                <div class="flex gap-2">
                                                    <input type="text" id="new-cat-desc-{{ $department->id }}" placeholder="Deskripsi (opsional)" class="flex-1 rounded-md border-slate-200 text-xs py-1 px-2">
                                                    <button type="button" onclick="submitNewCategory({{ $department->id }})" class="rounded-md bg-indigo-600 text-white px-3 py-1 text-xs font-bold hover:bg-indigo-700 transition">Simpan</button>
                                                </div>
                                            </div>
                                            
                                            <div class="max-h-48 overflow-y-auto pr-3">
                                                <table class="min-w-full text-xs">
                                                    <thead class="sticky top-0 bg-white">
                                                        <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wide text-[10px]">
                                                            <th class="py-2 pr-2 text-left font-semibold">Kode</th>
                                                            <th class="py-2 pr-2 text-left font-semibold">Nama Kategori</th>
                                                            <th class="py-2 text-right font-semibold">Rasio (%)</th>
                                                            <th class="py-2 pl-2 text-right font-semibold">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="cat-table-body-{{ $department->id }}">
                                                        @forelse($department->costCategories as $category)
                                                            <tr class="border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition-colors" id="cat-row-{{ $category->id }}">
                                                                <td class="py-2 pr-2 font-mono text-[10px] text-slate-500">{{ $category->code }}</td>
                                                                <td class="py-2 pr-2 font-medium text-slate-700">{{ $category->name }}</td>
                                                                <td class="py-2 text-right">
                                                                    <div class="flex items-center justify-end gap-1" id="ratio-cell-{{ $category->id }}">
                                                                        <input
                                                                            type="number"
                                                                            step="0.01" min="0" max="100"
                                                                            value="{{ number_format((float) $category->budget_ratio_percent, 2, '.', '') }}"
                                                                            class="w-16 rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-right text-xs font-bold text-slate-700 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-300 focus:outline-none"
                                                                            id="ratio-input-{{ $category->id }}"
                                                                        />
                                                                        <span class="text-xs text-slate-400">%</span>
                                                                        <button
                                                                            type="button"
                                                                            title="Simpan Rasio"
                                                                            onclick="saveRatio({{ $category->id }}, '{{ route('monitoring.categories.update', $category->id) }}')"
                                                                            class="flex h-5 w-5 items-center justify-center rounded bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition text-[10px] font-bold">
                                                                            ✓
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                                <td class="py-2 pl-2 pr-2 text-right">
                                                                    <div class="flex items-center justify-end gap-1">
                                                                        <button type="button" title="Edit" onclick="openEditCatModal({{ $category->id }}, '{{ addslashes($category->code) }}', '{{ addslashes($category->name) }}', '{{ $category->budget_ratio_percent }}', '{{ addslashes($category->description ?? '') }}', {{ $department->id }})" class="flex h-5 w-5 items-center justify-center rounded bg-amber-100 text-amber-700 hover:bg-amber-200 transition text-[10px]">✏️</button>
                                                                        <button type="button" title="Hapus" onclick="deleteCategory({{ $category->id }}, '{{ addslashes($category->name) }}', {{ $department->id }})" class="flex h-5 w-5 items-center justify-center rounded bg-rose-100 text-rose-700 hover:bg-rose-200 transition text-[10px]">🗑️</button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr id="cat-empty-{{ $department->id }}">
                                                                <td colspan="4" class="py-4 text-center text-slate-500 bg-slate-50 rounded-lg italic">
                                                                    Departemen ini belum memiliki kategori cost.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada data departemen untuk fiscal year aktif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($departments->isNotEmpty())
                        <tfoot>
                            <tr class="border-t border-slate-300 bg-slate-100 font-semibold text-slate-700">
                                <td class="px-5 py-3">Total</td>
                                <td class="px-5 py-3">Rp {{ number_format($displayedBudgetTotal, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 {{ abs($displayedWeightTotal - 100) < 0.01 ? 'text-emerald-700' : 'text-amber-700' }}">{{ number_format($displayedWeightTotal, 2, ',', '.') }}%</td>
                                <td class="px-5 py-3">—</td>
                                <td class="px-5 py-3"></td>
                                <td class="px-5 py-3"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-slate-200 px-5 py-3 text-xs text-slate-500">
                <p>Menampilkan {{ $displayedDepartmentCount }} dari {{ $totalDepartmentCount }} departemen</p>
                <p>Total bobot saat ini: <span class="font-semibold text-slate-700">{{ number_format($totalWeightAssigned, 2, ',', '.') }}%</span></p>
            </div>
        </form>
    </div>
    </div>

    <div id="modal-realisasi-dept" class="hidden fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4">
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
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                                <p class="text-[10px] uppercase tracking-wide text-slate-500">Total Alokasi</p>
                                <p id="realisasi-allocated" class="text-sm font-bold text-slate-900 mt-1">Rp 0</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                                <p class="text-[10px] uppercase tracking-wide text-slate-500">Total Pengeluaran</p>
                                <p id="realisasi-used" class="text-sm font-bold text-slate-900 mt-1">Rp 0</p>
                            </div>
                            <div class="rounded-xl bg-blue-50 border border-blue-100 p-3">
                                <p class="text-[10px] uppercase tracking-wide text-blue-500">Sisa Anggaran</p>
                                <p id="realisasi-remaining" class="text-sm font-bold text-blue-600 mt-1">Rp 0</p>
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

                    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
                        <div class="space-y-4">
                            <form id="realisasi-ratio-form" method="POST" action="" class="space-y-1.5">
                                @csrf
                                @method('PATCH')
                                <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Update Rasio Dept</label>
                                <div class="flex gap-2">
                                    <input id="realisasi-ratio-input" type="number" name="budget_ratio_percent" step="0.01" min="0" max="100" class="rounded-xl border-slate-200 py-1.5 text-xs flex-1" placeholder="Ratio %" required>
                                    <button class="rounded-xl bg-slate-900 text-white px-4 py-1.5 text-xs font-bold">Update</button>
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
                                <button class="rounded-xl bg-indigo-600 text-white px-4 py-1.5 text-xs font-bold w-full">Set Override</button>
                            </form>
                        </div>
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
                        <div class="flex items-center justify-end mb-2 gap-2">
                            <div class="text-xs text-slate-500 font-semibold">Real | Std</div>
                            <div class="text-xs inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-semibold js-realisasi-std-percent">0%</div>
                        </div>
                        <div id="realisasi-donut-legend" class="space-y-1 text-sm"></div>
                    </div>

                    <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-sm">
                        <h4 class="text-lg font-semibold text-slate-900 mb-3">Peringatan Anggaran</h4>
                        <div id="realisasi-alerts" class="space-y-2"></div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL: CREATE DEPARTMENT (superadmin & fat)                  --}}
    {{-- ============================================================ --}}
    @if(auth()->user()->isSuperAdmin() || auth()->user()->isFAT())
    <div id="modal-create-dept" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('modal-create-dept').classList.add('hidden')"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden animate-in">
            <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4">
                <h3 class="text-lg font-bold text-white">Tambah Departemen Baru</h3>
                <p class="text-sm text-emerald-100">Isi detail departemen untuk FY{{ $activeFiscalYear?->year ?? now()->year }}</p>
            </div>
            <form method="POST" action="{{ route('fat.departments.store') }}" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-black mb-1">Kode Departemen <span class="text-rose-600">*</span></label>
                        <input type="text" name="code" placeholder="FIN, HR..." class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-black mb-1">Nama Departemen <span class="text-rose-600">*</span></label>
                        <input type="text" name="name" placeholder="Finance & Accounting" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-black mb-1">Bobot Alokasi (%) <span class="text-rose-600">*</span></label>
                        <input type="number" name="budget_ratio_percent" step="0.01" min="0" max="100" placeholder="0.00" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-black mb-1">Kepala Departemen</label>
                        <input type="text" name="head_name" placeholder="Nama kepala departemen" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-black mb-1">Deskripsi</label>
                    <textarea name="description" rows="2" placeholder="Deskripsi singkat departemen..." class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('modal-create-dept').classList.add('hidden')" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 transition shadow-lg shadow-emerald-200">Simpan Departemen</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL: EDIT DEPARTMENT (superadmin only)                     --}}
    {{-- ============================================================ --}}
    <div id="modal-edit-dept" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('modal-edit-dept').classList.add('hidden')"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden animate-in">
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                <h3 class="text-lg font-bold text-white">Edit Departemen</h3>
                <p class="text-sm text-indigo-100">Perbarui informasi departemen</p>
            </div>
            <form id="form-edit-dept" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PATCH')
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-black mb-1">Kode Departemen <span class="text-rose-600">*</span></label>
                        <input type="text" name="code" id="edit-code" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-black mb-1">Nama Departemen <span class="text-rose-600">*</span></label>
                        <input type="text" name="name" id="edit-name" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-black mb-1">Bobot Alokasi (%) <span class="text-rose-600">*</span></label>
                        <input type="number" name="budget_ratio_percent" id="edit-ratio" step="0.01" min="0" max="100" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-black mb-1">Kepala Departemen</label>
                        <input type="text" name="head_name" id="edit-head" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-black mb-1">Deskripsi</label>
                    <textarea name="description" id="edit-desc" rows="2" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-black mb-1">Status</label>
                    <select name="is_active" id="edit-active" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('modal-edit-dept').classList.add('hidden')" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Perbarui</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL: DELETE CONFIRMATION (superadmin only)                  --}}
    {{-- ============================================================ --}}
    <div id="modal-delete-dept" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="document.getElementById('modal-delete-dept').classList.add('hidden')"></div>
        <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden animate-in">
            <div class="bg-gradient-to-r from-rose-600 to-rose-700 px-6 py-4">
                <h3 class="text-lg font-bold text-white">Hapus Departemen</h3>
            </div>
            <div class="p-6">
                <div class="flex items-start gap-3 mb-4">
                    <div class="mt-0.5 inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 text-lg">⚠</div>
                    <div>
                        <p class="text-sm text-slate-700">Apakah Anda yakin ingin menghapus departemen:</p>
                        <p id="delete-dept-name" class="mt-1 text-base font-bold text-slate-900"></p>
                        <p class="mt-2 text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan. Semua data terkait departemen ini akan turut terhapus.</p>
                    </div>
                </div>
                <form id="form-delete-dept" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('modal-delete-dept').classList.add('hidden')" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Batal</button>
                        <button type="submit" class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 transition shadow-lg shadow-rose-200">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const departmentPopupData = @json($departmentPopupData ?? []);
            let realisasiLineChart = null;
            let realisasiDonutChart = null;

            const formatCurrency = (value) => {
                const number = Number(value || 0);
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
            };

            function renderRealisasiLineChart(categories) {
                const canvas = document.getElementById('realisasi-line-chart');
                if (!canvas) {
                    return;
                }

                if (realisasiLineChart) {
                    realisasiLineChart.destroy();
                    realisasiLineChart = null;
                }

                if (typeof Chart === 'undefined') {
                    const parent = canvas.parentElement;
                    if (parent) {
                        parent.innerHTML = '<div class="h-full flex items-center justify-center text-sm text-slate-500">Grafik tidak tersedia.</div>';
                    }
                    return;
                }

                const labels = categories.map((category) => category.name ?? '-');
                const values = categories.map((category) => Number(category.used || 0));

                realisasiLineChart = new Chart(canvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Pengeluaran',
                            data: values,
                            borderColor: '#6366F1',
                            backgroundColor: 'rgba(99, 102, 241, 0.15)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4,
                            pointBackgroundColor: '#6366F1',
                            pointBorderColor: '#6366F1',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + Number(value).toLocaleString('id-ID');
                                    }
                                }
                            },
                            x: {
                                ticks: {
                                    maxRotation: 0,
                                    autoSkip: true,
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                }
                            }
                        }
                    }
                });
            }

            function renderRealisasiDonut(categories, deptAllocated = 0) {
                const canvas = document.getElementById('realisasi-donut-chart');
                const legendHolder = document.getElementById('realisasi-donut-legend');
                if (!canvas) {
                    return;
                }

                if (realisasiDonutChart) {
                    realisasiDonutChart.destroy();
                    realisasiDonutChart = null;
                }

                if (typeof Chart === 'undefined') {
                    return;
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
                        options: {
                            cutout: '65%',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: false }
                            }
                        }
                    });

                    if (legendHolder) {
                        legendHolder.innerHTML = '<p class="text-center text-xs text-slate-400">Belum ada realisasi</p>';
                    }
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
                                        let label = context.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += 'Rp ' + context.raw.toLocaleString('id-ID');
                                        return label;
                                    }
                                }
                            }
                        }
                    }
                });

                if (legendHolder) {
                    const totalAllocated = categories.reduce((s, r) => s + Number(r.allocated || 0), 0);
                    legendHolder.innerHTML = categories.map((category) => {
                        const index = nonZeroCategories.findIndex((row) => row.name === category.name);
                        const color = index >= 0 ? palette[index % palette.length] : '#94A3B8';
                        const share = total > 0 ? ((Number(category.used || 0) / total) * 100) : 0;
                        const allocated = Number(category.allocated || 0);
                        let percentOfDept = 0;
                        if (category && category.budget_ratio_percent != null && category.budget_ratio_percent !== '') {
                            percentOfDept = Number(category.budget_ratio_percent);
                        } else if (allocated > 0 && deptAllocated > 0) {
                            percentOfDept = (allocated / deptAllocated) * 100;
                        } else {
                            percentOfDept = 0;
                        }
                        return `
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="inline-block h-2.5 w-2.5 rounded-full" style="background:${color}"></span>
                                    <div class="min-w-0">
                                        <div class="truncate text-slate-600">${category.name ?? '-'}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-slate-700">${Math.round(share)}% | ${Math.round(percentOfDept)}%</div>
                                </div>
                            </div>
                        `;
                    }).join('');
                    try {
                        const stdBadge = document.querySelector('.js-realisasi-std-percent');
                        if (stdBadge) {
                            const stdPercent = deptAllocated > 0 ? (totalAllocated / deptAllocated) * 100 : 0;
                            stdBadge.textContent = Math.round(stdPercent) + '%';
                        }
                    } catch (e) {}
                }
            }

            function closeRealisasiPopup() {
                document.getElementById('modal-realisasi-dept')?.classList.add('hidden');
            }

            function openRealisasiPopup(departmentId) {
                if (window.DISABLE_REALISASI_POPUP) {
                    console.debug('Realisasi popup disabled by global flag');
                    return;
                }
                const data = departmentPopupData?.[departmentId];
                if (!data) {
                    alert('Detail realisasi departemen tidak tersedia.');
                    return;
                }

                const titleEl = document.getElementById('realisasi-title');
                const statusEl = document.getElementById('realisasi-status');
                const allocatedEl = document.getElementById('realisasi-allocated');
                const usedEl = document.getElementById('realisasi-used');
                const remainingEl = document.getElementById('realisasi-remaining');
                const utilizationEl = document.getElementById('realisasi-utilization');
                const progressEl = document.getElementById('realisasi-progress');
                const topCategoryEl = document.getElementById('realisasi-top-category');
                const alertsEl = document.getElementById('realisasi-alerts');
                const ratioFormEl = document.getElementById('realisasi-ratio-form');
                const ratioInputEl = document.getElementById('realisasi-ratio-input');
                const overrideFormEl = document.getElementById('realisasi-override-form');
                const overrideMonthEl = document.getElementById('realisasi-override-month');

                const deptBasePath = "{{ url('fat/departments') }}";
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

                titleEl.textContent = 'Detail Realisasi - ' + (data.department_name ?? 'Departemen');
                allocatedEl.textContent = formatCurrency(data.allocated);
                usedEl.textContent = formatCurrency(data.used);
                remainingEl.textContent = formatCurrency(data.remaining);
                utilizationEl.textContent = (Number(data.utilization || 0)).toFixed(2).replace('.', ',') + '%';

                const utilizationRaw = Number(data.utilization || 0);
                const utilization = Math.max(0, Math.min(100, utilizationRaw));
                progressEl.style.width = utilization + '%';
                progressEl.className = 'h-3 rounded-full ' + (utilizationRaw > 100 ? 'bg-rose-600' : (utilizationRaw <= 20 ? 'bg-amber-500' : (utilizationRaw <= 80 ? 'bg-emerald-500' : 'bg-amber-500')));

                const isOver = String(data.status_label || '').toLowerCase().includes('over');
                statusEl.textContent = data.status_label || '-';
                statusEl.className = 'rounded-full px-3 py-1 text-xs font-semibold ' + (isOver
                    ? 'bg-rose-100 text-rose-700'
                    : 'bg-emerald-100 text-emerald-700');

                const categories = Array.isArray(data.categories) ? data.categories : [];
                topCategoryEl.textContent = data.top_category || '-';

                renderRealisasiLineChart(categories);
                renderRealisasiDonut(categories, Number(data.allocated || 0));

                const alerts = Array.isArray(data.alerts) ? data.alerts : [];
                alertsEl.innerHTML = alerts.length
                    ? alerts.map((alertRow) => `
                        <div class="rounded-lg border ${Number(alertRow.utilization || 0) >= 100 ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50'} p-3">
                            <div class="flex items-start gap-3">
                                <div class="pt-0.5">
                                    <span class="inline-block h-3.5 w-3.5 rounded-full" style="background:${Number(alertRow.utilization || 0) >= 100 ? '#FDE8E8' : '#FFF7ED'}"></span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold ${Number(alertRow.utilization || 0) >= 100 ? 'text-rose-700' : 'text-amber-700'}">${alertRow.name ?? '-'} · ${Number(alertRow.share_percent || 0).toFixed(1).replace('.', ',')}%</p>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-sm ${Number(alertRow.utilization || 0) >= 100 ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800'}">${Number(alertRow.utilization || 0) >= 100 ? 'Overbudget' : 'Kritis'}</span>
                                    </div>
                                    <p class="text-sm ${Number(alertRow.utilization || 0) >= 100 ? 'text-rose-700' : 'text-amber-700'} mt-1">Realisasi ${formatCurrency(alertRow.used)}</p>
                                </div>
                            </div>
                        </div>
                    `).join('')
                    : `
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                            <div class="inline-flex items-center gap-3">
                                <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-emerald-100 text-emerald-700 font-semibold">✓</span>
                                <div>
                                    <p class="text-sm font-semibold text-emerald-700">Aman</p>
                                    <p class="text-sm text-emerald-600 mt-1">Belum ada item dengan pengeluaran tinggi.</p>
                                </div>
                            </div>
                        </div>
                    `;

                document.getElementById('modal-realisasi-dept')?.classList.remove('hidden');
            }

            document.addEventListener('DOMContentLoaded', () => {
                // ── Row expand / collapse toggle ──
                const toggleButtons = document.querySelectorAll('[data-dept-toggle]');
                const detailRows = document.querySelectorAll('[data-dept-detail-row]');

                toggleButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const targetId = button.getAttribute('data-dept-toggle');
                        const targetRow = document.getElementById(targetId);
                        if (!targetRow) return;

                        const isOpening = targetRow.classList.contains('hidden');

                        detailRows.forEach((row) => {
                            if (row.id !== targetId) {
                                row.classList.add('hidden');
                            }
                        });

                        toggleButtons.forEach((btn) => {
                            const chevron = btn.querySelector('[data-dept-chevron]');
                            btn.setAttribute('aria-expanded', 'false');
                            if (chevron) {
                                chevron.classList.remove('rotate-180');
                            }
                        });

                        targetRow.classList.toggle('hidden');

                        if (isOpening) {
                            button.setAttribute('aria-expanded', 'true');
                            const activeChevron = button.querySelector('[data-dept-chevron]');
                            if (activeChevron) {
                                activeChevron.classList.add('rotate-180');
                            }
                        }
                    });
                });

                const clickableRows = document.querySelectorAll('[data-dept-click-row]');
                clickableRows.forEach((row) => {
                    row.addEventListener('click', (event) => {
                        const clickedInteractiveElement = event.target.closest('input, select, textarea, button, a, label');
                        if (clickedInteractiveElement) {
                            return;
                        }

                        const targetId = row.getAttribute('data-dept-click-row');
                        const relatedButton = document.querySelector(`[data-dept-toggle="${targetId}"]`);
                        if (relatedButton) {
                            relatedButton.click();
                        }
                    });
                });
            });

            // ── Modal helpers (superadmin) ──
            const deptBaseUrl = "{{ url('fat/departments') }}";

            function openEditModal(id, code, name, ratio, odoo, head, desc, isActive) {
                document.getElementById('form-edit-dept').action = deptBaseUrl + '/' + id;
                document.getElementById('edit-code').value = code;
                document.getElementById('edit-name').value = name;
                document.getElementById('edit-ratio').value = ratio;
                document.getElementById('edit-ratio').value = ratio;
                document.getElementById('edit-head').value = head;
                document.getElementById('edit-desc').value = desc;
                document.getElementById('edit-active').value = isActive ? '1' : '0';
                document.getElementById('modal-edit-dept').classList.remove('hidden');
            }

            function openDeleteModal(id, name) {
                document.getElementById('form-delete-dept').action = deptBaseUrl + '/' + id;
                document.getElementById('delete-dept-name').textContent = name;
                document.getElementById('modal-delete-dept').classList.remove('hidden');
            }

            async function saveRatio(categoryId, url) {
                const input = document.getElementById('ratio-input-' + categoryId);
                const btn = input.nextElementSibling.nextElementSibling;
                const newRatio = parseFloat(input.value);
                if (isNaN(newRatio) || newRatio < 0 || newRatio > 100) {
                    input.classList.add('border-rose-400');
                    return;
                }
                btn.disabled = true;
                btn.textContent = '…';

                const csrfToken = (document.querySelector('meta[name="csrf-token"]')?.content)
                    ?? (document.querySelector('input[name="_token"]')?.value)
                    ?? '';
                const formData = new FormData();
                formData.append('_method', 'PATCH');
                formData.append('_token', csrfToken);
                formData.append('budget_ratio_percent', newRatio);

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData
                    });
                    if (res.ok || res.redirected) {
                        const json = await res.json().catch(() => ({}));
                        btn.textContent = '✓';
                        btn.classList.remove('bg-indigo-100', 'text-indigo-700');
                        btn.classList.add('bg-emerald-100', 'text-emerald-700');
                        input.classList.remove('border-rose-400');

                        // Update the department ratio input in the main table row
                        if (json.new_dept_ratio !== undefined && json.dept_id !== undefined) {
                            const deptHidden = document.querySelector(`input[name*="department_id"][value="${json.dept_id}"]`);
                            if (deptHidden) {
                                const deptInput = deptHidden.closest('td')
                                    ?.querySelector('input[name*="budget_ratio_percent"]');
                                if (deptInput) {
                                    deptInput.value = json.new_dept_ratio.toFixed(2);
                                    deptInput.style.transition = 'background 0.5s';
                                    deptInput.style.background = '#d1fae5';
                                    setTimeout(() => { deptInput.style.background = ''; }, 2000);
                                }
                            }
                        }

                        setTimeout(() => {
                            btn.classList.remove('bg-emerald-100', 'text-emerald-700');
                            btn.classList.add('bg-indigo-100', 'text-indigo-700');
                        }, 2000);
                    } else {
                        const err = await res.json().catch(() => ({}));
                        btn.textContent = '✗';
                        input.classList.add('border-rose-400');
                        alert(err.message ?? 'Gagal menyimpan. Periksa nilai rasio.');
                    }
                } catch(e) {
                    btn.textContent = '✗';
                    alert('Gagal menghubungi server.');
                }
                btn.disabled = false;
            }
        </script>
    @endpush

    {{-- EDIT CATEGORY MODAL --}}
    <div id="modal-edit-cat" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeEditCatModal()"></div>
        <div class="relative w-[420px] max-w-full rounded-2xl bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-slate-900 mb-4">Edit Kategori Cost</h3>
            <form id="edit-cat-form" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="space-y-3">
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Kode</label>
                        <input type="text" name="code" id="edit-cat-code" class="w-full rounded-lg border-slate-200 text-sm py-2 mt-1" required>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Nama Kategori</label>
                        <input type="text" name="name" id="edit-cat-name" class="w-full rounded-lg border-slate-200 text-sm py-2 mt-1" required>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Rasio (%)</label>
                        <input type="number" name="budget_ratio_percent" id="edit-cat-ratio" step="0.01" min="0" max="100" class="w-full rounded-lg border-slate-200 text-sm py-2 mt-1" required>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Deskripsi</label>
                        <input type="text" name="description" id="edit-cat-desc" class="w-full rounded-lg border-slate-200 text-sm py-2 mt-1">
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" onclick="closeEditCatModal()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 text-white px-4 py-2 text-xs font-bold hover:bg-indigo-700">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function toSameOrigin(url) {
            const parsed = new URL(url, window.location.origin);
            return window.location.origin + parsed.pathname + parsed.search;
        }

        const categoryBaseUrl = toSameOrigin("{{ url('/fat/categories') }}");
        const departmentBaseUrl = toSameOrigin("{{ url('/fat/departments') }}");
        const monitoringCategoryBaseUrl = toSameOrigin("{{ url('/monitoring/categories') }}");

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function updateDepartmentRatioInput(deptId, newRatio) {
            if (newRatio === undefined || deptId === undefined) return;
            const deptHidden = document.querySelector(`input[name*="department_id"][value="${deptId}"]`);
            if (!deptHidden) return;
            const deptInput = deptHidden.closest('td')?.querySelector('input[name*="budget_ratio_percent"]');
            if (!deptInput) return;

            deptInput.value = Number(newRatio).toFixed(2);
            deptInput.style.transition = 'background 0.5s';
            deptInput.style.background = '#d1fae5';
            setTimeout(() => { deptInput.style.background = ''; }, 1800);
        }

        function updateCategoryCount(deptId, delta) {
            const countEl = document.getElementById('cat-count-' + deptId);
            if (!countEl) return;
            const current = parseInt(countEl.textContent || '0', 10);
            countEl.textContent = String(Math.max(0, current + delta));
        }

        function openEditCatModal(catId, code, name, ratio, desc, deptId) {
            const modal = document.getElementById('modal-edit-cat');
            const form = document.getElementById('edit-cat-form');
            form.action = categoryBaseUrl + '/' + catId;
            document.getElementById('edit-cat-code').value = code;
            document.getElementById('edit-cat-name').value = name;
            document.getElementById('edit-cat-ratio').value = parseFloat(ratio).toFixed(2);
            document.getElementById('edit-cat-desc').value = desc;
            modal.classList.remove('hidden');
        }
        function closeEditCatModal() {
            document.getElementById('modal-edit-cat').classList.add('hidden');
        }

        async function submitNewCategory(deptId) {
            const code  = document.getElementById('new-cat-code-' + deptId).value.trim();
            const name  = document.getElementById('new-cat-name-' + deptId).value.trim();
            const ratio = document.getElementById('new-cat-ratio-' + deptId).value;
            const desc  = document.getElementById('new-cat-desc-' + deptId).value.trim();

            if (!code || !name || !ratio) {
                alert('Kode, Nama, dan Rasio wajib diisi.');
                return;
            }

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('code', code);
            formData.append('name', name);
            formData.append('budget_ratio_percent', ratio);
            formData.append('description', desc);

            try {
                const targetUrl = departmentBaseUrl + '/' + deptId + '/categories';
                
                const res = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: formData
                });
                if (res.status < 400) {
                    const json = await res.json().catch(() => ({}));
                    updateDepartmentRatioInput(json.dept_id, json.new_dept_ratio);

                    if (json.category?.id) {
                        const tbody = document.getElementById('cat-table-body-' + deptId);
                        if (tbody) {
                            const emptyRow = document.getElementById('cat-empty-' + deptId);
                            if (emptyRow) emptyRow.remove();

                            const ratioValue = Number(json.category.budget_ratio_percent || 0).toFixed(2);
                            const categoryId = json.category.id;
                            const row = document.createElement('tr');
                            row.id = 'cat-row-' + categoryId;
                            row.className = 'border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition-colors';
                            row.innerHTML = `
                                <td class="py-2 pr-2 font-mono text-[10px] text-slate-500">${escapeHtml(json.category.code)}</td>
                                <td class="py-2 pr-2 font-medium text-slate-700">${escapeHtml(json.category.name)}</td>
                                <td class="py-2 text-right">
                                    <div class="flex items-center justify-end gap-1" id="ratio-cell-${categoryId}">
                                        <input type="number" step="0.01" min="0" max="100" value="${ratioValue}" class="w-16 rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-right text-xs font-bold text-slate-700 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-300 focus:outline-none" id="ratio-input-${categoryId}" />
                                        <span class="text-xs text-slate-400">%</span>
                                        <button type="button" title="Simpan Rasio" onclick="saveRatio(${categoryId}, '${monitoringCategoryBaseUrl}/${categoryId}')" class="flex h-5 w-5 items-center justify-center rounded bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition text-[10px] font-bold">✓</button>
                                    </div>
                                </td>
                                <td class="py-2 pl-2 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" title="Edit" onclick="openEditCatModal(${categoryId}, '${escapeHtml(json.category.code)}', '${escapeHtml(json.category.name)}', '${ratioValue}', '${escapeHtml(json.category.description || '')}', ${deptId})" class="flex h-5 w-5 items-center justify-center rounded bg-amber-100 text-amber-700 hover:bg-amber-200 transition text-[10px]">✏️</button>
                                        <button type="button" title="Hapus" onclick="deleteCategory(${categoryId}, '${escapeHtml(json.category.name)}', ${deptId})" class="flex h-5 w-5 items-center justify-center rounded bg-rose-100 text-rose-700 hover:bg-rose-200 transition text-[10px]">🗑️</button>
                                    </div>
                                </td>
                            `;

                            tbody.prepend(row);
                            updateCategoryCount(deptId, 1);
                        }
                    } else {
                        // Fallback for non-JSON/redirect responses from backend
                        location.reload();
                        return;
                    }

                    const formWrap = document.getElementById('add-cat-form-' + deptId);
                    if (formWrap) formWrap.classList.add('hidden');
                    document.getElementById('new-cat-code-' + deptId).value = '';
                    document.getElementById('new-cat-name-' + deptId).value = '';
                    document.getElementById('new-cat-ratio-' + deptId).value = '';
                    document.getElementById('new-cat-desc-' + deptId).value = '';
                } else {
                    const err = await res.json().catch(() => ({}));
                    alert(err.message || Object.values(err.errors || {}).flat().join('\n') || 'Gagal menyimpan kategori.');
                }
            } catch(e) {
                alert('Gagal menghubungi server.');
            }
        }

        async function deleteCategory(catId, catName, deptId) {
            if (!confirm('Hapus kategori ' + catName + '?')) return;

            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('_method', 'DELETE');

            try {
                const targetUrl = categoryBaseUrl + '/' + catId;
                
                const res = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: formData
                });
                if (res.status < 400) {
                    const json = await res.json().catch(() => ({}));
                    updateDepartmentRatioInput(json.dept_id, json.new_dept_ratio);

                    const deletedId = Number(json.deleted_category_id || catId);
                    const row = document.getElementById('cat-row-' + deletedId);
                    if (row) row.remove();
                    if (deptId) {
                        updateCategoryCount(deptId, -1);
                        const tbody = document.getElementById('cat-table-body-' + deptId);
                        if (tbody && !tbody.querySelector('tr[id^="cat-row-"]')) {
                            const empty = document.createElement('tr');
                            empty.id = 'cat-empty-' + deptId;
                            empty.innerHTML = '<td colspan="4" class="py-4 text-center text-slate-500 bg-slate-50 rounded-lg italic">Departemen ini belum memiliki kategori cost.</td>';
                            tbody.appendChild(empty);
                        }
                    }

                    if (!json.success) {
                        location.reload();
                        return;
                    }
                } else {
                    const err = await res.json().catch(() => ({}));
                    alert(err.message || Object.values(err.errors || {}).flat().join('\n') || 'Gagal menghapus kategori.');
                }
            } catch(e) {
                alert('Gagal menghubungi server.');
            }
        }
    </script>
    @endpush

    <style>
        .animate-in {
            animation: modalIn 0.2s ease-out;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
@endsection
