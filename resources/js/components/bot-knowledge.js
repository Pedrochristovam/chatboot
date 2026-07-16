export default () => ({
    topics: [],
    stats: { topics: 0, active_topics: 0, answers: 0, active_answers: 0 },
    askNameMessage: '',
    welcomeBackMessage: '',
    selectedId: null,
    showTopicModal: false,
    showKnowledgeModal: false,
    editingTopic: null,
    editingKnowledge: null,
    topicForm: { title: '', description: '', sort_order: 1, is_active: true, transfers_to_human: false },
    knowledgeForm: { question: '', answer: '', keywordsText: '', is_active: true },
    saving: false,
    savingAsk: false,

    get selected() {
        return this.topics.find((t) => t.id === this.selectedId) || null;
    },

    get faqs() {
        return this.selected?.knowledge || [];
    },

    init() {
        const el = document.getElementById('bot-knowledge-initial');
        if (!el) return;
        try {
            const data = JSON.parse(el.textContent || '{}');
            this.topics = Array.isArray(data.topics) ? data.topics : [];
            this.stats = data.stats || this.stats;
            this.askNameMessage = data.ask_name_message || '';
            this.welcomeBackMessage = data.welcome_back_message || '';
            if (this.topics.length) {
                this.selectedId = this.topics[0].id;
            }
        } catch (e) {
            console.error('Falha ao carregar dados do robô', e);
        }
    },

    selectTopic(id) {
        this.selectedId = id;
    },

    openTopicCreate() {
        this.editingTopic = null;
        const nextOrder = this.topics.reduce((max, t) => Math.max(max, Number(t.sort_order) || 0), 0) + 1;
        this.topicForm = {
            title: '',
            description: '',
            sort_order: nextOrder,
            is_active: true,
            transfers_to_human: false,
        };
        this.showTopicModal = true;
    },

    openTopicEdit() {
        if (!this.selected) return;
        this.editingTopic = this.selected;
        this.topicForm = {
            title: this.selected.title || '',
            description: this.selected.description || '',
            sort_order: Number(this.selected.sort_order) || 0,
            is_active: !!this.selected.is_active,
            transfers_to_human: !!this.selected.transfers_to_human,
        };
        this.showTopicModal = true;
    },

    closeTopicModal() {
        this.showTopicModal = false;
        this.editingTopic = null;
    },

    async saveTopic() {
        if (!this.topicForm.title.trim()) {
            window.Swal?.fire('Atenção', 'Informe o título do assunto.', 'warning');
            return;
        }
        this.saving = true;
        try {
            if (this.editingTopic) {
                const { data } = await window.api.put(`/bot-topics/${this.editingTopic.id}`, this.topicForm);
                this.replaceTopic(data.topic);
            } else {
                const { data } = await window.api.post('/bot-topics', this.topicForm);
                this.replaceTopic(data.topic);
                this.selectedId = data.topic.id;
            }
            this.closeTopicModal();
            await this.refresh();
            window.Swal?.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Assunto salvo', showConfirmButton: false, timer: 1600 });
        } catch (e) {
            this.flashError(e, 'Erro ao salvar assunto.');
        } finally {
            this.saving = false;
        }
    },

    async removeTopic() {
        if (!this.selected) return;
        const result = await window.Swal?.fire({
            title: 'Excluir assunto?',
            text: `Também remove as FAQs de "${this.selected.title}".`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#8B1E3F',
            confirmButtonText: 'Excluir',
            cancelButtonText: 'Cancelar',
        });
        if (!result?.isConfirmed) return;
        try {
            await window.api.delete(`/bot-topics/${this.selected.id}`);
            this.selectedId = null;
            await this.refresh();
        } catch (e) {
            this.flashError(e, 'Erro ao excluir assunto.');
        }
    },

    openKnowledgeCreate() {
        if (!this.selected) return;
        this.editingKnowledge = null;
        this.knowledgeForm = { question: '', answer: '', keywordsText: '', is_active: true };
        this.showKnowledgeModal = true;
    },

    openKnowledgeEdit(item) {
        this.editingKnowledge = item;
        this.knowledgeForm = {
            question: item.question || '',
            answer: item.answer || '',
            keywordsText: (item.keywords || []).join(', '),
            is_active: !!item.is_active,
        };
        this.showKnowledgeModal = true;
    },

    closeKnowledgeModal() {
        this.showKnowledgeModal = false;
        this.editingKnowledge = null;
    },

    async saveKnowledge() {
        if (!this.selected) return;
        if (!this.knowledgeForm.question.trim() || !this.knowledgeForm.answer.trim()) {
            window.Swal?.fire('Atenção', 'Preencha pergunta e resposta.', 'warning');
            return;
        }
        this.saving = true;
        const payload = {
            bot_topic_id: this.selected.id,
            question: this.knowledgeForm.question,
            answer: this.knowledgeForm.answer,
            keywords: this.knowledgeForm.keywordsText,
            is_active: this.knowledgeForm.is_active,
        };
        try {
            if (this.editingKnowledge) {
                await window.api.put(`/bot-knowledge-items/${this.editingKnowledge.id}`, payload);
            } else {
                await window.api.post('/bot-knowledge-items', payload);
            }
            this.closeKnowledgeModal();
            await this.refresh();
            window.Swal?.fire({ toast: true, position: 'top-end', icon: 'success', title: 'FAQ salva', showConfirmButton: false, timer: 1600 });
        } catch (e) {
            this.flashError(e, 'Erro ao salvar FAQ.');
        } finally {
            this.saving = false;
        }
    },

    async removeKnowledge(item) {
        const result = await window.Swal?.fire({
            title: 'Excluir FAQ?',
            text: item.question,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#8B1E3F',
            confirmButtonText: 'Excluir',
            cancelButtonText: 'Cancelar',
        });
        if (!result?.isConfirmed) return;
        try {
            await window.api.delete(`/bot-knowledge-items/${item.id}`);
            await this.refresh();
        } catch (e) {
            this.flashError(e, 'Erro ao excluir FAQ.');
        }
    },

    async saveAskName() {
        this.savingAsk = true;
        try {
            await window.api.put('/bot-knowledge/ask-name', {
                ask_name_message: this.askNameMessage,
                welcome_back_message: this.welcomeBackMessage,
            });
            window.Swal?.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Mensagens salvas', showConfirmButton: false, timer: 1600 });
        } catch (e) {
            this.flashError(e, 'Não foi possível salvar as mensagens.');
        } finally {
            this.savingAsk = false;
        }
    },

    replaceTopic(topic) {
        const idx = this.topics.findIndex((t) => t.id === topic.id);
        if (idx >= 0) this.topics.splice(idx, 1, topic);
        else this.topics.push(topic);
        this.topics.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
    },

    async refresh() {
        const keepId = this.selectedId;
        const { data } = await window.api.get('/bot-knowledge');
        this.topics = data.topics || [];
        this.stats = data.stats || this.stats;
        this.askNameMessage = data.ask_name_message || this.askNameMessage;
        this.welcomeBackMessage = data.welcome_back_message || this.welcomeBackMessage;
        this.selectedId = this.topics.some((t) => t.id === keepId)
            ? keepId
            : (this.topics[0]?.id ?? null);
    },

    flashError(e, fallback) {
        const msg = Object.values(e.response?.data?.errors || {}).flat().join(', ')
            || e.response?.data?.message
            || fallback;
        window.Swal?.fire('Erro', msg, 'error');
    },
});
