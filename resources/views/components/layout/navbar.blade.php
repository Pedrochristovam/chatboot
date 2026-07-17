<header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen = true" class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-50 hover:text-[#8B1E3F] lg:hidden">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div>
            <h1 class="text-sm font-extrabold tracking-tight text-slate-900 sm:text-base">{{ $header ?? 'Painel' }}</h1>
            <p class="hidden text-[10px] font-medium uppercase tracking-[0.12em] text-slate-400 sm:block">Painel administrativo</p>
        </div>
    </div>

    <div class="flex items-center gap-2 sm:gap-3">
        <button type="button" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-50 hover:text-[#8B1E3F]" title="Notificações">
            <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span class="absolute right-1.5 top-1.5 h-1.5 w-1.5 rounded-full bg-[#8B1E3F] ring-2 ring-white"></span>
        </button>

        @auth
        <div class="hidden h-7 w-px bg-slate-200 sm:block"></div>
        <div class="hidden text-right sm:block">
            <p class="max-w-36 truncate text-xs font-bold text-slate-800">{{ auth()->user()->name }}</p>
            <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">{{ auth()->user()->role_title ?? 'Administrador' }}</p>
        </div>
        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-[11px] font-extrabold text-[#8B1E3F]">
            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
        </div>
        @endauth
    </div>
</header>
