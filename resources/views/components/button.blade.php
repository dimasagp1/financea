@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'fullWidth' => false,
])

@php
    $baseClass = 'inline-flex items-center justify-center font-semibold transition-all duration-200 ease-in-out active:scale-95 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    
    $variants = [
        'primary' => 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-md shadow-indigo-500/30 hover:shadow-lg hover:shadow-indigo-500/40 focus:ring-indigo-500',
        'secondary' => 'bg-white text-slate-700 border border-slate-200 shadow-sm hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-200 focus:ring-slate-200',
        'danger' => 'bg-gradient-to-r from-rose-500 to-red-600 text-white shadow-md shadow-rose-500/30 hover:shadow-lg hover:shadow-rose-500/40 focus:ring-rose-500',
        'success' => 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-500/30 hover:shadow-lg hover:shadow-emerald-500/40 focus:ring-emerald-500',
        'outline' => 'bg-transparent text-indigo-600 border-2 border-indigo-500 hover:bg-indigo-50 focus:ring-indigo-500',
        'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100 hover:text-indigo-600 focus:ring-slate-200',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs rounded-xl gap-1.5',
        'md' => 'px-4 py-2 text-sm rounded-xl gap-2',
        'lg' => 'px-6 py-3 text-base rounded-2xl gap-2.5',
    ];

    $widthClass = $fullWidth ? 'w-full' : '';
    $classes = "$baseClass {$variants[$variant]} {$sizes[$size]} $widthClass";
@endphp

@if($attributes->has('href'))
    <a {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
