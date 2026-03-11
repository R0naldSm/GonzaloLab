<?php
// app/controllers/CotizacionController.php
// ============================================
// CONTROLADOR: Cotizaciones de exámenes
// Roles: administrador, analistaL
// ============================================

require_once __DIR__ . '/../../core/RBAC.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../models/Cotizacion.php';
require_once __DIR__ . '/../models/Examen.php';
require_once __DIR__ . '/../models/Paciente.php';

class CotizacionController {

    private Security    $security;
    private Cotizacion  $model;
    private Examen      $examenModel;

    public function __construct() {
        $this->security    = Security::getInstance();
        $this->model       = new Cotizacion();
        $this->examenModel = new Examen();
    }

    // ─────────────────────────────────────────
    // LISTADO
    // ─────────────────────────────────────────

    public function index(): void {
        RBAC::requerirPermiso('cotizaciones.ver');

        $filtros = [
            'estado'      => $_GET['estado'] ?? '',
            'busqueda'    => $this->security->sanitize($_GET['q'] ?? ''),
            'fecha_desde' => $_GET['desde'] ?? '',
            'fecha_hasta' => $_GET['hasta'] ?? '',
        ];

        $cotizaciones  = $this->model->listar($filtros);
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('cotizaciones/index', compact(
            'cotizaciones', 'filtros', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // CREAR
    // ─────────────────────────────────────────

    public function crear(): void {
        RBAC::requerirPermiso('cotizaciones.crear');

        $examenesPorCategoria = $this->examenModel->listarPorCategoria();
        $menuNav              = RBAC::getMenu();
        $nombreUsuario        = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('cotizaciones/crear', compact(
            'examenesPorCategoria', 'menuNav', 'nombreUsuario'
        ));
    }

    public function guardar(): void {
        RBAC::requerirPermiso('cotizaciones.crear');
        $this->validarCSRF();

        // Exámenes seleccionados (array de {id_examen, precio, cantidad})
        $examenesJson = $_POST['examenes_json'] ?? '[]';
        $examenes     = json_decode($examenesJson, true) ?? [];

        if (empty($examenes)) {
            $this->flash('error', 'Debe seleccionar al menos un examen');
            header('Location: /cotizaciones/crear');
            exit;
        }

        $datos = [
            'id_paciente'   => !empty($_POST['id_paciente']) ? (int)$_POST['id_paciente'] : null,
            'nombre_cliente'=> $this->security->sanitize($_POST['nombre_cliente'] ?? ''),
            'fecha_validez' => $_POST['fecha_validez'] ?? null,
            'descuento'     => (float)($_POST['descuento'] ?? 0),
            'observaciones' => $this->security->sanitize($_POST['observaciones'] ?? ''),
        ];

        // Sanitizar exámenes del JSON
        $examenesSanitizados = array_map(fn($e) => [
            'id_examen' => (int)$e['id_examen'],
            'precio'    => (float)$e['precio'],
            'cantidad'  => max(1, (int)($e['cantidad'] ?? 1)),
        ], $examenes);

        $resultado = $this->model->crear($datos, $examenesSanitizados);

        if ($resultado['success']) {
            $this->flash('success', 'Cotización ' . $resultado['numero'] . ' creada exitosamente');
            header('Location: /cotizaciones');
        } else {
            $this->flash('error', $resultado['message']);
            header('Location: /cotizaciones/crear');
        }
        exit;
    }

    // ─────────────────────────────────────────
    // VER DETALLE
    // ─────────────────────────────────────────

    public function editar(int $id): void {
        RBAC::requerirPermiso('cotizaciones.ver');

        $cotizacion = $this->model->getById($id);
        if (!$cotizacion) {
            $this->flash('error', 'Cotización no encontrada');
            header('Location: /cotizaciones');
            exit;
        }

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('cotizaciones/historial', compact(
            'cotizacion', 'menuNav', 'nombreUsuario'
        ));
    }

    public function actualizar(int $id): void {
        RBAC::requerirPermiso('cotizaciones.editar');
        $this->validarCSRF();

        $estado = $this->security->sanitize($_POST['estado'] ?? '');

        $resultado = $this->model->cambiarEstado($id, $estado);

        if ($resultado['success']) {
            $this->flash('success', 'Estado de cotización actualizado');
        } else {
            $this->flash('error', $resultado['message']);
        }
        header('Location: /cotizaciones');
        exit;
    }

    public function eliminar(int $id): void {
        RBAC::requerirPermiso('cotizaciones.eliminar');

        $resultado = $this->model->eliminar($id);
        $this->jsonResponse($resultado);
    }

    // ─────────────────────────────────────────
    // EXPORTAR A EXCEL (CSV)
    // ─────────────────────────────────────────

    public function exportar(): void {
        RBAC::requerirPermiso('cotizaciones.exportar');

        $filtros = [
            'estado'      => $_GET['estado'] ?? '',
            'fecha_desde' => $_GET['desde']  ?? date('Y-m-01'),
            'fecha_hasta' => $_GET['hasta']  ?? date('Y-m-d'),
        ];

        $cotizaciones = $this->model->listarParaExportar($filtros);

        $filename = 'cotizaciones_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        $output = fopen('php://output', 'w');
        // BOM UTF-8 para Excel
        fwrite($output, "\xEF\xBB\xBF");

        // Encabezados
        fputcsv($output, [
            'N° Cotización', 'Fecha', 'Paciente / Cliente',
            'Subtotal', 'Descuento', 'Total', 'Estado', 'Validez'
        ], ';');

        foreach ($cotizaciones as $c) {
            $cliente = trim(($c['paciente_nombres'] ?? '') . ' ' . ($c['paciente_apellidos'] ?? ''))
                       ?: ($c['nombre_cliente'] ?? 'N/A');

            fputcsv($output, [
                $c['numero_cotizacion'],
                $c['fecha_cotizacion'],
                $cliente,
                number_format((float)$c['subtotal'], 2),
                number_format((float)$c['descuento'], 2),
                number_format((float)$c['total'], 2),
                ucfirst($c['estado']),
                $c['fecha_validez'] ?? '-',
            ], ';');
        }

        fclose($output);
        exit;
    }

    // ─────────────────────────────────────────
    // AJAX: calcular total en tiempo real
    // ─────────────────────────────────────────

    public function calcularTotal(): void {
        RBAC::requerirPermiso('cotizaciones.crear');
        header('Content-Type: application/json');

        $examenes  = json_decode($_POST['examenes_json'] ?? '[]', true) ?? [];
        $descuento = (float)($_POST['descuento'] ?? 0);

        $subtotal = 0;
        foreach ($examenes as $e) {
            $subtotal += (float)$e['precio'] * max(1, (int)($e['cantidad'] ?? 1));
        }
        $total = $subtotal - $descuento;

        echo json_encode([
            'subtotal'  => round($subtotal, 2),
            'descuento' => round($descuento, 2),
            'total'     => round($total, 2),
        ]);
        exit;
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    private function validarCSRF(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->security->validateCSRFToken($token)) {
            $this->security->logSecurityEvent('CSRF_ATTACK', 'CotizacionController');
            $this->flash('error', 'Token de seguridad inválido');
            header('Location: /cotizaciones');
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