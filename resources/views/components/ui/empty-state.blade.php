@props(['title' => 'Nada por aqui', 'description' => null])
<div {{ $attributes->merge(['class' => 'flex min-h-40 flex-col items-center justify-center px-5 py-10 text-center']) }}>
    <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        {{ $icon ?? '—' }}
    </div>
    <p class="text-sm font-extrabold text-slate-700">{{ $title }}</p>
    @if($description)<p class="mt-1 max-w-sm text-xs leading-5 text-slate-400">{{ $description }}</p>@endif
    @isset($action)<div class="mt-4">{{ $action }}</div>@endisset
</div>
