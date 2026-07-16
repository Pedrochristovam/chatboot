<header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur-md sm:px-6">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen = true" class="rounded-xl p-2 text-slate-500 transition hover:bg-slate-50 hover:text-[#8B1E3F] lg:hidden">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div>
            <h1 class="font-display text-lg font-semibold text-[#5C1529]">{{ $header ?? 'Painel' }}</h1>
            <p class="hidden text-[11px] text-slate-400 sm:block">MGI chat</p>
        </div>
    </div>

    <div class="flex items-center gap-1.5 sm:gap-2">
        <button type="button" @click="darkMode = !darkMode" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-50 hover:text-[#8B1E3F]" title="Tema">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>

        <button type="button" class="relative rounded-xl p-2 text-slate-400 transition hover:bg-slate-50 hover:text-[#8B1E3F]" title="Notificações">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-[#8B1E3F] ring-2 ring-white"></span>
        </button>

        @auth
        <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
            @csrf
            <button type="submit" class="ml-1 flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-[#8B1E3F] transition hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sair
            </button>
        </form>
        @endauth
    </div>
</header>
