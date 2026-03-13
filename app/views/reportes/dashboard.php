<?php
// Variables: $stats, $masSolicitados, $produccionAnalistas, $examenesPorCategoria, $desde, $hasta, $menuNav, $nombreUsuario, $csrfToken, $flash
$pageTitle = 'Reportes';
require_once __DIR__ . '/../layouts/header.php';

$s       = $stats ?? [];
$ordenes = $s['ordenes']  ?? [];
$pac     = $s['pacientes']?? [];
$crit    = $s['criticos'] ?? [];
$porEst  = $s['por_estado'] ?? [];
$porDia  = $s['por_dia']    ?? [];

// Construir mapa estado → cantidad
$estadoQ = [];
foreach ($porEst as $e) $estadoQ[$e['estado']] = (int)$e['cantidad'];

// Total de exámenes por categoría
$totalCatExa = array_sum(array_column($examenesPorCategoria ?? [], 'total')) ?: 1;
?>

<!-- Header con filtro de rango -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 style="font-size:1.45rem;font-weight:800;color:#0f172a;margin:0">Reportes</h1>
        <p style="font-size:.82rem;color:#64748b;margin:.15rem 0 0">
            Dashboard estadístico ·
            <span style="color:var(--primary)">
                <?= date('d M Y', strtotime($desde)) ?> — <?= date('d M Y', strtotime($hasta)) ?>
            </span>
        </p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" action="/reportes" class="d-flex gap-2 align-items-center">
            <input type="date" name="desde" class="gl-input" style="width:150px" value="<?= htmlspecialchars($desde) ?>">
            <span style="color:#94a3b8">–</span>
            <input type="date" name="hasta" class="gl-input" style="width:150px" value="<?= htmlspecialchars($hasta) ?>">
            <button type="submit" class="btn-gl btn-primary-gl"><i class="bi bi-filter"></i> Filtrar</button>
        </form>
        <?php if (\RBAC::puede('reportes.exportar_excel')): ?>
        <div class="dropdown">
            <button class="btn-gl btn-outline-gl" data-bs-toggle="dropdown">
                <i class="bi bi-download"></i> Exportar
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="border-radius:.625rem">
                <?php foreach (['ordenes'=>'Órdenes','criticos'=>'Valores críticos','examenes'=>'Exámenes solicitados','pacientes'=>'Pacientes'] as $t=>$l): ?>
                <li>
                    <a class="dropdown-item" style="font-size:.83rem"
                       href="/reportes/exportar?tipo=<?= $t ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>">
                        <i class="bi bi-file-earmark-excel me-2" style="color:#10b981"></i><?= $l ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['Total órdenes',     $ordenes['total'] ?? 0,      '#eff6ff', '#3b82f6', 'bi-clipboard2-pulse', null],
        ['Pendientes',        $ordenes['pendientes'] ?? 0,  '#fffbeb', '#f59e0b', 'bi-hourglass-split', null],
        ['Completadas',       $ordenes['completadas'] ?? 0,'#f0fdf4', '#10b981', 'bi-check-circle',    null],
        ['Ingresos',          '$'.number_format((float)($ordenes['ingresos']??0),2),'#faf5ff','#8b5cf6','bi-cash-stack',null],
        ['Pacientes atendidos',$pac['total'] ?? 0,          '#f0fdfe', '#06b6d4', 'bi-people',          null],
        ['Valores críticos',  $crit['total'] ?? 0,          '#fef2f2', '#ef4444', 'bi-exclamation-triangle-fill',null],
    ];
    foreach ($kpis as [$lbl, $val, $bg, $col, $ico, $sub]):
    ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="stat-icon" style="background:<?= $bg ?>"><i class="bi <?= $ico ?>" style="color:<?= $col ?>"></i></div>
            <div>
                <div class="stat-value" style="font-size:1.35rem;<?= $lbl==='Valores críticos' && $val > 0 ? 'color:#ef4444' : '' ?>">
                    <?= $val ?>
                </div>
                <div class="stat-label"><?= $lbl ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">

    <!-- Gráfico de actividad por día (canvas sparkline) -->
    <div class="col-lg-8">
        <div class="gl-card h-100">
            <div class="gl-card-header">
                <i class="bi bi-bar-chart-line" style="color:var(--primary);font-size:1.05rem"></i>
                <h5>Actividad diaria</h5>
                <div class="ms-auto d-flex gap-2">
                    <a href="/reportes/estadisticas?tipo=ingresos&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>"
                       class="btn-gl btn-outline-gl btn-sm-gl">Ver detalle</a>
                </div>
            </div>
            <div class="gl-card-body">
                <?php if (empty($porDia)): ?>
                <div style="text-align:center;color:#94a3b8;padding:3rem;font-size:.85rem">
                    <i class="bi bi-bar-chart" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3"></i>
                    Sin datos para el rango seleccionado
                </div>
                <?php else: ?>
                <canvas id="chartDia" style="max-height:240px"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Estados de órdenes -->
    <div class="col-lg-4">
        <div class="gl-card h-100">
            <div class="gl-card-header">
                <i class="bi bi-pie-chart" style="color:#8b5cf6;font-size:1.05rem"></i>
                <h5>Por estado</h5>
            </div>
            <div class="gl-card-body">
                <?php
                $estConfig = [
                    'creada'              => ['#a5f3fc','Creadas'],
                    'en_proceso'          => ['#fde68a','En proceso'],
                    'resultados_cargados' => ['#c4b5fd','Resultados listos'],
                    'validada'            => ['#93c5fd','Validadas'],
                    'publicada'           => ['#86efac','Publicadas'],
                    'cancelada'           => ['#fca5a5','Canceladas'],
                ];
                $totalOrds = max(array_sum($estadoQ), 1);
                foreach ($estConfig as $key => [$color, $lbl]):
                    $qty = $estadoQ[$key] ?? 0;
                    if (!$qty) continue;
                    $pct = round($qty / $totalOrds * 100);
                ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:12px;height:12px;border-radius:3px;background:<?= $color ?>;flex-shrink:0"></div>
                    <div class="flex-fill" style="font-size:.82rem;color:#374151"><?= $lbl ?></div>
                    <div style="font-size:.82rem;font-weight:700;color:#0f172a;min-width:28px;text-align:right"><?= $qty ?></div>
                    <div style="width:80px;height:6px;border-radius:3px;background:#f1f5f9;overflow:hidden">
                        <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:3px"></div>
                    </div>
                    <span style="font-size:.72rem;color:#94a3b8;min-width:32px"><?= $pct ?>%</span>
                </div>
                <?php endforeach; ?>
                <?php if (empty(array_filter($estadoQ))): ?>
                <p style="color:#94a3b8;text-align:center;font-size:.83rem;padding:1rem">Sin datos</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Exámenes más solicitados -->
    <div class="col-lg-6">
        <div class="gl-card h-100">
            <div class="gl-card-header">
                <i class="bi bi-flask" style="color:var(--primary);font-size:1.05rem"></i>
                <h5>Top 10 exámenes</h5>
                <a href="/reportes/estadisticas?tipo=examenes&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>"
                   class="btn-gl btn-outline-gl btn-sm-gl ms-auto">Ver todos</a>
            </div>
            <div class="gl-card-body" style="padding:.5rem">
                <?php if (empty($masSolicitados)): ?>
                <p style="color:#94a3b8;text-align:center;padding:2rem;font-size:.83rem">Sin datos</p>
                <?php else: ?>
                <?php $maxEx = max(array_column($masSolicitados, 'total_solicitado') + [1]); ?>
                <?php foreach (array_slice($masSolicitados, 0, 10) as $i => $ex): ?>
                <?php $pct = round($ex['total_solicitado'] / $maxEx * 100); ?>
                <div class="d-flex align-items-center gap-2 px-3 py-2 rounded"
                     style="margin-bottom:.25rem;<?= $i === 0 ? 'background:#f0fdfe' : '' ?>">
                    <span style="width:20px;font-size:.72rem;color:#94a3b8;text-align:center;flex-shrink:0"><?= $i+1 ?></span>
                    <div class="flex-fill" style="min-width:0">
                        <div style="font-size:.83rem;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <?= htmlspecialchars($ex['nombre']) ?>
                        </div>
                        <div style="height:4px;border-radius:2px;background:#f1f5f9;margin-top:.3rem;overflow:hidden">
                            <div style="height:100%;width:<?= $pct ?>%;background:var(--primary);border-radius:2px"></div>
                        </div>
                    </div>
                    <span class="gl-badge badge-info" style="font-size:.7rem;white-space:nowrap">
                        <?= $ex['total_solicitado'] ?>x
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Producción analistas + Categorías -->
    <div class="col-lg-6">
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-person-badge" style="color:#8b5cf6;font-size:1.05rem"></i>
                <h5>Producción del equipo</h5>
            </div>
            <div style="overflow-x:auto">
                <table class="gl-table" style="font-size:.82rem">
                    <thead><tr><th>Analista</th><th class="text-center">Órdenes</th><th class="text-center">Resultados</th></tr></thead>
                    <tbody>
                    <?php if (empty($produccionAnalistas)): ?>
                        <tr><td colspan="3" style="text-align:center;color:#94a3b8;padding:1.5rem">Sin datos</td></tr>
                    <?php else: ?>
                    <?php foreach ($produccionAnalistas as $a): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;flex-shrink:0">
                                    <?= strtoupper(substr($a['username'],0,1)) ?>
                                </div>
                                <div>
                                    <div class="fw-semibold" style="color:#0f172a">@<?= htmlspecialchars($a['username']) ?></div>
                                    <?php if (!empty($a['nombre_completo'])): ?>
                                    <div style="font-size:.72rem;color:#94a3b8"><?= htmlspecialchars($a['nombre_completo']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-center"><span class="gl-badge badge-info"><?= (int)$a['ordenes_atendidas'] ?></span></td>
                        <td class="text-center"><span class="gl-badge badge-success"><?= (int)$a['resultados_cargados'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Distribución por categoría -->
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-tags" style="color:#10b981;font-size:1.05rem"></i>
                <h5>Por categoría de examen</h5>
            </div>
            <div class="gl-card-body" style="padding:.75rem">
                <?php foreach ($examenesPorCategoria ?? [] as $cat): ?>
                <?php $pct = round($cat['total'] / $totalCatExa * 100); ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:10px;height:10px;border-radius:2px;background:<?= htmlspecialchars($cat['color_hex'] ?? '#06b6d4') ?>;flex-shrink:0"></div>
                    <div class="flex-fill" style="font-size:.8rem;color:#374151"><?= htmlspecialchars($cat['categoria']) ?></div>
                    <span class="fw-bold" style="font-size:.8rem;color:#0f172a"><?= $cat['total'] ?></span>
                    <div style="width:60px;height:5px;border-radius:2px;background:#f1f5f9;overflow:hidden">
                        <div style="height:100%;width:<?= $pct ?>%;background:<?= htmlspecialchars($cat['color_hex'] ?? '#06b6d4') ?>;border-radius:2px"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($examenesPorCategoria)): ?>
                <p style="color:#94a3b8;text-align:center;font-size:.83rem;padding:1rem">Sin datos</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- /row -->

<?php if (!empty($porDia)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode(array_column($porDia, 'dia')) ?>;
const datOrd = <?= json_encode(array_map('intval', array_column($porDia, 'ordenes'))) ?>;
const datIng = <?= json_encode(array_map('floatval', array_column($porDia, 'ingresos'))) ?>;

new Chart(document.getElementById('chartDia'), {
    type: 'bar',
    data: {
        labels: labels.map(d => new Date(d).toLocaleDateString('es-EC', {day:'2-digit',month:'short'})),
        datasets: [
            {
                label: 'Órdenes',
                data: datOrd,
                backgroundColor: '#a5f3fc',
                borderColor: '#06b6d4',
                borderWidth: 1,
                borderRadius: 4,
                yAxisID: 'y',
            },
            {
                label: 'Ingresos ($)',
                data: datIng,
                type: 'line',
                borderColor: '#8b5cf6',
                backgroundColor: '#8b5cf622',
                borderWidth: 2,
                fill: true,
                tension: .35,
                pointRadius: 3,
                yAxisID: 'y1',
            },
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
        scales: {
            y:  { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } },
            y1: { beginAtZero: true, position: 'right', grid: { display: false },
                  ticks: { callback: v => '$'+v.toFixed(0), font: { size: 10 } } },
        }
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>