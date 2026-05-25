@extends('layouts.dashboard', ['title' => 'Global Monthly Budget'])

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Input Omset Bulanan</h2>
                <div class="mt-1 flex items-center gap-2 text-sm text-slate-500">
                    <span class="px-2 py-0.5 rounded {{ $type === 'actual' ? 'bg-indigo-50 text-indigo-700 border-indigo-100' : 'bg-amber-50 text-amber-700 border-amber-100' }} font-semibold border">
                        {{ $type === 'actual' ? 'Budget Aktual (Monitoring)' : 'Budget Ramalan (Forecast)' }}
                    </span>
                    <span>• {{ $type === 'actual' ? 'Menjadi Pagu resmi untuk monitoring pengeluaran.' : 'Menjadi Pagu referensi untuk target peramalan.' }}</span>
                </div>
            </div>

            <a href="{{ route('fat.global-budgets.create', ['type' => $type]) }}"
                class="inline-flex items-center gap-2 rounded-xl {{ $type === 'actual' ? 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-100' : 'bg-amber-600 hover:bg-amber-700 shadow-amber-100' }} px-4 py-2.5 text-sm font-bold text-white transition shadow-lg">
                <span>＋</span>
                <span>Buat Target {{ ucfirst($type) }}</span>
            </a>
        </div>

        {{-- Tabs --}}
        <div class="mb-6 border-b border-slate-200">
            <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                <a href="{{ route('fat.global-budgets.index', ['type' => 'actual']) }}"
                    class="{{ $type === 'actual' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }} whitespace-nowrap py-3 px-1 border-b-2 font-bold text-sm transition transition-colors">
                    📊 Budget Aktual (Monitoring)
                </a>
                <a href="{{ route('fat.global-budgets.index', ['type' => 'forecast']) }}"
                    class="{{ $type === 'forecast' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }} whitespace-nowrap py-3 px-1 border-b-2 font-bold text-sm transition transition-colors">
                    🔮 Budget Ramalan (Forecast)
                </a>
            </nav>
        </div>

        <div
            class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden border-b-4 {{ $type === 'actual' ? 'border-b-indigo-500/20' : 'border-b-amber-500/20' }}">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50/80 border-b border-slate-100 italic">
                        <tr>
                            <th class="px-6 py-4">Bulan / Periode</th>
                            <th class="px-6 py-4 text-right">Target Omset (Nominal)</th>
                            <th class="px-6 py-4">Catatan Perencanaan</th>
                            <th class="px-6 py-4 text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($budgets as $budget)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="h-10 w-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                                            {{ substr(\Carbon\Carbon::create()->month($budget->month)->translatedFormat('F'), 0, 3) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">
                                                {{ \Carbon\Carbon::create()->month($budget->month)->translatedFormat('F') }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 uppercase tracking-tighter">
                                                FY{{ $activeFiscalYear->year }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-right font-mono text-slate-700 font-bold text-lg">
                                    <span
                                        class="text-xs text-slate-400 font-normal mr-1">Rp</span>{{ number_format($budget->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-5 text-slate-500 text-xs italic max-w-xs truncate">
                                    {{ $budget->notes ?? 'Tidak ada catatan.' }}
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('fat.global-budgets.edit', $budget->id) }}"
                                            class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition" title="Edit">
                                            ✏️
                                        </a>
                                        <form action="{{ route('fat.global-budgets.destroy', $budget->id) }}" method="POST"
                                            onsubmit="return confirm('Hapus budget bulan ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition" title="Hapus">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center opacity-40">
                                        <span class="text-5xl mb-4">📅</span>
                                        <p class="text-slate-500 italic">Belum ada target budget global ({{ ucfirst($type) }}) bulan ini.<br>Silakan
                                            tambah untuk memulai kalkulasi Pagu.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 p-4 rounded-xl bg-blue-50 border border-blue-100 flex items-start gap-3">
            <span class="text-blue-500 mt-0.5">ℹ️</span>
            <div class="text-xs text-blue-700 leading-relaxed">
                <strong>Catatan Penting:</strong> Nominal yang Anda input di sini akan secara otomatis dikalikan dengan
                <strong>Rasio Departemen (%)</strong> untuk menghasilkan Pagu (Jatah) bagi masing-masing departemen secara
                real-time. Jika Anda tidak menginput omset untuk bulan tertentu, maka jatah departemen di bulan
                tersebut akan bernilai Rp 0.
            </div>
        </div>
    </div>
@endsection