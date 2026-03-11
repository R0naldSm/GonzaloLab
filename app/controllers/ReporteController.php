<?php
// app/controllers/ReporteController.php
// ============================================
// CONTROLADOR: Reportes estadísticos y exportaciones
// Rol exclusivo: administrador
// ============================================

require_once __DIR__ . '/../../core/RBAC.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../models/Orden.php';
require_once __DIR__ . '/../models/Examen.php';
require_once __DIR__ . '/../models/Paciente.php';
require_once __DIR__ . '/../models/Auditoria.php';

class ReporteController {

    private Security  $security;
    private Orden     $ordenModel;
    private Examen    $examenModel;
    private Paciente  $pacienteModel;
    private Auditoria $auditoriaModel;

    public function __construct() {
        $this->security       = Security::getInstance();
        $this->ordenModel     = new Orden();
        $this->examenModel    = new Examen();
        $this->pacienteModel  = new Paciente();
        $this->auditoriaModel = new Auditoria();
    }

    // ─────────────────────────────────────────
    // DASHBOARD DE REPORTES
    // ─────────────────────────────────────────

    public function index(): void {
        RBAC::requerirPermiso('reportes.ver');

        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $stats          = $this->ordenModel->estadisticasGenerales($desde, $hasta);
        $masSolicitados = $this->examenModel->masSolicitados(10);
        $db             = Database::getInstance();

        // Producción por analista
        $produccionAnalistas = $db->query(
            "SELECT u.username,
                    AES_DECRYPT(u.nombre_completo, ?) AS nombre_completo,
                    COUNT(DISTINCT o.id_orden) AS ordenes_atendidas,
                    COUNT(DISTINCT r.id_resultado) AS resultados_cargados
             FROM usuarios u
             LEFT JOIN ordenes o ON o.creado_por = u.id_usuario
                   AND DATE(o.fecha_orden) BETWEEN ? AND ? AND o.eliminado = 0
             LEFT JOIN resultados r ON r.cargado_por = u.id_usuario
                   AND DATE(r.fecha_carga) BETWEEN ? AND ?
             WHERE u.rol IN ('administrador','analistaL') AND u.eliminado = 0
             GROUP BY u.id_usuario
             ORDER BY ordenes_atendidas DESC",
            [$this->security->getMySQLEncryptionKey(), $desde, $hasta, $desde, $hasta]
        );

        // Distribución por categoría de examen
        $examenesPorCategoria = $db->query(
            "SELECT cat.nombre AS categoria, cat.color_hex,
                    COUNT(oe.id_orden_examen) AS total
             FROM orden_examenes oe
             JOIN ordenes o ON oe.id_orden = o.id_orden
             JOIN examenes e ON oe.id_examen = e.id_examen
             JOIN categorias_examenes cat ON e.id_categoria = cat.id_categoria
             WHERE o.eliminado = 0 AND DATE(o.fecha_orden) BETWEEN ? AND ?
             GROUP BY cat.id_categoria
             ORDER BY total DESC",
            [$desde, $hasta]
        );

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('reportes/dashboard', compact(
            'stats', 'masSolicitados', 'produccionAnalistas',
            'examenesPorCategoria', 'desde', 'hasta', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // ESTADÍSTICAS DETALLADAS
    // ─────────────────────────────────────────

    public function estadisticas(): void {
        RBAC::requerirPermiso('reportes.ver');

        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $tipo  = $_GET['tipo']  ?? 'ordenes'; // ordenes | examenes | criticos | ingresos

        $db   = Database::getInstance();
        $data = [];

        switch ($tipo) {
            case 'examenes':
                $data = $this->examenModel->masSolicitados(20);
                break;

            case 'criticos':
                $data = $db->query(
                    "SELECT DATE(o.fecha_orden) AS dia,
                            e.nombre AS nombre_examen,
                            pe.nombre_parametro, r.valor_resultado,
                            vr.valor_min_critico, vr.valor_max_critico
                     FROM resultados r
                     JOIN parametros_examen pe ON r.id_parametro = pe.id_parametro
                     LEFT JOIN valores_referencia vr ON vr.id_parametro = pe.id_parametro AND vr.activo = 1
                     JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
                     JOIN examenes e ON oe.id_examen = e.id_examen
                     JOIN ordenes o ON oe.id_orden = o.id_orden
                     WHERE r.es_critico = 1
                       AND DATE(o.fecha_orden) BETWEEN ? AND ?
                     ORDER BY o.fecha_orden DESC",
                    [$desde, $hasta]
                );
                break;

            case 'ingresos':
                $data = $db->query(
                    "SELECT DATE(fecha_orden) AS dia,
                            COUNT(*) AS ordenes,
                            COALESCE(SUM(total_pagar), 0) AS total,
                            SUM(estado_pago = 'pagado') AS pagadas
                     FROM ordenes
                     WHERE eliminado = 0 AND DATE(fecha_orden) BETWEEN ? AND ?
                     GROUP BY dia ORDER BY dia",
                    [$desde, $hasta]
                );
                break;

            default: // ordenes
                $data = $this->ordenModel->estadisticasGenerales($desde, $hasta)['por_dia'];
                break;
        }

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('reportes/estadisticas', compact(
            'data', 'tipo', 'desde', 'hasta', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // EXPORTAR A EXCEL (CSV)
    // ─────────────────────────────────────────

    public function exportar(): void {
        RBAC::requerirPermiso('reportes.exportar_excel');

        $desde  = $_GET['desde']  ?? date('Y-m-01');
        $hasta  = $_GET['hasta']  ?? date('Y-m-d');
        $tipo   = $_GET['tipo']   ?? 'ordenes';

        $filename = "gonzalolabs_{$tipo}_" . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel

        switch ($tipo) {
            case 'ordenes':
                $this->exportarOrdenes($out, $desde, $hasta);
                break;
            case 'criticos':
                $this->exportarCriticos($out, $desde, $hasta);
                break;
            case 'examenes':
                $this->exportarExamenesSolicitados($out, $desde, $hasta);
                break;
            case 'pacientes':
                $this->exportarPacientes($out);
                break;
            default:
                $this->exportarOrdenes($out, $desde, $hasta);
        }

        fclose($out);
        exit;
    }

    // ─────────────────────────────────────────
    // AUDITORÍA
    // ─────────────────────────────────────────

    public function auditoria(): void {
        RBAC::requerirPermiso('reportes.auditoria');

        $filtros = [
            'tabla'       => $_GET['tabla']    ?? '',
            'operacion'   => $_GET['operacion']?? '',
            'usuario_id'  => $_GET['usuario']  ?? '',
            'fecha_desde' => $_GET['desde']    ?? date('Y-m-d'),
            'fecha_hasta' => $_GET['hasta']    ?? date('Y-m-d'),
        ];

        $registros   = $this->auditoriaModel->listar($filtros);
        $tablas      = $this->auditoriaModel->tablasDisponibles();
        $usuarios    = $this->auditoriaModel->usuariosConActividad();
        $menuNav     = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('reportes/estadisticas', compact(
            'registros', 'tablas', 'usuarios', 'filtros', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // MÉTODOS PRIVADOS DE EXPORTACIÓN
    // ─────────────────────────────────────────

    private function exportarOrdenes($out, string $desde, string $hasta): void {
        fputcsv($out, [
            'N° Orden', 'Fecha', 'Paciente', 'Cédula', 'Médico',
            'Exámenes', 'Total', 'Estado', 'Estado Pago', 'Tipo Atención'
        ], ';');

        $ordenes = $this->ordenModel->listar([
            'fecha_desde' => $desde,
            'fecha_hasta' => $hasta,
        ]);

        foreach ($ordenes as $o) {
            fputcsv($out, [
                $o['numero_orden'],
                $o['fecha_orden'],
                trim(($o['pac_nombres'] ?? '') . ' ' . ($o['pac_apellidos'] ?? '')),
                $o['pac_cedula'] ?? '',
                $o['medico_nombre'] ?? 'Sin médico',
                $o['total_examenes'] ?? 0,
                number_format((float)($o['total_pagar'] ?? 0), 2),
                ucfirst($o['estado']),
                ucfirst($o['estado_pago'] ?? ''),
                ucfirst($o['tipo_atencion'] ?? ''),
            ], ';');
        }
    }

    private function exportarCriticos($out, string $desde, string $hasta): void {
        fputcsv($out, [
            'Fecha', 'N° Orden', 'Paciente', 'Examen',
            'Parámetro', 'Valor', 'Mín Crítico', 'Máx Crítico', 'Validado'
        ], ';');

        $db   = Database::getInstance();
        $key  = $this->security->getMySQLEncryptionKey();
        $rows = $db->query(
            "SELECT DATE(o.fecha_orden) AS fecha, o.numero_orden,
                    AES_DECRYPT(p.nombres, '$key') AS pac_nombres,
                    AES_DECRYPT(p.apellidos, '$key') AS pac_apellidos,
                    e.nombre AS nombre_examen, pe.nombre_parametro,
                    r.valor_resultado, r.validado,
                    vr.valor_min_critico, vr.valor_max_critico
             FROM resultados r
             JOIN parametros_examen pe ON r.id_parametro = pe.id_parametro
             LEFT JOIN valores_referencia vr ON vr.id_parametro = pe.id_parametro AND vr.activo = 1
             JOIN orden_examenes oe ON r.id_orden_examen = oe.id_orden_examen
             JOIN examenes e ON oe.id_examen = e.id_examen
             JOIN ordenes o ON oe.id_orden = o.id_orden
             JOIN pacientes p ON o.id_paciente = p.id_paciente
             WHERE r.es_critico = 1 AND DATE(o.fecha_orden) BETWEEN ? AND ?
             ORDER BY o.fecha_orden DESC",
            [$desde, $hasta]
        );

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['fecha'],
                $r['numero_orden'],
                trim(($r['pac_nombres'] ?? '') . ' ' . ($r['pac_apellidos'] ?? '')),
                $r['nombre_examen'],
                $r['nombre_parametro'],
                $r['valor_resultado'],
                $r['valor_min_critico'] ?? 'N/A',
                $r['valor_max_critico'] ?? 'N/A',
                $r['validado'] ? 'Sí' : 'No',
            ], ';');
        }
    }

    private function exportarExamenesSolicitados($out, string $desde, string $hasta): void {
        fputcsv($out, ['Examen', 'Categoría', 'Total Solicitado', 'Precio Unitario'], ';');

        $db   = Database::getInstance();
        $rows = $db->query(
            "SELECT e.nombre, cat.nombre AS categoria,
                    COUNT(oe.id_orden_examen) AS total, e.precio
             FROM orden_examenes oe
             JOIN ordenes o ON oe.id_orden = o.id_orden
             JOIN examenes e ON oe.id_examen = e.id_examen
             JOIN categorias_examenes cat ON e.id_categoria = cat.id_categoria
             WHERE o.eliminado = 0 AND DATE(o.fecha_orden) BETWEEN ? AND ?
             GROUP BY e.id_examen ORDER BY total DESC",
            [$desde, $hasta]
        );

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['nombre'],
                $r['categoria'],
                $r['total'],
                number_format((float)($r['precio'] ?? 0), 2),
            ], ';');
        }
    }

    private function exportarPacientes($out): void {
        fputcsv($out, [
            'ID', 'Nombres', 'Apellidos', 'Cédula', 'Teléfono',
            'Email', 'Fecha Nacimiento', 'Género', 'Tipo Sangre', 'Fecha Registro'
        ], ';');

        $pacientes = $this->pacienteModel->listar();
        foreach ($pacientes as $p) {
            fputcsv($out, [
                $p['id_paciente'],
                $p['nombres'] ?? '',
                $p['apellidos'] ?? '',
                $p['cedula'] ?? '',
                $p['telefono'] ?? '',
                $p['email'] ?? '',
                $p['fecha_nacimiento'] ?? '',
                $p['genero'] ?? '',
                $p['tipo_sangre'] ?? '',
                $p['fecha_registro'] ?? '',
            ], ';');
        }
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    private function renderView(string $view, array $datos = []): void {
        extract($datos);
        $flash     = $this->consumeFlash();
        $csrfToken = $this->security->generateCSRFToken();
        require_once __DIR__ . "/../views/{$view}.php";
    }

    private function consumeFlash(): array {
        $f = ['type' => $_SESSION['flash_type'] ?? null, 'message' => $_SESSION['flash_message'] ?? null];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $f;
    }
}
?>