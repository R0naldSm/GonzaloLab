<?php
/**
 * layouts/sidebar.php — Sidebar de navegación lateral
 * Incluido automáticamente desde layouts/header.php.
 * También puede incluirse de forma independiente si se necesita
 * reconstruir el layout manualmente.
 *
 * Variables esperadas (heredadas del contexto):
 *   $menuNav       — array de RBAC::getMenu()
 *   $nombreUsuario — string
 *   $_SESSION['user_rol']
 */

$rolData = [
    'administrador' => ['Administrador', '#7c3aed', 'bi-shield-check'],
    'analistaL'     => ['Analista Lab',  '#0891b2', 'bi-eyedropper'],
    'medico'        => ['Médico',        '#059669', 'bi-heart-pulse'],
    'paciente'      => ['Paciente',      '#d97706', 'bi-person'],
][$_SESSION['user_rol'] ?? ''] ?? ['Usuario', '#6b7280', 'bi-person'];

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>

<aside id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                 stroke="white" stroke-width="2.5" stroke-linecap="round">
                <path d="M12 3L20 9V21H4V9L12 3Z"/>
                <line x1="9" y1="21" x2="9" y2="12"/>
                <line x1="15" y1="21" x2="15" y2="12"/>
                <line x1="9" y1="12" x2="15" y2="12"/>
            </svg>
        </div>
        <div>
            <h2>GonzaloLabs</h2>
            <p>Laboratorio Clínico</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav" aria-label="Navegación principal">
        <?php if (!empty($menuNav)):
            // Detectar si hay items con sección definida o generar sección única
            $conSecciones = !empty(array_filter($menuNav, fn($i) => ($i['type'] ?? '') === 'section'));
            if (!$conSecciones && count($menuNav) > 0):
        ?>
        <div class="nav-section">Menú</div>
        <?php endif; ?>

        <?php foreach ($menuNav as $item): ?>
            <?php if (($item['type'] ?? '') === 'section'): ?>
                <div class="nav-section"><?= htmlspecialchars($item['label']) ?></div>
            <?php else:
                $url      = $item['url'] ?? '#';
                $icono    = $item['icono'] ?? 'bi-circle';
                $label    = $item['label'] ?? '';
                $badge    = $item['badge'] ?? null;
                // Active si la ruta actual comienza con la URL del item
                $isActive = ($url !== '#') && str_starts_with($currentPath, $url);
            ?>
                <a href="<?= htmlspecialchars($url) ?>"
                   class="sidebar-link <?= $isActive ? 'active' : '' ?>"
                   <?= $isActive ? 'aria-current="page"' : '' ?>>
                    <i class="bi <?= htmlspecialchars($icono) ?>" aria-hidden="true"></i>
                    <span><?= htmlspecialchars($label) ?></span>
                    <?php if ($badge): ?>
                    <span class="badge-nav badge bg-danger"><?= (int)$badge ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($menuNav)): ?>
        <!-- Fallback si menuNav está vacío -->
        <div class="nav-section">Sistema</div>
        <a href="/dashboard"    class="sidebar-link"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
        <a href="/ordenes"      class="sidebar-link"><i class="bi bi-clipboard2-pulse"></i><span>Órdenes</span></a>
        <a href="/pacientes"    class="sidebar-link"><i class="bi bi-person-lines-fill"></i><span>Pacientes</span></a>
        <a href="/examenes"     class="sidebar-link"><i class="bi bi-eyedropper"></i><span>Exámenes</span></a>
        <a href="/resultados"   class="sidebar-link"><i class="bi bi-file-earmark-medical"></i><span>Resultados</span></a>
        <a href="/cotizaciones" class="sidebar-link"><i class="bi bi-receipt"></i><span>Cotizaciones</span></a>
        <a href="/facturas"     class="sidebar-link"><i class="bi bi-cash-stack"></i><span>Facturas / QR</span></a>
        <a href="/reportes"     class="sidebar-link"><i class="bi bi-bar-chart-line"></i><span>Reportes</span></a>
        <a href="/usuarios"     class="sidebar-link"><i class="bi bi-people"></i><span>Usuarios</span></a>
        <?php endif; ?>
    </nav>

    <!-- User footer -->
    <div class="sidebar-footer">
        <div class="user-card" title="Perfil">
            <div class="user-avatar">
                <?= strtoupper(substr($nombreUsuario ?? 'U', 0, 1)) ?>
            </div>
            <div style="min-width:0;flex:1">
                <div class="user-name"><?= htmlspecialchars($nombreUsuario ?? 'Usuario') ?></div>
                <span class="user-role gl-badge"
                      style="background:<?= $rolData[1] ?>22;color:<?= $rolData[1] ?>;font-size:.65rem">
                    <i class="bi <?= $rolData[2] ?>" style="font-size:.65rem"></i>
                    <?= $rolData[0] ?>
                </span>
            </div>
            <a href="/logout" title="Cerrar sesión"
               style="color:rgba(255,255,255,.35);font-size:1.05rem;padding:.2rem;text-decoration:none;transition:color .2s"
               onmouseover="this.style.color='#ef4444'"
               onmouseout="this.style.color='rgba(255,255,255,.35)'">
                <i class="bi bi-box-arrow-right" aria-label="Cerrar sesión"></i>
            </a>
        </div>
    </div>
</aside> <?php endif; ?>