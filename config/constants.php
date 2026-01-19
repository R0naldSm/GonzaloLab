<?php
// config/constants.php
// ============================================
// CONSTANTES DEL SISTEMA
// ============================================

// Cargar .env si existe
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    
    // Base de Datos
    define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $env['DB_NAME'] ?? 'gonzalolabs_db');
    define('DB_USER', $env['DB_USER'] ?? 'root');
    define('DB_PASSWORD', $env['DB_PASSWORD'] ?? '');
    define('DB_CHARSET', $env['DB_CHARSET'] ?? 'utf8mb4');
    
    // Aplicación
    define('APP_NAME', $env['APP_NAME'] ?? 'GonzaloLabs');
    define('APP_URL', $env['APP_URL'] ?? 'http://localhost');
    define('APP_ENV', $env['APP_ENV'] ?? 'development');
    define('APP_DEBUG', $env['APP_DEBUG'] === 'true');
    
    // Seguridad
    define('SESSION_LIFETIME', (int)($env['SESSION_LIFETIME'] ?? 1800));
    define('MAX_LOGIN_ATTEMPTS', (int)($env['MAX_LOGIN_ATTEMPTS'] ?? 5));
    define('LOCKOUT_TIME', (int)($env['LOCKOUT_TIME'] ?? 1800));
    
} else {
    // Valores por defecto si no existe .env
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'gonzalolabs_db');
    define('DB_USER', 'root');
    define('DB_PASSWORD', '');
    define('DB_CHARSET', 'utf8mb4');
    
    define('APP_NAME', 'GonzaloLabs');
    define('APP_URL', 'http://localhost');
    define('APP_ENV', 'development');
    define('APP_DEBUG', true);
    
    define('SESSION_LIFETIME', 1800);
    define('MAX_LOGIN_ATTEMPTS', 5);
    define('LOCKOUT_TIME', 1800);
}

// Rutas
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// Roles
define('ROL_ADMIN', 'administrador');
define('ROL_ANALISTA', 'analistaL');
define('ROL_MEDICO', 'medico');
define('ROL_PACIENTE', 'paciente');

// Estados
define('ESTADO_ACTIVO', 'activo');
define('ESTADO_INACTIVO', 'inactivo');
define('ESTADO_BLOQUEADO', 'bloqueado');

// Zona horaria
date_default_timezone_set('America/Guayaquil');

// Mostrar errores en desarrollo
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>