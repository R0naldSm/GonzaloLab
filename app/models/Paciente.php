<?php
// app/models/Paciente.php
// ============================================
// MODELO: Gestión de Pacientes (datos encriptados AES)
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Security.php';

class Paciente {

    private Database $db;
    private Security $security;

    public function __construct() {
        $this->db       = Database::getInstance();
        $this->security = Security::getInstance();
    }

    private function key(): string {
        return $this->security->getMySQLEncryptionKey();
    }

    // ─────────────────────────────────────────
    // BÚSQUEDA Y LECTURA
    // ─────────────────────────────────────────

    public function listar(array $filtros = []): array {
        $key = $this->key();
        $sql = "SELECT p.id_paciente,
                       AES_DECRYPT(p.cedula, '$key')    AS cedula,
                       AES_DECRYPT(p.nombres, '$key')   AS nombres,
                       AES_DECRYPT(p.apellidos, '$key') AS apellidos,
                       AES_DECRYPT(p.telefono, '$key')  AS telefono,
                       AES_DECRYPT(p.email, '$key')     AS email,
                       p.fecha_nacimiento, p.genero, p.tipo_sangre,
                       p.fecha_registro, p.eliminado,
                       u.username AS registrado_por_username
                FROM pacientes p
                LEFT JOIN usuarios u ON p.registrado_por = u.id_usuario
                WHERE p.eliminado = 0";
        $params = [];

        if (!empty($filtros['busqueda'])) {
            // Buscar en campos encriptados no es directo; buscamos por cedula exacta O en username
            // Para búsqueda parcial en nombres, usamos la vista o un enfoque alternativo:
            // encriptamos el término y buscamos, o cargamos y filtramos en PHP.
            // Aquí: intento de búsqueda por cedula exacta primero
            $sql .= " AND (u.username LIKE ?)";
            $params[] = '%' . $filtros['busqueda'] . '%';
        }

        $sql .= " ORDER BY p.fecha_registro DESC LIMIT 200";
        return $this->db->query($sql, $params);
    }

    /**
     * Búsqueda por cédula exacta (autocompletado).
     */
    public function buscarPorCedula(string $cedula): array|false {
        $key = $this->key();
        return $this->db->queryOne(
            "SELECT p.id_paciente,
                    AES_DECRYPT(p.cedula, '$key')    AS cedula,
                    AES_DECRYPT(p.nombres, '$key')   AS nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS apellidos,
                    AES_DECRYPT(p.telefono, '$key')  AS telefono,
                    AES_DECRYPT(p.email, '$key')     AS email,
                    p.fecha_nacimiento, p.genero, p.tipo_sangre,
                    p.direccion, p.alergias, p.observaciones
             FROM pacientes p
             WHERE p.cedula = AES_ENCRYPT(?, '$key') AND p.eliminado = 0",
            [$cedula]
        );
    }

    /**
     * Búsqueda parcial por nombre (para autocompletado AJAX).
     * Carga hasta 50 registros y filtra en PHP (datos encriptados no son filtrables en SQL parcial).
     */
    public function buscarPorNombre(string $termino): array {
        $key  = $this->key();
        $rows = $this->db->query(
            "SELECT p.id_paciente,
                    AES_DECRYPT(p.cedula, '$key')    AS cedula,
                    AES_DECRYPT(p.nombres, '$key')   AS nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS apellidos,
                    AES_DECRYPT(p.telefono, '$key')  AS telefono,
                    AES_DECRYPT(p.email, '$key')     AS email,
                    p.fecha_nacimiento, p.genero
             FROM pacientes p
             WHERE p.eliminado = 0
             ORDER BY p.fecha_registro DESC
             LIMIT 300"
        );

        $termino = mb_strtolower(trim($termino));
        return array_values(array_filter($rows, function($p) use ($termino) {
            $fullName = mb_strtolower(($p['nombres'] ?? '') . ' ' . ($p['apellidos'] ?? ''));
            $cedula   = $p['cedula'] ?? '';
            return str_contains($fullName, $termino) || str_contains($cedula, $termino);
        }));
    }

    public function getById(int $id): array|false {
        $key = $this->key();
        return $this->db->queryOne(
            "SELECT p.*,
                    AES_DECRYPT(p.cedula, '$key')    AS cedula,
                    AES_DECRYPT(p.nombres, '$key')   AS nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS apellidos,
                    AES_DECRYPT(p.telefono, '$key')  AS telefono,
                    AES_DECRYPT(p.email, '$key')     AS email
             FROM pacientes p
             WHERE p.id_paciente = ? AND p.eliminado = 0",
            [$id]
        );
    }

    // ─────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────

    public function crear(array $datos): array {
        try {
            // Verificar si la cédula ya existe
            if ($this->buscarPorCedula($datos['cedula'])) {
                return ['success' => false, 'message' => 'Ya existe un paciente con esa cédula'];
            }

            $key = $this->key();
            $id  = $this->db->insert(
                "INSERT INTO pacientes
                    (cedula, nombres, apellidos, fecha_nacimiento, genero,
                     telefono, email, direccion, tipo_sangre,
                     alergias, observaciones, registrado_por)
                 VALUES (
                    AES_ENCRYPT(?, '$key'), AES_ENCRYPT(?, '$key'),
                    AES_ENCRYPT(?, '$key'), ?, ?,
                    AES_ENCRYPT(?, '$key'), AES_ENCRYPT(?, '$key'),
                    ?, ?, ?, ?, ?
                 )",
                [
                    $datos['cedula'],
                    $datos['nombres'],
                    $datos['apellidos'],
                    $datos['fecha_nacimiento'] ?? null,
                    $datos['genero']           ?? null,
                    $datos['telefono']         ?? null,
                    $datos['email']            ?? null,
                    $datos['direccion']        ?? null,
                    $datos['tipo_sangre']      ?? null,
                    $datos['alergias']         ?? null,
                    $datos['observaciones']    ?? null,
                    $_SESSION['user_id'],
                ]
            );

            $this->auditoria('INSERT', $id);
            return ['success' => true, 'id_paciente' => $id, 'message' => 'Paciente registrado exitosamente'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actualizar(int $id, array $datos): array {
        try {
            $key = $this->key();
            $this->db->execute(
                "UPDATE pacientes SET
                    nombres = AES_ENCRYPT(?, '$key'),
                    apellidos = AES_ENCRYPT(?, '$key'),
                    fecha_nacimiento = ?, genero = ?,
                    telefono = AES_ENCRYPT(?, '$key'),
                    email = AES_ENCRYPT(?, '$key'),
                    direccion = ?, tipo_sangre = ?,
                    alergias = ?, observaciones = ?,
                    fecha_modificacion = NOW()
                 WHERE id_paciente = ? AND eliminado = 0",
                [
                    $datos['nombres'],
                    $datos['apellidos'],
                    $datos['fecha_nacimiento'] ?? null,
                    $datos['genero']           ?? null,
                    $datos['telefono']         ?? null,
                    $datos['email']            ?? null,
                    $datos['direccion']        ?? null,
                    $datos['tipo_sangre']      ?? null,
                    $datos['alergias']         ?? null,
                    $datos['observaciones']    ?? null,
                    $id,
                ]
            );

            $this->auditoria('UPDATE', $id);
            return ['success' => true, 'message' => 'Paciente actualizado correctamente'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function eliminar(int $id): array {
        try {
            $this->db->execute(
                "UPDATE pacientes SET eliminado = 1, fecha_modificacion = NOW() WHERE id_paciente = ?",
                [$id]
            );
            $this->auditoria('DELETE', $id);
            return ['success' => true, 'message' => 'Paciente desactivado'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    // HISTORIAL
    // ─────────────────────────────────────────

    public function getHistorial(int $idPaciente): array {
        return $this->db->query(
            "SELECT o.id_orden, o.numero_orden, o.fecha_orden, o.estado,
                    o.total_pagar, o.estado_pago, o.tipo_atencion,
                    COUNT(DISTINCT oe.id_examen) AS total_examenes,
                    SUM(r.es_critico) AS criticos
             FROM ordenes o
             LEFT JOIN orden_examenes oe ON oe.id_orden = o.id_orden
             LEFT JOIN resultados r ON r.id_orden_examen = oe.id_orden_examen
             WHERE o.id_paciente = ? AND o.eliminado = 0
             GROUP BY o.id_orden
             ORDER BY o.fecha_orden DESC",
            [$idPaciente]
        );
    }

    // ─────────────────────────────────────────
    // ESTADÍSTICAS PARA REPORTES
    // ─────────────────────────────────────────

    public function totalActivos(): int {
        return $this->db->count("SELECT COUNT(*) FROM pacientes WHERE eliminado = 0");
    }

    // ─────────────────────────────────────────
    // AUDITORÍA
    // ─────────────────────────────────────────

    private function auditoria(string $operacion, int $idRegistro): void {
        try {
            $this->db->execute(
                "INSERT INTO auditoria (tabla, operacion, id_registro, usuario_id, username, ip_address)
                 VALUES ('pacientes', ?, ?, ?, ?, ?)",
                [
                    $operacion,
                    $idRegistro,
                    $_SESSION['user_id']   ?? null,
                    $_SESSION['username']  ?? null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]
            );
        } catch (Exception) { /* silencioso */ }
    }
}
?>