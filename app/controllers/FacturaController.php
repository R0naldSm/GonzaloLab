<?php
// app/controllers/FacturaController.php
// ============================================
// CONTROLADOR: Facturas, comprobantes de pago y generación de QR
// Roles: administrador, analistaL
// ============================================

require_once __DIR__ . '/../../core/RBAC.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../models/Factura.php';
require_once __DIR__ . '/../models/Orden.php';

class FacturaController {

    private Security $security;
    private Factura  $model;

    public function __construct() {
        $this->security = Security::getInstance();
        $this->model    = new Factura();
    }

    // ─────────────────────────────────────────
    // LISTADO DE FACTURAS
    // ─────────────────────────────────────────

    public function index(): void {
        RBAC::requerirPermiso('facturas.ver');

        $filtros = [
            'estado_pago' => $_GET['estado_pago'] ?? '',
            'busqueda'    => $this->security->sanitize($_GET['q'] ?? ''),
            'fecha_desde' => $_GET['desde'] ?? date('Y-m-01'),
            'fecha_hasta' => $_GET['hasta'] ?? date('Y-m-d'),
        ];

        $facturas      = $this->model->listar($filtros);
        $ingresoHoy    = $this->model->totalIngresosHoy();
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('facturas/index', compact(
            'facturas', 'ingresoHoy', 'filtros', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // VER DETALLE / COMPROBANTE
    // ─────────────────────────────────────────

    public function crear(): void {
        RBAC::requerirPermiso('facturas.ver');

        $idOrden = (int)($_GET['orden'] ?? 0);

        if (!$idOrden) {
            $this->flash('error', 'Debe indicar una orden');
            header('Location: /facturas');
            exit;
        }

        $factura = $this->model->getById($idOrden);
        if (!$factura) {
            $this->flash('error', 'Orden no encontrada');
            header('Location: /facturas');
            exit;
        }

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('facturas/detalle', compact('factura', 'menuNav', 'nombreUsuario'));
    }

    public function guardar(): void {
        RBAC::requerirPermiso('facturas.crear');
        $this->validarCSRF();

        $idOrden     = (int)($_POST['id_orden'] ?? 0);
        $metodoPago  = $this->security->sanitize($_POST['metodo_pago'] ?? '');

        $metodosValidos = ['efectivo', 'tarjeta', 'transferencia', 'credito'];
        if (!$idOrden || !in_array($metodoPago, $metodosValidos, true)) {
            $this->flash('error', 'Datos de pago inválidos');
            header('Location: /facturas');
            exit;
        }

        $resultado = $this->model->registrarPago($idOrden, $metodoPago);

        if ($resultado['success']) {
            // Auto-generar token QR al registrar el pago
            $this->model->generarToken($idOrden);
            $this->flash('success', 'Pago registrado y QR generado para la orden');
            header('Location: /facturas/qr/' . $idOrden);
        } else {
            $this->flash('error', $resultado['message']);
            header('Location: /facturas');
        }
        exit;
    }

    // ─────────────────────────────────────────
    // ANULAR FACTURA
    // ─────────────────────────────────────────

    public function anular(int $idOrden): void {
        RBAC::requerirPermiso('facturas.anular');

        if (!$this->model->getById($idOrden)) {
            $this->jsonResponse(['success' => false, 'message' => 'Orden no encontrada']);
            return;
        }

        $resultado = $this->model->anular($idOrden);
        $this->jsonResponse($resultado);
    }

    // ─────────────────────────────────────────
    // GENERAR / VER QR
    // ─────────────────────────────────────────

    public function generarQR(int $idOrden): void {
        RBAC::requerirPermiso('facturas.generar_qr');

        $factura = $this->model->getById($idOrden);
        if (!$factura) {
            $this->flash('error', 'Orden no encontrada');
            header('Location: /facturas');
            exit;
        }

        // Si se pide regenerar (POST) o si no existe token activo
        $debeGenerar = ($_SERVER['REQUEST_METHOD'] === 'POST')
                    || empty($factura['token_acceso'])
                    || !$factura['token_activo'];

        if ($debeGenerar) {
            // Calcular minutos según tipo de exámenes (configurable)
            $minutos   = $this->getMinutosExpiracion($idOrden);
            $resultado = $this->model->generarToken($idOrden, $minutos);

            if (!$resultado['success']) {
                $this->flash('error', 'Error al generar el token: ' . $resultado['message']);
                header('Location: /facturas');
                exit;
            }

            // Recargar factura con el token nuevo
            $factura = $this->model->getById($idOrden);
        }

        $urlAcceso     = APP_URL . '/consulta/' . $factura['token_acceso'];
        $qrImageUrl    = $this->getQRUrl($urlAcceso);
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('facturas/qr', compact(
            'factura', 'urlAcceso', 'qrImageUrl', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // AJAX: Regenerar token
    // ─────────────────────────────────────────

    public function regenerarToken(): void {
        RBAC::requerirPermiso('facturas.generar_qr');
        header('Content-Type: application/json');

        $idOrden = (int)($_POST['id_orden'] ?? 0);
        if (!$idOrden) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }

        $minutos   = $this->getMinutosExpiracion($idOrden);
        $resultado = $this->model->generarToken($idOrden, $minutos);

        if ($resultado['success']) {
            $resultado['qr_url'] = $this->getQRUrl($resultado['url']);
        }

        echo json_encode($resultado);
        exit;
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    /**
     * Calcular minutos de expiración del token según los exámenes de la orden.
     * Si hay exámenes rápidos (<= 120 min), se usa la configuración de exámenes rápidos.
     * De lo contrario se usa la configuración estándar.
     */
    private function getMinutosExpiracion(int $idOrden): int {
        $db = Database::getInstance();

        // ¿Tiene exámenes rápidos?
        $tieneRapidos = $db->count(
            "SELECT COUNT(*) FROM orden_examenes oe
             JOIN examenes e ON oe.id_examen = e.id_examen
             WHERE oe.id_orden = ? AND e.tiempo_entrega_min IS NOT NULL",
            [$idOrden]
        ) > 0;

        if ($tieneRapidos) {
            $cfg = $db->queryOne(
                "SELECT valor FROM configuracion_sistema WHERE clave = 'tiempo_expiracion_rapido_min'"
            );
            return (int)($cfg['valor'] ?? 120);
        }

        $cfg = $db->queryOne(
            "SELECT valor FROM configuracion_sistema WHERE clave = 'tiempo_expiracion_estandar_dias'"
        );
        $dias = (int)($cfg['valor'] ?? 30);
        return $dias * 1440; // Convertir días a minutos
    }

    /**
     * URL de generación de QR usando API pública (sin dependencias PHP).
     * Usa Google Chart API (gratuita, sin API key).
     */
    private function getQRUrl(string $url): string {
        $encodedUrl = urlencode($url);
        return "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl={$encodedUrl}&choe=UTF-8";
    }

    private function validarCSRF(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->security->validateCSRFToken($token)) {
            $this->security->logSecurityEvent('CSRF_ATTACK', 'FacturaController');
            $this->flash('error', 'Token de seguridad inválido');
            header('Location: /facturas');
            exit;
        }
    }

    private function renderView(string $view, array $datos = []): void {
        extract($datos);
        $flash     = $this->consumeFlash();
        $csrfToken = $this->security->generateCSRFToken();
        require_once __DIR__ . "/../views/{$view}.php";
    }

    private function jsonResponse(array $data): void {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
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