<x-layout.app :title="'Clientes - MGI chat'">
    <x-slot name="header">Clientes</x-slot>

    <div x-data="clientsApp()" class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-[#8B1E3F]">Base de contatos</p>
                <h2 class="font-display text-2xl font-semibold text-[#5C1529]">Clientes</h2>
                <p class="mt-1 text-sm text-slate-500">Gerencie contatos, tags e histórico de interações.</p>
            </div>
            <button type="button" @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-2xl bg-[#8B1E3F] px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Novo cliente
            </button>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl bg-[#8B1E3F] p-5 text-white">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-white/70">Novos no mês</p>
                <p class="mt-2 font-display text-3xl font-semibold">+{{ $stats['new_this_month'] }}</p>
            </div>
            <div class="rounded-2xl bg-white p-5 ring-1 ring-slate-200">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Taxa de atividade</p>
                <p class="mt-2 font-display text-3xl font-semibold text-[#5C1529]">{{ $stats['activity_rate'] }}%</p>
            </div>
            <div class="rounded-2xl bg-white p-5 ring-1 ring-slate-200">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Clientes VIP</p>
                <p class="mt-2 font-display text-3xl font-semibold text-[#8B1E3F]">{{ $stats['vip'] }}</p>
            </div>
        </div>

        <form method="GET" class="flex flex-col gap-3 rounded-2xl bg-white p-4 ring-1 ring-slate-200 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar nome, e-mail ou telefone..."
                       class="w-full rounded-2xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
            </div>
            <select name="status" class="rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]">
                <option value="">Status: Todos</option>
                <option value="active" @selected(request('status') === 'active')>Ativo</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inativo</option>
            </select>
            <button type="submit" class="rounded-2xl bg-[#8B1E3F] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90">Filtrar</button>
        </form>

        <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-white text-left text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3.5">Nome</th>
                            <th class="px-5 py-3.5">Telefone</th>
                            <th class="px-5 py-3.5 hidden md:table-cell">E-mail</th>
                            <th class="px-5 py-3.5 hidden lg:table-cell">Empresa</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 hidden md:table-cell">Último contato</th>
                            <th class="px-5 py-3.5">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F3EAED]">
                        @forelse ($clients as $client)
                        <tr class="transition hover:bg-white">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-[#5C1529]">{{ strtoupper(substr($client->name, 0, 2)) }}</div>
                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $client->name }}</p>
                                        <p class="text-xs text-slate-400">#{{ $client->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">{{ $client->phone }}</td>
                            <td class="px-5 py-4 hidden text-slate-600 md:table-cell">{{ $client->email ?? '—' }}</td>
                            <td class="px-5 py-4 hidden text-slate-600 lg:table-cell">{{ $client->company ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $client->status->value === 'active' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-50 text-slate-500 ring-1 ring-slate-200' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $client->status->value === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $client->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 hidden text-slate-400 md:table-cell">{{ $client->last_contact_at?->diffForHumans() ?? '—' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex gap-1">
                                    <button type="button" @click="openEdit({{ Js::from($client->load('tags')) }})" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-50 hover:text-[#8B1E3F]" title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <button type="button" @click="remove({{ Js::from(['id' => $client->id, 'name' => $client->name]) }})" class="rounded-xl p-2 text-slate-400 transition hover:bg-red-50 hover:text-red-500" title="Excluir">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-xl">👥</div>
                                <p class="font-medium text-slate-600">Nenhum cliente cadastrado</p>
                                <p class="mt-1 text-sm text-slate-400">Clique em “Novo cliente” para começar.</p>
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
            <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>
            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-200" @click.stop>
                <div class="border-b border-slate-100 bg-white px-6 py-4">
                    <h3 class="font-display text-lg font-semibold text-[#5C1529]" x-text="editing ? 'Editar cliente' : 'Novo cliente'"></h3>
                    <p class="text-sm text-slate-400">Preencha os dados do contato</p>
                </div>
                <div class="space-y-4 p-6">
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nome</label><input x-model="form.name" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Telefone</label><input x-model="form.phone" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">E-mail</label><input x-model="form.email" type="email" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Empresa</label><input x-model="form.company" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Observações</label><textarea x-model="form.notes" rows="3" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]"></textarea></div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 bg-white/50 px-6 py-4">
                    <button type="button" @click="showModal = false" class="rounded-2xl px-4 py-2.5 text-sm font-medium text-slate-500 hover:bg-white">Cancelar</button>
                    <button type="button" @click="save()" :disabled="saving" class="rounded-2xl bg-[#8B1E3F] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90 disabled:opacity-50">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</x-layout.app>
