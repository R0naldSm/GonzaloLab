/**
 * GonzaloLabs — ordenes.js
 * Lógica de las vistas: ordenes/crear, ordenes/index, ordenes/validar.
 * Requiere: main.js, pacientes.js
 */

'use strict';

// ─────────────────────────────────────────────────────────────
// CREAR ORDEN — selección de exámenes, resumen sticky
// ─────────────────────────────────────────────────────────────

let _ordenExamenes = {};  // { id: { id, nombre, precio } }

function toggleExamenOrden(cb, id, nombre, precio) {
    precio = parseFloat(precio) || 0;
    const label = cb.closest('label');

    if (cb.checked) {
        _ordenExamenes[id] = { id: parseInt(id), nombre, precio };
        if (label) { label.style.background = '#f0fdfe'; label.style.borderColor = 'var(--primary)'; }
    } else {
        delete _ordenExamenes[id];
        if (label) { label.style.background = '#fff'; label.style.borderColor = '#f1f5f9'; }
    }

    actualizarResumenOrden();
    validarFormularioOrden();
}

function actualizarResumenOrden() {
    const lista  = document.getElementById('listaExamenes');
    const vacio  = document.getElementById('vacioMsgO');
    const cnt    = document.getElementById('cntExamenes');
    const total  = document.getElementById('totalOrden');
    const nota   = document.getElementById('notaAyuno');
    const json   = document.getElementById('examenesJson');
    if (!lista) return;

    const keys = Object.keys(_ordenExamenes);
    if (vacio) vacio.style.display = keys.length ? 'none' : 'block';
    lista.querySelectorAll('.ex-sel').forEach(e => e.remove());

    let suma = 0;
    let hayAyuno = false;

    keys.forEach(id => {
        const ex = _ordenExamenes[id];
        suma += ex.precio;

        // Detectar ayuno: checkbox con data-ayuno o texto del label
        const cb = document.querySelector(`input[data-examen="${id}"]`);
        if (cb?.dataset.ayuno === '1') hayAyuno = true;

        const div = document.createElement('div');
        div.className = 'ex-sel d-flex align-items-center gap-2 mb-1 px-2 py-1 rounded';
        div.style.cssText = 'background:#f0fdfe;font-size:.81rem';
        div.innerHTML = `
            <span class="flex-fill fw-semibold" style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${ex.nombre}</span>
            <span style="color:var(--primary);white-space:nowrap;font-weight:700">$${ex.precio.toFixed(2)}</span>
            <button type="button" onclick="GL.Ordenes.quitar(${id})"
                style="background:none;border:none;color:#ef4444;cursor:pointer;padding:0;line-height:1;font-size:.85rem">
                <i class="bi bi-x"></i>
            </button>`;
        lista.appendChild(div);
    });

    if (cnt)   cnt.textContent   = keys.length;
    if (total) total.textContent = suma.toFixed(2);
    if (nota)  nota.style.display = hayAyuno ? 'block' : 'none';
    if (json)  json.value = JSON.stringify(keys.map(Number));
}

function quitarExamenOrden(id) {
    delete _ordenExamenes[id];
    const cb = document.querySelector(`input[data-examen="${id}"]`);
    if (cb) {
        cb.checked = false;
        const label = cb.closest('label');
        if (label) { label.style.background = '#fff'; label.style.borderColor = '#f1f5f9'; }
    }
    actualizarResumenOrden();
    validarFormularioOrden();
}

function validarFormularioOrden() {
    const idPac  = document.getElementById('idPaciente')?.value
                ?? document.querySelector('[name=id_paciente]')?.value;
    const tieneP = !!idPac;
    const tieneE = Object.keys(_ordenExamenes).length > 0;
    const btn    = document.getElementById('btnCrear');
    const msg    = document.getElementById('msgBtn');

    if (btn) btn.disabled = !(tieneP && tieneE);
    if (msg) {
        if (!tieneP && !tieneE) msg.textContent = 'Seleccione paciente y al menos un examen';
        else if (!tieneP)       msg.textContent = 'Seleccione un paciente';
        else if (!tieneE)       msg.textContent = 'Seleccione al menos un examen';
        else                    msg.textContent = '';
    }
}

function filtrarCatalogoOrden(q) {
    q = (q || '').toLowerCase().trim();
    document.querySelectorAll('.ex-row').forEach(row => {
        const ok = !q
            || (row.dataset.nombre || '').toLowerCase().includes(q)
            || (row.dataset.codigo || '').toLowerCase().includes(q);
        row.style.display = ok ? '' : 'none';
    });
    document.querySelectorAll('.cat-group').forEach(g => {
        const visible = [...g.querySelectorAll('.ex-row')].some(r => r.style.display !== 'none');
        g.style.display = visible ? '' : 'none';
    });
}

function toggleCategoriaOrden(header) {
    const items = header.nextElementSibling;
    const icon  = header.querySelector('i');
    if (!items) return;
    const oculto = items.style.display === 'none';
    items.style.display = oculto ? '' : 'none';
    if (icon) icon.style.transform = oculto ? '' : 'rotate(180deg)';
}

// ─────────────────────────────────────────────────────────────
// ÍNDICE — validar / publicar (AJAX)
// ─────────────────────────────────────────────────────────────

async function accionOrden(id, accion, numero) {
    const labels = {
        validar:  { confirm: `¿Validar la orden ${numero}?`, ok: 'Orden validada', badgeClass: 'badge-info',    texto: 'Validada' },
        publicar: { confirm: `¿Publicar resultados de la orden ${numero}?\nEl paciente podrá acceder vía QR.`, ok: 'Orden publicada', badgeClass: 'badge-success', texto: 'Publicada' },
    };
    const lab = labels[accion];
    if (!lab) return;
    if (!confirm(lab.confirm)) return;

    const btn = document.querySelector(`[data-accion="${accion}"][data-orden="${id}"]`);
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i>'; }

    try {
        const res = await GL.apiPost(`/ordenes/${accion}/${id}`);
        if (res.success) {
            GL.glToast(lab.ok, 'success');
            // Actualizar badge de estado en la fila
            const badge = document.querySelector(`[data-estado-orden="${id}"]`);
            if (badge) {
                badge.className = `gl-badge ${lab.badgeClass}`;
                badge.textContent = lab.texto;
            }
            // Ocultar botones de acción ya realizados
            const fila = document.querySelector(`tr[data-orden="${id}"]`);
            fila?.querySelectorAll('[data-accion]').forEach(b => {
                if (b.dataset.accion === accion) b.remove();
            });
        } else {
            GL.glToast(res.message || 'Error', 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = `<i class="bi bi-${accion === 'validar' ? 'check2-circle' : 'send'}"></i>`; }
        }
    } catch (_) {
        GL.glToast('Error de conexión', 'error');
        if (btn) btn.disabled = false;
    }
}

// ─────────────────────────────────────────────────────────────
// VISTA VALIDAR — imprimir resultados
// ─────────────────────────────────────────────────────────────

function imprimirResultados() {
    window.print();
}

// ─────────────────────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    // Crear orden
    if (document.getElementById('formOrden')) {
        // Filtro del catálogo
        document.getElementById('buscarExamenOrden')
            ?.addEventListener('input', (e) => filtrarCatalogoOrden(e.target.value));

        // Autocompletado paciente (si no hay paciente pre-cargado)
        const hiddenPac = document.getElementById('idPaciente');
        if (hiddenPac && !hiddenPac.value && document.getElementById('buscarPaciente')) {
            GL.Pacientes.initBuscarPaciente({
                inputId:  'buscarPaciente',
                sugId:    'sugerencias',
                hiddenId: 'idPaciente',
                displayId:'pacienteSeleccionado',
                onSelect: () => validarFormularioOrden(),
                onClear:  () => validarFormularioOrden(),
            });
        }

        validarFormularioOrden();
    }
});

// Exportar
window.GL = window.GL || {};
window.GL.Ordenes = {
    toggle:       toggleExamenOrden,
    quitar:       quitarExamenOrden,
    filtrar:      filtrarCatalogoOrden,
    toggleCat:    toggleCategoriaOrden,
    accion:       accionOrden,
    imprimir:     imprimirResultados,
    validarForm:  validarFormularioOrden,
};