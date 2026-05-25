@extends('layouts.dashboard', ['title' => 'Manajemen Fiscal Year'])

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Manajemen Fiscal Year</h2>
        <p class="text-slate-500">Khusus SuperAdmin untuk mengelola tahun anggaran aktif.</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-1">
            <div class="rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/70 border border-slate-100">
                <h3 class="font-semibold mb-4">Tambah Fiscal Year</h3>
                <form method="POST" action="{{ route('fat.fiscal-years.store') }}" class="space-y-3">
                    @csrf
                    <input type="text" name="year" maxlength="4" placeholder="Contoh: 2027" class="w-full rounded-xl border-slate-200" required>
                    <input type="number" name="global_budget_amount" step="0.01" min="0" placeholder="Global Budget" class="w-full rounded-xl border-slate-200" required>
                    <textarea name="notes" rows="3" placeholder="Catatan" class="w-full rounded-xl border-slate-200"></textarea>
                    <button data-loading-text="Menyimpan fiscal year..." class="w-full rounded-xl bg-slate-900 text-white py-2.5">Simpan Fiscal Year</button>
                </form>
            </div>
        </div>

        <div class="xl:col-span-2">
            <div class="rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/70 border border-slate-100">
                <h3 class="font-semibold mb-4">Daftar Fiscal Year</h3>
                <div class="space-y-4">
                    @forelse($fiscalYears as $fy)
                        <div class="rounded-xl border border-slate-200 p-4">
                            <form method="POST" action="{{ route('fat.fiscal-years.update', $fy->id) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label class="text-xs text-slate-500">Year</label>
                                    <input type="text" name="year" value="{{ $fy->year }}" class="w-full rounded-xl border-slate-200" required>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500">Global Budget</label>
                                    <input type="number" name="global_budget_amount" step="0.01" min="0" value="{{ $fy->global_budget_amount }}" class="w-full rounded-xl border-slate-200" required>
                                </div>
                                <div>
                                    <label class="text-xs text-slate-500">Status</label>
                                    <div class="px-3 py-2 rounded-xl {{ $fy->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }} text-sm">
                                        {{ $fy->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button data-loading-text="Mengupdate fiscal year..." class="rounded-xl bg-indigo-600 text-white px-3 py-2">Update</button>
                                </div>
                                <div class="md:col-span-3">
                                    <textarea name="notes" rows="2" class="w-full rounded-xl border-slate-200" placeholder="Catatan">{{ $fy->notes }}</textarea>
                                </div>
                            </form>

                            <div class="mt-3 flex gap-2">
                                <form method="POST" action="{{ route('fat.fiscal-years.activate', $fy->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button data-loading-text="Mengaktifkan fiscal year..." class="rounded-xl px-3 py-2 {{ $fy->is_active ? 'bg-emerald-500 text-white' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $fy->is_active ? 'Sedang Aktif' : 'Jadikan Aktif' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-6">Belum ada fiscal year.</div>
                    @endforelse
                </div>
                <div class="mt-4">{{ $fiscalYears->links() }}</div>
            </div>
        </div>
    </div>
@endsection
