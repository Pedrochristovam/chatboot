@php
    $weekTotal = array_sum($attendanceChart['values'] ?? []);
    $monthTotal = array_sum($monthlyChart['values'] ?? []);
    $hour = (int) now()->format('H');
    $greeting = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
    $userName = auth()->user()->name ?? 'Administrador';
    $firstName = explode(' ', $userName)[0];

    $kpis = [
        [
            'label' => 'Conversas hoje',
            'value' => $metrics['conversations_today'],
            'hint' => 'entradas no dia',
            'icon' => 'chat',
            'tone' => 'bordo',
        ],
        [
            'label' => 'Aguardando',
            'value' => $metrics['waiting_conversations'],
            'hint' => $metrics['waiting_conversations'] > 0 ? 'fila precisa de atenção' : 'fila zerada',
            'icon' => 'alert',
            'tone' => $metrics['waiting_conversations'] > 0 ? 'warn' : 'soft',
            'alert' => $metrics['waiting_conversations'] > 0,
        ],
        [
            'label' => 'Atendentes online',
            'value' => $metrics['online_agents'],
            'hint' => 'prontos para atender',
            'icon' => 'headset',
            'tone' => 'soft',
        ],
        [
            'label' => 'Tempo médio',
            'value' => $metrics['avg_response_time'] . ' min',
            'hint' => 'primeira resposta hoje',
            'icon' => 'clock',
            'tone' => 'soft',
        ],
        [
            'label' => 'Clientes ativos',
            'value' => number_format($metrics['active_clients'], 0, ',', '.'),
            'hint' => 'base cadastrada',
            'icon' => 'users',
            'tone' => 'soft',
        ],
        [
            'label' => 'Encerradas hoje',
            'value' => $metrics['closed_today'],
            'hint' => 'finalizadas com sucesso',
            'icon' => 'check',
            'tone' => 'soft',
        ],
    ];

    $tones = [
        'bordo' => 'bg-[#8B1E3F] text-white',
        'warn' => 'border border-red-200 bg-red-50 text-red-700',
        'soft' => 'border border-slate-200 bg-white text-slate-900',
    ];
    $iconTones = [
        'bordo' => 'border border-white/20 bg-white/10 text-white',
        'warn' => 'border border-red-200 bg-white text-red-700',
        'soft' => 'border border-slate-200 bg-slate-50 text-[#8B1E3F]',
    ];
@endphp

<x-layout.app :title="'Dashboard - MGI Chat'">
    <x-slot name="header">Início</x-slot>

    <div class="dashboard-page space-y-4">
        <section class="dash-reveal overflow-hidden rounded-2xl border border-[#741832] bg-[#8B1E3F] px-5 py-5 text-white sm:px-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-xl">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/65">Central operacional · MGI Chat</p>
                    <h2 class="mt-1.5 font-display text-2xl font-semibold leading-tight sm:text-3xl">
                        {{ $greeting }}, {{ $firstName }}
                    </h2>
                    <p class="mt-2 max-w-lg text-sm leading-relaxed text-white/75">
                        Acompanhe a operação de atendimento em tempo real e priorize quem está na fila.
                    </p>
                </div>

                <div class="flex flex-wrap items-stretch gap-2">
                    <div class="min-w-24 rounded-xl border border-white/20 bg-white/10 px-3.5 py-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-white/60">Na fila</p>
                        <p class="mt-0.5 text-xl font-bold">{{ $metrics['waiting_conversations'] }}</p>
                    </div>
                    <div class="min-w-24 rounded-xl border border-white/20 bg-white/10 px-3.5 py-2.5">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-white/60">Hoje</p>
                        <p class="mt-0.5 text-xl font-bold">{{ $metrics['conversations_today'] }}</p>
                    </div>
                    <a href="{{ route('conversations.index') }}"
                       class="inline-flex items-center gap-2 rounded-xl border border-white bg-white px-4 py-2.5 text-sm font-semibold text-[#8B1E3F] transition hover:bg-slate-50">
                        Ir para conversas
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>
            </div>
        </section>

        @if ($metrics['waiting_conversations'] > 0)
            <a href="{{ route('conversations.index') }}?status=waiting"
               class="dash-reveal dash-reveal-delay-1 flex items-center justify-between gap-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 transition hover:bg-red-100">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 bg-white text-red-700">
                        @include('components.ui.kpi-icon', ['icon' => 'alert'])
                    </span>
                    <div>
                        <p class="font-semibold text-[#991B1B]">{{ $metrics['waiting_conversations'] }} conversa(s) aguardando atendimento</p>
                        <p class="text-sm text-[#B91C1C]/80">Abra a fila agora para reduzir o tempo de espera.</p>
                    </div>
                </div>
                <span class="shrink-0 text-sm font-semibold text-[#B91C1C]">Ver fila →</span>
            </a>
        @endif

        {{-- KPIs --}}
        <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
            @foreach ($kpis as $i => $kpi)
                <article class="dash-reveal dash-reveal-delay-{{ min($i + 1, 4) }} rounded-xl p-4 transition {{ $tones[$kpi['tone']] }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider {{ $kpi['tone'] === 'bordo' ? 'text-white/65' : 'text-slate-400' }}">{{ $kpi['label'] }}</p>
                            <p class="mt-1.5 font-display text-2xl font-semibold tracking-tight">{{ $kpi['value'] }}</p>
                            <p class="mt-1.5 text-[11px] {{ $kpi['tone'] === 'bordo' ? 'text-white/70' : (($kpi['alert'] ?? false) ? 'font-medium text-red-700' : 'text-slate-400') }}">
                                {{ $kpi['hint'] }}
                            </p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $iconTones[$kpi['tone']] }}">
                            @include('components.ui.kpi-icon', ['icon' => $kpi['icon']])
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        {{-- Charts --}}
        <section class="grid gap-4 lg:grid-cols-2">
            <div class="dash-reveal dash-reveal-delay-2 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="font-display text-base font-semibold text-slate-900">Atendimentos — 7 dias</h2>
                        <p class="mt-1 text-sm text-slate-400">Volume diário de novas conversas</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total</p>
                        <p class="font-display text-xl font-semibold text-[#8B1E3F]">{{ $weekTotal }}</p>
                    </div>
                </div>
                <canvas id="attendanceChart" height="170"
                        data-labels='@json($attendanceChart['labels'])'
                        data-values='@json($attendanceChart['values'])'
                        data-type="line" data-color="#8B1E3F"></canvas>
            </div>

            <div class="dash-reveal dash-reveal-delay-3 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                <div class="mb-4 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="font-display text-base font-semibold text-slate-900">Atendimentos mensais</h2>
                        <p class="mt-1 text-sm text-slate-400">Comparativo dos últimos 6 meses</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Acumulado</p>
                        <p class="font-display text-xl font-semibold text-slate-800">{{ number_format($monthTotal, 0, ',', '.') }}</p>
                    </div>
                </div>
                <canvas id="monthlyChart" height="170"
                        data-labels='@json($monthlyChart['labels'])'
                        data-values='@json($monthlyChart['values'])'
                        data-type="bar" data-color="#8B1E3F"></canvas>
            </div>
        </section>

        {{-- Recent conversations --}}
        <section class="dash-reveal dash-reveal-delay-4 overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3.5 sm:px-5">
                <div>
                    <h2 class="font-display text-base font-semibold text-slate-900">Últimas conversas</h2>
                    <p class="text-sm text-slate-400">Atividade mais recente no inbox</p>
                </div>
                <a href="{{ route('conversations.index') }}" class="text-sm font-semibold text-[#8B1E3F] hover:text-[#5C1529]">Ver todas</a>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($recentConversations as $conversation)
                    @php
                        $statusKey = $conversation->status->value ?? 'waiting';
                        $statusStyles = [
                            'waiting' => 'bg-amber-50 text-amber-700 ring-amber-200',
                            'in_progress' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                            'bot_active' => 'bg-slate-50 text-[#8B1E3F] ring-slate-200',
                            'bot_closed' => 'bg-slate-50 text-[#5C1529] ring-slate-200',
                            'resolved' => 'bg-slate-50 text-slate-600 ring-slate-200',
                            'closed' => 'bg-slate-50 text-slate-600 ring-slate-200',
                        ];
                    @endphp
                    <a href="{{ route('conversations.index') }}"
                       class="flex items-center gap-3 px-4 py-3 transition hover:bg-slate-50 sm:px-5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-xs font-bold text-[#5C1529]">
                            {{ strtoupper(substr($conversation->client->name ?? 'C', 0, 2)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-800">{{ $conversation->client->name ?? 'Cliente' }}</p>
                            <p class="truncate text-sm text-slate-400">
                                {{ $conversation->assignedAgent->name ?? 'Sem atendente' }}
                                · {{ $conversation->client->phone ?? '—' }}
                            </p>
                        </div>
                        <span class="hidden rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 sm:inline {{ $statusStyles[$statusKey] ?? 'bg-slate-50 text-slate-600 ring-slate-200' }}">
                            {{ $conversation->status->label() }}
                        </span>
                        <span class="shrink-0 text-xs text-slate-400">
                            {{ $conversation->last_message_at?->diffForHumans() ?? $conversation->created_at->diffForHumans() }}
                        </span>
                    </a>
                @empty
                    <div class="px-5 py-14 text-center">
                        <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-[#8B1E3F]">
                            @include('components.ui.kpi-icon', ['icon' => 'chat'])
                        </div>
                        <p class="font-medium text-slate-600">Nenhuma conversa ainda</p>
                        <p class="mt-1 text-sm text-slate-400">Quando os clientes enviarem WhatsApp, elas aparecem aqui.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-layout.app>
