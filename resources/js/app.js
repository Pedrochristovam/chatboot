import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import Swal from 'sweetalert2';
import api from './utils/api';
import chatApp from './components/chat';
import botKnowledgeApp from './components/bot-knowledge';

window.Alpine = Alpine;
window.Chart = Chart;
window.Swal = Swal;
window.api = api;

Alpine.data('chatApp', chatApp);
Alpine.data('botKnowledgeApp', botKnowledgeApp);

Alpine.data('botConversationsApp', () => ({
    search: '',
    loading: false,
    conversations: [],
    selected: null,
    detail: null,
    messages: [],
    detailLoading: false,
    mobileView: 'list',

    async init() {
        await this.loadConversations();
    },

    async loadConversations() {
        this.loading = true;
        try {
            const { data } = await api.get('/conversations', {
                params: { search: this.search, status: 'bot_closed' },
            });
            this.conversations = data.conversations || [];
            if (this.conversations.length && !this.selected) {
                await this.openDetails(this.conversations[0]);
            } else if (this.selected) {
                const still = this.conversations.find((c) => c.id === this.selected.id);
                if (!still) {
                    this.selected = null;
                    this.detail = null;
                    this.messages = [];
                    this.mobileView = 'list';
                }
            }
        } catch (e) {
            console.error('Erro ao carregar conversas do bot', e);
        } finally {
            this.loading = false;
        }
    },

    async openDetails(conv) {
        this.selected = conv;
        this.mobileView = 'chat';
        this.detailLoading = true;
        this.messages = [];
        try {
            const { data } = await api.get(`/conversations/${conv.id}`);
            this.detail = data.conversation;
            this.messages = data.messages || [];
        } catch (e) {
            console.error('Erro ao carregar detalhes', e);
            window.Swal?.fire('Erro', 'Não foi possível carregar os detalhes.', 'error');
        } finally {
            this.detailLoading = false;
        }
    },
}));

Alpine.data('closedConversationsApp', (agentId) => ({
    agentId,
    search: '',
    loading: false,
    conversations: [],
    selected: null,
    detail: null,
    messages: [],
    detailLoading: false,
    mobileView: 'list',

    async init() {
        await this.loadConversations();
    },

    async loadConversations() {
        this.loading = true;
        try {
            const { data } = await api.get('/conversations', {
                params: {
                    search: this.search,
                    status: 'closed',
                    closed_by: this.agentId,
                },
            });
            this.conversations = data.conversations || [];
            if (this.conversations.length && !this.selected) {
                await this.openDetails(this.conversations[0]);
            } else if (this.selected) {
                const still = this.conversations.find((c) => c.id === this.selected.id);
                if (!still) {
                    this.selected = null;
                    this.detail = null;
                    this.messages = [];
                    this.mobileView = 'list';
                }
            }
        } catch (e) {
            console.error('Erro ao carregar conversas encerradas', e);
        } finally {
            this.loading = false;
        }
    },

    async openDetails(conv) {
        this.selected = conv;
        this.mobileView = 'chat';
        this.detailLoading = true;
        this.messages = [];
        try {
            const { data } = await api.get(`/conversations/${conv.id}`);
            this.detail = data.conversation;
            this.messages = data.messages || [];
        } catch (e) {
            console.error('Erro ao carregar detalhes', e);
            window.Swal?.fire('Erro', 'Não foi possível carregar os detalhes.', 'error');
        } finally {
            this.detailLoading = false;
        }
    },
}));

Alpine.data('clientsApp', () => ({
    showModal: false,
    editing: null,
    form: { name: '', phone: '', email: '', company: '', notes: '', status: 'active', tag_ids: [] },
    saving: false,

    openCreate() {
        this.editing = null;
        this.form = { name: '', phone: '', email: '', company: '', notes: '', status: 'active', tag_ids: [] };
        this.showModal = true;
    },

    openEdit(client) {
        this.editing = client;
        this.form = {
            name: client.name,
            phone: client.phone,
            email: client.email || '',
            company: client.company || '',
            notes: client.notes || '',
            status: client.status?.value ?? client.status ?? 'active',
            tag_ids: (client.tags || []).map(t => t.id),
        };
        this.showModal = true;
    },

    async save() {
        this.saving = true;
        try {
            if (this.editing) {
                await api.put(`/clients/${this.editing.id}`, this.form);
            } else {
                await api.post('/clients', this.form);
            }
            this.showModal = false;
            window.location.reload();
        } catch (e) {
            const msg = e.response?.data?.message || Object.values(e.response?.data?.errors || {}).flat().join(', ') || 'Erro ao salvar.';
            Swal.fire('Erro', msg, 'error');
        } finally {
            this.saving = false;
        }
    },

    async remove(client) {
        const result = await Swal.fire({
            title: 'Remover cliente?',
            text: client.name,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Remover',
            cancelButtonText: 'Cancelar',
        });
        if (!result.isConfirmed) return;
        await api.delete(`/clients/${client.id}`);
        window.location.reload();
    },
}));

Alpine.data('settingsApp', () => ({
    form: {},
    saving: false,
    activeTab: 'geral',

    init() {
        const el = document.getElementById('settings-initial-data');
        if (el) {
            try {
                this.form = JSON.parse(el.textContent || '{}');
            } catch {
                this.form = {};
            }
        }
    },

    async save() {
        this.saving = true;
        try {
            await api.put('/settings', this.form);
            Swal.fire('Sucesso', 'Configurações salvas!', 'success');
        } catch (e) {
            Swal.fire('Erro', 'Não foi possível salvar.', 'error');
        } finally {
            this.saving = false;
        }
    },
}));

Alpine.data('agentsApp', () => ({
    showModal: false,
    form: { name: '', email: '', password: '', role_title: 'Atendente', role_id: '', department_ids: [] },
    saving: false,

    openCreate() {
        this.form = { name: '', email: '', password: '', role_title: 'Atendente', role_id: '', department_ids: [] };
        this.showModal = true;
    },

    async save() {
        this.saving = true;
        try {
            await api.post('/agents', this.form);
            this.showModal = false;
            window.location.reload();
        } catch (e) {
            const msg = Object.values(e.response?.data?.errors || {}).flat().join(', ') || 'Erro ao criar atendente.';
            Swal.fire('Erro', msg, 'error');
        } finally {
            this.saving = false;
        }
    },
}));

Alpine.start();

if (document.body.dataset.authenticatedUser) {
    const heartbeat = () => api.post('/presence/heartbeat').catch(() => {});
    const markOffline = () => {
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (navigator.sendBeacon) {
                const body = new Blob([JSON.stringify({ _token: token })], { type: 'application/json' });
                // Prefer fetch keepalive so CSRF cookie/session header still applies
            }
            fetch('/api/internal/presence/offline', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                credentials: 'same-origin',
                keepalive: true,
                body: '{}',
            }).catch(() => {});
        } catch (_) {}
    };

    heartbeat();
    const heartbeatTimer = window.setInterval(heartbeat, 60000);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') heartbeat();
    });

    window.addEventListener('pagehide', () => {
        window.clearInterval(heartbeatTimer);
        markOffline();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('canvas[data-type]').forEach((canvas) => initChart(canvas));
});

function initChart(canvas) {
    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');
    const type = canvas.dataset.type || 'line';
    const color = canvas.dataset.color || '#8B1E3F';
    const compact = canvas.dataset.compact === 'true';

    const config = {
        type,
        data: {
            labels,
            datasets: [{
                label: 'Dados',
                data: values,
                borderColor: color,
                backgroundColor: type === 'bar'
                    ? values.map((_, i) => (i === values.length - 1 ? color : `${color}33`))
                    : type === 'doughnut'
                        ? [color, '#E8C4CE']
                        : 'rgba(139, 30, 63, 0.12)',
                tension: 0.35,
                fill: type === 'line',
                borderRadius: type === 'bar' ? (compact ? 6 : 8) : 0,
                borderSkipped: false,
                maxBarThickness: compact ? 28 : 48,
                borderWidth: type === 'doughnut' ? 0 : (type === 'bar' ? 0 : 2.5),
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: type === 'line' ? 4 : 0,
                pointHoverRadius: type === 'line' ? 6 : 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: !compact,
            plugins: {
                legend: { display: type === 'doughnut' },
                tooltip: {
                    backgroundColor: '#8B1E3F',
                    titleFont: { size: 12 },
                    bodyFont: { size: 12 },
                    padding: 10,
                    cornerRadius: 10,
                    displayColors: false,
                },
            },
            layout: compact ? { padding: { top: 4, right: 4, bottom: 0, left: 0 } } : {},
            scales: type === 'doughnut' ? {} : {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: {
                        precision: 0,
                        color: '#94a3b8',
                        font: { size: compact ? 10 : 11 },
                        maxTicksLimit: compact ? 4 : 8,
                    },
                    border: { display: false },
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: compact ? 10 : 11 },
                        maxRotation: 0,
                        autoSkip: true,
                    },
                    border: { display: false },
                },
            },
        },
    };

    if (type === 'doughnut') {
        config.options.cutout = '70%';
    }

    new Chart(canvas, config);
}
