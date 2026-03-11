<?php
// app/models/Orden.php
// ============================================
// MODELO: Órdenes de Laboratorio (CRUD + resultados + estadísticas)
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Security.php';

class Orden {

    private Database $db;
    private Security $security;

    public function __construct() {
        $this->db       = Database::getInstance();
        $this->security = Security::getInstance();
    }

    private function key(): string { return $this->security->getMySQLEncryptionKey(); }

    // ─────────────────────────────────────────
    // LISTAR
    // ─────────────────────────────────────────

    public function listar(array $filtros = []): array {
        $key = $this->key();
        $sql = "SELECT o.id_orden, o.numero_orden, o.fecha_orden, o.estado,
                       o.total_pagar, o.estado_pago, o.tipo_atencion,
                       AES_DECRYPT(p.nombres, '$key')   AS pac_nombres,
                       AES_DECRYPT(p.apellidos, '$key') AS pac_apellidos,
                       AES_DECRYPT(p.cedula, '$key')    AS pac_cedula,
                       AES_DECRYPT(med.nombre_completo, '$key') AS medico_nombre,
                       COUNT(DISTINCT oe.id_examen) AS total_examenes,
                       SUM(r.es_critico) AS criticos,
                       u.username AS creado_por_username
                FROM ordenes o
                JOIN pacientes p ON o.id_paciente = p.id_paciente
                LEFT JOIN usuarios med ON o.id_medico = med.id_usuario
                LEFT JOIN orden_examenes oe ON oe.id_orden = o.id_orden
                LEFT JOIN resultados r ON r.id_orden_examen = oe.id_orden_examen
                LEFT JOIN usuarios u ON o.creado_por = u.id_usuario
                WHERE o.eliminado = 0";
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= " AND o.estado = ?"; $params[] = $filtros['estado'];
        }
        if (!empty($filtros['estado_pago'])) {
            $sql .= " AND o.estado_pago = ?"; $params[] = $filtros['estado_pago'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(o.fecha_orden) >= ?"; $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(o.fecha_orden) <= ?"; $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['numero_orden'])) {
            $sql .= " AND o.numero_orden LIKE ?"; $params[] = '%' . $filtros['numero_orden'] . '%';
        }
        if (!empty($filtros['id_paciente'])) {
            $sql .= " AND o.id_paciente = ?"; $params[] = (int)$filtros['id_paciente'];
        }

        $sql .= " GROUP BY o.id_orden ORDER BY o.fecha_creacion DESC LIMIT 300";
        return $this->db->query($sql, $params);
    }

    public function getById(int $id): array|false {
        $key = $this->key();
        $orden = $this->db->queryOne(
            "SELECT o.*,
                    AES_DECRYPT(p.nombres, '$key')   AS pac_nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS pac_apellidos,
                    AES_DECRYPT(p.cedula, '$key')    AS pac_cedula,
                    p.fecha_nacimiento, p.genero,
                    AES_DECRYPT(med.nombre_completo, '$key') AS medico_nombre,
                    u.username AS creado_por_username
             FROM ordenes o
             JOIN pacientes p ON o.id_paciente = p.id_paciente
             LEFT JOIN usuarios med ON o.id_medico = med.id_usuario
             LEFT JOIN usuarios u ON o.creado_por = u.id_usuario
             WHERE o.id_orden = ? AND o.eliminado = 0",
            [$id]
        );

        if ($orden) {
            $orden['examenes'] = $this->db->query(
                "SELECT oe.*, e.nombre AS nombre_examen, e.codigo, e.precio,
                        cat.nombre AS categoria
                 FROM orden_examenes oe
                 JOIN examenes e ON oe.id_examen = e.id_examen
                 JOIN categorias_examenes cat ON e.id_categoria = cat.id_categoria
                 WHERE oe.id_orden = ?
                 ORDER BY cat.orden_visualizacion, e.nombre",
                [$id]
            );
        }
        return $orden;
    }

    // ─────────────────────────────────────────
    // CREAR / ACTUALIZAR / ELIMINAR
    // ─────────────────────────────────────────

    public function crear(array $datos, array $idExamenes): array {
        try {
            $this->db->beginTransaction();

            $numeroOrden = $this->generarNumero();
            $total       = 0;

            $idOrden = $this->db->insert(
                "INSERT INTO ordenes
                    (numero_orden, id_paciente, id_medico, fecha_orden,
                     fecha_toma_muestra, estado, tipo_atencion,
                     observaciones_recibo, creado_por)
                 VALUES (?, ?, ?, NOW(), NOW(), 'creada', ?, ?, ?)",
                [
                    $numeroOrden,
                    (int)$datos['id_paciente'],
                    !empty($datos['id_medico']) ? (int)$datos['id_medico'] : null,
                    $datos['tipo_atencion'] ?? 'normal',
                    $datos['observaciones'] ?? null,
                    $_SESSION['user_id'],
                ]
            );

            foreach ($idExamenes as $idExamen) {
                $examen = $this->db->queryOne(
                    "SELECT precio, tiempo_entrega_min, tiempo_entrega_dias FROM examenes WHERE id_examen = ?",
                    [(int)$idExamen]
                );
                if (!$examen) continue;

                $precio = (float)($examen['precio'] ?? 0);
                $total += $precio;

                $fechaEstimada = null;
                if ($examen['tiempo_entrega_min']) {
                    $fechaEstimada = date('Y-m-d H:i:s', strtotime("+{$examen['tiempo_entrega_min']} minutes"));
                } elseif ($examen['tiempo_entrega_dias']) {
                    $fechaEstimada = date('Y-m-d H:i:s', strtotime("+{$examen['tiempo_entrega_dias']} days"));
                }

                $this->db->execute(
                    "INSERT INTO orden_examenes (id_orden, id_examen, precio_unitario, fecha_resultado_estimada)
                     VALUES (?, ?, ?, ?)",
                    [$idOrden, (int)$idExamen, $precio, $fechaEstimada]
                );
            }

            $this->db->execute(
                "UPDATE ordenes SET total_pagar = ? WHERE id_orden = ?",
                [$total, $idOrden]
            );

            $this->db->commit();
            return ['success' => true, 'id_orden' => $idOrden,
                    'numero_orden' => $numeroOrden, 'message' => 'Orden creada'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actualizar(int $id, array $datos): array {
        try {
            $this->db->execute(
                "UPDATE ordenes SET
                    id_medico = ?, tipo_atencion = ?,
                    observaciones_recibo = ?, metodo_pago = ?,
                    estado_pago = ?, modificado_por = ?
                 WHERE id_orden = ? AND eliminado = 0",
                [
                    !empty($datos['id_medico']) ? (int)$datos['id_medico'] : null,
                    $datos['tipo_atencion']    ?? 'normal',
                    $datos['observaciones']    ?? null,
                    $datos['metodo_pago']      ?? null,
                    $datos['estado_pago']      ?? 'pendiente',
                    $_SESSION['user_id'],
                    $id,
                ]
            );
            return ['success' => true, 'message' => 'Orden actualizada'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function eliminar(int $id): array {
        try {
            $this->db->execute(
                "UPDATE ordenes SET eliminado = 1, modificado_por = ? WHERE id_orden = ?",
                [$_SESSION['user_id'], $id]
            );
            return ['success' => true, 'message' => 'Orden eliminada'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    // CAMBIO DE ESTADO
    // ─────────────────────────────────────────

    public function validar(int $id): array {
        try {
            $this->db->execute(
                "UPDATE ordenes SET estado = 'validada', modificado_por = ? WHERE id_orden = ?",
                [$_SESSION['user_id'], $id]
            );
            return ['success' => true, 'message' => 'Orden validada'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function publicar(int $id): array {
        try {
            $this->db->execute(
                "UPDATE ordenes SET estado = 'publicada', modificado_por = ? WHERE id_orden = ?",
                [$_SESSION['user_id'], $id]
            );
            return ['success' => true, 'message' => 'Orden publicada'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    // STATS DASHBOARD
    // ─────────────────────────────────────────

    public function estadisticasGenerales(string $desde, string $hasta): array {
        return [
            'ordenes' => $this->db->queryOne(
                "SELECT COUNT(*) AS total,
                    SUM(estado IN ('creada','en_proceso')) AS pendientes,
                    SUM(estado IN ('resultados_cargados','validada','publicada')) AS completadas,
                    COALESCE(SUM(total_pagar),0) AS ingresos
                 FROM ordenes WHERE DATE(fecha_orden) BETWEEN ? AND ? AND eliminado = 0",
                [$desde, $hasta]
            ),
            'pacientes' => $this->db->queryOne(
                "SELECT COUNT(DISTINCT id_paciente) AS total FROM ordenes
                 WHERE DATE(fecha_orden) BETWEEN ? AND ? AND eliminado = 0",
                [$desde, $hasta]
            ),
            'criticos' => $this->db->queryOne(
                "SELECT COUNT(*) AS total FROM resultados r
                 JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
                 JOIN ordenes o ON oe.id_orden = o.id_orden
                 WHERE r.es_critico = 1 AND DATE(o.fecha_orden) BETWEEN ? AND ? AND o.eliminado = 0",
                [$desde, $hasta]
            ),
            'por_estado' => $this->db->query(
                "SELECT estado, COUNT(*) AS cantidad FROM ordenes
                 WHERE DATE(fecha_orden) BETWEEN ? AND ? AND eliminado = 0 GROUP BY estado",
                [$desde, $hasta]
            ),
            'por_dia' => $this->db->query(
                "SELECT DATE(fecha_orden) AS dia, COUNT(*) AS ordenes,
                        COALESCE(SUM(total_pagar),0) AS ingresos
                 FROM ordenes WHERE DATE(fecha_orden) BETWEEN ? AND ? AND eliminado = 0
                 GROUP BY dia ORDER BY dia",
                [$desde, $hasta]
            ),
        ];
    }

    // ─────────────────────────────────────────
    // RESULTADOS PARA VISTA MEDICO / PACIENTE / TOKEN
    // ─────────────────────────────────────────

    public function getResultadosOrden(int $idOrden): array|false {
        $key   = $this->key();
        $orden = $this->db->queryOne(
            "SELECT o.*,
                    AES_DECRYPT(p.nombres, '$key')   AS pac_nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS pac_apellidos,
                    AES_DECRYPT(p.cedula, '$key')    AS pac_cedula,
                    p.fecha_nacimiento, p.genero,
                    AES_DECRYPT(med.nombre_completo, '$key') AS medico_nombre
             FROM ordenes o
             JOIN pacientes p ON o.id_paciente = p.id_paciente
             LEFT JOIN usuarios med ON o.id_medico = med.id_usuario
             WHERE o.id_orden = ? AND o.eliminado = 0",
            [$idOrden]
        );
        if (!$orden) return false;

        $examenes = $this->db->query(
            "SELECT oe.id_orden_examen, oe.estado AS estado_examen,
                    e.nombre AS nombre_examen, e.codigo, e.metodo_analisis,
                    cat.nombre AS categoria
             FROM orden_examenes oe
             JOIN examenes e ON oe.id_examen = e.id_examen
             JOIN categorias_examenes cat ON e.id_categoria = cat.id_categoria
             WHERE oe.id_orden = ? ORDER BY cat.orden_visualizacion, e.nombre",
            [$idOrden]
        );

        foreach ($examenes as &$ex) {
            $ex['resultados'] = $this->db->query(
                "SELECT r.*, pe.nombre_parametro, pe.unidad_medida, pe.tipo_dato,
                        vr.valor_min_normal, vr.valor_max_normal,
                        vr.valor_min_critico, vr.valor_max_critico
                 FROM resultados r
                 JOIN parametros_examen pe ON r.id_parametro = pe.id_parametro
                 LEFT JOIN valores_referencia vr ON vr.id_parametro = pe.id_parametro AND vr.activo = 1
                 WHERE r.id_orden_examen = ?
                 ORDER BY pe.orden_visualizacion",
                [$ex['id_orden_examen']]
            );
            $ex['tiene_criticos'] = array_sum(array_column($ex['resultados'], 'es_critico')) > 0;
        }

        $orden['examenes']       = $examenes;
        $orden['tiene_criticos'] = array_sum(array_column($examenes, 'tiene_criticos')) > 0;
        return $orden;
    }

    public function getOrdenesPaciente(int $idPaciente): array {
        return $this->db->query(
            "SELECT o.id_orden, o.numero_orden, o.fecha_orden, o.estado,
                    o.total_pagar, o.estado_pago,
                    COUNT(DISTINCT oe.id_examen) AS total_examenes,
                    SUM(r.es_critico) AS criticos, arp.token_acceso
             FROM ordenes o
             LEFT JOIN orden_examenes oe ON oe.id_orden = o.id_orden
             LEFT JOIN resultados r ON r.id_orden_examen = oe.id_orden_examen
             LEFT JOIN acceso_resultados_publico arp ON arp.id_orden = o.id_orden AND arp.activo = 1
             WHERE o.id_paciente = ? AND o.eliminado = 0
             GROUP BY o.id_orden ORDER BY o.fecha_orden DESC",
            [$idPaciente]
        );
    }

    public function alertasCriticasRecientes(): array {
        $key = $this->key();
        return $this->db->query(
            "SELECT r.id_resultado, r.valor_resultado, r.fecha_carga,
                    pe.nombre_parametro, pe.unidad_medida, e.nombre AS nombre_examen,
                    o.numero_orden, o.id_orden,
                    AES_DECRYPT(p.nombres, '$key') AS pac_nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS pac_apellidos
             FROM resultados r
             JOIN parametros_examen pe ON r.id_parametro = pe.id_parametro
             JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
             JOIN examenes e ON oe.id_examen = e.id_examen
             JOIN ordenes o ON oe.id_orden = o.id_orden
             JOIN pacientes p ON o.id_paciente = p.id_paciente
             WHERE r.es_critico = 1 AND r.fecha_carga >= NOW() - INTERVAL 24 HOUR
             ORDER BY r.fecha_carga DESC LIMIT 50"
        );
    }

    // ─────────────────────────────────────────
    // REPORTES
    // ─────────────────────────────────────────

    public function exportarListado(array $filtros = []): array {
        return $this->listar($filtros);
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    private function generarNumero(): string {
        $total = $this->db->count("SELECT COUNT(*) FROM ordenes");
        return 'ORD-' . date('Ymd') . '-' . str_pad($total + 1, 5, '0', STR_PAD_LEFT);
    }
}
?>