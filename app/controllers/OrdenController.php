<?php
// app/controllers/OrdenController.php
// ============================================
// CONTROLADOR: Órdenes de Laboratorio (flujo completo)
// Roles: administrador, analistaL
// ============================================

require_once __DIR__ . '/../../core/RBAC.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../models/Orden.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Examen.php';
require_once __DIR__ . '/../models/Factura.php';

class OrdenController {

    private Security $security;
    private Orden    $model;
    private Paciente $pacienteModel;
    private Examen   $examenModel;
    private Factura  $facturaModel;

    public function __construct() {
        $this->security      = Security::getInstance();
        $this->model         = new Orden();
        $this->pacienteModel = new Paciente();
        $this->examenModel   = new Examen();
        $this->facturaModel  = new Factura();
    }

    // ─────────────────────────────────────────
    // LISTADO
    // ─────────────────────────────────────────

    public function index(): void {
        RBAC::requerirPermiso('ordenes.ver');

        $filtros = [
            'estado'       => $_GET['estado']       ?? '',
            'estado_pago'  => $_GET['estado_pago']  ?? '',
            'fecha_desde'  => $_GET['desde']        ?? date('Y-m-01'),
            'fecha_hasta'  => $_GET['hasta']        ?? date('Y-m-d'),
            'numero_orden' => $this->security->sanitize($_GET['q'] ?? ''),
        ];

        $ordenes       = $this->model->listar($filtros);
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('ordenes/index', compact(
            'ordenes', 'filtros', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // CREAR
    // ─────────────────────────────────────────

    public function crear(): void {
        RBAC::requerirPermiso('ordenes.crear');

        $examenesPorCategoria = $this->examenModel->listarPorCategoria();
        $menuNav              = RBAC::getMenu();
        $nombreUsuario        = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        // Pre-cargar paciente si viene desde el listado de pacientes
        $pacientePreload = null;
        if (!empty($_GET['id_paciente'])) {
            $pacientePreload = $this->pacienteModel->getById((int)$_GET['id_paciente']);
        }

        // Lista de médicos para asignar (solo los del rol medico)
        $db      = Database::getInstance();
        $medicos = $db->query(
            "SELECT id_usuario, AES_DECRYPT(nombre_completo, ?) AS nombre_completo, username
             FROM usuarios WHERE rol = 'medico' AND estado = 'activo' AND eliminado = 0
             ORDER BY nombre_completo",
            [$this->security->getMySQLEncryptionKey()]
        );

        $this->renderView('ordenes/crear', compact(
            'examenesPorCategoria', 'pacientePreload', 'medicos', 'menuNav', 'nombreUsuario'
        ));
    }

    public function guardar(): void {
        RBAC::requerirPermiso('ordenes.crear');
        $this->validarCSRF();

        $idPaciente = (int)($_POST['id_paciente'] ?? 0);

        if (!$idPaciente || !$this->pacienteModel->getById($idPaciente)) {
            $this->flash('error', 'Debe seleccionar un paciente válido');
            header('Location: /ordenes/crear');
            exit;
        }

        // Exámenes seleccionados (array de IDs)
        $idExamenes = array_map('intval', $_POST['examenes'] ?? []);

        if (empty($idExamenes)) {
            $this->flash('error', 'Debe seleccionar al menos un examen');
            header('Location: /ordenes/crear');
            exit;
        }

        $datos = [
            'id_paciente'   => $idPaciente,
            'id_medico'     => !empty($_POST['id_medico']) ? (int)$_POST['id_medico'] : null,
            'tipo_atencion' => in_array($_POST['tipo_atencion'] ?? '', ['control','urgencia','normal'])
                               ? $_POST['tipo_atencion'] : 'normal',
            'observaciones' => $this->security->sanitize($_POST['observaciones'] ?? ''),
        ];

        $resultado = $this->model->crear($datos, $idExamenes);

        if ($resultado['success']) {
            // Auto-generar token QR al crear la orden
            $this->facturaModel->generarToken($resultado['id_orden']);
            $this->flash('success', 'Orden ' . $resultado['numero_orden'] . ' creada. QR generado.');
            header('Location: /ordenes');
        } else {
            $this->flash('error', $resultado['message']);
            header('Location: /ordenes/crear');
        }
        exit;
    }

    // ─────────────────────────────────────────
    // EDITAR
    // ─────────────────────────────────────────

    public function editar(int $id): void {
        RBAC::requerirPermiso('ordenes.editar');

        $orden = $this->model->getById($id);
        if (!$orden) {
            $this->flash('error', 'Orden no encontrada');
            header('Location: /ordenes');
            exit;
        }

        $db      = Database::getInstance();
        $medicos = $db->query(
            "SELECT id_usuario, AES_DECRYPT(nombre_completo, ?) AS nombre_completo, username
             FROM usuarios WHERE rol = 'medico' AND estado = 'activo' AND eliminado = 0
             ORDER BY nombre_completo",
            [$this->security->getMySQLEncryptionKey()]
        );

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('ordenes/editar', compact(
            'orden', 'medicos', 'menuNav', 'nombreUsuario'
        ));
    }

    public function actualizar(int $id): void {
        RBAC::requerirPermiso('ordenes.editar');
        $this->validarCSRF();

        if (!$this->model->getById($id)) {
            $this->flash('error', 'Orden no encontrada');
            header('Location: /ordenes');
            exit;
        }

        $datos = [
            'id_medico'     => !empty($_POST['id_medico']) ? (int)$_POST['id_medico'] : null,
            'tipo_atencion' => $this->security->sanitize($_POST['tipo_atencion'] ?? 'normal'),
            'observaciones' => $this->security->sanitize($_POST['observaciones'] ?? ''),
            'metodo_pago'   => $this->security->sanitize($_POST['metodo_pago'] ?? ''),
            'estado_pago'   => $this->security->sanitize($_POST['estado_pago'] ?? 'pendiente'),
        ];

        $resultado = $this->model->actualizar($id, $datos);

        if ($resultado['success']) {
            $this->flash('success', 'Orden actualizada');
            header('Location: /ordenes');
        } else {
            $this->flash('error', $resultado['message']);
            header("Location: /ordenes/editar/$id");
        }
        exit;
    }

    // ─────────────────────────────────────────
    // VALIDAR (cambio de estado → validada)
    // ─────────────────────────────────────────

    public function validar(int $id): void {
        RBAC::requerirPermiso('ordenes.validar');

        $orden = $this->model->getById($id);
        if (!$orden) {
            $this->jsonResponse(['success' => false, 'message' => 'Orden no encontrada']);
            return;
        }

        if (!in_array($orden['estado'], ['resultados_cargados', 'en_proceso'], true)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Solo se pueden validar órdenes con resultados cargados'
            ]);
            return;
        }

        $resultado = $this->model->validar($id);
        $this->jsonResponse($resultado);
    }

    // ─────────────────────────────────────────
    // PUBLICAR (cambio de estado → publicada)
    // ─────────────────────────────────────────

    public function publicar(int $id): void {
        RBAC::requerirPermiso('ordenes.publicar');

        $orden = $this->model->getById($id);
        if (!$orden) {
            $this->jsonResponse(['success' => false, 'message' => 'Orden no encontrada']);
            return;
        }

        if ($orden['estado'] !== 'validada') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'La orden debe estar validada antes de publicarse'
            ]);
            return;
        }

        $resultado = $this->model->publicar($id);

        if ($resultado['success']) {
            // Regenerar token QR al publicar
            $this->facturaModel->generarToken($id);
        }

        $this->jsonResponse($resultado);
    }

    // ─────────────────────────────────────────
    // VER DETALLE (con formulario de validación)
    // ─────────────────────────────────────────

    public function verValidar(int $id): void {
        RBAC::requerirPermiso('ordenes.validar');

        $orden = $this->model->getResultadosOrden($id);
        if (!$orden) {
            $this->flash('error', 'Orden no encontrada');
            header('Location: /ordenes');
            exit;
        }

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('ordenes/validar', compact(
            'orden', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    private function validarCSRF(): void {
        if (!$this->security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->security->logSecurityEvent('CSRF_ATTACK', 'OrdenController');
            $this->flash('error', 'Token de seguridad inválido');
            header('Location: /ordenes');
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
        $_SESSION['flash_type'] = $tipo; $_SESSION['flash_message'] = $mensaje;
    }

    private function consumeFlash(): array {
        $f = ['type' => $_SESSION['flash_type'] ?? null, 'message' => $_SESSION['flash_message'] ?? null];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $f;
    }
}
?>