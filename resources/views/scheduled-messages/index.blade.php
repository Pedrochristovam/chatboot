<x-layout.app :title="'Mensagens agendadas - MGI Chat'">
    <x-slot name="header">Mensagens agendadas</x-slot>

    <div class="mx-auto max-w-6xl space-y-5">
        <header>
            <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-[#8B1E3F]">Envios</p>
            <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-slate-900">Fila de agendamentos</h2>
            <p class="mt-1 text-sm text-slate-500">Mensagens criadas no chat e despachadas pelo comando <code class="rounded bg-slate-100 px-1">messages:dispatch-scheduled</code>.</p>
        </header>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px] text-sm">
                    <thead class="bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Conteúdo</th>
                            <th class="px-4 py-3">Quando</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Criado por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($items as $item)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $item['client_name'] ?? '—' }}</td>
                                <td class="max-w-md truncate px-4 py-3 text-slate-600">{{ $item['content'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">{{ $item['scheduled_at_label'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-700">{{ $item['status_label'] }}</span>
                                    @if (! empty($item['error_message']))
                                        <p class="mt-1 max-w-xs truncate text-[11px] text-red-600">{{ $item['error_message'] }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $item['creator_name'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-16 text-center text-sm text-slate-500">
                                    Nenhum agendamento. Abra uma conversa e use o botão <strong>Agendar</strong>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layout.app>
