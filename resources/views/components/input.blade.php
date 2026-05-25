@props(['disabled' => false, 'icon' => null, 'error' => null])

<div class="relative w-full">
    @if($icon)
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
            <i class='bx {{ $icon }} text-lg'></i>
        </div>
    @endif
    
    <input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
        'class' => 'w-full rounded-xl border border-slate-200 bg-white/50 px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/10 transition-all duration-200 shadow-sm disabled:bg-slate-100 disabled:cursor-not-allowed ' . ($icon ? 'pl-10' : '') . ($error ? ' border-rose-300 focus:border-rose-500 focus:ring-rose-500/10' : '')
    ]) !!}>
    
    @if($error)
        <p class="mt-1.5 text-xs font-medium text-rose-500 flex items-center gap-1">
            <i class='bx bx-error-circle'></i> {{ $error }}
        </p>
    @endif
</div>
