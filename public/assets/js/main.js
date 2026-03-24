/**
 * GonzaloLabs — main.js
 * Utilidades globales cargadas en todas las páginas internas.
 * Depende de: Bootstrap 5 (ya cargado en footer)
 */

'use strict';

// ─────────────────────────────────────────────────────────────
// UTILIDADES GENERALES
// ─────────────────────────────────────────────────────────────

/**
 * Debounce: retrasa la ejecución de fn hasta que paren de llamarla.
 * @param {Function} fn
 * @param {number} ms
 */
function debounce(fn, ms = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), ms);
    };
}

/**
 * Petición AJAX con CSRF automático.
 * @param {string} url
 * @param {object} data  - payload JSON
 * @param {string} method
 * @returns {Promise<object>}
 */
async function apiPost(url, data = {}, method = 'POST') {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
              || window.GL_CSRF
              || '';
    const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ...data, csrf_token: csrf }),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
}

/**
 * Muestra un toast/snackbar temporal en la esquina inferior derecha.
 * @param {string} mensaje
 * @param {'success'|'error'|'warning'|'info'} tipo
 */
function glToast(mensaje, tipo = 'success') {
    const colors = {
        success: { bg: '#f0fdf4', border: '#86efac', text: '#065f46', icon: '✓' },
        error:   { bg: '#fef2f2', border: '#fca5a5', text: '#7f1d1d', icon: '✗' },
        warning: { bg: '#fffbeb', border: '#fde68a', text: '#78350f', icon: '⚠' },
        info:    { bg: '#eff6ff', border: '#93c5fd', text: '#1e3a8a', icon: 'ℹ' },
    };
    const c = colors[tipo] || colors.info;

    const el = document.createElement('div');
    el.style.cssText = `
        position:fixed; bottom:1.5rem; right:1.5rem; z-index:9999;
        background:${c.bg}; border:1.5px solid ${c.border}; color:${c.text};
        border-radius:.75rem; padding:.75rem 1.25rem; font-size:.85rem;
        font-weight:600; box-shadow:0 8px 24px rgba(0,0,0,.12);
        display:flex; align-items:center; gap:.6rem; max-width:360px;
        animation:glSlideIn .25s ease; font-family:inherit;
    `;
    el.innerHTML = `<span style="font-size:1rem">${c.icon}</span><span>${mensaje}</span>`;
    document.body.appendChild(el);

    setTimeout(() => {
        el.style.transition = 'opacity .4s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 3500);
}

// Inyectar keyframe si no existe
if (!document.getElementById('gl-toast-style')) {
    const s = document.createElement('style');
    s.id = 'gl-toast-style';
    s.textContent = `@keyframes glSlideIn{from{transform:translateY(1rem);opacity:0}to{transform:translateY(0);opacity:1}}`;
    document.head.appendChild(s);
}

// ─────────────────────────────────────────────────────────────
// SIDEBAR — toggle móvil
// ─────────────────────────────────────────────────────────────
(function initSidebar() {
    const btn = document.getElementById('btnSidebarToggle');
    const sb  = document.getElementById('sidebar');
    if (!btn || !sb) return;

    btn.addEventListener('click', () => sb.classList.toggle('show'));

    // Cerrar al hacer click fuera
    document.addEventListener('click', (e) => {
        if (sb.classList.contains('show') && !sb.contains(e.target) && e.target !== btn)
            sb.classList.remove('show');
    });
})();

// ─────────────────────────────────────────────────────────────
// FLASH MESSAGES — auto-cerrar
// ─────────────────────────────────────────────────────────────
(function initFlash() {
    const flash = document.querySelector('#topbar .gl-alert, .gl-flash');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity .5s';
            flash.style.opacity = '0';
            setTimeout(() => flash.remove(), 500);
        }, 4500);
    }
})();

// ─────────────────────────────────────────────────────────────
// TOOLTIPS Bootstrap — inicializar automático
// ─────────────────────────────────────────────────────────────
(function initTooltips() {
    if (typeof bootstrap === 'undefined') return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });
})();

// ─────────────────────────────────────────────────────────────
// CONFIRMACIÓN GLOBAL — botones data-confirm
// ─────────────────────────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    const msg = btn.dataset.confirm || '¿Está seguro?';
    if (!confirm(msg)) e.preventDefault();
});

// ─────────────────────────────────────────────────────────────
// ALERTAS CRÍTICAS — polling cada 60s si hay contador activo
// ─────────────────────────────────────────────────────────────
(function pollAlertas() {
    const badge = document.getElementById('alertasCriticasBadge');
    if (!badge) return;

    setInterval(async () => {
        try {
            const res = await fetch('/resultados/alertas-count');
            if (!res.ok) return;
            const { count } = await res.json();
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        } catch (_) { /* silencioso */ }
    }, 60_000);
})();

// ─────────────────────────────────────────────────────────────
// MODAL HELPER — abrir/cerrar modales de la UI custom (no Bootstrap)
// ─────────────────────────────────────────────────────────────
const GLModal = {
    open(id) {
        const m = document.getElementById(id);
        if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    },
    close(id) {
        const m = document.getElementById(id);
        if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
    },
};

// Cerrar modales al hacer click en el backdrop
document.addEventListener('click', (e) => {
    if (e.target.matches('[data-modal-close]'))
        GLModal.close(e.target.dataset.modalClose);
});

// ─────────────────────────────────────────────────────────────
// COPIAR AL PORTAPAPELES — botones data-copy
// ─────────────────────────────────────────────────────────────
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;
    const texto = document.getElementById(btn.dataset.copy)?.textContent
                ?? btn.dataset.copy;
    try {
        await navigator.clipboard.writeText(texto.trim());
        glToast('Copiado al portapapeles', 'success');
    } catch (_) {
        glToast('No se pudo copiar', 'error');
    }
});

// Exportar globalmente
window.GL = { debounce, apiPost, glToast, GLModal };