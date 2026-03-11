<?php
// app/controllers/AuthController.php
// ============================================
// CONTROLADOR DE AUTENTICACIÓN
// ============================================

require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../../core/RBAC.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    
    private $security;
    private $usuarioModel;
    
    public function __construct() {
        $this->security = Security::getInstance();
        $this->security->initSecureSession();
        $this->usuarioModel = new Usuario();
    }
    
    /**
     * Mostrar formulario de login
     */
    public function mostrarLogin() {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->security->isValidSession()) {
            header('Location: /dashboard');
            exit;
        }
        
        require_once __DIR__ . '/../views/auth/login.php';
    }
    
    /**
     * Procesar login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }
        
        // Validar CSRF token
        if (!isset($_POST['csrf_token']) || !$this->security->validateCSRFToken($_POST['csrf_token'])) {
            $this->security->logSecurityEvent('CSRF_ATTACK', 'Token CSRF inválido en login');
            $this->redirect('/login', 'error', 'Token de seguridad inválido');
            return;
        }
        
        // Sanitizar inputs
        $username = $this->security->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $recordar = isset($_POST['recordar']);
        
        // Validar campos vacíos
        if (empty($username) || empty($password)) {
            $this->redirect('/login', 'error', 'Complete todos los campos');
            return;
        }
        
        // Verificar posible SQL injection
        if ($this->security->containsSQLInjection($username)) {
            $this->security->logSecurityEvent('SQL_INJECTION_ATTEMPT', "Usuario: $username");
            $this->redirect('/login', 'error', 'Datos inválidos');
            return;
        }
        
        // Validar credenciales
        $resultado = $this->usuarioModel->validarLogin($username, $password);
        
        if (!$resultado['success']) {
            $this->redirect('/login', 'error', $resultado['message']);
            return;
        }
        
        // Login exitoso - Crear sesión
        $usuario = $resultado['usuario'];
        
        $_SESSION['user_id'] = $usuario['id_usuario'];
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['user_rol'] = $usuario['rol'];
        $_SESSION['nombre_completo'] = $usuario['nombre_completo'];
        $_SESSION['authenticated'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        
        // Cookie "recordarme" (opcional)
        if ($recordar) {
            $token = $this->security->generateAccessToken();
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
            // TODO: Guardar token en BD para validación futura
        }
        
        // Registrar en auditoría
        $this->registrarAuditoria('LOGIN', $usuario['id_usuario'], $usuario['username']);
        
        // Redirigir según rol
        $this->redirigirPorRol($usuario['rol']);
    }
    
    /**
     * Logout
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $username = $_SESSION['username'] ?? 'unknown';
            
            // Registrar logout
            $this->registrarAuditoria('LOGOUT', $userId, $username);
        }
        
        // Destruir sesión
        $this->security->destroySession();
        
        // Eliminar cookie de recordar
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        $this->redirect('/login', 'success', 'Sesión cerrada correctamente');
    }
    
    /**
     * Mostrar formulario recuperar contraseña
     */
    public function mostrarRecuperar() {
        require_once __DIR__ . '/../views/auth/recuperar_password.php';
    }
    
    /**
     * Procesar solicitud de recuperación
     */
    public function procesarRecuperacion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /recuperar-password');
            exit;
        }
        
        // Validar CSRF
        if (!$this->security->validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->redirect('/recuperar-password', 'error', 'Token inválido');
            return;
        }
        
        $email = $this->security->sanitize($_POST['email'] ?? '');
        
        if (empty($email) || !$this->security->validateEmail($email)) {
            $this->redirect('/recuperar-password', 'error', 'Email inválido');
            return;
        }
        
        // Generar token
        $resultado = $this->usuarioModel->generarTokenRecuperacion($email);
        
        if ($resultado['success']) {
            // Enviar email (implementar función de envío)
            $this->enviarEmailRecuperacion($resultado['usuario'], $resultado['token']);
            
            $mensaje = 'Si el email existe, recibirá un enlace de recuperación';
        } else {
            // No revelar si el email existe o no
            $mensaje = 'Si el email existe, recibirá un enlace de recuperación';
        }
        
        $this->redirect('/login', 'info', $mensaje);
    }
    
    /**
     * Mostrar formulario de nueva contraseña
     */
    public function mostrarCambiarPassword() {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            $this->redirect('/login', 'error', 'Token inválido');
            return;
        }
        
        // Validar token
        $usuario = $this->usuarioModel->validarTokenRecuperacion($token);
        
        if (!$usuario) {
            $this->redirect('/login', 'error', 'El enlace ha expirado o es inválido');
            return;
        }
        
        require_once __DIR__ . '/../views/auth/cambiar_password.php';
    }
    
    /**
     * Procesar cambio de contraseña
     */
    public function cambiarPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }
        
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmar = $_POST['confirmar_password'] ?? '';
        
        // Validaciones
        if (empty($password) || empty($confirmar)) {
            $this->redirect("/cambiar-password?token=$token", 'error', 'Complete todos los campos');
            return;
        }
        
        if ($password !== $confirmar) {
            $this->redirect("/cambiar-password?token=$token", 'error', 'Las contraseñas no coinciden');
            return;
        }
        
        if (strlen($password) < 8) {
            $this->redirect("/cambiar-password?token=$token", 'error', 'La contraseña debe tener al menos 8 caracteres');
            return;
        }
        
        // Cambiar contraseña
        $resultado = $this->usuarioModel->cambiarPasswordConToken($token, $password);
        
        if ($resultado['success']) {
            $this->redirect('/login', 'success', 'Contraseña actualizada. Ya puede iniciar sesión');
        } else {
            $this->redirect('/login', 'error', $resultado['message']);
        }
    }
    
    /**
     * Verificar si está autenticado (para AJAX)
     */
    public function verificarSesion() {
        header('Content-Type: application/json');
        
        if ($this->security->isValidSession()) {
            echo json_encode([
                'authenticated' => true,
                'user_id' => $_SESSION['user_id'] ?? null,
                'rol' => $_SESSION['user_rol'] ?? null
            ]);
        } else {
            echo json_encode(['authenticated' => false]);
        }
    }
    
    /**
     * Middleware: Requiere autenticación
     */
    public function requireAuth() {
        if (!$this->security->isValidSession()) {
            $this->redirect('/login', 'warning', 'Debe iniciar sesión');
            exit;
        }
    }
    
    /**
     * Middleware: Requiere rol específico
     */
    public function requireRole($rolesPermitidos) {
        $this->requireAuth();
        $this->security->requireRole($rolesPermitidos);
    }
    
    // ============================================
    // MÉTODOS AUXILIARES
    // ============================================
    
    /**
     * Redirigir según rol del usuario
     */
    private function redirigirPorRol($rol) {
        switch ($rol) {
            case ROL_ADMIN:
                header('Location: /dashboard');
                break;
            case ROL_ANALISTA:
                header('Location: /dashboard');
                break;
            case ROL_MEDICO:
                header('Location: /medico/resultados');
                break;
            case ROL_PACIENTE:
                header('Location: /portal/resultados');
                break;
            default:
                header('Location: /dashboard');
        }
        exit;
    }
    
    /**
     * Redirigir con mensaje
     */
    private function redirect($url, $tipo = 'info', $mensaje = '') {
        if (!empty($mensaje)) {
            $_SESSION['flash_message'] = $mensaje;
            $_SESSION['flash_type'] = $tipo;
        }
        header("Location: $url");
        exit;
    }
    
    /**
     * Registrar en auditoría
     */
    private function registrarAuditoria($operacion, $userId, $username) {
        try {
            $db = Database::getInstance();
            $sql = "INSERT INTO auditoria (
                        tabla, operacion, usuario_id, username, 
                        ip_address, user_agent
                    ) VALUES (
                        'usuarios', ?, ?, ?, ?, ?
                    )";
            
            $db->execute($sql, [
                $operacion,
                $userId,
                $username,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Log silencioso
            error_log('Error en auditoría: ' . $e->getMessage());
        }
    }
    
    /**
     * Enviar email de recuperación
     */
    private function enviarEmailRecuperacion($usuario, $token) {
        // TODO: Implementar envío real de email
        // Por ahora solo registrar en log
        
        $enlace = APP_URL . "/cambiar-password?token=$token";
        $mensaje = "Hola {$usuario['nombre_completo']},\n\n";
        $mensaje .= "Has solicitado restablecer tu contraseña.\n\n";
        $mensaje .= "Haz clic en el siguiente enlace:\n";
        $mensaje .= "$enlace\n\n";
        $mensaje .= "Este enlace expirará en 1 hora.\n\n";
        $mensaje .= "Si no solicitaste este cambio, ignora este mensaje.\n\n";
        $mensaje .= "GonzaloLabs";
        
        // Log temporal (reemplazar con PHPMailer o similar)
        error_log("EMAIL RECUPERACIÓN:\nPara: {$usuario['email']}\n$mensaje");
        
        // Ejemplo con mail() de PHP (requiere servidor configurado)
        /*
        $headers = "From: noreply@gonzalolabs.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        mail(
            $usuario['email'],
            'Recuperar contraseña - GonzaloLabs',
            $mensaje,
            $headers
        );
        */
    }
}
?>