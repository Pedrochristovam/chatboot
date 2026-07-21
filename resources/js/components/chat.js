import api from '../utils/api';
import emojiCategories from './emojis';

const FALLBACK_EMOJIS = [{
    id: 'smileys',
    label: 'Carinhas',
    icon: '😀',
    emojis: ['😀', '😁', '😂', '🤣', '😊', '😍', '🥰', '😘', '😎', '🤩', '😢', '😭', '😤', '😡', '👍', '👎', '🙏', '👏', '🔥', '❤️', '💯', '✅', '🎉'],
}];

const EMOJI_DATA = Array.isArray(emojiCategories) && emojiCategories.length
    ? emojiCategories
    : FALLBACK_EMOJIS;

const toast = (icon, title) => {
    window.Swal?.fire({
        toast: true,
        position: 'top-end',
        icon,
        title,
        showConfirmButton: false,
        timer: 2800,
    });
};

const apiError = (e, fallback = 'Falha na requisição.') =>
    e.response?.data?.message
    || Object.values(e.response?.data?.errors || {}).flat().join(', ')
    || fallback;

export default () => ({
    mobileView: 'list',
    search: '',
    activeFilter: 'all',
    selected: null,
    selectedClient: null,
    selectedConversation: null,
    newMessage: '',
    pendingImage: null,
    pendingImagePreview: null,
    pendingImageName: '',
    loading: false,
    loadingOlder: false,
    loadingMoreInbox: false,
    hasMoreMessages: false,
    nextBeforeId: null,
    inboxMeta: { total: 0, limit: 50, offset: 0, has_more: false },
    conversations: [],
    messages: [],
    internalNotes: [],
    scheduledMessages: [],
    noteDraft: '',
    scheduleDraft: '',
    scheduleAt: '',
    agents: [],
    departments: [],
    currentUserId: null,
    transferAgentId: '',
    transferDepartmentId: '',
    transferReason: '',
    templateName: '',
    templateLanguage: 'pt_BR',
    templateParams: '',
    showTransferModal: false,
    showTemplateModal: false,
    showScheduleModal: false,
    networkError: null,
    showEmojiPicker: false,
    emojiTab: EMOJI_DATA[0]?.id || 'smileys',
    emojiSearch: '',
    emojiCategories: EMOJI_DATA,
    visibleEmojis: EMOJI_DATA[0]?.emojis || FALLBACK_EMOJIS[0].emojis,
    filters: [
        { id: 'all', label: 'Todas' },
        { id: 'waiting', label: 'Aguardando' },
        { id: 'active', label: 'Em atendimento' },
    ],
    _inboxReloadTimer: null,

    get isReadOnly() {
        return this.selectedConversation?.is_read_only ?? this.selected?.is_read_only ?? false;
    },

    get careWindowOpen() {
        return this.selectedConversation?.care_window?.open !== false;
    },

    get canClaim() {
        return !this.isReadOnly
            && this.selected
            && (!this.selectedConversation?.assigned_to || this.selectedConversation?.assigned_to === this.currentUserId);
    },

    init() {
        this.refreshVisibleEmojis();
        this.loadLookup();
        this.loadConversations();
        this.subscribeInbox();
    },

    async loadLookup() {
        try {
            const { data } = await api.get('/conversations/lookup');
            this.agents = data.agents || [];
            this.departments = data.departments || [];
            this.currentUserId = data.current_user_id;
            this.networkError = null;
        } catch (e) {
            this.networkError = 'Não foi possível carregar atendentes/departamentos.';
        }
    },

    refreshVisibleEmojis() {
        const cats = this.emojiCategories || [];
        const q = (this.emojiSearch || '').trim().toLowerCase();

        if (q) {
            const matched = cats.filter(
                (c) => c.label.toLowerCase().includes(q) || c.id.includes(q)
            );
            this.visibleEmojis = matched.length
                ? matched.flatMap((c) => c.emojis)
                : (cats.find((c) => c.id === this.emojiTab) || cats[0])?.emojis || [];
            return;
        }

        const cat = cats.find((c) => c.id === this.emojiTab) || cats[0];
        this.visibleEmojis = cat?.emojis || [];
    },

    setEmojiTab(id) {
        this.emojiTab = id;
        this.emojiSearch = '';
        this.refreshVisibleEmojis();
    },

    currentCategoryLabel() {
        const cat = (this.emojiCategories || []).find((c) => c.id === this.emojiTab);
        const q = (this.emojiSearch || '').trim();
        if (q) {
            const hasMatch = (this.emojiCategories || []).some(
                (c) => c.label.toLowerCase().includes(q.toLowerCase()) || c.id.includes(q.toLowerCase())
            );
            return hasMatch ? 'Resultados' : (cat?.label || 'Emojis');
        }
        return cat?.label || 'Emojis';
    },

    scheduleInboxReload() {
        if (this._inboxReloadTimer) {
            window.clearTimeout(this._inboxReloadTimer);
        }
        this._inboxReloadTimer = window.setTimeout(() => {
            this.loadConversations({ preserveSelection: true });
        }, 2500);
    },

    upsertConversation(card) {
        if (!card?.id) {
            this.scheduleInboxReload();
            return;
        }

        const matchesFilter = this.activeFilter === 'all'
            || card.status === this.activeFilter
            || (this.activeFilter === 'waiting' && card.status === 'waiting')
            || (this.activeFilter === 'active' && card.status === 'active');

        if (!matchesFilter) {
            this.conversations = this.conversations.filter((c) => c.id !== card.id);
            if (this.selected?.id === card.id) {
                this.selected = null;
                this.selectedConversation = null;
                this.messages = [];
            }
            return;
        }

        const idx = this.conversations.findIndex((c) => c.id === card.id);
        if (idx >= 0) {
            this.conversations.splice(idx, 1);
        }
        this.conversations.unshift(card);
        if (this.selected?.id === card.id) {
            this.selected = card;
        }
    },

    async loadConversations({ preserveSelection = false, append = false } = {}) {
        try {
            const offset = append ? this.conversations.length : 0;
            const { data } = await api.get('/conversations', {
                params: {
                    search: this.search,
                    status: this.activeFilter,
                    limit: 50,
                    offset,
                },
            });
            const rows = data.conversations || [];
            this.inboxMeta = data.meta || this.inboxMeta;
            this.conversations = append ? [...this.conversations, ...rows.filter((r) => !this.conversations.some((c) => c.id === r.id))] : rows;
            this.networkError = null;
            if (this.conversations.length && !this.selected && !preserveSelection) {
                await this.selectConversation(this.conversations[0]);
            } else if (this.selected) {
                const updated = this.conversations.find((c) => c.id === this.selected.id);
                if (updated) this.selected = updated;
                else if (!this.conversations.length) {
                    this.selected = null;
                    this.selectedClient = null;
                    this.selectedConversation = null;
                    this.messages = [];
                }
            }
        } catch (e) {
            this.networkError = 'Falha ao carregar a caixa de entrada.';
            toast('error', this.networkError);
        }
    },

    async loadMoreInbox() {
        if (!this.inboxMeta?.has_more || this.loadingMoreInbox) return;
        this.loadingMoreInbox = true;
        try {
            await this.loadConversations({ preserveSelection: true, append: true });
        } finally {
            this.loadingMoreInbox = false;
        }
    },

    get filteredConversations() {
        return this.conversations;
    },

    async selectConversation(conv) {
        this.selected = conv;
        conv.unread = 0;
        this.mobileView = 'chat';
        this.showEmojiPicker = false;
        this.loading = true;
        this.messages = [];
        this.internalNotes = [];
        this.scheduledMessages = [];
        this.hasMoreMessages = false;
        this.nextBeforeId = null;
        try {
            const { data } = await api.get(`/conversations/${conv.id}`, {
                params: { limit: 50 },
            });
            this.messages = data.messages || [];
            this.hasMoreMessages = !!data.messages_meta?.has_more;
            this.nextBeforeId = data.messages_meta?.next_before_id || null;
            this.selectedClient = data.conversation.client;
            this.selectedConversation = data.conversation;
            this.internalNotes = data.internal_notes || [];
            this.scheduledMessages = data.scheduled_messages || [];
            this.networkError = null;
            this.subscribeRealtime(conv.id);
        } catch (e) {
            this.networkError = 'Não foi possível abrir a conversa.';
            toast('error', this.networkError);
        } finally {
            this.loading = false;
        }
    },

    async loadOlderMessages() {
        if (!this.selected || !this.hasMoreMessages || this.loadingOlder || !this.nextBeforeId) return;
        this.loadingOlder = true;
        const scroller = this.$refs?.messageList;
        const previousHeight = scroller?.scrollHeight || 0;
        try {
            const { data } = await api.get(`/conversations/${this.selected.id}`, {
                params: { limit: 50, before_id: this.nextBeforeId },
            });
            const older = data.messages || [];
            const existing = new Set(this.messages.map((m) => m.id));
            this.messages = [...older.filter((m) => !existing.has(m.id)), ...this.messages];
            this.hasMoreMessages = !!data.messages_meta?.has_more;
            this.nextBeforeId = data.messages_meta?.next_before_id || null;
            this.$nextTick(() => {
                if (scroller) {
                    scroller.scrollTop = scroller.scrollHeight - previousHeight;
                }
            });
        } catch (e) {
            toast('error', 'Erro ao carregar histórico');
        } finally {
            this.loadingOlder = false;
        }
    },

    subscribeRealtime(conversationId) {
        if (!window.Echo || !conversationId) return;
        try {
            if (this._echoChannel) {
                window.Echo.leave(this._echoChannel);
            }
            this._echoChannel = `conversation.${conversationId}`;
            window.Echo.private(this._echoChannel)
                .listen('.message.received', (payload) => {
                    if (!this.messages.find((m) => m.id === payload.id)) {
                        this.messages.push(payload);
                    }
                })
                .listen('.message.sent', (payload) => {
                    if (!this.messages.find((m) => m.id === payload.id)) {
                        this.messages.push(payload);
                    }
                })
                .listen('.message.status', (payload) => {
                    const message = this.messages.find((m) => m.id === payload.id);
                    if (message) {
                        message.status = payload.status;
                        message.error = payload.error;
                    }
                });
        } catch (e) {
            console.warn('Realtime indisponível', e);
        }
    },

    subscribeInbox() {
        if (!window.Echo) {
            this._fallbackPoll = window.setInterval(() => this.loadConversations({ preserveSelection: true }), 15000);
            return;
        }
        try {
            window.Echo.private('inbox')
                .listen('.message.received', (payload) => {
                    const existing = this.conversations.find((c) => c.id === payload.conversation_id);
                    if (existing) {
                        existing.preview = payload.image_url ? '📷 Imagem' : (payload.text || existing.preview);
                        existing.time = payload.time || existing.time;
                        if (this.selected?.id !== existing.id) {
                            existing.unread = (existing.unread || 0) + 1;
                        }
                        this.conversations = [existing, ...this.conversations.filter((c) => c.id !== existing.id)];
                    } else {
                        this.scheduleInboxReload();
                    }
                })
                .listen('.conversation.updated', (payload) => {
                    if (payload.conversation) {
                        this.upsertConversation(payload.conversation);
                    } else {
                        this.scheduleInboxReload();
                    }
                });
        } catch (e) {
            console.warn('Inbox em tempo real indisponível', e);
            this._fallbackPoll = window.setInterval(() => this.loadConversations({ preserveSelection: true }), 15000);
        }
    },

    insertEmoji(emoji) {
        this.newMessage = (this.newMessage || '') + emoji;
        this.$nextTick(() => {
            const input = this.$refs.messageInput;
            if (input) {
                input.focus();
                const len = this.newMessage.length;
                try {
                    input.setSelectionRange(len, len);
                } catch (_) {}
            }
        });
    },

    onImageSelected(event) {
        const file = event.target.files?.[0];
        event.target.value = '';
        if (!file || !this.selected || this.isReadOnly) return;

        if (file.size > 5 * 1024 * 1024) {
            window.Swal?.fire('Arquivo grande', 'A imagem deve ter no máximo 5 MB.', 'warning');
            return;
        }

        this.clearPendingImage();
        this.pendingImage = file;
        this.pendingImageName = file.name;
        this.pendingImagePreview = URL.createObjectURL(file);
    },

    clearPendingImage() {
        if (this.pendingImagePreview) {
            URL.revokeObjectURL(this.pendingImagePreview);
        }
        this.pendingImage = null;
        this.pendingImagePreview = null;
        this.pendingImageName = '';
    },

    async sendMessage() {
        if (!this.selected || this.isReadOnly) return;
        const hasImage = !!this.pendingImage;
        const content = (this.newMessage || '').trim();
        if (!hasImage && !content) return;

        const caption = content;
        const imageFile = this.pendingImage;
        this.newMessage = '';
        this.showEmojiPicker = false;
        this.clearPendingImage();

        try {
            let data;
            if (imageFile) {
                const form = new FormData();
                form.append('image', imageFile);
                if (caption) form.append('content', caption);
                const response = await api.post(`/conversations/${this.selected.id}/messages`, form, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                data = response.data;
            } else {
                const response = await api.post(`/conversations/${this.selected.id}/messages`, { content: caption });
                data = response.data;
            }
            this.messages.push(data);
            this.selected.preview = data.image_url ? '📷 Imagem' : (data.text || caption);
            this.networkError = null;
        } catch (e) {
            const msg = apiError(e, 'Não foi possível enviar a mensagem.');
            window.Swal?.fire('Erro', msg, 'error');
            this.newMessage = caption;
            if (imageFile) {
                this.pendingImage = imageFile;
                this.pendingImageName = imageFile.name;
                this.pendingImagePreview = URL.createObjectURL(imageFile);
            }
        }
    },

    async claimConversation() {
        if (!this.selected || this.isReadOnly) return;
        try {
            await api.post(`/conversations/${this.selected.id}/assign`, {});
            toast('success', 'Conversa assumida');
            await this.selectConversation(this.selected);
            await this.loadConversations({ preserveSelection: true });
        } catch (e) {
            window.Swal?.fire('Erro', apiError(e, 'Não foi possível assumir.'), 'error');
        }
    },

    openTransferModal() {
        this.transferAgentId = '';
        this.transferDepartmentId = this.selectedConversation?.department_id || '';
        this.transferReason = '';
        this.showTransferModal = true;
    },

    async submitTransfer() {
        if (!this.selected) return;
        if (!this.transferAgentId && !this.transferDepartmentId) {
            window.Swal?.fire('Atenção', 'Selecione um atendente ou departamento.', 'warning');
            return;
        }
        try {
            await api.post(`/conversations/${this.selected.id}/transfer`, {
                agent_id: this.transferAgentId || null,
                department_id: this.transferDepartmentId || null,
                reason: this.transferReason || null,
            });
            this.showTransferModal = false;
            toast('success', 'Conversa transferida');
            await this.loadConversations({ preserveSelection: true });
            this.selected = null;
            this.selectedConversation = null;
            this.messages = [];
            this.mobileView = 'list';
        } catch (e) {
            window.Swal?.fire('Erro', apiError(e, 'Falha na transferência.'), 'error');
        }
    },

    async addNote() {
        const body = (this.noteDraft || '').trim();
        if (!body || !this.selected) return;
        try {
            const { data } = await api.post(`/conversations/${this.selected.id}/notes`, { body });
            this.internalNotes = [data.note, ...this.internalNotes];
            this.noteDraft = '';
            toast('success', 'Nota adicionada');
        } catch (e) {
            window.Swal?.fire('Erro', apiError(e, 'Não foi possível salvar a nota.'), 'error');
        }
    },

    async removeNote(note) {
        try {
            await api.delete(`/conversation-notes/${note.id}`);
            this.internalNotes = this.internalNotes.filter((n) => n.id !== note.id);
        } catch (e) {
            window.Swal?.fire('Erro', apiError(e, 'Não foi possível remover.'), 'error');
        }
    },

    openScheduleModal() {
        const inOneHour = new Date(Date.now() + 60 * 60 * 1000);
        inOneHour.setMinutes(inOneHour.getMinutes() - inOneHour.getTimezoneOffset());
        this.scheduleAt = inOneHour.toISOString().slice(0, 16);
        this.scheduleDraft = '';
        this.showScheduleModal = true;
    },

    async submitSchedule() {
        if (!this.selected || !(this.scheduleDraft || '').trim() || !this.scheduleAt) return;
        try {
            const { data } = await api.post(`/conversations/${this.selected.id}/scheduled-messages`, {
                content: this.scheduleDraft.trim(),
                scheduled_at: new Date(this.scheduleAt).toISOString(),
            });
            this.scheduledMessages = [...(this.scheduledMessages || []), data.scheduled];
            this.showScheduleModal = false;
            toast('success', 'Mensagem agendada');
        } catch (e) {
            window.Swal?.fire('Erro', apiError(e, 'Falha ao agendar.'), 'error');
        }
    },

    async cancelSchedule(item) {
        try {
            await api.delete(`/scheduled-messages/${item.id}`);
            this.scheduledMessages = this.scheduledMessages.filter((s) => s.id !== item.id);
            toast('success', 'Agendamento cancelado');
        } catch (e) {
            window.Swal?.fire('Erro', apiError(e, 'Não foi possível cancelar.'), 'error');
        }
    },

    openTemplateModal() {
        this.templateName = '';
        this.templateLanguage = 'pt_BR';
        this.templateParams = '';
        this.showTemplateModal = true;
    },

    async submitTemplate() {
        if (!this.selected || !(this.templateName || '').trim()) return;
        const params = (this.templateParams || '')
            .split('\n')
            .map((s) => s.trim())
            .filter(Boolean);
        try {
            const { data } = await api.post(`/conversations/${this.selected.id}/templates`, {
                template_name: this.templateName.trim(),
                language: this.templateLanguage || 'pt_BR',
                body_parameters: params,
            });
            this.messages.push(data);
            this.showTemplateModal = false;
            toast('success', 'Template enviado');
            if (this.selectedConversation?.care_window) {
                this.selectedConversation.care_window.open = true;
            }
        } catch (e) {
            window.Swal?.fire('Erro', apiError(e, 'Falha ao enviar template.'), 'error');
        }
    },

    async closeConversation() {
        if (!this.selected || this.isReadOnly) return;
        const result = await window.Swal?.fire({
            title: 'Encerrar conversa?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#8B1E3F',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Encerrar',
        });
        if (!result?.isConfirmed) return;
        try {
            await api.post(`/conversations/${this.selected.id}/close`);
            await this.loadConversations({ preserveSelection: true });
            this.selected = null;
            this.selectedConversation = null;
            this.messages = [];
            this.showEmojiPicker = false;
            this.mobileView = 'list';
            toast('success', 'Encerrada — veja em “Encerradas por mim”');
        } catch (e) {
            window.Swal?.fire('Erro', apiError(e, 'Não foi possível encerrar.'), 'error');
        }
    },

    retryLoad() {
        this.loadLookup();
        this.loadConversations({ preserveSelection: true });
        if (this.selected) this.selectConversation(this.selected);
    },
});
