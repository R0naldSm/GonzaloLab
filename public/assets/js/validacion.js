/**
 * GonzaloLabs — validacion.js
 * Librería de validación reutilizable para todos los formularios.
 * Sin dependencias externas.
 */

'use strict';

// ─────────────────────────────────────────────────────────────
// CÉDULA ECUATORIANA — Algoritmo Módulo 10
// ─────────────────────────────────────────────────────────────

/**
 * Valida cédula ecuatoriana con algoritmo módulo 10.
 * @param {string} cedula
 * @returns {{ valida: boolean, mensaje: string }}
 */
function validarCedulaEC(cedula) {
    cedula = (cedula || '').trim();

    if (!cedula) return { valida: false, mensaje: 'La cédula es obligatoria' };
    if (!/^\d{10}$/.test(cedula)) return { valida: false, mensaje: 'Debe tener exactamente 10 dígitos' };

    const provincia = parseInt(cedula.substring(0, 2), 10);
    if (provincia < 1 || provincia > 24) {
        return { valida: false, mensaje: 'Código de provincia inválido (01–24)' };
    }

    const tercer = parseInt(cedula[2], 10);
    if (tercer >= 6) return { valida: false, mensaje: 'Tercer dígito inválido' };

    // Módulo 10
    const coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    let suma = 0;
    for (let i = 0; i < 9; i++) {
        let val = parseInt(cedula[i], 10) * coeficientes[i];
        if (val >= 10) val -= 9;
        suma += val;
    }
    const verificador = suma % 10 === 0 ? 0 : 10 - (suma % 10);

    if (verificador !== parseInt(cedula[9], 10)) {
        return { valida: false, mensaje: 'Cédula inválida (dígito verificador incorrecto)' };
    }

    return { valida: true, mensaje: '✓ Cédula válida' };
}

// ─────────────────────────────────────────────────────────────
// EMAIL
// ─────────────────────────────────────────────────────────────

function validarEmail(email) {
    if (!email) return { valida: false, mensaje: 'El email es obligatorio' };
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    return re.test(email)
        ? { valida: true,  mensaje: '' }
        : { valida: false, mensaje: 'Formato de email inválido' };
}

// ─────────────────────────────────────────────────────────────
// CONTRASEÑA — fortaleza
// ─────────────────────────────────────────────────────────────

/**
 * Evalúa la fortaleza de una contraseña.
 * @returns {{ score: 0-5, nivel: string, color: string, porcentaje: number }}
 */
function evaluarPassword(pass) {
    if (!pass) return { score: 0, nivel: '', color: '#f1f5f9', porcentaje: 0 };
    let score = 0;
    if (pass.length >= 8)                    score++;
    if (pass.length >= 12)                   score++;
    if (/[A-Z]/.test(pass))                  score++;
    if (/[0-9]/.test(pass))                  score++;
    if (/[^a-zA-Z0-9]/.test(pass))          score++;

    const niveles = [
        { nivel: 'Muy débil',  color: '#ef4444', porcentaje: 20 },
        { nivel: 'Débil',      color: '#f97316', porcentaje: 40 },
        { nivel: 'Regular',    color: '#f59e0b', porcentaje: 60 },
        { nivel: 'Buena',      color: '#84cc16', porcentaje: 80 },
        { nivel: 'Muy fuerte', color: '#10b981', porcentaje: 100 },
    ];
    return { score, ...niveles[Math.min(score, 4)] };
}

/**
 * Conecta un input de contraseña con una barra de fortaleza visual.
 * @param {string} inputId      - ID del <input type="password">
 * @param {string} barraId      - ID del <div> que actúa de barra
 * @param {string} textoId      - ID del <span> para texto del nivel
 */
function conectarBarraPassword(inputId, barraId, textoId) {
    const inp = document.getElementById(inputId);
    const bar = document.getElementById(barraId);
    const txt = document.getElementById(textoId);
    if (!inp) return;

    inp.addEventListener('input', () => {
        const { color, porcentaje, nivel } = evaluarPassword(inp.value);
        if (bar) { bar.style.width = porcentaje + '%'; bar.style.background = color; }
        if (txt) { txt.textContent = nivel; txt.style.color = color; }
    });
}

// ─────────────────────────────────────────────────────────────
// CAMPOS REQUERIDOS — feedback visual en tiempo real
// ─────────────────────────────────────────────────────────────

/**
 * Activa validación visual HTML5 en un <form>.
 * Muestra estilos de error/éxito al salir de cada campo (blur).
 * @param {string|HTMLFormElement} formSelector
 */
function initValidacionFormulario(formSelector) {
    const form = typeof formSelector === 'string'
        ? document.querySelector(formSelector)
        : formSelector;
    if (!form) return;

    form.querySelectorAll('input, select, textarea').forEach(el => {
        el.addEventListener('blur', () => marcarCampo(el));
        el.addEventListener('input', () => {
            if (el.classList.contains('gl-input--error')) marcarCampo(el);
        });
    });

    form.addEventListener('submit', (e) => {
        let valido = true;
        form.querySelectorAll('[required]').forEach(el => {
            if (!marcarCampo(el)) valido = false;
        });
        if (!valido) {
            e.preventDefault();
            const primer = form.querySelector('.gl-input--error');
            primer?.focus();
            primer?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
}

function marcarCampo(el) {
    const ok = el.checkValidity() && el.value.trim() !== '' || !el.required;
    el.classList.toggle('gl-input--error',  !ok);
    el.classList.toggle('gl-input--valid',   ok && el.value.trim() !== '');
    return ok;
}

// ─────────────────────────────────────────────────────────────
// CÉDULA — widget completo con feedback inline
// ─────────────────────────────────────────────────────────────

/**
 * Conecta un input de cédula con un elemento de feedback.
 * @param {string} inputId
 * @param {string} feedbackId  - Elemento donde se muestra el mensaje
 * @param {Function} [onValida] - Callback cuando la cédula es válida
 */
function conectarValidacionCedula(inputId, feedbackId, onValida) {
    const inp = document.getElementById(inputId);
    const fb  = document.getElementById(feedbackId);
    if (!inp) return;

    const validar = GL.debounce((val) => {
        // Sólo dígitos
        inp.value = val.replace(/\D/g, '').slice(0, 10);
        const { valida, mensaje } = validarCedulaEC(inp.value);

        if (!fb) return;
        fb.textContent = inp.value.length > 0 ? mensaje : '';
        fb.style.color  = valida ? '#10b981' : '#ef4444';

        if (valida && typeof onValida === 'function') onValida(inp.value);
    }, 250);

    inp.addEventListener('input', (e) => validar(e.target.value));
}

// ─────────────────────────────────────────────────────────────
// TOGGLE VISIBILIDAD CONTRASEÑA
// ─────────────────────────────────────────────────────────────

/**
 * Alterna type="password" ↔ type="text" en un input.
 * @param {string} inputId
 * @param {string} [iconId]  - Elemento <i> con clase bi-eye / bi-eye-slash
 */
function togglePassword(inputId, iconId) {
    const inp = document.getElementById(inputId);
    if (!inp) return;
    const es = inp.type === 'password';
    inp.type = es ? 'text' : 'password';
    if (iconId) {
        const ico = document.getElementById(iconId);
        if (ico) ico.className = es ? 'bi bi-eye-slash' : 'bi bi-eye';
    }
}

// ─────────────────────────────────────────────────────────────
// COMPARAR CONTRASEÑAS
// ─────────────────────────────────────────────────────────────

/**
 * Conecta dos inputs de contraseña y muestra si coinciden.
 * @param {string} pass1Id
 * @param {string} pass2Id
 * @param {string} feedbackId
 */
function conectarConfirmPassword(pass1Id, pass2Id, feedbackId) {
    const p2 = document.getElementById(pass2Id);
    const fb = document.getElementById(feedbackId);
    if (!p2 || !fb) return;

    p2.addEventListener('input', () => {
        const p1val = document.getElementById(pass1Id)?.value || '';
        const p2val = p2.value;
        if (!p2val) { fb.textContent = ''; return; }
        const ok = p1val === p2val;
        fb.textContent = ok ? '✓ Las contraseñas coinciden' : '✗ No coinciden';
        fb.style.color = ok ? '#10b981' : '#ef4444';
    });
}

// Exportar
window.GL = window.GL || {};
Object.assign(window.GL, {
    validarCedulaEC,
    validarEmail,
    evaluarPassword,
    conectarBarraPassword,
    initValidacionFormulario,
    conectarValidacionCedula,
    togglePassword,
    conectarConfirmPassword,
});