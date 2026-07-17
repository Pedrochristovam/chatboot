@props(['variant' => 'primary', 'type' => 'button'])
@php
    $styles = $variant === 'secondary'
        ? 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-[#8B1E3F]'
        : ($variant === 'danger'
            ? 'bg-red-600 text-white hover:bg-red-700'
            : 'bg-[#8B1E3F] text-white hover:bg-[#721832]');
@endphp
<button type="{{ $type }}" {{ $attributes->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-xs font-bold transition disabled:cursor-not-allowed disabled:opacity-50 {$styles}"]) }}>
    {{ $slot }}
</button>
