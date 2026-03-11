<?php
// app/controllers/ConsultaPublicaController.php
// ============================================
// CONTROLADOR: Consulta de resultados
//   - Acceso público por token QR (sin sesión)
//   - Portal del paciente autenticado
//   - Vista del médico (solo lectura)
// ============================================

require_once __DIR__ . '/../../core/RBAC.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../models/Factura.php';
require_once __DIR__ . '/../models/Orden.php';

class ConsultaPublicaController {

    private Security $security;
    private Factura  $facturaModel;
    private Orden    $ordenModel;

    public function __construct() {
        $this->security     = Security::getInstance();
        $this->facturaModel = new Factura();
        $this->ordenModel   = new Orden();
    }

    // ─────────────────────────────────────────
    // 1. ACCESO PÚBLICO POR TOKEN / QR
    //    Ruta: /consulta/{token}  (sin sesión)
    // ─────────────────────────────────────────

    public function verResultados(string $token): void {
        // Sanitizar token: solo hex de 64 chars
        $token = preg_replace('/[^a-f0-9]/', '', strtolower(trim($token)));

        if (strlen($token) !== 64) {
            $this->renderPublica('publica/token_invalido', [
                'error' => 'El enlace es inválido o está mal formado.'
            ]);
            return;
        }

        // Validar token en BD
        $idOrden = $this->facturaModel->validarToken($token);

        if (!$idOrden) {
            $this->renderPublica('publica/token_invalido', [
                'error' => 'Este enlace ha expirado o ya no es válido. Solicite uno nuevo en el laboratorio.'
            ]);
            return;
        }

        // Obtener resultados de la orden
        $orden = $this->ordenModel->getResultadosOrden($idOrden);

        if (!$orden || !in_array($orden['estado'], ['resultados_cargados', 'validada', 'publicada'], true)) {
            $this->renderPublica('publica/sin_resultados', [
                'mensaje' => 'Los resultados de esta orden aún no están disponibles. Por favor intente más tarde.'
            ]);
            return;
        }

        // Todo OK → mostrar resultados (vista pública, sin menú de sistema)
        $this->renderPublica('publica/consulta_resultados', [
            'orden' => $orden,
            'token' => $token,
        ]);
    }

    // ─────────────────────────────────────────
    // 2. PORTAL PACIENTE AUTENTICADO
    //    Ruta: /portal/resultados  (requiere sesión rol=paciente)
    // ─────────────────────────────────────────

    public function portalPaciente(): void {
        RBAC::requerirPermiso('portal.ver_propios');

        $idUsuario = $_SESSION['user_id'];

        // Buscar el paciente vinculado al usuario (por email o id)
        $db     = Database::getInstance();
        $secKey = $this->security->getMySQLEncryptionKey();

        $paciente = $db->queryOne(
            "SELECT p.* FROM pacientes p
             JOIN usuarios u ON AES_DECRYPT(p.email, '$secKey') = u.email
             WHERE u.id_usuario = ? AND p.eliminado = 0",
            [$idUsuario]
        );

        if (!$paciente) {
            // No hay registro de paciente vinculado todavía
            $this->renderView('publica/portal_sin_datos', [
                'mensaje'      => 'No encontramos un paciente asociado a tu cuenta. Consulta en el laboratorio.',
                'menuNav'      => RBAC::getMenu(),
                'nombreUsuario'=> $_SESSION['nombre_completo'] ?? $_SESSION['username'],
            ]);
            return;
        }

        $ordenes = $this->ordenModel->getOrdenesPaciente($paciente['id_paciente']);
        $menuNav = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('publica/portal_paciente', compact(
            'paciente', 'ordenes', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // 3. VISTA DEL MÉDICO (solo lectura)
    //    Ruta: /medico/resultados
    // ─────────────────────────────────────────

    public function vistaMedico(): void {
        RBAC::requerirPermiso('resultados.ver_medico');

        $db     = Database::getInstance();
        $secKey = $this->security->getMySQLEncryptionKey();

        // El médico solo ve órdenes donde él está asignado como id_medico
        // Los admins/analistas ven todas.
        $soloPropio = RBAC::esRol('medico');
        $params     = [];

        $sql = "SELECT o.id_orden, o.numero_orden, o.fecha_orden, o.estado,
                       AES_DECRYPT(p.nombres, '$secKey') AS pac_nombres,
                       AES_DECRYPT(p.apellidos, '$secKey') AS pac_apellidos,
                       COUNT(DISTINCT oe.id_examen) AS total_examenes,
                       SUM(r.es_critico) AS criticos
                FROM ordenes o
                JOIN pacientes p ON o.id_paciente = p.id_paciente
                LEFT JOIN orden_examenes oe ON oe.id_orden = o.id_orden
                LEFT JOIN resultados r ON r.id_orden_examen = oe.id_orden_examen
                WHERE o.eliminado = 0
                  AND o.estado IN ('resultados_cargados','validada','publicada')";

        if ($soloPropio) {
            $sql   .= " AND o.id_medico = ?";
            $params[] = $_SESSION['user_id'];
        }

        $sql .= " GROUP BY o.id_orden ORDER BY o.fecha_orden DESC LIMIT 100";

        $ordenes       = $db->query($sql, $params);
        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('publica/acceso_medico', compact(
            'ordenes', 'menuNav', 'nombreUsuario', 'soloPropio'
        ));
    }

    // ─────────────────────────────────────────
    // 4. DETALLE DE ORDEN (vista médico)
    //    Ruta: /medico/resultados/{id}
    // ─────────────────────────────────────────

    public function detalleMedico(int $idOrden): void {
        RBAC::requerirPermiso('resultados.ver_medico');

        $orden = $this->ordenModel->getResultadosOrden($idOrden);

        if (!$orden) {
            $this->flash('error', 'Orden no encontrada');
            header('Location: /medico/resultados');
            exit;
        }

        // Los médicos solo ven órdenes que les pertenecen
        if (RBAC::esRol('medico') && (int)$orden['id_medico'] !== (int)$_SESSION['user_id']) {
            // No es su paciente
            $this->flash('error', 'No tiene acceso a esta orden');
            header('Location: /medico/resultados');
            exit;
        }

        // Verificar que los resultados estén disponibles
        $estadosVisibles = ['resultados_cargados', 'validada', 'publicada'];
        if (!in_array($orden['estado'], $estadosVisibles, true)) {
            $this->flash('warning', 'Los resultados de esta orden aún no están disponibles');
            header('Location: /medico/resultados');
            exit;
        }

        $menuNav       = RBAC::getMenu();
        $nombreUsuario = $_SESSION['nombre_completo'] ?? $_SESSION['username'];

        $this->renderView('resultados/ver_medico', compact(
            'orden', 'menuNav', 'nombreUsuario'
        ));
    }

    // ─────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────

    /**
     * Renderizar vista de acceso autenticado (con menú y sesión).
     */
    private function renderView(string $view, array $datos = []): void {
        extract($datos);
        $flash     = $this->consumeFlash();
        $csrfToken = $this->security->generateCSRFToken();
        require_once __DIR__ . "/../views/{$view}.php";
    }

    /**
     * Renderizar vista pública (sin menú de sistema, sin sesión requerida).
     */
    private function renderPublica(string $view, array $datos = []): void {
        extract($datos);
        // No se necesita csrfToken ni menú en vistas públicas
        require_once __DIR__ . "/../views/{$view}.php";
    }

    private function flash(string $tipo, string $mensaje): void {
        $_SESSION['flash_type']    = $tipo;
        $_SESSION['flash_message'] = $mensaje;
    }

    private function consumeFlash(): array {
        $flash = [
            'type'    => $_SESSION['flash_type']    ?? null,
            'message' => $_SESSION['flash_message'] ?? null,
        ];
        unset($_SESSION['flash_type'], $_SESSION['flash_message']);
        return $flash;
    }
}
?>