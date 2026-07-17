@props(['title' => null, 'description' => null])
<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white']) }}>
    @if($title || $description)
        <header class="border-b border-slate-100 px-4 py-3">
            @if($title)<h2 class="text-sm font-extrabold text-slate-900">{{ $title }}</h2>@endif
            @if($description)<p class="mt-0.5 text-xs text-slate-500">{{ $description }}</p>@endif
        </header>
    @endif
    {{ $slot }}
</section>
