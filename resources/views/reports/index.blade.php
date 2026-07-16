@php
    $periodLabel = now()->translatedFormat('F Y');
    $weekTotal = array_sum($dailyChart['values'] ?? []);
    $maxMsg = max(array_column($messagesByAgent, 'total') ?: [1]);

    $kpis = [
        [
            'label' => 'Atendimentos',
            'value' => number_format($metrics['total_conversations'], 0, ',', '.'),
            'hint' => 'conversas no mês',
            'icon' => 'chat',
            'tone' => 'bordo',
        ],
        [
            'label' => 'Msgs da equipe',
            'value' => number_format($metrics['messages_sent'], 0, ',', '.'),
            'hint' => 'enviadas por atendentes',
            'icon' => 'headset',
            'tone' => 'soft',
        ],
        [
            'label' => 'Tempo médio',
            'value' => $metrics['avg_response_time'],
            'hint' => 'até a 1ª resposta',
            'icon' => 'clock',
            'tone' => 'soft',
        ],
        [
            'label' => 'Taxa resolvida',
            'value' => $metrics['conversion_rate'] . '%',
            'hint' => 'conversas finalizadas',
            'icon' => 'check',
            'tone' => 'soft',
        ],
    ];

    $tones = [
        'bordo' => 'bg-[#8B1E3F] text-white',
        'soft' => 'bg-white text-slate-800 ring-1 ring-slate-200/80',
    ];
    $iconTones = [
        'bordo' => 'bg-white/15 text-white',
        'soft' => 'bg-slate-50 text-[#8B1E3F]',
    ];
@endphp

<x-layout.app :title="'Relatórios - MGI chat'">
    <x-slot name="header">Relatórios</x-slot>

    <div class="mx-auto max-w-7xl space-y-6">
        {{-- Hero --}}
        <section class="relative overflow-hidden rounded-3xl bg-[#8B1E3F] px-6 py-7 text-white sm:px-8">
            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/65">MGI chat · Performance</p>
                    <h2 class="mt-2 font-display text-3xl font-semibold leading-tight sm:text-4xl">Relatórios</h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/75">
                        Visão do mês: volume de atendimento, ritmo da equipe e resolução das conversas.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm ring-1 ring-white/15">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-white/60">Período</p>
                        <p class="mt-0.5 text-lg font-bold capitalize">{{ $periodLabel }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm ring-1 ring-white/15">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-white/60">Últimos 7 dias</p>
                        <p class="mt-0.5 text-lg font-bold">{{ $weekTotal }} conversas</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- KPIs --}}
        <section>
            <div class="mb-3 flex items-end justify-between gap-3">
                <div>
                    <h3 class="font-display text-lg font-semibold text-[#5C1529]">Resumo do mês</h3>
                    <p class="text-sm text-slate-500">Números principais do período atual</p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($kpis as $kpi)
                    <article class="rounded-2xl p-5 transition duration-300 hover:-translate-y-0.5 {{ $tones[$kpi['tone']] }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider {{ $kpi['tone'] === 'bordo' ? 'text-white/65' : 'text-slate-400' }}">{{ $kpi['label'] }}</p>
                                <p class="mt-2 font-display text-3xl font-semibold tracking-tight">{{ $kpi['value'] }}</p>
                                <p class="mt-2 text-xs {{ $kpi['tone'] === 'bordo' ? 'text-white/70' : 'text-slate-400' }}">{{ $kpi['hint'] }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $iconTones[$kpi['tone']] }}">
                                @include('components.ui.kpi-icon', ['icon' => $kpi['icon']])
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Volume + Equipe --}}
        <div class="grid gap-5 lg:grid-cols-12">
            <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/80 lg:col-span-7">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-[#8B1E3F]">Volume</p>
                        <h3 class="mt-1 font-display text-lg font-semibold text-[#5C1529]">Últimos 7 dias</h3>
                        <p class="mt-0.5 text-sm text-slate-500">Novas conversas por dia</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Total</p>
                        <p class="font-display text-xl font-semibold text-[#8B1E3F]">{{ $weekTotal }}</p>
                    </div>
                </div>
                <div class="px-4 py-4 sm:px-5">
                    <div class="relative h-44 w-full sm:h-48">
                        <canvas id="reportDailyChart"
                                class="absolute inset-0 h-full w-full"
                                data-labels='@json($dailyChart['labels'])'
                                data-values='@json($dailyChart['values'])'
                                data-type="bar"
                                data-color="#8B1E3F"
                                data-compact="true"></canvas>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/80 lg:col-span-5">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-[#8B1E3F]">Equipe</p>
                    <h3 class="mt-1 font-display text-lg font-semibold text-[#5C1529]">Mensagens por atendente</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Quem mais atendeu no período</p>
                </div>
                <div class="space-y-5 p-5 sm:p-6">
                    @forelse ($messagesByAgent as $index => $agent)
                        @php $pct = $maxMsg > 0 ? round(($agent['total'] / $maxMsg) * 100) : 0; @endphp
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-[#5C1529]">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="truncate font-semibold text-slate-800">{{ $agent['name'] }}</span>
                                </div>
                                <span class="shrink-0 text-sm font-medium text-slate-500">{{ number_format($agent['total'], 0, ',', '.') }} msg</span>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-[#8B1E3F] transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-[#8B1E3F]">
                                @include('components.ui.kpi-icon', ['icon' => 'headset'])
                            </div>
                            <p class="font-medium text-slate-600">Sem mensagens de atendentes ainda</p>
                            <p class="mt-1 text-sm text-slate-400">Quando a equipe responder clientes, o ranking aparece aqui.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Departamentos --}}
        <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200/80">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-[#8B1E3F]">Áreas</p>
                    <h3 class="mt-1 font-display text-lg font-semibold text-[#5C1529]">Desempenho por departamento</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Volume de conversas por área</p>
                </div>

                @if (count($departments))
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                    <th class="px-5 py-3 sm:px-6">Departamento</th>
                                    <th class="px-3 py-3">Atendimentos</th>
                                    <th class="px-3 py-3">Tempo médio</th>
                                    <th class="px-5 py-3 sm:px-6">Situação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($departments as $dept)
                                    <tr class="transition hover:bg-slate-50/70">
                                        <td class="px-5 py-4 font-semibold text-slate-800 sm:px-6">{{ $dept['name'] }}</td>
                                        <td class="px-3 py-4 tabular-nums text-slate-600">{{ number_format($dept['total'], 0, ',', '.') }}</td>
                                        <td class="px-3 py-4 text-slate-500">{{ $dept['avg_time'] }}</td>
                                        <td class="px-5 py-4 sm:px-6">
                                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                {{ $dept['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-5 py-14 text-center sm:px-6">
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-[#8B1E3F]">
                            @include('components.ui.kpi-icon', ['icon' => 'chat'])
                        </div>
                        <p class="font-medium text-slate-600">Nenhum departamento com dados</p>
                        <p class="mt-1 text-sm text-slate-400">Quando as conversas tiverem área definida, a tabela preenche sozinha.</p>
                    </div>
                @endif
        </section>

        {{-- Clientes --}}
        <section class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Base</p>
                        <h3 class="mt-1 font-display text-lg font-semibold text-[#5C1529]">Novos clientes</h3>
                        <p class="mt-1 text-sm text-slate-500">Entraram no CRM neste mês</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-50 text-[#8B1E3F]">
                        @include('components.ui.kpi-icon', ['icon' => 'users'])
                    </div>
                </div>
                <p class="mt-6 font-display text-4xl font-semibold text-[#8B1E3F]">{{ number_format($metrics['new_clients'], 0, ',', '.') }}</p>
                <a href="{{ route('clients.index') }}" class="mt-4 inline-flex text-sm font-semibold text-[#8B1E3F] hover:text-[#5C1529]">Ver clientes →</a>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Retorno</p>
                        <h3 class="mt-1 font-display text-lg font-semibold text-[#5C1529]">Clientes recorrentes</h3>
                        <p class="mt-1 text-sm text-slate-500">Abriram mais de uma conversa</p>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-50 text-[#8B1E3F]">
                        @include('components.ui.kpi-icon', ['icon' => 'check'])
                    </div>
                </div>
                <p class="mt-6 font-display text-4xl font-semibold text-[#5C1529]">{{ number_format($metrics['recurring_clients'], 0, ',', '.') }}</p>
                <a href="{{ route('conversations.index') }}" class="mt-4 inline-flex text-sm font-semibold text-[#8B1E3F] hover:text-[#5C1529]">Ver conversas →</a>
            </div>
        </section>
    </div>
</x-layout.app>
