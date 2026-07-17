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
        'bordo' => 'border-[#8B1E3F]',
        'soft' => 'border-slate-200',
    ];
@endphp

<x-layout.app :title="'Relatórios - MGI Chat'">
    <x-slot name="header">Relatórios</x-slot>

    <div class="mx-auto max-w-7xl space-y-4">
        <section class="rounded-xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#8B1E3F]">Operações · Performance</p>
                    <h2 class="mt-1 font-display text-xl font-semibold text-slate-900">Visão geral dos atendimentos</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Volume, produtividade e desempenho por área.</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="rounded-lg border border-slate-200 px-3 py-2">
                        <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Período</p>
                        <p class="text-xs font-semibold capitalize text-slate-700">{{ $periodLabel }}</p>
                    </div>
                    <div class="rounded-lg bg-[#8B1E3F] px-3 py-2 text-white">
                        <p class="text-[9px] font-bold uppercase tracking-wider text-white/70">7 dias</p>
                        <p class="text-xs font-semibold">{{ $weekTotal }} conversas</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- KPIs --}}
        <section aria-labelledby="report-kpis-title">
            <div class="mb-2 flex items-center justify-between">
                <h3 id="report-kpis-title" class="text-xs font-bold uppercase tracking-wider text-slate-500">Resumo do mês</h3>
                <span class="text-xs text-slate-400">Período atual</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($kpis as $kpi)
                    <article class="rounded-xl border bg-white p-4 shadow-sm {{ $tones[$kpi['tone']] }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $kpi['label'] }}</p>
                                <p class="mt-1 font-display text-2xl font-semibold tracking-tight text-slate-900">{{ $kpi['value'] }}</p>
                                <p class="mt-1 text-[11px] text-slate-500">{{ $kpi['hint'] }}</p>
                            </div>
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#8B1E3F] text-white">
                                @include('components.ui.kpi-icon', ['icon' => $kpi['icon']])
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Volume + Equipe --}}
        <div class="grid gap-4 lg:grid-cols-12">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-7">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-[#8B1E3F]">Volume</p>
                        <h3 class="font-display text-base font-semibold text-slate-900">Conversas nos últimos 7 dias</h3>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Total</p>
                        <p class="font-display text-lg font-semibold text-[#8B1E3F]">{{ $weekTotal }}</p>
                    </div>
                </div>
                <div class="px-4 py-3">
                    <div class="relative h-56 w-full">
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

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-5">
                <div class="border-b border-slate-200 px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-[#8B1E3F]">Ranking da equipe</p>
                    <h3 class="font-display text-base font-semibold text-slate-900">Mensagens por atendente</h3>
                </div>
                <div class="space-y-4 p-4">
                    @forelse ($messagesByAgent as $index => $agent)
                        @php $pct = $maxMsg > 0 ? round(($agent['total'] / $maxMsg) * 100) : 0; @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-slate-100 text-[11px] font-bold text-[#8B1E3F]">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="truncate text-sm font-semibold text-slate-800">{{ $agent['name'] }}</span>
                                </div>
                                <span class="shrink-0 text-sm font-medium text-slate-500">{{ number_format($agent['total'], 0, ',', '.') }} msg</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-[#8B1E3F]" style="width: {{ $pct }}%"></div>
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
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-[#8B1E3F]">Departamentos</p>
                    <h3 class="font-display text-base font-semibold text-slate-900">Desempenho por área</h3>
                </div>

                @if (count($departments))
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">
                                    <th class="px-4 py-2.5">Departamento</th>
                                    <th class="px-3 py-3">Atendimentos</th>
                                    <th class="px-3 py-3">Tempo médio</th>
                                    <th class="px-4 py-2.5">Situação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($departments as $dept)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $dept['name'] }}</td>
                                        <td class="px-3 py-3 tabular-nums text-slate-600">{{ number_format($dept['total'], 0, ',', '.') }}</td>
                                        <td class="px-3 py-3 text-slate-500">{{ $dept['avg_time'] }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
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
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Base</p>
                        <h3 class="mt-1 font-display text-lg font-semibold text-[#5C1529]">Novos clientes</h3>
                        <p class="mt-1 text-sm text-slate-500">Entraram no CRM neste mês</p>
                    </div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50 text-[#8B1E3F]">
                        @include('components.ui.kpi-icon', ['icon' => 'users'])
                    </div>
                </div>
                <p class="mt-3 font-display text-2xl font-semibold text-[#8B1E3F]">{{ number_format($metrics['new_clients'], 0, ',', '.') }}</p>
                <a href="{{ route('clients.index') }}" class="mt-2 inline-flex text-xs font-semibold text-[#8B1E3F] hover:text-[#5C1529]">Ver clientes →</a>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Retorno</p>
                        <h3 class="mt-1 font-display text-lg font-semibold text-[#5C1529]">Clientes recorrentes</h3>
                        <p class="mt-1 text-sm text-slate-500">Abriram mais de uma conversa</p>
                    </div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50 text-[#8B1E3F]">
                        @include('components.ui.kpi-icon', ['icon' => 'check'])
                    </div>
                </div>
                <p class="mt-3 font-display text-2xl font-semibold text-[#5C1529]">{{ number_format($metrics['recurring_clients'], 0, ',', '.') }}</p>
                <a href="{{ route('conversations.index') }}" class="mt-2 inline-flex text-xs font-semibold text-[#8B1E3F] hover:text-[#5C1529]">Ver conversas →</a>
            </div>
        </section>
    </div>
</x-layout.app>
