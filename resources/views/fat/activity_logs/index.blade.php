@extends('layouts.dashboard', ['title' => 'Audit Trail'])

@section('content')
@php
    $eventConfig = [
        'created' => ['label' => 'DIBUAT',   'bg' => 'background:#d1fae5', 'color' => 'color:#065f46', 'icon' => '✚'],
        'updated' => ['label' => 'DIUBAH',   'bg' => 'background:#dbeafe', 'color' => 'color:#1e40af', 'icon' => '✎'],
        'deleted' => ['label' => 'DIHAPUS',  'bg' => 'background:#fee2e2', 'color' => 'color:#991b1b', 'icon' => '✕'],
    ];
@endphp

<div class="mx-auto max-w-7xl space-y-6">

    {{-- ── Header ── --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">🔍 Audit Trail</h2>
            <p class="mt-1 text-sm text-slate-500">Log historis seluruh aktivitas perubahan data pada sistem</p>
        </div>
        <div class="text-xs text-slate-400 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5">
            🕒 Server: {{ now()->translatedFormat('d F Y, H:i') }} WIB
        </div>
    </div>

    {{-- ── Stat Cards ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @php
            $stats = [
                ['label' => 'Hari Ini',    'value' => $totalToday,   'icon' => '📅', 'color' => '#6366f1'],
                ['label' => 'Dibuat',      'value' => $totalCreated, 'icon' => '✚',  'color' => '#10b981'],
                ['label' => 'Diubah',      'value' => $totalUpdated, 'icon' => '✎',  'color' => '#3b82f6'],
                ['label' => 'Dihapus',     'value' => $totalDeleted, 'icon' => '✕',  'color' => '#ef4444'],
            ];
        @endphp
        @foreach($stats as $stat)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm flex items-center gap-3">
            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-lg font-bold"
                 style="background-color:{{ $stat['color'] }}20; color:{{ $stat['color'] }}">
                {{ $stat['icon'] }}
            </div>
            <div>
                <p class="text-xs text-slate-400 font-medium">{{ $stat['label'] }}</p>
                <p class="text-xl font-bold text-slate-800">{{ number_format($stat['value']) }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Filter Panel ── --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
        <form method="GET" action="{{ route('fat.activity-logs.index') }}" id="filter-form">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">

                {{-- Search --}}
                <div class="xl:col-span-2">
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">🔎 Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari di deskripsi / data..."
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-slate-50">
                </div>

                {{-- User --}}
                <div>
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">👤 User</label>
                    <select name="causer_id"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-slate-50">
                        <option value="">Semua User</option>
                        @foreach($causers as $causer)
                            <option value="{{ $causer->id }}" {{ request('causer_id') == $causer->id ? 'selected' : '' }}>
                                {{ $causer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Event Type --}}
                <div>
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">⚡ Aksi</label>
                    <select name="event"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-slate-50">
                        <option value="">Semua Aksi</option>
                        @foreach($eventTypes as $evt)
                            <option value="{{ $evt }}" {{ request('event') === $evt ? 'selected' : '' }}>
                                {{ strtoupper($evt) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">📆 Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-slate-50">
                </div>

                {{-- Date To --}}
                <div>
                    <label class="text-xs font-semibold text-slate-500 mb-1 block">📆 Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-slate-50">
                </div>
            </div>

            <div class="flex items-center gap-2 mt-4">
                <button type="submit"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition shadow-sm"
                        style="background-color:#4f46e5">
                    Terapkan Filter
                </button>
                <a href="{{ route('fat.activity-logs.index') }}"
                   class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Reset
                </a>
                @if(request()->hasAny(['search','causer_id','event','date_from','date_to','subject_type']))
                    <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                        🔵 Filter aktif — {{ $logs->total() }} hasil ditemukan
                    </span>
                @endif
            </div>
        </form>
    </div>

    {{-- ── Log Table ── --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left whitespace-nowrap">Waktu</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap">User</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap">Aksi</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap">Model / ID</th>
                        <th class="px-4 py-3 text-left">Detail Perubahan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        @php
                            $evt = $eventConfig[$log->event] ?? ['label' => strtoupper($log->event ?? '?'), 'bg' => 'background:#f1f5f9', 'color' => 'color:#475569', 'icon' => '•'];
                            $hasOld  = isset($log->properties['old']) && count((array)$log->properties['old']) > 0;
                            $hasNew  = isset($log->properties['attributes']) && count((array)$log->properties['attributes']) > 0;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition align-top">

                            {{-- Waktu --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-xs font-semibold text-slate-700">{{ $log->created_at->format('d M Y') }}</div>
                                <div class="text-[11px] text-slate-400">{{ $log->created_at->format('H:i:s') }}</div>
                                <div class="text-[10px] text-slate-300 mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                            </td>

                            {{-- User --}}
                            <td class="px-4 py-3">
                                @if($log->causer)
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-700">
                                            {{ strtoupper(substr($log->causer->name, 0, 2)) }}
                                        </span>
                                        <div>
                                            <div class="text-xs font-semibold text-slate-700 whitespace-nowrap">{{ $log->causer->name }}</div>
                                            <div class="text-[10px] text-slate-400">{{ $log->causer->role ?? '' }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Sistem / Otomatis</span>
                                @endif
                            </td>

                            {{-- Event --}}
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-[11px] font-bold"
                                      style="{{ $evt['bg'] }};{{ $evt['color'] }}">
                                    <span>{{ $evt['icon'] }}</span>
                                    <span>{{ $evt['label'] }}</span>
                                </span>
                            </td>

                            {{-- Model --}}
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-xs font-semibold text-slate-700">{{ class_basename($log->subject_type ?? '') }}</div>
                                @if($log->subject_id)
                                    <div class="text-[10px] text-slate-400">ID #{{ $log->subject_id }}</div>
                                @endif
                            </td>

                            {{-- Detail --}}
                            <td class="px-4 py-3">
                                @if($hasOld || $hasNew)
                                    <div class="space-y-1.5 text-[11px] font-mono max-w-sm">
                                        @if($hasOld)
                                            <div class="rounded-lg border border-rose-100 bg-rose-50 px-3 py-2">
                                                <div class="text-[10px] font-sans font-bold text-rose-500 mb-1 uppercase tracking-wide">Sebelum</div>
                                                @foreach((array)$log->properties['old'] as $field => $val)
                                                    <div class="text-rose-700">
                                                        <span class="font-bold">{{ $field }}:</span>
                                                        {{ is_array($val) ? json_encode($val) : $val }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($hasNew)
                                            <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2">
                                                <div class="text-[10px] font-sans font-bold text-emerald-600 mb-1 uppercase tracking-wide">Sesudah</div>
                                                @foreach((array)$log->properties['attributes'] as $field => $val)
                                                    <div class="text-emerald-700">
                                                        <span class="font-bold">{{ $field }}:</span>
                                                        {{ is_array($val) ? json_encode($val) : $val }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-300 italic">— Tidak ada detail —</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <div class="text-4xl mb-3">🔍</div>
                                <div class="text-slate-500 font-semibold">Tidak ada log yang ditemukan</div>
                                <div class="text-slate-400 text-sm mt-1">Coba ubah filter atau reset pencarian</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3">
            <div class="text-xs text-slate-400">
                Menampilkan
                <strong class="text-slate-600">{{ $logs->firstItem() }}–{{ $logs->lastItem() }}</strong>
                dari <strong class="text-slate-600">{{ number_format($logs->total()) }}</strong> log
            </div>
            <div>
                {{ $logs->links() }}
            </div>
        </div>
        @else
        <div class="px-5 py-3 border-t border-slate-100 text-xs text-slate-400">
            Total <strong class="text-slate-600">{{ number_format($logs->total()) }}</strong> log ditemukan
        </div>
        @endif
    </div>

</div>
@endsection
