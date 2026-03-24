<?php
// config/database.php
// ============================================
// CONEXIÓN SEGURA A BASE DE DATOS
// Prepared Statements / PDO
// ============================================

class Database {
    
    private static $instance = null;
    private $connection;
    
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    
    private function __construct() {
        // Cargar configuración desde .env o constantes
        $this->loadConfig();
        $this->connect();
    }
    
    /**
     * Cargar configuración de base de datos
     */
    private function loadConfig() {
        // Opción 1: Desde archivo .env
        if (file_exists(__DIR__ . '/../.env')) {
            $envFile = parse_ini_file(__DIR__ . '/../.env');
            $this->host = $envFile['DB_HOST'] ?? 'localhost';
            $this->dbname = $envFile['DB_NAME'] ?? 'gonzalolabs_db';
            $this->username = $envFile['DB_USER'] ?? 'root';
            $this->password = $envFile['DB_PASSWORD'] ?? '';
        } else {
            // Opción 2: Constantes definidas
            $this->host = DB_HOST;
            $this->dbname = DB_NAME;
            $this->username = DB_USER;
            $this->password = DB_PASSWORD;
        }
    }
    
    /**
     * Establecer conexión PDO
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // Importante para seguridad
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
                PDO::ATTR_PERSISTENT => false
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            // Log del error (no mostrar detalles al usuario)
            $this->logError('Conexión fallida: ' . $e->getMessage());
            die('Error al conectar con la base de datos. Contacte al administrador.');
        }
    }
    
    /**
     * Obtener instancia única (Singleton)
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtener conexión PDO
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    // ============================================
    // MÉTODOS DE CONSULTA SEGUROS
    // ============================================
    
    /**
     * Ejecutar consulta SELECT
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError('Query error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw new Exception('Error al ejecutar consulta');
        }
    }
    
    /**
     * Ejecutar consulta y obtener un solo registro
     * @param string $sql
     * @param array $params
     * @return array|false
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError('QueryOne error: ' . $e->getMessage());
            throw new Exception('Error al ejecutar consulta');
        }
    }
    
    /**
     * Ejecutar INSERT, UPDATE, DELETE
     * @param string $sql
     * @param array $params
     * @return bool
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->logError('Execute error: ' . $e->getMessage());
            throw new Exception('Error al ejecutar operación');
        }
    }
    
    /**
     * Ejecutar INSERT y retornar último ID
     * @param string $sql
     * @param array $params
     * @return int
     */
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) {
            $this->logError('Insert error: ' . $e->getMessage());
            throw new Exception('Error al insertar registro');
        }
    }
    
    /**
     * Contar registros
     * @param string $sql
     * @param array $params
     * @return int
     */
    public function count($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logError('Count error: ' . $e->getMessage());
            throw new Exception('Error al contar registros');
        }
    }
    
    // ============================================
    // TRANSACCIONES
    // ============================================
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirmar transacción
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Revertir transacción
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    // ============================================
    // STORED PROCEDURES
    // ============================================
    
    /**
     * Ejecutar procedimiento almacenado
     * @param string $procedureName
     * @param array $params
     * @return array
     */
    public function callProcedure($procedureName, $params = []) {
        try {
            $placeholders = implode(',', array_fill(0, count($params), '?'));
            $sql = "CALL $procedureName($placeholders)";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            // Obtener todos los result sets
            $results = [];
            do {
                $results[] = $stmt->fetchAll();
            } while ($stmt->nextRowset());
            
            return $results;
            
        } catch (PDOException $e) {
            $this->logError('Procedure error: ' . $e->getMessage());
            throw new Exception('Error al ejecutar procedimiento almacenado');
        }
    }
    
    /**
     * Ejecutar procedimiento con parámetros OUT
     * @param string $procedureName
     * @param array $inParams
     * @param array $outParams
     * @return array
     */
    public function callProcedureWithOut($procedureName, $inParams = [], $outParams = []) {
        try {
            // Construir placeholders para IN y OUT
            $inPlaceholders = array_fill(0, count($inParams), '?');
            $outPlaceholders = array_fill(0, count($outParams), '@out' . ($i ?? 0));
            
            $allPlaceholders = array_merge($inPlaceholders, $outPlaceholders);
            $sql = "CALL $procedureName(" . implode(',', $allPlaceholders) . ")";
            
            // Ejecutar procedimiento
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($inParams);
            
            // Obtener valores OUT
            $outValues = [];
            foreach ($outParams as $i => $param) {
                $result = $this->connection->query("SELECT @out$i as value");
                $outValues[$param] = $result->fetch()['value'];
            }
            
            return $outValues;
            
        } catch (PDOException $e) {
            $this->logError('Procedure OUT error: ' . $e->getMessage());
            throw new Exception('Error al ejecutar procedimiento');
        }
    }
    
    // ============================================
    // UTILIDADES
    // ============================================
    
    /**
     * Escapar valor (como última línea de defensa, usar prepared statements es mejor)
     * @param string $value
     * @return string
     */
    public function escape($value) {
        return $this->connection->quote($value);
    }
    
    /**
     * Verificar si existe un registro
     * @param string $table
     * @param array $conditions
     * @return bool
     */
    public function exists($table, $conditions = []) {
        $where = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $where[] = "$field = ?";
            $params[] = $value;
        }
        
        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM $table WHERE $whereClause";
        
        return $this->count($sql, $params) > 0;
    }
    
    /**
     * Logging de errores
     * @param string $message
     */
    private function logError($message) {
        $logFile = __DIR__ . '/../storage/logs/database_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        
        if (!is_dir(__DIR__ . '/../storage/logs')) {
            mkdir(__DIR__ . '/../storage/logs', 0700, true);
        }
        
        $logMessage = "[$timestamp] $message\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Cerrar conexión
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }
}

// Nota: La clase Usuario real se encuentra en app/models/usuario.php

?>