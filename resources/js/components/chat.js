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
    hasMoreMessages: false,
    nextBeforeId: null,
    conversations: [],
    messages: [],
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

    init() {
        this.refreshVisibleEmojis();
        this.loadConversations();
        this.subscribeInbox();
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

    async loadConversations({ preserveSelection = false } = {}) {
        try {
            const { data } = await api.get('/conversations', {
                params: { search: this.search, status: this.activeFilter },
            });
            this.conversations = data.conversations || [];
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
            console.error('Erro ao carregar conversas', e);
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
            this.subscribeRealtime(conv.id);
        } catch (e) {
            console.error('Erro ao carregar mensagens', e);
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
            console.error('Erro ao carregar histórico', e);
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
        } catch (e) {
            const msg = e.response?.data?.message
                || Object.values(e.response?.data?.errors || {}).flat().join(', ')
                || 'Não foi possível enviar a mensagem.';
            window.Swal?.fire('Erro', msg, 'error');
            this.newMessage = caption;
            if (imageFile) {
                this.pendingImage = imageFile;
                this.pendingImageName = imageFile.name;
                this.pendingImagePreview = URL.createObjectURL(imageFile);
            }
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
        await api.post(`/conversations/${this.selected.id}/close`);
        await this.loadConversations({ preserveSelection: true });
        this.selected = null;
        this.selectedConversation = null;
        this.messages = [];
        this.showEmojiPicker = false;
        this.mobileView = 'list';
        window.Swal?.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Encerrada — veja em “Encerradas por mim”',
            showConfirmButton: false,
            timer: 2800,
        });
    },
});
