<?php
// Variables: $orden, $medicos, $menuNav, $nombreUsuario, $csrfToken, $flash
$pageTitle   = 'Editar Orden';
$breadcrumbs = [['label'=>'Órdenes','url'=>'/ordenes'],['label'=>$orden['numero_orden'] ?? '','url'=>'/ordenes/validar/'.($orden['id_orden']??'')],['label'=>'Editar']];
require_once __DIR__ . '/../layouts/header.php';

$o = $orden;
$paciente = trim(($o['pac_nombres'] ?? '') . ' ' . ($o['pac_apellidos'] ?? '')) ?: '—';

$estadoEditable = in_array($o['estado'] ?? '', ['creada', 'en_proceso']);
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/ordenes/validar/<?= $o['id_orden'] ?>" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">
            Editar orden <span style="color:var(--primary);font-family:monospace"><?= htmlspecialchars($o['numero_orden'] ?? '') ?></span>
        </h1>
        <p style="font-size:.82rem;color:#64748b;margin:0">Solo médico, tipo de atención y observaciones son editables</p>
    </div>
</div>

<?php if (!$estadoEditable): ?>
<div class="gl-alert gl-alert-warning mb-4">
    <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0"></i>
    <div>
        <strong>Edición limitada:</strong> Esta orden está en estado
        <span class="gl-badge badge-warning"><?= htmlspecialchars($o['estado']) ?></span>.
        Solo se pueden editar datos administrativos (médico, pago, observaciones). Para modificar exámenes cree una nueva orden.
    </div>
</div>
<?php endif; ?>

<form method="POST" action="/ordenes/actualizar/<?= $o['id_orden'] ?>" id="formEditar" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

<div class="row g-4">
    <div class="col-lg-8">

        <!-- Info del paciente (solo lectura) -->
        <div class="gl-card mb-4" style="border:1px solid #e2e8f0">
            <div class="gl-card-header" style="background:#f8fafc">
                <i class="bi bi-person-circle" style="color:#94a3b8;font-size:1.05rem"></i>
                <h5 style="color:#64748b">Paciente (solo lectura)</h5>
            </div>
            <div class="gl-card-body">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;color:#fff;flex-shrink:0">
                        <?= strtoupper(substr($o['pac_nombres'] ?? 'P', 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($paciente) ?></div>
                        <div style="font-size:.8rem;color:#64748b">
                            Cédula: <?= htmlspecialchars($o['pac_cedula'] ?? '—') ?>
                            <?php if (!empty($o['pac_cedula'])): ?>
                            · <a href="/pacientes/historial/<?= $o['id_paciente'] ?>" style="color:var(--primary)">Ver historial</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exámenes (solo lectura) -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-flask" style="color:var(--primary);font-size:1.05rem"></i>
                <h5>Exámenes de la orden</h5>
                <span class="gl-badge badge-info ms-auto"><?= count($o['examenes'] ?? []) ?> examen(es)</span>
            </div>
            <div style="overflow-x:auto">
                <table class="gl-table">
                    <thead><tr><th>Examen</th><th>Código</th><th>Categoría</th><th class="text-end">Precio</th></tr></thead>
                    <tbody>
                    <?php if (empty($o['examenes'])): ?>
                        <tr><td colspan="4" class="text-center py-3" style="color:#94a3b8">Sin exámenes</td></tr>
                    <?php else: ?>
                    <?php foreach ($o['examenes'] as $ex): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($ex['nombre_examen'] ?? '—') ?></td>
                        <td><code style="font-size:.78rem;background:#f1f5f9;padding:.1rem .35rem;border-radius:.25rem"><?= htmlspecialchars($ex['codigo'] ?? '') ?></code></td>
                        <td><span class="gl-badge badge-gray"><?= htmlspecialchars($ex['categoria'] ?? '') ?></span></td>
                        <td class="text-end fw-semibold" style="color:var(--primary)">$<?= number_format((float)($ex['precio'] ?? 0), 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Campos editables -->
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-pencil-square" style="color:#f59e0b;font-size:1.05rem"></i>
                <h5>Datos editables</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="gl-label">Médico solicitante</label>
                        <select name="id_medico" class="gl-input gl-select">
                            <option value="">— Sin asignar —</option>
                            <?php foreach ($medicos ?? [] as $med): ?>
                            <option value="<?= $med['id_usuario'] ?>"
                                <?= ($o['id_medico'] ?? '') == $med['id_usuario'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($med['nombre_completo'] ?? $med['username']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="gl-label">Tipo de atención</label>
                        <select name="tipo_atencion" class="gl-input gl-select">
                            <?php foreach (['normal'=>'Normal','urgencia'=>'Urgencia','control'=>'Control'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($o['tipo_atencion'] ?? 'normal') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="gl-label">Método de pago</label>
                        <select name="metodo_pago" class="gl-input gl-select">
                            <option value="">—</option>
                            <?php foreach (['efectivo'=>'Efectivo','transferencia'=>'Transferencia','tarjeta'=>'Tarjeta','seguro'=>'Seguro médico'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($o['metodo_pago'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Observaciones</label>
                        <textarea name="observaciones" class="gl-input" rows="3"><?= htmlspecialchars($o['observaciones'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /col-lg-8 -->

    <div class="col-lg-4">
        <!-- Resumen -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-receipt" style="color:var(--primary)"></i>
                <h5>Resumen</h5>
            </div>
            <div class="gl-card-body">
                <div style="background:#f8fafc;border-radius:.625rem;padding:1rem;margin-bottom:1rem">
                    <div class="d-flex justify-content-between mb-2" style="font-size:.82rem">
                        <span style="color:#64748b">N° Orden</span>
                        <span class="fw-semibold" style="font-family:monospace"><?= htmlspecialchars($o['numero_orden'] ?? '') ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size:.82rem">
                        <span style="color:#64748b">Fecha</span>
                        <span><?= !empty($o['fecha_orden']) ? date('d/m/Y', strtotime($o['fecha_orden'])) : '—' ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size:.82rem">
                        <span style="color:#64748b">Estado</span>
                        <?php
                        $emap = ['creada'=>'badge-info','en_proceso'=>'badge-warning','resultados_cargados'=>'badge-purple','validada'=>'badge-info','publicada'=>'badge-success','cancelada'=>'badge-danger'];
                        ?>
                        <span class="gl-badge <?= $emap[$o['estado'] ?? ''] ?? 'badge-gray' ?>"><?= ucfirst(str_replace('_',' ',$o['estado'] ?? '')) ?></span>
                    </div>
                    <hr style="border-color:#e2e8f0;margin:.5rem 0">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold">Total a pagar</span>
                        <span class="fw-bold" style="font-size:1.15rem;color:var(--primary)">
                            $<?= number_format((float)($o['total_pagar'] ?? 0), 2) ?>
                        </span>
                    </div>
                </div>
                <div class="d-flex flex-column gap-2">
                    <button type="submit" class="btn-gl btn-primary-gl" style="justify-content:center;padding:.7rem">
                        <i class="bi bi-check-lg"></i> Guardar cambios
                    </button>
                    <a href="/ordenes/validar/<?= $o['id_orden'] ?>" class="btn-gl btn-outline-gl" style="justify-content:center">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>