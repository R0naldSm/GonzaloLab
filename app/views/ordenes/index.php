<?php
$pageTitle = 'Órdenes de Laboratorio';
require_once __DIR__ . '/../layouts/header.php';

$estadoColores = [
    'creada'             => ['badge-info',    'Creada'],
    'en_proceso'         => ['badge-warning', 'En proceso'],
    'resultados_cargados'=> ['badge-purple',  'Resultados listos'],
    'validada'           => ['badge-teal',    'Validada'],
    'publicada'          => ['badge-success', 'Publicada'],
    'cancelada'          => ['badge-danger',  'Cancelada'],
];
$pagoCols = [
    'pendiente' => ['badge-warning','Pendiente'],
    'pagado'    => ['badge-success','Pagado'],
    'anulado'   => ['badge-danger', 'Anulado'],
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 style="font-size:1.45rem;font-weight:800;color:#0f172a;margin:0">Órdenes</h1>
        <p style="font-size:.82rem;color:#64748b;margin:.15rem 0 0">Gestión de órdenes de laboratorio clínico</p>
    </div>
    <?php if (\RBAC::puede('ordenes.crear')): ?>
    <a href="/ordenes/crear" class="btn-gl btn-primary-gl">
        <i class="bi bi-plus-lg"></i> Nueva orden
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="gl-card mb-4">
    <div class="gl-card-body">
        <form method="GET" action="/ordenes" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="gl-label">Buscar N° orden</label>
                <input type="text" name="q" class="gl-input" placeholder="ORD-2025…"
                       value="<?= htmlspecialchars($filtros['numero_orden'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="gl-label">Estado</label>
                <select name="estado" class="gl-input gl-select">
                    <option value="">Todos</option>
                    <?php foreach ($estadoColores as $v => [$cls,$lbl]): ?>
                    <option value="<?= $v ?>" <?= ($filtros['estado'] ?? '') === $v ? 'selected' : '' ?>>
                        <?= $lbl ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="gl-label">Pago</label>
                <select name="estado_pago" class="gl-input gl-select">
                    <option value="">Todos</option>
                    <?php foreach ($pagoCols as $v => [$cls,$lbl]): ?>
                    <option value="<?= $v ?>" <?= ($filtros['estado_pago'] ?? '') === $v ? 'selected' : '' ?>>
                        <?= $lbl ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="gl-label">Desde</label>
                <input type="date" name="desde" class="gl-input"
                       value="<?= htmlspecialchars($filtros['fecha_desde'] ?? date('Y-m-01')) ?>">
            </div>
            <div class="col-md-2">
                <label class="gl-label">Hasta</label>
                <input type="date" name="hasta" class="gl-input"
                       value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn-gl btn-primary-gl flex-fill" title="Buscar">
                    <i class="bi bi-search"></i>
                </button>
                <a href="/ordenes" class="btn-gl btn-outline-gl" title="Limpiar">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="gl-card">
    <div class="gl-card-header">
        <i class="bi bi-clipboard2-pulse" style="color:var(--primary);font-size:1.05rem"></i>
        <h5><?= count($ordenes ?? []) ?> orden(es)</h5>
        <span class="ms-auto" style="font-size:.77rem;color:#94a3b8">
            <?= htmlspecialchars($filtros['fecha_desde'] ?? '') ?> — <?= htmlspecialchars($filtros['fecha_hasta'] ?? '') ?>
        </span>
    </div>
    <div style="overflow-x:auto">
        <table class="gl-table">
            <thead>
                <tr>
                    <th>N° Orden</th>
                    <th>Fecha</th>
                    <th>Paciente</th>
                    <th>Médico</th>
                    <th>Exám.</th>
                    <th>Total</th>
                    <th>Pago</th>
                    <th>Estado</th>
                    <th style="width:150px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ordenes)): ?>
                <tr><td colspan="9" style="text-align:center;padding:3rem;color:#94a3b8">
                    <i class="bi bi-clipboard2-x" style="font-size:2.5rem;display:block;margin-bottom:.6rem;opacity:.35"></i>
                    <div style="font-size:.9rem">No hay órdenes para los filtros seleccionados</div>
                    <?php if (\RBAC::puede('ordenes.crear')): ?>
                    <a href="/ordenes/crear" class="btn-gl btn-primary-gl mt-3" style="display:inline-flex">
                        <i class="bi bi-plus-lg"></i> Crear primera orden
                    </a>
                    <?php endif; ?>
                </td></tr>
            <?php else: ?>
            <?php foreach ($ordenes as $o): ?>
            <?php
                [$estadoCls, $estadoLbl] = $estadoColores[$o['estado']] ?? ['badge-gray', ucfirst($o['estado'])];
                [$pagoCls, $pagoLbl]     = $pagoCols[$o['estado_pago'] ?? 'pendiente'] ?? ['badge-gray','—'];
                $paciente = trim(($o['pac_nombres'] ?? '') . ' ' . ($o['pac_apellidos'] ?? '')) ?: '—';
                $criticos = (int)($o['criticos'] ?? 0);
            ?>
            <tr>
                <td>
                    <a href="/ordenes/validar/<?= $o['id_orden'] ?>" style="text-decoration:none">
                        <span class="fw-bold" style="color:var(--primary);font-family:monospace;font-size:.82rem">
                            <?= htmlspecialchars($o['numero_orden'] ?? '') ?>
                        </span>
                    </a>
                    <?php if ($criticos > 0): ?>
                    <span class="critico-badge ms-1"><i class="bi bi-exclamation-triangle-fill"></i><?= $criticos ?>⚠</span>
                    <?php endif; ?>
                </td>
                <td style="color:#64748b;font-size:.82rem;white-space:nowrap">
                    <?= !empty($o['fecha_orden']) ? date('d/m/Y', strtotime($o['fecha_orden'])) : '—' ?>
                    <div style="font-size:.72rem;color:#c4cdd6">
                        <?= !empty($o['fecha_orden']) ? date('H:i', strtotime($o['fecha_orden'])) : '' ?>
                    </div>
                </td>
                <td>
                    <div class="fw-semibold" style="color:#0f172a;font-size:.87rem">
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($paciente, 0, 22))) ?>
                    </div>
                    <div style="font-size:.73rem;color:#94a3b8"><?= htmlspecialchars($o['pac_cedula'] ?? '') ?></div>
                </td>
                <td style="font-size:.82rem;color:#64748b">
                    <?= htmlspecialchars(mb_substr($o['medico_nombre'] ?? 'Sin asignar', 0, 18)) ?>
                </td>
                <td class="text-center">
                    <span class="gl-badge badge-info"><?= (int)($o['total_examenes'] ?? 0) ?></span>
                </td>
                <td class="fw-bold" style="color:var(--primary)">
                    $<?= number_format((float)($o['total_pagar'] ?? 0), 2) ?>
                </td>
                <td><span class="gl-badge <?= $pagoCls ?>"><?= $pagoLbl ?></span></td>
                <td><span class="gl-badge <?= $estadoCls ?>"><?= $estadoLbl ?></span></td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <!-- Ver / Validar -->
                        <a href="/ordenes/validar/<?= $o['id_orden'] ?>"
                           class="btn-gl btn-outline-gl btn-sm-gl" title="Ver detalle">
                            <i class="bi bi-eye"></i>
                        </a>
                        <!-- Cargar resultados -->
                        <?php if (\RBAC::puede('resultados.cargar_manual') && in_array($o['estado'], ['creada','en_proceso'])): ?>
                        <a href="/resultados/cargar?orden=<?= $o['id_orden'] ?>"
                           class="btn-gl btn-outline-gl btn-sm-gl" title="Cargar resultados">
                            <i class="bi bi-flask"></i>
                        </a>
                        <?php endif; ?>
                        <!-- Editar -->
                        <?php if (\RBAC::puede('ordenes.editar')): ?>
                        <a href="/ordenes/editar/<?= $o['id_orden'] ?>"
                           class="btn-gl btn-outline-gl btn-sm-gl" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <!-- Validar / Publicar -->
                        <?php if (\RBAC::puede('ordenes.validar') && $o['estado'] === 'resultados_cargados'): ?>
                        <button class="btn-gl btn-success-gl btn-sm-gl" title="Validar orden"
                            onclick="cambiarEstado(<?= $o['id_orden'] ?>, 'validar', '<?= htmlspecialchars($o['numero_orden']) ?>')">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <?php endif; ?>
                        <?php if (\RBAC::puede('ordenes.publicar') && $o['estado'] === 'validada'): ?>
                        <button class="btn-gl btn-primary-gl btn-sm-gl" title="Publicar resultados"
                            onclick="cambiarEstado(<?= $o['id_orden'] ?>, 'publicar', '<?= htmlspecialchars($o['numero_orden']) ?>')">
                            <i class="bi bi-send"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const CSRF = '<?= htmlspecialchars($csrfToken) ?>';
function cambiarEstado(id, accion, num) {
    const msgs = {
        validar:  '¿Validar la orden ' + num + '? Los resultados quedarán confirmados.',
        publicar: '¿Publicar la orden ' + num + '? El paciente podrá consultar sus resultados vía QR.',
    };
    if (!confirm(msgs[accion] || '¿Continuar?')) return;
    fetch('/ordenes/' + accion + '/' + id, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({csrf_token: CSRF})
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else alert(d.message || 'No se pudo completar la acción');
    }).catch(() => alert('Error de conexión'));
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>