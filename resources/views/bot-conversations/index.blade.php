<x-layout.app :title="'Encerradas pelo robô - MGI Chat'" :fullWidth="true">
    <x-slot name="header">Encerradas pelo robô</x-slot>

    <div x-data="botConversationsApp()" x-init="init()" class="flex h-[calc(100dvh-8rem)] overflow-hidden border-y border-slate-200 bg-slate-100 lg:h-[calc(100dvh-4rem)]">

        {{-- Lista --}}
        <div :class="mobileView === 'chat' ? 'hidden lg:flex' : 'flex'"
             class="w-full flex-col border-r border-slate-200 bg-white lg:w-80 xl:w-[21rem]">
            <div class="border-b border-slate-200 bg-white px-4 py-3.5">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <div>
                        <p class="font-display text-base font-semibold text-slate-900">Arquivo do bot</p>
                        <p class="text-xs text-slate-400">Conversas finalizadas automaticamente</p>
                    </div>
                    <div class="shrink-0 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-center">
                        <p class="text-sm font-bold leading-none text-[#8B1E3F]" x-text="conversations.length"></p>
                        <p class="mt-0.5 text-[10px] font-medium uppercase tracking-wide text-[#5C1529]/70">total</p>
                    </div>
                </div>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" placeholder="Buscar nome ou telefone..." x-model="search" @input.debounce.400ms="loadConversations()"
                           class="w-full rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm outline-none transition focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10">
                </div>
                <div class="mt-3 flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-[#5C1529]">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-sm">🤖</span>
                    <span>Somente leitura — o bot já encerrou o atendimento.</span>
                </div>
            </div>

            <div class="custom-scrollbar flex-1 overflow-y-auto bg-white">
                <template x-for="conv in conversations" :key="conv.id">
                    <button type="button" @click="openDetails(conv)"
                            :class="selected?.id === conv.id ? 'bg-slate-50 border-l-[3px] border-[#8B1E3F]' : 'hover:bg-slate-50 border-l-[3px] border-transparent'"
                            class="flex w-full items-start gap-3 border-b border-slate-100 px-4 py-3 text-left transition">
                        <div class="relative shrink-0">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-[#5C1529]" x-text="conv.initials"></div>
                            <span class="absolute -bottom-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-md border border-slate-200 bg-white text-[10px]">🤖</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate font-semibold text-slate-800" x-text="conv.name"></span>
                                <span class="shrink-0 text-[11px] text-slate-400" x-text="conv.bot_closed_at || conv.time"></span>
                            </div>
                            <p class="mt-0.5 truncate text-sm text-slate-500" x-text="conv.preview"></p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                <span class="inline-block rounded-full bg-white px-2 py-0.5 text-[10px] font-bold uppercase text-[#8B1E3F] ring-1 ring-slate-200">Encerrada</span>
                                <span class="truncate text-[11px] text-slate-400" x-text="conv.phone"></span>
                            </div>
                        </div>
                    </button>
                </template>

                <div x-show="!loading && conversations.length === 0" class="flex flex-col items-center px-6 py-16 text-center">
                    <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-2xl ring-1 ring-slate-200">🤖</div>
                    <p class="font-semibold text-slate-700">Nenhuma conversa encerrada</p>
                    <p class="mt-1 text-sm text-slate-400">Quando o robô finalizar um atendimento, o histórico aparece aqui.</p>
                </div>
                <div x-show="loading" class="py-10 text-center text-sm text-slate-400">Carregando...</div>
            </div>
        </div>

        {{-- Histórico --}}
        <div :class="mobileView !== 'chat' ? 'hidden lg:flex' : 'flex'" class="relative flex flex-1 flex-col bg-slate-50">

            <div class="flex min-h-16 items-center justify-between border-b border-slate-200 bg-white px-4 py-2.5">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" @click="mobileView = 'list'" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-50 lg:hidden">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <template x-if="selected">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-[#5C1529]" x-text="selected.initials"></div>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-800" x-text="selected.name"></p>
                                <p class="truncate text-xs text-slate-400" x-text="selected.phone"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="!selected">
                        <div>
                            <p class="font-semibold text-slate-700">Selecione uma conversa</p>
                            <p class="text-xs text-slate-400">Veja o histórico completo à esquerda</p>
                        </div>
                    </template>
                </div>
                <span x-show="selected" class="shrink-0 rounded-full bg-slate-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-[#5C1529] ring-1 ring-slate-200">
                    Encerrada pelo robô
                </span>
            </div>

            <div x-show="selected" class="flex flex-wrap gap-2 border-b border-slate-200 bg-white px-4 py-2 text-xs text-slate-500">
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5">
                    Encerrada: <strong class="text-[#5C1529]" x-text="detail?.bot_closed_at || '—'"></strong>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5">
                    Cliente: <strong class="text-[#5C1529]" x-text="detail?.client_messages ?? '—'"></strong>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5">
                    Bot: <strong class="text-[#5C1529]" x-text="detail?.bot_messages ?? '—'"></strong>
                </div>
            </div>

            <div class="custom-scrollbar flex-1 space-y-3 overflow-y-auto p-4 sm:p-5">
                <div x-show="!selected && !loading" class="flex h-full min-h-[16rem] flex-col items-center justify-center text-center">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-xl border border-slate-200 bg-white text-2xl">🤖</div>
                    <p class="font-display text-lg font-semibold text-slate-900">Arquivo do robô</p>
                    <p class="mt-2 max-w-sm text-sm text-slate-500">Selecione uma conversa encerrada para revisar se o bot respondeu corretamente às dúvidas do cliente.</p>
                </div>

                <div x-show="detailLoading" class="py-10 text-center text-sm text-slate-400">Carregando histórico...</div>

                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.from === 'client' ? 'flex justify-start' : 'flex justify-end'">
                        <div :class="{
                                'border border-slate-200 bg-white text-slate-800 rounded-xl rounded-tl-sm': msg.from === 'client',
                                'border border-slate-200 bg-white text-[#5C1529] rounded-xl rounded-tr-sm': msg.from === 'bot',
                                'border border-[#741832] bg-[#8B1E3F] text-white rounded-xl rounded-tr-sm': msg.from === 'agent',
                             }"
                             class="max-w-[78%] overflow-hidden px-3 py-2.5 text-sm leading-relaxed">
                            <p x-show="msg.from === 'bot'" class="mb-1 px-1 text-[10px] font-bold uppercase tracking-wide text-[#8B1E3F]">Assistente virtual</p>
                            <p x-show="msg.from === 'client'" class="mb-1 px-1 text-[10px] font-bold uppercase tracking-wide text-slate-400">Cliente</p>
                            <p x-show="msg.from === 'agent'" class="mb-1 px-1 text-[10px] font-bold uppercase tracking-wide text-white/80">Atendente</p>
                            <template x-if="msg.image_url">
                                <a :href="msg.image_url" target="_blank" rel="noopener" class="mb-2 block">
                                    <img :src="msg.image_url" alt="Imagem" class="max-h-64 w-full max-w-xs rounded-xl object-cover">
                                </a>
                            </template>
                            <p class="whitespace-pre-wrap break-words px-1"
                               x-show="msg.text && msg.text !== '[imagem]'"
                               x-text="msg.text"></p>
                            <div :class="msg.from === 'client' ? 'text-slate-400' : (msg.from === 'bot' ? 'text-[#8B1E3F]/70' : 'text-white/70')"
                                 class="mt-1.5 px-1 text-right text-[10px]" x-text="msg.time"></div>
                        </div>
                    </div>
                </template>

                <div x-show="selected && !detailLoading && messages.length === 0" class="py-10 text-center text-sm text-slate-400">
                    Nenhuma mensagem registrada nesta conversa.
                </div>
            </div>

            <div x-show="selected" class="border-t border-slate-200 bg-white px-4 py-3">
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-[#5C1529]">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-base">🔒</span>
                    <div>
                        <p class="font-semibold">Somente visualização</p>
                        <p class="text-xs text-[#5C1529]/75">Esta conversa foi encerrada pelo robô. Não é possível enviar mensagens.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Painel resumo --}}
        <div class="hidden w-72 flex-col border-l border-slate-200 bg-white xl:flex">
            <div class="custom-scrollbar flex-1 overflow-y-auto" x-show="selected">
                <div class="border-b border-slate-200 bg-white px-5 pb-5 pt-6 text-center">
                    <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-xl font-bold text-[#8B1E3F]" x-text="selected?.initials"></div>
                    <h3 class="font-display text-lg font-semibold text-slate-900" x-text="selected?.name"></h3>
                    <p class="mt-1 text-sm text-slate-500" x-text="selected?.phone"></p>
                    <span class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-xs font-bold text-[#8B1E3F] ring-1 ring-slate-200">
                        <span>🤖</span> Encerrada pelo robô
                    </span>
                </div>
                <div class="space-y-3 p-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Encerrada em</p>
                        <p class="mt-1 text-sm font-semibold text-[#5C1529]" x-text="detail?.bot_closed_at || '—'"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Msgs cliente</p>
                            <p class="mt-1 text-2xl font-bold text-[#5C1529]" x-text="detail?.client_messages ?? '—'"></p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Msgs bot</p>
                            <p class="mt-1 text-2xl font-bold text-[#8B1E3F]" x-text="detail?.bot_messages ?? '—'"></p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">
                        Use este arquivo para auditar respostas do bot e identificar dúvidas que precisem ir para atendimento humano.
                    </div>
                </div>
            </div>
            <div x-show="!selected" class="flex flex-1 flex-col items-center justify-center p-8 text-center">
                <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-50 text-2xl">📋</div>
                <p class="font-medium text-slate-600">Resumo do atendimento</p>
                <p class="mt-1 text-sm text-slate-400">Os detalhes da conversa selecionada aparecem aqui.</p>
            </div>
        </div>
    </div>
</x-layout.app>
