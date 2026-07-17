<x-layout.app :title="'Atendentes - MGI Chat'">
    <x-slot name="header">Atendentes</x-slot>

    <div x-data="agentsApp()" class="mx-auto max-w-7xl space-y-4">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-[#8B1E3F]">Equipe</p>
                <h2 class="mt-0.5 font-display text-2xl font-semibold text-slate-900">Atendentes</h2>
                <p class="mt-1 text-sm text-slate-500">Gerencie acessos e disponibilidade da equipe de suporte.</p>
            </div>
            <button type="button" @click="openCreate()"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#8B1E3F] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#741832] focus:outline-none focus:ring-2 focus:ring-[#8B1E3F]/20 sm:w-auto">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Adicionar atendente
            </button>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="flex items-center gap-3 rounded-lg border border-[#8B1E3F] bg-white px-4 py-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-md bg-[#8B1E3F] text-white">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total</p>
                    <p class="font-display text-2xl font-semibold text-[#8B1E3F]">{{ $stats['total'] }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-md bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Online agora</p>
                    <p class="font-display text-2xl font-semibold text-emerald-600">{{ $stats['online'] }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-md bg-slate-50 text-[#8B1E3F]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Prontos para atender</p>
                    <p class="font-display text-2xl font-semibold text-[#5C1529]">{{ $stats['online'] }}/{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($agents as $agent)
            <article class="group overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:border-slate-300">
                <div class="h-1 bg-[#8B1E3F]"></div>
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="relative shrink-0">
                            <div class="flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-sm font-bold text-[#8B1E3F]">
                                {{ strtoupper(substr($agent->name, 0, 2)) }}
                            </div>
                            <span class="absolute bottom-0 right-0 h-3.5 w-3.5 rounded-full border-2 border-white {{ $agent->isOnline() ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-800">{{ $agent->name }}</p>
                            <p class="truncate text-xs text-slate-400">{{ $agent->email }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-bold {{ $agent->hasRole('super-admin') || $agent->hasRole('administrador') ? 'text-slate-700' : 'text-[#8B1E3F]' }}">
                            {{ $agent->hasRole('super-admin') ? 'Admin' : 'Atendente' }}
                        </span>
                        <span class="rounded-md border px-2 py-0.5 text-[11px] font-semibold {{ $agent->isOnline() ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500' }}">
                            {{ $agent->isOnline() ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                    @if ($agent->departments->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-1">
                        @foreach ($agent->departments->take(3) as $dept)
                            <span class="rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-medium text-slate-500">{{ $dept->name }}</span>
                        @endforeach
                    </div>
                    @endif
                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Último acesso</p>
                        <p class="mt-0.5 text-xs font-medium text-slate-600">{{ $agent->last_seen_at?->diffForHumans() ?? 'Nunca' }}</p>
                    </div>
                </div>
            </article>
            @endforeach

            @if ($agents->isEmpty())
            <div class="col-span-full rounded-lg border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H2v-2a4 4 0 014-4h3m4 6v-2a4 4 0 00-4-4m4 6H9m8-10a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <p class="mt-3 font-medium text-slate-700">Nenhum atendente cadastrado</p>
                <p class="mt-1 text-sm text-slate-500">Adicione o primeiro membro da equipe para começar.</p>
            </div>
            @endif

            <button type="button" @click="openCreate()"
                    class="flex min-h-[180px] flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white p-5 text-center transition hover:border-[#8B1E3F] hover:bg-slate-50">
                <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-[#8B1E3F]">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
                <p class="font-semibold text-[#5C1529]">Novo atendente</p>
                <p class="mt-1 text-xs text-slate-400">Convide um membro para a equipe</p>
            </button>
        </div>

        <div x-show="showModal" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="display: none;">
            <div class="absolute inset-0 bg-slate-950/45" @click="showModal = false"></div>
            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl" @click.stop>
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-display text-lg font-semibold text-slate-900">Novo atendente</h3>
                    <p class="mt-0.5 text-sm text-slate-500">Defina acesso e departamentos</p>
                </div>
                <div class="max-h-[70vh] space-y-3 overflow-y-auto p-5">
                    <div><label class="mb-1 block text-sm font-medium text-slate-700">Nome</label><input x-model="form.name" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></div>
                    <div><label class="mb-1 block text-sm font-medium text-slate-700">E-mail</label><input x-model="form.email" type="email" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></div>
                    <div><label class="mb-1 block text-sm font-medium text-slate-700">Senha</label><input x-model="form.password" type="password" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></div>
                    <div><label class="mb-1 block text-sm font-medium text-slate-700">Cargo</label><input x-model="form.role_title" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Perfil</label>
                        <select x-model="form.role_id" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10">
                            <option value="">Selecione...</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700">Departamentos</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($departments as $dept)
                                <label class="flex cursor-pointer items-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-sm transition hover:border-[#8B1E3F]">
                                    <input type="checkbox" value="{{ $dept->id }}" x-model="form.department_ids" class="rounded text-[#8B1E3F]">
                                    {{ $dept->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="showModal = false" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="button" @click="save()" :disabled="saving" class="rounded-md bg-[#8B1E3F] px-5 py-2 text-sm font-semibold text-white hover:bg-[#741832] disabled:cursor-wait disabled:opacity-50"><span x-text="saving ? 'Salvando...' : 'Salvar'"></span></button>
                </div>
            </div>
        </div>
    </div>
</x-layout.app>
