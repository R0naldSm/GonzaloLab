<?php
// core/Middleware.php
// ============================================
// MIDDLEWARE DE AUTENTICACIÓN Y RBAC
// Se invoca antes de cada controlador en el router
// ============================================

require_once __DIR__ . '/RBAC.php';
require_once __DIR__ . '/Security.php';

class Middleware {

    private static Security $security;

    // ─────────────────────────────────────────────────────────────────
    // TABLA DE RUTAS → PERMISOS REQUERIDOS
    // Agregar aquí cada ruta nueva con su permiso correspondiente.
    // ─────────────────────────────────────────────────────────────────
    private static array $rutasProtegidas = [

        // Dashboard
        '/dashboard'                    => ['permiso' => 'dashboard.ver'],

        // Pacientes
        '/pacientes'                    => ['permiso' => 'pacientes.ver'],
        '/pacientes/crear'              => ['permiso' => 'pacientes.crear'],
        '/pacientes/editar'             => ['permiso' => 'pacientes.editar'],
        '/pacientes/eliminar'           => ['permiso' => 'pacientes.eliminar'],
        '/pacientes/historial'          => ['permiso' => 'pacientes.historial'],

        // Órdenes
        '/ordenes'                      => ['permiso' => 'ordenes.ver'],
        '/ordenes/crear'                => ['permiso' => 'ordenes.crear'],
        '/ordenes/editar'               => ['permiso' => 'ordenes.editar'],
        '/ordenes/validar'              => ['permiso' => 'ordenes.validar'],
        '/ordenes/publicar'             => ['permiso' => 'ordenes.publicar'],

        // Resultados
        '/resultados'                   => ['permiso' => 'resultados.ver_completo'],
        '/resultados/cargar'            => ['permiso' => 'resultados.cargar_manual'],
        '/resultados/cargar-automatico' => ['permiso' => 'resultados.cargar_auto'],
        '/resultados/editar'            => ['permiso' => 'resultados.editar'],
        '/resultados/validar'           => ['permiso' => 'resultados.validar'],
        '/resultados/alertas'           => ['permiso' => 'resultados.ver_alertas'],

        // Médico (solo lectura de resultados)
        '/medico/resultados'            => ['permiso' => 'resultados.ver_medico'],

        // Portal paciente
        '/portal/resultados'            => ['permiso' => 'portal.ver_propios'],

        // Exámenes
        '/examenes'                     => ['permiso' => 'examenes.ver'],
        '/examenes/crear'               => ['permiso' => 'examenes.crear'],
        '/examenes/editar'              => ['permiso' => 'examenes.editar'],
        '/examenes/eliminar'            => ['permiso' => 'examenes.eliminar'],
        '/examenes/parametros'          => ['permiso' => 'examenes.parametros'],

        // Cotizaciones
        '/cotizaciones'                 => ['permiso' => 'cotizaciones.ver'],
        '/cotizaciones/crear'           => ['permiso' => 'cotizaciones.crear'],
        '/cotizaciones/editar'          => ['permiso' => 'cotizaciones.editar'],
        '/cotizaciones/exportar'        => ['permiso' => 'cotizaciones.exportar'],

        // Facturas
        '/facturas'                     => ['permiso' => 'facturas.ver'],
        '/facturas/crear'               => ['permiso' => 'facturas.crear'],
        '/facturas/anular'              => ['permiso' => 'facturas.anular'],
        '/facturas/qr'                  => ['permiso' => 'facturas.generar_qr'],

        // Reportes
        '/reportes'                     => ['permiso' => 'reportes.ver'],
        '/reportes/exportar'            => ['permiso' => 'reportes.exportar_excel'],
        '/reportes/auditoria'           => ['permiso' => 'reportes.auditoria'],

        // Usuarios
        '/usuarios'                     => ['permiso' => 'usuarios.ver'],
        '/usuarios/crear'               => ['permiso' => 'usuarios.crear'],
        '/usuarios/editar'              => ['permiso' => 'usuarios.editar'],
        '/usuarios/desactivar'          => ['permiso' => 'usuarios.desactivar'],

        // Configuración
        '/configuracion'                => ['permiso' => 'config.ver'],
        '/configuracion/editar'         => ['permiso' => 'config.editar'],
    ];

    // ─────────────────────────────────────────────────────────────────
    // Rutas completamente públicas (sin sesión)
    // ─────────────────────────────────────────────────────────────────
    private static array $rutasPublicas = [
        '/',
        '/login',
        '/logout',
        '/recuperar-password',
        '/cambiar-password',
        '/consulta',          // Acceso por token QR
        '/verificar-sesion',
    ];

    // ============================================
    // MÉTODO PRINCIPAL
    // ============================================

    /**
     * Ejecutar el middleware para la ruta actual.
     * Llamar desde index.php antes de despachar el controlador.
     *
     * @param string $ruta  La ruta limpia (sin query string)
     */
    public static function handle(string $ruta): void {
        self::$security = Security::getInstance();
        self::$security->initSecureSession();

        // 1. Rutas públicas → pasar siempre
        if (self::esPublica($ruta)) {
            return;
        }

        // 2. Verificar autenticación
        if (!self::$security->isValidSession()) {
            $_SESSION['flash_message'] = 'Debe iniciar sesión para continuar.';
            $_SESSION['flash_type']    = 'warning';
            $_SESSION['redirect_after_login'] = $ruta; // Recordar destino
            header('Location: /login');
            exit;
        }

        // 3. Verificar RBAC si la ruta tiene permiso definido
        $rutaNormalizada = self::normalizarRuta($ruta);

        if (isset(self::$rutasProtegidas[$rutaNormalizada])) {
            $permiso = self::$rutasProtegidas[$rutaNormalizada]['permiso'];
            RBAC::requerirPermiso($permiso, self::getRedirectPorRol());
        }

        // 4. Actualizar actividad
        $_SESSION['last_activity'] = time();
    }

    // ============================================
    // HELPERS
    // ============================================

    /**
     * Verificar si la ruta es pública.
     */
    private static function esPublica(string $ruta): bool {
        // Exact match
        if (in_array($ruta, self::$rutasPublicas, true)) {
            return true;
        }

        // Prefijos públicos (ej: /consulta/token123abc)
        $prefijosPublicos = ['/consulta/'];
        foreach ($prefijosPublicos as $prefijo) {
            if (str_starts_with($ruta, $prefijo)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalizar ruta para matchear la tabla de permisos.
     * Ej: /pacientes/editar/42  →  /pacientes/editar
     */
    private static function normalizarRuta(string $ruta): string {
        // Eliminar segmentos numéricos finales (IDs)
        return preg_replace('#/\d+$#', '', $ruta);
    }

    /**
     * URL de redirección apropiada según el rol actual.
     */
    private static function getRedirectPorRol(): string {
        $rol = $_SESSION['user_rol'] ?? 'paciente';
        return match($rol) {
            'administrador' => '/dashboard',
            'analistaL'     => '/dashboard',
            'medico'        => '/medico/resultados',
            'paciente'      => '/portal/resultados',
            default         => '/login',
        };
    }
}
?>