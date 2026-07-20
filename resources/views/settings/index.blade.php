<x-layout.app :title="'Configurações - MGI Chat'">
    <x-slot name="header">Configurações</x-slot>

    <div class="mx-auto max-w-6xl space-y-4" x-data="settingsApp()" x-init="init()">
        <script type="application/json" id="settings-initial-data">@json($settings)</script>

        <div class="rounded-xl border border-slate-200 bg-white px-4 py-4 shadow-sm sm:px-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#8B1E3F]">Workspace</p>
                    <h2 class="mt-1 font-display text-xl font-semibold text-slate-900">Configurações da operação</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Identidade, atendimento automático e integração oficial.</p>
                </div>
                <div class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-[#8B1E3F] text-white">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <p class="text-[9px] font-bold uppercase tracking-wide text-slate-400">WhatsApp</p>
                        <p class="text-xs font-semibold text-slate-700">Meta Cloud API</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex gap-1 overflow-x-auto border-b border-slate-200 bg-slate-50 p-1.5" role="tablist" aria-label="Seções de configuração">
                @foreach ([
                    ['geral','Geral','M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                    ['horario','Horário','M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['mensagem','Mensagem auto','M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                    ['api','WhatsApp Meta','M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                ] as $tab)
                <button type="button" @click="activeTab = '{{ $tab[0] }}'"
                        role="tab"
                        :aria-selected="activeTab === '{{ $tab[0] }}'"
                        :class="activeTab === '{{ $tab[0] }}' ? 'bg-[#8B1E3F] text-white' : 'text-slate-600 hover:bg-white hover:text-slate-900'"
                        class="flex shrink-0 items-center gap-2 rounded-md px-3 py-2 text-xs font-semibold transition-colors">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab[2] }}"/></svg>
                    {{ $tab[1] }}
                </button>
                @endforeach
            </div>

            <div class="p-4 sm:p-5">
                <div x-show="activeTab === 'geral'" role="tabpanel">
                    <h3 class="font-display text-base font-semibold text-slate-900">Informações da empresa</h3>
                    <p class="mb-4 text-xs text-slate-500">Dados principais da conta MGI Chat.</p>
                    <div class="space-y-4">
                        <div>
                            <label for="company_name" class="mb-1 block text-xs font-semibold text-slate-700">Nome da empresa</label>
                            <input id="company_name" type="text" x-model="form.company_name" autocomplete="organization"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
                        </div>
                        <div>
                            <label for="primary_color" class="mb-1 block text-xs font-semibold text-slate-700">Cor da marca</label>
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-lg border border-slate-200 bg-[#8B1E3F]" aria-hidden="true"></div>
                                <input id="primary_color" type="text" x-model="form.primary_color" class="w-32 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
                            </div>
                        </div>
                        <div>
                            <span class="mb-1 block text-xs font-semibold text-slate-700">Logo</span>
                            <div class="flex items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4">
                                <svg class="h-6 w-6 shrink-0 text-[#8B1E3F]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <div><p class="text-xs font-medium text-slate-600">Arraste a logo ou clique para buscar</p><p class="text-[11px] text-slate-400">PNG ou JPG até 5MB</p></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'horario'" x-cloak role="tabpanel">
                    <h3 class="font-display text-base font-semibold text-slate-900">Horário comercial</h3>
                    <p class="mb-4 text-xs text-slate-500">Fora desse intervalo, a mensagem automática é enviada.</p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="business_start" class="mb-1 block text-xs font-semibold text-slate-700">Início</label><input id="business_start" type="time" x-model="form.business_start" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15"></div>
                        <div><label for="business_end" class="mb-1 block text-xs font-semibold text-slate-700">Fim</label><input id="business_end" type="time" x-model="form.business_end" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15"></div>
                        <div class="sm:col-span-2">
                            <label for="sla_first_response_minutes" class="mb-1 block text-xs font-semibold text-slate-700">SLA da primeira resposta (minutos úteis)</label>
                            <input id="sla_first_response_minutes" type="number" min="1" max="1440" x-model.number="form.sla_first_response_minutes" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
                            <p class="mt-1 text-[11px] text-slate-400">A contagem pausa automaticamente fora do horário comercial.</p>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'mensagem'" x-cloak role="tabpanel">
                    <h3 class="font-display text-base font-semibold text-slate-900">Mensagem automática</h3>
                    <p class="mb-4 text-xs text-slate-500">Enviada quando o cliente escreve fora do horário.</p>
                    <label for="auto_reply" class="sr-only">Mensagem automática</label>
                    <textarea id="auto_reply" rows="5" x-model="form.auto_reply" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15"></textarea>
                </div>

                <div x-show="activeTab === 'api'" x-cloak role="tabpanel">
                    <h3 class="font-display text-base font-semibold text-slate-900">WhatsApp Meta Cloud API</h3>
                    <p class="mb-4 text-xs text-slate-500">Conecte o número oficial do WhatsApp Business.</p>

                    <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        <p class="font-semibold text-slate-800">Como obter as credenciais</p>
                        <ol class="mt-1.5 list-decimal space-y-1 pl-4">
                            <li>Acesse <a href="https://developers.facebook.com" target="_blank" class="underline">developers.facebook.com</a></li>
                            <li>Crie um App → tipo <strong>Business</strong></li>
                            <li>Adicione o produto <strong>WhatsApp</strong></li>
                            <li>Em API Setup, copie o <strong>Token</strong> e o <strong>Phone Number ID</strong></li>
                            <li>Configure o webhook com o Verify Token abaixo</li>
                        </ol>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div>
                            <label for="whatsapp_driver" class="mb-1 block text-xs font-semibold text-slate-700">Modo</label>
                            <select id="whatsapp_driver" x-model="form.whatsapp_driver" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
                                <option value="null">Simulado (teste local)</option>
                                <option value="meta">WhatsApp Meta Cloud API</option>
                            </select>
                        </div>
                        <div>
                            <label for="meta_token" class="mb-1 block text-xs font-semibold text-slate-700">Token de acesso</label>
                            <input id="meta_token" type="password" x-model="form.meta_token" :placeholder="form.meta_token_configured ? 'Token já configurado — deixe vazio para manter' : 'EAAxxxx...'" autocomplete="new-password" spellcheck="false" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
                        </div>
                        <div>
                            <label for="meta_phone_number_id" class="mb-1 block text-xs font-semibold text-slate-700">Phone Number ID</label>
                            <input id="meta_phone_number_id" type="text" x-model="form.meta_phone_number_id" placeholder="123456789012345" inputmode="numeric" autocomplete="off" spellcheck="false" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
                        </div>
                        <div>
                            <label for="webhook_verify_token" class="mb-1 block text-xs font-semibold text-slate-700">Webhook Verify Token</label>
                            <input id="webhook_verify_token" type="password" x-model="form.webhook_verify_token" :placeholder="form.webhook_verify_token_configured ? 'Token já configurado — deixe vazio para manter' : 'mgi_webhook_secret'" autocomplete="new-password" spellcheck="false" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
                        </div>
                        <div class="lg:col-span-2">
                            <label for="meta_app_secret" class="mb-1 block text-xs font-semibold text-slate-700">App Secret da Meta</label>
                            <input id="meta_app_secret" type="password" x-model="form.meta_app_secret" :placeholder="form.meta_app_secret_configured ? 'Segredo já configurado — deixe vazio para manter' : 'Cole o App Secret para validar a assinatura dos webhooks'" autocomplete="new-password" spellcheck="false" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/15">
                            <p class="mt-1 text-[11px] text-slate-400">Armazenado criptografado e nunca devolvido ao navegador.</p>
                        </div>
                        <div class="lg:col-span-2">
                            <label for="webhook_callback_url" class="mb-1 block text-xs font-semibold text-slate-700">URL do webhook</label>
                            <div class="flex gap-2">
                                <input id="webhook_callback_url" type="text" readonly :value="form.webhook_callback_url" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-xs font-mono text-slate-600">
                                <button type="button" @click="navigator.clipboard.writeText(form.webhook_callback_url); Swal.fire('Copiado!', 'URL do webhook copiada.', 'success')" class="shrink-0 rounded-lg bg-[#8B1E3F] px-4 py-2 text-xs font-semibold text-white hover:bg-[#741832] focus:outline-none focus:ring-2 focus:ring-[#8B1E3F]/30">Copiar</button>
                            </div>
                            <p class="mt-1 text-xs text-slate-400">Use HTTPS público (ex.: ngrok) em desenvolvimento.</p>
                        </div>
                        <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 lg:col-span-2">
                            <input type="checkbox" x-model="form.bot_enabled" class="rounded border-slate-300 text-[#8B1E3F] focus:ring-[#8B1E3F]">
                            <span class="text-xs font-medium text-slate-700">Bot automático ativo (responde antes do atendente)</span>
                        </label>
                    </div>

                    <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
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

        <div class="flex flex-wrap items-center justify-end gap-2">
            <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Descartar</button>
            <button type="button" @click="save()" :disabled="saving"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#8B1E3F] px-5 py-2 text-xs font-bold text-white transition hover:bg-[#741832] focus:outline-none focus:ring-2 focus:ring-[#8B1E3F]/30 disabled:cursor-not-allowed disabled:opacity-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Salvar configurações
            </button>
        </div>
    </div>
</x-layout.app>
