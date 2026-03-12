<?php
$pageTitle = 'Cotizaciones';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 style="font-size:1.5rem;font-weight:800;color:#0f172a;margin:0">Cotizaciones</h1>
        <p style="font-size:.85rem;color:#64748b;margin:.2rem 0 0">Historial y gestión de cotizaciones de exámenes</p>
    </div>
    <?php if (\RBAC::puede('cotizaciones.crear')): ?>
    <a href="/cotizaciones/crear" class="btn-gl btn-primary-gl">
        <i class="bi bi-plus-lg"></i> Nueva cotización
    </a>
    <?php endif; ?>
</div>

<!-- FILTROS -->
<div class="gl-card mb-4">
    <div class="gl-card-body">
        <form method="GET" action="/cotizaciones" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="gl-label">Buscar</label>
                <input type="text" name="q" class="gl-input" placeholder="N° cotización o cliente…"
                    value="<?= htmlspecialchars($filtros['busqueda'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="gl-label">Estado</label>
                <select name="estado" class="gl-input gl-select">
                    <option value="">Todos</option>
                    <?php foreach (['vigente','aceptada','rechazada','expirada'] as $e): ?>
                    <option value="<?= $e ?>" <?= ($filtros['estado'] ?? '') === $e ? 'selected' : '' ?>>
                        <?= ucfirst($e) ?>
                    </option>
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
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn-gl btn-primary-gl flex-fill">
                    <i class="bi bi-search"></i> Filtrar
                </button>
                <a href="/cotizaciones/exportar?<?= http_build_query($filtros ?? []) ?>"
                   class="btn-gl btn-outline-gl" title="Exportar CSV">
                    <i class="bi bi-download"></i>
                </a>
                <a href="/cotizaciones" class="btn-gl btn-outline-gl" title="Limpiar">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- TABLA -->
<div class="gl-card">
    <div class="gl-card-header">
        <i class="bi bi-receipt" style="color:var(--primary);font-size:1.1rem"></i>
        <h5><?= count($cotizaciones ?? []) ?> cotización(es) encontrada(s)</h5>
    </div>
    <div style="overflow-x:auto">
        <table class="gl-table">
            <thead>
                <tr>
                    <th>N° Cotización</th>
                    <th>Fecha</th>
                    <th>Cliente / Paciente</th>
                    <th>Exámenes</th>
                    <th>Subtotal</th>
                    <th>Descuento</th>
                    <th>Total</th>
                    <th>Validez</th>
                    <th>Estado</th>
                    <th style="width:120px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($cotizaciones)): ?>
                <tr><td colspan="10" class="text-center py-4" style="color:#94a3b8;font-size:.85rem">
                    <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
                    No hay cotizaciones para los filtros seleccionados
                </td></tr>
            <?php else: ?>
                <?php foreach ($cotizaciones as $c): ?>
                <?php
                    $cliente = trim(($c['paciente_nombres'] ?? '') . ' ' . ($c['paciente_apellidos'] ?? ''))
                               ?: ($c['nombre_cliente'] ?? 'Cliente genérico');
                    $estadoBadge = [
                        'vigente'   => 'badge-info',
                        'aceptada'  => 'badge-success',
                        'rechazada' => 'badge-danger',
                        'expirada'  => 'badge-gray',
                    ][$c['estado']] ?? 'badge-gray';
                    $expirado = !empty($c['fecha_validez']) && $c['fecha_validez'] < date('Y-m-d');
                ?>
                <tr>
                    <td><a href="/cotizaciones/<?= $c['id_cotizacion'] ?>" class="fw-semibold" style="color:var(--primary);text-decoration:none"><?= htmlspecialchars($c['numero_cotizacion']) ?></a></td>
                    <td style="color:#64748b;font-size:.82rem"><?= date('d/m/Y', strtotime($c['fecha_cotizacion'])) ?></td>
                    <td>
                        <span class="fw-semibold"><?= htmlspecialchars(mb_substr($cliente, 0, 28)) ?></span>
                    </td>
                    <td>
                        <?php
                        $items = $c['items'] ?? [];
                        $nItems = count($items);
                        echo $nItems > 0
                            ? '<span class="gl-badge badge-info">'.$nItems.' examen'.($nItems>1?'es':'').'</span>'
                            : '—';
                        ?>
                    </td>
                    <td class="fw-semibold">$<?= number_format((float)$c['subtotal'], 2) ?></td>
                    <td style="color:#ef4444"><?= $c['descuento'] > 0 ? '-$'.number_format((float)$c['descuento'],2) : '—' ?></td>
                    <td class="fw-bold" style="color:#0f172a">$<?= number_format((float)$c['total'], 2) ?></td>
                    <td style="font-size:.8rem;color:<?= $expirado ? '#ef4444' : '#64748b' ?>">
                        <?= !empty($c['fecha_validez']) ? date('d/m/Y', strtotime($c['fecha_validez'])) : '—' ?>
                        <?= $expirado ? '<i class="bi bi-exclamation-triangle-fill ms-1"></i>' : '' ?>
                    </td>
                    <td><span class="gl-badge <?= $estadoBadge ?>"><?= ucfirst($c['estado']) ?></span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="/cotizaciones/<?= $c['id_cotizacion'] ?>" class="btn-gl btn-outline-gl btn-sm-gl" title="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (\RBAC::puede('cotizaciones.editar')): ?>
                            <button class="btn-gl btn-outline-gl btn-sm-gl" title="Cambiar estado"
                                onclick="abrirEstado(<?= $c['id_cotizacion'] ?>, '<?= $c['estado'] ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php endif; ?>
                            <?php if (\RBAC::puede('cotizaciones.eliminar')): ?>
                            <button class="btn-gl btn-danger-gl btn-sm-gl" title="Eliminar"
                                onclick="eliminar(<?= $c['id_cotizacion'] ?>, '<?= htmlspecialchars($c['numero_cotizacion']) ?>')">
                                <i class="bi bi-trash"></i>
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

<!-- MODAL CAMBIAR ESTADO -->
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="border-radius:.875rem;border:none;box-shadow:0 20px 60px rgba(0,0,0,.15)">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Cambiar estado</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEstado">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-body pt-2">
                    <label class="gl-label">Nuevo estado</label>
                    <select name="estado" class="gl-input gl-select" id="selectEstado">
                        <option value="vigente">Vigente</option>
                        <option value="aceptada">Aceptada</option>
                        <option value="rechazada">Rechazada</option>
                        <option value="expirada">Expirada</option>
                    </select>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn-gl btn-outline-gl" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-gl btn-primary-gl">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirEstado(id, estadoActual) {
    document.getElementById('formEstado').action = '/cotizaciones/' + id;
    document.getElementById('selectEstado').value = estadoActual;
    new bootstrap.Modal(document.getElementById('modalEstado')).show();
}

function eliminar(id, num) {
    if (!confirm('¿Eliminar cotización ' + num + '? Esta acción no se puede deshacer.')) return;
    fetch('/cotizaciones/eliminar/' + id, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({csrf_token: '<?= $csrfToken ?>'})
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else alert(d.message);
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>