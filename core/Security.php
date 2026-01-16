<?php
// core/Security.php
// ============================================
// CLASE DE SEGURIDAD - GonzaloLabs
// Encriptación, Validación, Protección CSRF/XSS
// ============================================

class Security {
    
    private static $encryptionKey;
    private static $instance = null;
    
    private function __construct() {
        // Cargar clave de encriptación
        self::$encryptionKey = $this->getEncryptionKey();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // ============================================
    // ENCRIPTACIÓN AES-256
    // ============================================
    
    /**
     * Obtener o generar clave de encriptación
     */
    private function getEncryptionKey() {
        $keyFile = __DIR__ . '/../storage/.encryption_key';
        
        if (file_exists($keyFile)) {
            return file_get_contents($keyFile);
        }
        
        // Generar nueva clave
        $key = bin2hex(random_bytes(32)); // 256 bits
        
        // Guardar en archivo seguro
        if (!is_dir(__DIR__ . '/../storage')) {
            mkdir(__DIR__ . '/../storage', 0700, true);
        }
        file_put_contents($keyFile, $key);
        chmod($keyFile, 0600); // Solo lectura para el propietario
        
        return $key;
    }
    
    /**
     * Encriptar datos sensibles
     * @param string $data
     * @return string
     */
    public function encrypt($data) {
        if (empty($data)) return $data;
        
        $cipher = "AES-256-CBC";
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt(
            $data, 
            $cipher, 
            self::$encryptionKey, 
            0, 
            $iv
        );
        
        // Combinar IV + datos encriptados en Base64
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Desencriptar datos
     * @param string $encryptedData
     * @return string|false
     */
    public function decrypt($encryptedData) {
        if (empty($encryptedData)) return $encryptedData;
        
        $cipher = "AES-256-CBC";
        $ivLength = openssl_cipher_iv_length($cipher);
        
        $decoded = base64_decode($encryptedData);
        $iv = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength);
        
        return openssl_decrypt(
            $encrypted, 
            $cipher, 
            self::$encryptionKey, 
            0, 
            $iv
        );
    }
    
    /**
     * Obtener clave para MySQL AES_ENCRYPT/AES_DECRYPT
     * @return string
     */
    public function getMySQLEncryptionKey() {
        return self::$encryptionKey;
    }
    
    // ============================================
    // HASH DE PASSWORDS
    // ============================================
    
    /**
     * Hashear password con bcrypt
     * @param string $password
     * @return string
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verificar password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // ============================================
    // PROTECCIÓN CSRF
    // ============================================
    
    /**
     * Generar token CSRF
     * @return string
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validar token CSRF
     * @param string $token
     * @return bool
     */
    public function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // ============================================
    // SANITIZACIÓN Y VALIDACIÓN
    // ============================================
    
    /**
     * Sanitizar input general
     * @param mixed $data
     * @return mixed
     */
    public function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validar email
     * @param string $email
     * @return bool
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validar cédula ecuatoriana
     * @param string $cedula
     * @return bool
     */
    public function validateCedulaEcuador($cedula) {
        if (strlen($cedula) != 10) return false;
        
        $provincia = (int)substr($cedula, 0, 2);
        if ($provincia < 1 || $provincia > 24) return false;
        
        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $suma = 0;
        
        for ($i = 0; $i < 9; $i++) {
            $valor = (int)$cedula[$i] * $coeficientes[$i];
            if ($valor >= 10) $valor -= 9;
            $suma += $valor;
        }
        
        $digitoVerificador = ($suma % 10 === 0) ? 0 : 10 - ($suma % 10);
        
        return $digitoVerificador == (int)$cedula[9];
    }
    
    /**
     * Validar SQL (prevención básica)
     * @param string $input
     * @return bool
     */
    public function containsSQLInjection($input) {
        $patterns = [
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            '/(\bOR\b\s+\d+\s*=\s*\d+)/i',
            '/(--|#|\/\*|\*\/)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        return false;
    }
    
    // ============================================
    // GENERACIÓN DE TOKENS
    // ============================================
    
    /**
     * Generar token único para acceso público
     * @return string
     */
    public function generateAccessToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Generar token para recuperación de password
     * @return string
     */
    public function generatePasswordResetToken() {
        return bin2hex(random_bytes(32));
    }
    
    // ============================================
    // CONTROL DE SESIÓN
    // ============================================
    
    /**
     * Iniciar sesión segura
     */
    public function initSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuración segura de sesión
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', 1); // Solo HTTPS
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerar ID de sesión periódicamente
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) { // 30 minutos
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Verificar si la sesión es válida
     * @return bool
     */
    public function isValidSession() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Verificar tiempo de inactividad (30 minutos)
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > 1800) {
                $this->destroySession();
                return false;
            }
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Destruir sesión
     */
    public function destroySession() {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
    
    // ============================================
    // LOGGING DE SEGURIDAD
    // ============================================
    
    /**
     * Registrar intento de acceso sospechoso
     * @param string $tipo
     * @param string $descripcion
     */
    public function logSecurityEvent($tipo, $descripcion) {
        $logFile = __DIR__ . '/../storage/logs/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        
        $message = "[$timestamp] $tipo | IP: $ip | $descripcion | UA: $userAgent\n";
        
        if (!is_dir(__DIR__ . '/../storage/logs')) {
            mkdir(__DIR__ . '/../storage/logs', 0700, true);
        }
        
        file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }
    
    // ============================================
    // RATE LIMITING
    // ============================================
    
    /**
     * Verificar rate limiting para intentos de login
     * @param string $identifier (IP o username)
     * @param int $maxAttempts
     * @param int $timeWindow (segundos)
     * @return bool
     */
    public function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
        $cacheFile = __DIR__ . "/../storage/cache/rate_limit_" . md5($identifier) . ".json";
        
        if (!is_dir(__DIR__ . '/../storage/cache')) {
            mkdir(__DIR__ . '/../storage/cache', 0700, true);
        }
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            
            // Limpiar intentos antiguos
            $data['attempts'] = array_filter($data['attempts'], function($time) use ($timeWindow) {
                return (time() - $time) < $timeWindow;
            });
            
            if (count($data['attempts']) >= $maxAttempts) {
                return false; // Bloqueado
            }
            
            $data['attempts'][] = time();
        } else {
            $data = ['attempts' => [time()]];
        }
        
        file_put_contents($cacheFile, json_encode($data));
        return true;
    }
    
    // ============================================
    // VALIDACIÓN DE PERMISOS
    // ============================================
    
    /**
     * Verificar si el usuario tiene el rol requerido
     * @param array $rolesPermitidos
     * @return bool
     */
    public function hasRole($rolesPermitidos) {
        if (!isset($_SESSION['user_rol'])) {
            return false;
        }
        
        if (is_string($rolesPermitidos)) {
            $rolesPermitidos = [$rolesPermitidos];
        }
        
        return in_array($_SESSION['user_rol'], $rolesPermitidos);
    }
    
    /**
     * Redirigir si no tiene permisos
     * @param array $rolesPermitidos
     */
    public function requireRole($rolesPermitidos) {
        if (!$this->hasRole($rolesPermitidos)) {
            $this->logSecurityEvent('ACCESO_DENEGADO', 'Intento de acceso sin permisos');
            header('Location: /dashboard?error=sin_permisos');
            exit;
        }
    }
}
?>