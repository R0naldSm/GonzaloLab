<?php
// Variables: $result (resultado individual), $menuNav, $nombreUsuario, $csrfToken, $flash
// Esta vista se usa para editar UN resultado específico
$r = $result;
$pageTitle   = 'Editar resultado';
$breadcrumbs = [
    ['label'=>'Resultados','url'=>'/resultados'],
    ['label'=>'Orden #'.($r['id_orden']??''),'url'=>'/ordenes/validar/'.($r['id_orden']??'')],
    ['label'=>'Editar resultado'],
];
require_once __DIR__ . '/../layouts/header.php';

$numVal  = is_numeric($r['valor_resultado'] ?? '') ? (float)$r['valor_resultado'] : null;
$esCrit  = (bool)($r['es_critico'] ?? false);
$minN    = $r['valor_min_normal']  ?? null;
$maxN    = $r['valor_max_normal']  ?? null;
$minC    = $r['valor_min_critico'] ?? null;
$maxC    = $r['valor_max_critico'] ?? null;

// Estado del valor actual
$estado = 'normal';
if ($esCrit) $estado = 'critico';
elseif ($numVal !== null) {
    if ($minN !== null && $numVal < (float)$minN) $estado = 'bajo';
    elseif ($maxN !== null && $numVal > (float)$maxN) $estado = 'alto';
}
$estadoColors = ['normal'=>'#10b981','bajo'=>'#f59e0b','alto'=>'#f59e0b','critico'=>'#ef4444'];
$estadoLabels = ['normal'=>'Normal','bajo'=>'Bajo ↓','alto'=>'Alto ↑','critico'=>'⚠ CRÍTICO'];
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/ordenes/validar/<?= $r['id_orden'] ?>" class="btn-gl btn-outline-gl btn-sm-gl">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">Editar resultado individual</h1>
        <p style="font-size:.82rem;color:#64748b;margin:0">
            <?= htmlspecialchars($r['nombre_examen'] ?? '') ?> →
            <strong><?= htmlspecialchars($r['nombre_parametro'] ?? '') ?></strong>
        </p>
    </div>
</div>

<?php if ($esCrit): ?>
<div class="gl-alert gl-alert-error mb-4">
    <i class="bi bi-exclamation-octagon-fill" style="flex-shrink:0"></i>
    <div><strong>Valor crítico activo.</strong> Verifique que el nuevo valor sea correcto y notifique al médico si es necesario.</div>
</div>
<?php endif; ?>

<div class="row g-4 justify-content-center">
    <div class="col-lg-7">

        <!-- Información del resultado actual -->
        <div class="gl-card mb-4" style="border-left:4px solid <?= $estadoColors[$estado] ?>">
            <div class="gl-card-header">
                <i class="bi bi-flask" style="color:var(--primary)"></i>
                <h5>Valor actual</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4 text-center">
                        <div style="font-size:2.5rem;font-weight:800;color:<?= $estadoColors[$estado] ?>;line-height:1">
                            <?= htmlspecialchars($r['valor_resultado'] ?? '—') ?>
                        </div>
                        <div style="font-size:.85rem;color:#94a3b8"><?= htmlspecialchars($r['unidad_medida'] ?? '') ?></div>
                        <span class="gl-badge mt-2 <?= ['normal'=>'badge-success','bajo'=>'badge-warning','alto'=>'badge-warning','critico'=>'badge-danger'][$estado] ?>">
                            <?= $estadoLabels[$estado] ?>
                        </span>
                    </div>
                    <div class="col-md-8">
                        <div style="font-size:.82rem;color:#64748b">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Parámetro</span>
                                <strong style="color:#0f172a"><?= htmlspecialchars($r['nombre_parametro'] ?? '') ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Examen</span>
                                <span><?= htmlspecialchars($r['nombre_examen'] ?? '') ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Rango normal</span>
                                <span class="fw-semibold" style="color:#0f172a">
                                    <?php
                                    if ($minN !== null && $maxN !== null) echo $minN . ' – ' . $maxN;
                                    elseif ($minN !== null) echo '≥ ' . $minN;
                                    elseif ($maxN !== null) echo '≤ ' . $maxN;
                                    else echo '—';
                                    ?>
                                    <?= htmlspecialchars($r['unidad_medida'] ?? '') ?>
                                </span>
                            </div>
                            <?php if ($minC !== null || $maxC !== null): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Rango crítico</span>
                                <span class="fw-semibold" style="color:#ef4444">
                                    <?php
                                    if ($minC !== null && $maxC !== null) echo '< ' . $minC . ' o > ' . $maxC;
                                    elseif ($minC !== null) echo '< ' . $minC;
                                    else echo '> ' . $maxC;
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($r['cargado_por_username'])): ?>
                            <div class="d-flex justify-content-between">
                                <span>Cargado por</span>
                                <span>@<?= htmlspecialchars($r['cargado_por_username']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de edición -->
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-pencil-square" style="color:#f59e0b"></i>
                <h5>Nuevo valor</h5>
            </div>
            <div class="gl-card-body">
                <form method="POST" action="/resultados/actualizar/<?= $r['id_resultado'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="mb-3">
                        <label class="gl-label">Valor corregido <span style="color:#ef4444">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" name="valor_resultado" id="nuevoValor" class="gl-input" required
                                value="<?= htmlspecialchars($r['valor_resultado'] ?? '') ?>"
                                style="width:180px;font-size:1.1rem;font-weight:700;text-align:center"
                                oninput="previewEstado(this.value)">
                            <span style="color:#64748b;font-size:.9rem"><?= htmlspecialchars($r['unidad_medida'] ?? '') ?></span>
                            <div id="previewBadge"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="gl-label">Motivo de la corrección <span style="color:#94a3b8;font-size:.72rem">(opcional)</span></label>
                        <textarea name="observacion_correccion" class="gl-input" rows="2"
                            placeholder="Ej: Error de transcripción, valor confirmado con repetición del análisis…"></textarea>
                    </div>

                    <div class="gl-alert gl-alert-warning mb-3" style="font-size:.8rem">
                        <i class="bi bi-exclamation-triangle" style="flex-shrink:0"></i>
                        Al editar un resultado, la orden volverá al estado "En proceso" para ser validada nuevamente.
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="/ordenes/validar/<?= $r['id_orden'] ?>" class="btn-gl btn-outline-gl">Cancelar</a>
                        <button type="submit" class="btn-gl btn-primary-gl">
                            <i class="bi bi-check-lg"></i> Guardar corrección
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
const minN = <?= json_encode($minN) ?>;
const maxN = <?= json_encode($maxN) ?>;
const minC = <?= json_encode($minC) ?>;
const maxC = <?= json_encode($maxC) ?>;

function previewEstado(val) {
    const badge = document.getElementById('previewBadge');
    const num   = parseFloat(val);
    if (isNaN(num)) { badge.innerHTML = ''; return; }

    let estado = 'normal';
    if (minC !== null && num < minC) estado = 'critico';
    else if (maxC !== null && num > maxC) estado = 'critico';
    else if (minN !== null && num < minN) estado = 'bajo';
    else if (maxN !== null && num > maxN) estado = 'alto';

    const cls  = {normal:'badge-success',bajo:'badge-warning',alto:'badge-warning',critico:'badge-danger'}[estado];
    const lbl  = {normal:'Normal',bajo:'Bajo ↓',alto:'Alto ↑',critico:'⚠ CRÍTICO'}[estado];
    badge.innerHTML = `<span class="gl-badge ${cls}" style="${estado==='critico'?'animation:pulse 1.5s infinite':''}">${lbl}</span>`;
}

previewEstado(document.getElementById('nuevoValor').value);
</script>
<style>@keyframes pulse{0%,100%{opacity:1}50%{opacity:.7}}</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>