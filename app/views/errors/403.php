<?php
// app/views/errors/403.php
// Página de acceso denegado
if (!defined('APP_NAME')) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado — GonzaloLabs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .error-card { max-width: 480px; text-align: center; }
        .error-icon { font-size: 5rem; color: #dc3545; }
        .btn-primary { background: #0d9488; border-color: #0d9488; }
        .btn-primary:hover { background: #0f766e; border-color: #0f766e; }
    </style>
</head>
<body>
<div class="error-card card shadow-lg p-5">
    <div class="error-icon mb-3"><i class="bi bi-shield-lock-fill"></i></div>
    <h2 class="fw-bold text-danger mb-2">Acceso Denegado</h2>
    <p class="text-muted mb-4">
        No tienes los permisos necesarios para ver esta sección.<br>
        Contacta al administrador si crees que es un error.
    </p>
    <a href="javascript:history.back()" class="btn btn-primary me-2">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <a href="/dashboard" class="btn btn-outline-secondary">
        <i class="bi bi-house"></i> Inicio
    </a>
</div>
</body>
</html>