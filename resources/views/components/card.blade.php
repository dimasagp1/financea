@props(['title' => null, 'padding' => 'p-6', 'class' => ''])

<div {{ $attributes->merge(['class' => "bg-white/80 backdrop-blur-xl border border-slate-200/60 rounded-3xl shadow-sm hover:shadow-xl transition-all duration-300 $padding $class"]) }}>
    @if($title)
        <div class="mb-5 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">{{ $title }}</h3>
            {{ $headerActions ?? '' }}
        </div>
    @endif
    
    <div>
        {{ $slot }}
    </div>
</div>
