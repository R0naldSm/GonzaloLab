<?php
/**
 * reportes/exportar.php
 * Esta vista NO renderiza HTML — es llamada directamente por
 * ReporteController::exportar() que hace streaming del CSV.
 *
 * Si por alguna razón se llega aquí directamente (sin el controlador),
 * mostramos un mensaje informativo y redirigimos.
 *
 * En producción: el controlador llama a los métodos privados de exportación
 * y envía las cabeceras Content-Disposition: attachment antes de cualquier output.
 * Este archivo PHP sirve como placeholder documentado para la vista.
 */

// Si llegamos aquí es porque el controlador no hizo el streaming.
// Redirigir al dashboard de reportes.
if (!headers_sent()) {
    header('Location: /reportes');
    exit;
}

// Fallback en caso de que headers ya hayan sido enviados
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="3;url=/reportes">
    <title>Exportando… — GonzaloLabs</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; display: flex; align-items: center;
               justify-content: center; min-height: 100vh; margin: 0; background: #f1f5f9; }
        .card { background: #fff; border-radius: 1rem; padding: 2.5rem 3rem;
                text-align: center; box-shadow: 0 8px 32px rgba(0,0,0,.08); max-width: 420px; }
        h2 { font-size: 1.2rem; font-weight: 800; color: #0f172a; margin: 0 0 .5rem; }
        p  { font-size: .88rem; color: #64748b; margin: 0 0 1.5rem; }
        a  { display: inline-flex; align-items: center; gap: .4rem;
             background: linear-gradient(135deg,#06b6d4,#3b82f6); color: #fff;
             padding: .6rem 1.25rem; border-radius: .5rem; text-decoration: none;
             font-weight: 600; font-size: .85rem; }
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size:3rem;margin-bottom:1rem">📊</div>
        <h2>Preparando exportación</h2>
        <p>Si la descarga no inicia automáticamente, vuelva al panel de reportes y vuelva a intentarlo.</p>
        <a href="/reportes">← Volver a Reportes</a>
    </div>
</body>
</html>