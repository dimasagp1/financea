@extends('layouts.dashboard', ['title' => 'Staging Pengeluaran Pagu'])

@section('content')
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Staging Pengeluaran Pagu</h2>
            <p class="text-slate-500 mt-1">Daftar realisasi Purchase Request dari sistem Procurement. Silakan periksa,
                catat manual di Odoo, lalu tandai sebagai Reported Odoo di sini.</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-md shadow-slate-200/50 p-6 mb-6">
        <form method="GET" action="{{ route('fat.staging.index') }}"
            class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="search" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Cari
                    Referensi / Deskripsi</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all shadow-sm"
                    placeholder="Contoh: PR-2026-0001...">
            </div>
            <div>
                <label for="department_id"
                    class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Departemen</label>
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
                <label for="status" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Status
                    Staging</label>
                <select name="status" id="status"
                    class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all shadow-sm">
                    <option value="">Semua Status (Urutan Pending Utama)</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending (Belum Reported Odoo)</option>
                    <option value="bon" @selected(request('status') === 'bon')>Reported Odoo (Sudah Diproses)</option>
                    <option value="ignored" @selected(request('status') === 'ignored')>Diabaikan</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800 focus:outline-none transition-all shadow-sm">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'department_id', 'status']))
                    <a href="{{ route('fat.staging.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 focus:outline-none transition-all shadow-sm">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Workflow Alert -->
    <div class="bg-amber-50 border border-amber-200/60 rounded-2xl p-4 mb-6 flex items-start gap-3 shadow-xs">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 mt-0.5 shrink-0" viewBox="0 0 20 20"
            fill="currentColor">
            <path fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                clip-rule="evenodd" />
        </svg>
        <div class="text-sm text-amber-800">
            <p class="font-bold mb-0.5">Panduan Kerja Finance (Reported Odoo)</p>
            <p class="leading-relaxed">Data pengeluaran pagu di bawah ini dikirim otomatis saat PR disetujui di Procurement.
                Lakukan input manual transaksi di Odoo berdasarkan detail berikut. Setelah transaksi berhasil dicatat di
                Odoo, tekan tombol <strong class="text-amber-950">"Reported Odoo"</strong> untuk mengubah statusnya.</p>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-md shadow-slate-200/50 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <th class="px-5 py-4 w-12 text-center">No</th>
                        <th class="px-5 py-4">Referensi PR</th>
                        <th class="px-5 py-4">Departemen & Kategori</th>
                        <th class="px-5 py-4">Tanggal Realisasi</th>
                        <th class="px-5 py-4">Deskripsi / Detail Item</th>
                        <th class="px-5 py-4 text-center">Qty</th>
                        <th class="px-5 py-4 text-right">Jumlah (Rp)</th>
                        <th class="px-5 py-4 text-center">Status</th>
                        <th class="px-5 py-4">Pemeriksa / Waktu</th>
                        <th class="px-5 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 align-middle">
                    @forelse($stagings as $index => $staging)
                        @php
                            if ($staging->status === 'pending') {
                                $statusBadge = 'bg-amber-50 text-amber-700 border border-amber-200';
                                $statusText = 'Pending';
                            } elseif ($staging->status === 'bon') {
                                $statusBadge = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                $statusText = 'Reported Odoo';
                            } else {
                                $statusBadge = 'bg-slate-100 text-slate-600 border border-slate-200';
                                $statusText = 'Diabaikan';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-5 py-4 text-center text-slate-400 font-medium">
                                {{ $stagings->firstItem() + $index }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="font-bold text-slate-900">{{ $staging->reference }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-800">{{ $staging->department?->name }}</div>
                                <div class="text-xs text-slate-500 font-mono mt-0.5">[{{ $staging->budgetCategory?->code }}]
                                    {{ $staging->budgetCategory?->name }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-slate-600 font-medium">
                                {{ $staging->date->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-5 py-4 text-slate-700 max-w-xs break-words">
                                {{ $staging->description }}
                            </td>
                            <td class="px-5 py-4 text-center font-bold text-slate-600">
                                {{ number_format($staging->qty, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 text-right font-mono text-slate-950 font-bold whitespace-nowrap">
                                Rp {{ number_format($staging->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $statusBadge }}">
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-500">
                                @if($staging->checkedBy)
                                    <div class="font-semibold text-slate-700">{{ $staging->checkedBy->name }}</div>
                                    <div class="mt-0.5">{{ $staging->checked_at?->translatedFormat('d M Y H:i') }}</div>
                                @else
                                    <span class="text-slate-400 italic">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($staging->status === 'pending')
                                        <form method="POST" action="{{ route('fat.staging.bon', $staging) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center justify-center gap-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-3 py-1.5 text-xs transition shadow-sm"
                                                title="Tandai sudah dicatat di Odoo (Reported Odoo)">
                                                ✓ Reported Odoo
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('fat.staging.ignore', $staging) }}" class="inline"
                                            onsubmit="return confirm('Abaikan pengeluaran ini?')">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center justify-center gap-1 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold px-3 py-1.5 text-xs transition"
                                                title="Abaikan pengeluaran">
                                                ✕ Abaikan
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('fat.staging.pending', $staging) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center justify-center gap-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold px-3 py-1.5 text-xs transition shadow-sm"
                                                title="Kembalikan ke Status Pending">
                                                ↩ Reset
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                <div class="max-w-md mx-auto flex flex-col items-center justify-center">
                                    <div
                                        class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <h3 class="text-base font-bold text-slate-800">Tidak Ada Data Staging</h3>
                                    <p class="text-sm text-slate-400 mt-1">Belum ada data realisasi PR yang masuk atau memenuhi
                                        kriteria filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stagings->hasPages())
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100">
                {{ $stagings->links() }}
            </div>
        @endif
    </div>
@endsection