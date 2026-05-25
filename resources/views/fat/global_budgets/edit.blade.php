@extends('layouts.dashboard', ['title' => 'Edit Global Budget'])

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-8">
        <div class="mb-8 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div
                    class="h-12 w-12 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl shadow-lg shadow-indigo-50">
                    ✍️
                </div>
                <div>
                    <h2 class="text-2xl font-black text-slate-900">Penyesuaian Target</h2>
                    <p class="text-sm text-slate-500">
                        {{ \Carbon\Carbon::create()->month($globalBudget->month)->translatedFormat('F') }}
                        {{ $globalBudget->year }}
                    </p>
                </div>
            </div>
            <a href="{{ route('fat.global-budgets.index') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50 transition shadow-sm">
                <span>✕</span> <span>Batal</span>
            </a>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50 overflow-hidden">
            <div class="bg-indigo-400 h-2 w-full opacity-50"></div>
            <form action="{{ route('fat.global-budgets.update', $globalBudget->id) }}" method="POST" class="p-8 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Bulan
                            Periode</label>
                        <div
                            class="w-full rounded-2xl border border-slate-200 bg-slate-100 p-4 text-slate-700 text-sm font-bold">
                            {{ \Carbon\Carbon::create()->month($globalBudget->month)->translatedFormat('F') }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Tahun
                            Fiskal</label>
                        <div
                            class="w-full rounded-2xl border border-slate-200 bg-slate-100 p-4 text-slate-700 text-sm font-bold">
                            {{ $globalBudget->year }}
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Nominal Target
                        Omset (Rp)</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <span class="text-slate-400 font-bold">Rp</span>
                        </div>
                        <input type="number" name="amount" min="0" step="0.01"
                            value="{{ old('amount', $globalBudget->amount) }}"
                            class="w-full rounded-xl border border-slate-300 bg-white pl-12 pr-4 py-3 text-lg font-mono font-bold text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm"
                            placeholder="0" required>
                    </div>
                    @error('amount')
                        <p class="mt-2 text-xs text-rose-500 font-medium">⚠️ {{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-slate-500 mb-2">Catatan
                        Perubahan</label>
                    <textarea name="notes" rows="3"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 transition-all shadow-sm placeholder:text-slate-300"
                        placeholder="Berikan alasan perubahan target...">{{ old('notes', $globalBudget->notes) }}</textarea>
                    @error('notes')
                        <p class="mt-2 text-xs text-rose-500 font-medium">⚠️ {{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-6">
                    <button type="submit"
                        class="w-full rounded-2xl bg-slate-900 p-4 text-sm font-black uppercase tracking-widest text-white hover:bg-slate-800 transition shadow-xl shadow-slate-200 flex items-center justify-center gap-2">
                        <span>🔄</span>
                        <span>Perbarui Target</span>
                    </button>
                    <p class="mt-4 text-center text-[10px] text-slate-400 italic">
                        Perubahan ini akan langsung berdampak pada perhitungan alokasi seluruh departemen.
                    </p>
                </div>
            </form>
        </div>
    </div>
@endsection