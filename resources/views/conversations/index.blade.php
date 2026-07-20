<x-layout.app :title="'Conversas - MGI Chat'" :fullWidth="true">
    <x-slot name="header">Conversas</x-slot>

    <div x-data="chatApp()" x-init="init()" class="flex h-[calc(100dvh-8rem)] overflow-hidden border-y border-slate-200 bg-slate-100 lg:h-[calc(100dvh-4rem)]">

        {{-- Lista --}}
        <div :class="mobileView === 'chat' || mobileView === 'info' ? 'hidden lg:flex' : 'flex'"
             class="w-full flex-col border-r border-slate-200 bg-white lg:w-80 xl:w-[21rem]">
            <div class="border-b border-slate-200 bg-white px-4 py-3.5">
                <div class="mb-2.5 flex items-center justify-between">
                    <div>
                        <p class="font-display text-base font-semibold text-slate-900">Caixa de entrada</p>
                        <p class="text-[11px] text-slate-500" x-text="conversations.length + ' conversa(s)'"></p>
                    </div>
                </div>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" placeholder="Buscar nome ou telefone..." x-model="search" @input.debounce.400ms="loadConversations()"
                           class="w-full rounded-xl border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm outline-none transition focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10">
                </div>
                <div class="mt-3 flex gap-2 overflow-x-auto pb-0.5">
                    <template x-for="f in filters" :key="f.id">
                        <button type="button" @click="activeFilter = f.id; loadConversations()"
                                :class="activeFilter === f.id ? 'bg-[#8B1E3F] text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-white'"
                                class="shrink-0 rounded-lg border border-transparent px-3 py-1.5 text-[11px] font-semibold transition" x-text="f.label"></button>
                    </template>
                </div>
            </div>
            <div class="custom-scrollbar flex-1 overflow-y-auto bg-white">
                <template x-for="conv in filteredConversations" :key="conv.id">
                    <button type="button" @click="selectConversation(conv)"
                            :class="selected?.id === conv.id ? 'bg-slate-50 border-l-[3px] border-[#8B1E3F]' : 'hover:bg-slate-50 border-l-[3px] border-transparent'"
                            class="flex w-full items-start gap-3 border-b border-slate-100 px-4 py-3 text-left transition">
                        <div class="relative shrink-0">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-[#5C1529]" x-text="conv.initials"></div>
                            <span x-show="conv.unread > 0" class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-[#8B1E3F] px-1 text-[10px] font-bold text-white" x-text="conv.unread"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="truncate font-semibold text-slate-800" x-text="conv.name"></span>
                                <span class="shrink-0 text-[11px] text-slate-400" x-text="conv.time"></span>
                            </div>
                            <p class="mt-0.5 truncate text-sm text-slate-500" x-text="conv.preview"></p>
                            <div class="mt-1.5 flex flex-wrap gap-1">
                                <span x-show="conv.is_bot" class="inline-block rounded-full bg-white px-2 py-0.5 text-[10px] font-bold uppercase text-[#8B1E3F] ring-1 ring-slate-200" x-text="conv.status_label"></span>
                                <span x-show="conv.tag" :class="conv.tagClass" class="inline-block rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" x-text="conv.tag"></span>
                            </div>
                        </div>
                    </button>
                </template>
                <div x-show="conversations.length === 0" class="flex flex-col items-center px-6 py-14 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xl">💬</div>
                    <p class="font-semibold text-slate-700">Nenhuma conversa</p>
                    <p class="mt-1 text-sm text-slate-400">Quando um cliente mandar WhatsApp, a conversa aparece aqui.</p>
                </div>
            </div>
        </div>

        {{-- Chat --}}
        <div :class="mobileView !== 'chat' ? 'hidden lg:flex' : 'flex'" class="relative flex flex-1 flex-col bg-slate-50">
            <div class="flex min-h-16 items-center justify-between border-b border-slate-200 bg-white px-4 py-2.5">
                <div class="flex items-center gap-3">
                    <button type="button" @click="mobileView = 'list'" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-50 lg:hidden">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <template x-if="selected">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-[#5C1529]" x-text="selected.initials"></div>
                            <div>
                                <p class="font-semibold text-slate-800" x-text="selected.name"></p>
                                <p class="text-xs text-slate-400" x-text="selected.phone || selectedConversation?.client?.phone || ''"></p>
                                <p x-show="selectedConversation?.is_bot" class="text-xs font-medium text-[#8B1E3F]" x-text="selectedConversation?.status_label"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="!selected">
                        <div>
                            <p class="font-semibold text-slate-700">Selecione uma conversa</p>
                            <p class="text-xs text-slate-400">Escolha um contato à esquerda para começar</p>
                        </div>
                    </template>
                </div>
                <div class="flex items-center gap-2" x-show="selected && !isReadOnly">
                    <button type="button" @click="closeConversation()" class="rounded-lg border border-[#741832] bg-[#8B1E3F] px-3.5 py-2 text-sm font-semibold text-white hover:bg-[#741832]">Encerrar</button>
                    <button type="button" @click="mobileView = 'info'" class="rounded-lg p-2 text-slate-400 hover:bg-slate-50 xl:hidden">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                </div>
            </div>

            <div class="custom-scrollbar flex-1 space-y-2.5 overflow-y-auto bg-slate-50 p-4 sm:p-5" x-ref="messageList" @click="showEmojiPicker = false">
                <div x-show="!selected && !loading" class="flex h-full min-h-[16rem] flex-col items-center justify-center text-center">
                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-xl border border-slate-200 bg-white text-2xl">💬</div>
                    <p class="font-display text-lg font-semibold text-slate-900">MGI Chat</p>
                    <p class="mt-2 max-w-xs text-sm text-slate-500">Selecione uma conversa para ver mensagens e responder pelo WhatsApp.</p>
                </div>
                <div x-show="loading" class="py-10 text-center text-sm text-slate-400">Carregando mensagens...</div>
                <div x-show="selected && hasMoreMessages && !loading" class="pb-1 text-center">
                    <button type="button"
                            @click="loadOlderMessages()"
                            :disabled="loadingOlder"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-bold text-slate-500 hover:text-[#8B1E3F] disabled:opacity-50">
                        <span x-text="loadingOlder ? 'Carregando...' : 'Carregar mensagens anteriores'"></span>
                    </button>
                </div>
                <template x-for="msg in messages" :key="msg.id">
                    <div :class="msg.from === 'client' ? 'flex justify-start' : 'flex justify-end'">
                        <div :class="{
                                'border border-slate-200 bg-white text-slate-800 rounded-xl rounded-tl-sm': msg.from === 'client',
                                'border border-[#741832] bg-[#8B1E3F] text-white rounded-xl rounded-tr-sm': msg.from === 'agent',
                                'border border-slate-200 bg-white text-[#5C1529] rounded-xl rounded-tr-sm': msg.from === 'bot',
                             }"
                             class="max-w-[78%] overflow-hidden px-3 py-2.5 text-sm leading-relaxed">
                            <p x-show="msg.from === 'bot'" class="mb-1 px-1 text-[10px] font-bold uppercase tracking-wide text-[#8B1E3F]">Bot</p>

                            <template x-if="msg.image_url">
                                <a :href="msg.image_url" target="_blank" rel="noopener" class="mb-2 block overflow-hidden rounded-xl">
                                    <img :src="msg.image_url" alt="Imagem"
                                         class="max-h-64 w-full max-w-xs object-cover transition hover:opacity-95">
                                </a>
                            </template>

                            <template x-if="!msg.image_url && (msg.attachments || []).length">
                                <a :href="msg.attachments[0].url" target="_blank" rel="noopener"
                                   class="mb-2 flex items-center gap-2 rounded-xl px-2 py-2 text-sm font-medium underline-offset-2 hover:underline"
                                   :class="msg.from === 'agent' ? 'bg-white/10 text-white' : 'bg-slate-50 text-[#8B1E3F]'">
                                    <span>📎</span>
                                    <span x-text="msg.attachments[0].name || 'Arquivo'"></span>
                                </a>
                            </template>

                            <p class="whitespace-pre-wrap break-words px-1"
                               x-show="msg.text && msg.text !== '[imagem]' && msg.text !== '[áudio]' && msg.text !== '[vídeo]' && msg.text !== '[documento]' && msg.text !== '[figurinha]'"
                               x-text="msg.text"></p>
                            <div :class="msg.from === 'client' ? 'text-slate-400' : (msg.from === 'bot' ? 'text-[#8B1E3F]/70' : 'text-white/70')" class="mt-1.5 px-1 text-right text-[10px]">
                                <span x-text="msg.time"></span>
                                <span x-show="msg.from === 'agent'"> ✓✓</span>
                            </div>
                        </div>
                    </div>
                </template>
                <div x-show="selected && !loading && messages.length === 0" class="py-10 text-center text-sm text-slate-400">
                    Nenhuma mensagem nesta conversa ainda.
                </div>
            </div>

            <div class="relative border-t border-slate-200 bg-white p-3" x-show="selected">
                <div x-show="isReadOnly" class="mb-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-center text-sm text-[#5C1529]">
                    <span x-text="selectedConversation?.status === 'bot_closed' ? 'Conversa encerrada pelo bot — apenas visualização.' : 'Bot em atendimento — você pode acompanhar, mas não enviar mensagens.'"></span>
                </div>

                <div class="relative" x-show="!isReadOnly" @click.outside="showEmojiPicker = false">
                    <div x-show="showEmojiPicker"
                         x-cloak
                         x-transition.opacity.duration.150ms
                         class="absolute bottom-full left-0 right-0 z-50 mb-2 overflow-hidden rounded-xl border border-slate-300 bg-white">
                        <div class="border-b border-slate-100 px-3 py-2">
                            <input type="text" x-model="emojiSearch" @input="refreshVisibleEmojis()"
                                   placeholder="Filtrar categorias (ex: Comida)"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-[#8B1E3F]">
                        </div>
                        <div class="flex items-center gap-1 overflow-x-auto border-b border-slate-100 px-2 py-1.5">
                            <template x-for="cat in emojiCategories" :key="cat.id">
                                <button type="button" @click="setEmojiTab(cat.id)"
                                        :class="emojiTab === cat.id && !emojiSearch ? 'bg-slate-50 ring-1 ring-slate-200' : 'hover:bg-white'"
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-lg transition"
                                        :title="cat.label" x-text="cat.icon"></button>
                            </template>
                        </div>
                        <div class="custom-scrollbar max-h-56 overflow-y-auto p-2">
                            <p class="mb-2 px-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="currentCategoryLabel()"></p>
                            <div class="grid grid-cols-8 gap-0.5 sm:grid-cols-10">
                                <template x-for="(emoji, idx) in visibleEmojis" :key="idx + '-' + emoji">
                                    <button type="button" @click="insertEmoji(emoji)"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg text-xl transition hover:bg-slate-50 active:scale-95"
                                            x-text="emoji"></button>
                                </template>
                            </div>
                            <p x-show="!visibleEmojis.length" class="py-6 text-center text-sm text-slate-400">Nenhum emoji nesta categoria.</p>
                        </div>
                    </div>

                    <div class="flex items-end gap-1.5 rounded-xl border border-slate-300 bg-white p-1">
                        <button type="button"
                                @click="showEmojiPicker = !showEmojiPicker; if (showEmojiPicker) { emojiSearch = ''; refreshVisibleEmojis(); }"
                                :class="showEmojiPicker ? 'bg-slate-50 text-[#8B1E3F]' : 'text-slate-500 hover:bg-white hover:text-[#8B1E3F]'"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg transition" title="Emojis">
                            <span class="text-xl leading-none">😊</span>
                        </button>
                        <label class="flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-50 hover:text-[#8B1E3F]" title="Enviar foto">
                            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onImageSelected($event)">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z"/>
                            </svg>
                        </label>
                        <input type="text" x-ref="messageInput" placeholder="Digite uma mensagem" x-model="newMessage"
                               @keydown.enter.prevent="sendMessage()"
                               class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2.5 text-sm outline-none placeholder:text-slate-400">
                        <button type="button" @click="sendMessage()" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-[#741832] bg-[#8B1E3F] text-white transition hover:bg-[#741832]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </div>
                    <div x-show="pendingImagePreview" x-cloak class="mt-2 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                        <img :src="pendingImagePreview" alt="Prévia" class="h-14 w-14 rounded-lg object-cover">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-700" x-text="pendingImageName"></p>
                            <p class="text-xs text-slate-400">Pronto para enviar (pode escrever uma legenda acima)</p>
                        </div>
                        <button type="button" @click="clearPendingImage()" class="rounded-lg px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Remover</button>
                        <button type="button" @click="sendMessage()" class="rounded-lg bg-[#8B1E3F] px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90">Enviar foto</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Painel cliente --}}
        <div :class="mobileView !== 'info' ? 'hidden xl:flex' : 'flex'" class="w-full flex-col border-l border-slate-200 bg-white xl:w-72">
            <div class="custom-scrollbar flex-1 overflow-y-auto" x-show="selectedClient">
                <div class="border-b border-slate-200 bg-white px-5 pb-5 pt-6 text-center">
                    <div class="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-xl font-bold text-[#8B1E3F]" x-text="selected?.initials"></div>
                    <h3 class="font-display text-lg font-semibold text-slate-900" x-text="selectedClient?.name"></h3>
                    <p class="mt-1 text-sm text-slate-500" x-text="selectedClient?.phone"></p>
                    <p class="mt-0.5 text-xs text-slate-400" x-text="selectedClient?.email || ''"></p>
                    <div class="mt-3 flex flex-wrap justify-center gap-2">
                        <template x-for="tag in (selectedClient?.tags || [])" :key="tag.name">
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold" :style="'background:' + tag.color + '22;color:' + tag.color" x-text="tag.name"></span>
                        </template>
                    </div>
                </div>
                <div class="space-y-4 p-5">
                    <div x-show="selectedConversation?.sla_due_at">
                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">SLA de resposta</h4>
                        <div class="rounded-xl border px-4 py-3"
                             :class="selectedConversation?.sla_state === 'overdue' ? 'border-red-200 bg-red-50' : (selectedConversation?.sla_state === 'warning' ? 'border-amber-200 bg-amber-50' : 'border-slate-200 bg-slate-50')">
                            <p class="text-sm font-bold"
                               :class="selectedConversation?.sla_state === 'overdue' ? 'text-red-700' : (selectedConversation?.sla_state === 'warning' ? 'text-amber-700' : 'text-slate-700')"
                               x-text="selectedConversation?.sla_state === 'overdue' ? 'Prazo excedido' : (selectedConversation?.sla_state === 'warning' ? 'Próximo do limite' : 'Dentro do prazo')"></p>
                            <p class="mt-1 text-xs text-slate-500" x-text="'Vence em ' + selectedConversation?.sla_due_at"></p>
                        </div>
                    </div>
                    <div>
                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">Empresa</h4>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" x-text="selectedClient?.company || 'Não informado'"></div>
                    </div>
                    <div>
                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">Notas do Cliente</h4>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-relaxed text-slate-700" x-text="selectedClient?.notes || 'Sem observações.'"></div>
                    </div>
                </div>
            </div>
            <div x-show="!selectedClient" class="flex flex-1 flex-col items-center justify-center p-8 text-center text-sm text-slate-400">
                <p>Detalhes do cliente aparecem ao selecionar uma conversa.</p>
            </div>
        </div>
    </div>
</x-layout.app>
