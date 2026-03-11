<?php
// app/controllers/ExamenController.php
// ============================================
// CONTROLADOR: Catálogo de Exámenes, Categorías y Parámetros
// Roles: ver → administrador/analistaL | crear/editar/eliminar → administrador
// ============================================

require_once __DIR__ . '/../../core/RBAC.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../models/Examen.php';

class ExamenController {

    private Security $security;
    private Examen   $model;

    public function __construct() {
        $this->security = Security::getInstance();
        $this->model    = new Examen();
    }

    // ─────────────────────────────────────────
    // LISTADO PRINCIPAL
    // ─────────────────────────────────────────

    public function index(): void {
        RBAC::requerirPermiso('examenes.ver');

        $filtros = [
            'id_categoria' => $_GET['categoria'] ?? null,
            'busqueda'     => $this->security->sanitize($_GET['q'] ?? ''),
            'activo'       => $_GET['activo'] ?? null,
        ];

        $examenes   = $this->model->listar($filtros);
        $categorias = $this->model->listarCategorias();
        $menuNav    = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('examenes/index', compact(
            'examenes', 'categorias', 'filtros', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // CREAR
    // ─────────────────────────────────────────

    public function crear(): void {
        RBAC::requerirPermiso('examenes.crear');

        $categorias    = $this->model->listarCategorias();
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('examenes/crear', compact('categorias', 'menuNav', 'nombreUsuario'));
    }

    public function guardar(): void {
        RBAC::requerirPermiso('examenes.crear');
        $this->validarCSRF();

        $datos = $this->sanitizarDatosExamen($_POST);

        // Validaciones básicas
        if (empty($datos['nombre']) || empty($datos['id_categoria'])) {
            $this->flash('error', 'El nombre y la categoría son obligatorios');
            header('Location: /examenes/crear');
            exit;
        }

        // Verificar código único si se proporcionó
        if (!empty($datos['codigo']) && $this->model->existeCodigo($datos['codigo'])) {
            $this->flash('error', 'El código de examen ya existe');
            header('Location: /examenes/crear');
            exit;
        }

        $resultado = $this->model->crear($datos);

        if ($resultado['success']) {
            $this->flash('success', 'Examen "' . $datos['nombre'] . '" creado exitosamente');
            header('Location: /examenes/parametros/' . $resultado['id_examen']);
        } else {
            $this->flash('error', $resultado['message']);
            header('Location: /examenes/crear');
        }
        exit;
    }

    // ─────────────────────────────────────────
    // EDITAR
    // ─────────────────────────────────────────

    public function editar(int $id): void {
        RBAC::requerirPermiso('examenes.editar');

        $examen = $this->model->getById($id);
        if (!$examen) {
            $this->flash('error', 'Examen no encontrado');
            header('Location: /examenes');
            exit;
        }

        $categorias    = $this->model->listarCategorias();
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('examenes/editar', compact(
            'examen', 'categorias', 'menuNav', 'nombreUsuario'
        ));
    }

    public function actualizar(int $id): void {
        RBAC::requerirPermiso('examenes.editar');
        $this->validarCSRF();

        $datos = $this->sanitizarDatosExamen($_POST);

        if (empty($datos['nombre']) || empty($datos['id_categoria'])) {
            $this->flash('error', 'El nombre y la categoría son obligatorios');
            header("Location: /examenes/editar/$id");
            exit;
        }

        // Verificar código único (excluyendo el propio)
        if (!empty($datos['codigo']) && $this->model->existeCodigo($datos['codigo'], $id)) {
            $this->flash('error', 'El código ya pertenece a otro examen');
            header("Location: /examenes/editar/$id");
            exit;
        }

        $resultado = $this->model->actualizar($id, $datos);

        if ($resultado['success']) {
            $this->flash('success', 'Examen actualizado correctamente');
            header('Location: /examenes');
        } else {
            $this->flash('error', $resultado['message']);
            header("Location: /examenes/editar/$id");
        }
        exit;
    }

    // ─────────────────────────────────────────
    // PARÁMETROS Y RANGOS
    // ─────────────────────────────────────────

    public function parametros(int $id): void {
        RBAC::requerirPermiso('examenes.parametros');

        $examen = $this->model->getById($id);
        if (!$examen) {
            $this->flash('error', 'Examen no encontrado');
            header('Location: /examenes');
            exit;
        }

        $parametros    = $this->model->getParametros($id);
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('examenes/parametros', compact(
            'examen', 'parametros', 'menuNav', 'nombreUsuario'
        ));
    }

    public function guardarParametros(int $id): void {
        RBAC::requerirPermiso('examenes.parametros');
        $this->validarCSRF();

        if (!$this->model->getById($id)) {
            $this->flash('error', 'Examen no encontrado');
            header('Location: /examenes');
            exit;
        }

        $parametros = $_POST['parametros'] ?? [];

        if (empty($parametros)) {
            $this->flash('warning', 'No se enviaron parámetros');
            header("Location: /examenes/parametros/$id");
            exit;
        }

        $resultado = $this->model->guardarParametros($id, $parametros);

        if ($resultado['success']) {
            $this->flash('success', 'Parámetros y rangos guardados exitosamente');
            header("Location: /examenes/parametros/$id");
        } else {
            $this->flash('error', $resultado['message']);
            header("Location: /examenes/parametros/$id");
        }
        exit;
    }

    // ─────────────────────────────────────────
    // ELIMINAR (lógico)
    // ─────────────────────────────────────────

    public function eliminar(int $id): void {
        RBAC::requerirPermiso('examenes.eliminar');

        $examen = $this->model->getById($id);
        if (!$examen) {
            $this->jsonResponse(['success' => false, 'message' => 'Examen no encontrado']);
            return;
        }

        $resultado = $this->model->eliminar($id);
        $this->jsonResponse($resultado);
    }

    // ─────────────────────────────────────────
    // AJAX: Listar por categoría (para selectores en órdenes/cotizaciones)
    // ─────────────────────────────────────────

    public function listarPorCategoria(): void {
        // Accesible para admin y analista
        RBAC::requerirPermiso('examenes.ver');

        $data = $this->model->listarPorCategoria();
        $this->jsonResponse(['success' => true, 'data' => $data]);
    }

    // ─────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────

    private function sanitizarDatosExamen(array $post): array {
        return [
            'codigo'                 => $this->security->sanitize($post['codigo'] ?? ''),
            'nombre'                 => $this->security->sanitize($post['nombre'] ?? ''),
            'id_categoria'           => (int)($post['id_categoria'] ?? 0),
            'descripcion'            => $this->security->sanitize($post['descripcion'] ?? ''),
            'precio'                 => !empty($post['precio']) ? (float)$post['precio'] : null,
            'tiempo_entrega_min'     => !empty($post['tiempo_entrega_min']) ? (int)$post['tiempo_entrega_min'] : null,
            'tiempo_entrega_dias'    => !empty($post['tiempo_entrega_dias']) ? (int)$post['tiempo_entrega_dias'] : null,
            'requiere_ayuno'         => isset($post['requiere_ayuno']) ? 1 : 0,
            'instrucciones_paciente' => $this->security->sanitize($post['instrucciones_paciente'] ?? ''),
            'metodo_analisis'        => $this->security->sanitize($post['metodo_analisis'] ?? ''),
            'activo'                 => isset($post['activo']) ? 1 : 0,
        ];
    }

    private function validarCSRF(): void {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->security->validateCSRFToken($token)) {
            $this->security->logSecurityEvent('CSRF_ATTACK', 'ExamenController');
            $this->flash('error', 'Token de seguridad inválido. Intente de nuevo.');
            header('Location: /examenes');
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