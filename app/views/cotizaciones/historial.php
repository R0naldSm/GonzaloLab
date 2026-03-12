<?php
// view: cotizaciones/historial.php
// Variables esperadas: $cotizacion (array con items), $csrfToken, $flash
$pageTitle = 'Cotización ' . htmlspecialchars($cotizacion['numero_cotizacion'] ?? '');
require_once __DIR__ . '/../layouts/header.php';

$estadoClase = [
    'vigente'   => 'badge-info',
    'aceptada'  => 'badge-success',
    'rechazada' => 'badge-danger',
    'expirada'  => 'badge-gray',
][$cotizacion['estado'] ?? ''] ?? 'badge-gray';
?>

<!-- Breadcrumb + acciones -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <a href="/cotizaciones" class="btn-gl btn-outline-gl btn-sm-gl">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">
                <?= htmlspecialchars($cotizacion['numero_cotizacion'] ?? '') ?>
            </h1>
            <div class="d-flex align-items-center gap-2 mt-1">
                <span class="gl-badge <?= $estadoClase ?>"><?= ucfirst($cotizacion['estado'] ?? '') ?></span>
                <span style="font-size:.8rem;color:#64748b">
                    Generada el <?= date('d/m/Y H:i', strtotime($cotizacion['fecha_cotizacion'] ?? 'now')) ?>
                </span>
                <?php if (!empty($cotizacion['fecha_validez'])): ?>
                <span style="font-size:.8rem;color:<?= $cotizacion['fecha_validez'] < date('Y-m-d') ? '#ef4444' : '#10b981' ?>">
                    · Válida hasta <?= date('d/m/Y', strtotime($cotizacion['fecha_validez'])) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        <button onclick="window.print()" class="btn-gl btn-outline-gl">
            <i class="bi bi-printer"></i> Imprimir
        </button>
        <?php if (\RBAC::puede('cotizaciones.editar')): ?>
        <button class="btn-gl btn-outline-gl" data-bs-toggle="modal" data-bs-target="#modalEstado">
            <i class="bi bi-pencil"></i> Cambiar estado
        </button>
        <?php endif; ?>
        <?php if ($cotizacion['estado'] === 'aceptada' && \RBAC::puede('ordenes.crear')): ?>
        <a href="/ordenes/crear?desde_cotizacion=<?= $cotizacion['id_cotizacion'] ?>" class="btn-gl btn-primary-gl">
            <i class="bi bi-clipboard-plus"></i> Crear orden desde cotización
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">

    <!-- Detalle principal -->
    <div class="col-lg-8">
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-person-circle" style="color:var(--primary);font-size:1.1rem"></i>
                <h5>Cliente</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="gl-label mb-1">Nombre</p>
                        <p style="font-size:.93rem;font-weight:600;color:#0f172a">
                            <?php
                            $cliente = trim(($cotizacion['paciente_nombres'] ?? '') . ' ' . ($cotizacion['paciente_apellidos'] ?? ''))
                                       ?: ($cotizacion['nombre_cliente'] ?? '—');
                            echo htmlspecialchars($cliente);
                            ?>
                        </p>
                    </div>
                    <?php if (!empty($cotizacion['pac_cedula'])): ?>
                    <div class="col-md-3">
                        <p class="gl-label mb-1">Cédula</p>
                        <p style="font-size:.9rem"><?= htmlspecialchars($cotizacion['pac_cedula']) ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <p class="gl-label mb-1">Creado por</p>
                        <p style="font-size:.9rem;color:#64748b">@<?= htmlspecialchars($cotizacion['creado_por_username'] ?? '—') ?></p>
                    </div>
                    <?php if (!empty($cotizacion['observaciones'])): ?>
                    <div class="col-12">
                        <p class="gl-label mb-1">Observaciones</p>
                        <p style="font-size:.88rem;color:#374151;background:#f8fafc;padding:.625rem .875rem;border-radius:.5rem;margin:0">
                            <?= nl2br(htmlspecialchars($cotizacion['observaciones'])) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Items de exámenes -->
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-flask" style="color:var(--primary);font-size:1.1rem"></i>
                <h5>Exámenes cotizados</h5>
                <span class="gl-badge badge-info ms-auto"><?= count($items ?? []) ?> ítem(s)</span>
            </div>
            <div style="overflow-x:auto">
                <table class="gl-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Examen</th>
                            <th>Código</th>
                            <th>Categoría</th>
                            <th class="text-end">P. Unit.</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="7" class="text-center py-3" style="color:#94a3b8;font-size:.85rem">Sin ítems</td></tr>
                    <?php else: ?>
                    <?php foreach ($items as $i => $item): ?>
                        <tr>
                            <td style="color:#94a3b8;font-size:.8rem"><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($item['nombre_examen'] ?? '—') ?></td>
                            <td><code style="font-size:.78rem;background:#f1f5f9;padding:.1rem .35rem;border-radius:.25rem"><?= htmlspecialchars($item['codigo'] ?? '') ?></code></td>
                            <td><span class="gl-badge badge-gray"><?= htmlspecialchars($item['categoria'] ?? '') ?></span></td>
                            <td class="text-end">$<?= number_format((float)($item['precio_unitario'] ?? 0), 2) ?></td>
                            <td class="text-center"><?= (int)($item['cantidad'] ?? 1) ?></td>
                            <td class="text-end fw-semibold">$<?= number_format((float)($item['subtotal'] ?? 0), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar derecho: resumen + historial cambios -->
    <div class="col-lg-4">

        <!-- Totales -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-calculator" style="color:var(--primary);font-size:1.1rem"></i>
                <h5>Resumen económico</h5>
            </div>
            <div class="gl-card-body">
                <div style="background:#f8fafc;border-radius:.625rem;padding:1.1rem">
                    <?php
                    $subtotal  = (float)($cotizacion['subtotal']  ?? 0);
                    $descuento = (float)($cotizacion['descuento'] ?? 0);
                    $total     = (float)($cotizacion['total']     ?? 0);
                    ?>
                    <div class="d-flex justify-content-between mb-2" style="font-size:.875rem">
                        <span style="color:#64748b">Subtotal</span>
                        <span class="fw-semibold">$<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <?php if ($descuento > 0): ?>
                    <div class="d-flex justify-content-between mb-2" style="font-size:.875rem">
                        <span style="color:#64748b">Descuento</span>
                        <span style="color:#ef4444;font-weight:600">-$<?= number_format($descuento, 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <hr style="border-color:#e2e8f0;margin:.625rem 0">
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-weight:700;color:#0f172a">TOTAL</span>
                        <span style="font-size:1.5rem;font-weight:800;color:var(--primary)">
                            $<?= number_format($total, 2) ?>
                        </span>
                    </div>
                </div>

                <?php if ($cotizacion['estado'] === 'aceptada'): ?>
                <div class="mt-3 p-3" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.5rem">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill" style="color:#10b981"></i>
                        <span style="font-size:.83rem;font-weight:600;color:#065f46">Cotización aceptada por el cliente</span>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($cotizacion['estado'] === 'rechazada'): ?>
                <div class="mt-3 p-3" style="background:#fef2f2;border:1px solid #fecaca;border-radius:.5rem">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-x-circle-fill" style="color:#ef4444"></i>
                        <span style="font-size:.83rem;font-weight:600;color:#7f1d1d">Cotización rechazada</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Acciones rápidas -->
        <?php if (\RBAC::puede('cotizaciones.editar')): ?>
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-lightning-charge" style="color:var(--warning);font-size:1rem"></i>
                <h5>Acciones rápidas</h5>
            </div>
            <div class="gl-card-body d-flex flex-column gap-2">
                <?php if ($cotizacion['estado'] === 'vigente'): ?>
                <form method="POST" action="/cotizaciones/<?= $cotizacion['id_cotizacion'] ?>" class="d-grid">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="estado" value="aceptada">
                    <button type="submit" class="btn-gl" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;justify-content:center">
                        <i class="bi bi-check-lg"></i> Marcar como aceptada
                    </button>
                </form>
                <form method="POST" action="/cotizaciones/<?= $cotizacion['id_cotizacion'] ?>" class="d-grid">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="estado" value="rechazada">
                    <button type="submit" class="btn-gl btn-danger-gl" style="justify-content:center">
                        <i class="bi bi-x-lg"></i> Marcar como rechazada
                    </button>
                </form>
                <?php endif; ?>
                <a href="/cotizaciones/exportar/<?= $cotizacion['id_cotizacion'] ?>" class="btn-gl btn-outline-gl" style="justify-content:center">
                    <i class="bi bi-file-earmark-pdf"></i> Exportar PDF/CSV
                </a>
            </div>
        </div>
        <?php endif; ?>
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
            <form method="POST" action="/cotizaciones/<?= $cotizacion['id_cotizacion'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="modal-body pt-2">
                    <label class="gl-label">Nuevo estado</label>
                    <select name="estado" class="gl-input gl-select">
                        <?php foreach (['vigente','aceptada','rechazada','expirada'] as $e): ?>
                        <option value="<?= $e ?>" <?= ($cotizacion['estado'] ?? '') === $e ? 'selected' : '' ?>>
                            <?= ucfirst($e) ?>
                        </option>
                        <?php endforeach; ?>
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

<style>
@media print {
    #sidebar, #topbar, .btn-gl, .modal { display: none !important; }
    #main { margin: 0 !important; padding: 0 !important; }
    .gl-card { box-shadow: none !important; border: 1px solid #ccc !important; }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>