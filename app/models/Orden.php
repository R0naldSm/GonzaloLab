<?php
// app/models/Orden.php
// ============================================
// MODELO: Órdenes de Laboratorio
// (incluye métodos para dashboard y consulta de resultados)
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

    // ─────────────────────────────────────────
    // ESTADÍSTICAS PARA DASHBOARD
    // ─────────────────────────────────────────

    public function estadisticasGenerales(string $desde, string $hasta): array {
        return [
            'ordenes'  => $this->db->queryOne(
                "SELECT
                    COUNT(*) AS total,
                    SUM(estado IN ('creada','en_proceso')) AS pendientes,
                    SUM(estado IN ('resultados_cargados','validada','publicada')) AS completadas,
                    COALESCE(SUM(total_pagar), 0) AS ingresos
                 FROM ordenes
                 WHERE DATE(fecha_orden) BETWEEN ? AND ? AND eliminado = 0",
                [$desde, $hasta]
            ),
            'pacientes' => $this->db->queryOne(
                "SELECT COUNT(DISTINCT id_paciente) AS total
                 FROM ordenes
                 WHERE DATE(fecha_orden) BETWEEN ? AND ? AND eliminado = 0",
                [$desde, $hasta]
            ),
            'criticos' => $this->db->queryOne(
                "SELECT COUNT(*) AS total
                 FROM resultados r
                 JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
                 JOIN ordenes o ON oe.id_orden = o.id_orden
                 WHERE r.es_critico = 1 AND DATE(o.fecha_orden) BETWEEN ? AND ? AND o.eliminado = 0",
                [$desde, $hasta]
            ),
            'por_estado' => $this->db->query(
                "SELECT estado, COUNT(*) AS cantidad
                 FROM ordenes
                 WHERE DATE(fecha_orden) BETWEEN ? AND ? AND eliminado = 0
                 GROUP BY estado",
                [$desde, $hasta]
            ),
            'por_dia' => $this->db->query(
                "SELECT DATE(fecha_orden) AS dia, COUNT(*) AS ordenes, COALESCE(SUM(total_pagar),0) AS ingresos
                 FROM ordenes
                 WHERE DATE(fecha_orden) BETWEEN ? AND ? AND eliminado = 0
                 GROUP BY dia ORDER BY dia",
                [$desde, $hasta]
            ),
        ];
    }

    // ─────────────────────────────────────────
    // RESULTADOS COMPLETOS DE UNA ORDEN
    // (Usado por médicos, pacientes y consulta pública)
    // ─────────────────────────────────────────

    public function getResultadosOrden(int $idOrden): array|false {
        $key = $this->security->getMySQLEncryptionKey();

        $orden = $this->db->queryOne(
            "SELECT o.*,
                    AES_DECRYPT(p.nombres, '$key') AS pac_nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS pac_apellidos,
                    AES_DECRYPT(p.cedula, '$key') AS pac_cedula,
                    p.fecha_nacimiento, p.genero,
                    AES_DECRYPT(med.nombre_completo, '$key') AS medico_nombre
             FROM ordenes o
             JOIN pacientes p ON o.id_paciente = p.id_paciente
             LEFT JOIN usuarios med ON o.id_medico = med.id_usuario
             WHERE o.id_orden = ? AND o.eliminado = 0",
            [$idOrden]
        );

        if (!$orden) return false;

        // Exámenes de la orden con sus resultados
        $examenes = $this->db->query(
            "SELECT oe.id_orden_examen, oe.estado AS estado_examen,
                    e.nombre AS nombre_examen, e.codigo, e.metodo_analisis,
                    cat.nombre AS categoria
             FROM orden_examenes oe
             JOIN examenes e ON oe.id_examen = e.id_examen
             JOIN categorias_examenes cat ON e.id_categoria = cat.id_categoria
             WHERE oe.id_orden = ?
             ORDER BY cat.orden_visualizacion, e.nombre",
            [$idOrden]
        );

        foreach ($examenes as &$ex) {
            $ex['resultados'] = $this->db->query(
                "SELECT r.*, pe.nombre_parametro, pe.unidad_medida, pe.tipo_dato,
                        vr.valor_min_normal, vr.valor_max_normal,
                        vr.valor_min_critico, vr.valor_max_critico
                 FROM resultados r
                 JOIN parametros_examen pe ON r.id_parametro = pe.id_parametro
                 LEFT JOIN valores_referencia vr
                       ON vr.id_parametro = pe.id_parametro AND vr.activo = 1
                 WHERE r.id_orden_examen = ?
                 ORDER BY pe.orden_visualizacion",
                [$ex['id_orden_examen']]
            );
            $ex['tiene_criticos'] = array_sum(array_column($ex['resultados'], 'es_critico')) > 0;
        }

        $orden['examenes'] = $examenes;
        $orden['tiene_criticos'] = array_sum(array_column($examenes, 'tiene_criticos')) > 0;
        return $orden;
    }

    // ─────────────────────────────────────────
    // ÓRDENES DE UN PACIENTE (portal paciente)
    // ─────────────────────────────────────────

    public function getOrdenesPaciente(int $idPaciente): array {
        return $this->db->query(
            "SELECT o.id_orden, o.numero_orden, o.fecha_orden, o.estado,
                    o.total_pagar, o.estado_pago,
                    COUNT(DISTINCT oe.id_examen) AS total_examenes,
                    SUM(r.es_critico) AS criticos,
                    arp.token_acceso
             FROM ordenes o
             LEFT JOIN orden_examenes oe ON oe.id_orden = o.id_orden
             LEFT JOIN resultados r ON r.id_orden_examen = oe.id_orden_examen
             LEFT JOIN acceso_resultados_publico arp ON arp.id_orden = o.id_orden AND arp.activo = 1
             WHERE o.id_paciente = ? AND o.eliminado = 0
             GROUP BY o.id_orden
             ORDER BY o.fecha_orden DESC",
            [$idPaciente]
        );
    }

    // ─────────────────────────────────────────
    // ALERTAS CRÍTICAS (últimas 24h)
    // ─────────────────────────────────────────

    public function alertasCriticasRecientes(): array {
        $key = $this->security->getMySQLEncryptionKey();
        return $this->db->query(
            "SELECT r.id_resultado, r.valor_resultado, r.fecha_carga,
                    pe.nombre_parametro, pe.unidad_medida,
                    e.nombre AS nombre_examen,
                    o.numero_orden,
                    AES_DECRYPT(p.nombres, '$key') AS pac_nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS pac_apellidos
             FROM resultados r
             JOIN parametros_examen pe ON r.id_parametro = pe.id_parametro
             JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
             JOIN examenes e ON oe.id_examen = e.id_examen
             JOIN ordenes o ON oe.id_orden = o.id_orden
             JOIN pacientes p ON o.id_paciente = p.id_paciente
             WHERE r.es_critico = 1
               AND r.fecha_carga >= NOW() - INTERVAL 24 HOUR
             ORDER BY r.fecha_carga DESC
             LIMIT 50"
        );
    }
}
?>