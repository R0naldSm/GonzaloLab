<?php
// core/RBAC.php
// ============================================
// SISTEMA DE CONTROL DE ACCESO BASADO EN ROLES
// Role-Based Access Control (RBAC) - GonzaloLabs
// ============================================

class RBAC {

    /**
     * MATRIZ DE PERMISOS
     * Define qué puede hacer cada rol en cada módulo.
     *
     * Estructura:
     *   'permiso' => ['rol1', 'rol2', ...]
     *
     * Convenio de nombres:
     *   modulo.accion
     *   Ej: 'pacientes.crear', 'resultados.cargar', 'usuarios.admin'
     */
    private static array $permisos = [

        // ─────────────────────────────────────────
        // DASHBOARD
        // ─────────────────────────────────────────
        'dashboard.ver'             => ['administrador', 'analistaL'],
        'dashboard.estadisticas'    => ['administrador'],
        'dashboard.reportes'        => ['administrador'],

        // ─────────────────────────────────────────
        // USUARIOS (Administración interna)
        // ─────────────────────────────────────────
        'usuarios.ver'              => ['administrador'],
        'usuarios.crear'            => ['administrador'],
        'usuarios.editar'           => ['administrador'],
        'usuarios.desactivar'       => ['administrador'],
        'usuarios.resetear_clave'   => ['administrador'],

        // ─────────────────────────────────────────
        // PACIENTES
        // ─────────────────────────────────────────
        'pacientes.ver'             => ['administrador', 'analistaL'],
        'pacientes.crear'           => ['administrador', 'analistaL'],
        'pacientes.editar'          => ['administrador', 'analistaL'],
        'pacientes.eliminar'        => ['administrador'],
        'pacientes.historial'       => ['administrador', 'analistaL'],

        // ─────────────────────────────────────────
        // ÓRDENES DE LABORATORIO
        // ─────────────────────────────────────────
        'ordenes.ver'               => ['administrador', 'analistaL'],
        'ordenes.crear'             => ['administrador', 'analistaL'],
        'ordenes.editar'            => ['administrador', 'analistaL'],
        'ordenes.validar'           => ['administrador', 'analistaL'],
        'ordenes.eliminar'          => ['administrador'],
        'ordenes.publicar'          => ['administrador', 'analistaL'],

        // ─────────────────────────────────────────
        // EXÁMENES (Catálogo)
        // ─────────────────────────────────────────
        'examenes.ver'              => ['administrador', 'analistaL'],
        'examenes.crear'            => ['administrador'],
        'examenes.editar'           => ['administrador'],
        'examenes.eliminar'         => ['administrador'],
        'examenes.parametros'       => ['administrador'],
        'examenes.rangos'           => ['administrador'],
        'examenes.categorias'       => ['administrador'],

        // ─────────────────────────────────────────
        // RESULTADOS
        // ─────────────────────────────────────────
        'resultados.ver_completo'   => ['administrador', 'analistaL'],
        'resultados.ver_medico'     => ['administrador', 'analistaL', 'medico'],
        'resultados.cargar_manual'  => ['administrador', 'analistaL'],
        'resultados.cargar_auto'    => ['administrador', 'analistaL'],
        'resultados.editar'         => ['administrador', 'analistaL'],
        'resultados.validar'        => ['administrador', 'analistaL'],
        'resultados.ver_alertas'    => ['administrador', 'analistaL'],

        // ─────────────────────────────────────────
        // PORTAL PACIENTE (acceso propio)
        // ─────────────────────────────────────────
        'portal.ver_propios'        => ['paciente'],
        'portal.acceso_token'       => ['paciente', 'medico', 'administrador'],

        // ─────────────────────────────────────────
        // COTIZACIONES
        // ─────────────────────────────────────────
        'cotizaciones.ver'          => ['administrador', 'analistaL'],
        'cotizaciones.crear'        => ['administrador', 'analistaL'],
        'cotizaciones.editar'       => ['administrador', 'analistaL'],
        'cotizaciones.eliminar'     => ['administrador'],
        'cotizaciones.exportar'     => ['administrador', 'analistaL'],

        // ─────────────────────────────────────────
        // FACTURACIÓN / QR
        // ─────────────────────────────────────────
        'facturas.ver'              => ['administrador', 'analistaL'],
        'facturas.crear'            => ['administrador', 'analistaL'],
        'facturas.anular'           => ['administrador'],
        'facturas.generar_qr'       => ['administrador', 'analistaL'],

        // ─────────────────────────────────────────
        // REPORTES Y EXPORTACIÓN
        // ─────────────────────────────────────────
        'reportes.ver'              => ['administrador'],
        'reportes.exportar_excel'   => ['administrador'],
        'reportes.exportar_pdf'     => ['administrador'],
        'reportes.auditoria'        => ['administrador'],

        // ─────────────────────────────────────────
        // CONFIGURACIÓN DEL SISTEMA
        // ─────────────────────────────────────────
        'config.ver'                => ['administrador'],
        'config.editar'             => ['administrador'],
        'config.tiempos_examenes'   => ['administrador'],
        'config.alertas'            => ['administrador'],
    ];

    /**
     * Menú de navegación por rol.
     * Controla qué ítems del sidebar/navbar ve cada rol.
     */
    private static array $menuPorRol = [
        'administrador' => [
            ['label' => 'Dashboard',      'url' => '/dashboard',    'icono' => 'bi-speedometer2'],
            ['label' => 'Pacientes',      'url' => '/pacientes',    'icono' => 'bi-person-lines-fill'],
            ['label' => 'Órdenes',        'url' => '/ordenes',      'icono' => 'bi-clipboard2-pulse'],
            ['label' => 'Resultados',     'url' => '/resultados',   'icono' => 'bi-file-earmark-medical'],
            ['label' => 'Exámenes',       'url' => '/examenes',     'icono' => 'bi-eyedropper'],
            ['label' => 'Cotizaciones',   'url' => '/cotizaciones', 'icono' => 'bi-receipt'],
            ['label' => 'Facturas',       'url' => '/facturas',     'icono' => 'bi-cash-stack'],
            ['label' => 'Reportes',       'url' => '/reportes',     'icono' => 'bi-bar-chart-line'],
            ['label' => 'Usuarios',       'url' => '/usuarios',     'icono' => 'bi-people'],
            ['label' => 'Configuración',  'url' => '/configuracion','icono' => 'bi-gear'],
        ],
        'analistaL' => [
            ['label' => 'Dashboard',      'url' => '/dashboard',    'icono' => 'bi-speedometer2'],
            ['label' => 'Pacientes',      'url' => '/pacientes',    'icono' => 'bi-person-lines-fill'],
            ['label' => 'Órdenes',        'url' => '/ordenes',      'icono' => 'bi-clipboard2-pulse'],
            ['label' => 'Resultados',     'url' => '/resultados',   'icono' => 'bi-file-earmark-medical'],
            ['label' => 'Exámenes',       'url' => '/examenes',     'icono' => 'bi-eyedropper'],
            ['label' => 'Cotizaciones',   'url' => '/cotizaciones', 'icono' => 'bi-receipt'],
            ['label' => 'Facturas',       'url' => '/facturas',     'icono' => 'bi-cash-stack'],
        ],
        'medico' => [
            ['label' => 'Mis Pacientes',  'url' => '/medico/resultados', 'icono' => 'bi-file-earmark-medical'],
        ],
        'paciente' => [
            ['label' => 'Mis Resultados', 'url' => '/portal/resultados', 'icono' => 'bi-file-earmark-text'],
        ],
    ];

    // ============================================
    // VERIFICACIÓN DE PERMISOS
    // ============================================

    /**
     * Verificar si el usuario en sesión tiene un permiso específico.
     *
     * @param string $permiso  Ej: 'resultados.cargar_manual'
     * @return bool
     */
    public static function puede(string $permiso): bool {
        $rol = $_SESSION['user_rol'] ?? null;

        if ($rol === null) {
            return false;
        }

        if (!isset(self::$permisos[$permiso])) {
            // Permiso no definido → denegar por defecto
            return false;
        }

        return in_array($rol, self::$permisos[$permiso], true);
    }

    /**
     * Verificar si el usuario tiene AL MENOS UNO de varios permisos.
     *
     * @param array $permisos
     * @return bool
     */
    public static function puedeAlguno(array $permisos): bool {
        foreach ($permisos as $permiso) {
            if (self::puede($permiso)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verificar si el usuario tiene TODOS los permisos indicados.
     *
     * @param array $permisos
     * @return bool
     */
    public static function puedeTodos(array $permisos): bool {
        foreach ($permisos as $permiso) {
            if (!self::puede($permiso)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verificar si el usuario tiene un rol específico.
     *
     * @param string|array $roles
     * @return bool
     */
    public static function esRol(string|array $roles): bool {
        $rolActual = $_SESSION['user_rol'] ?? null;
        if ($rolActual === null) return false;

        if (is_string($roles)) {
            return $rolActual === $roles;
        }

        return in_array($rolActual, $roles, true);
    }

    // ============================================
    // ENFORCEMENT (forzar en controladores)
    // ============================================

    /**
     * Detener ejecución si el usuario NO tiene el permiso.
     * Redirige con mensaje de acceso denegado.
     *
     * @param string $permiso
     * @param string $redirigirA  URL de destino si se deniega
     */
    public static function requerirPermiso(string $permiso, string $redirigirA = '/dashboard'): void {
        if (!self::puede($permiso)) {
            self::denegarAcceso($redirigirA);
        }
    }

    /**
     * Detener ejecución si el usuario NO tiene alguno de los permisos.
     *
     * @param array  $permisos
     * @param string $redirigirA
     */
    public static function requerirAlguno(array $permisos, string $redirigirA = '/dashboard'): void {
        if (!self::puedeAlguno($permisos)) {
            self::denegarAcceso($redirigirA);
        }
    }

    /**
     * Detener ejecución si el usuario NO es alguno de los roles.
     *
     * @param string|array $roles
     * @param string       $redirigirA
     */
    public static function requerirRol(string|array $roles, string $redirigirA = '/dashboard'): void {
        if (!self::esRol($roles)) {
            self::denegarAcceso($redirigirA);
        }
    }

    // ============================================
    // MENÚ DINÁMICO
    // ============================================

    /**
     * Obtener los ítems de menú del rol actual.
     *
     * @return array
     */
    public static function getMenu(): array {
        $rol = $_SESSION['user_rol'] ?? 'paciente';
        return self::$menuPorRol[$rol] ?? [];
    }

    // ============================================
    // HELPERS PARA VISTAS (usar en HTML/PHP)
    // ============================================

    /**
     * Imprimir atributo disabled si no tiene permiso.
     * Uso en vistas: <button <?= RBAC::disabledSi('resultados.editar') ?>>
     *
     * @param string $permiso
     * @return string
     */
    public static function disabledSi(string $permiso): string {
        return self::puede($permiso) ? '' : 'disabled title="Sin permisos para esta acción"';
    }

    /**
     * Devolver clase CSS 'd-none' si no tiene permiso (Bootstrap).
     * Uso: <div class="<?= RBAC::ocultarSi('usuarios.ver') ?>">
     *
     * @param string $permiso
     * @return string
     */
    public static function ocultarSi(string $permiso): string {
        return self::puede($permiso) ? '' : 'd-none';
    }

    /**
     * Mostrar contenido HTML solo si tiene el permiso.
     * Uso: <?= RBAC::soloSi('reportes.ver', '<a href="/reportes">Reportes</a>') ?>
     *
     * @param string $permiso
     * @param string $html
     * @return string
     */
    public static function soloSi(string $permiso, string $html): string {
        return self::puede($permiso) ? $html : '';
    }

    // ============================================
    // INTERNOS
    // ============================================

    /**
     * Registrar y redirigir acceso denegado.
     *
     * @param string $redirigirA
     */
    private static function denegarAcceso(string $redirigirA): void {
        // Log de seguridad
        $rolActual  = $_SESSION['user_rol']  ?? 'sin_rol';
        $userId     = $_SESSION['user_id']   ?? 0;
        $uri        = $_SERVER['REQUEST_URI'] ?? 'desconocida';
        $ip         = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';

        // Guardar en log de seguridad
        $logMsg = sprintf(
            "[%s] ACCESO_DENEGADO | usuario_id=%d | rol=%s | ruta=%s | ip=%s\n",
            date('Y-m-d H:i:s'), $userId, $rolActual, $uri, $ip
        );
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) mkdir($logDir, 0700, true);
        file_put_contents($logDir . '/accesos.log', $logMsg, FILE_APPEND | LOCK_EX);

        // Intentar registrar en BD (no bloquear si falla)
        try {
            if (class_exists('Database')) {
                $db = Database::getInstance();
                $db->execute(
                    "INSERT INTO auditoria (tabla, operacion, usuario_id, username, ip_address, user_agent)
                     VALUES ('acceso', 'ACCESO_DENEGADO', ?, ?, ?, ?)",
                    [
                        $userId,
                        $_SESSION['username'] ?? 'desconocido',
                        $ip,
                        $_SERVER['HTTP_USER_AGENT'] ?? ''
                    ]
                );
            }
        } catch (Throwable) {
            // Silencioso: no bloquear el redirect por un error de BD
        }

        // Flash message y redirect
        $_SESSION['flash_message'] = 'No tienes permisos para acceder a esa sección.';
        $_SESSION['flash_type']    = 'danger';

        header("Location: $redirigirA");
        exit;
    }
}
?>