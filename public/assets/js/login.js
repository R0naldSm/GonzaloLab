// ============================================
// GonzaloLabs - LOGIN FINAL PRO (MEZCLA)
// Compatible con tu login.php actual
// ============================================

'use strict';

document.addEventListener('DOMContentLoaded', function () {

    const loginForm = document.getElementById('loginForm');
    const btnLogin = document.getElementById('btnLogin');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    // ─────────────────────────────────────────
    // TOGGLE PASSWORD (compatible con tu HTML)
    // ─────────────────────────────────────────
    window.togglePassword = function () {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;

        const eyeIcon = document.querySelector('.eye-icon');
        if (!eyeIcon) return;

        eyeIcon.innerHTML = type === 'text'
            ? `<path d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21"/>`
            : `<path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
               <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10z"/>`;
    };

    // ─────────────────────────────────────────
    // VALIDACIONES MEJORADAS
    // ─────────────────────────────────────────

    usernameInput?.addEventListener('input', () => validateUsername(usernameInput));
    passwordInput?.addEventListener('input', () => validatePassword(passwordInput));

    function validateUsername(input) {
        const value = input.value.trim();

        if (!value) {
            setInputError(input, '');
            return false;
        }

        if (value.length < 3) {
            setInputError(input, 'Mínimo 3 caracteres');
            return false;
        }

        // Anti SQL Injection básico
        const patterns = [
            /(\bOR\b|\bAND\b)\s+\d+=\d+/i,
            /(--|#|\/\*)/,
            /(\bSELECT\b|\bDROP\b|\bINSERT\b|\bDELETE\b|\bUNION\b)/i
        ];

        for (let p of patterns) {
            if (p.test(value)) {
                setInputError(input, 'Entrada no válida');
                return false;
            }
        }

        clearInputError(input);
        return true;
    }

    function validatePassword(input) {
        const value = input.value;

        if (!value) {
            setInputError(input, '');
            return false;
        }

        if (value.length < 4) {
            setInputError(input, 'Contraseña muy corta');
            return false;
        }

        clearInputError(input);
        return true;
    }

    // ─────────────────────────────────────────
    // UI ERRORES
    // ─────────────────────────────────────────

    function setInputError(input, message) {
        const wrapper = input.closest('.input-wrapper');
        const formGroup = input.closest('.form-group');

        input.style.borderColor = 'var(--error)';

        const old = formGroup.querySelector('.error-message');
        if (old) old.remove();

        if (message) {
            const div = document.createElement('div');
            div.className = 'error-message';
            div.style.cssText = 'color:var(--error);font-size:0.75rem;margin-top:4px;';
            div.textContent = message;
            wrapper.after(div);
        }
    }

    function clearInputError(input) {
        const formGroup = input.closest('.form-group');
        input.style.borderColor = '';

        const error = formGroup.querySelector('.error-message');
        if (error) error.remove();
    }

    // ─────────────────────────────────────────
    // SUBMIT CONTROLADO (MEJORADO)
    // ─────────────────────────────────────────

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {

            const validUser = validateUsername(usernameInput);
            const validPass = validatePassword(passwordInput);

            if (!validUser || !validPass) {
                e.preventDefault();

                (!validUser ? usernameInput : passwordInput).focus();
                return;
            }

            // Estado loading (mejorado)
            btnLogin.disabled = true;

            const span = btnLogin.querySelector('.btn-text');
            if (span) span.textContent = 'Verificando...';

            btnLogin.insertAdjacentHTML('afterbegin', `
                <svg class="animate-spin" width="20" height="20" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"/>
                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4"/>
                </svg>
            `);
        });
    }

    // ─────────────────────────────────────────
    // ALERTAS AUTO-CIERRE
    // ─────────────────────────────────────────

    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // ─────────────────────────────────────────
    // CAPS LOCK DETECCIÓN
    // ─────────────────────────────────────────

    passwordInput?.addEventListener('keyup', function (e) {
        if (e.getModifierState('CapsLock')) {
            setInputError(this, 'Mayúsculas activadas');
        } else {
            clearInputError(this);
        }
    });

    // ─────────────────────────────────────────
    // SPINNER ANIMACIÓN
    // ─────────────────────────────────────────

    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }
    `;
    document.head.appendChild(style);

});

// ─────────────────────────────────────────
// EXTRA SEGURIDAD (opcional)
// ─────────────────────────────────────────

function constantTimeCompare(a, b) {
    if (a.length !== b.length) return false;
    let res = 0;
    for (let i = 0; i < a.length; i++) {
        res |= a.charCodeAt(i) ^ b.charCodeAt(i);
    }
    return res === 0;
}