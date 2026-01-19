<?php
// public/index.php
// ============================================
// PUNTO DE ENTRADA PRINCIPAL
// ============================================

// Cargar configuración
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Security.php';

// Cargar controladores
require_once __DIR__ . '/../app/controllers/AuthController.php';

// Iniciar seguridad
$security = Security::getInstance();
$security->initSecureSession();

// Obtener ruta solicitada
$requestUri = $_SERVER['REQUEST_URI'];
$requestUri = strtok($requestUri, '?'); // Remover query string

// Router simple
switch ($requestUri) {
    // ============================================
    // AUTENTICACIÓN
    // ============================================
    case '/':
    case '/login':
        $authController = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } else {
            $authController->mostrarLogin();
        }
        break;
    
    case '/logout':
        $authController = new AuthController();
        $authController->logout();
        break;
    
    case '/recuperar-password':
        $authController = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->procesarRecuperacion();
        } else {
            $authController->mostrarRecuperar();
        }
        break;
    
    case '/cambiar-password':
        $authController = new AuthController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->cambiarPassword();
        } else {
            $authController->mostrarCambiarPassword();
        }
        break;
    
    case '/verificar-sesion':
        $authController = new AuthController();
        $authController->verificarSesion();
        break;
    
    // ============================================
    // DASHBOARD (requiere autenticación)
    // ============================================
    case '/dashboard':
        $authController = new AuthController();
        $authController->requireAuth();
        
        // Aquí cargarías el DashboardController
        echo "<h1>Dashboard - En construcción</h1>";
        echo "<p>Bienvenido, " . htmlspecialchars($_SESSION['nombre_completo']) . "</p>";
        echo "<p>Rol: " . htmlspecialchars($_SESSION['user_rol']) . "</p>";
        echo "<a href='/logout'>Cerrar sesión</a>";
        break;
    
    // ============================================
    // MÓDULOS (pendientes)
    // ============================================
    case '/pacientes':
        $authController = new AuthController();
        $authController->requireRole([ROL_ADMIN, ROL_ANALISTA]);
        echo "<h1>Gestión de Pacientes - En construcción</h1>";
        break;
    
    case '/ordenes':
        $authController = new AuthController();
        $authController->requireRole([ROL_ADMIN, ROL_ANALISTA]);
        echo "<h1>Gestión de Órdenes - En construcción</h1>";
        break;
    
    case '/resultados':
        $authController = new AuthController();
        $authController->requireRole([ROL_ADMIN, ROL_ANALISTA]);
        echo "<h1>Carga de Resultados - En construcción</h1>";
        break;
    
    case '/usuarios':
        $authController = new AuthController();
        $authController->requireRole([ROL_ADMIN]);
        echo "<h1>Gestión de Usuarios - En construcción</h1>";
        break;
    
    // ============================================
    // 404
    // ============================================
    default:
        http_response_code(404);
        echo "<h1>404 - Página no encontrada</h1>";
        echo "<a href='/login'>Volver al inicio</a>";
        break;
}
?>