<?php
// app/models/Factura.php
// ============================================
// MODELO: Facturas / Comprobantes de Pago + Tokens QR
// Nota: Las facturas en GonzaloLabs están ligadas a las órdenes.
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Security.php';

class Factura {

    private Database $db;
    private Security $security;

    public function __construct() {
        $this->db       = Database::getInstance();
        $this->security = Security::getInstance();
    }

    // ─────────────────────────────────────────
    // LISTAR / BUSCAR
    // ─────────────────────────────────────────

    public function listar(array $filtros = []): array {
        $key = $this->security->getMySQLEncryptionKey();
        $sql = "SELECT o.id_orden, o.numero_orden, o.fecha_orden,
                       o.total_pagar, o.estado_pago, o.metodo_pago, o.estado,
                       o.creado_por,
                       AES_DECRYPT(p.nombres, '$key') AS pac_nombres,
                       AES_DECRYPT(p.apellidos, '$key') AS pac_apellidos,
                       AES_DECRYPT(p.cedula, '$key') AS pac_cedula,
                       arp.token_acceso, arp.fecha_expiracion, arp.activo AS token_activo,
                       u.username AS creado_por_username
                FROM ordenes o
                JOIN pacientes p ON o.id_paciente = p.id_paciente
                LEFT JOIN acceso_resultados_publico arp ON arp.id_orden = o.id_orden AND arp.activo = 1
                LEFT JOIN usuarios u ON o.creado_por = u.id_usuario
                WHERE o.eliminado = 0";
        $params = [];

        if (!empty($filtros['estado_pago'])) {
            $sql .= " AND o.estado_pago = ?";
            $params[] = $filtros['estado_pago'];
        }
        if (!empty($filtros['busqueda'])) {
            $sql .= " AND o.numero_orden LIKE ?";
            $params[] = '%' . $filtros['busqueda'] . '%';
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(o.fecha_orden) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(o.fecha_orden) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $sql .= " ORDER BY o.fecha_creacion DESC";
        return $this->db->query($sql, $params);
    }

    public function getById(int $idOrden): array|false {
        $key = $this->security->getMySQLEncryptionKey();
        $factura = $this->db->queryOne(
            "SELECT o.*, 
                    AES_DECRYPT(p.nombres, '$key') AS pac_nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS pac_apellidos,
                    AES_DECRYPT(p.cedula, '$key') AS pac_cedula,
                    AES_DECRYPT(p.telefono, '$key') AS pac_telefono,
                    AES_DECRYPT(p.email, '$key') AS pac_email,
                    arp.token_acceso, arp.fecha_expiracion, arp.activo AS token_activo
             FROM ordenes o
             JOIN pacientes p ON o.id_paciente = p.id_paciente
             LEFT JOIN acceso_resultados_publico arp ON arp.id_orden = o.id_orden AND arp.activo = 1
             WHERE o.id_orden = ? AND o.eliminado = 0",
            [$idOrden]
        );

        if ($factura) {
            // Traer líneas de exámenes
            $factura['items'] = $this->db->query(
                "SELECT oe.*, e.nombre AS nombre_examen, e.codigo,
                        cat.nombre AS categoria
                 FROM orden_examenes oe
                 JOIN examenes e ON oe.id_examen = e.id_examen
                 JOIN categorias_examenes cat ON e.id_categoria = cat.id_categoria
                 WHERE oe.id_orden = ?",
                [$idOrden]
            );
        }
        return $factura;
    }

    // ─────────────────────────────────────────
    // REGISTRAR PAGO
    // ─────────────────────────────────────────

    public function registrarPago(int $idOrden, string $metodoPago): array {
        try {
            $this->db->execute(
                "UPDATE ordenes
                 SET estado_pago = 'pagado', metodo_pago = ?
                 WHERE id_orden = ? AND eliminado = 0",
                [$metodoPago, $idOrden]
            );
            return ['success' => true, 'message' => 'Pago registrado'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    // ANULAR
    // ─────────────────────────────────────────

    public function anular(int $idOrden): array {
        try {
            $this->db->execute(
                "UPDATE ordenes SET estado_pago = 'anulado' WHERE id_orden = ? AND eliminado = 0",
                [$idOrden]
            );
            // Desactivar tokens asociados
            $this->db->execute(
                "UPDATE acceso_resultados_publico SET activo = 0 WHERE id_orden = ?",
                [$idOrden]
            );
            return ['success' => true, 'message' => 'Factura anulada'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    // TOKENS QR
    // ─────────────────────────────────────────

    /**
     * Generar token de acceso público para resultados de esta orden.
     * Los minutos de expiración se toman de la configuración del sistema.
     */
    public function generarToken(int $idOrden, int $minutosExpiracion = 43200): array {
        try {
            // Desactivar tokens anteriores de esta orden
            $this->db->execute(
                "UPDATE acceso_resultados_publico SET activo = 0 WHERE id_orden = ?",
                [$idOrden]
            );

            $token = $this->security->generateAccessToken(); // 64 hex chars
            $expiracion = date('Y-m-d H:i:s', strtotime("+{$minutosExpiracion} minutes"));

            $this->db->execute(
                "INSERT INTO acceso_resultados_publico
                    (id_orden, token_acceso, fecha_generacion, fecha_expiracion, activo)
                 VALUES (?, ?, NOW(), ?, 1)",
                [$idOrden, $token, $expiracion]
            );

            return [
                'success'    => true,
                'token'      => $token,
                'expiracion' => $expiracion,
                'url'        => APP_URL . '/consulta/' . $token,
                'message'    => 'Token generado',
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Validar token de consulta pública y devolver id_orden si es válido.
     */
    public function validarToken(string $token): int|false {
        $row = $this->db->queryOne(
            "SELECT id_orden, fecha_expiracion FROM acceso_resultados_publico
             WHERE token_acceso = ? AND activo = 1",
            [$token]
        );

        if (!$row) return false;

        if (new DateTime() > new DateTime($row['fecha_expiracion'])) {
            // Expiró: desactivar
            $this->db->execute(
                "UPDATE acceso_resultados_publico SET activo = 0 WHERE token_acceso = ?",
                [$token]
            );
            return false;
        }

        // Registrar acceso
        $this->db->execute(
            "UPDATE acceso_resultados_publico
             SET intentos_acceso = intentos_acceso + 1,
                 ultimo_acceso = NOW(),
                 ip_ultimo_acceso = ?
             WHERE token_acceso = ?",
            [$_SERVER['REMOTE_ADDR'] ?? null, $token]
        );

        return (int)$row['id_orden'];
    }

    // ─────────────────────────────────────────
    // ESTADÍSTICAS
    // ─────────────────────────────────────────

    public function totalIngresosHoy(): float {
        $row = $this->db->queryOne(
            "SELECT COALESCE(SUM(total_pagar), 0) AS total
             FROM ordenes
             WHERE DATE(fecha_orden) = CURDATE() AND estado_pago = 'pagado' AND eliminado = 0"
        );
        return (float)($row['total'] ?? 0);
    }
}
?>