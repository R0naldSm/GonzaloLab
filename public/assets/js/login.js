// ============================================
// LOGIN JAVASCRIPT - GonzaloLabs
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const btnLogin = document.getElementById('btnLogin');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    
    // Toggle password visibility
    window.togglePassword = function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        const eyeIcon = document.querySelector('.eye-icon');
        if (type === 'text') {
            eyeIcon.innerHTML = `
                <path d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
            `;
        } else {
            eyeIcon.innerHTML = `
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
            `;
        }
    };
    
    // Validación en tiempo real
    usernameInput.addEventListener('input', function() {
        validateUsername(this);
    });
    
    passwordInput.addEventListener('input', function() {
        validatePassword(this);
    });
    
    // Validar username
    function validateUsername(input) {
        const value = input.value.trim();
        
        if (value.length === 0) {
            setInputError(input, '');
            return false;
        }
        
        if (value.length < 3) {
            setInputError(input, 'Mínimo 3 caracteres');
            return false;
        }
        
        // Detectar posibles inyecciones SQL básicas
        const sqlPatterns = [
            /(\bOR\b|\bAND\b)\s+\d+\s*=\s*\d+/i,
            /(--|#|\/\*)/,
            /(\bUNION\b|\bSELECT\b|\bINSERT\b|\bDELETE\b|\bDROP\b)/i
        ];
        
        for (let pattern of sqlPatterns) {
            if (pattern.test(value)) {
                setInputError(input, 'Caracteres no permitidos');
                return false;
            }
        }
        
        clearInputError(input);
        return true;
    }
    
    // Validar password
    function validatePassword(input) {
        const value = input.value;
        
        if (value.length === 0) {
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
    
    // Mostrar error en input
    function setInputError(input, message) {
        const wrapper = input.closest('.input-wrapper');
        const formGroup = input.closest('.form-group');
        
        input.style.borderColor = 'var(--error)';
        
        // Remover mensaje de error previo
        const existingError = formGroup.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Agregar nuevo mensaje si existe
        if (message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.cssText = `
                color: var(--error);
                font-size: 0.75rem;
                margin-top: 0.25rem;
            `;
            errorDiv.textContent = message;
            wrapper.after(errorDiv);
        }
    }
    
    // Limpiar error en input
    function clearInputError(input) {
        const formGroup = input.closest('.form-group');
        input.style.borderColor = '';
        
        const errorMessage = formGroup.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
    }
    
    // Submit del formulario
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Validar antes de enviar
            const isUsernameValid = validateUsername(usernameInput);
            const isPasswordValid = validatePassword(passwordInput);
            
            if (!isUsernameValid || !isPasswordValid) {
                e.preventDefault();
                
                if (!isUsernameValid) {
                    usernameInput.focus();
                } else {
                    passwordInput.focus();
                }
                
                return;
            }
            
            // Deshabilitar botón para evitar doble submit
            btnLogin.disabled = true;
            btnLogin.innerHTML = `
                <svg class="animate-spin" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10" stroke-width="4" stroke-opacity="0.25"/>
                    <path d="M4 12a8 8 0 018-8" stroke-width="4" stroke-linecap="round"/>
                </svg>
                <span>Iniciando sesión...</span>
            `;
        });
    }
    
    // Auto-cerrar alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.animation = 'slideUp 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Animación del spinner
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes slideUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
    `;
    document.head.appendChild(style);
    
    // Prevenir copiar/pegar en password (opcional - seguridad adicional)
    passwordInput.addEventListener('paste', function(e) {
        // e.preventDefault(); // Descomentar si quieres bloquear paste
    });
    
    // Detectar CAPS LOCK
    passwordInput.addEventListener('keyup', function(e) {
        if (e.getModifierState('CapsLock')) {
            setInputError(this, 'Mayúsculas activadas');
        } else {
            const errors = this.closest('.form-group').querySelectorAll('.error-message');
            errors.forEach(error => {
                if (error.textContent === 'Mayúsculas activadas') {
                    error.remove();
                    this.style.borderColor = '';
                }
            });
        }
    });
});

// Prevenir ataques de timing
function constantTimeCompare(a, b) {
    if (a.length !== b.length) {
        return false;
    }
    let result = 0;
    for (let i = 0; i < a.length; i++) {
        result |= a.charCodeAt(i) ^ b.charCodeAt(i);
    }
    return result === 0;
}