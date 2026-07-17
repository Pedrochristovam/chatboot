@props(['tone' => 'neutral'])
@php
    $styles = match($tone) {
        'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/15',
        'warning' => 'bg-amber-50 text-amber-700 ring-amber-600/15',
        'danger' => 'bg-red-50 text-red-700 ring-red-600/15',
        'primary' => 'bg-[#F5EEF0] text-[#8B1E3F] ring-[#8B1E3F]/15',
        default => 'bg-slate-100 text-slate-600 ring-slate-500/10',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-1 text-[10px] font-bold ring-1 ring-inset {$styles}"]) }}>{{ $slot }}</span>
