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

    get isReadOnly() {
        return this.selectedConversation?.is_read_only ?? this.selected?.is_read_only ?? false;
    },

    init() {
        this.refreshVisibleEmojis();
        this.loadConversations();
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

    async loadConversations() {
        try {
            const { data } = await api.get('/conversations', {
                params: { search: this.search, status: this.activeFilter },
            });
            this.conversations = data.conversations || [];
            if (this.conversations.length && !this.selected) {
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
        try {
            const { data } = await api.get(`/conversations/${conv.id}`);
            this.messages = data.messages || [];
            this.selectedClient = data.conversation.client;
            this.selectedConversation = data.conversation;
            this.subscribeRealtime(conv.id);
        } catch (e) {
            console.error('Erro ao carregar mensagens', e);
        } finally {
            this.loading = false;
        }
    },

    subscribeRealtime(conversationId) {
        if (!window.Echo || !conversationId) return;
        try {
            if (this._echoChannel) {
                window.Echo.leave(this._echoChannel);
            }
            this._echoChannel = `conversation.${conversationId}`;
            window.Echo.channel(this._echoChannel)
                .listen('.message.received', (payload) => {
                    if (!this.messages.find((m) => m.id === payload.id)) {
                        this.messages.push(payload);
                    }
                })
                .listen('.message.sent', (payload) => {
                    if (!this.messages.find((m) => m.id === payload.id)) {
                        this.messages.push(payload);
                    }
                });
        } catch (e) {
            console.warn('Realtime indisponível', e);
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
        await this.loadConversations();
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
