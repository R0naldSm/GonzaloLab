<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - GonzaloLabs</title>
    <link rel="stylesheet" href="/public/assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-form-section">
            <div class="login-form-wrapper">
                <div class="logo-container">
                    <div class="logo-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
                            <rect width="48" height="48" rx="12" fill="url(#gradient1)"/>
                            <path d="M24 12L32 18V30L24 36L16 30V18L24 12Z" stroke="white" stroke-width="2"/>
                            <defs>
                                <linearGradient id="gradient1" x1="0" y1="0" x2="48" y2="48">
                                    <stop stop-color="#06b6d4"/>
                                    <stop offset="1" stop-color="#3b82f6"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <h1>GonzaloLabs</h1>
                </div>

                <div class="welcome-text">
                    <h2>Recuperar Contraseña</h2>
                    <p>Ingrese su email y le enviaremos un enlace para restablecer su contraseña</p>
                </div>

                <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?>">
                    <svg class="alert-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
                </div>
                <?php 
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                endif; 
                ?>

                <form method="POST" action="/recuperar-password" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $this->security->generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="email">Correo Electrónico</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required 
                                autofocus
                                placeholder="ejemplo@correo.com"
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        <span class="btn-text">Enviar Enlace de Recuperación</span>
                    </button>
                </form>

                <div class="help-text">
                    <p><a href="/login">← Volver al inicio de sesión</a></p>
                </div>
            </div>
        </div>

        <div class="login-brand-section">
            <div class="brand-content">
                <div class="brand-icon">
                    <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                        <circle cx="60" cy="60" r="55" stroke="white" stroke-width="2" opacity="0.2"/>
                        <path d="M60 35C47.297 35 37 45.297 37 58v4c0 2.761 2.239 5 5 5h3v-9c0-8.284 6.716-15 15-15s15 6.716 15 15v9h3c2.761 0 5-2.239 5-5v-4c0-12.703-10.297-23-23-23z" stroke="white" stroke-width="3" fill="rgba(255,255,255,0.1)"/>
                        <rect x="48" y="58" width="24" height="27" rx="3" stroke="white" stroke-width="3" fill="rgba(255,255,255,0.1)"/>
                        <circle cx="60" cy="71" r="3" fill="white"/>
                        <line x1="60" y1="74" x2="60" y2="79" stroke="white" stroke-width="3"/>
                    </svg>
                </div>
                <h2>Seguridad</h2>
                <h3>Recuperación de Acceso</h3>
                <p>El enlace de recuperación expirará en 1 hora por seguridad. Si no solicitó este cambio, ignore este proceso.</p>
            </div>
        </div>
    </div>

    <script>
        // Auto-cerrar alertas
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.animation = 'slideUp 0.3s ease';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>