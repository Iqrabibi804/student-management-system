/**
 * main.js v2 — Core UI: Dark Mode, Sidebar, Toast, Keyboard shortcuts
 */
'use strict';

/* ── Dark Mode ─────────────────────────────────────── */
const DarkMode = {
    init() {
        const saved = localStorage.getItem('sms-theme') || 'light';
        this.apply(saved);
        document.getElementById('darkModeToggle')?.addEventListener('click', () => this.toggle());
    },
    apply(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        const icon = document.getElementById('darkModeIcon');
        if (icon) icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        localStorage.setItem('sms-theme', theme);
    },
    toggle() {
        const cur = document.documentElement.getAttribute('data-theme') || 'light';
        this.apply(cur === 'dark' ? 'light' : 'dark');
    }
};

/* ── Sidebar ────────────────────────────────────────── */
const Sidebar = {
    el: null, main: null, overlay: null,

    init() {
        this.el      = document.getElementById('sidebar');
        this.main    = document.getElementById('mainContent');
        this.overlay = document.createElement('div');
        this.overlay.className = 'sidebar-overlay';
        document.body.appendChild(this.overlay);

        document.getElementById('sidebarToggle')?.addEventListener('click', () => this.toggle());
        this.overlay.addEventListener('click', () => this.close());

        // Restore desktop state
        if (window.innerWidth > 768 && localStorage.getItem('sms-sidebar') === 'collapsed') {
            this.collapse();
        }
    },

    isMobile: () => window.innerWidth <= 768,

    toggle() {
        if (this.isMobile()) {
            this.el.classList.contains('mobile-open') ? this.close() : this.openMobile();
        } else {
            this.el.classList.contains('collapsed') ? this.expand() : this.collapse();
        }
    },

    openMobile() {
        this.el.classList.add('mobile-open');
        this.overlay.classList.add('active');
    },

    close() {
        this.el.classList.remove('mobile-open');
        this.overlay.classList.remove('active');
    },

    collapse() {
        this.el.classList.add('collapsed');
        this.main?.classList.add('expanded');
        localStorage.setItem('sms-sidebar', 'collapsed');
    },

    expand() {
        this.el.classList.remove('collapsed');
        this.main?.classList.remove('expanded');
        localStorage.setItem('sms-sidebar', 'expanded');
    }
};

/* ── Toast ──────────────────────────────────────────── */
const Toast = {
    container: null,
    icons: { success:'fa-circle-check', error:'fa-circle-xmark', warning:'fa-triangle-exclamation', info:'fa-circle-info' },

    init() { this.container = document.getElementById('toastContainer'); },

    show(message, type = 'info', duration = 4200) {
        const t = document.createElement('div');
        t.className = `toast toast-${type}`;
        t.innerHTML = `
            <i class="fas ${this.icons[type] || this.icons.info} toast-icon"></i>
            <span class="toast-msg">${message}</span>
            <button class="toast-close" onclick="Toast.remove(this.parentElement)"><i class="fas fa-xmark"></i></button>`;
        this.container.appendChild(t);
        if (duration > 0) setTimeout(() => this.remove(t), duration);
        return t;
    },

    remove(el) {
        if (!el?.parentElement) return;
        el.classList.add('removing');
        setTimeout(() => el.remove(), 320);
    },

    success(m, d) { return this.show(m, 'success', d); },
    error(m, d)   { return this.show(m, 'error',   d); },
    warning(m, d) { return this.show(m, 'warning', d); },
    info(m, d)    { return this.show(m, 'info',    d); },
};

/* ── Admin Dropdown ─────────────────────────────────── */
function toggleAdminMenu() {
    document.getElementById('adminTrigger')?.classList.toggle('open');
    document.getElementById('adminDropdown')?.classList.toggle('open');
}

document.addEventListener('click', e => {
    if (!e.target.closest('#adminMenu')) {
        document.getElementById('adminTrigger')?.classList.remove('open');
        document.getElementById('adminDropdown')?.classList.remove('open');
    }
});

/* ── Keyboard Shortcuts ─────────────────────────────── */
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const el = document.getElementById('globalSearch');
        el?.focus(); el?.select();
    }
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
        document.body.style.overflow = '';
    }
});

/* ── Nav badge update ───────────────────────────────── */
async function updateNavCount() {
    try {
        const res  = await fetch('process.php?action=fetch&limit=1&page=1');
        const data = await res.json();
        const el   = document.getElementById('navStudentCount');
        if (el && data.total !== undefined) el.textContent = data.total;
    } catch {}
}

/* ── Animate counters ───────────────────────────────── */
function animateCounters() {
    document.querySelectorAll('.counter').forEach(el => {
        const target = parseInt(el.dataset.target || el.textContent, 10);
        if (isNaN(target) || target === 0) return;
        let n = 0;
        const step = Math.max(1, Math.ceil(target / 45));
        const timer = setInterval(() => {
            n = Math.min(n + step, target);
            el.textContent = n.toLocaleString();
            if (n >= target) clearInterval(timer);
        }, 25);
    });
}

/* ── INIT ────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    DarkMode.init();
    Sidebar.init();
    Toast.init();
    updateNavCount();
    animateCounters();
});
