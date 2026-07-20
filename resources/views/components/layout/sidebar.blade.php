<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex w-56 flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0">

    <div class="flex h-16 items-center gap-3 border-b border-slate-100 px-5">
        <img src="{{ asset('images/mgi-logo-oficial.png') }}"
             alt="Logo MGI"
             class="h-9 w-12 rounded-md bg-white object-contain">
        <div>
            <p class="text-base font-extrabold leading-tight tracking-tight text-[#8B1E3F]">MGI Chat</p>
            <p class="mt-0.5 text-[9px] font-bold uppercase tracking-[0.14em] text-slate-400">WhatsApp CRM</p>
        </div>
    </div>

    <nav class="custom-scrollbar flex-1 space-y-1 overflow-y-auto px-3 py-5">
        @php
            $links = [
                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                ['route' => 'conversations.index', 'label' => 'Conversas', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                ['route' => 'closed-conversations.index', 'label' => 'Encerradas por mim', 'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
                ['route' => 'bot-conversations.index', 'label' => 'Encerradas pelo robô', 'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                ['route' => 'bot-knowledge.index', 'label' => 'Robô & FAQ', 'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                ['route' => 'clients.index', 'label' => 'Clientes', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['route' => 'agents.index', 'label' => 'Atendentes', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                ['route' => 'reports.index', 'label' => 'Relatórios', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ['route' => 'operations.index', 'label' => 'Operações', 'permission' => 'audit.view', 'icon' => 'M3.75 13.5l2.25-2.25 2.25 2.25 4.5-4.5 3 3 4.5-4.5M4.5 19.5h15a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5h-15A1.5 1.5 0 003 6v12a1.5 1.5 0 001.5 1.5z'],
                ['route' => 'settings.index', 'label' => 'Configurações', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
            ];
        @endphp

        @foreach ($links as $link)
            @continue(isset($link['permission']) && ! auth()->user()?->hasPermission($link['permission']))
            @php $active = request()->routeIs($link['route'].'*'); @endphp
            <a href="{{ route($link['route']) }}"
               @click="sidebarOpen = false"
               class="relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-semibold transition
                      {{ $active
                          ? 'bg-[#F5EEF0] text-[#8B1E3F] before:absolute before:-left-3 before:top-1/2 before:h-7 before:w-0.5 before:-translate-y-1/2 before:rounded-r-full before:bg-[#8B1E3F]'
                          : 'text-slate-600 hover:bg-slate-50 hover:text-[#8B1E3F]' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}"/>
                </svg>
                <span class="truncate">{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    @auth
    <div class="border-t border-slate-100 p-3">
        <div class="flex items-center gap-2.5 rounded-lg p-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-xs font-extrabold text-[#8B1E3F]">
                {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-bold text-slate-800">{{ auth()->user()->name }}</p>
                <p class="truncate text-[10px] text-slate-400">{{ auth()->user()->role_title ?? 'Administrador' }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-1">
            @csrf
            <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-red-50 hover:text-red-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3-3H9m0 0l3-3m-3 3l3 3"/></svg>
                Sair da conta
            </button>
        </form>
    </div>
    @endauth
</aside>
