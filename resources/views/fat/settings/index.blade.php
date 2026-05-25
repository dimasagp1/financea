@extends('layouts.dashboard', ['title' => 'Pengaturan Aplikasi'])

@section('content')
<div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Pengaturan Aplikasi
            </h1>
            <p class="mt-1 text-sm text-slate-500">Konfigurasi nama website, logo, dan favicon.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 shadow-sm flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <h3 class="font-bold text-emerald-800">Berhasil!</h3>
                <p class="text-sm text-emerald-600 mt-1">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <form action="{{ route('fat.settings.update') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-8">
            @csrf

            <!-- App Name Field -->
            <div>
                <label for="app_name" class="block text-sm font-bold text-slate-700 mb-1">Nama Aplikasi</label>
                <input type="text" name="app_name" id="app_name" value="{{ old('app_name', $settings['app_name']) }}" 
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all shadow-sm" required>
                @error('app_name')
                    <p class="mt-1 text-sm text-rose-500">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-slate-500">Nama ini akan ditampilkan pada halaman login dan sidebar aplikasi.</p>
            </div>

            <!-- Logo Field -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Logo Aplikasi</label>
                <div class="flex items-start gap-6">
                    <div class="shrink-0 h-24 w-24 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-center p-2 overflow-hidden">
                        @if($settings['app_logo'])
                            <img src="{{ Storage::url($settings['app_logo']) }}" alt="Logo" class="max-h-full max-w-full object-contain">
                        @else
                            <span class="text-slate-400 text-sm font-medium">Default</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input type="file" name="app_logo" id="app_logo" accept="image/*"
                               class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all">
                        @error('app_logo')
                            <p class="mt-1 text-sm text-rose-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-slate-500">Gunakan format PNG, JPG, atau SVG dengan ukuran maksimal 2MB. Logo idealnya berukuran transparan.</p>
                    </div>
                </div>
            </div>

            <!-- Favicon Field -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Favicon Aplikasi (Ikon Tab)</label>
                <div class="flex items-start gap-6">
                    <div class="shrink-0 h-16 w-16 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-center p-2 overflow-hidden">
                        @if($settings['app_favicon'])
                            <img src="{{ Storage::url($settings['app_favicon']) }}" alt="Favicon" class="max-h-full max-w-full object-contain">
                        @else
                            <span class="text-slate-400 text-xs font-medium">Default</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input type="file" name="app_favicon" id="app_favicon" accept="image/x-icon,image/png,image/svg+xml"
                               class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 transition-all">
                        @error('app_favicon')
                            <p class="mt-1 text-sm text-rose-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-slate-500">Gunakan format ICO, PNG atau SVG dengan ukuran rasio 1:1 maksimal 1MB.</p>
                    </div>
                </div>
            </div>

            <!-- Procurement API Key Field -->
            <div class="border-t border-slate-100 pt-6">
                <label for="procurement_api_key" class="block text-sm font-bold text-slate-700 mb-1">Procurement API Key</label>
                <div class="flex gap-2">
                    <input type="text" name="procurement_api_key" id="procurement_api_key" value="{{ old('procurement_api_key', $settings['procurement_api_key']) }}" 
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 transition-all shadow-sm" placeholder="Masukkan atau generate API Key">
                    <button type="button" id="btn-generate-key" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all shadow-sm" title="Generate Random API Key">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m-2 4a2 2 0 012 2m-8-3a3 3 0 00-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1m-6 3a3 3 0 003 3h4a3 3 0 013-3V9a3 3 0 01-3-3h-4a3 3 0 00-3 3v2z" />
                        </svg>
                        Generate Key
                    </button>
                </div>
                @error('procurement_api_key')
                    <p class="mt-1 text-sm text-rose-500">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-slate-500">API Key ini digunakan oleh aplikasi Procurement untuk mengamankan komunikasi API ke sistem Finance (FAT). Samakan API Key ini di halaman pengaturan Procurement.</p>
            </div>

            <!-- Budget Categories Checklist -->
            <div class="border-t border-slate-100 pt-6">
                <h2 class="text-lg font-bold text-slate-900 mb-2">Kategori Anggaran yang Diizinkan untuk API</h2>
                <p class="text-sm text-slate-500 mb-4">Centang kategori anggaran yang diizinkan untuk digunakan di sistem Procurement (PROC). Kategori yang tidak dicentang akan dinonaktifkan (is_active = false) dan diblokir oleh API.</p>

                @if($activeYear)
                    <div class="space-y-6">
                        @foreach($departments as $dept)
                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                                <h3 class="font-bold text-slate-800 text-sm mb-3 flex items-center justify-between">
                                    <span>{{ $dept->name }} ({{ $dept->code }})</span>
                                    <button type="button" onclick="toggleDeptCategories({{ $dept->id }}, this)" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold focus:outline-none">
                                        Pilih Semua
                                    </button>
                                </h3>
                                
                                @if($dept->budgetCategories->isEmpty())
                                    <p class="text-xs text-slate-400 italic">Tidak ada kategori anggaran untuk departemen ini.</p>
                                @else
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach($dept->budgetCategories as $category)
                                            <label class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors shadow-sm">
                                                <input type="checkbox" name="active_categories[]" value="{{ $category->id }}" 
                                                       class="dept-checkbox-{{ $dept->id }} rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                                                       {{ $category->is_active ? 'checked' : '' }}>
                                                <div>
                                                    <span class="block text-sm font-semibold text-slate-800">{{ $category->name }}</span>
                                                    <span class="block text-xs text-slate-400">Kode: {{ $category->code }}</span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 flex items-start gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <h3 class="font-bold text-amber-800">Tahun Fiskal Tidak Aktif</h3>
                            <p class="text-sm text-amber-600 mt-1">Harap aktifkan tahun fiskal terlebih dahulu untuk memunculkan daftar kategori anggaran.</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnGen = document.getElementById('btn-generate-key');
        if (btnGen) {
            btnGen.addEventListener('click', function () {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let result = 'FAT_KEY_';
                for (let i = 0; i < 32; i++) {
                    result += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                document.getElementById('procurement_api_key').value = result;
            });
        }
    });

    function toggleDeptCategories(deptId, btn) {
        const checkboxes = document.querySelectorAll('.dept-checkbox-' + deptId);
        let allChecked = true;
        checkboxes.forEach(cb => {
            if (!cb.checked) allChecked = false;
        });
        
        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
        });
        
        btn.textContent = !allChecked ? 'Batal Pilih Semua' : 'Pilih Semua';
    }
</script>
@endpush
@endsection
