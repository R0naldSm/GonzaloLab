<?php
// Variables: $orden (con examenes[].resultados[]), $menuNav, $nombreUsuario, $csrfToken, $flash
// Esta vista sirve tanto para VER el detalle como para VALIDAR/PUBLICAR la orden
$o = $orden;
$paciente = trim(($o['pac_nombres'] ?? '') . ' ' . ($o['pac_apellidos'] ?? '')) ?: '—';
$pageTitle = 'Orden ' . htmlspecialchars($o['numero_orden'] ?? '');
$breadcrumbs = [['label'=>'Órdenes','url'=>'/ordenes'],['label'=>$o['numero_orden'] ?? '']];
require_once __DIR__ . '/../layouts/header.php';

$estadoMap = [
    'creada'              => ['badge-info',    'Creada',           'bi-plus-circle'],
    'en_proceso'          => ['badge-warning', 'En proceso',       'bi-hourglass-split'],
    'resultados_cargados' => ['badge-purple',  'Resultados listos','bi-check2-circle'],
    'validada'            => ['badge-info',    'Validada',         'bi-shield-check'],
    'publicada'           => ['badge-success', 'Publicada',        'bi-send-check'],
    'cancelada'           => ['badge-danger',  'Cancelada',        'bi-x-circle'],
];

[$estadoCls, $estadoLbl, $estadoIcon] = $estadoMap[$o['estado'] ?? ''] ?? ['badge-gray','—','bi-circle'];

// Contar críticos globales
$totalCriticos = 0;
foreach ($o['examenes'] ?? [] as $ex) {
    $totalCriticos += (int)($ex['tiene_criticos'] ?? 0);
}

// Calcular si puede validar / publicar
$puedeValidar  = \RBAC::puede('ordenes.validar')  && in_array($o['estado'] ?? '', ['resultados_cargados','en_proceso']);
$puedePublicar = \RBAC::puede('ordenes.publicar') && ($o['estado'] ?? '') === 'validada';
$puedeCargar   = \RBAC::puede('resultados.cargar_manual') && in_array($o['estado'] ?? '', ['creada','en_proceso']);
?>

<!-- Header de la orden -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <a href="/ordenes" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
        <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h1 style="font-size:1.3rem;font-weight:800;color:#0f172a;margin:0;font-family:monospace">
                    <?= htmlspecialchars($o['numero_orden'] ?? '') ?>
                </h1>
                <span class="gl-badge <?= $estadoCls ?>">
                    <i class="bi <?= $estadoIcon ?>"></i> <?= $estadoLbl ?>
                </span>
                <?php if ($totalCriticos > 0): ?>
                <span class="critico-badge">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= $totalCriticos ?> valor<?= $totalCriticos > 1 ? 'es' : '' ?> crítico<?= $totalCriticos > 1 ? 's' : '' ?>
                </span>
                <?php endif; ?>
            </div>
            <div style="font-size:.8rem;color:#64748b;margin-top:.3rem">
                <?= !empty($o['fecha_orden']) ? date('d/m/Y H:i', strtotime($o['fecha_orden'])) : '—' ?>
                · Creado por @<?= htmlspecialchars($o['creado_por_username'] ?? '—') ?>
            </div>
        </div>
    </div>

    <!-- Botones de acción principal -->
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($puedeCargar): ?>
        <a href="/resultados/cargar?orden=<?= $o['id_orden'] ?>" class="btn-gl btn-outline-gl">
            <i class="bi bi-flask"></i> Cargar resultados
        </a>
        <?php endif; ?>
        <?php if (\RBAC::puede('ordenes.editar')): ?>
        <a href="/ordenes/editar/<?= $o['id_orden'] ?>" class="btn-gl btn-outline-gl">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <?php endif; ?>
        <?php if ($puedeValidar): ?>
        <button class="btn-gl" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0"
            onclick="accionOrden('validar', <?= $o['id_orden'] ?>, '<?= htmlspecialchars($o['numero_orden'] ?? '') ?>')">
            <i class="bi bi-shield-check"></i> Validar orden
        </button>
        <?php endif; ?>
        <?php if ($puedePublicar): ?>
        <button class="btn-gl btn-primary-gl"
            onclick="accionOrden('publicar', <?= $o['id_orden'] ?>, '<?= htmlspecialchars($o['numero_orden'] ?? '') ?>')">
            <i class="bi bi-send"></i> Publicar resultados
        </button>
        <?php endif; ?>
        <button onclick="window.print()" class="btn-gl btn-outline-gl" title="Imprimir">
            <i class="bi bi-printer"></i>
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Columna principal: datos + resultados -->
    <div class="col-lg-8">

        <!-- Datos del paciente y orden -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="gl-card h-100">
                    <div class="gl-card-header">
                        <i class="bi bi-person-circle" style="color:var(--primary)"></i>
                        <h5>Paciente</h5>
                    </div>
                    <div class="gl-card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;color:#fff;flex-shrink:0">
                                <?= strtoupper(substr($o['pac_nombres'] ?? 'P', 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size:.93rem"><?= htmlspecialchars($paciente) ?></div>
                                <code style="font-size:.75rem;color:#64748b"><?= htmlspecialchars($o['pac_cedula'] ?? '—') ?></code>
                            </div>
                        </div>
                        <?php if (!empty($o['genero']) || !empty($o['fecha_nacimiento'])): ?>
                        <div style="font-size:.8rem;color:#64748b;border-top:1px solid #f1f5f9;padding-top:.625rem;margin-top:.5rem">
                            <?php if (!empty($o['fecha_nacimiento'])): ?>
                            <?= date('d/m/Y', strtotime($o['fecha_nacimiento'])) ?>
                            (<?= (int)date_diff(date_create($o['fecha_nacimiento']), date_create())->y ?> años)
                            <?php endif; ?>
                            <?= $o['genero'] === 'M' ? '· Masculino' : ($o['genero'] === 'F' ? '· Femenino' : '') ?>
                        </div>
                        <?php endif; ?>
                        <div class="mt-2">
                            <a href="/pacientes/historial/<?= $o['id_paciente'] ?>" style="font-size:.78rem;color:var(--primary)">
                                <i class="bi bi-clock-history me-1"></i>Ver historial completo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="gl-card h-100">
                    <div class="gl-card-header">
                        <i class="bi bi-sliders" style="color:#8b5cf6"></i>
                        <h5>Datos de la orden</h5>
                    </div>
                    <div class="gl-card-body">
                        <div style="font-size:.82rem;color:#64748b">
                            <?php
                            $filas = [
                                ['Médico',        $o['medico_nombre'] ?? 'Sin asignar'],
                                ['Tipo atención', ucfirst($o['tipo_atencion'] ?? 'Normal')],
                                ['Método pago',   ucfirst($o['metodo_pago'] ?? '—')],
                                ['Estado pago',   ucfirst($o['estado_pago'] ?? '—')],
                            ];
                            foreach ($filas as [$lbl, $val]):
                            ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?= $lbl ?></span>
                                <span class="fw-semibold" style="color:#374151;text-align:right;max-width:55%"><?= htmlspecialchars($val) ?></span>
                            </div>
                            <?php endforeach; ?>
                            <?php if (!empty($o['observaciones'])): ?>
                            <div style="background:#f8fafc;border-radius:.4rem;padding:.5rem .75rem;font-size:.77rem;margin-top:.25rem;color:#475569">
                                <i class="bi bi-chat-text me-1"></i><?= nl2br(htmlspecialchars($o['observaciones'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resultados por examen -->
        <?php foreach ($o['examenes'] ?? [] as $ex): ?>
        <?php
            $tieneRes   = !empty($ex['resultados']);
            $tieneCrit  = $ex['tiene_criticos'] ?? false;
            $exEstado    = $ex['estado_examen'] ?? 'pendiente';
        ?>
        <div class="gl-card mb-3" style="border-left:3px solid <?= $tieneCrit ? '#ef4444' : ($tieneRes ? '#10b981' : '#e2e8f0') ?>">
            <div class="gl-card-header" style="background:#fafafa">
                <div class="d-flex align-items-center gap-2 flex-fill flex-wrap">
                    <code style="font-size:.78rem;background:#f1f5f9;padding:.15rem .45rem;border-radius:.3rem;color:var(--primary)">
                        <?= htmlspecialchars($ex['codigo'] ?? '') ?>
                    </code>
                    <span class="fw-bold" style="color:#0f172a"><?= htmlspecialchars($ex['nombre_examen'] ?? '—') ?></span>
                    <?php if ($tieneCrit): ?>
                    <span class="critico-badge"><i class="bi bi-exclamation-triangle-fill"></i>Valores críticos</span>
                    <?php elseif ($tieneRes): ?>
                    <span class="gl-badge badge-success"><i class="bi bi-check2"></i>Completado</span>
                    <?php else: ?>
                    <span class="gl-badge badge-gray">Pendiente</span>
                    <?php endif; ?>
                    <span class="gl-badge badge-info ms-1"><?= htmlspecialchars($ex['categoria'] ?? '') ?></span>
                </div>
                <?php if ($puedeCargar && !$tieneRes): ?>
                <a href="/resultados/cargar?orden=<?= $o['id_orden'] ?>&examen=<?= $ex['id_orden_examen'] ?>"
                   class="btn-gl btn-outline-gl btn-sm-gl ms-auto" title="Cargar resultado">
                    <i class="bi bi-flask"></i> Cargar
                </a>
                <?php endif; ?>
            </div>

            <?php if ($tieneRes): ?>
            <div style="overflow-x:auto">
                <table class="gl-table" style="font-size:.82rem">
                    <thead>
                        <tr>
                            <th>Parámetro</th>
                            <th>Resultado</th>
                            <th>Unidad</th>
                            <th>Rango normal</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ex['resultados'] as $r): ?>
                    <?php
                        $val      = $r['valor_resultado'] ?? '—';
                        $minN     = $r['valor_min_normal'] ?? null;
                        $maxN     = $r['valor_max_normal'] ?? null;
                        $minC     = $r['valor_min_critico'] ?? null;
                        $maxC     = $r['valor_max_critico'] ?? null;
                        $esCrit   = (bool)($r['es_critico'] ?? false);
                        $numVal   = is_numeric($val) ? (float)$val : null;

                        $estRes = 'normal';
                        if ($esCrit) $estRes = 'critico';
                        elseif ($numVal !== null) {
                            if ($minN !== null && $numVal < (float)$minN) $estRes = 'bajo';
                            elseif ($maxN !== null && $numVal > (float)$maxN) $estRes = 'alto';
                        }

                        $colorMap = ['normal'=>'#10b981','bajo'=>'#f59e0b','alto'=>'#f59e0b','critico'=>'#ef4444'];
                        $labelMap = ['normal'=>'Normal','bajo'=>'Bajo','alto'=>'Alto','critico'=>'⚠ CRÍTICO'];
                        $badgeMap = ['normal'=>'badge-success','bajo'=>'badge-warning','alto'=>'badge-warning','critico'=>'badge-danger'];
                    ?>
                    <tr style="<?= $esCrit ? 'background:#fef9f9' : '' ?>">
                        <td class="fw-semibold"><?= htmlspecialchars($r['nombre_parametro'] ?? '—') ?></td>
                        <td>
                            <span class="fw-bold" style="font-size:.93rem;color:<?= $colorMap[$estRes] ?>">
                                <?= htmlspecialchars($val) ?>
                            </span>
                        </td>
                        <td style="color:#94a3b8"><?= htmlspecialchars($r['unidad_medida'] ?? '') ?></td>
                        <td style="font-size:.78rem;color:#64748b">
                            <?php
                            if ($minN !== null && $maxN !== null) echo $minN . ' – ' . $maxN;
                            elseif ($minN !== null) echo '≥ ' . $minN;
                            elseif ($maxN !== null) echo '≤ ' . $maxN;
                            else echo '—';
                            ?>
                            <?php if ($minC !== null || $maxC !== null): ?>
                            <div style="color:#ef4444;font-size:.7rem">
                                Crítico: <?= $minC ?? '—' ?> / <?= $maxC ?? '—' ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="gl-badge <?= $badgeMap[$estRes] ?>"><?= $labelMap[$estRes] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="gl-card-body" style="text-align:center;color:#94a3b8;font-size:.83rem;padding:1rem">
                <i class="bi bi-hourglass me-1"></i>Sin resultados cargados aún
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

    </div><!-- /col-lg-8 -->

    <!-- Sidebar derecho -->
    <div class="col-lg-4">

        <!-- Resumen económico -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-cash-stack" style="color:#10b981"></i>
                <h5>Resumen económico</h5>
            </div>
            <div class="gl-card-body">
                <div style="background:#f8fafc;border-radius:.625rem;padding:1rem">
                    <div class="d-flex justify-content-between mb-2" style="font-size:.83rem">
                        <span style="color:#64748b">Exámenes</span>
                        <span class="fw-semibold"><?= count($o['examenes'] ?? []) ?></span>
                    </div>
                    <hr style="border-color:#e2e8f0;margin:.5rem 0">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total a pagar</span>
                        <span class="fw-bold" style="font-size:1.3rem;color:var(--primary)">
                            $<?= number_format((float)($o['total_pagar'] ?? 0), 2) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flujo de estado -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-arrow-right-circle" style="color:var(--primary)"></i>
                <h5>Flujo de la orden</h5>
            </div>
            <div class="gl-card-body">
                <?php
                $pasos = [
                    'creada'              => 'Orden creada',
                    'en_proceso'          => 'Muestra tomada',
                    'resultados_cargados' => 'Resultados cargados',
                    'validada'            => 'Validada por analista',
                    'publicada'           => 'Publicada al paciente',
                ];
                $pasosKeys = array_keys($pasos);
                $estadoActual = $o['estado'] ?? 'creada';
                $idxActual = array_search($estadoActual, $pasosKeys) ?: 0;
                ?>
                <?php foreach ($pasos as $key => $lbl): ?>
                <?php
                    $idx  = array_search($key, $pasosKeys);
                    $done = $idx <= $idxActual && $estadoActual !== 'cancelada';
                    $curr = $key === $estadoActual;
                ?>
                <div class="d-flex align-items-center gap-2 mb-3 <?= $idx < count($pasos)-1 ? 'position-relative' : '' ?>">
                    <?php if ($idx < count($pasos)-1): ?>
                    <div style="position:absolute;left:11px;top:24px;bottom:-8px;width:2px;background:<?= ($idx < $idxActual && $estadoActual !== 'cancelada') ? 'var(--primary)' : '#e2e8f0' ?>"></div>
                    <?php endif; ?>
                    <div style="width:22px;height:22px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;z-index:1;
                        background:<?= $done ? 'var(--primary)' : '#e2e8f0' ?>;
                        border:2px solid <?= $curr ? 'var(--primary)' : ($done ? 'var(--primary)' : '#d1d5db') ?>">
                        <?php if ($done): ?>
                        <i class="bi bi-check" style="color:#fff;font-size:.65rem"></i>
                        <?php else: ?>
                        <div style="width:6px;height:6px;border-radius:50%;background:#d1d5db"></div>
                        <?php endif; ?>
                    </div>
                    <span style="font-size:.8rem;color:<?= $curr ? '#0f172a' : ($done ? '#374151' : '#94a3b8') ?>;font-weight:<?= $curr ? '700' : '400' ?>">
                        <?= $lbl ?>
                        <?php if ($curr): ?><span style="font-size:.68rem;color:var(--primary);margin-left:.3rem">← actual</span><?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- QR si está publicada -->
        <?php if ($o['estado'] === 'publicada' && !empty($o['token_acceso'])): ?>
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-qr-code" style="color:var(--primary)"></i>
                <h5>Acceso QR del paciente</h5>
            </div>
            <div class="gl-card-body text-center">
                <img src="https://chart.googleapis.com/chart?chs=160x160&cht=qr&chl=<?= urlencode((isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : '') . '/consulta/' . $o['token_acceso']) ?>&choe=UTF-8"
                     alt="QR de acceso" style="width:160px;height:160px;border-radius:.5rem;border:1px solid #e2e8f0">
                <p style="font-size:.75rem;color:#94a3b8;margin-top:.5rem">Entregue este QR al paciente para consultar sus resultados</p>
                <a href="/consulta/<?= htmlspecialchars($o['token_acceso']) ?>" target="_blank" class="btn-gl btn-outline-gl btn-sm-gl" style="margin-top:.5rem">
                    <i class="bi bi-box-arrow-up-right"></i> Previsualizar
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div><!-- /row -->

<script>
const CSRF = '<?= htmlspecialchars($csrfToken) ?>';
function accionOrden(accion, id, num) {
    const msgs = {
        validar:  '¿Validar la orden ' + num + '?\nConfirme que los resultados son correctos.',
        publicar: '¿Publicar la orden ' + num + '?\nEl paciente podrá ver sus resultados vía QR.',
    };
    if (!confirm(msgs[accion])) return;
    fetch('/ordenes/' + accion + '/' + id, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({csrf_token: CSRF})
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else alert(d.message || 'Error al procesar');
    }).catch(() => alert('Error de conexión'));
}
</script>

<style>
@media print {
    #sidebar, #topbar, .btn-gl { display: none !important; }
    #main { margin: 0 !important; padding: 0 !important; }
    .col-lg-4 { display: none !important; }
    .col-lg-8 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>