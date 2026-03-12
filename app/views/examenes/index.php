<?php
$pageTitle = 'Catálogo de Exámenes';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 style="font-size:1.5rem;font-weight:800;color:#0f172a;margin:0">Catálogo de Exámenes</h1>
        <p style="font-size:.85rem;color:#64748b;margin:.2rem 0 0">Gestión de exámenes clínicos, categorías y precios</p>
    </div>
    <?php if (\RBAC::puede('examenes.crear')): ?>
    <a href="/examenes/crear" class="btn-gl btn-primary-gl">
        <i class="bi bi-plus-lg"></i> Nuevo examen
    </a>
    <?php endif; ?>
</div>

<!-- FILTROS -->
<div class="gl-card mb-4">
    <div class="gl-card-body">
        <form method="GET" action="/examenes" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="gl-label">Buscar examen</label>
                <input type="text" name="q" class="gl-input" placeholder="Nombre o código…"
                    value="<?= htmlspecialchars($filtros['busqueda'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="gl-label">Categoría</label>
                <select name="categoria" class="gl-input gl-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias ?? [] as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>"
                        <?= ($filtros['categoria'] ?? '') == $cat['id_categoria'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="gl-label">Estado</label>
                <select name="activo" class="gl-input gl-select">
                    <option value="">Todos</option>
                    <option value="1" <?= ($filtros['activo'] ?? '') === '1' ? 'selected' : '' ?>>Activos</option>
                    <option value="0" <?= ($filtros['activo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn-gl btn-primary-gl flex-fill">
                    <i class="bi bi-search"></i> Buscar
                </button>
                <a href="/examenes" class="btn-gl btn-outline-gl" title="Limpiar filtros">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Estadísticas rápidas -->
<?php
$total     = count($examenes ?? []);
$activos   = count(array_filter($examenes ?? [], fn($e) => $e['activo'] ?? false));
$inactivos = $total - $activos;
?>
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff">
                <i class="bi bi-flask" style="color:var(--secondary)"></i>
            </div>
            <div>
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Total exámenes</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f0fdf4">
                <i class="bi bi-check-circle" style="color:#10b981"></i>
            </div>
            <div>
                <div class="stat-value"><?= $activos ?></div>
                <div class="stat-label">Exámenes activos</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef2f2">
                <i class="bi bi-pause-circle" style="color:#ef4444"></i>
            </div>
            <div>
                <div class="stat-value"><?= $inactivos ?></div>
                <div class="stat-label">Inactivos</div>
            </div>
        </div>
    </div>
</div>

<!-- TABLA -->
<div class="gl-card">
    <div class="gl-card-header">
        <i class="bi bi-flask" style="color:var(--primary);font-size:1.1rem"></i>
        <h5><?= $total ?> examen(es) encontrado(s)</h5>
    </div>
    <div style="overflow-x:auto">
        <table class="gl-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Examen</th>
                    <th>Categoría</th>
                    <th>Método</th>
                    <th>Tiempo entrega</th>
                    <th>Precio</th>
                    <th>Ayuno</th>
                    <th>Estado</th>
                    <th style="width:140px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($examenes)): ?>
                <tr><td colspan="9" class="text-center py-5" style="color:#94a3b8">
                    <i class="bi bi-flask" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.4"></i>
                    <div style="font-size:.9rem">No hay exámenes que coincidan con los filtros</div>
                    <?php if (\RBAC::puede('examenes.crear')): ?>
                    <a href="/examenes/crear" class="btn-gl btn-primary-gl mt-3" style="display:inline-flex">
                        <i class="bi bi-plus-lg"></i> Crear primer examen
                    </a>
                    <?php endif; ?>
                </td></tr>
            <?php else: ?>
            <?php foreach ($examenes as $ex): ?>
            <tr>
                <td>
                    <code style="font-size:.78rem;background:#f1f5f9;padding:.2rem .5rem;border-radius:.3rem;color:var(--primary);font-weight:600">
                        <?= htmlspecialchars($ex['codigo'] ?? '') ?>
                    </code>
                </td>
                <td>
                    <div class="fw-semibold" style="color:#0f172a"><?= htmlspecialchars($ex['nombre']) ?></div>
                    <?php if (!empty($ex['descripcion'])): ?>
                    <div style="font-size:.76rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px">
                        <?= htmlspecialchars(mb_substr($ex['descripcion'], 0, 55)) ?>…
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="gl-badge badge-info" style="background:<?= htmlspecialchars($ex['color_hex'] ?? '#dbeafe') ?>22;color:<?= htmlspecialchars($ex['color_hex'] ?? '#1e40af') ?>">
                        <?= htmlspecialchars($ex['categoria'] ?? '—') ?>
                    </span>
                </td>
                <td style="font-size:.82rem;color:#64748b"><?= htmlspecialchars($ex['metodo_analisis'] ?? '—') ?></td>
                <td style="font-size:.82rem">
                    <?php
                    if (!empty($ex['tiempo_entrega_min'])) {
                        echo '<span class="gl-badge badge-warning"><i class="bi bi-lightning-charge"></i> ' . $ex['tiempo_entrega_min'] . ' min</span>';
                    } elseif (!empty($ex['tiempo_entrega_dias'])) {
                        echo '<span class="gl-badge badge-gray">' . $ex['tiempo_entrega_dias'] . ' días</span>';
                    } else {
                        echo '<span style="color:#94a3b8">—</span>';
                    }
                    ?>
                </td>
                <td class="fw-bold" style="color:var(--primary)">
                    $<?= number_format((float)($ex['precio'] ?? 0), 2) ?>
                </td>
                <td class="text-center">
                    <?= ($ex['requiere_ayuno'] ?? false)
                        ? '<i class="bi bi-moon-stars-fill" style="color:#f59e0b" title="Requiere ayuno"></i>'
                        : '<i class="bi bi-dash" style="color:#d1d5db"></i>' ?>
                </td>
                <td>
                    <span class="gl-badge <?= ($ex['activo'] ?? false) ? 'badge-success' : 'badge-gray' ?>">
                        <?= ($ex['activo'] ?? false) ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <?php if (\RBAC::puede('examenes.editar')): ?>
                        <a href="/examenes/editar/<?= $ex['id_examen'] ?>" class="btn-gl btn-outline-gl btn-sm-gl" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (\RBAC::puede('examenes.parametros')): ?>
                        <a href="/examenes/parametros/<?= $ex['id_examen'] ?>" class="btn-gl btn-outline-gl btn-sm-gl" title="Parámetros y rangos">
                            <i class="bi bi-sliders"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (\RBAC::puede('examenes.eliminar')): ?>
                        <button class="btn-gl btn-danger-gl btn-sm-gl" title="Eliminar"
                            onclick="eliminar(<?= $ex['id_examen'] ?>, '<?= htmlspecialchars(addslashes($ex['nombre'])) ?>')">
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

<script>
function eliminar(id, nombre) {
    if (!confirm('¿Eliminar el examen "' + nombre + '"?\nEsta acción no se puede deshacer.')) return;
    fetch('/examenes/eliminar/' + id, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({csrf_token: '<?= $csrfToken ?>'})
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
        else alert(d.message || 'No se pudo eliminar el examen');
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>