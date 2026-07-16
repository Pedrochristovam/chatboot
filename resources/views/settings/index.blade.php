<x-layout.app :title="'Configurações - MGI chat'">
    <x-slot name="header">Configurações</x-slot>

    <div class="space-y-6" x-data="settingsApp()" x-init="init()">
        <script type="application/json" id="settings-initial-data">@json($settings)</script>

        <div class="flex flex-col gap-4 lg:flex-row lg:items-stretch">
            <div class="flex-1 rounded-3xl bg-[#8B1E3F] p-6 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider text-white/70">Workspace</p>
                <h2 class="mt-2 font-display text-2xl font-semibold">Personalize o MGI chat</h2>
                <p class="mt-2 max-w-lg text-sm text-white/75">Identidade, horário comercial, respostas automáticas e integração com WhatsApp Meta.</p>
            </div>
            <div class="flex min-w-[16rem] items-center gap-4 rounded-3xl bg-white p-6 ring-1 ring-slate-200">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-[#8B1E3F]">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">API WhatsApp</p>
                    <p class="font-semibold text-[#5C1529]">Pronto para configurar</p>
                    <p class="text-xs text-slate-400">Meta Cloud API</p>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row">
            <div class="flex flex-row gap-2 overflow-x-auto lg:w-56 lg:flex-col">
                @foreach ([
                    ['geral','Geral','M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                    ['horario','Horário','M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['mensagem','Mensagem auto','M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                    ['api','WhatsApp Meta','M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                ] as $tab)
                <button type="button" @click="activeTab = '{{ $tab[0] }}'"
                        :class="activeTab === '{{ $tab[0] }}' ? 'bg-[#8B1E3F] text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-white'"
                        class="flex shrink-0 items-center gap-2.5 rounded-2xl px-4 py-3 text-sm font-semibold transition">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab[2] }}"/></svg>
                    {{ $tab[1] }}
                </button>
                @endforeach
            </div>

            <div class="flex-1 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <div x-show="activeTab === 'geral'">
                    <h3 class="font-display text-xl font-semibold text-[#5C1529]">Informações da empresa</h3>
                    <p class="mb-6 text-sm text-slate-400">Dados principais da conta MGI chat.</p>
                    <div class="space-y-5">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Nome da empresa</label>
                            <input type="text" x-model="form.company_name"
                                   class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Cor da marca</label>
                            <div class="flex items-center gap-3">
                                <div class="h-11 w-11 rounded-2xl bg-[#8B1E3F] shadow-sm ring-2 ring-slate-200"></div>
                                <input type="text" x-model="form.primary_color" class="w-36 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-mono outline-none focus:border-[#8B1E3F]">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Logo</label>
                            <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white py-10 text-center">
                                <svg class="mb-2 h-8 w-8 text-[#E8C4CE]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <p class="text-sm font-medium text-slate-500">Arraste a logo ou clique para buscar</p>
                                <p class="mt-1 text-xs text-slate-400">PNG ou JPG até 5MB</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'horario'" x-cloak>
                    <h3 class="font-display text-xl font-semibold text-[#5C1529]">Horário comercial</h3>
                    <p class="mb-6 text-sm text-slate-400">Fora desse intervalo, a mensagem automática é enviada.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Início</label><input type="time" x-model="form.business_start" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]"></div>
                        <div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Fim</label><input type="time" x-model="form.business_end" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:border-[#8B1E3F]"></div>
                    </div>
                </div>

                <div x-show="activeTab === 'mensagem'" x-cloak>
                    <h3 class="font-display text-xl font-semibold text-[#5C1529]">Mensagem automática</h3>
                    <p class="mb-6 text-sm text-slate-400">Enviada quando o cliente escreve fora do horário.</p>
                    <textarea rows="5" x-model="form.auto_reply" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15"></textarea>
                </div>

                <div x-show="activeTab === 'api'" x-cloak>
                    <h3 class="font-display text-xl font-semibold text-[#5C1529]">WhatsApp Meta Cloud API</h3>
                    <p class="mb-4 text-sm text-slate-400">Conecte o número oficial do WhatsApp Business.</p>

                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                        <p class="font-semibold">Como obter as credenciais:</p>
                        <ol class="mt-2 list-decimal space-y-1 pl-5">
                            <li>Acesse <a href="https://developers.facebook.com" target="_blank" class="underline">developers.facebook.com</a></li>
                            <li>Crie um App → tipo <strong>Business</strong></li>
                            <li>Adicione o produto <strong>WhatsApp</strong></li>
                            <li>Em API Setup, copie o <strong>Token</strong> e o <strong>Phone Number ID</strong></li>
                            <li>Configure o webhook com o Verify Token abaixo</li>
                        </ol>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Modo</label>
                            <select x-model="form.whatsapp_driver" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm">
                                <option value="null">Simulado (teste local)</option>
                                <option value="meta">WhatsApp Meta Cloud API</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Token de acesso</label>
                            <input type="password" x-model="form.meta_token" placeholder="EAAxxxx..." class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-mono">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Phone Number ID</label>
                            <input type="text" x-model="form.meta_phone_number_id" placeholder="123456789012345" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-mono">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">Webhook Verify Token</label>
                            <input type="text" x-model="form.webhook_verify_token" placeholder="mgi_webhook_secret" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-mono">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">URL do webhook</label>
                            <div class="flex gap-2">
                                <input type="text" readonly :value="form.webhook_callback_url" class="flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-mono text-slate-600">
                                <button type="button" @click="navigator.clipboard.writeText(form.webhook_callback_url); Swal.fire('Copiado!', 'URL do webhook copiada.', 'success')" class="shrink-0 rounded-2xl bg-[#8B1E3F] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">Copiar</button>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">Use HTTPS público (ex.: ngrok) em desenvolvimento.</p>
                        </div>
                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4">
                            <input type="checkbox" x-model="form.bot_enabled" class="rounded text-[#8B1E3F]">
                            <span class="text-sm text-slate-700">Bot automático ativo (responde antes do atendente)</span>
                        </label>
                    </div>

                    <div class="mt-6 rounded-2xl bg-slate-50 p-4 text-sm text-[#5C1529] ring-1 ring-slate-200">
                        <p class="font-semibold">Na Meta, configure:</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            <li>Callback URL = URL acima</li>
                            <li>Verify Token = o mesmo digitado aqui</li>
                            <li>Assinar o campo <strong>messages</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <button type="button" class="rounded-2xl px-4 py-2.5 text-sm font-semibold text-slate-400 hover:bg-white hover:text-slate-600">Descartar</button>
            <button type="button" @click="save()" :disabled="saving"
                    class="inline-flex items-center gap-2 rounded-2xl bg-[#8B1E3F] px-6 py-3 text-sm font-bold text-white transition hover:opacity-90 disabled:opacity-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Salvar configurações
            </button>
        </div>
    </div>
</x-layout.app>
