<?php
// app/models/Examen.php
// ============================================
// MODELO: Catálogo de Exámenes, Categorías y Parámetros
// ============================================

require_once __DIR__ . '/../../config/database.php';

class Examen {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ─────────────────────────────────────────
    // CATEGORÍAS
    // ─────────────────────────────────────────

    public function listarCategorias(): array {
        return $this->db->query(
            "SELECT * FROM categorias_examenes WHERE eliminado = 0 ORDER BY orden_visualizacion, nombre"
        );
    }

    public function getCategoriaById(int $id): array|false {
        return $this->db->queryOne(
            "SELECT * FROM categorias_examenes WHERE id_categoria = ? AND eliminado = 0",
            [$id]
        );
    }

    // ─────────────────────────────────────────
    // EXÁMENES
    // ─────────────────────────────────────────

    public function listar(array $filtros = []): array {
        $sql = "SELECT e.*, c.nombre AS nombre_categoria, c.color_hex
                FROM examenes e
                JOIN categorias_examenes c ON e.id_categoria = c.id_categoria
                WHERE e.eliminado = 0";
        $params = [];

        if (!empty($filtros['id_categoria'])) {
            $sql .= " AND e.id_categoria = ?";
            $params[] = (int)$filtros['id_categoria'];
        }
        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (e.nombre LIKE ? OR e.codigo LIKE ?)";
            $b = '%' . $filtros['busqueda'] . '%';
            $params[] = $b;
            $params[] = $b;
        }
        if (isset($filtros['activo'])) {
            $sql .= " AND e.activo = ?";
            $params[] = (int)$filtros['activo'];
        }

        $sql .= " ORDER BY c.orden_visualizacion, e.nombre";
        return $this->db->query($sql, $params);
    }

    public function listarPorCategoria(): array {
        $categorias = $this->listarCategorias();
        $examenes   = $this->listar(['activo' => 1]);

        $result = [];
        foreach ($categorias as $cat) {
            $cat['examenes'] = array_values(array_filter(
                $examenes,
                fn($e) => $e['id_categoria'] === $cat['id_categoria']
            ));
            $result[] = $cat;
        }
        return $result;
    }

    public function getById(int $id): array|false {
        return $this->db->queryOne(
            "SELECT e.*, c.nombre AS nombre_categoria
             FROM examenes e
             JOIN categorias_examenes c ON e.id_categoria = c.id_categoria
             WHERE e.id_examen = ? AND e.eliminado = 0",
            [$id]
        );
    }

    public function crear(array $datos): array {
        try {
            if (empty($datos['codigo'])) {
                $datos['codigo'] = $this->generarCodigo((int)$datos['id_categoria']);
            }
            if ($this->existeCodigo($datos['codigo'])) {
                return ['success' => false, 'message' => 'El código de examen ya existe'];
            }

            $id = $this->db->insert(
                "INSERT INTO examenes
                    (codigo, nombre, id_categoria, descripcion, precio,
                     tiempo_entrega_min, tiempo_entrega_dias, requiere_ayuno,
                     instrucciones_paciente, metodo_analisis, activo)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                [
                    strtoupper(trim($datos['codigo'])),
                    trim($datos['nombre']),
                    (int)$datos['id_categoria'],
                    $datos['descripcion']           ?? null,
                    $datos['precio']                ?? null,
                    $datos['tiempo_entrega_min']    ?? null,
                    $datos['tiempo_entrega_dias']   ?? null,
                    (int)($datos['requiere_ayuno']  ?? 0),
                    $datos['instrucciones_paciente']?? null,
                    $datos['metodo_analisis']       ?? null,
                ]
            );
            return ['success' => true, 'id_examen' => $id, 'message' => 'Examen creado exitosamente'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actualizar(int $id, array $datos): array {
        try {
            $this->db->execute(
                "UPDATE examenes SET
                    nombre = ?, id_categoria = ?, descripcion = ?, precio = ?,
                    tiempo_entrega_min = ?, tiempo_entrega_dias = ?,
                    requiere_ayuno = ?, instrucciones_paciente = ?,
                    metodo_analisis = ?, activo = ?
                 WHERE id_examen = ? AND eliminado = 0",
                [
                    trim($datos['nombre']),
                    (int)$datos['id_categoria'],
                    $datos['descripcion']           ?? null,
                    $datos['precio']                ?? null,
                    $datos['tiempo_entrega_min']    ?? null,
                    $datos['tiempo_entrega_dias']   ?? null,
                    (int)($datos['requiere_ayuno']  ?? 0),
                    $datos['instrucciones_paciente']?? null,
                    $datos['metodo_analisis']       ?? null,
                    (int)($datos['activo']          ?? 1),
                    $id,
                ]
            );
            return ['success' => true, 'message' => 'Examen actualizado'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function eliminar(int $id): array {
        try {
            $this->db->execute(
                "UPDATE examenes SET eliminado = 1, activo = 0 WHERE id_examen = ?",
                [$id]
            );
            return ['success' => true, 'message' => 'Examen eliminado'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    // PARÁMETROS
    // ─────────────────────────────────────────

    public function getParametros(int $idExamen): array {
        return $this->db->query(
            "SELECT p.*, vr.valor_min_normal, vr.valor_max_normal,
                    vr.valor_min_critico, vr.valor_max_critico, vr.descripcion_rango
             FROM parametros_examen p
             LEFT JOIN valores_referencia vr ON vr.id_parametro = p.id_parametro AND vr.activo = 1
             WHERE p.id_examen = ? AND p.eliminado = 0
             ORDER BY p.orden_visualizacion",
            [$idExamen]
        );
    }

    public function guardarParametros(int $idExamen, array $parametros): array {
        try {
            $this->db->beginTransaction();

            $this->db->execute(
                "UPDATE parametros_examen SET eliminado = 1 WHERE id_examen = ?",
                [$idExamen]
            );

            foreach ($parametros as $i => $p) {
                if (empty(trim($p['nombre_parametro'] ?? ''))) continue;

                $idParam = $this->db->insert(
                    "INSERT INTO parametros_examen
                        (id_examen, nombre_parametro, unidad_medida,
                         orden_visualizacion, tipo_dato, opciones_seleccion, activo)
                     VALUES (?, ?, ?, ?, ?, ?, 1)",
                    [
                        $idExamen,
                        trim($p['nombre_parametro']),
                        $p['unidad_medida']      ?? null,
                        $i,
                        $p['tipo_dato']          ?? 'numerico',
                        $p['opciones_seleccion'] ?? null,
                    ]
                );

                if (!empty($p['valor_min_normal']) || !empty($p['valor_max_normal'])) {
                    $this->db->execute(
                        "INSERT INTO valores_referencia
                            (id_parametro, genero, valor_min_normal, valor_max_normal,
                             valor_min_critico, valor_max_critico, descripcion_rango, activo)
                         VALUES (?, 'Ambos', ?, ?, ?, ?, ?, 1)",
                        [
                            $idParam,
                            $p['valor_min_normal']  ?? null,
                            $p['valor_max_normal']  ?? null,
                            $p['valor_min_critico'] ?? null,
                            $p['valor_max_critico'] ?? null,
                            $p['descripcion_rango'] ?? null,
                        ]
                    );
                }
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Parámetros guardados exitosamente'];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    private function generarCodigo(int $idCategoria): string {
        $cat     = $this->getCategoriaById($idCategoria);
        $prefijo = strtoupper(substr($cat['nombre'] ?? 'EX', 0, 3));
        $num     = $this->db->count(
            "SELECT COUNT(*) FROM examenes WHERE id_categoria = ?",
            [$idCategoria]
        );
        return $prefijo . '-' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
    }

    public function existeCodigo(string $codigo, int $excludeId = 0): bool {
        return $this->db->count(
            "SELECT COUNT(*) FROM examenes WHERE codigo = ? AND id_examen != ? AND eliminado = 0",
            [strtoupper($codigo), $excludeId]
        ) > 0;
    }

    public function masSolicitados(int $limite = 10): array {
        return $this->db->query(
            "SELECT e.nombre, c.nombre AS categoria, COUNT(oe.id_examen) AS total
             FROM orden_examenes oe
             JOIN examenes e ON oe.id_examen = e.id_examen
             JOIN categorias_examenes c ON e.id_categoria = c.id_categoria
             JOIN ordenes o ON oe.id_orden = o.id_orden
             WHERE o.eliminado = 0
             GROUP BY e.id_examen
             ORDER BY total DESC
             LIMIT ?",
            [$limite]
        );
    }
}
?>