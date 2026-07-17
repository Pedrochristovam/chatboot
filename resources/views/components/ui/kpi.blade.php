@props(['label', 'value', 'detail' => null, 'tone' => 'slate'])
<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-4']) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.1em] text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">{{ $value }}</p>
            @if($detail)<p class="mt-1 text-[11px] text-slate-500">{{ $detail }}</p>@endif
        </div>
        @isset($icon)<span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-[#8B1E3F]">{{ $icon }}</span>@endisset
    </div>
</div>
