<?php
// public/index.php
// ============================================
// PUNTO DE ENTRADA PRINCIPAL — GonzaloLabs
// Router + RBAC Middleware integrado
// ============================================

// ── Configuración base ──────────────────────
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// ── Núcleo ───────────────────────────────────
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/RBAC.php';
require_once __DIR__ . '/../core/Middleware.php';

// ── Controladores ────────────────────────────
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/DashboardController.php';
require_once __DIR__ . '/../app/controllers/PacienteController.php';
require_once __DIR__ . '/../app/controllers/OrdenController.php';
require_once __DIR__ . '/../app/controllers/ResultadoController.php';
require_once __DIR__ . '/../app/controllers/ExamenController.php';
require_once __DIR__ . '/../app/controllers/CotizacionController.php';
require_once __DIR__ . '/../app/controllers/FacturaController.php';
require_once __DIR__ . '/../app/controllers/ReporteController.php';
require_once __DIR__ . '/../app/controllers/UsuarioController.php';
require_once __DIR__ . '/../app/controllers/ConsultaPublicaController.php';

// ── Obtener ruta ─────────────────────────────
$uri    = $_SERVER['REQUEST_URI'];
$ruta   = strtok($uri, '?');
$metodo = $_SERVER['REQUEST_METHOD'];

// ── RBAC Middleware ───────────────────────────
Middleware::handle($ruta);

// ============================================
// ROUTER
// ============================================
switch (true) {

    // AUTENTICACION (publicas)
    case ($ruta === '/' || $ruta === '/login'):
        $c = new AuthController();
        $metodo === 'POST' ? $c->login() : $c->mostrarLogin();
        break;

    case $ruta === '/logout':
        (new AuthController())->logout();
        break;

    case $ruta === '/recuperar-password':
        $c = new AuthController();
        $metodo === 'POST' ? $c->procesarRecuperacion() : $c->mostrarRecuperar();
        break;

    case $ruta === '/cambiar-password':
        $c = new AuthController();
        $metodo === 'POST' ? $c->cambiarPassword() : $c->mostrarCambiarPassword();
        break;

    case $ruta === '/verificar-sesion':
        (new AuthController())->verificarSesion();
        break;

    // CONSULTA PUBLICA POR TOKEN / QR
    case $ruta === '/consulta':
    case (bool) preg_match('#^/consulta/([a-f0-9]{64})$#', $ruta, $m):
        $token = $m[1] ?? ($_GET['token'] ?? '');
        (new ConsultaPublicaController())->verResultados($token);
        break;

    // DASHBOARD (administrador, analistaL)
    case $ruta === '/dashboard':
        (new DashboardController())->index();
        break;

    // PORTAL PACIENTE
    case $ruta === '/portal/resultados':
        (new ConsultaPublicaController())->portalPaciente();
        break;

    // ACCESO MEDICO (solo lectura)
    case $ruta === '/medico/resultados':
        (new ResultadoController())->vistaMedico();
        break;

    case (bool) preg_match('#^/medico/resultados/(\d+)$#', $ruta, $m):
        (new ResultadoController())->detalleMedico((int)$m[1]);
        break;

    // PACIENTES
    case $ruta === '/pacientes':
        (new PacienteController())->index();
        break;

    case $ruta === '/pacientes/crear':
        $c = new PacienteController();
        $metodo === 'POST' ? $c->guardar() : $c->crear();
        break;

    case (bool) preg_match('#^/pacientes/editar/(\d+)$#', $ruta, $m):
        $c = new PacienteController();
        $metodo === 'POST' ? $c->actualizar((int)$m[1]) : $c->editar((int)$m[1]);
        break;

    case (bool) preg_match('#^/pacientes/eliminar/(\d+)$#', $ruta, $m):
        (new PacienteController())->eliminar((int)$m[1]);
        break;

    case (bool) preg_match('#^/pacientes/historial/(\d+)$#', $ruta, $m):
        (new PacienteController())->historial((int)$m[1]);
        break;

    case $ruta === '/pacientes/buscar':
        (new PacienteController())->buscar();
        break;

    // ORDENES
    case $ruta === '/ordenes':
        (new OrdenController())->index();
        break;

    case $ruta === '/ordenes/crear':
        $c = new OrdenController();
        $metodo === 'POST' ? $c->guardar() : $c->crear();
        break;

    case (bool) preg_match('#^/ordenes/editar/(\d+)$#', $ruta, $m):
        $c = new OrdenController();
        $metodo === 'POST' ? $c->actualizar((int)$m[1]) : $c->editar((int)$m[1]);
        break;

    case (bool) preg_match('#^/ordenes/validar/(\d+)$#', $ruta, $m):
        (new OrdenController())->validar((int)$m[1]);
        break;

    case (bool) preg_match('#^/ordenes/publicar/(\d+)$#', $ruta, $m):
        (new OrdenController())->publicar((int)$m[1]);
        break;

    // RESULTADOS
    case $ruta === '/resultados':
        (new ResultadoController())->index();
        break;

    case $ruta === '/resultados/cargar':
        $c = new ResultadoController();
        $metodo === 'POST' ? $c->guardarManual() : $c->cargarManual();
        break;

    case $ruta === '/resultados/cargar-automatico':
        $c = new ResultadoController();
        $metodo === 'POST' ? $c->procesarImportacion() : $c->cargarAutomatico();
        break;

    case (bool) preg_match('#^/resultados/editar/(\d+)$#', $ruta, $m):
        $c = new ResultadoController();
        $metodo === 'POST' ? $c->actualizar((int)$m[1]) : $c->editar((int)$m[1]);
        break;

    case (bool) preg_match('#^/resultados/validar/(\d+)$#', $ruta, $m):
        (new ResultadoController())->validar((int)$m[1]);
        break;

    case $ruta === '/resultados/alertas':
        (new ResultadoController())->alertasCriticas();
        break;

    // EXAMENES
    case $ruta === '/examenes':
        (new ExamenController())->index();
        break;

    case $ruta === '/examenes/crear':
        $c = new ExamenController();
        $metodo === 'POST' ? $c->guardar() : $c->crear();
        break;

    case (bool) preg_match('#^/examenes/editar/(\d+)$#', $ruta, $m):
        $c = new ExamenController();
        $metodo === 'POST' ? $c->actualizar((int)$m[1]) : $c->editar((int)$m[1]);
        break;

    case (bool) preg_match('#^/examenes/eliminar/(\d+)$#', $ruta, $m):
        (new ExamenController())->eliminar((int)$m[1]);
        break;

    case (bool) preg_match('#^/examenes/parametros/(\d+)$#', $ruta, $m):
        $c = new ExamenController();
        $metodo === 'POST' ? $c->guardarParametros((int)$m[1]) : $c->parametros((int)$m[1]);
        break;

    // COTIZACIONES
    case $ruta === '/cotizaciones':
        (new CotizacionController())->index();
        break;

    case $ruta === '/cotizaciones/crear':
        $c = new CotizacionController();
        $metodo === 'POST' ? $c->guardar() : $c->crear();
        break;

    case (bool) preg_match('#^/cotizaciones/editar/(\d+)$#', $ruta, $m):
        $c = new CotizacionController();
        $metodo === 'POST' ? $c->actualizar((int)$m[1]) : $c->editar((int)$m[1]);
        break;

    case $ruta === '/cotizaciones/exportar':
        (new CotizacionController())->exportar();
        break;

    // FACTURAS
    case $ruta === '/facturas':
        (new FacturaController())->index();
        break;

    case $ruta === '/facturas/crear':
        $c = new FacturaController();
        $metodo === 'POST' ? $c->guardar() : $c->crear();
        break;

    case (bool) preg_match('#^/facturas/anular/(\d+)$#', $ruta, $m):
        (new FacturaController())->anular((int)$m[1]);
        break;

    case (bool) preg_match('#^/facturas/qr/(\d+)$#', $ruta, $m):
        (new FacturaController())->generarQR((int)$m[1]);
        break;

    // REPORTES (solo administrador)
    case $ruta === '/reportes':
        (new ReporteController())->index();
        break;

    case $ruta === '/reportes/exportar':
        (new ReporteController())->exportar();
        break;

    case $ruta === '/reportes/auditoria':
        (new ReporteController())->auditoria();
        break;

    // USUARIOS (solo administrador)
    case $ruta === '/usuarios':
        (new UsuarioController())->index();
        break;

    case $ruta === '/usuarios/crear':
        $c = new UsuarioController();
        $metodo === 'POST' ? $c->guardar() : $c->crear();
        break;

    case (bool) preg_match('#^/usuarios/editar/(\d+)$#', $ruta, $m):
        $c = new UsuarioController();
        $metodo === 'POST' ? $c->actualizar((int)$m[1]) : $c->editar((int)$m[1]);
        break;

    case (bool) preg_match('#^/usuarios/desactivar/(\d+)$#', $ruta, $m):
        (new UsuarioController())->desactivar((int)$m[1]);
        break;

    case (bool) preg_match('#^/usuarios/resetear-clave/(\d+)$#', $ruta, $m):
        (new UsuarioController())->resetearClave((int)$m[1]);
        break;

    // CONFIGURACION (solo administrador)
    case $ruta === '/configuracion':
        (new DashboardController())->configuracion();
        break;

    // API AJAX
    case $ruta === '/api/sesion':
        (new AuthController())->verificarSesion();
        break;

    // 403
    case $ruta === '/acceso-denegado':
        http_response_code(403);
        $titulo = '403 — Acceso denegado';
        $mensaje = 'No tienes permisos para acceder a esta sección.';
        include __DIR__ . '/../app/views/errors/403.php';
        break;

    // 404
    default:
        http_response_code(404);
        include __DIR__ . '/../app/views/errors/404.php';
        break;
}