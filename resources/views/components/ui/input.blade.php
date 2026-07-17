@props(['label' => null, 'name' => null, 'type' => 'text', 'hint' => null])
<label class="block">
    @if($label)<span class="mb-1.5 block text-xs font-bold text-slate-700">{{ $label }}</span>@endif
    <input type="{{ $type }}" @if($name) name="{{ $name }}" @endif
        {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#8B1E3F] focus:ring-3 focus:ring-[#8B1E3F]/8']) }}>
    @if($hint)<span class="mt-1 block text-[11px] text-slate-400">{{ $hint }}</span>@endif
</label>
