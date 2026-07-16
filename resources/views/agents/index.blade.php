<x-layout.app :title="'Atendentes - MGI chat'">
    <x-slot name="header">Atendentes</x-slot>

    <div x-data="agentsApp()" class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-[#8B1E3F]">Equipe</p>
                <h2 class="font-display text-2xl font-semibold text-[#5C1529]">Atendentes</h2>
                <p class="mt-1 text-sm text-slate-500">Gerencie acessos e disponibilidade da equipe de suporte.</p>
            </div>
            <button type="button" @click="openCreate()"
                    class="inline-flex items-center gap-2 rounded-2xl bg-[#8B1E3F] px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Adicionar atendente
            </button>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="flex items-center gap-4 rounded-2xl bg-[#8B1E3F] p-5 text-white">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-white/70">Total</p>
                    <p class="font-display text-2xl font-semibold">{{ $stats['total'] }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-2xl bg-white p-5 ring-1 ring-slate-200">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Online agora</p>
                    <p class="font-display text-2xl font-semibold text-emerald-600">{{ $stats['online'] }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-2xl bg-white p-5 ring-1 ring-slate-200">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-[#8B1E3F]">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Prontos para atender</p>
                    <p class="font-display text-2xl font-semibold text-[#5C1529]">{{ $stats['online'] }}/{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($agents as $agent)
            <div class="group overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-slate-200">
                <div class="h-2 bg-[#8B1E3F]"></div>
                <div class="p-5">
                    <div class="flex items-start gap-3">
                        <div class="relative shrink-0">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-[#5C1529]">
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
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $agent->hasRole('super-admin') || $agent->hasRole('administrador') ? 'bg-slate-50 text-[#5C1529] ring-1 ring-slate-200' : 'bg-white text-[#8B1E3F] ring-1 ring-slate-200' }}">
                            {{ $agent->hasRole('super-admin') ? 'Admin' : 'Atendente' }}
                        </span>
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $agent->isOnline() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-500' }}">
                            {{ $agent->isOnline() ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                    @if ($agent->departments->isNotEmpty())
                    <div class="mt-3 flex flex-wrap gap-1">
                        @foreach ($agent->departments->take(3) as $dept)
                            <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-medium text-slate-500 ring-1 ring-slate-200">{{ $dept->name }}</span>
                        @endforeach
                    </div>
                    @endif
                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Último acesso</p>
                        <p class="mt-0.5 text-xs font-medium text-slate-600">{{ $agent->last_seen_at?->diffForHumans() ?? 'Nunca' }}</p>
                    </div>
                </div>
            </div>
            @endforeach

            <button type="button" @click="openCreate()"
                    class="flex min-h-[200px] flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-white/50 p-5 text-center transition hover:border-[#8B1E3F] hover:bg-slate-50">
                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-[#8B1E3F] ring-1 ring-slate-200">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                </div>
                <p class="font-semibold text-[#5C1529]">Novo atendente</p>
                <p class="mt-1 text-xs text-slate-400">Convide um membro para a equipe</p>
            </button>
        </div>

        <div x-show="showModal" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="display: none;">
            <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>
            <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-slate-200" @click.stop>
                <div class="border-b border-slate-100 bg-white px-6 py-4">
                    <h3 class="font-display text-lg font-semibold text-[#5C1529]">Novo atendente</h3>
                    <p class="text-sm text-slate-400">Defina acesso e departamentos</p>
                </div>
                <div class="max-h-[70vh] space-y-4 overflow-y-auto p-6">
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Nome</label><input x-model="form.name" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">E-mail</label><input x-model="form.email" type="email" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Senha</label><input x-model="form.password" type="password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]"></div>
                    <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Cargo</label><input x-model="form.role_title" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]"></div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Perfil</label>
                        <select x-model="form.role_id" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]">
                            <option value="">Selecione...</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Departamentos</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($departments as $dept)
                                <label class="flex cursor-pointer items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-sm transition hover:border-[#8B1E3F]">
                                    <input type="checkbox" value="{{ $dept->id }}" x-model="form.department_ids" class="rounded text-[#8B1E3F]">
                                    {{ $dept->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 bg-white/50 px-6 py-4">
                    <button type="button" @click="showModal = false" class="rounded-2xl px-4 py-2.5 text-sm font-medium text-slate-500 hover:bg-white">Cancelar</button>
                    <button type="button" @click="save()" :disabled="saving" class="rounded-2xl bg-[#8B1E3F] px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-50">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</x-layout.app>
