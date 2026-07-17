<x-layout.app :title="'Clientes - MGI Chat'">
    <x-slot name="header">Clientes</x-slot>

    <div x-data="clientsApp()" class="mx-auto max-w-7xl space-y-4">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-[#8B1E3F]">Base de contatos</p>
                <h2 class="mt-0.5 font-display text-2xl font-semibold text-slate-900">Clientes</h2>
                <p class="mt-1 text-sm text-slate-500">Gerencie contatos, tags e histórico de interações.</p>
            </div>
            <button type="button" @click="openCreate()"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#8B1E3F] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#741832] focus:outline-none focus:ring-2 focus:ring-[#8B1E3F]/20 sm:w-auto">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Novo cliente
            </button>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-[#8B1E3F] bg-white px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Novos no mês</p>
                <p class="mt-1 font-display text-2xl font-semibold text-[#8B1E3F]">+{{ $stats['new_this_month'] }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Taxa de atividade</p>
                <p class="mt-1 font-display text-2xl font-semibold text-slate-900">{{ $stats['activity_rate'] }}%</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Clientes VIP</p>
                <p class="mt-1 font-display text-2xl font-semibold text-slate-900">{{ $stats['vip'] }}</p>
            </div>
        </div>

        <form method="GET" class="flex flex-col gap-2 rounded-lg border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar nome, e-mail ou telefone..."
                       class="w-full rounded-md border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10">
            </div>
            <select name="status" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#8B1E3F]">
                <option value="">Status: Todos</option>
                <option value="active" @selected(request('status') === 'active')>Ativo</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inativo</option>
            </select>
            <button type="submit" class="rounded-md bg-[#8B1E3F] px-5 py-2 text-sm font-semibold text-white hover:bg-[#741832]">Filtrar</button>
        </form>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Nome</th>
                            <th class="px-4 py-3">Telefone</th>
                            <th class="hidden px-4 py-3 md:table-cell">E-mail</th>
                            <th class="hidden px-4 py-3 lg:table-cell">Empresa</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="hidden px-4 py-3 md:table-cell">Último contato</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($clients as $client)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-xs font-bold text-[#8B1E3F]">{{ strtoupper(substr($client->name, 0, 2)) }}</div>
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $client->name }}</p>
                                        <p class="text-xs text-slate-400">#{{ $client->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $client->phone }}</td>
                            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">{{ $client->email ?? '—' }}</td>
                            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">{{ $client->company ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs font-semibold {{ $client->status->value === 'active' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $client->status->value === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $client->status->label() }}
                                </span>
                            </td>
                            <td class="hidden px-4 py-3 text-slate-500 md:table-cell">{{ $client->last_contact_at?->diffForHumans() ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <button type="button" @click="openEdit({{ Js::from($client->load('tags')) }})" class="rounded-md border border-transparent p-2 text-slate-500 transition hover:border-slate-200 hover:bg-white hover:text-[#8B1E3F]" title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <button type="button" @click="remove({{ Js::from(['id' => $client->id, 'name' => $client->name]) }})" class="rounded-md border border-transparent p-2 text-slate-500 transition hover:border-red-100 hover:bg-red-50 hover:text-red-600" title="Excluir">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-5 py-14 text-center">
                                <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H2v-2a4 4 0 014-4h3m4 6v-2a4 4 0 00-4-4m4 6H9m8-10a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                </div>
                                <p class="font-medium text-slate-700">{{ request()->hasAny(['search', 'status']) ? 'Nenhum cliente encontrado' : 'Nenhum cliente cadastrado' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ request()->hasAny(['search', 'status']) ? 'Ajuste os filtros para ampliar a busca.' : 'Clique em “Novo cliente” para começar.' }}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($clients->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $clients->links() }}</div>
            @endif
        </div>

        <div x-show="showModal" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="display: none;">
            <div class="absolute inset-0 bg-slate-950/45" @click="showModal = false"></div>
            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl" @click.stop>
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-display text-lg font-semibold text-slate-900" x-text="editing ? 'Editar cliente' : 'Novo cliente'"></h3>
                    <p class="mt-0.5 text-sm text-slate-500">Preencha os dados do contato</p>
                </div>
                <div class="max-h-[70vh] space-y-3 overflow-y-auto p-5">
                    <div><label class="mb-1 block text-sm font-medium text-slate-700">Nome</label><input x-model="form.name" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></div>
                    <div><label class="mb-1 block text-sm font-medium text-slate-700">Telefone</label><input x-model="form.phone" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></div>
                    <div><label class="mb-1 block text-sm font-medium text-slate-700">E-mail</label><input x-model="form.email" type="email" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></div>
                    <div><label class="mb-1 block text-sm font-medium text-slate-700">Empresa</label><input x-model="form.company" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></div>
                    <div><label class="mb-1 block text-sm font-medium text-slate-700">Observações</label><textarea x-model="form.notes" rows="3" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></textarea></div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="showModal = false" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="button" @click="save()" :disabled="saving" class="rounded-md bg-[#8B1E3F] px-5 py-2 text-sm font-semibold text-white hover:bg-[#741832] disabled:cursor-wait disabled:opacity-50"><span x-text="saving ? 'Salvando...' : 'Salvar'"></span></button>
                </div>
            </div>
        </div>
    </div>
</x-layout.app>
