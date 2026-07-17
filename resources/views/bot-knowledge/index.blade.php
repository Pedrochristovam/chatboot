<x-layout.app :title="'Robô & FAQ - MGI Chat'">
    <x-slot name="header">Robô & FAQ</x-slot>

    <div x-data="botKnowledgeApp" class="mx-auto max-w-7xl space-y-4">
        <script type="application/json" id="bot-knowledge-initial">{!! json_encode($initial, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

        {{-- Cabeçalho --}}
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#8B1E3F]">Configuração do atendimento automático</p>
                <h2 class="mt-0.5 font-display text-2xl font-semibold text-slate-900">Robô & FAQ</h2>
                <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-600">
                    Aqui você ensina o que o robô deve dizer no WhatsApp.
                    Não precisa saber programar: só preencha as 3 partes abaixo, na ordem.
                </p>
            </div>
            <button type="button" @click="openTopicCreate()"
                    class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-[#8B1E3F] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#741832] focus:outline-none focus:ring-2 focus:ring-[#8B1E3F]/20 sm:w-auto">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Criar assunto do menu
            </button>
        </div>

        {{-- Como funciona --}}
        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h3 class="text-base font-semibold text-slate-800">Como o cliente conversa com o robô</h3>
                <p class="mt-1 text-sm text-slate-500">O atendimento automático sempre segue esta ordem:</p>
            </div>
            <ol class="grid gap-0 sm:grid-cols-2 lg:grid-cols-4">
                <li class="border-b border-slate-100 p-4 sm:border-r lg:border-b-0">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#8B1E3F] text-xs font-bold text-white">1</span>
                    <p class="mt-2 text-sm font-semibold text-slate-800">Cliente manda “oi”</p>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500">Se for a 1ª vez, o robô pede o nome. Se já conhece o número, cumprimenta de volta pelo nome.</p>
                </li>
                <li class="border-b border-slate-100 p-4 sm:border-r-0 lg:border-b-0 lg:border-r">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#8B1E3F] text-xs font-bold text-white">2</span>
                    <p class="mt-2 text-sm font-semibold text-slate-800">Nome ou “de volta”</p>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500">Em seguida o robô mostra o menu de assuntos (Parte B).</p>
                </li>
                <li class="border-b border-slate-100 p-4 sm:border-r lg:border-b-0">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#8B1E3F] text-xs font-bold text-white">3</span>
                    <p class="mt-2 text-sm font-semibold text-slate-800">Cliente escolhe um assunto</p>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500">Ex.: “Pagamentos”. Depois o cliente pergunta o que precisa.</p>
                </li>
                <li class="p-4">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-[#8B1E3F] text-xs font-bold text-white">4</span>
                    <p class="mt-2 text-sm font-semibold text-slate-800">Robô responde ou chama humano</p>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500">Usa as FAQs (Parte C). Se não achar resposta, ou o assunto for “falar com atendente”, vai para a equipe.</p>
                </li>
            </ol>
            <div class="border-t border-amber-100 bg-amber-50 px-4 py-2.5">
                <p class="text-sm text-amber-900">
                    <span class="font-semibold">Dica:</span>
                    Comece pelo assunto mais comum (ex.: “Horário”, “Pagamento”). Crie o assunto → depois adicione as respostas (FAQs) dele.
                </p>
            </div>
        </section>

        {{-- Resumo rápido --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Assuntos no menu</p>
                <p class="mt-1 text-2xl font-bold text-slate-900" x-text="stats.topics"></p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Visíveis para o cliente</p>
                <p class="mt-1 text-2xl font-bold text-slate-900" x-text="stats.active_topics"></p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Respostas cadastradas</p>
                <p class="mt-1 text-2xl font-bold text-slate-900" x-text="stats.answers"></p>
            </div>
            <div class="rounded-lg border border-[#8B1E3F] bg-white px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Respostas ativas</p>
                <p class="mt-1 text-2xl font-bold text-[#8B1E3F]" x-text="stats.active_answers"></p>
            </div>
        </div>

        {{-- Parte A --}}
        <section class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#8B1E3F] text-sm font-bold text-white">A</span>
                    <div>
                        <h3 class="text-base font-semibold text-slate-800">Mensagens de saudação</h3>
                        <p class="mt-1 max-w-xl text-sm leading-relaxed text-slate-500">
                            Duas situações: cliente <strong class="font-semibold text-slate-700">novo</strong> (pede o nome) e cliente que <strong class="font-semibold text-slate-700">já falou antes</strong> (mesmo número — cumprimenta pelo nome e mostra o menu).
                        </p>
                    </div>
                </div>
                <button type="button" @click="saveAskName()" :disabled="savingAsk"
                        class="w-full shrink-0 rounded-md bg-[#8B1E3F] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#741832] disabled:cursor-wait disabled:opacity-50 sm:w-auto">
                    <span x-text="savingAsk ? 'Salvando...' : 'Salvar mensagens'"></span>
                </button>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Cliente novo — pedir o nome</label>
                    <textarea x-model="askNameMessage" rows="4"
                              placeholder="Ex.: Olá! Sou o assistente virtual. Para começar, qual é o seu nome?"
                              class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></textarea>
                    <p class="mt-2 text-xs text-slate-400">Usada só na primeira vez, quando o sistema ainda não tem o nome desse número.</p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Cliente que volta — mesmo número</label>
                    <textarea x-model="welcomeBackMessage" rows="4"
                              placeholder="Ex.: Que bom ter você de volta, *{name}*! O que posso ajudar hoje?"
                              class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></textarea>
                    <p class="mt-2 text-xs text-slate-400">Use <code class="rounded bg-slate-100 px-1">{name}</code> onde o nome deve aparecer. Depois o robô envia o menu de assuntos.</p>
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-400">Depois de editar, clique em “Salvar mensagens”. Sem salvar, a alteração não vale.</p>
        </section>

        {{-- Partes B + C --}}
        <section class="grid gap-4 lg:grid-cols-12">
            {{-- Lista de assuntos --}}
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm lg:col-span-4">
                <div class="border-b border-slate-200 px-4 py-3">
                    <div class="flex gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#8B1E3F] text-sm font-bold text-white">B</span>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-800">Assuntos do menu</h3>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">
                                Cada item vira uma opção no WhatsApp (lista clicável ou números).
                                Clique em um assunto para cadastrar as respostas dele à direita.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="max-h-[28rem] overflow-y-auto p-2">
                    <template x-for="topic in topics" :key="topic.id">
                        <button type="button"
                                @click="selectTopic(topic.id)"
                                class="mb-1 flex w-full items-start gap-3 rounded-md border px-3 py-2.5 text-left transition"
                                :class="selectedId === topic.id ? 'border-[#8B1E3F] bg-[#8B1E3F] text-white' : 'border-transparent hover:border-slate-200 hover:bg-slate-50 text-slate-800'">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-xs font-bold"
                                  :class="selectedId === topic.id ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                                  x-text="topic.sort_order"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold" x-text="topic.title"></span>
                                <span class="mt-0.5 block truncate text-xs"
                                      :class="selectedId === topic.id ? 'text-white/70' : 'text-slate-400'"
                                      x-text="(topic.knowledge_count ?? (topic.knowledge || []).length) + ' resposta(s)'"></span>
                                <span class="mt-1 flex flex-wrap gap-1">
                                    <span x-show="topic.transfers_to_human"
                                          class="rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase"
                                          :class="selectedId === topic.id ? 'bg-white/15 text-white' : 'bg-amber-50 text-amber-700'">Vai p/ humano</span>
                                    <span x-show="!topic.is_active"
                                          class="rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase"
                                          :class="selectedId === topic.id ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-500'">Oculto</span>
                                </span>
                            </span>
                        </button>
                    </template>
                    <div x-show="!topics.length" class="px-3 py-10 text-center">
                        <p class="text-sm font-medium text-slate-600">Ainda não há assuntos</p>
                        <p class="mt-1 text-xs text-slate-400">Crie o primeiro (ex.: “Pagamentos” ou “Falar com atendente”).</p>
                        <button type="button" @click="openTopicCreate()" class="mt-4 rounded-md bg-[#8B1E3F] px-4 py-2 text-sm font-semibold text-white hover:bg-[#741832]">Criar primeiro assunto</button>
                    </div>
                </div>
            </div>

            {{-- FAQs do assunto --}}
            <div class="rounded-lg border border-slate-200 bg-white shadow-sm lg:col-span-8">
                <template x-if="!selected">
                    <div class="flex min-h-[22rem] flex-col items-center justify-center px-6 text-center">
                        <span class="flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-sm font-bold text-slate-500">C</span>
                        <p class="mt-4 text-base font-semibold text-slate-700">Escolha um assunto à esquerda</p>
                        <p class="mt-2 max-w-sm text-sm leading-relaxed text-slate-500">
                            Depois disso você cadastra as respostas (FAQs) daquele tema.
                            Sem assunto selecionado, não dá para criar FAQ.
                        </p>
                    </div>
                </template>

                <template x-if="selected">
                    <div>
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
                            <div class="flex gap-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-[#8B1E3F] text-sm font-bold text-white">C</span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Respostas deste assunto</p>
                                    <h3 class="mt-1 text-lg font-semibold text-slate-900" x-text="selected.title"></h3>
                                    <p class="mt-1 max-w-lg text-sm leading-relaxed text-slate-500"
                                       x-text="selected.transfers_to_human
                                         ? 'Este assunto manda o cliente direto para um atendente humano (não usa FAQ).'
                                         : (selected.description || 'Cadastre abaixo o que o robô deve responder quando o cliente perguntar sobre este tema.')"></p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="openTopicEdit()" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Editar assunto</button>
                                <button type="button" @click="removeTopic()" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:border-red-200 hover:bg-red-50">Excluir</button>
                                <button type="button" @click="openKnowledgeCreate()" x-show="!selected.transfers_to_human"
                                        class="rounded-md bg-[#8B1E3F] px-3 py-2 text-sm font-semibold text-white hover:bg-[#741832]">+ Nova resposta</button>
                            </div>
                        </div>

                        <div class="space-y-3 p-4 sm:p-5" x-show="!selected.transfers_to_human">
                            <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                <p class="font-medium text-slate-700">Como o robô escolhe a resposta?</p>
                                <p class="mt-1 leading-relaxed">
                                    O cliente escreve uma pergunta. Se a mensagem tiver alguma <strong class="font-semibold">palavra-chave</strong> que você cadastrou, o robô envia aquela resposta.
                                    Ex.: palavras-chave <em>boleto, fatura</em> → resposta sobre 2ª via.
                                </p>
                            </div>

                            <template x-for="item in faqs" :key="item.id">
                                <article class="rounded-lg border border-slate-200 p-4 transition hover:border-slate-300">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Pergunta (só para você organizar)</p>
                                            <h4 class="mt-0.5 font-semibold text-slate-800" x-text="item.question"></h4>
                                            <p class="mt-3 text-[11px] font-semibold uppercase tracking-wide text-slate-400">O que o robô envia ao cliente</p>
                                            <p class="mt-0.5 whitespace-pre-wrap text-sm leading-relaxed text-slate-600" x-text="item.answer"></p>
                                            <div class="mt-3">
                                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Palavras-chave que disparam esta resposta</p>
                                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                                    <template x-for="kw in (item.keywords || [])" :key="kw">
                                                        <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600" x-text="kw"></span>
                                                    </template>
                                                    <span x-show="!(item.keywords || []).length" class="text-[11px] text-amber-700">Sem palavras-chave — o robô quase não vai achar esta resposta</span>
                                                    <span x-show="!item.is_active" class="rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Desligada</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 gap-1">
                                            <button type="button" @click="openKnowledgeEdit(item)" class="rounded-md border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-50 hover:text-[#8B1E3F]">Editar</button>
                                            <button type="button" @click="removeKnowledge(item)" class="rounded-md border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:border-red-100 hover:bg-red-50 hover:text-red-600">Excluir</button>
                                        </div>
                                    </div>
                                </article>
                            </template>

                            <div x-show="!faqs.length" class="rounded-lg border border-dashed border-slate-300 px-4 py-12 text-center">
                                <p class="font-medium text-slate-600">Nenhuma resposta neste assunto ainda</p>
                                <p class="mt-1 max-w-md mx-auto text-sm text-slate-400">
                                    Adicione pelo menos uma FAQ com palavras-chave, senão o robô não terá o que responder neste tema.
                                </p>
                                <button type="button" @click="openKnowledgeCreate()" class="mt-4 rounded-md bg-[#8B1E3F] px-4 py-2 text-sm font-semibold text-white hover:bg-[#741832]">Adicionar primeira resposta</button>
                            </div>
                        </div>

                        <div class="p-5" x-show="selected.transfers_to_human">
                            <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-950">
                                <p class="font-semibold">Este assunto só transferir para humano</p>
                                <p class="mt-2 leading-relaxed">
                                    Quando o cliente escolher este item no menu, o robô <strong>não</strong> tenta responder FAQ —
                                    a conversa vai para a fila de atendentes. Use para opções como “Falar com atendente” ou “Outros”.
                                </p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        {{-- Modal assunto --}}
        <template x-teleport="body">
            <div x-show="showTopicModal" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="display: none;">
                <div class="absolute inset-0 bg-slate-950/45" @click="closeTopicModal()"></div>
                <div class="relative z-10 w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl" @click.stop>
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="text-lg font-semibold text-slate-900" x-text="editingTopic ? 'Editar assunto do menu' : 'Novo assunto do menu'"></h3>
                        <p class="mt-0.5 text-sm text-slate-500">O título é o que o cliente vê na lista do WhatsApp.</p>
                    </div>
                    <div class="max-h-[70vh] space-y-3 overflow-y-auto p-5">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Nome do assunto (aparece no menu)</label>
                            <input type="text" x-model="topicForm.title" maxlength="80" placeholder="Ex.: Pagamentos, Horário, Falar com atendente"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Descrição curta (opcional)</label>
                            <input type="text" x-model="topicForm.description" maxlength="120" placeholder="Ex.: 2ª via, vencimento, formas de pagamento"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10">
                            <p class="mt-1 text-xs text-slate-400">Ajuda o cliente a entender o que tem nesse item.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Posição no menu</label>
                            <input type="number" min="0" x-model.number="topicForm.sort_order"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10">
                            <p class="mt-1 text-xs text-slate-400">1 = primeiro da lista. Use 2, 3… para ordenar.</p>
                        </div>
                        <label class="flex items-start gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700">
                            <input type="checkbox" x-model="topicForm.is_active" class="mt-0.5 rounded border-slate-300 text-[#8B1E3F]">
                            <span>
                                <span class="font-medium">Mostrar no menu</span>
                                <span class="mt-0.5 block text-xs text-slate-500">Se desmarcar, o cliente não vê este assunto.</span>
                            </span>
                        </label>
                        <label class="flex items-start gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700">
                            <input type="checkbox" x-model="topicForm.transfers_to_human" class="mt-0.5 rounded border-slate-300 text-[#8B1E3F]">
                            <span>
                                <span class="font-medium">Só transferir para atendente</span>
                                <span class="mt-0.5 block text-xs text-slate-500">Marque em “Falar com atendente” / “Outros”. O robô não responde FAQ neste caso.</span>
                            </span>
                        </label>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 sm:flex-row sm:justify-end">
                        <button type="button" @click="closeTopicModal()" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                        <button type="button" @click="saveTopic()" :disabled="saving" class="rounded-md bg-[#8B1E3F] px-4 py-2 text-sm font-semibold text-white hover:bg-[#741832] disabled:cursor-wait disabled:opacity-50"><span x-text="saving ? 'Salvando...' : 'Salvar assunto'"></span></button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Modal FAQ --}}
        <template x-teleport="body">
            <div x-show="showKnowledgeModal" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" style="display: none;">
                <div class="absolute inset-0 bg-slate-950/45" @click="closeKnowledgeModal()"></div>
                <div class="relative z-10 w-full max-w-lg overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl" @click.stop>
                    <div class="border-b border-slate-200 px-5 py-4">
                        <h3 class="text-lg font-semibold text-slate-900" x-text="editingKnowledge ? 'Editar resposta' : 'Nova resposta do robô'"></h3>
                        <p class="mt-0.5 text-sm text-slate-500">Preencha os 3 campos: dúvida, resposta e palavras que disparam a mensagem.</p>
                    </div>
                    <div class="max-h-[70vh] space-y-3 overflow-y-auto p-5">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">1. Título da dúvida (só para você)</label>
                            <input type="text" x-model="knowledgeForm.question" placeholder="Ex.: Como emitir 2ª via do boleto?"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10">
                            <p class="mt-1 text-xs text-slate-400">Não é enviado ao cliente. Serve para você organizar.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">2. Resposta que o robô envia</label>
                            <textarea x-model="knowledgeForm.answer" rows="4" placeholder="Ex.: Para emitir a 2ª via, acesse o portal… ou digite seu CPF."
                                      class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10"></textarea>
                            <p class="mt-1 text-xs text-slate-400">Escreva a mensagem completa que o cliente vai ler no WhatsApp.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">3. Palavras-chave (obrigatório para funcionar bem)</label>
                            <input type="text" x-model="knowledgeForm.keywordsText" placeholder="boleto, 2ª via, fatura, pagamento"
                                   class="w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#8B1E3F] focus:ring-2 focus:ring-[#8B1E3F]/10">
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">
                                Separe por vírgula. Se a mensagem do cliente tiver <em>qualquer uma</em> dessas palavras, o robô usa esta resposta.
                            </p>
                        </div>
                        <label class="flex items-start gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700">
                            <input type="checkbox" x-model="knowledgeForm.is_active" class="mt-0.5 rounded border-slate-300 text-[#8B1E3F]">
                            <span>
                                <span class="font-medium">Resposta ligada</span>
                                <span class="mt-0.5 block text-xs text-slate-500">Desmarque para pausar sem apagar.</span>
                            </span>
                        </label>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 sm:flex-row sm:justify-end">
                        <button type="button" @click="closeKnowledgeModal()" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancelar</button>
                        <button type="button" @click="saveKnowledge()" :disabled="saving" class="rounded-md bg-[#8B1E3F] px-4 py-2 text-sm font-semibold text-white hover:bg-[#741832] disabled:cursor-wait disabled:opacity-50"><span x-text="saving ? 'Salvando...' : 'Salvar resposta'"></span></button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-layout.app>
