<?php
// Modo dual:
// 1. Listado (cuando hay $resultados): index de resultados
// 2. Formulario (cuando hay $orden + $examenesConParametros): carga de una orden específica
$modoFormulario = isset($orden) && isset($examenesConParametros);

if ($modoFormulario) {
    $o          = $orden;
    $paciente   = trim(($o['pac_nombres'] ?? '') . ' ' . ($o['pac_apellidos'] ?? ''));
    $pageTitle  = 'Cargar resultados — ' . ($o['numero_orden'] ?? '');
    $breadcrumbs = [
        ['label'=>'Órdenes','url'=>'/ordenes'],
        ['label'=>$o['numero_orden']??'','url'=>'/ordenes/validar/'.($o['id_orden']??'')],
        ['label'=>'Cargar resultados'],
    ];
} else {
    $pageTitle  = 'Resultados';
}

require_once __DIR__ . '/../layouts/header.php';

// Colores por estado de resultado
$estadoMap = [
    'resultados_cargados' => ['badge-purple', 'Listos'],
    'validada'            => ['badge-info',   'Validada'],
    'publicada'           => ['badge-success','Publicada'],
    'en_proceso'          => ['badge-warning','En proceso'],
    'creada'              => ['badge-gray',   'Pendiente'],
];
?>

<?php if ($modoFormulario): ?>
<!-- ═══════════════════════════════════════════════ -->
<!-- MODO: FORMULARIO DE CARGA                       -->
<!-- ═══════════════════════════════════════════════ -->

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/ordenes/validar/<?= $o['id_orden'] ?>" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">
            Cargar resultados — <span style="color:var(--primary);font-family:monospace"><?= htmlspecialchars($o['numero_orden'] ?? '') ?></span>
        </h1>
        <p style="font-size:.82rem;color:#64748b;margin:0">
            Paciente: <strong><?= htmlspecialchars($paciente) ?></strong>
            · Cédula: <?= htmlspecialchars($o['pac_cedula'] ?? '—') ?>
        </p>
    </div>
</div>

<form method="POST" action="/resultados/guardar-manual" id="formResultados" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
<input type="hidden" name="id_orden"   value="<?= $o['id_orden'] ?>">

<?php foreach ($examenesConParametros as $idx => $ex): ?>
<?php $tienePrev = !empty($ex['previos']); ?>
<div class="gl-card mb-4" style="border-left:3px solid <?= $tienePrev ? '#10b981' : 'var(--primary)' ?>">
    <div class="gl-card-header">
        <div class="d-flex align-items-center gap-2 flex-fill">
            <code style="font-size:.78rem;background:#f1f5f9;padding:.15rem .4rem;border-radius:.3rem;color:var(--primary)">
                <?= htmlspecialchars($ex['codigo'] ?? '') ?>
            </code>
            <span class="fw-bold" style="color:#0f172a"><?= htmlspecialchars($ex['nombre_examen']) ?></span>
            <span class="gl-badge badge-gray"><?= htmlspecialchars($ex['categoria']) ?></span>
            <?php if ($tienePrev): ?>
            <span class="gl-badge badge-success"><i class="bi bi-check2"></i> Ya cargado</span>
            <?php endif; ?>
        </div>
        <span style="font-size:.78rem;color:#94a3b8"><?= count($ex['parametros']) ?> parámetros</span>
    </div>

    <?php if (empty($ex['parametros'])): ?>
    <div class="gl-card-body" style="color:#94a3b8;text-align:center;font-size:.83rem;padding:1.25rem">
        <i class="bi bi-exclamation-circle me-1"></i>
        Este examen no tiene parámetros configurados. Configure los parámetros en <a href="/examenes/parametros/<?= $ex['id_examen'] ?? '' ?>" style="color:var(--primary)">Gestión de exámenes</a>.
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="gl-table" style="font-size:.83rem">
            <thead>
                <tr>
                    <th style="min-width:160px">Parámetro</th>
                    <th style="min-width:130px">Valor</th>
                    <th>Unidad</th>
                    <th>Rango normal</th>
                    <th>Rango crítico</th>
                    <th style="width:80px">Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ex['parametros'] as $p): ?>
            <?php
                $previo     = $ex['previos'][$p['id_parametro']] ?? null;
                $valorPrev  = $previo['valor_resultado'] ?? '';
                $esCrit     = $previo ? (bool)$previo['es_critico'] : false;
                $inputId    = 'r_' . $ex['id_orden_examen'] . '_' . $p['id_parametro'];
                $vref       = $p['valores_referencia'][0] ?? null;
                $minN = $vref['valor_min_normal']  ?? null;
                $maxN = $vref['valor_max_normal']  ?? null;
                $minC = $vref['valor_min_critico'] ?? null;
                $maxC = $vref['valor_max_critico'] ?? null;
            ?>
            <tr id="row_<?= $inputId ?>" style="<?= $esCrit ? 'background:#fef9f9' : '' ?>">
                <td>
                    <label for="<?= $inputId ?>" style="font-weight:600;color:#0f172a;margin:0;cursor:pointer">
                        <?= htmlspecialchars($p['nombre_parametro']) ?>
                    </label>
                    <?php if (!empty($p['descripcion'])): ?>
                    <i class="bi bi-info-circle ms-1" style="color:#94a3b8;font-size:.75rem"
                       title="<?= htmlspecialchars($p['descripcion']) ?>"></i>
                    <?php endif; ?>
                </td>
                <td>
                    <input type="hidden" name="resultados[<?= $idx ?>][<?= $p['id_parametro'] ?>][id_orden_examen]" value="<?= $ex['id_orden_examen'] ?>">
                    <input type="hidden" name="resultados[<?= $idx ?>][<?= $p['id_parametro'] ?>][id_parametro]"   value="<?= $p['id_parametro'] ?>">

                    <?php if ($p['tipo_dato'] === 'texto'): ?>
                    <input type="text" id="<?= $inputId ?>"
                        name="resultados[<?= $idx ?>][<?= $p['id_parametro'] ?>][valor]"
                        class="gl-input" style="font-size:.83rem;padding:.3rem .6rem"
                        value="<?= htmlspecialchars($valorPrev) ?>"
                        placeholder="—">
                    <?php elseif ($p['tipo_dato'] === 'booleano'): ?>
                    <select id="<?= $inputId ?>"
                        name="resultados[<?= $idx ?>][<?= $p['id_parametro'] ?>][valor]"
                        class="gl-input gl-select" style="font-size:.83rem;padding:.3rem .6rem"
                        onchange="evaluarCritico('<?= $inputId ?>', <?= json_encode($minN) ?>, <?= json_encode($maxN) ?>, <?= json_encode($minC) ?>, <?= json_encode($maxC) ?>)">
                        <option value="">—</option>
                        <option value="Positivo" <?= $valorPrev === 'Positivo' ? 'selected' : '' ?>>Positivo</option>
                        <option value="Negativo" <?= $valorPrev === 'Negativo' ? 'selected' : '' ?>>Negativo</option>
                        <option value="Reactivo" <?= $valorPrev === 'Reactivo' ? 'selected' : '' ?>>Reactivo</option>
                        <option value="No reactivo" <?= $valorPrev === 'No reactivo' ? 'selected' : '' ?>>No reactivo</option>
                    </select>
                    <?php else: ?>
                    <input type="number" id="<?= $inputId ?>"
                        name="resultados[<?= $idx ?>][<?= $p['id_parametro'] ?>][valor]"
                        class="gl-input" style="font-size:.83rem;padding:.3rem .6rem;width:110px"
                        value="<?= htmlspecialchars($valorPrev) ?>"
                        step="any" placeholder="0.00"
                        oninput="evaluarCritico('<?= $inputId ?>',<?= json_encode($minN) ?>,<?= json_encode($maxN) ?>,<?= json_encode($minC) ?>,<?= json_encode($maxC) ?>)">
                    <?php endif; ?>
                </td>
                <td style="color:#94a3b8;white-space:nowrap"><?= htmlspecialchars($p['unidad_medida'] ?? '') ?></td>
                <td style="font-size:.75rem;color:#64748b;white-space:nowrap">
                    <?php
                    if ($minN !== null && $maxN !== null) echo $minN . ' – ' . $maxN;
                    elseif ($minN !== null) echo '≥ ' . $minN;
                    elseif ($maxN !== null) echo '≤ ' . $maxN;
                    else echo '<span style="color:#d1d5db">—</span>';
                    ?>
                </td>
                <td style="font-size:.75rem;color:#ef4444;white-space:nowrap">
                    <?php
                    if ($minC !== null || $maxC !== null) {
                        echo '<i class="bi bi-exclamation-triangle me-1"></i>';
                        if ($minC !== null && $maxC !== null) echo '< '.$minC.' o > '.$maxC;
                        elseif ($minC !== null) echo '< '.$minC;
                        else echo '> '.$maxC;
                    } else echo '<span style="color:#d1d5db">—</span>';
                    ?>
                </td>
                <td>
                    <div id="st_<?= $inputId ?>" style="font-size:.72rem">
                        <?php if ($previo): ?>
                        <span class="gl-badge <?= $esCrit ? 'badge-danger' : 'badge-success' ?>">
                            <?= $esCrit ? '⚠ Crítico' : 'Normal' ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="d-flex gap-2 justify-content-end mt-2">
    <a href="/ordenes/validar/<?= $o['id_orden'] ?>" class="btn-gl btn-outline-gl">Cancelar</a>
    <button type="submit" class="btn-gl btn-primary-gl">
        <i class="bi bi-flask"></i> Guardar resultados
    </button>
</div>
</form>

<script>
function evaluarCritico(id, minN, maxN, minC, maxC) {
    const inp = document.getElementById(id);
    const st  = document.getElementById('st_' + id);
    const row = document.getElementById('row_' + id);
    if (!inp || !st) return;
    const val = parseFloat(inp.value);
    if (isNaN(val)) { st.innerHTML = ''; row.style.background = ''; return; }

    let esCrit = false, esAlto = false, esBajo = false;
    if (minC !== null && val < minC) esCrit = true;
    if (maxC !== null && val > maxC) esCrit = true;
    if (!esCrit && minN !== null && val < minN) esBajo = true;
    if (!esCrit && maxN !== null && val > maxN) esAlto = true;

    if (esCrit) {
        st.innerHTML = '<span class="gl-badge badge-danger" style="font-size:.65rem;animation:pulse 2s infinite">⚠ CRÍTICO</span>';
        row.style.background = '#fef9f9';
    } else if (esBajo || esAlto) {
        st.innerHTML = '<span class="gl-badge badge-warning" style="font-size:.65rem">' + (esBajo ? '↓ Bajo' : '↑ Alto') + '</span>';
        row.style.background = '#fffbeb';
    } else {
        st.innerHTML = '<span class="gl-badge badge-success" style="font-size:.65rem">Normal</span>';
        row.style.background = '';
    }
}
</script>

<?php else: ?>
<!-- ═══════════════════════════════════════════════ -->
<!-- MODO: LISTADO DE ÓRDENES CON RESULTADOS         -->
<!-- ═══════════════════════════════════════════════ -->

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 style="font-size:1.45rem;font-weight:800;color:#0f172a;margin:0">Resultados</h1>
        <p style="font-size:.82rem;color:#64748b;margin:.15rem 0 0">Órdenes con carga de resultados</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (count($alertas ?? []) > 0): ?>
        <a href="/resultados/alertas" class="btn-gl btn-danger-gl" style="animation:pulse 2s infinite">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= count($alertas) ?> alerta<?= count($alertas) > 1 ? 's' : '' ?> crítica<?= count($alertas) > 1 ? 's' : '' ?>
        </a>
        <?php endif; ?>
        <?php if (\RBAC::puede('resultados.cargar_auto')): ?>
        <a href="/resultados/cargar-automatico" class="btn-gl btn-outline-gl">
            <i class="bi bi-cloud-upload"></i> Importar CSV
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros -->
<div class="gl-card mb-4">
    <div class="gl-card-body">
        <form method="GET" action="/resultados" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="gl-label">N° Orden</label>
                <input type="text" name="q" class="gl-input" placeholder="ORD-2025…"
                    value="<?= htmlspecialchars($filtros['numero_orden'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="gl-label">Estado</label>
                <select name="estado" class="gl-input gl-select">
                    <option value="">Todos</option>
                    <?php foreach ($estadoMap as $v => [$c, $l]): ?>
                    <option value="<?= $v ?>" <?= ($filtros['estado'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="gl-label">Desde</label>
                <input type="date" name="desde" class="gl-input" value="<?= htmlspecialchars($filtros['fecha_desde'] ?? date('Y-m-01')) ?>">
            </div>
            <div class="col-md-2">
                <label class="gl-label">Hasta</label>
                <input type="date" name="hasta" class="gl-input" value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <label class="gl-label d-block w-100" style="margin-bottom:0">
                    <input type="checkbox" name="criticos" value="1" <?= !empty($filtros['solo_criticos']) ? 'checked' : '' ?> style="accent-color:#ef4444">
                    Solo críticos
                </label>
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn-gl btn-primary-gl flex-fill"><i class="bi bi-search"></i></button>
                <a href="/resultados" class="btn-gl btn-outline-gl"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="gl-card">
    <div class="gl-card-header">
        <i class="bi bi-flask" style="color:var(--primary);font-size:1.05rem"></i>
        <h5><?= count($resultados ?? []) ?> orden(es)</h5>
    </div>
    <div style="overflow-x:auto">
        <table class="gl-table">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Paciente</th>
                    <th>Fecha</th>
                    <th class="text-center">Total res.</th>
                    <th class="text-center">Validados</th>
                    <th class="text-center">Críticos</th>
                    <th>Estado</th>
                    <th style="width:120px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($resultados)): ?>
                <tr><td colspan="8" style="text-align:center;padding:3rem;color:#94a3b8">
                    <i class="bi bi-flask" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3"></i>
                    No hay resultados para los filtros seleccionados
                </td></tr>
            <?php else: ?>
            <?php foreach ($resultados as $r): ?>
            <?php
                [$estCls, $estLbl] = $estadoMap[$r['estado'] ?? ''] ?? ['badge-gray', ucfirst($r['estado'] ?? '')];
                $pac = trim(($r['pac_nombres'] ?? '') . ' ' . ($r['pac_apellidos'] ?? '')) ?: '—';
                $criticos  = (int)($r['criticos'] ?? 0);
                $validados = (int)($r['validados'] ?? 0);
                $total     = (int)($r['total_resultados'] ?? 0);
            ?>
            <tr style="<?= $criticos > 0 ? 'background:#fef9f9' : '' ?>">
                <td>
                    <a href="/ordenes/validar/<?= $r['id_orden'] ?>" style="text-decoration:none">
                        <span class="fw-bold" style="color:var(--primary);font-family:monospace;font-size:.82rem">
                            <?= htmlspecialchars($r['numero_orden'] ?? '') ?>
                        </span>
                    </a>
                </td>
                <td class="fw-semibold" style="font-size:.87rem"><?= htmlspecialchars(mb_substr($pac, 0, 22)) ?></td>
                <td style="font-size:.8rem;color:#64748b;white-space:nowrap">
                    <?= !empty($r['fecha_orden']) ? date('d/m/Y', strtotime($r['fecha_orden'])) : '—' ?>
                </td>
                <td class="text-center"><span class="gl-badge badge-info"><?= $total ?></span></td>
                <td class="text-center">
                    <span class="gl-badge <?= $validados >= $total && $total > 0 ? 'badge-success' : 'badge-gray' ?>">
                        <?= $validados ?>/<?= $total ?>
                    </span>
                </td>
                <td class="text-center">
                    <?php if ($criticos > 0): ?>
                    <span class="critico-badge"><?= $criticos ?> ⚠</span>
                    <?php else: ?>
                    <span style="color:#d1d5db">—</span>
                    <?php endif; ?>
                </td>
                <td><span class="gl-badge <?= $estCls ?>"><?= $estLbl ?></span></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/resultados/cargar?orden=<?= $r['id_orden'] ?>"
                           class="btn-gl btn-primary-gl btn-sm-gl" title="Cargar resultados">
                            <i class="bi bi-flask"></i>
                        </a>
                        <a href="/ordenes/validar/<?= $r['id_orden'] ?>"
                           class="btn-gl btn-outline-gl btn-sm-gl" title="Ver orden">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.7}}</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>