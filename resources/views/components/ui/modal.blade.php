@props(['title', 'maxWidth' => 'max-w-lg'])
<div {{ $attributes->merge(['class' => 'fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-[1px]']) }}>
    <div class="w-full {{ $maxWidth }} overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-950/10" @click.stop>
        <header class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-extrabold text-slate-900">{{ $title }}</h2>
            @isset($close){{ $close }}@endisset
        </header>
        <div class="p-5">{{ $slot }}</div>
        @isset($footer)<footer class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">{{ $footer }}</footer>@endisset
    </div>
</div>
