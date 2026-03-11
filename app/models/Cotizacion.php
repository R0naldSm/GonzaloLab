<?php
// app/models/Cotizacion.php
// ============================================
// MODELO: Cotizaciones de Exámenes
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Security.php';

class Cotizacion {

    private Database $db;
    private Security $security;

    public function __construct() {
        $this->db       = Database::getInstance();
        $this->security = Security::getInstance();
    }

    public function listar(array $filtros = []): array {
        $key = $this->security->getMySQLEncryptionKey();
        $sql = "SELECT c.*,
                       AES_DECRYPT(p.nombres, '$key') AS paciente_nombres,
                       AES_DECRYPT(p.apellidos, '$key') AS paciente_apellidos,
                       u.username AS creado_por_username
                FROM cotizaciones c
                LEFT JOIN pacientes p ON c.id_paciente = p.id_paciente
                LEFT JOIN usuarios u ON c.creado_por = u.id_usuario
                WHERE c.eliminado = 0";
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= " AND c.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (c.numero_cotizacion LIKE ? OR c.nombre_cliente LIKE ?)";
            $b = '%' . $filtros['busqueda'] . '%';
            $params[] = $b;
            $params[] = $b;
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(c.fecha_cotizacion) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(c.fecha_cotizacion) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $sql .= " ORDER BY c.fecha_creacion DESC";
        return $this->db->query($sql, $params);
    }

    public function getById(int $id): array|false {
        $key = $this->security->getMySQLEncryptionKey();
        $cotizacion = $this->db->queryOne(
            "SELECT c.*,
                    AES_DECRYPT(p.nombres, '$key') AS paciente_nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS paciente_apellidos,
                    AES_DECRYPT(p.cedula, '$key') AS paciente_cedula,
                    u.username AS creado_por_username
             FROM cotizaciones c
             LEFT JOIN pacientes p ON c.id_paciente = p.id_paciente
             LEFT JOIN usuarios u ON c.creado_por = u.id_usuario
             WHERE c.id_cotizacion = ? AND c.eliminado = 0",
            [$id]
        );

        if ($cotizacion) {
            $cotizacion['items'] = $this->getItems($id);
        }
        return $cotizacion;
    }

    public function getItems(int $idCotizacion): array {
        return $this->db->query(
            "SELECT ce.*, e.nombre AS nombre_examen, e.codigo,
                    cat.nombre AS categoria
             FROM cotizacion_examenes ce
             JOIN examenes e ON ce.id_examen = e.id_examen
             JOIN categorias_examenes cat ON e.id_categoria = cat.id_categoria
             WHERE ce.id_cotizacion = ?
             ORDER BY e.nombre",
            [$idCotizacion]
        );
    }

    public function crear(array $datos, array $examenes): array {
        try {
            $this->db->beginTransaction();

            $numero = $this->generarNumero();
            $subtotal  = 0;
            $descuento = (float)($datos['descuento'] ?? 0);

            // Calcular subtotal
            foreach ($examenes as $ex) {
                $subtotal += (float)$ex['precio'] * (int)($ex['cantidad'] ?? 1);
            }
            $total = $subtotal - $descuento;

            $idCotizacion = $this->db->insert(
                "INSERT INTO cotizaciones
                    (numero_cotizacion, id_paciente, nombre_cliente, fecha_cotizacion,
                     fecha_validez, subtotal, descuento, total, estado, observaciones, creado_por)
                 VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, 'vigente', ?, ?)",
                [
                    $numero,
                    $datos['id_paciente'] ?? null,
                    $datos['nombre_cliente'] ?? null,
                    $datos['fecha_validez'] ?? null,
                    $subtotal,
                    $descuento,
                    $total,
                    $datos['observaciones'] ?? null,
                    $_SESSION['user_id'],
                ]
            );

            foreach ($examenes as $ex) {
                $cant      = (int)($ex['cantidad'] ?? 1);
                $precio    = (float)$ex['precio'];
                $subtotalItem = $cant * $precio;

                $this->db->execute(
                    "INSERT INTO cotizacion_examenes
                        (id_cotizacion, id_examen, cantidad, precio_unitario, subtotal)
                     VALUES (?, ?, ?, ?, ?)",
                    [$idCotizacion, (int)$ex['id_examen'], $cant, $precio, $subtotalItem]
                );
            }

            $this->db->commit();
            return ['success' => true, 'id_cotizacion' => $idCotizacion,
                    'numero' => $numero, 'message' => 'Cotización creada'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function cambiarEstado(int $id, string $estado): array {
        $validos = ['vigente', 'aceptada', 'rechazada', 'expirada'];
        if (!in_array($estado, $validos, true)) {
            return ['success' => false, 'message' => 'Estado inválido'];
        }
        try {
            $this->db->execute(
                "UPDATE cotizaciones SET estado = ? WHERE id_cotizacion = ? AND eliminado = 0",
                [$estado, $id]
            );
            return ['success' => true, 'message' => 'Estado actualizado'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function eliminar(int $id): array {
        try {
            $this->db->execute(
                "UPDATE cotizaciones SET eliminado = 1 WHERE id_cotizacion = ?",
                [$id]
            );
            return ['success' => true, 'message' => 'Cotización eliminada'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Para exportación */
    public function listarParaExportar(array $filtros = []): array {
        return $this->listar($filtros);
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    private function generarNumero(): string {
        $total = $this->db->count("SELECT COUNT(*) FROM cotizaciones");
        return 'COT-' . date('Ymd') . '-' . str_pad($total + 1, 4, '0', STR_PAD_LEFT);
    }
}
?>