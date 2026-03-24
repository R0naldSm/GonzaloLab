/**
 * GonzaloLabs — resultados.js
 * Lógica de carga manual, importación CSV y alertas de resultados clínicos.
 * Requiere: main.js
 */

'use strict';

// ─────────────────────────────────────────────────────────────
// EVALUACIÓN DE CRITICIDAD EN TIEMPO REAL
// Igual que la función inline de cargar_manual.php pero centralizada.
// ─────────────────────────────────────────────────────────────

/**
 * Evalúa si un valor es crítico/alto/bajo y colorea la fila.
 * @param {string} inputId   - ID del <input> del parámetro
 * @param {number|null} minN - Valor mínimo normal
 * @param {number|null} maxN - Valor máximo normal
 * @param {number|null} minC - Valor mínimo crítico
 * @param {number|null} maxC - Valor máximo crítico
 */
function evaluarCritico(inputId, minN, maxN, minC, maxC) {
    const inp = document.getElementById(inputId);
    const st  = document.getElementById('st_' + inputId);
    const row = document.getElementById('row_' + inputId);
    if (!inp) return;

    const val = parseFloat(inp.value);
    if (isNaN(val)) {
        if (st)  st.innerHTML = '';
        if (row) row.style.background = '';
        return;
    }

    let estado = 'normal';
    if (minC !== null && val < minC) estado = 'critico_bajo';
    else if (maxC !== null && val > maxC) estado = 'critico_alto';
    else if (minN !== null && val < minN) estado = 'bajo';
    else if (maxN !== null && val > maxN) estado = 'alto';

    const config = {
        normal:       { badge: 'badge-success', texto: '✓ Normal',      bg: '' },
        bajo:         { badge: 'badge-warning', texto: '↓ Bajo',         bg: '#fffbeb' },
        alto:         { badge: 'badge-warning', texto: '↑ Alto',         bg: '#fffbeb' },
        critico_bajo: { badge: 'badge-danger',  texto: '⚠ CRÍTICO ↓',    bg: '#fef9f9', anim: true },
        critico_alto: { badge: 'badge-danger',  texto: '⚠ CRÍTICO ↑',    bg: '#fef9f9', anim: true },
    };

    const c = config[estado];
    if (st)  st.innerHTML = `<span class="gl-badge ${c.badge}" style="${c.anim ? 'animation:pulse 1.5s infinite' : ''};font-size:.65rem">${c.texto}</span>`;
    if (row) row.style.background = c.bg;
}

// ─────────────────────────────────────────────────────────────
// PREVIEW DE ESTADO — vista validar.php (editar un resultado)
// ─────────────────────────────────────────────────────────────

function previewEstadoResultado(valor, minN, maxN, minC, maxC) {
    const badge = document.getElementById('previewBadge');
    if (!badge) return;

    const num = parseFloat(valor);
    if (isNaN(num)) { badge.innerHTML = ''; return; }

    let estado = 'normal';
    if (minC !== null && num < minC) estado = 'critico';
    else if (maxC !== null && num > maxC) estado = 'critico';
    else if (minN !== null && num < minN) estado = 'bajo';
    else if (maxN !== null && num > maxN) estado = 'alto';

    const map = {
        normal:  { cls: 'badge-success', lbl: 'Normal',    anim: false },
        bajo:    { cls: 'badge-warning', lbl: 'Bajo ↓',    anim: false },
        alto:    { cls: 'badge-warning', lbl: 'Alto ↑',    anim: false },
        critico: { cls: 'badge-danger',  lbl: '⚠ CRÍTICO', anim: true  },
    };
    const { cls, lbl, anim } = map[estado];
    badge.innerHTML = `<span class="gl-badge ${cls}" style="${anim ? 'animation:pulse 1.5s infinite' : ''}">${lbl}</span>`;
}

// ─────────────────────────────────────────────────────────────
// IMPORTACIÓN CSV — drag & drop + preview
// ─────────────────────────────────────────────────────────────

function initImportacionCSV() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const btnProcesar = document.getElementById('btnProcesar');
    const fileNameEl = document.getElementById('fileName');
    if (!dropZone || !fileInput) return;

    // Click en zona → abrir selector
    dropZone.addEventListener('click', () => fileInput.click());

    // Drag & drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--primary)';
        dropZone.style.background  = '#f0fdfe';
    });
    dropZone.addEventListener('dragleave', () => resetDropZone());
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        resetDropZone();
        const file = e.dataTransfer.files[0];
        if (!file) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        mostrarNombreArchivo(file.name);
    });

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (file) mostrarNombreArchivo(file.name);
    });

    function mostrarNombreArchivo(nombre) {
        if (!validarExtension(nombre)) {
            GL.glToast('Solo se aceptan archivos CSV o TXT', 'error');
            fileInput.value = '';
            return;
        }
        if (fileNameEl) {
            fileNameEl.textContent = '📄 ' + nombre;
            fileNameEl.style.display = 'block';
        }
        dropZone.style.borderColor = 'var(--primary)';
        dropZone.style.background  = '#f0fdfe';
        if (btnProcesar) btnProcesar.disabled = false;
    }

    function resetDropZone() {
        dropZone.style.borderColor = '#c7d2fe';
        dropZone.style.background  = '#f8faff';
    }

    function validarExtension(nombre) {
        return /\.(csv|txt)$/i.test(nombre);
    }
}

// ─────────────────────────────────────────────────────────────
// VALIDAR ORDEN COMPLETA (AJAX desde cargar_manual)
// ─────────────────────────────────────────────────────────────

async function validarOrden(idOrden) {
    if (!confirm('¿Validar todos los resultados de esta orden?')) return;
    try {
        const res = await GL.apiPost('/resultados/validar/' + idOrden);
        if (res.success) {
            GL.glToast('Resultados validados correctamente', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            GL.glToast(res.message || 'Error al validar', 'error');
        }
    } catch (_) {
        GL.glToast('Error de conexión', 'error');
    }
}

// ─────────────────────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    // Preview en tiempo real para editar resultado individual
    const nuevoValor = document.getElementById('nuevoValor');
    if (nuevoValor) {
        const minN = parseFloat(nuevoValor.dataset.minN) || null;
        const maxN = parseFloat(nuevoValor.dataset.maxN) || null;
        const minC = parseFloat(nuevoValor.dataset.minC) || null;
        const maxC = parseFloat(nuevoValor.dataset.maxC) || null;

        nuevoValor.addEventListener('input', () => {
            previewEstadoResultado(nuevoValor.value, minN, maxN, minC, maxC);
        });
        previewEstadoResultado(nuevoValor.value, minN, maxN, minC, maxC);
    }

    // Importación CSV
    if (document.getElementById('dropZone')) initImportacionCSV();
});

// Exportar
window.GL = window.GL || {};
window.GL.Resultados = {
    evaluarCritico,
    previewEstado: previewEstadoResultado,
    validarOrden,
    initImportacion: initImportacionCSV,
};