/**
 * GonzaloLabs — cotizacion.js
 * Lógica del módulo cotizaciones: crear, calcular, filtrar, eliminar.
 * Requiere: main.js, pacientes.js
 */

'use strict';

// Estado en memoria de la cotización en curso
let _cotExamenes = {};

// ─────────────────────────────────────────────────────────────
// SELECCIÓN DE EXÁMENES
// ─────────────────────────────────────────────────────────────

function toggleExamenCot(checkbox, id, nombre, precio) {
    precio = parseFloat(precio) || 0;
    const row = checkbox.closest('.examen-row');

    if (checkbox.checked) {
        _cotExamenes[id] = { id_examen: parseInt(id), nombre, precio, cantidad: 1 };
        if (row) { row.style.borderColor = 'var(--primary)'; row.style.background = '#f0fdfe'; }
    } else {
        delete _cotExamenes[id];
        if (row) { row.style.borderColor = '#f1f5f9'; row.style.background = '#fff'; }
    }

    renderSeleccionados();
}

function renderSeleccionados() {
    const lista  = document.getElementById('listaSeleccionados');
    const vacio  = document.getElementById('vacioMsg');
    const btnGrd = document.getElementById('btnGuardar');
    const btnMsg = document.getElementById('btnMsg');
    if (!lista) return;

    const keys = Object.keys(_cotExamenes);
    if (vacio) vacio.style.display = keys.length ? 'none' : 'block';

    // Eliminar items anteriores
    lista.querySelectorAll('.sel-item').forEach(e => e.remove());

    keys.forEach(id => {
        const ex = _cotExamenes[id];
        const div = document.createElement('div');
        div.className = 'sel-item d-flex align-items-center gap-2 mb-1 p-2 rounded';
        div.style.cssText = 'background:#f0fdfe;font-size:.81rem';
        div.innerHTML = `
            <span class="flex-fill fw-semibold" style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${ex.nombre}</span>
            <span style="color:var(--primary);white-space:nowrap;font-weight:700">$${(ex.precio * ex.cantidad).toFixed(2)}</span>
            <button type="button" onclick="GL.Cotizacion.quitar(${id})"
                style="background:none;border:none;color:#ef4444;cursor:pointer;padding:0;line-height:1;flex-shrink:0">
                <i class="bi bi-x-circle"></i>
            </button>`;
        lista.appendChild(div);
    });

    recalcular();
    actualizarJSON();

    if (btnGrd) btnGrd.disabled = keys.length === 0;
    if (btnMsg) btnMsg.style.display = keys.length === 0 ? 'block' : 'none';
}

function quitarExamenCot(id) {
    delete _cotExamenes[id];
    const cb = document.getElementById('ex_' + id);
    if (cb) {
        cb.checked = false;
        const row = cb.closest('.examen-row');
        if (row) { row.style.borderColor = '#f1f5f9'; row.style.background = '#fff'; }
    }
    renderSeleccionados();
}

// ─────────────────────────────────────────────────────────────
// CÁLCULO DE TOTALES
// ─────────────────────────────────────────────────────────────

function recalcular() {
    const subtotalEl  = document.getElementById('subtotal');
    const descuentoEl = document.getElementById('descuento');
    const descDispEl  = document.getElementById('descuentoDisplay');
    const totalEl     = document.getElementById('total');

    let subtotal  = Object.values(_cotExamenes)
                          .reduce((sum, ex) => sum + ex.precio * ex.cantidad, 0);
    let descuento = parseFloat(descuentoEl?.value) || 0;

    if (descuento > subtotal) {
        descuento = subtotal;
        if (descuentoEl) descuentoEl.value = descuento.toFixed(2);
    }

    if (subtotalEl)  subtotalEl.textContent  = subtotal.toFixed(2);
    if (descDispEl)  descDispEl.textContent  = descuento.toFixed(2);
    if (totalEl)     totalEl.textContent     = Math.max(0, subtotal - descuento).toFixed(2);
}

function actualizarJSON() {
    const hidden = document.getElementById('examenesJson');
    if (hidden) hidden.value = JSON.stringify(Object.values(_cotExamenes));
}

// ─────────────────────────────────────────────────────────────
// FILTRAR CATÁLOGO
// ─────────────────────────────────────────────────────────────

function filtrarExamenesCot(q) {
    q = (q || '').toLowerCase().trim();
    document.querySelectorAll('.examen-row').forEach(row => {
        const ok = !q
            || (row.dataset.nombre || '').toLowerCase().includes(q)
            || (row.dataset.codigo || '').toLowerCase().includes(q);
        row.style.display = ok ? '' : 'none';
    });

    // Ocultar categoría si todos sus hijos están ocultos
    document.querySelectorAll('.cat-group').forEach(g => {
        const alguno = [...g.querySelectorAll('.examen-row')].some(r => r.style.display !== 'none');
        g.style.display = alguno ? '' : 'none';
    });
}

function toggleCategoriaCot(header) {
    const items = header.nextElementSibling;
    const icon  = header.querySelector('.bi-chevron-down, .bi-chevron-up');
    if (!items) return;
    const oculto = items.style.display === 'none';
    items.style.display = oculto ? '' : 'none';
    if (icon) icon.className = oculto
        ? 'bi bi-chevron-down ms-auto'
        : 'bi bi-chevron-up ms-auto';
}

// ─────────────────────────────────────────────────────────────
// ELIMINAR COTIZACIÓN (index)
// ─────────────────────────────────────────────────────────────

async function eliminarCotizacion(id, numero) {
    if (!confirm(`¿Eliminar la cotización ${numero}?\nEsta acción no se puede deshacer.`)) return;
    try {
        const res = await GL.apiPost('/cotizaciones/eliminar/' + id);
        if (res.success) {
            GL.glToast('Cotización eliminada', 'success');
            const fila = document.querySelector(`tr[data-cot="${id}"]`);
            if (fila) {
                fila.style.transition = 'opacity .35s';
                fila.style.opacity = '0';
                setTimeout(() => fila.remove(), 350);
            } else {
                setTimeout(() => location.reload(), 500);
            }
        } else {
            GL.glToast(res.message || 'Error al eliminar', 'error');
        }
    } catch (_) {
        GL.glToast('Error de conexión', 'error');
    }
}

// ─────────────────────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCotizacion');
    if (!form) return;

    // Descuento → recalcular al cambiar
    document.getElementById('descuento')
        ?.addEventListener('input', recalcular);

    // Filtro de búsqueda en catálogo
    document.getElementById('buscarExamenCot')
        ?.addEventListener('input', (e) => filtrarExamenesCot(e.target.value));

    // Autocompletado de paciente
    if (document.getElementById('buscarPaciente')) {
        GL.Pacientes.initBuscarPaciente({
            inputId:  'buscarPaciente',
            sugId:    'sugerencias',
            hiddenId: 'idPaciente',
        });
    }
});

// Exportar
window.GL = window.GL || {};
window.GL.Cotizacion = {
    toggle:      toggleExamenCot,
    quitar:      quitarExamenCot,
    filtrar:     filtrarExamenesCot,
    toggleCat:   toggleCategoriaCot,
    recalcular,
    eliminar:    eliminarCotizacion,
};