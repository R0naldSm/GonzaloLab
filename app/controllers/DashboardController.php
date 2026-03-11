<?php
// app/controllers/DashboardController.php
// ============================================
// CONTROLADOR: Dashboard estadístico y configuración
// Roles: administrador, analistaL
// ============================================

require_once __DIR__ . '/../../core/RBAC.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../models/Orden.php';
require_once __DIR__ . '/../models/Examen.php';

class DashboardController {

    private Security $security;
    private Orden    $ordenModel;
    private Examen   $examenModel;

    public function __construct() {
        $this->security    = Security::getInstance();
        $this->ordenModel  = new Orden();
        $this->examenModel = new Examen();
    }

    // ─────────────────────────────────────────
    // DASHBOARD PRINCIPAL
    // ─────────────────────────────────────────

    public function index(): void {
        RBAC::requerirPermiso('dashboard.ver');

        // Rango de fechas (por defecto: mes actual)
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $stats         = $this->ordenModel->estadisticasGenerales($desde, $hasta);
        $masSolicitados = $this->examenModel->masSolicitados(8);
        $alertas       = $this->ordenModel->alertasCriticasRecientes();
        $menuNav       = RBAC::getMenu();
        $rolActual     = $_SESSION['user_rol'];
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        // Sólo el admin ve estadísticas completas
        $verEstadisticas = RBAC::puede('dashboard.estadisticas');

        $this->renderView('dashboard/index', compact(
            'stats', 'masSolicitados', 'alertas',
            'menuNav', 'rolActual', 'nombreUsuario',
            'verEstadisticas', 'desde', 'hasta'
        ));
    }

    // ─────────────────────────────────────────
    // CONFIGURACIÓN DEL SISTEMA (solo admin)
    // ─────────────────────────────────────────

    public function configuracion(): void {
        RBAC::requerirPermiso('config.ver');

        $db     = Database::getInstance();
        $config = $db->query("SELECT * FROM configuracion_sistema ORDER BY clave");
        $menuNav = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RBAC::requerirPermiso('config.editar');
            $this->guardarConfiguracion($_POST['config'] ?? []);
            $this->flash('success', 'Configuración guardada');
            header('Location: /configuracion');
            exit;
        }

        $this->renderView('dashboard/configuracion', compact('config', 'menuNav', 'nombreUsuario'));
    }

    // ─────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────

    private function guardarConfiguracion(array $items): void {
        $db = Database::getInstance();
        foreach ($items as $clave => $valor) {
            $db->execute(
                "UPDATE configuracion_sistema SET valor = ?, modificado_por = ?
                 WHERE clave = ?",
                [$valor, $_SESSION['user_id'], $clave]
            );
        }
    }

    private function renderView(string $view, array $datos = []): void {
        extract($datos);
        $flash        = $this->consumeFlash();
        $csrfToken    = $this->security->generateCSRFToken();
        require_once __DIR__ . "/../views/{$view}.php";
    }

    private function flash(string $tipo, string $mensaje): void {
        $_SESSION['flash_type']    = $tipo;
        $_SESSION['flash_message'] = $mensaje;
    }

    private function consumeFlash(): array {
        $flash = [
            'type'    => $_SESSION['flash_type']    ?? null,
            'message' => $_SESSION['flash_message'] ?? null,
        ];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $flash;
    }
}
?>