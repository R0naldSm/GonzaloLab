<?php
// app/controllers/UsuarioController.php
// ============================================
// CONTROLADOR: Administración de Usuarios del sistema
// Rol exclusivo: administrador
// ============================================

require_once __DIR__ . '/../../core/RBAC.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../models/usuario.php';
require_once __DIR__ . '/../models/Auditoria.php';

class UsuarioController {

    private Security  $security;
    private Usuario   $model;
    private Auditoria $auditoriaModel;

    public function __construct() {
        $this->security       = Security::getInstance();
        $this->model          = new Usuario();
        $this->auditoriaModel = new Auditoria();
    }

    // ─────────────────────────────────────────
    // LISTADO
    // ─────────────────────────────────────────

    public function index(): void {
        RBAC::requerirPermiso('usuarios.ver');

        $filtros = [
            'rol'      => $_GET['rol']    ?? '',
            'estado'   => $_GET['estado'] ?? '',
            'busqueda' => $this->security->sanitize($_GET['q'] ?? ''),
        ];

        $usuarios      = $this->model->listar($filtros);
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        // Estadísticas rápidas
        $db     = Database::getInstance();
        $stats  = $db->queryOne(
            "SELECT
                COUNT(*) AS total,
                SUM(rol = 'administrador') AS admins,
                SUM(rol = 'analistaL') AS analistas,
                SUM(rol = 'medico') AS medicos,
                SUM(rol = 'paciente') AS pacientes,
                SUM(estado = 'activo') AS activos,
                SUM(estado = 'bloqueado') AS bloqueados
             FROM usuarios WHERE eliminado = 0"
        );

        $this->renderView('usuarios/index', compact(
            'usuarios', 'filtros', 'stats', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // CREAR
    // ─────────────────────────────────────────

    public function crear(): void {
        RBAC::requerirPermiso('usuarios.crear');

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];
        $rolesDisponibles = ['administrador', 'analistaL', 'medico', 'paciente'];

        $this->renderView('usuarios/crear', compact(
            'menuNav', 'nombreUsuario', 'rolesDisponibles'
        ));
    }

    public function guardar(): void {
        RBAC::requerirPermiso('usuarios.crear');
        $this->validarCSRF();

        $datos   = $this->sanitizarDatos($_POST);
        $errores = $this->validarDatosCrear($datos);

        if ($errores) {
            $this->flash('error', implode(' | ', $errores));
            header('Location: /usuarios/crear');
            exit;
        }

        // Asignar contraseña temporal si no se ingresó
        $passwordTemporal = false;
        if (empty($_POST['password'])) {
            $datos['password'] = $this->generarPasswordTemporal();
            $passwordTemporal  = $datos['password'];
        } else {
            $datos['password'] = $_POST['password'];
        }

        $datos['creado_por'] = $_SESSION['user_id'];

        $resultado = $this->model->crear($datos);

        if ($resultado['success']) {
            $msg = 'Usuario creado exitosamente.';
            if ($passwordTemporal) {
                $msg .= ' Contraseña temporal: <strong>' . $passwordTemporal . '</strong>';
            }
            $this->flash('success', $msg);

            $this->auditoriaModel->registrar('usuarios', 'INSERT', $resultado['id_usuario'],
                null, ['username' => $datos['username'], 'rol' => $datos['rol']]);

            header('Location: /usuarios');
        } else {
            $this->flash('error', $resultado['message']);
            header('Location: /usuarios/crear');
        }
        exit;
    }

    // ─────────────────────────────────────────
    // EDITAR
    // ─────────────────────────────────────────

    public function editar(int $id): void {
        RBAC::requerirPermiso('usuarios.editar');

        $usuario = $this->model->findById($id);
        if (!$usuario) {
            $this->flash('error', 'Usuario no encontrado');
            header('Location: /usuarios');
            exit;
        }

        // No permitir editar al propio administrador logueado aquí (use perfil)
        if ($id === (int)$_SESSION['user_id']) {
            $this->flash('warning', 'Para editar su propio perfil use la sección de perfil');
            header('Location: /usuarios');
            exit;
        }

        $menuNav          = RBAC::getMenu();
        $nombreUsuario    = $_SESSION['nombre_completo'] ?? $_SESSION['username'];
        $rolesDisponibles = ['administrador', 'analistaL', 'medico', 'paciente'];

        $this->renderView('usuarios/editar', compact(
            'usuario', 'menuNav', 'nombreUsuario', 'rolesDisponibles'
        ));
    }

    public function actualizar(int $id): void {
        RBAC::requerirPermiso('usuarios.editar');
        $this->validarCSRF();

        $usuarioActual = $this->model->findById($id);
        if (!$usuarioActual) {
            $this->flash('error', 'Usuario no encontrado');
            header('Location: /usuarios');
            exit;
        }

        $datos   = $this->sanitizarDatos($_POST);
        $errores = $this->validarDatosEditar($datos, $id);

        if ($errores) {
            $this->flash('error', implode(' | ', $errores));
            header("Location: /usuarios/editar/$id");
            exit;
        }

        $db  = Database::getInstance();
        $key = $this->security->getMySQLEncryptionKey();

        $db->execute(
            "UPDATE usuarios SET
                username = ?, email = ?,
                nombre_completo = AES_ENCRYPT(?, '$key'),
                cedula = AES_ENCRYPT(?, '$key'),
                rol = ?, estado = ?
             WHERE id_usuario = ? AND eliminado = 0",
            [
                $datos['username'],
                $datos['email'],
                $datos['nombre_completo'],
                $datos['cedula'],
                $datos['rol'],
                $datos['estado'],
                $id,
            ]
        );

        $this->auditoriaModel->registrar('usuarios', 'UPDATE', $id,
            ['rol' => $usuarioActual['rol'], 'estado' => $usuarioActual['estado']],
            ['rol' => $datos['rol'], 'estado' => $datos['estado']]
        );

        $this->flash('success', 'Usuario actualizado correctamente');
        header('Location: /usuarios');
        exit;
    }

    // ─────────────────────────────────────────
    // DESACTIVAR (eliminación lógica)
    // ─────────────────────────────────────────

    public function desactivar(int $id): void {
        RBAC::requerirPermiso('usuarios.desactivar');

        if ($id === (int)$_SESSION['user_id']) {
            $this->jsonResponse(['success' => false, 'message' => 'No puede desactivar su propio usuario']);
            return;
        }

        $usuario = $this->model->findById($id);
        if (!$usuario) {
            $this->jsonResponse(['success' => false, 'message' => 'Usuario no encontrado']);
            return;
        }

        $resultado = $this->model->desactivar($id);
        $this->auditoriaModel->registrar('usuarios', 'DELETE', $id);

        $this->jsonResponse(['success' => true, 'message' => 'Usuario desactivado']);
    }

    // ─────────────────────────────────────────
    // RESETEAR CONTRASEÑA
    // ─────────────────────────────────────────

    public function resetearClave(int $id): void {
        RBAC::requerirPermiso('usuarios.resetear_clave');

        $usuario = $this->model->findById($id);
        if (!$usuario) {
            $this->jsonResponse(['success' => false, 'message' => 'Usuario no encontrado']);
            return;
        }

        $nuevaClave = $this->generarPasswordTemporal();
        $hash       = $this->security->hashPassword($nuevaClave);

        $db = Database::getInstance();
        $db->execute(
            "UPDATE usuarios SET password_hash = ?, token_recuperacion = NULL,
                token_expiracion = NULL, intentos_fallidos = 0,
                estado = CASE WHEN estado = 'bloqueado' THEN 'activo' ELSE estado END
             WHERE id_usuario = ?",
            [$hash, $id]
        );

        $this->auditoriaModel->registrar('usuarios', 'UPDATE', $id,
            null, ['accion' => 'reset_password']
        );

        $this->jsonResponse([
            'success'       => true,
            'nueva_clave'   => $nuevaClave,
            'message'       => 'Contraseña reseteada. Entregue la nueva clave al usuario de forma segura.',
        ]);
    }

    // ─────────────────────────────────────────
    // DESBLOQUEAR CUENTA
    // ─────────────────────────────────────────

    public function desbloquear(int $id): void {
        RBAC::requerirPermiso('usuarios.editar');

        $db = Database::getInstance();
        $db->execute(
            "UPDATE usuarios SET estado = 'activo', intentos_fallidos = 0 WHERE id_usuario = ?",
            [$id]
        );

        $this->auditoriaModel->registrar('usuarios', 'UPDATE', $id,
            null, ['accion' => 'desbloquear']
        );

        $this->jsonResponse(['success' => true, 'message' => 'Cuenta desbloqueada']);
    }

    // ─────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────

    private function sanitizarDatos(array $post): array {
        return [
            'username'       => $this->security->sanitize($post['username'] ?? ''),
            'email'          => $this->security->sanitize($post['email'] ?? ''),
            'nombre_completo'=> $this->security->sanitize($post['nombre_completo'] ?? ''),
            'cedula'         => $this->security->sanitize($post['cedula'] ?? ''),
            'rol'            => in_array($post['rol'] ?? '', ['administrador','analistaL','medico','paciente'])
                                ? $post['rol'] : 'analistaL',
            'estado'         => in_array($post['estado'] ?? '', ['activo','inactivo','bloqueado'])
                                ? $post['estado'] : 'activo',
        ];
    }

    private function validarDatosCrear(array $datos): array {
        $errores = [];
        if (empty($datos['username'])) $errores[] = 'El nombre de usuario es obligatorio';
        if (strlen($datos['username']) < 4) $errores[] = 'El username debe tener al menos 4 caracteres';
        if (empty($datos['email'])) $errores[] = 'El email es obligatorio';
        if (!$this->security->validateEmail($datos['email'])) $errores[] = 'Email inválido';
        if (empty($datos['nombre_completo'])) $errores[] = 'El nombre completo es obligatorio';

        // Verificar unicidad
        if ($this->model->findByUsername($datos['username'])) {
            $errores[] = 'El nombre de usuario ya existe';
        }
        if ($this->model->findByEmail($datos['email'])) {
            $errores[] = 'El email ya está registrado';
        }

        // Validar contraseña si se proporcionó
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 8) {
                $errores[] = 'La contraseña debe tener al menos 8 caracteres';
            }
            if ($_POST['password'] !== ($_POST['confirmar_password'] ?? '')) {
                $errores[] = 'Las contraseñas no coinciden';
            }
        }

        return $errores;
    }

    private function validarDatosEditar(array $datos, int $id): array {
        $errores = [];
        if (empty($datos['nombre_completo'])) $errores[] = 'El nombre completo es obligatorio';
        if (empty($datos['email'])) $errores[] = 'El email es obligatorio';
        if (!$this->security->validateEmail($datos['email'])) $errores[] = 'Email inválido';

        // Verificar que el username no lo tenga otro usuario
        $otroUser = $this->model->findByUsername($datos['username']);
        if ($otroUser && (int)$otroUser['id_usuario'] !== $id) {
            $errores[] = 'El username ya pertenece a otro usuario';
        }

        return $errores;
    }

    private function generarPasswordTemporal(): string {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#$!';
        $pwd   = '';
        for ($i = 0; $i < 10; $i++) {
            $pwd .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $pwd;
    }

    private function validarCSRF(): void {
        if (!$this->security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->security->logSecurityEvent('CSRF_ATTACK', 'UsuarioController');
            $this->flash('error', 'Token de seguridad inválido');
            header('Location: /usuarios');
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