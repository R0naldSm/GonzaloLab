<?php
// app/models/Resultados.php
// ============================================
// MODELO: Resultados de exámenes, validación y alertas críticas
// ============================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Security.php';

class Resultados {

    private Database $db;
    private Security $security;

    public function __construct() {
        $this->db       = Database::getInstance();
        $this->security = Security::getInstance();
    }

    // ─────────────────────────────────────────
    // LECTURA
    // ─────────────────────────────────────────

    /**
     * Obtener resultados de un orden-examen específico con rangos de referencia.
     */
    public function getByOrdenExamen(int $idOrdenExamen): array {
        return $this->db->query(
            "SELECT r.*,
                    pe.nombre_parametro, pe.unidad_medida, pe.tipo_dato,
                    vr.valor_min_normal, vr.valor_max_normal,
                    vr.valor_min_critico, vr.valor_max_critico, vr.descripcion_rango,
                    u.username AS cargado_por_username
             FROM resultados r
             JOIN parametros_examen pe ON r.id_parametro = pe.id_parametro
             LEFT JOIN valores_referencia vr ON vr.id_parametro = pe.id_parametro AND vr.activo = 1
             LEFT JOIN usuarios u ON r.cargado_por = u.id_usuario
             WHERE r.id_orden_examen = ?
             ORDER BY pe.orden_visualizacion",
            [$idOrdenExamen]
        );
    }

    /**
     * Obtener todos los resultados críticos sin validar.
     */
    public function getCriticosPendientes(): array {
        $key = $this->security->getMySQLEncryptionKey();
        return $this->db->query(
            "SELECT r.id_resultado, r.valor_resultado, r.fecha_carga, r.validado,
                    pe.nombre_parametro, pe.unidad_medida,
                    e.nombre AS nombre_examen,
                    o.numero_orden, o.id_orden,
                    AES_DECRYPT(p.nombres, '$key') AS pac_nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS pac_apellidos,
                    vr.valor_min_critico, vr.valor_max_critico
             FROM resultados r
             JOIN parametros_examen pe ON r.id_parametro = pe.id_parametro
             LEFT JOIN valores_referencia vr ON vr.id_parametro = pe.id_parametro AND vr.activo = 1
             JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
             JOIN examenes e ON oe.id_examen = e.id_examen
             JOIN ordenes o ON oe.id_orden = o.id_orden
             JOIN pacientes p ON o.id_paciente = p.id_paciente
             WHERE r.es_critico = 1 AND r.validado = 0
             ORDER BY r.fecha_carga DESC
             LIMIT 100"
        );
    }

    /**
     * Obtener listado general de resultados (para el índice con filtros).
     */
    public function listar(array $filtros = []): array {
        $key = $this->security->getMySQLEncryptionKey();
        $sql = "SELECT o.id_orden, o.numero_orden, o.fecha_orden, o.estado,
                       AES_DECRYPT(p.nombres, '$key') AS pac_nombres,
                       AES_DECRYPT(p.apellidos, '$key') AS pac_apellidos,
                       COUNT(DISTINCT r.id_resultado) AS total_resultados,
                       SUM(r.es_critico) AS criticos,
                       SUM(r.validado) AS validados
                FROM ordenes o
                JOIN pacientes p ON o.id_paciente = p.id_paciente
                LEFT JOIN orden_examenes oe ON oe.id_orden = o.id_orden
                LEFT JOIN resultados r ON r.id_orden_examen = oe.id_orden_examen
                WHERE o.eliminado = 0";
        $params = [];

        if (!empty($filtros['solo_criticos'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM resultados rx
                                  JOIN orden_examenes oex ON rx.id_orden_examen = oex.id_orden_examen
                                  WHERE oex.id_orden = o.id_orden AND rx.es_critico = 1)";
        }
        if (!empty($filtros['estado'])) {
            $sql .= " AND o.estado = ?";
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND DATE(o.fecha_orden) >= ?";
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND DATE(o.fecha_orden) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['numero_orden'])) {
            $sql .= " AND o.numero_orden LIKE ?";
            $params[] = '%' . $filtros['numero_orden'] . '%';
        }

        $sql .= " GROUP BY o.id_orden ORDER BY o.fecha_orden DESC LIMIT 200";
        return $this->db->query($sql, $params);
    }

    // ─────────────────────────────────────────
    // CARGAR RESULTADOS MANUAL
    // ─────────────────────────────────────────

    /**
     * Guardar múltiples resultados para una orden.
     * $resultados: array de ['id_orden_examen' => X, 'id_parametro' => Y, 'valor' => Z]
     */
    public function guardarManual(array $resultados, int $idOrden): array {
        $criticos = 0;
        $errores  = [];

        try {
            $this->db->beginTransaction();

            foreach ($resultados as $r) {
                if (empty(trim($r['valor'] ?? ''))) continue;

                $idOrdenExamen = (int)$r['id_orden_examen'];
                $idParametro   = (int)$r['id_parametro'];
                $valor         = trim($r['valor']);

                // Verificar si ya existe resultado para este parámetro
                $existente = $this->db->queryOne(
                    "SELECT id_resultado FROM resultados
                     WHERE id_orden_examen = ? AND id_parametro = ?",
                    [$idOrdenExamen, $idParametro]
                );

                $esCritico = $this->evaluarCritico($idParametro, $valor);
                if ($esCritico) $criticos++;

                if ($existente) {
                    // Actualizar (trigger incrementa versión)
                    $this->db->execute(
                        "UPDATE resultados SET
                            valor_resultado = ?, es_critico = ?,
                            cargado_por = ?, metodo_carga = 'manual',
                            validado = 0, fecha_carga = NOW()
                         WHERE id_resultado = ?",
                        [$valor, $esCritico ? 1 : 0, $_SESSION['user_id'], $existente['id_resultado']]
                    );
                } else {
                    $this->db->execute(
                        "INSERT INTO resultados
                            (id_orden_examen, id_parametro, valor_resultado,
                             es_critico, fecha_carga, cargado_por, metodo_carga)
                         VALUES (?, ?, ?, ?, NOW(), ?, 'manual')",
                        [$idOrdenExamen, $idParametro, $valor,
                         $esCritico ? 1 : 0, $_SESSION['user_id']]
                    );
                }

                // Marcar examen como completado
                $this->db->execute(
                    "UPDATE orden_examenes SET estado = 'completado' WHERE id_orden_examen = ?",
                    [$idOrdenExamen]
                );
            }

            // Actualizar estado de la orden
            $this->actualizarEstadoOrden($idOrden);

            $this->db->commit();

            return [
                'success'  => true,
                'criticos' => $criticos,
                'message'  => $criticos > 0
                    ? "Resultados guardados. ⚠️ $criticos valor(es) CRÍTICO(s) detectado(s)."
                    : 'Resultados guardados exitosamente.',
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    // IMPORTACIÓN AUTOMÁTICA (CSV/Excel-like)
    // ─────────────────────────────────────────

    /**
     * Procesar archivo CSV de resultados.
     * Formato esperado: numero_orden, codigo_examen, nombre_parametro, valor
     * Devuelve preview para confirmar antes de guardar.
     */
    public function procesarImportacion(string $archivoPath): array {
        if (!file_exists($archivoPath)) {
            return ['success' => false, 'message' => 'Archivo no encontrado'];
        }

        $filas    = [];
        $errores  = [];
        $preview  = [];
        $linea    = 0;

        $handle = fopen($archivoPath, 'r');
        // Saltar encabezado
        fgetcsv($handle, 0, ';');

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $linea++;
            if (count($row) < 4) {
                $errores[] = "Línea $linea: formato incorrecto (se esperan 4 columnas)";
                continue;
            }

            [$numeroOrden, $codigoExamen, $nombreParametro, $valor] = array_map('trim', $row);

            // Buscar orden
            $orden = $this->db->queryOne(
                "SELECT id_orden FROM ordenes WHERE numero_orden = ? AND eliminado = 0",
                [$numeroOrden]
            );
            if (!$orden) {
                $errores[] = "Línea $linea: Orden '$numeroOrden' no encontrada";
                continue;
            }

            // Buscar orden_examen
            $ordenExamen = $this->db->queryOne(
                "SELECT oe.id_orden_examen, e.id_examen
                 FROM orden_examenes oe
                 JOIN examenes e ON oe.id_examen = e.id_examen
                 WHERE oe.id_orden = ? AND e.codigo = ?",
                [$orden['id_orden'], $codigoExamen]
            );
            if (!$ordenExamen) {
                $errores[] = "Línea $linea: Examen '$codigoExamen' no está en la orden '$numeroOrden'";
                continue;
            }

            // Buscar parámetro
            $parametro = $this->db->queryOne(
                "SELECT id_parametro, nombre_parametro FROM parametros_examen
                 WHERE id_examen = ? AND nombre_parametro LIKE ? AND eliminado = 0",
                [$ordenExamen['id_examen'], '%' . $nombreParametro . '%']
            );
            if (!$parametro) {
                $errores[] = "Línea $linea: Parámetro '$nombreParametro' no encontrado";
                continue;
            }

            $esCritico = $this->evaluarCritico($parametro['id_parametro'], $valor);

            $preview[] = [
                'linea'            => $linea,
                'numero_orden'     => $numeroOrden,
                'codigo_examen'    => $codigoExamen,
                'nombre_parametro' => $parametro['nombre_parametro'],
                'valor'            => $valor,
                'es_critico'       => $esCritico,
                'id_orden_examen'  => $ordenExamen['id_orden_examen'],
                'id_parametro'     => $parametro['id_parametro'],
                'id_orden'         => $orden['id_orden'],
            ];
        }
        fclose($handle);

        return [
            'success' => true,
            'preview' => $preview,
            'errores' => $errores,
            'total'   => count($preview),
        ];
    }

    /**
     * Confirmar y guardar resultados procesados del CSV.
     */
    public function confirmarImportacion(array $filas): array {
        $criticos = 0;
        try {
            $this->db->beginTransaction();

            $ordenesAfectadas = [];
            foreach ($filas as $f) {
                $esCritico = (bool)$f['es_critico'];
                if ($esCritico) $criticos++;

                $existente = $this->db->queryOne(
                    "SELECT id_resultado FROM resultados
                     WHERE id_orden_examen = ? AND id_parametro = ?",
                    [$f['id_orden_examen'], $f['id_parametro']]
                );

                if ($existente) {
                    $this->db->execute(
                        "UPDATE resultados SET valor_resultado = ?, es_critico = ?,
                            cargado_por = ?, metodo_carga = 'importacion', validado = 0, fecha_carga = NOW()
                         WHERE id_resultado = ?",
                        [$f['valor'], $esCritico ? 1 : 0, $_SESSION['user_id'], $existente['id_resultado']]
                    );
                } else {
                    $this->db->execute(
                        "INSERT INTO resultados
                            (id_orden_examen, id_parametro, valor_resultado,
                             es_critico, fecha_carga, cargado_por, metodo_carga)
                         VALUES (?, ?, ?, ?, NOW(), ?, 'importacion')",
                        [$f['id_orden_examen'], $f['id_parametro'], $f['valor'],
                         $esCritico ? 1 : 0, $_SESSION['user_id']]
                    );
                }

                $this->db->execute(
                    "UPDATE orden_examenes SET estado = 'completado' WHERE id_orden_examen = ?",
                    [$f['id_orden_examen']]
                );

                $ordenesAfectadas[$f['id_orden']] = true;
            }

            foreach (array_keys($ordenesAfectadas) as $idOrden) {
                $this->actualizarEstadoOrden($idOrden);
            }

            $this->db->commit();
            return [
                'success'  => true,
                'criticos' => $criticos,
                'message'  => "Importación completada. $criticos valor(es) crítico(s).",
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    // VALIDACIÓN
    // ─────────────────────────────────────────

    public function validarOrden(int $idOrden): array {
        try {
            // Marcar todos los resultados como validados
            $this->db->execute(
                "UPDATE resultados r
                 JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
                 SET r.validado = 1, r.fecha_validacion = NOW(), r.validado_por = ?
                 WHERE oe.id_orden = ?",
                [$_SESSION['user_id'], $idOrden]
            );

            // Actualizar estado de la orden a 'validada'
            $this->db->execute(
                "UPDATE ordenes SET estado = 'validada', modificado_por = ? WHERE id_orden = ?",
                [$_SESSION['user_id'], $idOrden]
            );

            return ['success' => true, 'message' => 'Resultados validados correctamente'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    // HELPERS INTERNOS
    // ─────────────────────────────────────────

    /**
     * Evaluar si un valor es crítico según los rangos definidos.
     */
    private function evaluarCritico(int $idParametro, string $valor): bool {
        $ref = $this->db->queryOne(
            "SELECT valor_min_critico, valor_max_critico, id_referencia
             FROM valores_referencia
             WHERE id_parametro = ? AND activo = 1 LIMIT 1",
            [$idParametro]
        );

        if (!$ref || (!isset($ref['valor_min_critico']) && !isset($ref['valor_max_critico']))) {
            return false;
        }

        // Solo evaluar si el valor es numérico
        if (!is_numeric($valor)) return false;

        $num = (float)$valor;
        $minC = $ref['valor_min_critico'];
        $maxC = $ref['valor_max_critico'];

        if ($minC !== null && $num < (float)$minC) return true;
        if ($maxC !== null && $num > (float)$maxC) return true;

        return false;
    }

    /**
     * Actualizar el estado de la orden según el avance de resultados.
     */
    private function actualizarEstadoOrden(int $idOrden): void {
        $totales = $this->db->queryOne(
            "SELECT COUNT(*) AS total,
                    SUM(estado = 'completado') AS completados
             FROM orden_examenes
             WHERE id_orden = ?",
            [$idOrden]
        );

        if ($totales && (int)$totales['completados'] === (int)$totales['total'] && $totales['total'] > 0) {
            $this->db->execute(
                "UPDATE ordenes SET estado = 'resultados_cargados', modificado_por = ?
                 WHERE id_orden = ? AND estado IN ('creada', 'en_proceso')",
                [$_SESSION['user_id'], $idOrden]
            );
        } else {
            $this->db->execute(
                "UPDATE ordenes SET estado = 'en_proceso', modificado_por = ?
                 WHERE id_orden = ? AND estado = 'creada'",
                [$_SESSION['user_id'], $idOrden]
            );
        }
    }
}
?>