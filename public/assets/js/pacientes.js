/**
 * GonzaloLabs — pacientes.js
 * Lógica para todas las vistas del módulo pacientes.
 * Requiere: main.js (GL.apiPost, GL.debounce, GL.glToast)
 *           validacion.js (GL.validarCedulaEC)
 */

'use strict';

// ─────────────────────────────────────────────────────────────
// AUTOCOMPLETADO DE PACIENTE (usado en crear/editar orden y cotización)
// ─────────────────────────────────────────────────────────────

/**
 * Inicializa un buscador de pacientes con autocompletado.
 * @param {object} opts
 * @param {string} opts.inputId          - ID del input de búsqueda
 * @param {string} opts.sugId            - ID del contenedor de sugerencias
 * @param {string} opts.hiddenId         - ID del input hidden con id_paciente
 * @param {string} [opts.displayId]      - ID del div que muestra el paciente seleccionado
 * @param {Function} [opts.onSelect]     - Callback(paciente) al seleccionar
 * @param {Function} [opts.onClear]      - Callback al limpiar selección
 */
function initBuscarPaciente(opts) {
    const input   = document.getElementById(opts.inputId);
    const sug     = document.getElementById(opts.sugId);
    const hidden  = document.getElementById(opts.hiddenId);
    const display = opts.displayId ? document.getElementById(opts.displayId) : null;
    if (!input || !sug) return;

    const buscar = GL.debounce(async (q) => {
        if (q.length < 2) { sug.style.display = 'none'; return; }
        try {
            const res = await fetch('/pacientes/buscar?q=' + encodeURIComponent(q));
            const data = await res.json();
            sug.innerHTML = '';
            if (!data.data?.length) { sug.style.display = 'none'; return; }

            data.data.slice(0, 8).forEach(p => {
                const item = document.createElement('div');
                item.style.cssText = 'padding:.65rem 1rem;cursor:pointer;font-size:.845rem;border-bottom:1px solid #f1f5f9;transition:background .12s';
                item.innerHTML = `<strong>${p.nombre_completo}</strong> <span style="color:#94a3b8;font-size:.76rem">${p.cedula ?? ''}</span>`;
                item.onmouseenter = () => item.style.background = '#f8fafc';
                item.onmouseleave = () => item.style.background = '';
                item.onclick = () => seleccionar(p);
                sug.appendChild(item);
            });
            sug.style.display = 'block';
        } catch (_) { sug.style.display = 'none'; }
    }, 300);

    input.addEventListener('input', (e) => {
        if (!e.target.value.trim()) limpiar();
        buscar(e.target.value.trim());
    });

    // Cerrar al click fuera
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#' + opts.inputId) && !e.target.closest('#' + opts.sugId))
            sug.style.display = 'none';
    });

    function seleccionar(p) {
        if (hidden) hidden.value = p.id_paciente;
        input.value = p.nombre_completo;
        sug.style.display = 'none';

        if (display) {
            const inicial = (p.nombre_completo || '?')[0].toUpperCase();
            display.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <div style="width:34px;height:34px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;flex-shrink:0">${inicial}</div>
                    <div>
                        <div class="fw-bold" style="font-size:.9rem">${p.nombre_completo}</div>
                        <div style="font-size:.75rem;color:#64748b">${p.cedula ?? ''}</div>
                    </div>
                    <button type="button" onclick="GL.Pacientes.limpiarBuscador('${opts.inputId}')"
                        class="btn-gl btn-outline-gl btn-sm-gl ms-auto" title="Cambiar paciente">
                        <i class="bi bi-x"></i>
                    </button>
                </div>`;
            display.style.display = 'block';
        }

        if (typeof opts.onSelect === 'function') opts.onSelect(p);
    }

    function limpiar() {
        if (hidden)  hidden.value = '';
        input.value = '';
        sug.style.display = 'none';
        if (display) display.style.display = 'none';
        if (typeof opts.onClear === 'function') opts.onClear();
    }

    // Exponer limpiar por referencia de ID
    input._glLimpiar = limpiar;
}

// ─────────────────────────────────────────────────────────────
// ÍNDICE — eliminar paciente con confirmación AJAX
// ─────────────────────────────────────────────────────────────

async function eliminarPaciente(id, nombre) {
    if (!confirm(`¿Desactivar al paciente "${nombre}"?\nSus órdenes e historial se conservarán.`)) return;
    try {
        const res = await GL.apiPost('/pacientes/eliminar/' + id);
        if (res.success) {
            GL.glToast('Paciente desactivado correctamente', 'success');
            const fila = document.querySelector(`tr[data-paciente="${id}"]`);
            if (fila) {
                fila.style.transition = 'opacity .4s';
                fila.style.opacity = '0';
                setTimeout(() => fila.remove(), 400);
            } else {
                setTimeout(() => location.reload(), 600);
            }
        } else {
            GL.glToast(res.message || 'Error al desactivar', 'error');
        }
    } catch (e) {
        GL.glToast('Error de conexión', 'error');
    }
}

// ─────────────────────────────────────────────────────────────
// FORMULARIO CREAR — validación de cédula + preview + edad
// ─────────────────────────────────────────────────────────────

function initFormCrearPaciente() {
    const form = document.getElementById('formPaciente');
    if (!form) return;

    // Validación de cédula
    GL.conectarValidacionCedula('cedula', 'feedbackCedula', (cedula) => {
        // Verificar si ya existe
        GL.debounce(async () => {
            try {
                const res = await fetch('/pacientes/buscar?q=' + encodeURIComponent(cedula) + '&tipo=cedula');
                const data = await res.json();
                const fb = document.getElementById('feedbackCedula');
                if (data.data?.length > 0) {
                    fb.textContent = '⚠ Ya existe un paciente con esta cédula';
                    fb.style.color = '#f59e0b';
                }
            } catch (_) {}
        }, 500)();
    });

    // Preview del nombre
    const previewNombre = document.getElementById('previewNombre');
    ['nombres', 'apellidos'].forEach(campo => {
        document.querySelector(`[name=${campo}]`)?.addEventListener('input', actualizarPreview);
    });

    function actualizarPreview() {
        const n = document.querySelector('[name=nombres]')?.value.trim() || '';
        const a = document.querySelector('[name=apellidos]')?.value.trim() || '';
        if (previewNombre && (n || a)) previewNombre.textContent = `${n} ${a}`.trim();
    }

    // Cálculo de edad en tiempo real
    const fnInput = document.querySelector('[name=fecha_nacimiento]');
    const edadDisplay = document.getElementById('edadDisplay');
    fnInput?.addEventListener('input', () => {
        if (!edadDisplay) return;
        const dob = fnInput.value;
        if (!dob) { edadDisplay.value = ''; return; }
        const años = Math.floor((Date.now() - new Date(dob)) / 31_557_600_000);
        edadDisplay.value = años >= 0 ? `${años} años` : '';
    });
}

// ─────────────────────────────────────────────────────────────
// FORMULARIO EDITAR — solo cálculo de edad
// ─────────────────────────────────────────────────────────────

function initFormEditarPaciente() {
    const fn = document.querySelector('[name=fecha_nacimiento]');
    const display = document.getElementById('edadDisplay');
    if (!fn || !display) return;

    const calcular = () => {
        if (!fn.value) { display.value = ''; return; }
        const años = Math.floor((Date.now() - new Date(fn.value)) / 31_557_600_000);
        display.value = años >= 0 ? `${años} años` : '';
    };
    fn.addEventListener('input', calcular);
    calcular(); // ejecutar al cargar si ya tiene valor
}

// ─────────────────────────────────────────────────────────────
// HELPER — limpiar buscador por inputId (llamado desde HTML)
// ─────────────────────────────────────────────────────────────

function limpiarBuscador(inputId) {
    const input = document.getElementById(inputId);
    input?._glLimpiar?.();
}

// Auto-init según la página
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('formPaciente'))    initFormCrearPaciente();
    if (document.querySelector('[name=fecha_nacimiento]') &&
        !document.getElementById('formPaciente'))   initFormEditarPaciente();
});

// Exportar
window.GL = window.GL || {};
window.GL.Pacientes = { initBuscarPaciente, eliminarPaciente, limpiarBuscador };