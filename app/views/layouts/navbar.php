<?php
/**
 * layouts/navbar.php — Barra superior (topbar)
 * Incluido automáticamente desde layouts/header.php.
 *
 * Variables esperadas:
 *   $pageTitle        — string
 *   $alertasCriticas  — int (opcional, muestra badge en campana)
 *   $flash            — array ['type'=>'...','message'=>'...'] (opcional)
 */
$alertasCriticas = $alertasCriticas ?? 0;
$breadcrumbs     = $breadcrumbs     ?? [];   // array de ['label'=>'...','url'=>'...']
?>

<header id="topbar" role="banner">

    <!-- Botón hamburguesa mobile -->
    <button class="topbar-btn d-md-none me-1"
            onclick="document.getElementById('sidebar').classList.toggle('show')"
            aria-label="Abrir menú" aria-controls="sidebar">
        <i class="bi bi-list" style="font-size:1.25rem"></i>
    </button>

    <!-- Breadcrumb / Título -->
    <div class="flex-fill" style="min-width:0">
        <?php if (!empty($breadcrumbs)): ?>
        <nav aria-label="Ruta de navegación" style="font-size:.78rem;color:#94a3b8">
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                <?php if ($i > 0): ?><i class="bi bi-chevron-right" style="font-size:.65rem;margin:0 .3rem"></i><?php endif; ?>
                <?php if (!empty($crumb['url']) && $i < count($breadcrumbs)-1): ?>
                    <a href="<?= htmlspecialchars($crumb['url']) ?>" style="color:#94a3b8;text-decoration:none"><?= htmlspecialchars($crumb['label']) ?></a>
                <?php else: ?>
                    <span style="color:#374151;font-weight:600"><?= htmlspecialchars($crumb['label']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php else: ?>
        <span class="page-title"><?= htmlspecialchars($pageTitle ?? 'GonzaloLabs') ?></span>
        <?php endif; ?>
    </div>

    <!-- Flash message inline (compacto) -->
    <?php if (!empty($flash['message'])): ?>
    <div class="gl-alert gl-alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?>"
         id="topbarFlash"
         style="margin:0;padding:.45rem .875rem;font-size:.8rem;max-width:380px;border-radius:.5rem">
        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'x-circle' : 'info-circle') ?>"
           style="flex-shrink:0"></i>
        <span><?= htmlspecialchars($flash['message']) ?></span>
    </div>
    <?php endif; ?>

    <!-- Acciones topbar -->
    <div class="d-flex align-items-center gap-1">

        <!-- Alertas críticas -->
        <a href="/resultados/alertas"
           class="topbar-btn position-relative"
           title="<?= $alertasCriticas > 0 ? "$alertasCriticas alerta(s) crítica(s)" : 'Sin alertas críticas' ?>">
            <i class="bi bi-bell<?= $alertasCriticas > 0 ? '-fill' : '' ?>"
               style="<?= $alertasCriticas > 0 ? 'color:#ef4444' : '' ?>"></i>
            <?php if ($alertasCriticas > 0): ?>
            <span class="position-absolute top-0 end-0 badge rounded-pill bg-danger"
                  style="font-size:.55rem;padding:.2em .4em;min-width:16px;transform:translate(4px,-4px)">
                <?= $alertasCriticas > 9 ? '9+' : $alertasCriticas ?>
            </span>
            <?php endif; ?>
        </a>

        <!-- Ayuda rápida -->
        <div class="dropdown">
            <button class="topbar-btn" data-bs-toggle="dropdown" aria-label="Ayuda">
                <i class="bi bi-question-circle"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width:190px;border-radius:.625rem;border:1px solid #e2e8f0">
                <li class="dropdown-header" style="font-size:.72rem;color:#94a3b8;padding:.5rem 1rem">
                    GonzaloLabs v1.0
                </li>
                <li><a class="dropdown-item" href="/dashboard" style="font-size:.83rem">
                    <i class="bi bi-house me-2"></i>Inicio
                </a></li>
                <li><a class="dropdown-item" href="mailto:soporte@gonzalolabs.com" style="font-size:.83rem">
                    <i class="bi bi-envelope me-2"></i>Soporte técnico
                </a></li>
            </ul>
        </div>

        <!-- Avatar / logout -->
        <div class="dropdown">
            <button class="topbar-btn" data-bs-toggle="dropdown" aria-label="Mi cuenta">
                <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;color:#fff;font-size:.72rem;font-weight:700">
                    <?= strtoupper(substr($nombreUsuario ?? 'U', 0, 1)) ?>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width:180px;border-radius:.625rem;border:1px solid #e2e8f0">
                <li class="px-3 py-2" style="font-size:.8rem;font-weight:600;color:#0f172a;border-bottom:1px solid #f1f5f9">
                    <?= htmlspecialchars($nombreUsuario ?? 'Usuario') ?>
                </li>
                <li><a class="dropdown-item" href="/cambiar-password" style="font-size:.83rem">
                    <i class="bi bi-key me-2"></i>Cambiar contraseña
                </a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item text-danger" href="/logout" style="font-size:.83rem">
                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión
                </a></li>
            </ul>
        </div>
    </div>
</header>

<?php if (!empty($flash['message'])): ?>
<script>
// Auto-cerrar flash del topbar
setTimeout(() => {
    const el = document.getElementById('topbarFlash');
    if (el) { el.style.transition = 'opacity .4s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }
}, 5000);
</script>
<?php endif; ?>