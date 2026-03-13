<?php
// Variables: $data, $tipo, $desde, $hasta, $menuNav, $nombreUsuario, $csrfToken, $flash
// También puede recibir: $registros, $tablas, $usuarios, $filtros (modo auditoría)
$modoAuditoria = isset($registros);
$pageTitle = $modoAuditoria ? 'Auditoría del Sistema' : 'Estadísticas Detalladas';
$breadcrumbs = [['label'=>'Reportes','url'=>'/reportes'],['label'=>$pageTitle]];
require_once __DIR__ . '/../layouts/header.php';

$tipoLabels = [
    'ordenes'  => ['Órdenes por día',        'bi-clipboard2-pulse', '#3b82f6'],
    'examenes' => ['Exámenes más solicitados','bi-flask',            '#10b981'],
    'criticos' => ['Valores críticos',        'bi-exclamation-triangle-fill','#ef4444'],
    'ingresos' => ['Ingresos diarios',        'bi-cash-stack',       '#8b5cf6'],
];
$tipoActual = $tipo ?? 'ordenes';
[$tipoLbl, $tipoIco, $tipoColor] = $tipoLabels[$tipoActual] ?? ['Estadísticas','bi-bar-chart','#06b6d4'];
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <a href="/reportes" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0"><?= $pageTitle ?></h1>
            <?php if (!$modoAuditoria): ?>
            <p style="font-size:.82rem;color:#64748b;margin:0">
                <?= date('d/m/Y', strtotime($desde)) ?> — <?= date('d/m/Y', strtotime($hasta)) ?>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$modoAuditoria && \RBAC::puede('reportes.exportar_excel')): ?>
    <a href="/reportes/exportar?tipo=<?= $tipoActual ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>"
       class="btn-gl btn-outline-gl">
        <i class="bi bi-file-earmark-excel" style="color:#10b981"></i> Exportar CSV
    </a>
    <?php endif; ?>
</div>

<?php if (!$modoAuditoria): ?>
<!-- Tabs de tipo -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <?php foreach ($tipoLabels as $t => [$l, $ico, $col]): ?>
    <a href="/reportes/estadisticas?tipo=<?= $t ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>"
       class="btn-gl <?= $tipoActual === $t ? 'btn-primary-gl' : 'btn-outline-gl' ?>" style="font-size:.82rem">
        <i class="bi <?= $ico ?>"></i> <?= $l ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Datos según tipo -->
<div class="gl-card">
    <div class="gl-card-header">
        <i class="bi <?= $tipoIco ?>" style="color:<?= $tipoColor ?>;font-size:1.05rem"></i>
        <h5><?= $tipoLbl ?></h5>
        <span class="gl-badge badge-info ms-auto"><?= count($data ?? []) ?> registro(s)</span>
    </div>

    <?php if (empty($data)): ?>
    <div class="gl-card-body text-center py-5" style="color:#94a3b8">
        <i class="bi bi-bar-chart" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3"></i>
        Sin datos para el rango y tipo seleccionados
    </div>
    <?php else: ?>

    <?php if ($tipoActual === 'ordenes' || $tipoActual === 'ingresos'): ?>
    <!-- Vista tabular: órdenes o ingresos por día -->
    <div style="overflow-x:auto">
        <table class="gl-table" style="font-size:.83rem">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <?php if ($tipoActual === 'ordenes'): ?>
                    <th class="text-center">Órdenes</th>
                    <th class="text-right">Ingresos</th>
                    <?php else: ?>
                    <th class="text-center">Órdenes</th>
                    <th class="text-center">Pagadas</th>
                    <th class="text-right">Total ingresos</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td class="fw-semibold"><?= !empty($row['dia']) ? date('d/m/Y (l)', strtotime($row['dia'])) : '—' ?></td>
                <td class="text-center"><span class="gl-badge badge-info"><?= (int)$row['ordenes'] ?></span></td>
                <?php if ($tipoActual === 'ingresos'): ?>
                <td class="text-center"><span class="gl-badge badge-success"><?= (int)($row['pagadas'] ?? 0) ?></span></td>
                <?php endif; ?>
                <td style="text-align:right;font-weight:700;color:var(--primary)">
                    $<?= number_format((float)($row['ingresos'] ?? 0), 2) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot style="background:#f8fafc">
                <tr>
                    <td class="fw-bold" style="font-size:.85rem">TOTAL</td>
                    <td class="text-center fw-bold"><?= array_sum(array_column($data, 'ordenes')) ?></td>
                    <?php if ($tipoActual === 'ingresos'): ?>
                    <td class="text-center fw-bold"><?= array_sum(array_column($data, 'pagadas')) ?></td>
                    <?php endif; ?>
                    <td style="text-align:right;font-weight:800;color:var(--primary);font-size:.95rem">
                        $<?= number_format(array_sum(array_column($data, 'ingresos')), 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php elseif ($tipoActual === 'examenes'): ?>
    <!-- Top exámenes -->
    <div style="overflow-x:auto">
        <table class="gl-table" style="font-size:.83rem">
            <thead><tr><th>#</th><th>Examen</th><th>Categoría</th><th class="text-center">Veces solicitado</th><th class="text-right">Precio unit.</th></tr></thead>
            <tbody>
            <?php $maxE = max(array_column($data, 'total_solicitado') + [1]); ?>
            <?php foreach ($data as $i => $ex): ?>
            <tr>
                <td style="color:#94a3b8"><?= $i+1 ?></td>
                <td>
                    <div class="fw-semibold"><?= htmlspecialchars($ex['nombre'] ?? '—') ?></div>
                    <div style="height:4px;border-radius:2px;background:#f1f5f9;margin-top:.3rem;width:120px">
                        <div style="height:100%;width:<?= round($ex['total_solicitado']/$maxE*100) ?>%;background:var(--primary);border-radius:2px"></div>
                    </div>
                </td>
                <td><span class="gl-badge badge-gray"><?= htmlspecialchars($ex['categoria'] ?? '') ?></span></td>
                <td class="text-center"><span class="gl-badge badge-info fw-bold"><?= (int)$ex['total_solicitado'] ?></span></td>
                <td style="text-align:right;color:var(--primary);font-weight:600">
                    $<?= number_format((float)($ex['precio'] ?? 0), 2) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($tipoActual === 'criticos'): ?>
    <!-- Valores críticos -->
    <div style="overflow-x:auto">
        <table class="gl-table" style="font-size:.82rem">
            <thead><tr><th>Fecha</th><th>Orden</th><th>Examen</th><th>Parámetro</th><th>Valor</th><th>Mín crítico</th><th>Máx crítico</th></tr></thead>
            <tbody>
            <?php if (empty($data)): ?>
            <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:2rem">Sin valores críticos en el período</td></tr>
            <?php else: ?>
            <?php foreach ($data as $r): ?>
            <tr style="background:#fef9f9">
                <td style="white-space:nowrap"><?= !empty($r['dia']) ? date('d/m/Y', strtotime($r['dia'])) : '—' ?></td>
                <td><span style="font-family:monospace;font-size:.8rem;color:var(--primary)"><?= htmlspecialchars($r['numero_orden'] ?? '') ?></span></td>
                <td><?= htmlspecialchars($r['nombre_examen'] ?? '') ?></td>
                <td class="fw-semibold"><?= htmlspecialchars($r['nombre_parametro'] ?? '') ?></td>
                <td class="fw-bold" style="color:#ef4444;font-size:.95rem"><?= htmlspecialchars($r['valor_resultado'] ?? '') ?></td>
                <td style="color:#64748b"><?= htmlspecialchars($r['valor_min_critico'] ?? '—') ?></td>
                <td style="color:#64748b"><?= htmlspecialchars($r['valor_max_critico'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php else: ?>
<!-- ═══════════ MODO AUDITORÍA ═══════════ -->
<div class="row g-4">
    <div class="col-lg-9">
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-journal-check" style="color:var(--primary)"></i>
                <h5>Registros de auditoría</h5>
                <span class="gl-badge badge-info ms-auto"><?= count($registros ?? []) ?></span>
            </div>
            <div style="overflow-x:auto">
                <table class="gl-table" style="font-size:.8rem">
                    <thead><tr><th>Fecha/Hora</th><th>Tabla</th><th>Operación</th><th>Registro ID</th><th>Usuario</th><th>Cambios</th></tr></thead>
                    <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:2rem">Sin registros para los filtros seleccionados</td></tr>
                    <?php else: ?>
                    <?php foreach ($registros as $reg): ?>
                    <?php
                        $opColor = ['INSERT'=>'badge-success','UPDATE'=>'badge-info','DELETE'=>'badge-danger'][$reg['operacion']??''] ?? 'badge-gray';
                    ?>
                    <tr>
                        <td style="white-space:nowrap;font-size:.75rem;color:#64748b">
                            <?= !empty($reg['fecha_operacion']) ? date('d/m/Y H:i:s', strtotime($reg['fecha_operacion'])) : '—' ?>
                        </td>
                        <td><code style="font-size:.75rem;background:#f1f5f9;padding:.1rem .35rem;border-radius:.25rem"><?= htmlspecialchars($reg['tabla'] ?? '') ?></code></td>
                        <td><span class="gl-badge <?= $opColor ?>" style="font-size:.68rem"><?= $reg['operacion'] ?? '—' ?></span></td>
                        <td style="color:#94a3b8">#<?= $reg['registro_id'] ?? '—' ?></td>
                        <td><span style="font-size:.78rem;font-weight:600">@<?= htmlspecialchars($reg['username'] ?? '?') ?></span></td>
                        <td style="font-size:.73rem;color:#64748b;max-width:220px">
                            <?php
                            $nuevos = $reg['datos_nuevos'] ?? '';
                            if ($nuevos && strlen($nuevos) > 80) $nuevos = substr($nuevos, 0, 80) . '…';
                            echo htmlspecialchars($nuevos ?: '—');
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Filtros auditoría -->
    <div class="col-lg-3">
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-funnel" style="color:#94a3b8"></i>
                <h5>Filtros</h5>
            </div>
            <div class="gl-card-body">
                <form method="GET" action="/reportes/auditoria" class="d-flex flex-column gap-3">
                    <div>
                        <label class="gl-label">Tabla</label>
                        <select name="tabla" class="gl-input gl-select">
                            <option value="">Todas</option>
                            <?php foreach ($tablas ?? [] as $t): ?>
                            <option value="<?= $t['tabla'] ?>" <?= ($filtros['tabla']??'') === $t['tabla'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['tabla']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="gl-label">Operación</label>
                        <select name="operacion" class="gl-input gl-select">
                            <option value="">Todas</option>
                            <?php foreach (['INSERT','UPDATE','DELETE'] as $op): ?>
                            <option value="<?= $op ?>" <?= ($filtros['operacion']??'') === $op ? 'selected' : '' ?>><?= $op ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="gl-label">Desde</label>
                        <input type="date" name="desde" class="gl-input" value="<?= htmlspecialchars($filtros['fecha_desde'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div>
                        <label class="gl-label">Hasta</label>
                        <input type="date" name="hasta" class="gl-input" value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? date('Y-m-d')) ?>">
                    </div>
                    <button type="submit" class="btn-gl btn-primary-gl" style="justify-content:center">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="/reportes/auditoria" class="btn-gl btn-outline-gl" style="justify-content:center">Limpiar</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>