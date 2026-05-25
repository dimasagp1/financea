@props(['variant' => 'primary', 'size' => 'md', 'dot' => false])

@php
    $variants = [
        'primary' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'secondary' => 'bg-slate-100 text-slate-700 border-slate-200',
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'danger' => 'bg-rose-50 text-rose-700 border-rose-200',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-200',
        'info' => 'bg-sky-50 text-sky-700 border-sky-200',
    ];

    $dotColors = [
        'primary' => 'bg-indigo-500',
        'secondary' => 'bg-slate-500',
        'success' => 'bg-emerald-500',
        'danger' => 'bg-rose-500',
        'warning' => 'bg-amber-500',
        'info' => 'bg-sky-500',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-[10px]',
        'md' => 'px-2.5 py-1 text-xs',
        'lg' => 'px-3 py-1.5 text-sm',
    ];

    $classes = "inline-flex items-center justify-center font-semibold border rounded-lg whitespace-nowrap {$variants[$variant]} {$sizes[$size]}";
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot)
        <span class="mr-1.5 w-1.5 h-1.5 rounded-full {{ $dotColors[$variant] }}"></span>
    @endif
    {{ $slot }}
</span>
