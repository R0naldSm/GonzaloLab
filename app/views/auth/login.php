<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - GonzaloLabs</title>
    <link rel="stylesheet" href="/public/assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <!-- Sección izquierda - Formulario -->
        <div class="login-form-section">
            <div class="login-form-wrapper">
                <div class="logo-container">
                    <div class="logo-icon">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="48" height="48" rx="12" fill="url(#gradient1)"/>
                            <path d="M24 12L32 18V30L24 36L16 30V18L24 12Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M24 24L32 18M24 24L16 18M24 24V36" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
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
                    <h2>Bienvenido de nuevo</h2>
                    <p>Ingrese sus credenciales para acceder al sistema</p>
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

                <form method="POST" action="/login" class="login-form" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $this->security->generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="username">Usuario o Email</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                required 
                                autofocus
                                autocomplete="username"
                                placeholder="Ingrese su usuario"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                autocomplete="current-password"
                                placeholder="Ingrese su contraseña"
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <svg class="eye-icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="recordar">
                            <span class="checkbox-text">Recordarme</span>
                        </label>
                        <a href="/recuperar-password" class="forgot-link">¿Olvidó su contraseña?</a>
                    </div>

                    <button type="submit" class="btn-login" id="btnLogin">
                        <span class="btn-text">Iniciar Sesión</span>
                        <svg class="btn-arrow" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </form>

                <div class="help-text">
                    <p>¿Necesita ayuda? <a href="mailto:soporte@gonzalolabs.com">Contacte a soporte</a></p>
                </div>
            </div>
        </div>

        <!-- Sección derecha - Branding -->
        <div class="login-brand-section">
            <div class="brand-content">
                <div class="brand-icon">
                    <svg width="120" height="120" viewBox="0 0 120 120" fill="none">
                        <circle cx="60" cy="60" r="55" stroke="white" stroke-width="2" opacity="0.2"/>
                        <circle cx="60" cy="60" r="45" stroke="white" stroke-width="2" opacity="0.3"/>
                        <circle cx="60" cy="60" r="35" stroke="white" stroke-width="2" opacity="0.5"/>
                        <path d="M60 30L75 40V80L60 90L45 80V40L60 30Z" stroke="white" stroke-width="3" fill="rgba(255,255,255,0.1)"/>
                        <path d="M60 60L75 40M60 60L45 40M60 60V90" stroke="white" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </div>
                <h2>Sistema de Gestión</h2>
                <h3>Laboratorio Clínico</h3>
                <p>Plataforma profesional para gestión de exámenes, resultados y reportes clínicos</p>
                
                <div class="features">
                    <div class="feature-item">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Gestión de pacientes y órdenes</span>
                    </div>
                    <div class="feature-item">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Detección automática de valores críticos</span>
                    </div>
                    <div class="feature-item">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Reportes y estadísticas en tiempo real</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/public/assets/js/login.js"></script>
</body>
</html>