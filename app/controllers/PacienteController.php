<?php
// app/controllers/PacienteController.php
// ============================================
// CONTROLADOR: Gestión de Pacientes
// Roles: administrador, analistaL
// ============================================

require_once __DIR__ . '/../../core/RBAC.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../models/Paciente.php';

class PacienteController {

    private Security $security;
    private Paciente $model;

    public function __construct() {
        $this->security = Security::getInstance();
        $this->model    = new Paciente();
    }

    // ─────────────────────────────────────────
    // LISTADO
    // ─────────────────────────────────────────

    public function index(): void {
        RBAC::requerirPermiso('pacientes.ver');

        $filtros = [
            'busqueda' => $this->security->sanitize($_GET['q'] ?? ''),
        ];

        $pacientes     = $this->model->listar($filtros);
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('pacientes/index', compact(
            'pacientes', 'filtros', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // CREAR
    // ─────────────────────────────────────────

    public function crear(): void {
        RBAC::requerirPermiso('pacientes.crear');

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];
        $cedula        = $this->security->sanitize($_GET['cedula'] ?? '');

        // Pre-rellenar si viene de búsqueda por cédula
        $pacienteExistente = null;
        if ($cedula) {
            $pacienteExistente = $this->model->buscarPorCedula($cedula);
        }

        $this->renderView('pacientes/crear', compact(
            'menuNav', 'nombreUsuario', 'cedula', 'pacienteExistente'
        ));
    }

    public function guardar(): void {
        RBAC::requerirPermiso('pacientes.crear');
        $this->validarCSRF();

        $datos = $this->sanitizarDatos($_POST);
        $errores = $this->validarDatos($datos);

        if ($errores) {
            $this->flash('error', implode(' | ', $errores));
            header('Location: /pacientes/crear');
            exit;
        }

        $resultado = $this->model->crear($datos);

        if ($resultado['success']) {
            $this->flash('success', 'Paciente registrado exitosamente');

            // Si viene de creación de orden, redirigir allá
            if (!empty($_POST['redirect_orden'])) {
                header('Location: /ordenes/crear?id_paciente=' . $resultado['id_paciente']);
            } else {
                header('Location: /pacientes');
            }
        } else {
            $this->flash('error', $resultado['message']);
            header('Location: /pacientes/crear');
        }
        exit;
    }

    // ─────────────────────────────────────────
    // EDITAR
    // ─────────────────────────────────────────

    public function editar(int $id): void {
        RBAC::requerirPermiso('pacientes.editar');

        $paciente = $this->model->getById($id);
        if (!$paciente) {
            $this->flash('error', 'Paciente no encontrado');
            header('Location: /pacientes');
            exit;
        }

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('pacientes/editar', compact(
            'paciente', 'menuNav', 'nombreUsuario'
        ));
    }

    public function actualizar(int $id): void {
        RBAC::requerirPermiso('pacientes.editar');
        $this->validarCSRF();

        if (!$this->model->getById($id)) {
            $this->flash('error', 'Paciente no encontrado');
            header('Location: /pacientes');
            exit;
        }

        $datos   = $this->sanitizarDatos($_POST);
        $errores = $this->validarDatos($datos, actualizando: true);

        if ($errores) {
            $this->flash('error', implode(' | ', $errores));
            header("Location: /pacientes/editar/$id");
            exit;
        }

        $resultado = $this->model->actualizar($id, $datos);

        if ($resultado['success']) {
            $this->flash('success', 'Datos del paciente actualizados');
            header('Location: /pacientes');
        } else {
            $this->flash('error', $resultado['message']);
            header("Location: /pacientes/editar/$id");
        }
        exit;
    }

    // ─────────────────────────────────────────
    // ELIMINAR (lógico, solo admin)
    // ─────────────────────────────────────────

    public function eliminar(int $id): void {
        RBAC::requerirPermiso('pacientes.eliminar');

        $paciente = $this->model->getById($id);
        if (!$paciente) {
            $this->jsonResponse(['success' => false, 'message' => 'Paciente no encontrado']);
            return;
        }

        $resultado = $this->model->eliminar($id);
        $this->jsonResponse($resultado);
    }

    // ─────────────────────────────────────────
    // HISTORIAL
    // ─────────────────────────────────────────

    public function historial(int $id): void {
        RBAC::requerirPermiso('pacientes.historial');

        $paciente = $this->model->getById($id);
        if (!$paciente) {
            $this->flash('error', 'Paciente no encontrado');
            header('Location: /pacientes');
            exit;
        }

        $historial     = $this->model->getHistorial($id);
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('pacientes/historial', compact(
            'paciente', 'historial', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // AJAX: Búsqueda para autocompletado
    // ─────────────────────────────────────────

    public function buscar(): void {
        RBAC::requerirPermiso('pacientes.ver');
        header('Content-Type: application/json');

        $termino = $this->security->sanitize($_GET['q'] ?? '');
        $tipo    = $_GET['tipo'] ?? 'nombre'; // 'nombre' | 'cedula'

        if (strlen($termino) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        if ($tipo === 'cedula') {
            $paciente = $this->model->buscarPorCedula($termino);
            $data     = $paciente ? [$paciente] : [];
        } else {
            $data = $this->model->buscarPorNombre($termino);
        }

        // Solo devolver campos necesarios (no datos sensibles extra)
        $resultado = array_map(fn($p) => [
            'id_paciente'      => $p['id_paciente'],
            'cedula'           => $p['cedula'],
            'nombres'          => $p['nombres'],
            'apellidos'        => $p['apellidos'],
            'nombre_completo'  => trim(($p['nombres'] ?? '') . ' ' . ($p['apellidos'] ?? '')),
            'fecha_nacimiento' => $p['fecha_nacimiento'] ?? null,
            'genero'           => $p['genero'] ?? null,
        ], $data);

        echo json_encode(['success' => true, 'data' => $resultado]);
        exit;
    }

    // ─────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────

    private function sanitizarDatos(array $post): array {
        return [
            'cedula'           => $this->security->sanitize($post['cedula'] ?? ''),
            'nombres'          => $this->security->sanitize($post['nombres'] ?? ''),
            'apellidos'        => $this->security->sanitize($post['apellidos'] ?? ''),
            'fecha_nacimiento' => !empty($post['fecha_nacimiento']) ? $post['fecha_nacimiento'] : null,
            'genero'           => in_array($post['genero'] ?? '', ['M','F','Otro']) ? $post['genero'] : null,
            'telefono'         => $this->security->sanitize($post['telefono'] ?? ''),
            'email'            => $this->security->sanitize($post['email'] ?? ''),
            'direccion'        => $this->security->sanitize($post['direccion'] ?? ''),
            'tipo_sangre'      => $this->security->sanitize($post['tipo_sangre'] ?? ''),
            'alergias'         => $this->security->sanitize($post['alergias'] ?? ''),
            'observaciones'    => $this->security->sanitize($post['observaciones'] ?? ''),
        ];
    }

    private function validarDatos(array $datos, bool $actualizando = false): array {
        $errores = [];

        if (!$actualizando && empty($datos['cedula'])) {
            $errores[] = 'La cédula es obligatoria';
        }
        if (!$actualizando && !$this->security->validateCedulaEcuador($datos['cedula'])) {
            $errores[] = 'La cédula ingresada no es válida';
        }
        if (empty($datos['nombres'])) {
            $errores[] = 'El nombre es obligatorio';
        }
        if (empty($datos['apellidos'])) {
            $errores[] = 'Los apellidos son obligatorios';
        }
        if (!empty($datos['email']) && !$this->security->validateEmail($datos['email'])) {
            $errores[] = 'El email no tiene un formato válido';
        }

        return $errores;
    }

    private function validarCSRF(): void {
        if (!$this->security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->security->logSecurityEvent('CSRF_ATTACK', 'PacienteController');
            $this->flash('error', 'Token de seguridad inválido');
            header('Location: /pacientes');
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
        $f = ['type' => $_SESSION['flash_type'] ?? null, 'message' => $_SESSION['flash_message'] ?? null];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $f;
    }
}
?>