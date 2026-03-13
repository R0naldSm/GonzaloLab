<?php
// Variables: $alertas, $menuNav, $nombreUsuario, $csrfToken, $flash
$pageTitle        = 'Alertas Críticas';
$alertasCriticas  = count($alertas ?? []);
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 style="font-size:1.45rem;font-weight:800;color:#0f172a;margin:0">
            <i class="bi bi-exclamation-triangle-fill me-2" style="color:#ef4444"></i>
            Alertas Críticas
        </h1>
        <p style="font-size:.82rem;color:#64748b;margin:.15rem 0 0">Valores fuera de rango crítico sin validar</p>
    </div>
    <?php if ($alertasCriticas > 0): ?>
    <span class="gl-badge badge-danger" style="font-size:.9rem;padding:.45rem .875rem;animation:pulse 2s infinite">
        <?= $alertasCriticas ?> alerta<?= $alertasCriticas > 1 ? 's' : '' ?> activa<?= $alertasCriticas > 1 ? 's' : '' ?>
    </span>
    <?php endif; ?>
</div>

<?php if (empty($alertas)): ?>
<div class="gl-card">
    <div class="gl-card-body text-center py-5">
        <div style="width:72px;height:72px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
            <i class="bi bi-check-circle-fill" style="font-size:2rem;color:#10b981"></i>
        </div>
        <h3 style="font-size:1.1rem;font-weight:700;color:#065f46;margin-bottom:.35rem">Sin alertas críticas</h3>
        <p style="font-size:.85rem;color:#64748b">Todos los valores críticos han sido validados o no hay resultados pendientes.</p>
        <a href="/resultados" class="btn-gl btn-outline-gl mt-3" style="display:inline-flex">
            <i class="bi bi-arrow-left"></i> Ver resultados
        </a>
    </div>
</div>
<?php else: ?>

<!-- Banner de urgencia -->
<div style="background:linear-gradient(135deg,#fef2f2,#fee2e2);border:2px solid #fca5a5;border-radius:.875rem;padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:.875rem">
    <i class="bi bi-exclamation-octagon-fill" style="color:#dc2626;font-size:1.5rem;flex-shrink:0"></i>
    <div>
        <div style="font-weight:700;color:#7f1d1d;margin-bottom:.2rem">Acción inmediata requerida</div>
        <div style="font-size:.82rem;color:#7f1d1d">
            Hay <strong><?= $alertasCriticas ?></strong> valor<?= $alertasCriticas > 1 ? 'es' : '' ?> fuera de rango crítico sin validar.
            Notifique al médico tratante y valide los resultados una vez confirmados.
        </div>
    </div>
</div>

<!-- Tabla de alertas -->
<div class="gl-card">
    <div class="gl-card-header">
        <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;font-size:1.05rem"></i>
        <h5>Resultados críticos pendientes</h5>
        <span class="ms-auto gl-badge badge-danger"><?= $alertasCriticas ?></span>
    </div>
    <div style="overflow-x:auto">
        <table class="gl-table">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Orden</th>
                    <th>Examen / Parámetro</th>
                    <th>Valor</th>
                    <th>Rango crítico</th>
                    <th>Hora carga</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($alertas as $a): ?>
            <?php
                $paciente  = trim(($a['pac_nombres'] ?? '') . ' ' . ($a['pac_apellidos'] ?? '')) ?: '—';
                $minC      = $a['valor_min_critico'] ?? null;
                $maxC      = $a['valor_max_critico'] ?? null;
                $val       = $a['valor_resultado'] ?? '—';
                $numVal    = is_numeric($val) ? (float)$val : null;
                $esBajo    = $numVal !== null && $minC !== null && $numVal < (float)$minC;
                $esAlto    = $numVal !== null && $maxC !== null && $numVal > (float)$maxC;
                $elapsed   = !empty($a['fecha_carga']) ? (time() - strtotime($a['fecha_carga'])) : 0;
                $urgente   = $elapsed < 3600; // menos de 1 hora
            ?>
            <tr style="background:#fef9f9;<?= $urgente ? 'animation:fadeIn .5s' : '' ?>">
                <td>
                    <div class="fw-semibold" style="font-size:.87rem;color:#0f172a"><?= htmlspecialchars(mb_substr($paciente, 0, 22)) ?></div>
                    <?php if (!empty($a['pac_cedula'])): ?>
                    <div style="font-size:.72rem;color:#94a3b8"><?= htmlspecialchars($a['pac_cedula']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/ordenes/validar/<?= $a['id_orden'] ?>" style="text-decoration:none">
                        <span class="fw-bold" style="color:var(--primary);font-family:monospace;font-size:.82rem">
                            <?= htmlspecialchars($a['numero_orden'] ?? '') ?>
                        </span>
                    </a>
                </td>
                <td>
                    <div class="fw-semibold" style="font-size:.83rem"><?= htmlspecialchars($a['nombre_examen'] ?? '—') ?></div>
                    <div style="font-size:.75rem;color:#64748b"><?= htmlspecialchars($a['nombre_parametro'] ?? '') ?></div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <span style="font-size:1.05rem;font-weight:800;color:#dc2626">
                            <?= htmlspecialchars($val) ?>
                        </span>
                        <span style="font-size:.72rem;color:#94a3b8"><?= htmlspecialchars($a['unidad_medida'] ?? '') ?></span>
                        <span class="gl-badge badge-danger ms-1" style="font-size:.65rem">
                            <?= $esBajo ? '↓ Muy bajo' : ($esAlto ? '↑ Muy alto' : 'Crítico') ?>
                        </span>
                    </div>
                </td>
                <td style="font-size:.78rem;color:#64748b">
                    <?php
                    if ($minC !== null && $maxC !== null) echo $minC . ' – ' . $maxC;
                    elseif ($minC !== null) echo '> ' . $minC;
                    elseif ($maxC !== null) echo '< ' . $maxC;
                    else echo '—';
                    echo ' <span style="color:#94a3b8">' . htmlspecialchars($a['unidad_medida'] ?? '') . '</span>';
                    ?>
                </td>
                <td style="font-size:.8rem;white-space:nowrap">
                    <?php if (!empty($a['fecha_carga'])): ?>
                    <div><?= date('d/m/Y', strtotime($a['fecha_carga'])) ?></div>
                    <div style="color:<?= $urgente ? '#ef4444' : '#94a3b8' ?>;font-size:.72rem">
                        <?= date('H:i', strtotime($a['fecha_carga'])) ?>
                        <?= $urgente ? '<span class="gl-badge badge-danger" style="font-size:.6rem">Reciente</span>' : '' ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/ordenes/validar/<?= $a['id_orden'] ?>" class="btn-gl btn-outline-gl btn-sm-gl" title="Ver orden">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="/resultados/cargar?orden=<?= $a['id_orden'] ?>" class="btn-gl btn-primary-gl btn-sm-gl" title="Ir a resultados">
                            <i class="bi bi-flask"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<style>
@keyframes pulse { 0%,100%{opacity:1}50%{opacity:.7} }
@keyframes fadeIn { from{background:#ffdcdc} to{background:#fef9f9} }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>