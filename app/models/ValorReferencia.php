<?php
// app/models/ValorReferencial.php
// ============================================
// MODELO: Rangos de referencia clínicos
// Tabla: valores_referencia
//
// Relación: parametros_examen → valores_referencia (1:N)
// Un parámetro puede tener múltiples rangos por género/edad
// ============================================

require_once __DIR__ . '/../../config/database.php';

class ValorReferencial {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    // LECTURA
    // ─────────────────────────────────────────

    /**
     * Obtener todos los rangos de referencia de un parámetro.
     * Ordenados por género y edad mínima.
     */
    public function getByParametro(int $idParametro): array {
        return $this->db->query(
            "SELECT id_referencia, id_parametro, genero,
                    edad_min, edad_max,
                    valor_min_normal, valor_max_normal,
                    valor_min_critico, valor_max_critico,
                    descripcion_rango, activo
             FROM valores_referencia
             WHERE id_parametro = ? AND activo = 1
             ORDER BY genero, edad_min",
            [$idParametro]
        );
    }

    /**
     * Obtener un rango específico por ID.
     */
    public function getById(int $id): ?array {
        $row = $this->db->queryOne(
            "SELECT * FROM valores_referencia WHERE id_referencia = ?",
            [$id]
        );
        return $row ?: null;
    }

    /**
     * Obtener el rango más apropiado para un paciente.
     * Prioridad: género + edad > solo género > Ambos
     */
    public function getRangoAplicable(int $idParametro, string $genero = 'Ambos', ?int $edad = null): ?array {
        $params = [$idParametro];

        // Buscar rango específico por género y edad
        if ($genero !== 'Ambos' && $edad !== null) {
            $row = $this->db->queryOne(
                "SELECT * FROM valores_referencia
                 WHERE id_parametro = ? AND activo = 1
                   AND (genero = ? OR genero = 'Ambos')
                   AND (edad_min IS NULL OR edad_min <= ?)
                   AND (edad_max IS NULL OR edad_max >= ?)
                 ORDER BY genero DESC, edad_min DESC
                 LIMIT 1",
                [$idParametro, $genero, $edad, $edad]
            );
            if ($row) return $row;
        }

        // Fallback: solo género
        if ($genero !== 'Ambos') {
            $row = $this->db->queryOne(
                "SELECT * FROM valores_referencia
                 WHERE id_parametro = ? AND activo = 1
                   AND (genero = ? OR genero = 'Ambos')
                   AND edad_min IS NULL
                 ORDER BY genero DESC LIMIT 1",
                [$idParametro, $genero]
            );
            if ($row) return $row;
        }

        // Fallback final: cualquier rango activo
        return $this->db->queryOne(
            "SELECT * FROM valores_referencia
             WHERE id_parametro = ? AND activo = 1
             ORDER BY id_referencia LIMIT 1",
            [$idParametro]
        ) ?: null;
    }

    // ─────────────────────────────────────────
    // ESCRITURA
    // ─────────────────────────────────────────

    /**
     * Crear un nuevo rango de referencia.
     * Retorna ['success'=>bool, 'id_referencia'=>int|null, 'message'=>string]
     */
    public function crear(array $datos): array {
        try {
            $id = $this->db->insert(
                "INSERT INTO valores_referencia
                    (id_parametro, genero, edad_min, edad_max,
                     valor_min_normal, valor_max_normal,
                     valor_min_critico, valor_max_critico,
                     descripcion_rango, activo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                [
                    (int)$datos['id_parametro'],
                    $datos['genero']             ?? 'Ambos',
                    isset($datos['edad_min']) && $datos['edad_min'] !== '' ? (int)$datos['edad_min'] : null,
                    isset($datos['edad_max']) && $datos['edad_max'] !== '' ? (int)$datos['edad_max'] : null,
                    $datos['valor_min_normal']   !== '' ? $datos['valor_min_normal']   : null,
                    $datos['valor_max_normal']   !== '' ? $datos['valor_max_normal']   : null,
                    $datos['valor_min_critico']  !== '' ? $datos['valor_min_critico']  : null,
                    $datos['valor_max_critico']  !== '' ? $datos['valor_max_critico']  : null,
                    $datos['descripcion_rango']  ?? null,
                ]
            );

            return ['success' => true, 'id_referencia' => $id, 'message' => 'Rango creado correctamente'];
        } catch (\Exception $e) {
            return ['success' => false, 'id_referencia' => null, 'message' => 'Error al crear rango: ' . $e->getMessage()];
        }
    }

    /**
     * Actualizar un rango existente.
     */
    public function actualizar(int $id, array $datos): array {
        try {
            $this->db->execute(
                "UPDATE valores_referencia SET
                    genero             = ?,
                    edad_min           = ?,
                    edad_max           = ?,
                    valor_min_normal   = ?,
                    valor_max_normal   = ?,
                    valor_min_critico  = ?,
                    valor_max_critico  = ?,
                    descripcion_rango  = ?
                 WHERE id_referencia = ?",
                [
                    $datos['genero']             ?? 'Ambos',
                    isset($datos['edad_min']) && $datos['edad_min'] !== '' ? (int)$datos['edad_min'] : null,
                    isset($datos['edad_max']) && $datos['edad_max'] !== '' ? (int)$datos['edad_max'] : null,
                    $datos['valor_min_normal']   !== '' ? $datos['valor_min_normal']   : null,
                    $datos['valor_max_normal']   !== '' ? $datos['valor_max_normal']   : null,
                    $datos['valor_min_critico']  !== '' ? $datos['valor_min_critico']  : null,
                    $datos['valor_max_critico']  !== '' ? $datos['valor_max_critico']  : null,
                    $datos['descripcion_rango']  ?? null,
                    $id,
                ]
            );

            return ['success' => true, 'message' => 'Rango actualizado correctamente'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()];
        }
    }

    /**
     * Desactivar (soft delete) un rango.
     */
    public function eliminar(int $id): array {
        try {
            $this->db->execute(
                "UPDATE valores_referencia SET activo = 0 WHERE id_referencia = ?",
                [$id]
            );
            return ['success' => true, 'message' => 'Rango eliminado'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error al eliminar: ' . $e->getMessage()];
        }
    }

    /**
     * Reemplazar todos los rangos de un parámetro en una sola operación.
     * Usado por ExamenController al guardar parámetros.
     *
     * @param int   $idParametro
     * @param array $rangos  Array de datos de rango
     */
    public function reemplazarPorParametro(int $idParametro, array $rangos): void {
        // Desactivar todos los anteriores
        $this->db->execute(
            "UPDATE valores_referencia SET activo = 0 WHERE id_parametro = ?",
            [$idParametro]
        );

        foreach ($rangos as $r) {
            // Omitir filas completamente vacías
            if (empty($r['valor_min_normal']) && empty($r['valor_max_normal']) &&
                empty($r['valor_min_critico']) && empty($r['valor_max_critico'])) {
                continue;
            }
            $r['id_parametro'] = $idParametro;
            $this->crear($r);
        }
    }

    // ─────────────────────────────────────────
    // EVALUACIÓN CLÍNICA
    // ─────────────────────────────────────────

    /**
     * Evaluar si un valor es crítico para un parámetro dado.
     *
     * @param int    $idParametro
     * @param mixed  $valor        Valor a evaluar
     * @param string $genero       'M', 'F', 'Ambos'
     * @param int    $edad         Edad del paciente en años
     * @return array ['es_critico'=>bool, 'es_alto'=>bool, 'es_bajo'=>bool,
     *               'valor_min_critico'=>float|null, 'valor_max_critico'=>float|null]
     */
    public function evaluar(int $idParametro, $valor, string $genero = 'Ambos', ?int $edad = null): array {
        $resultado = [
            'es_critico'        => false,
            'es_alto'           => false,
            'es_bajo'           => false,
            'fuera_de_rango'    => false,
            'valor_min_normal'  => null,
            'valor_max_normal'  => null,
            'valor_min_critico' => null,
            'valor_max_critico' => null,
        ];

        // Solo evaluar valores numéricos
        if (!is_numeric($valor)) return $resultado;
        $num = (float)$valor;

        $rango = $this->getRangoAplicable($idParametro, $genero, $edad);
        if (!$rango) return $resultado;

        $resultado['valor_min_normal']  = $rango['valor_min_normal']  ?? null;
        $resultado['valor_max_normal']  = $rango['valor_max_normal']  ?? null;
        $resultado['valor_min_critico'] = $rango['valor_min_critico'] ?? null;
        $resultado['valor_max_critico'] = $rango['valor_max_critico'] ?? null;

        // Evaluar criticidad
        if ($rango['valor_min_critico'] !== null && $num < (float)$rango['valor_min_critico']) {
            $resultado['es_critico'] = true;
            $resultado['es_bajo']    = true;
        }
        if ($rango['valor_max_critico'] !== null && $num > (float)$rango['valor_max_critico']) {
            $resultado['es_critico'] = true;
            $resultado['es_alto']    = true;
        }

        // Evaluar rango normal (solo si no es crítico)
        if (!$resultado['es_critico']) {
            if ($rango['valor_min_normal'] !== null && $num < (float)$rango['valor_min_normal']) {
                $resultado['fuera_de_rango'] = true;
                $resultado['es_bajo']        = true;
            }
            if ($rango['valor_max_normal'] !== null && $num > (float)$rango['valor_max_normal']) {
                $resultado['fuera_de_rango'] = true;
                $resultado['es_alto']        = true;
            }
        }

        return $resultado;
    }
}
?>