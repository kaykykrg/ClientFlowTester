/**
 * NotificationManager — Sistema de notificações in-app do ClientFlow
 *
 * Responsável por:
 *  - Renderizar o sino com badge na topbar
 *  - Exibir dropdown estilo Facebook
 *  - Fazer polling a cada 30 s para atualizar a contagem
 *  - Marcar notificações como lidas ao clicar
 */

class NotificationManager {
    static _pollInterval = null;
    static _open = false;
    static _notifications = [];
    static _unreadCount = 0;

    // -----------------------------------------------------------------------
    // Bootstrap
    // -----------------------------------------------------------------------
    static async init() {
        this._injectBellButton();
        this._injectDropdown();
        this._bindEvents();
        await this.fetchNotifications();
        this._startPolling();
    }

    // -----------------------------------------------------------------------
    // DOM helpers
    // -----------------------------------------------------------------------
    static _injectBellButton() {
        if (document.getElementById('cf-notif-bell')) return;

        const topbar = document.getElementById('clientflow-topbar');
        if (!topbar) return;

        const bellHtml = `
            <div class="cf-notif-wrapper" id="cf-notif-wrapper">
                <button type="button" id="cf-notif-bell" class="cf-notif-bell" aria-label="Notificações" title="Notificações">
                    <i class="fas fa-bell"></i>
                    <span class="cf-notif-badge" id="cf-notif-badge" style="display:none;">0</span>
                </button>
            </div>
        `;

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = bellHtml.trim();
        const bellEl = tempDiv.firstElementChild;

        // Insere dentro do grupo direito do topbar, antes do avatar
        const rightGroup = document.getElementById('topbar-right');
        const avatarBtn  = document.getElementById('openProfileBtn');

        if (rightGroup && avatarBtn) {
            rightGroup.insertBefore(bellEl, avatarBtn);
        } else if (rightGroup) {
            rightGroup.prepend(bellEl);
        } else if (avatarBtn && avatarBtn.parentNode) {
            avatarBtn.parentNode.insertBefore(bellEl, avatarBtn);
        } else {
            const container = topbar.querySelector('.container-fluid');
            if (container) container.appendChild(bellEl);
        }
    }

    static _injectDropdown() {
        if (document.getElementById('cf-notif-dropdown')) return;

        const dropdownHtml = `
            <div id="cf-notif-dropdown" class="cf-notif-dropdown" role="dialog" aria-label="Painel de Notificações">
                <div class="cf-notif-dropdown-header">
                    <span class="cf-notif-dropdown-title">
                        <i class="fas fa-bell me-2"></i>Notificações
                    </span>
                    <button type="button" id="cf-notif-mark-all" class="cf-notif-mark-all-btn" title="Marcar todas como lidas">
                        <i class="fas fa-check-double me-1"></i>Marcar todas
                    </button>
                </div>
                <div class="cf-notif-list" id="cf-notif-list">
                    <div class="cf-notif-empty">
                        <i class="fas fa-bell-slash"></i>
                        <p>Nenhuma notificação</p>
                    </div>
                </div>
            </div>
        `;

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = dropdownHtml.trim();
        document.body.appendChild(tempDiv.firstElementChild);
    }

    // -----------------------------------------------------------------------
    // Events
    // -----------------------------------------------------------------------
    static _bindEvents() {
        // Abre/fecha dropdown ao clicar no sino
        document.addEventListener('click', (e) => {
            const bell = document.getElementById('cf-notif-bell');
            const dropdown = document.getElementById('cf-notif-dropdown');
            const wrapper = document.getElementById('cf-notif-wrapper');

            if (!bell || !dropdown) return;

            if (wrapper && wrapper.contains(e.target)) {
                this._toggle();
            } else if (!dropdown.contains(e.target)) {
                this._close();
            }
        });

        // Marcar todas como lidas
        document.addEventListener('click', (e) => {
            if (e.target.closest('#cf-notif-mark-all')) {
                this.markAllAsRead();
            }
        });
    }

    static _toggle() {
        this._open ? this._close() : this._openDropdown();
    }

    static _openDropdown() {
        const dropdown = document.getElementById('cf-notif-dropdown');
        const bell = document.getElementById('cf-notif-bell');
        if (!dropdown) return;
        this._open = true;
        dropdown.classList.add('open');
        bell?.classList.add('active');
        this._positionDropdown();
    }

    static _close() {
        const dropdown = document.getElementById('cf-notif-dropdown');
        const bell = document.getElementById('cf-notif-bell');
        if (!dropdown) return;
        this._open = false;
        dropdown.classList.remove('open');
        bell?.classList.remove('active');
    }

    static _positionDropdown() {
        const wrapper = document.getElementById('cf-notif-wrapper');
        const dropdown = document.getElementById('cf-notif-dropdown');
        if (!wrapper || !dropdown) return;

        const rect = wrapper.getBoundingClientRect();
        const ddWidth = 360;
        let left = rect.right - ddWidth;
        if (left < 8) left = 8;

        dropdown.style.top  = (rect.bottom + 8) + 'px';
        dropdown.style.left = left + 'px';
    }

    // -----------------------------------------------------------------------
    // Data fetching
    // -----------------------------------------------------------------------
    static async fetchNotifications() {
        try {
            const apiBase = this._getApiBase();
            const res = await fetch(apiBase + 'notificacoes_listar.php', {
                credentials: 'include'
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.status === 'ok') {
                this._notifications = data.data.notificacoes || [];
                this._unreadCount   = data.data.nao_lidas    || 0;
                this._updateBadge();
                this._renderList();
            }
        } catch (err) {
            // silencioso — não interrompe o fluxo
        }
    }

    static _getApiBase() {
        const path = window.location.pathname;
        // Suporte para /public/pages/ ou raiz
        if (path.includes('/public/pages/')) return '../../api/';
        if (path.includes('/public/'))      return '../api/';
        return 'api/';
    }

    static _startPolling() {
        if (this._pollInterval) clearInterval(this._pollInterval);
        this._pollInterval = setInterval(() => this.fetchNotifications(), 30000);
    }

    // -----------------------------------------------------------------------
    // Rendering
    // -----------------------------------------------------------------------
    static _updateBadge() {
        const badge = document.getElementById('cf-notif-badge');
        if (!badge) return;
        if (this._unreadCount > 0) {
            badge.style.display = '';
            badge.textContent = this._unreadCount > 99 ? '99+' : String(this._unreadCount);
        } else {
            badge.style.display = 'none';
        }
    }

    static _renderList() {
        const list = document.getElementById('cf-notif-list');
        if (!list) return;

        if (!this._notifications.length) {
            list.innerHTML = `
                <div class="cf-notif-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>Nenhuma notificação</p>
                </div>
            `;
            return;
        }

        list.innerHTML = this._notifications.map(n => this._renderItem(n)).join('');

        // Bind clique em cada item
        list.querySelectorAll('.cf-notif-item').forEach(el => {
            el.addEventListener('click', () => {
                const id   = parseInt(el.dataset.id, 10);
                const link = el.dataset.link;
                this.markAsRead(id, link);
            });
        });
    }

    static _renderItem(n) {
        const lida   = parseInt(n.lida) === 1;
        const icone  = this._getIcon(n.tipo);
        const tempo  = this._timeAgo(n.criado_em);
        const link   = n.link ? `data-link="${n.link}"` : '';

        return `
            <div class="cf-notif-item ${lida ? 'lida' : 'nao-lida'}" data-id="${n.id}" ${link}>
                <div class="cf-notif-item-icon ${n.tipo}">
                    <i class="${icone}"></i>
                </div>
                <div class="cf-notif-item-body">
                    <p class="cf-notif-item-title">${this._esc(n.titulo)}</p>
                    <p class="cf-notif-item-msg">${this._esc(n.mensagem)}</p>
                    <span class="cf-notif-item-time">${tempo}</span>
                </div>
                ${!lida ? '<span class="cf-notif-unread-dot"></span>' : ''}
            </div>
        `;
    }

    static _getIcon(tipo) {
        const map = {
            item_enviado:      'fas fa-upload',
            item_aprovado:     'fas fa-check-circle',
            item_reprovado:    'fas fa-times-circle',
            checklist_concluido: 'fas fa-trophy',
            nova_mensagem:     'fas fa-comment-dots',
            vinculado_projeto:  'fas fa-folder-open',
            novo_projeto_criado: 'fas fa-file-alt',
        };
        return map[tipo] || 'fas fa-bell';
    }

    static _timeAgo(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr.replace(' ', 'T'));
        const diff = Math.floor((Date.now() - date.getTime()) / 1000);

        if (diff < 60)     return 'Agora mesmo';
        if (diff < 3600)   return `${Math.floor(diff / 60)} min atrás`;
        if (diff < 86400)  return `${Math.floor(diff / 3600)}h atrás`;
        if (diff < 604800) return `${Math.floor(diff / 86400)}d atrás`;
        return date.toLocaleDateString('pt-BR');
    }

    static _esc(str) {
        return (str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // -----------------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------------
    static async markAsRead(notifId, link) {
        if (!notifId) return;

        // Marca localmente imediatamente para UI responsiva
        const n = this._notifications.find(x => x.id === notifId);
        if (n && !n.lida) {
            n.lida = 1;
            this._unreadCount = Math.max(0, this._unreadCount - 1);
            this._updateBadge();
            this._renderList();
        }

        // Chama API em background
        try {
            const apiBase = this._getApiBase();
            await fetch(apiBase + 'notificacoes_marcar_lida.php', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notificacao_id: notifId })
            });
        } catch (_) {}

        // Navega para o link, se houver
        if (link) {
            const base = window.location.origin + '/ClientFlow/';
            window.location.href = base + link;
        }
    }

    static async markAllAsRead() {
        const btn = document.getElementById('cf-notif-mark-all');
        if (btn) btn.disabled = true;

        this._notifications.forEach(n => { n.lida = 1; });
        this._unreadCount = 0;
        this._updateBadge();
        this._renderList();

        try {
            const apiBase = this._getApiBase();
            await fetch(apiBase + 'notificacoes_marcar_lida.php', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ todas: 1 })
            });
        } catch (_) {}

        if (btn) btn.disabled = false;
    }
}

window.NotificationManager = NotificationManager;
