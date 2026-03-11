<?php
// app/controllers/ResultadoController.php
// ============================================
// CONTROLADOR: Carga, validación y alertas de resultados
// Roles: cargar/editar → administrador, analistaL | ver → + medico
// ============================================

require_once __DIR__ . '/../../core/RBAC.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../models/Resultados.php';
require_once __DIR__ . '/../models/Orden.php';
require_once __DIR__ . '/../models/Examen.php';

class ResultadoController {

    private Security   $security;
    private Resultados $model;
    private Orden      $ordenModel;
    private Examen     $examenModel;

    private const UPLOAD_DIR = STORAGE_PATH . '/uploads/importaciones/';

    public function __construct() {
        $this->security    = Security::getInstance();
        $this->model       = new Resultados();
        $this->ordenModel  = new Orden();
        $this->examenModel = new Examen();
    }

    // ─────────────────────────────────────────
    // ÍNDICE: Listado de órdenes con resultados
    // ─────────────────────────────────────────

    public function index(): void {
        RBAC::requerirPermiso('resultados.ver_completo');

        $filtros = [
            'estado'        => $_GET['estado']         ?? '',
            'solo_criticos' => !empty($_GET['criticos']) ? 1 : 0,
            'fecha_desde'   => $_GET['desde']          ?? date('Y-m-01'),
            'fecha_hasta'   => $_GET['hasta']          ?? date('Y-m-d'),
            'numero_orden'  => $this->security->sanitize($_GET['q'] ?? ''),
        ];

        $resultados    = $this->model->listar($filtros);
        $alertas       = $this->model->getCriticosPendientes();
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('resultados/cargar_manual', compact(
            'resultados', 'alertas', 'filtros', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // CARGA MANUAL
    // ─────────────────────────────────────────

    /**
     * Mostrar formulario de carga manual para una orden específica.
     * Ruta GET: /resultados/cargar?orden={id}
     */
    public function cargarManual(): void {
        RBAC::requerirPermiso('resultados.cargar_manual');

        $idOrden = (int)($_GET['orden'] ?? 0);

        if (!$idOrden) {
            $this->flash('error', 'Debe indicar una orden');
            header('Location: /resultados');
            exit;
        }

        $orden = $this->ordenModel->getById($idOrden);
        if (!$orden) {
            $this->flash('error', 'Orden no encontrada');
            header('Location: /resultados');
            exit;
        }

        // Para cada examen de la orden, obtener sus parámetros con resultados previos
        $examenesConParametros = [];
        foreach ($orden['examenes'] as $ex) {
            $parametros = $this->examenModel->getParametros($ex['id_examen']);
            $resultadosPrevios = $this->model->getByOrdenExamen($ex['id_orden_examen']);

            // Indexar previos por id_parametro para fácil acceso en la vista
            $previosPorParam = [];
            foreach ($resultadosPrevios as $r) {
                $previosPorParam[$r['id_parametro']] = $r;
            }

            $examenesConParametros[] = [
                'id_orden_examen' => $ex['id_orden_examen'],
                'nombre_examen'   => $ex['nombre_examen'],
                'codigo'          => $ex['codigo'],
                'categoria'       => $ex['categoria'],
                'estado'          => $ex['estado_examen'] ?? 'pendiente',
                'parametros'      => $parametros,
                'previos'         => $previosPorParam,
            ];
        }

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('resultados/cargar_manual', compact(
            'orden', 'examenesConParametros', 'menuNav', 'nombreUsuario'
        ));
    }

    public function guardarManual(): void {
        RBAC::requerirPermiso('resultados.cargar_manual');
        $this->validarCSRF();

        $idOrden = (int)($_POST['id_orden'] ?? 0);

        if (!$idOrden || !$this->ordenModel->getById($idOrden)) {
            $this->flash('error', 'Orden no válida');
            header('Location: /resultados');
            exit;
        }

        // $_POST['resultados'] = [['id_orden_examen'=>X, 'id_parametro'=>Y, 'valor'=>Z], ...]
        $resultados = $_POST['resultados'] ?? [];

        if (empty($resultados)) {
            $this->flash('warning', 'No se enviaron valores para guardar');
            header("Location: /resultados/cargar?orden=$idOrden");
            exit;
        }

        $resultado = $this->model->guardarManual($resultados, $idOrden);

        if ($resultado['success']) {
            $tipo = $resultado['criticos'] > 0 ? 'warning' : 'success';
            $this->flash($tipo, $resultado['message']);
            header("Location: /resultados/cargar?orden=$idOrden");
        } else {
            $this->flash('error', $resultado['message']);
            header("Location: /resultados/cargar?orden=$idOrden");
        }
        exit;
    }

    // ─────────────────────────────────────────
    // CARGA AUTOMÁTICA (importación CSV)
    // ─────────────────────────────────────────

    public function cargarAutomatico(): void {
        RBAC::requerirPermiso('resultados.cargar_auto');

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        // Preview de importación previa en sesión
        $preview   = $_SESSION['importacion_preview'] ?? null;
        $erroresIm = $_SESSION['importacion_errores'] ?? [];

        $this->renderView('resultados/cargar_automatico', compact(
            'menuNav', 'nombreUsuario', 'preview', 'erroresIm'
        ));
    }

    public function procesarImportacion(): void {
        RBAC::requerirPermiso('resultados.cargar_auto');
        $this->validarCSRF();

        $accion = $_POST['accion'] ?? 'procesar'; // 'procesar' | 'confirmar' | 'cancelar'

        if ($accion === 'cancelar') {
            unset($_SESSION['importacion_preview'], $_SESSION['importacion_errores']);
            $this->flash('info', 'Importación cancelada');
            header('Location: /resultados/cargar-automatico');
            exit;
        }

        if ($accion === 'confirmar') {
            // Confirmar filas ya procesadas que están en sesión
            $filas = $_SESSION['importacion_preview'] ?? [];
            if (empty($filas)) {
                $this->flash('error', 'No hay datos para confirmar');
                header('Location: /resultados/cargar-automatico');
                exit;
            }

            $resultado = $this->model->confirmarImportacion($filas);
            unset($_SESSION['importacion_preview'], $_SESSION['importacion_errores']);

            $tipo = $resultado['criticos'] > 0 ? 'warning' : 'success';
            $this->flash($tipo, $resultado['message']);
            header('Location: /resultados');
            exit;
        }

        // === PROCESAR: subir y parsear archivo ===
        if (empty($_FILES['archivo']['tmp_name'])) {
            $this->flash('error', 'Debe seleccionar un archivo CSV');
            header('Location: /resultados/cargar-automatico');
            exit;
        }

        $file    = $_FILES['archivo'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $maxSize = 5 * 1024 * 1024; // 5 MB

        if (!in_array($ext, ['csv', 'txt'], true)) {
            $this->flash('error', 'Solo se aceptan archivos CSV');
            header('Location: /resultados/cargar-automatico');
            exit;
        }

        if ($file['size'] > $maxSize) {
            $this->flash('error', 'El archivo no debe superar 5 MB');
            header('Location: /resultados/cargar-automatico');
            exit;
        }

        // Guardar temporalmente
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0750, true);
        }

        $tmpPath = self::UPLOAD_DIR . 'import_' . time() . '_' . bin2hex(random_bytes(4)) . '.csv';
        move_uploaded_file($file['tmp_name'], $tmpPath);

        $resultado = $this->model->procesarImportacion($tmpPath);
        @unlink($tmpPath);

        if (!$resultado['success']) {
            $this->flash('error', $resultado['message']);
            header('Location: /resultados/cargar-automatico');
            exit;
        }

        // Guardar preview en sesión para confirmar
        $_SESSION['importacion_preview'] = $resultado['preview'];
        $_SESSION['importacion_errores'] = $resultado['errores'];

        $this->flash('info', "Se procesaron {$resultado['total']} resultado(s). Revise el preview y confirme.");
        header('Location: /resultados/cargar-automatico');
        exit;
    }

    // ─────────────────────────────────────────
    // EDITAR RESULTADO INDIVIDUAL
    // ─────────────────────────────────────────

    public function editar(int $idResultado): void {
        RBAC::requerirPermiso('resultados.editar');

        $db     = Database::getInstance();
        $result = $db->queryOne(
            "SELECT r.*, pe.nombre_parametro, pe.unidad_medida,
                    oe.id_orden, e.nombre AS nombre_examen
             FROM resultados r
             JOIN parametros_examen pe ON r.id_parametro = pe.id_parametro
             JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
             JOIN examenes e ON oe.id_examen = e.id_examen
             WHERE r.id_resultado = ?",
            [$idResultado]
        );

        if (!$result) {
            $this->flash('error', 'Resultado no encontrado');
            header('Location: /resultados');
            exit;
        }

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('resultados/validar', compact(
            'result', 'menuNav', 'nombreUsuario'
        ));
    }

    public function actualizar(int $idResultado): void {
        RBAC::requerirPermiso('resultados.editar');
        $this->validarCSRF();

        $nuevoValor = $this->security->sanitize($_POST['valor_resultado'] ?? '');
        $db         = Database::getInstance();

        $result = $db->queryOne(
            "SELECT r.id_parametro, oe.id_orden FROM resultados r
             JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
             WHERE r.id_resultado = ?",
            [$idResultado]
        );

        if (!$result) {
            $this->flash('error', 'Resultado no encontrado');
            header('Location: /resultados');
            exit;
        }

        // Re-evaluar criticidad con el nuevo valor
        $this->model->guardarManual([[
            'id_orden_examen' => $db->queryOne(
                "SELECT id_orden_examen FROM resultados WHERE id_resultado = ?",
                [$idResultado]
            )['id_orden_examen'],
            'id_parametro' => $result['id_parametro'],
            'valor'        => $nuevoValor,
        ]], $result['id_orden']);

        $this->flash('success', 'Resultado actualizado');
        header('Location: /resultados/cargar?orden=' . $result['id_orden']);
        exit;
    }

    // ─────────────────────────────────────────
    // VALIDAR ORDEN COMPLETA
    // ─────────────────────────────────────────

    public function validar(int $idOrden): void {
        RBAC::requerirPermiso('resultados.validar');

        $orden = $this->ordenModel->getById($idOrden);
        if (!$orden) {
            $this->jsonResponse(['success' => false, 'message' => 'Orden no encontrada']);
            return;
        }

        $resultado = $this->model->validarOrden($idOrden);
        $this->jsonResponse($resultado);
    }

    // ─────────────────────────────────────────
    // ALERTAS CRÍTICAS
    // ─────────────────────────────────────────

    public function alertasCriticas(): void {
        RBAC::requerirPermiso('resultados.ver_alertas');

        $alertas       = $this->model->getCriticosPendientes();
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('resultados/alertas', compact(
            'alertas', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // VISTA MÉDICO (delegada a ConsultaPublicaController)
    // ─────────────────────────────────────────

    public function vistaMedico(): void {
        require_once __DIR__ . '/ConsultaPublicaController.php';
        (new ConsultaPublicaController())->vistaMedico();
    }

    public function detalleMedico(int $idOrden): void {
        require_once __DIR__ . '/ConsultaPublicaController.php';
        (new ConsultaPublicaController())->detalleMedico($idOrden);
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    private function validarCSRF(): void {
        if (!$this->security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->security->logSecurityEvent('CSRF_ATTACK', 'ResultadoController');
            $this->flash('error', 'Token de seguridad inválido');
            header('Location: /resultados');
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