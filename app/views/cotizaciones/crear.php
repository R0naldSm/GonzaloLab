<?php
$pageTitle = 'Nueva Cotización';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/cotizaciones" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">Nueva Cotización</h1>
        <p style="font-size:.82rem;color:#64748b;margin:0">Seleccione exámenes y calcule el total automáticamente</p>
    </div>
</div>

<form method="POST" action="/cotizaciones/guardar" id="formCotizacion" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
<input type="hidden" name="examenes_json" id="examenesJson" value="[]">

<div class="row g-4">

    <!-- Columna izquierda: datos + exámenes -->
    <div class="col-lg-8">

        <!-- Datos del cliente -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-person-circle" style="color:var(--primary);font-size:1.1rem"></i>
                <h5>Datos del cliente</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <!-- Buscar paciente -->
                    <div class="col-md-12">
                        <label class="gl-label">Buscar paciente (opcional)</label>
                        <div class="position-relative">
                            <input type="text" id="buscarPaciente" class="gl-input"
                                placeholder="Nombre o cédula del paciente…" autocomplete="off">
                            <div id="sugerencias" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;box-shadow:0 8px 24px rgba(0,0,0,.1);z-index:100;max-height:200px;overflow-y:auto"></div>
                        </div>
                        <input type="hidden" name="id_paciente" id="idPaciente">
                    </div>
                    <div class="col-md-8">
                        <label class="gl-label">Nombre del cliente (si no es paciente registrado)</label>
                        <input type="text" name="nombre_cliente" id="nombreCliente" class="gl-input"
                            placeholder="Nombre completo del cliente">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Validez de cotización</label>
                        <input type="date" name="fecha_validez" class="gl-input"
                            value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Observaciones</label>
                        <textarea name="observaciones" class="gl-input" rows="2"
                            placeholder="Notas adicionales para el cliente…"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catálogo de exámenes -->
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-flask" style="color:var(--primary);font-size:1.1rem"></i>
                <h5>Selección de exámenes</h5>
                <div class="ms-auto">
                    <input type="text" id="buscarExamen" class="gl-input" style="width:220px;font-size:.8rem"
                        placeholder="Filtrar exámenes…" oninput="filtrarExamenes(this.value)">
                </div>
            </div>
            <div class="gl-card-body" style="padding:.75rem">

                <?php if (empty($examenesPorCategoria)): ?>
                <p style="color:#94a3b8;text-align:center;padding:2rem">No hay exámenes activos en el catálogo</p>
                <?php else: ?>
                <?php foreach ($examenesPorCategoria as $cat): ?>
                <?php if (empty($cat['examenes'])) continue; ?>
                <div class="cat-group mb-2">
                    <div class="cat-header" style="padding:.5rem .75rem;background:#f8fafc;border-radius:.5rem;margin-bottom:.35rem;cursor:pointer;display:flex;align-items:center;gap:.5rem"
                        onclick="toggleCat(this)">
                        <span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:<?= htmlspecialchars($cat['color_hex'] ?? '#06b6d4') ?>"></span>
                        <span style="font-size:.8rem;font-weight:700;color:#374151"><?= htmlspecialchars($cat['nombre']) ?></span>
                        <span class="gl-badge badge-gray ms-1"><?= count($cat['examenes']) ?></span>
                        <i class="bi bi-chevron-down ms-auto" style="font-size:.75rem;color:#94a3b8"></i>
                    </div>
                    <div class="cat-items">
                    <?php foreach ($cat['examenes'] as $ex): ?>
                    <div class="examen-row d-flex align-items-center px-2 py-1 rounded mb-1"
                        style="background:#fff;border:1px solid #f1f5f9;transition:border .2s"
                        data-nombre="<?= strtolower(htmlspecialchars($ex['nombre'])) ?>"
                        data-codigo="<?= strtolower(htmlspecialchars($ex['codigo'])) ?>">
                        <input type="checkbox" class="form-check-input me-2" id="ex_<?= $ex['id_examen'] ?>"
                            onchange="toggleExamen(this, <?= $ex['id_examen'] ?>, '<?= addslashes($ex['nombre']) ?>', <?= (float)($ex['precio'] ?? 0) ?>)">
                        <label for="ex_<?= $ex['id_examen'] ?>" class="flex-fill" style="cursor:pointer;font-size:.845rem">
                            <span class="fw-semibold"><?= htmlspecialchars($ex['nombre']) ?></span>
                            <span style="color:#94a3b8;font-size:.75rem;margin-left:.35rem"><?= htmlspecialchars($ex['codigo']) ?></span>
                            <?php if ($ex['requiere_ayuno'] ?? false): ?>
                            <span class="gl-badge badge-warning ms-1" style="font-size:.65rem">Ayuno</span>
                            <?php endif; ?>
                        </label>
                        <span class="fw-bold" style="color:var(--primary);font-size:.855rem;white-space:nowrap">
                            $<?= number_format((float)($ex['precio'] ?? 0), 2) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Columna derecha: resumen -->
    <div class="col-lg-4">
        <div class="gl-card" style="position:sticky;top:calc(var(--topbar-h) + 1rem)">
            <div class="gl-card-header">
                <i class="bi bi-calculator" style="color:var(--primary);font-size:1.1rem"></i>
                <h5>Resumen</h5>
            </div>
            <div class="gl-card-body">

                <!-- Lista de seleccionados -->
                <div id="listaSeleccionados" style="min-height:60px;margin-bottom:1rem">
                    <p id="vacioMsg" style="color:#94a3b8;font-size:.82rem;text-align:center;padding:.75rem 0">
                        <i class="bi bi-inbox d-block mb-1" style="font-size:1.5rem"></i>
                        Seleccione exámenes del catálogo
                    </p>
                </div>

                <hr style="border-color:#f1f5f9;margin:.75rem 0">

                <!-- Descuento -->
                <div class="mb-3">
                    <label class="gl-label">Descuento ($)</label>
                    <input type="number" name="descuento" id="descuento" class="gl-input"
                        value="0" min="0" step="0.01" oninput="recalcular()">
                </div>

                <!-- Totales -->
                <div style="background:#f8fafc;border-radius:.625rem;padding:1rem;margin-bottom:1rem">
                    <div class="d-flex justify-content-between mb-2" style="font-size:.85rem">
                        <span style="color:#64748b">Subtotal</span>
                        <span class="fw-semibold">$<span id="subtotal">0.00</span></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size:.85rem">
                        <span style="color:#64748b">Descuento</span>
                        <span style="color:#ef4444">-$<span id="descuentoDisplay">0.00</span></span>
                    </div>
                    <hr style="border-color:#e2e8f0;margin:.5rem 0">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold" style="color:#0f172a">TOTAL</span>
                        <span class="fw-bold" style="font-size:1.25rem;color:var(--primary)">$<span id="total">0.00</span></span>
                    </div>
                </div>

                <button type="submit" class="btn-gl btn-primary-gl w-100" id="btnGuardar" disabled>
                    <i class="bi bi-check-lg"></i> Generar cotización
                </button>
                <p id="btnMsg" style="text-align:center;font-size:.75rem;color:#94a3b8;margin-top:.5rem">
                    Seleccione al menos un examen
                </p>
            </div>
        </div>
    </div>

</div>
</form>

<script>
let selectedExams = {};

function toggleExamen(cb, id, nombre, precio) {
    const row = cb.closest('.examen-row');
    if (cb.checked) {
        selectedExams[id] = { id_examen: id, nombre, precio, cantidad: 1 };
        row.style.borderColor = 'var(--primary)';
        row.style.background = '#f0fdfe';
    } else {
        delete selectedExams[id];
        row.style.borderColor = '#f1f5f9';
        row.style.background = '#fff';
    }
    renderSelected();
}

function renderSelected() {
    const lista = document.getElementById('listaSeleccionados');
    const vacio = document.getElementById('vacioMsg');
    const keys = Object.keys(selectedExams);
    vacio.style.display = keys.length ? 'none' : 'block';

    // Limpiar items anteriores
    lista.querySelectorAll('.sel-item').forEach(e => e.remove());

    keys.forEach(id => {
        const e = selectedExams[id];
        const div = document.createElement('div');
        div.className = 'sel-item d-flex align-items-center gap-2 mb-1 p-1';
        div.style.cssText = 'background:#f0fdfe;border-radius:.375rem;font-size:.8rem';
        div.innerHTML = `
            <span class="flex-fill fw-semibold">${e.nombre}</span>
            <span style="color:var(--primary);white-space:nowrap">$${(e.precio * e.cantidad).toFixed(2)}</span>
            <button type="button" onclick="quitarExamen(${id})"
                style="background:none;border:none;color:#ef4444;cursor:pointer;padding:0;line-height:1">
                <i class="bi bi-x"></i>
            </button>`;
        lista.appendChild(div);
    });

    recalcular();
    updateJSON();
    const btn = document.getElementById('btnGuardar');
    const msg = document.getElementById('btnMsg');
    btn.disabled = keys.length === 0;
    msg.style.display = keys.length === 0 ? 'block' : 'none';
}

function quitarExamen(id) {
    delete selectedExams[id];
    const cb = document.getElementById('ex_' + id);
    if (cb) { cb.checked = false; cb.dispatchEvent(new Event('change')); }
    else renderSelected();
}

function recalcular() {
    let sub = Object.values(selectedExams).reduce((a,e) => a + e.precio * e.cantidad, 0);
    let desc = parseFloat(document.getElementById('descuento').value) || 0;
    if (desc > sub) { desc = sub; document.getElementById('descuento').value = desc.toFixed(2); }
    document.getElementById('subtotal').textContent = sub.toFixed(2);
    document.getElementById('descuentoDisplay').textContent = desc.toFixed(2);
    document.getElementById('total').textContent = Math.max(0, sub - desc).toFixed(2);
}

function updateJSON() {
    document.getElementById('examenesJson').value = JSON.stringify(Object.values(selectedExams));
}

function filtrarExamenes(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.examen-row').forEach(row => {
        const visible = !q || row.dataset.nombre.includes(q) || row.dataset.codigo.includes(q);
        row.style.display = visible ? '' : 'none';
    });
    // Ocultar categoría si todos sus items están ocultos
    document.querySelectorAll('.cat-group').forEach(g => {
        const visible = [...g.querySelectorAll('.examen-row')].some(r => r.style.display !== 'none');
        g.style.display = visible ? '' : 'none';
    });
}

function toggleCat(header) {
    const items = header.nextElementSibling;
    const icon  = header.querySelector('.bi-chevron-down, .bi-chevron-up');
    items.style.display = items.style.display === 'none' ? '' : 'none';
    if (icon) icon.className = items.style.display === 'none' ? 'bi bi-chevron-up ms-auto' : 'bi bi-chevron-down ms-auto';
}

// Autocompletado paciente
let debounce;
document.getElementById('buscarPaciente').addEventListener('input', function() {
    clearTimeout(debounce);
    const q = this.value.trim();
    const sug = document.getElementById('sugerencias');
    if (q.length < 2) { sug.style.display = 'none'; return; }
    debounce = setTimeout(() => {
        fetch('/pacientes/buscar?q=' + encodeURIComponent(q))
            .then(r => r.json()).then(d => {
                sug.innerHTML = '';
                if (!d.data || !d.data.length) { sug.style.display = 'none'; return; }
                d.data.slice(0, 8).forEach(p => {
                    const item = document.createElement('div');
                    item.style.cssText = 'padding:.6rem 1rem;cursor:pointer;font-size:.845rem;border-bottom:1px solid #f1f5f9';
                    item.innerHTML = `<strong>${p.nombre_completo}</strong> <span style="color:#94a3b8;font-size:.78rem">${p.cedula || ''}</span>`;
                    item.onmouseenter = () => item.style.background = '#f8fafc';
                    item.onmouseleave = () => item.style.background = '';
                    item.onclick = () => {
                        document.getElementById('idPaciente').value = p.id_paciente;
                        document.getElementById('nombreCliente').value = p.nombre_completo;
                        document.getElementById('buscarPaciente').value = p.nombre_completo;
                        sug.style.display = 'none';
                    };
                    sug.appendChild(item);
                });
                sug.style.display = 'block';
            });
    }, 300);
});
document.addEventListener('click', e => {
    if (!e.target.closest('#buscarPaciente') && !e.target.closest('#sugerencias'))
        document.getElementById('sugerencias').style.display = 'none';
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>