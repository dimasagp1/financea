@extends('layouts.dashboard', ['title' => 'Monitoring Budget'])

@section('content')
    <div class="mx-auto max-w-7xl text-center py-20">
        <div class="bg-indigo-50 inline-flex items-center justify-center w-20 h-20 rounded-full mb-6">
            <span class="text-4xl">📊</span>
        </div>
        <h2 class="text-2xl font-bold text-slate-800">Tidak ada data Departemen</h2>
        <p class="mt-2 text-slate-500 max-w-md mx-auto">
            Belum ada departemen yang terdaftar untuk Tahun Fiskal {{ $activeFiscalYear->year ?? 'Aktif' }}.
            Silakan tambahkan departemen terlebih dahulu.
        </p>

        @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('fat.departments.index') }}"
                class="mt-8 inline-block px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition">
                Kelola Departemen
            </a>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        // Auto-refresh session setiap 15 menit untuk mencegah Page Expired (419)
        setInterval(function() {
            fetch(window.location.href, { method: 'HEAD' })
                .catch(e => console.log('Keep-alive failed', e));
        }, 1000 * 60 * 15);
    </script>
@endpush