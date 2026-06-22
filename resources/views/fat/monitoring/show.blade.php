@extends('layouts.dashboard', ['title' => 'Rincian Pengeluaran'])

@section('content')
    <div class="mx-auto max-w-5xl">
        {{-- Header --}}
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                    <a href="{{ route('monitoring.index', ['department_id' => $category->department_id]) }}"
                        class="hover:text-blue-600 transition">Monitoring Budget</a>
                    <span>/</span>
                    <span>{{ $category->code }}</span>
                </div>
                <h2 class="text-2xl font-bold text-slate-900">{{ $category->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">
                    <span class="font-semibold text-indigo-600">{{ $category->department->name }}</span> · FY{{ $activeFiscalYear->year }}
                </p>
            </div>

            <div class="flex items-center gap-3">
                <form action="{{ route('monitoring.categories.show', $category->id) }}" method="GET"
                    class="flex items-center gap-2 bg-white p-1.5 rounded-lg border border-slate-300 shadow-sm">
                    <select name="month" onchange="this.form.submit()"
                        class="rounded-md border-0 bg-transparent text-sm font-medium text-slate-600 py-1.5 focus:ring-0 cursor-pointer">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                    <select name="year" onchange="this.form.submit()"
                        class="rounded-md border-0 bg-transparent text-sm font-medium text-slate-600 py-1.5 focus:ring-0 cursor-pointer">
                        <option value="{{ $activeFiscalYear->year }}" selected>{{ $activeFiscalYear->year }}</option>
                    </select>
                </form>

                @if($is_fat_or_superadmin)
                <button type="button" onclick="document.getElementById('modal-add-expense-show').classList.remove('hidden')"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition shadow-sm">
                    <span>＋</span>
                    <span>Catat Pengeluaran</span>
                </button>
                @endif
                <a href="{{ route('monitoring.index', ['department_id' => $category->department_id, 'month' => $selectedMonth, 'year' => $selectedYear]) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    <span>←</span>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        {{-- Category Summary --}}
        @php
            $status_color = match ($category->status) {
                'danger' => 'text-rose-700',
                'warning' => 'text-amber-600',
                default => 'text-emerald-600',
            };
            $status_border = match ($category->status) {
                'danger' => 'border-rose-200 bg-rose-50/30',
                'warning' => 'border-amber-200 bg-amber-50/30',
                default => 'border-emerald-200 bg-emerald-50/30',
            };
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Target ({{ $currentMonthName }})</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">Rp
                    {{ number_format($category->calculated_budget, 0, ',', '.') }}
                </p>
                <div class="mt-1 text-xs text-slate-400">Rasio: {{ $category->budget_ratio_percent }}% dari Dept</div>
            </div>
            <div class="rounded-xl border {{ $status_border }} p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Terpakai ({{ $currentMonthName }})</p>
                <p class="mt-2 text-2xl font-bold {{ $status_color }}">Rp
                    {{ number_format($category->total_used, 0, ',', '.') }}
                </p>
                <div class="mt-1 text-xs {{ $status_color }} font-bold">{{ round($category->utilization, 1) }}% Kapasitas
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-wide text-slate-500">Sisa Anggaran</p>
                <p class="mt-2 text-2xl font-bold text-slate-700">Rp
                    {{ number_format($category->remaining, 0, ',', '.') }}
                </p>
                <div class="mt-1 text-xs text-slate-400">Available to spend</div>
            </div>
            <div class="rounded-xl border {{ $status_border }} p-5 shadow-sm flex flex-col justify-center">
                <p class="text-xs uppercase tracking-wide text-slate-500 mb-1">Status Cerdas</p>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ str_replace('text-', 'bg-', $status_color) }} bg-opacity-10 {{ $status_color }}">
                    {{ strtoupper($category->status === 'danger' ? 'CRITICAL' : ($category->status === 'warning' ? 'WARNING' : 'SUCCESS')) }}
                </span>
            </div>
        </div>
        @if(!$is_fat_or_superadmin)
        {{-- Visualisasi Progress Bar & Alokasi Anggaran --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm mb-6">
            <h3 class="font-semibold text-slate-800 mb-4">Visualisasi Penggunaan Anggaran</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-500 font-medium">Progress Terpakai</span>
                        <span class="font-semibold {{ $status_color }}">{{ round($category->utilization, 1) }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                        <div class="h-full rounded-full {{ str_replace('text-', 'bg-', $status_color) }}" style="width: {{ min($category->utilization, 100) }}%"></div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-100 text-center">
                    <div>
                        <p class="text-xs text-slate-400 uppercase font-semibold">Target</p>
                        <p class="text-sm font-bold text-slate-700 mt-1">Rp {{ number_format($category->calculated_budget, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase font-semibold">Terpakai</p>
                        <p class="text-sm font-bold text-slate-700 mt-1">Rp {{ number_format($category->total_used, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase font-semibold">Sisa</p>
                        <p class="text-sm font-bold text-slate-700 mt-1">Rp {{ number_format($category->remaining, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($stagingExpenses->isNotEmpty())
        {{-- Staging Expenses List --}}
        <div class="rounded-xl border border-amber-200 bg-white shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-amber-100 bg-amber-50/30 flex items-center justify-between">
                <h3 class="font-semibold text-amber-900 flex items-center gap-2">
                    <span>📦</span>
                    <span>Staging Pengeluaran Pagu (Procurement)</span>
                </h3>
                <span class="text-xs text-amber-700 bg-white px-2 py-1 rounded border border-amber-200 font-bold">
                    {{ $stagingExpenses->count() }} Staging
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-amber-800 uppercase bg-amber-50/40 border-b border-amber-100">
                        <tr>
                            <th class="px-6 py-3">Tanggal</th>
                            <th class="px-6 py-3">Referensi PR</th>
                            <th class="px-6 py-3">Deskripsi</th>
                            <th class="px-6 py-3 text-center">Status Staging</th>
                            <th class="px-6 py-3 text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-50">
                        @foreach($stagingExpenses as $staging)
                            @php
                                $isSynced = $expenses->contains(function ($e) use ($staging) {
                                    return $e->reference === $staging->reference;
                                });
                                if ($isSynced) {
                                    $badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    $label = 'Sudah Sinkron Odoo';
                                } elseif ($staging->status === 'bon') {
                                    $badge = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                                    $label = 'Reported Odoo (Menunggu Sync)';
                                } elseif ($staging->status === 'ignored') {
                                    $badge = 'bg-slate-50 text-slate-500 border-slate-200';
                                    $label = 'Diabaikan';
                                } else {
                                    $badge = 'bg-amber-50 text-amber-700 border-amber-200';
                                    $label = 'Pending (Belum Reported Odoo)';
                                }
                            @endphp
                            <tr class="hover:bg-amber-50/20 transition-colors">
                                <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                    {{ $staging->date->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 font-bold text-slate-900 whitespace-nowrap">
                                    {{ $staging->reference }}
                                </td>
                                <td class="px-6 py-4 text-slate-800">
                                    {{ $staging->description }}
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $badge }}">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-mono font-bold text-slate-900">
                                    Rp {{ number_format($staging->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Expenses List --}}
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">Daftar Pengeluaran ({{ $currentMonthName }})</h3>
                <span
                    class="text-xs text-slate-500 bg-white px-2 py-1 rounded border border-slate-200">{{ $expenses->count() }}
                    Transaksi</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-100">
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <th class="px-6 py-3">Tanggal</th>
                            <th class="px-6 py-3">Deskripsi</th>
                            <th class="px-6 py-3 text-right">Nominal</th>
                            @if($is_fat_or_superadmin)
                                <th class="px-6 py-3 text-center">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($expenses as $expense)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($expense->date)->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-slate-800 font-medium">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span>{{ $expense->description }}</span>
                                            @if($expense->odoo_move_line_id)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    Odoo Synced
                                                </span>
                                            @endif
                                        </div>
                                        @if($expense->odoo_move_line_id)
                                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400 mt-1.5 font-normal">
                                                @if($expense->reference)
                                                    <span><strong class="text-slate-500">Ref:</strong> {{ $expense->reference }}</span>
                                                @endif
                                                @if(isset($expense->odoo_data['account_id']) && is_array($expense->odoo_data['account_id']))
                                                    <span><strong class="text-slate-500">COA Odoo:</strong> {{ $expense->odoo_data['account_id'][1] }}</span>
                                                @endif
                                                <span><strong class="text-slate-500">Cost Center:</strong> {{ $expense->department->name }} ({{ $expense->department->code }})</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-slate-700">
                                    Rp {{ number_format($expense->amount, 0, ',', '.') }}
                                </td>
                                @if($is_fat_or_superadmin)
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            @if($expense->odoo_move_line_id)
                                                <span class="inline-flex items-center justify-center p-1.5 text-slate-400 bg-slate-50 border border-slate-100 rounded-lg cursor-not-allowed" title="Terkunci (Sinkron Odoo)">
                                                    🔒 <span class="text-[10px] ml-1 font-bold uppercase text-slate-400">Locked</span>
                                                </span>
                                            @else
                                                <button type="button"
                                                    onclick="openEditExpense({{ $expense->id }}, '{{ $expense->date }}', {{ $expense->amount }}, '{{ addslashes($expense->description) }}')"
                                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit">
                                                    ✏️
                                                </button>
                                                <form action="{{ route('monitoring.expenses.destroy', $expense->id) }}" method="POST"
                                                    onsubmit="return confirm('Hapus pengeluaran ini?')" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition"
                                                        title="Hapus">
                                                        🗑️
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $is_fat_or_superadmin ? 4 : 3 }}" class="px-6 py-12 text-center text-slate-400 italic">
                                    Belum ada data pengeluaran untuk kategori ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL ADD EXPENSE (from show page) --}}
    @if($is_fat_or_superadmin)
    <div id="modal-add-expense-show" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
            onclick="document.getElementById('modal-add-expense-show').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-in">
            <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4 flex justify-between items-center text-white">
                <div>
                    <h3 class="font-bold text-lg">Catat Pengeluaran</h3>
                    <p class="text-sm text-emerald-100 opacity-90">Kategori: {{ $category->name }}</p>
                </div>
                <button onclick="document.getElementById('modal-add-expense-show').classList.add('hidden')"
                    class="text-emerald-100 hover:text-white text-xl">✕</button>
            </div>
            <form id="form-add-expense-show" action="{{ route('monitoring.expenses.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="budget_category_id" value="{{ $category->id }}">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}"
                            class="w-full rounded-lg border-slate-300 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nominal (Rp)</label>
                        <input type="number" name="amount" id="add-expense-amount" min="0"
                            class="w-full rounded-lg border-slate-300 focus:ring-emerald-500 focus:border-emerald-500 text-sm font-mono"
                            placeholder="0" required>
                    </div>
                </div>

                {{-- Over-budget warning banner (hidden by default) --}}
                <div id="overbudget-warning" class="hidden rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    ⚠️ <strong>Perhatian:</strong> Nominal ini melebihi sisa anggaran kategori
                    (<span class="font-mono">Rp {{ number_format($category->remaining ?? 0, 0, ',', '.') }}</span>).
                    Lanjutkan tetap akan disimpan.
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi / Keterangan</label>
                    <textarea name="description" rows="3"
                        class="w-full rounded-lg border-slate-300 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
                        placeholder="Contoh: Pembelian ATK bulan Februari..." required></textarea>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modal-add-expense-show').classList.add('hidden')"
                        class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Batal</button>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-200">Simpan Pengeluaran</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- MODAL EDIT EXPENSE --}}
    <div id="modal-edit-expense" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
            onclick="document.getElementById('modal-edit-expense').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-in">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex justify-between items-center text-white">
                <h3 class="font-bold text-lg">Edit Pengeluaran</h3>
                <button onclick="document.getElementById('modal-edit-expense').classList.add('hidden')"
                    class="text-blue-100 hover:text-white text-xl">✕</button>
            </div>
            <form id="form-edit-expense" action="" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal</label>
                        <input type="date" name="date" id="edit-date"
                            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nominal (Rp)</label>
                        <input type="number" name="amount" id="edit-amount" min="0"
                            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm font-mono"
                            required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi / Keterangan</label>
                    <textarea name="description" id="edit-desc" rows="3"
                        class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        required></textarea>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modal-edit-expense').classList.add('hidden')"
                        class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50">Batal</button>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200">Update
                        Pengeluaran</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // Auto-refresh session setiap 15 menit untuk mencegah Page Expired (419)
            setInterval(function() {
                fetch(window.location.href, { method: 'HEAD' })
                    .catch(e => console.log('Keep-alive failed', e));
            }, 1000 * 60 * 15);

            function openEditExpense(id, date, amount, description) {
                const cleanDate = date.split(' ')[0];
                document.getElementById('form-edit-expense').action = "{{ url('monitoring/expenses') }}/" + id;
                document.getElementById('edit-date').value = cleanDate;
                document.getElementById('edit-amount').value = amount;
                document.getElementById('edit-desc').value = description;
                document.getElementById('modal-edit-expense').classList.remove('hidden');
            }

            // Over-budget live warning
            const remainingBudget = {{ $category->remaining ?? 0 }};
            const amountInput = document.getElementById('add-expense-amount');
            if (amountInput) {
                amountInput.addEventListener('input', function () {
                    const warning = document.getElementById('overbudget-warning');
                    if (parseFloat(this.value) > remainingBudget && remainingBudget >= 0) {
                        warning.classList.remove('hidden');
                    } else {
                        warning.classList.add('hidden');
                    }
                });
            }

            const addExpenseForm = document.getElementById('form-add-expense-show');
            if (addExpenseForm) {
                addExpenseForm.addEventListener('submit', async function(e) {
                    if (this.dataset.overbudgetBypassed === '1') {
                        delete this.dataset.overbudgetBypassed;
                        return; // proceed with submit
                    }

                    const amount = parseFloat(document.getElementById('add-expense-amount').value) || 0;
                    if (amount > remainingBudget && remainingBudget >= 0) {
                        e.preventDefault();
                        e.stopImmediatePropagation();

                        const message = 'Nominal melebihi sisa anggaran kategori (Rp ' +
                            remainingBudget.toLocaleString('id-ID') +
                            '). Tetap simpan?';

                        const accepted = await window.showConfirmModal(message);
                        if (accepted) {
                            this.dataset.overbudgetBypassed = '1';
                            this.submit();
                        }
                    }
                });
            }
        </script>
        <style>
            .animate-in {
                animation: popIn 0.2s ease-out;
            }

            @keyframes popIn {
                from {
                    opacity: 0;
                    transform: scale(0.95);
                }

                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }
        </style>
    @endpush
@endsection