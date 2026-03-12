<?php
/**
 * publica/acceso_medico.php
 * Vista del médico autenticado — Ruta: /medico/resultados
 * Rol: 'medico' (RBAC aplicado en el controlador antes de llegar aquí)
 *
 * NOTA RBAC (importante):
 * ─ Esta vista SÍ requiere sesión con rol='medico'.
 * ─ El middleware ya verificó 'resultados.ver_medico' antes de renderizar.
 * ─ El médico SOLO ve las órdenes asociadas a su id_usuario (filtro en modelo).
 * ─ No se puede acceder a información de otros médicos desde esta vista.
 * ─ No incluye acciones de validar/publicar/cargar (solo visualización).
 *
 * Variables: $ordenes, $menuNav, $nombreUsuario, $csrfToken, $flash
 */
$pageTitle = 'Resultados de mis pacientes';
require_once __DIR__ . '/../layouts/header.php';

$estadoMap = [
    'resultados_cargados' => ['badge-purple',  'Resultados listos'],
    'validada'            => ['badge-info',    'Validada'],
    'publicada'           => ['badge-success', 'Publicada'],
    'en_proceso'          => ['badge-warning', 'En proceso'],
    'creada'              => ['badge-gray',    'Pendiente'],
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 style="font-size:1.45rem;font-weight:800;color:#0f172a;margin:0">Mis Pacientes</h1>
        <p style="font-size:.82rem;color:#64748b;margin:.15rem 0 0">
            Órdenes y resultados asignados a usted como médico solicitante
        </p>
    </div>
    <div style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);border:1px solid #6ee7b7;border-radius:.625rem;padding:.5rem 1rem;font-size:.82rem;font-weight:600;color:#065f46;display:flex;align-items:center;gap:.5rem">
        <i class="bi bi-eye" style="font-size:.9rem"></i>
        Solo lectura — Modo médico
    </div>
</div>

<!-- Filtros -->
<div class="gl-card mb-4">
    <div class="gl-card-body">
        <form method="GET" action="/medico/resultados" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="gl-label">Buscar paciente</label>
                <input type="text" name="q" class="gl-input" placeholder="Nombre o cédula…"
                    value="<?= htmlspecialchars($filtros['busqueda'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="gl-label">Estado</label>
                <select name="estado" class="gl-input gl-select">
                    <option value="">Todos</option>
                    <?php foreach ($estadoMap as $v=>[$c,$l]): ?>
                    <option value="<?= $v ?>" <?= ($filtros['estado'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn-gl btn-primary-gl flex-fill">
                    <i class="bi bi-search"></i> Buscar
                </button>
                <a href="/medico/resultados" class="btn-gl btn-outline-gl" title="Limpiar">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de órdenes -->
<?php if (empty($ordenes)): ?>
<div class="gl-card">
    <div class="gl-card-body text-center py-5" style="color:#94a3b8">
        <i class="bi bi-clipboard2-x" style="font-size:3rem;display:block;margin-bottom:.875rem;opacity:.3"></i>
        <div style="font-size:.95rem;font-weight:600;margin-bottom:.35rem">No hay órdenes asignadas</div>
        <div style="font-size:.83rem">
            <?= !empty($filtros['busqueda']) || !empty($filtros['estado'])
                ? 'No hay resultados para los filtros seleccionados.'
                : 'Aún no tiene órdenes de laboratorio asignadas a su nombre.' ?>
        </div>
    </div>
</div>
<?php else: ?>

<div class="row g-3">
    <?php foreach ($ordenes as $o): ?>
    <?php
        [$estCls, $estLbl] = $estadoMap[$o['estado'] ?? ''] ?? ['badge-gray', ucfirst($o['estado'] ?? '')];
        $paciente   = trim(($o['pac_nombres'] ?? '') . ' ' . ($o['pac_apellidos'] ?? '')) ?: '—';
        $criticos   = (int)($o['criticos'] ?? 0);
        $tieneResult= in_array($o['estado'] ?? '', ['resultados_cargados','validada','publicada']);
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="gl-card h-100" style="border-left:4px solid <?= $criticos > 0 ? '#ef4444' : ($tieneResult ? '#10b981' : '#e2e8f0') ?>;transition:box-shadow .2s"
             onmouseenter="this.style.boxShadow='0 4px 20px rgba(0,0,0,.08)'"
             onmouseleave="this.style.boxShadow=''">
            <div class="gl-card-body">
                <!-- Cabecera: nombre + estado -->
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;font-size:.95rem;font-weight:700;color:#fff;flex-shrink:0">
                            <?= strtoupper(substr($o['pac_nombres'] ?? 'P', 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:.88rem;color:#0f172a;line-height:1.3">
                                <?= htmlspecialchars(mb_substr($paciente, 0, 24)) ?>
                            </div>
                            <code style="font-size:.72rem;color:#94a3b8"><?= htmlspecialchars($o['pac_cedula'] ?? '') ?></code>
                        </div>
                    </div>
                    <span class="gl-badge <?= $estCls ?>" style="font-size:.67rem;white-space:nowrap"><?= $estLbl ?></span>
                </div>

                <!-- Datos de la orden -->
                <div style="font-size:.78rem;color:#64748b;margin-bottom:.875rem">
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="bi bi-receipt me-1"></i>N° Orden</span>
                        <span class="fw-semibold" style="font-family:monospace;color:var(--primary)">
                            <?= htmlspecialchars($o['numero_orden'] ?? '') ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span><i class="bi bi-calendar3 me-1"></i>Fecha</span>
                        <span><?= !empty($o['fecha_orden']) ? date('d/m/Y', strtotime($o['fecha_orden'])) : '—' ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-flask me-1"></i>Exámenes</span>
                        <span class="gl-badge badge-info"><?= (int)($o['total_examenes'] ?? 0) ?></span>
                    </div>
                </div>

                <!-- Alerta críticos -->
                <?php if ($criticos > 0): ?>
                <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:.5rem;padding:.5rem .75rem;font-size:.77rem;color:#7f1d1d;margin-bottom:.875rem;display:flex;align-items:center;gap:.4rem">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong><?= $criticos ?> valor<?= $criticos > 1 ? 'es' : '' ?> crítico<?= $criticos > 1 ? 's' : '' ?></strong> — requiere atención
                </div>
                <?php endif; ?>

                <!-- Acciones -->
                <div class="d-flex gap-2">
                    <?php if ($tieneResult): ?>
                    <a href="/medico/resultados/<?= $o['id_orden'] ?>"
                       class="btn-gl btn-primary-gl flex-fill" style="justify-content:center;font-size:.8rem">
                        <i class="bi bi-eye"></i> Ver resultados
                    </a>
                    <?php else: ?>
                    <div class="flex-fill text-center" style="font-size:.78rem;color:#94a3b8;padding:.5rem;background:#f8fafc;border-radius:.5rem">
                        <i class="bi bi-hourglass me-1"></i>Resultados pendientes
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Paginación simple si hay muchos -->
<?php if (count($ordenes) >= 20): ?>
<div class="text-center mt-4" style="font-size:.82rem;color:#94a3b8">
    Mostrando <?= count($ordenes) ?> resultados.
    <a href="?<?= http_build_query(array_merge($filtros ?? [], ['todos' => 1])) ?>" style="color:var(--primary)">Cargar más</a>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Panel informativo para médicos -->
<div class="gl-card mt-4" style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);border:none">
    <div class="gl-card-body">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <div style="width:44px;height:44px;border-radius:.75rem;background:var(--secondary);display:flex;align-items:center;justify-content:center">
                    <i class="bi bi-info-circle-fill" style="color:#fff;font-size:1.2rem"></i>
                </div>
            </div>
            <div class="col">
                <div style="font-size:.875rem;font-weight:700;color:#1e3a5f;margin-bottom:.2rem">Vista del Médico — Solo lectura</div>
                <div style="font-size:.78rem;color:#1e40af">
                    Usted puede consultar los resultados de sus pacientes. Para solicitar correcciones o aclaraciones sobre los resultados, contacte al laboratorio directamente. Los valores fuera de rango están marcados y los críticos requieren atención inmediata.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>