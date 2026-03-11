<?php
// app/models/Auditoria.php
// ============================================
// MODELO: Bitácora de auditoría del sistema
// ============================================

require_once __DIR__ . '/../../config/database.php';

class Auditoria {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function listar(array $filtros = []): array {
        $sql = "SELECT a.*, u.username AS nombre_usuario
                FROM auditoria a
                LEFT JOIN usuarios u ON a.usuario_id = u.id_usuario
                WHERE 1=1";
        $params = [];

        if (!empty($filtros['tabla'])) {
            $sql .= " AND a.tabla = ?";
            $params[] = $filtros['tabla'];
        }
        if (!empty($filtros['operacion'])) {
            $sql .= " AND a.operacion = ?";
            $params[] = $filtros['operacion'];
        }
        if (!empty($filtros['usuario_id'])) {
            $sql .= " AND a.usuario_id = ?";
            $params[] = (int)$filtros['usuario_id'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(a.fecha_hora) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(a.fecha_hora) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $sql .= " ORDER BY a.fecha_hora DESC LIMIT 500";
        return $this->db->query($sql, $params);
    }

    public function registrar(string $tabla, string $operacion, ?int $idRegistro = null,
                              ?array $datosAnt = null, ?array $datosNuevo = null): void {
        try {
            $this->db->execute(
                "INSERT INTO auditoria
                    (tabla, operacion, id_registro, usuario_id, username,
                     ip_address, user_agent, datos_anteriores, datos_nuevos)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $tabla, $operacion, $idRegistro,
                    $_SESSION['user_id']   ?? null,
                    $_SESSION['username']  ?? null,
                    $_SERVER['REMOTE_ADDR']        ?? null,
                    $_SERVER['HTTP_USER_AGENT']    ?? null,
                    $datosAnt    ? json_encode($datosAnt)    : null,
                    $datosNuevo  ? json_encode($datosNuevo)  : null,
                ]
            );
        } catch (Exception) { /* silencioso */ }
    }

    public function tablasDisponibles(): array {
        return $this->db->query("SELECT DISTINCT tabla FROM auditoria ORDER BY tabla");
    }

    public function usuariosConActividad(): array {
        return $this->db->query(
            "SELECT DISTINCT a.usuario_id, u.username
             FROM auditoria a
             JOIN usuarios u ON a.usuario_id = u.id_usuario
             ORDER BY u.username"
        );
    }

    // Estadísticas para reportes
    public function actividadPorDia(string $desde, string $hasta): array {
        return $this->db->query(
            "SELECT DATE(fecha_hora) AS dia, operacion, COUNT(*) AS total
             FROM auditoria
             WHERE DATE(fecha_hora) BETWEEN ? AND ?
             GROUP BY dia, operacion
             ORDER BY dia DESC",
            [$desde, $hasta]
        );
    }
}
?>