<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - GonzaloLabs</title>
    <link rel="stylesheet" href="/public/assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .password-strength { height: 4px; border-radius: 2px; background: #e5e7eb; margin-top: 0.5rem; overflow: hidden; }
        .password-strength-bar { height: 100%; border-radius: 2px; transition: width 0.4s ease, background 0.4s ease; width: 0; }
        .strength-label { font-size: 0.75rem; margin-top: 0.35rem; transition: color 0.3s; }
        .requirements { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 0.875rem 1rem; margin-top: 0.75rem; }
        .req-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #6b7280; margin-bottom: 0.35rem; transition: color 0.3s; }
        .req-item:last-child { margin-bottom: 0; }
        .req-item svg { flex-shrink: 0; transition: stroke 0.3s; }
        .req-item.ok { color: #059669; }
        .req-item.ok svg { stroke: #059669; }
    </style>
</head>
<body>
<div class="login-container">

    <!-- Formulario -->
    <div class="login-form-section">
        <div class="login-form-wrapper">
            <div class="logo-container">
                <div class="logo-icon">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                        <rect width="48" height="48" rx="12" fill="url(#g1)"/>
                        <path d="M24 12L32 18V30L24 36L16 30V18L24 12Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M24 24L32 18M24 24L16 18M24 24V36" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs>
                            <linearGradient id="g1" x1="0" y1="0" x2="48" y2="48">
                                <stop stop-color="#06b6d4"/><stop offset="1" stop-color="#3b82f6"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <h1>GonzaloLabs</h1>
            </div>

            <div class="welcome-text">
                <h2>Crear nueva contraseña</h2>
                <p>Elija una contraseña segura para proteger su cuenta</p>
            </div>

            <?php if (!empty($flash['message'])): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?>">
                <svg class="alert-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="/cambiar-password" class="login-form" id="formCambiar" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                <!-- Nueva contraseña -->
                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        <input type="password" id="password" name="password" required
                            autocomplete="new-password" placeholder="Mínimo 8 caracteres"
                            oninput="checkStrength(this.value)" minlength="8">
                        <button type="button" class="toggle-password" onclick="toggleVis('password',this)" title="Mostrar contraseña">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Barra de fortaleza -->
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <p class="strength-label" id="strengthLabel" style="color:#9ca3af"></p>
                    <!-- Requisitos -->
                    <div class="requirements" id="reqBox" style="display:none">
                        <div class="req-item" id="req-len">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            Al menos 8 caracteres
                        </div>
                        <div class="req-item" id="req-upper">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            Una letra mayúscula
                        </div>
                        <div class="req-item" id="req-num">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            Un número
                        </div>
                    </div>
                </div>

                <!-- Confirmar contraseña -->
                <div class="form-group">
                    <label for="confirmar_password">Confirmar contraseña</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <input type="password" id="confirmar_password" name="confirmar_password" required
                            autocomplete="new-password" placeholder="Repita la nueva contraseña"
                            oninput="checkMatch()">
                        <button type="button" class="toggle-password" onclick="toggleVis('confirmar_password',this)" title="Mostrar">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                    <p class="strength-label" id="matchLabel" style="color:#9ca3af; margin-top:0.35rem;"></p>
                </div>

                <button type="submit" class="btn-login" id="btnSubmit" disabled>
                    <span class="btn-text">Guardar nueva contraseña</span>
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </form>

            <div class="help-text">
                <p><a href="/login">← Volver al inicio de sesión</a></p>
            </div>
        </div>
    </div>

    <!-- Branding -->
    <div class="login-brand-section">
        <div class="brand-content">
            <div class="brand-icon">
                <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                    <circle cx="60" cy="60" r="55" stroke="white" stroke-width="2" opacity="0.2"/>
                    <circle cx="60" cy="60" r="40" stroke="white" stroke-width="2" opacity="0.3"/>
                    <path d="M60 35C47.3 35 37 45.3 37 58v4c0 2.8 2.2 5 5 5h3v-9c0-8.3 6.7-15 15-15s15 6.7 15 15v9h3c2.8 0 5-2.2 5-5v-4C83 45.3 72.7 35 60 35z" stroke="white" stroke-width="3" fill="rgba(255,255,255,0.1)"/>
                    <rect x="48" y="58" width="24" height="27" rx="3" stroke="white" stroke-width="3" fill="rgba(255,255,255,0.1)"/>
                    <circle cx="60" cy="71" r="3" fill="white"/>
                    <line x1="60" y1="74" x2="60" y2="79" stroke="white" stroke-width="3"/>
                    <path d="M44 90 L60 98 L76 90" stroke="white" stroke-width="2" opacity="0.5" stroke-linecap="round"/>
                </svg>
            </div>
            <h2>Seguridad</h2>
            <h3>Nueva contraseña</h3>
            <p>Elija una contraseña que no haya usado antes y guárdela en un lugar seguro.</p>
            <div class="features">
                <div class="feature-item">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Mínimo 8 caracteres</span>
                </div>
                <div class="feature-item">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Combina letras, números y mayúsculas</span>
                </div>
                <div class="feature-item">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="white"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>El enlace expira en 1 hora</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleVis(id, btn) {
    const inp = document.getElementById(id);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.style.opacity = inp.type === 'text' ? '1' : '0.6';
}

function checkStrength(val) {
    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    const box   = document.getElementById('reqBox');
    box.style.display = val.length > 0 ? 'block' : 'none';

    const len   = val.length >= 8;
    const upper = /[A-Z]/.test(val);
    const num   = /[0-9]/.test(val);

    setReq('req-len',   len);
    setReq('req-upper', upper);
    setReq('req-num',   num);

    const score = [len, upper, num].filter(Boolean).length;
    const configs = [
        { w:'0%', c:'#e5e7eb', t:'' },
        { w:'33%',c:'#ef4444', t:'Débil' },
        { w:'66%',c:'#f59e0b', t:'Moderada' },
        { w:'100%',c:'#10b981',t:'Fuerte ✓' }
    ];
    const cfg = val.length === 0 ? configs[0] : configs[score];
    bar.style.width = cfg.w;
    bar.style.background = cfg.c;
    label.textContent = cfg.t;
    label.style.color = cfg.c;
    checkMatch();
}

function setReq(id, ok) {
    const el = document.getElementById(id);
    el.classList.toggle('ok', ok);
    el.querySelector('svg').setAttribute('stroke', ok ? '#059669' : '#9ca3af');
    el.querySelector('svg').innerHTML = ok
        ? '<polyline points="20 6 9 17 4 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
        : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>';
}

function checkMatch() {
    const p1 = document.getElementById('password').value;
    const p2 = document.getElementById('confirmar_password').value;
    const lbl = document.getElementById('matchLabel');
    const btn = document.getElementById('btnSubmit');
    if (!p2) { lbl.textContent = ''; btn.disabled = true; return; }
    const match = p1 === p2 && p1.length >= 8;
    lbl.textContent = match ? '✓ Las contraseñas coinciden' : '✗ Las contraseñas no coinciden';
    lbl.style.color  = match ? '#059669' : '#ef4444';
    btn.disabled = !match;
}
</script>
</body>
</html>