<?php
// app/models/Usuario.php
// ============================================
// MODELO DE USUARIO
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Security.php';

class Usuario {
    
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->security = Security::getInstance();
    }
    
    /**
     * Buscar usuario por username
     */
    public function findByUsername($username) {
        $sql = "SELECT 
                    id_usuario,
                    username,
                    email,
                    password_hash,
                    rol,
                    estado,
                    intentos_fallidos,
                    ultimo_acceso,
                    AES_DECRYPT(nombre_completo, ?) as nombre_completo,
                    AES_DECRYPT(cedula, ?) as cedula
                FROM usuarios 
                WHERE username = ? 
                AND eliminado = 0";
        
        $encryptionKey = $this->security->getMySQLEncryptionKey();
        return $this->db->queryOne($sql, [$encryptionKey, $encryptionKey, $username]);
    }
    
    /**
     * Buscar usuario por email
     */
    public function findByEmail($email) {
        $sql = "SELECT 
                    id_usuario,
                    username,
                    email,
                    rol,
                    estado,
                    AES_DECRYPT(nombre_completo, ?) as nombre_completo
                FROM usuarios 
                WHERE email = ? 
                AND eliminado = 0";
        
        return $this->db->queryOne($sql, [$this->security->getMySQLEncryptionKey(), $email]);
    }
    
    /**
     * Buscar usuario por ID
     */
    public function findById($id) {
        $sql = "SELECT 
                    id_usuario,
                    username,
                    email,
                    rol,
                    estado,
                    fecha_creacion,
                    ultimo_acceso,
                    AES_DECRYPT(nombre_completo, ?) as nombre_completo,
                    AES_DECRYPT(cedula, ?) as cedula
                FROM usuarios 
                WHERE id_usuario = ? 
                AND eliminado = 0";
        
        $encryptionKey = $this->security->getMySQLEncryptionKey();
        return $this->db->queryOne($sql, [$encryptionKey, $encryptionKey, $id]);
    }
    
    /**
     * Validar credenciales de login
     */
    public function validarLogin($username, $password) {
        // Verificar rate limiting
        if (!$this->security->checkRateLimit($username, MAX_LOGIN_ATTEMPTS, LOCKOUT_TIME)) {
            return [
                'success' => false,
                'message' => 'Cuenta temporalmente bloqueada por múltiples intentos fallidos. Intente en ' . (LOCKOUT_TIME / 60) . ' minutos.',
                'locked' => true
            ];
        }
        
        $usuario = $this->findByUsername($username);
        
        if (!$usuario) {
            $this->security->logSecurityEvent('LOGIN_FALLIDO', "Usuario no encontrado: $username");
            return [
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ];
        }
        
        // Verificar si está bloqueado
        if ($usuario['estado'] === ESTADO_BLOQUEADO) {
            $this->security->logSecurityEvent('LOGIN_BLOQUEADO', "Intento de acceso a cuenta bloqueada: $username");
            return [
                'success' => false,
                'message' => 'Su cuenta está bloqueada. Contacte al administrador.'
            ];
        }
        
        // Verificar si está inactivo
        if ($usuario['estado'] === ESTADO_INACTIVO) {
            return [
                'success' => false,
                'message' => 'Su cuenta está inactiva. Contacte al administrador.'
            ];
        }
        
        // Verificar password
        if (!$this->security->verifyPassword($password, $usuario['password_hash'])) {
            $this->incrementarIntentosFallidos($usuario['id_usuario']);
            $this->security->logSecurityEvent('LOGIN_FALLIDO', "Contraseña incorrecta para: $username");
            
            return [
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ];
        }
        
        // Login exitoso
        $this->resetearIntentosFallidos($usuario['id_usuario']);
        $this->actualizarUltimoAcceso($usuario['id_usuario']);
        
        $this->security->logSecurityEvent('LOGIN_EXITOSO', "Usuario: $username");
        
        return [
            'success' => true,
            'usuario' => $usuario
        ];
    }
    
    /**
     * Incrementar intentos fallidos
     */
    private function incrementarIntentosFallidos($idUsuario) {
        $sql = "UPDATE usuarios 
                SET intentos_fallidos = intentos_fallidos + 1,
                    estado = CASE 
                        WHEN intentos_fallidos + 1 >= ? THEN 'bloqueado'
                        ELSE estado
                    END
                WHERE id_usuario = ?";
        
        $this->db->execute($sql, [MAX_LOGIN_ATTEMPTS, $idUsuario]);
    }
    
    /**
     * Resetear intentos fallidos
     */
    private function resetearIntentosFallidos($idUsuario) {
        $sql = "UPDATE usuarios 
                SET intentos_fallidos = 0
                WHERE id_usuario = ?";
        
        $this->db->execute($sql, [$idUsuario]);
    }
    
    /**
     * Actualizar último acceso
     */
    private function actualizarUltimoAcceso($idUsuario) {
        $sql = "UPDATE usuarios 
                SET ultimo_acceso = NOW()
                WHERE id_usuario = ?";
        
        $this->db->execute($sql, [$idUsuario]);
    }
    
    /**
     * Crear nuevo usuario
     */
    public function crear($datos) {
        try {
            $this->db->beginTransaction();
            
            // Validar que username y email no existan
            if ($this->findByUsername($datos['username'])) {
                throw new Exception('El nombre de usuario ya existe');
            }
            
            if ($this->findByEmail($datos['email'])) {
                throw new Exception('El email ya está registrado');
            }
            
            $sql = "INSERT INTO usuarios (
                        username,
                        email,
                        password_hash,
                        nombre_completo,
                        cedula,
                        rol,
                        estado,
                        creado_por
                    ) VALUES (
                        ?,
                        ?,
                        ?,
                        AES_ENCRYPT(?, ?),
                        AES_ENCRYPT(?, ?),
                        ?,
                        'activo',
                        ?
                    )";
            
            $encryptionKey = $this->security->getMySQLEncryptionKey();
            $passwordHash = $this->security->hashPassword($datos['password']);
            
            $params = [
                $datos['username'],
                $datos['email'],
                $passwordHash,
                $datos['nombre_completo'],
                $encryptionKey,
                $datos['cedula'],
                $encryptionKey,
                $datos['rol'],
                $datos['creado_por'] ?? null
            ];
            
            $idUsuario = $this->db->insert($sql, $params);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'id_usuario' => $idUsuario,
                'message' => 'Usuario creado exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar token de recuperación de contraseña
     */
    public function generarTokenRecuperacion($email) {
        $usuario = $this->findByEmail($email);
        
        if (!$usuario) {
            return [
                'success' => false,
                'message' => 'No existe usuario con ese email'
            ];
        }
        
        $token = $this->security->generatePasswordResetToken();
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $sql = "UPDATE usuarios 
                SET token_recuperacion = ?,
                    token_expiracion = ?
                WHERE id_usuario = ?";
        
        $this->db->execute($sql, [$token, $expiracion, $usuario['id_usuario']]);
        
        return [
            'success' => true,
            'token' => $token,
            'usuario' => $usuario
        ];
    }
    
    /**
     * Validar token de recuperación
     */
    public function validarTokenRecuperacion($token) {
        $sql = "SELECT id_usuario, username, email,
                       AES_DECRYPT(nombre_completo, ?) as nombre_completo
                FROM usuarios 
                WHERE token_recuperacion = ?
                AND token_expiracion > NOW()
                AND eliminado = 0";
        
        return $this->db->queryOne($sql, [$this->security->getMySQLEncryptionKey(), $token]);
    }
    
    /**
     * Cambiar contraseña con token
     */
    public function cambiarPasswordConToken($token, $nuevaPassword) {
        $usuario = $this->validarTokenRecuperacion($token);
        
        if (!$usuario) {
            return [
                'success' => false,
                'message' => 'Token inválido o expirado'
            ];
        }
        
        $passwordHash = $this->security->hashPassword($nuevaPassword);
        
        $sql = "UPDATE usuarios 
                SET password_hash = ?,
                    token_recuperacion = NULL,
                    token_expiracion = NULL,
                    intentos_fallidos = 0,
                    estado = CASE 
                        WHEN estado = 'bloqueado' THEN 'activo'
                        ELSE estado
                    END
                WHERE id_usuario = ?";
        
        $this->db->execute($sql, [$passwordHash, $usuario['id_usuario']]);
        
        $this->security->logSecurityEvent('PASSWORD_CHANGED', "Usuario: {$usuario['username']}");
        
        return [
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente'
        ];
    }
    
    /**
     * Cambiar contraseña (usuario autenticado)
     */
    public function cambiarPassword($idUsuario, $passwordActual, $nuevaPassword) {
        $usuario = $this->findById($idUsuario);
        
        if (!$usuario) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }
        
        // Verificar password actual
        $usuarioCompleto = $this->findByUsername($usuario['username']);
        if (!$this->security->verifyPassword($passwordActual, $usuarioCompleto['password_hash'])) {
            return [
                'success' => false,
                'message' => 'La contraseña actual es incorrecta'
            ];
        }
        
        $passwordHash = $this->security->hashPassword($nuevaPassword);
        
        $sql = "UPDATE usuarios 
                SET password_hash = ?
                WHERE id_usuario = ?";
        
        $this->db->execute($sql, [$passwordHash, $idUsuario]);
        
        return [
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente'
        ];
    }
    
    /**
     * Listar usuarios
     */
    public function listar($filtros = []) {
        $sql = "SELECT 
                    id_usuario,
                    username,
                    email,
                    rol,
                    estado,
                    fecha_creacion,
                    ultimo_acceso,
                    AES_DECRYPT(nombre_completo, ?) as nombre_completo
                FROM usuarios 
                WHERE eliminado = 0";
        
        $params = [$this->security->getMySQLEncryptionKey()];
        
        if (!empty($filtros['rol'])) {
            $sql .= " AND rol = ?";
            $params[] = $filtros['rol'];
        }
        
        if (!empty($filtros['estado'])) {
            $sql .= " AND estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (username LIKE ? OR email LIKE ?)";
            $busqueda = '%' . $filtros['busqueda'] . '%';
            $params[] = $busqueda;
            $params[] = $busqueda;
        }
        
        $sql .= " ORDER BY fecha_creacion DESC";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Desactivar usuario (eliminación lógica)
     */
    public function desactivar($idUsuario) {
        $sql = "UPDATE usuarios 
                SET estado = 'inactivo',
                    eliminado = 1
                WHERE id_usuario = ?";
        
        return $this->db->execute($sql, [$idUsuario]);
    }
}
?>