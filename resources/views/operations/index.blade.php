<x-layout.app :title="'Operações - MGI Chat'">
    <x-slot name="header">Operações</x-slot>

    <div class="mx-auto max-w-7xl space-y-5">
        <header>
            <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-[#8B1E3F]">Confiabilidade</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Saúde do sistema</h2>
            <p class="mt-1 text-sm text-slate-500">Fila, Redis, Reverb, agendador e auditoria operacional.</p>
        </header>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Fila</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $queueStats['pending'] ?? 0 }}</p>
                <p class="mt-1 text-xs text-slate-500">pendente(s) · driver {{ $queueStats['driver'] ?? '—' }}</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Em processamento</p>
                <p class="mt-2 text-2xl font-extrabold text-slate-900">{{ $queueStats['reserved'] ?? 0 }}</p>
                <p class="mt-1 text-xs text-slate-500">jobs reservados</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Falhas</p>
                <p class="mt-2 text-2xl font-extrabold {{ ($queueStats['failed'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ $queueStats['failed'] ?? 0 }}</p>
                <p class="mt-1 text-xs text-slate-500">failed_jobs</p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Horizon</p>
                <p class="mt-2 text-sm font-bold text-slate-800">Painel de filas embutido</p>
                <p class="mt-1 text-xs text-slate-500">Use Redis + `queue:work`/`horizon` em produção (ver docker-compose).</p>
            </article>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($health['checks'] as $check)
                <article class="rounded-xl border bg-white p-4 {{ $check['ok'] ? 'border-slate-200' : 'border-red-200' }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-extrabold text-slate-800">{{ $check['label'] }}</p>
                            <p class="mt-1 text-xs {{ $check['ok'] ? 'text-slate-500' : 'text-red-600' }}">{{ $check['detail'] }}</p>
                        </div>
                        <span class="h-2.5 w-2.5 rounded-full {{ $check['ok'] ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <h3 class="text-sm font-extrabold text-slate-900">Mensagens com falha</h3>
                <p class="mt-0.5 text-xs text-slate-500">O reenvio só é permitido quando a Meta ainda não confirmou um ID.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Mensagem</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Erro</th>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($failedMessages as $message)
                            <tr>
                                <td class="max-w-xs truncate px-4 py-3 text-slate-700">{{ $message->content }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $message->conversation?->client?->name ?? '—' }}</td>
                                <td class="max-w-sm truncate px-4 py-3 text-red-600">{{ $message->error_message ?? 'Falha não detalhada' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ $message->updated_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if (! $message->whatsapp_message_id && ! ($message->metadata['bot_delivery_guard']['cancelled'] ?? false))
                                        <form method="POST" action="{{ route('operations.messages.retry', $message) }}">
                                            @csrf
                                            <button class="rounded-lg bg-[#8B1E3F] px-3 py-2 text-xs font-bold text-white hover:bg-[#721832]">Reenviar</button>
                                        </form>
                                    @else
                                        <span class="text-[11px] font-semibold text-slate-400">Reenvio bloqueado</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-14 text-center text-sm text-slate-500">Nenhuma mensagem com falha.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3">
                <h3 class="text-sm font-extrabold text-slate-900">Audit log</h3>
                <p class="mt-0.5 text-xs text-slate-500">Transferências, atribuições, encerramentos e eventos recentes.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead class="bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Quando</th>
                            <th class="px-4 py-3">Usuário</th>
                            <th class="px-4 py-3">Ação</th>
                            <th class="px-4 py-3">Modelo</th>
                            <th class="px-4 py-3">Detalhe</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($auditLogs as $log)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $log->user?->name ?? 'sistema' }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $log->action }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ class_basename((string) $log->model_type) }} #{{ $log->model_id }}</td>
                                <td class="max-w-sm truncate px-4 py-3 text-xs text-slate-500">{{ json_encode($log->new_values ?? $log->old_values) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-14 text-center text-sm text-slate-500">Nenhum evento de auditoria.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layout.app>
