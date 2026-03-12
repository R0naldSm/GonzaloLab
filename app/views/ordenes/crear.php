<?php
// Variables: $examenesPorCategoria, $pacientePreload, $medicos, $menuNav, $nombreUsuario, $csrfToken, $flash
$pageTitle   = 'Nueva Orden';
$breadcrumbs = [['label'=>'Órdenes','url'=>'/ordenes'],['label'=>'Nueva orden']];
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/ordenes" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">Nueva Orden de Laboratorio</h1>
        <p style="font-size:.82rem;color:#64748b;margin:0">Seleccione el paciente y los exámenes a realizar</p>
    </div>
</div>

<form method="POST" action="/ordenes/guardar" id="formOrden" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
<input type="hidden" name="examenes_json" id="examenesJson" value="[]">

<div class="row g-4">

    <!-- Columna izquierda -->
    <div class="col-lg-8">

        <!-- Paciente -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-person-circle" style="color:var(--primary);font-size:1.05rem"></i>
                <h5>Paciente <span style="color:#ef4444">*</span></h5>
                <?php if (empty($pacientePreload)): ?>
                <a href="/pacientes/crear?redirect_orden=1" class="btn-gl btn-outline-gl btn-sm-gl ms-auto">
                    <i class="bi bi-person-plus"></i> Registrar nuevo
                </a>
                <?php endif; ?>
            </div>
            <div class="gl-card-body">
                <?php if ($pacientePreload): ?>
                <!-- Paciente pre-cargado desde listado -->
                <input type="hidden" name="id_paciente" value="<?= $pacientePreload['id_paciente'] ?>">
                <div style="display:flex;align-items:center;gap:1rem;background:#f0fdfe;border:1px solid #a5f3fc;border-radius:.625rem;padding:.875rem 1rem">
                    <div style="width:42px;height:42px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;color:#fff;flex-shrink:0">
                        <?= strtoupper(substr($pacientePreload['nombres'] ?? 'P', 0, 1)) ?>
                    </div>
                    <div class="flex-fill">
                        <div class="fw-bold" style="color:#0f172a">
                            <?= htmlspecialchars(trim(($pacientePreload['nombres'] ?? '') . ' ' . ($pacientePreload['apellidos'] ?? ''))) ?>
                        </div>
                        <div style="font-size:.8rem;color:#64748b">
                            Cédula: <?= htmlspecialchars($pacientePreload['cedula'] ?? '—') ?>
                        </div>
                    </div>
                    <a href="/ordenes/crear" class="btn-gl btn-outline-gl btn-sm-gl">
                        <i class="bi bi-x-lg"></i> Cambiar
                    </a>
                </div>
                <?php else: ?>
                <!-- Búsqueda de paciente -->
                <div class="position-relative">
                    <input type="text" id="buscarPaciente" class="gl-input" autocomplete="off"
                        placeholder="Buscar por nombre o cédula…" style="padding-left:2.25rem">
                    <i class="bi bi-search" style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8"></i>
                    <div id="sugerencias" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;box-shadow:0 8px 24px rgba(0,0,0,.1);z-index:100;max-height:220px;overflow-y:auto"></div>
                </div>
                <input type="hidden" name="id_paciente" id="idPaciente" required>
                <div id="pacienteSeleccionado" style="display:none;margin-top:.75rem;background:#f0fdfe;border:1px solid #a5f3fc;border-radius:.625rem;padding:.75rem 1rem"></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Datos de la orden -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-sliders" style="color:#8b5cf6;font-size:1.05rem"></i>
                <h5>Datos de la orden</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="gl-label">Tipo de atención</label>
                        <select name="tipo_atencion" class="gl-input gl-select">
                            <option value="normal">Normal</option>
                            <option value="urgencia">Urgencia</option>
                            <option value="control">Control / Seguimiento</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="gl-label">Médico solicitante</label>
                        <select name="id_medico" class="gl-input gl-select">
                            <option value="">— Sin asignar —</option>
                            <?php foreach ($medicos ?? [] as $med): ?>
                            <option value="<?= $med['id_usuario'] ?>">
                                <?= htmlspecialchars($med['nombre_completo'] ?? $med['username']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Observaciones clínicas</label>
                        <textarea name="observaciones" class="gl-input" rows="2"
                            placeholder="Diagnóstico presuntivo, indicaciones especiales…"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catálogo de exámenes -->
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-flask" style="color:var(--primary);font-size:1.05rem"></i>
                <h5>Selección de exámenes <span style="color:#ef4444">*</span></h5>
                <div class="ms-auto">
                    <input type="text" id="filtroExamen" class="gl-input" style="width:200px;font-size:.8rem"
                        placeholder="Filtrar exámenes…" oninput="filtrarCatalogo(this.value)">
                </div>
            </div>
            <div class="gl-card-body" style="padding:.75rem">

                <?php if (empty($examenesPorCategoria)): ?>
                <p style="color:#94a3b8;text-align:center;padding:2rem">No hay exámenes activos disponibles</p>
                <?php else: ?>
                <?php foreach ($examenesPorCategoria as $cat): ?>
                <?php if (empty($cat['examenes'])) continue; ?>
                <div class="cat-group mb-2">
                    <div class="cat-hdr d-flex align-items-center gap-2 px-2 py-2 rounded mb-1"
                         style="background:#f8fafc;cursor:pointer;user-select:none"
                         onclick="toggleCat(this)">
                        <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:<?= htmlspecialchars($cat['color_hex'] ?? '#06b6d4') ?>;flex-shrink:0"></span>
                        <span style="font-size:.8rem;font-weight:700;color:#374151;flex:1"><?= htmlspecialchars($cat['nombre']) ?></span>
                        <span class="gl-badge badge-gray" style="font-size:.67rem"><?= count($cat['examenes']) ?></span>
                        <i class="bi bi-chevron-down" style="font-size:.7rem;color:#94a3b8;transition:.2s"></i>
                    </div>
                    <div class="cat-items">
                    <?php foreach ($cat['examenes'] as $ex): ?>
                    <label class="ex-row d-flex align-items-center gap-2 px-3 py-2 mb-1 rounded"
                           style="cursor:pointer;border:1px solid #f1f5f9;transition:all .15s;background:#fff"
                           data-nombre="<?= strtolower(htmlspecialchars($ex['nombre'])) ?>"
                           data-codigo="<?= strtolower(htmlspecialchars($ex['codigo'])) ?>"
                           onmouseenter="this.style.borderColor='#a5f3fc'"
                           onmouseleave="if(!this.querySelector('input').checked)this.style.borderColor='#f1f5f9'">
                        <input type="checkbox" name="examenes[]" value="<?= $ex['id_examen'] ?>"
                               style="width:16px;height:16px;accent-color:var(--primary);flex-shrink:0"
                               onchange="toggleExamen(this, <?= $ex['id_examen'] ?>, '<?= addslashes($ex['nombre']) ?>', <?= (float)($ex['precio'] ?? 0) ?>)">
                        <div class="flex-fill">
                            <span class="fw-semibold" style="font-size:.855rem"><?= htmlspecialchars($ex['nombre']) ?></span>
                            <span style="color:#94a3b8;font-size:.73rem;margin-left:.3rem"><?= htmlspecialchars($ex['codigo']) ?></span>
                            <?php if ($ex['requiere_ayuno'] ?? false): ?>
                            <span class="gl-badge badge-warning ms-1" style="font-size:.63rem">Ayuno</span>
                            <?php endif; ?>
                            <?php if (($ex['resultado_inmediato'] ?? false)): ?>
                            <span class="gl-badge badge-success ms-1" style="font-size:.63rem"><i class="bi bi-lightning-charge"></i> Inmediato</span>
                            <?php endif; ?>
                        </div>
                        <span class="fw-bold" style="color:var(--primary);font-size:.855rem;white-space:nowrap">
                            $<?= number_format((float)($ex['precio'] ?? 0), 2) ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </div><!-- /col-lg-8 -->

    <!-- Resumen lateral -->
    <div class="col-lg-4">
        <div class="gl-card" style="position:sticky;top:calc(var(--topbar-h)+1rem)">
            <div class="gl-card-header">
                <i class="bi bi-receipt" style="color:var(--primary);font-size:1.05rem"></i>
                <h5>Resumen de orden</h5>
            </div>
            <div class="gl-card-body">
                <div id="listaExamenes" style="min-height:60px;margin-bottom:.75rem">
                    <p id="vacioMsgO" style="color:#94a3b8;font-size:.82rem;text-align:center;padding:.75rem">
                        <i class="bi bi-inbox d-block mb-1" style="font-size:1.5rem"></i>
                        Seleccione exámenes
                    </p>
                </div>

                <hr style="border-color:#f1f5f9;margin:.5rem 0">

                <div style="background:#f8fafc;border-radius:.625rem;padding:1rem;margin-bottom:1rem">
                    <div class="d-flex justify-content-between mb-1" style="font-size:.82rem">
                        <span style="color:#64748b">Exámenes seleccionados</span>
                        <span id="cntExamenes" class="fw-semibold">0</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span style="font-weight:700;color:#0f172a">Total estimado</span>
                        <span style="font-size:1.2rem;font-weight:800;color:var(--primary)">$<span id="totalOrden">0.00</span></span>
                    </div>
                </div>

                <!-- Nota ayuno -->
                <div id="notaAyuno" style="display:none;background:#fffbeb;border:1px solid #fde68a;border-radius:.5rem;padding:.625rem .875rem;font-size:.78rem;color:#92400e;margin-bottom:1rem">
                    <i class="bi bi-moon-stars-fill me-1"></i>
                    <strong>Uno o más exámenes requieren ayuno de 8h.</strong> Informe al paciente.
                </div>

                <button type="submit" class="btn-gl btn-primary-gl w-100" id="btnCrear" disabled style="justify-content:center;padding:.7rem">
                    <i class="bi bi-check-lg"></i> Generar orden
                </button>
                <p id="msgBtn" style="text-align:center;font-size:.75rem;color:#94a3b8;margin-top:.5rem">
                    Seleccione paciente y al menos un examen
                </p>
            </div>
        </div>
    </div>

</div><!-- /row -->
</form>

<script>
let seleccionados = {};
let tieneAyuno    = {};

function toggleExamen(cb, id, nombre, precio) {
    const label = cb.closest('label');
    if (cb.checked) {
        seleccionados[id] = { id, nombre, precio };
        label.style.background   = '#f0fdfe';
        label.style.borderColor  = 'var(--primary)';
    } else {
        delete seleccionados[id];
        label.style.background   = '#fff';
        label.style.borderColor  = '#f1f5f9';
    }
    actualizarResumen();
    validarFormulario();
}

function actualizarResumen() {
    const keys  = Object.keys(seleccionados);
    const lista = document.getElementById('listaExamenes');
    const vacio = document.getElementById('vacioMsgO');
    vacio.style.display = keys.length ? 'none' : 'block';
    lista.querySelectorAll('.ex-sel').forEach(e => e.remove());

    let total = 0;
    let hayAyuno = false;
    keys.forEach(id => {
        const e = seleccionados[id];
        total += e.precio;
        const div = document.createElement('div');
        div.className = 'ex-sel d-flex align-items-center gap-2 mb-1 px-2 py-1 rounded';
        div.style.cssText = 'background:#f0fdfe;font-size:.8rem';
        div.innerHTML = `<span class="flex-fill fw-semibold">${e.nombre}</span>
            <span style="color:var(--primary);white-space:nowrap">$${e.precio.toFixed(2)}</span>
            <button type="button" onclick="quitarExamen(${id})" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:0;line-height:1;font-size:.85rem"><i class="bi bi-x"></i></button>`;
        lista.appendChild(div);
        // Chequear si requiere ayuno
        const cb = document.querySelector(`input[value="${id}"]`);
        if (cb?.closest('label')?.textContent?.includes('Ayuno')) hayAyuno = true;
    });

    document.getElementById('cntExamenes').textContent = keys.length;
    document.getElementById('totalOrden').textContent = total.toFixed(2);
    document.getElementById('notaAyuno').style.display = hayAyuno ? 'block' : 'none';
    document.getElementById('examenesJson').value = JSON.stringify(Object.values(seleccionados).map(e => e.id));
}

function quitarExamen(id) {
    const cb = document.querySelector(`input[value="${id}"]`);
    if (cb) { cb.checked = false; cb.dispatchEvent(new Event('change')); }
    else { delete seleccionados[id]; actualizarResumen(); validarFormulario(); }
}

function validarFormulario() {
    const tieneP = !!document.getElementById('idPaciente')?.value || !!document.querySelector('[name=id_paciente]')?.value;
    const tieneE = Object.keys(seleccionados).length > 0;
    const btn    = document.getElementById('btnCrear');
    const msg    = document.getElementById('msgBtn');
    btn.disabled = !(tieneP && tieneE);
    if (!tieneP && !tieneE) msg.textContent = 'Seleccione paciente y al menos un examen';
    else if (!tieneP) msg.textContent = 'Seleccione un paciente';
    else if (!tieneE) msg.textContent = 'Seleccione al menos un examen';
    else msg.textContent = '';
}

function filtrarCatalogo(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.ex-row').forEach(row => {
        const ok = !q || row.dataset.nombre?.includes(q) || row.dataset.codigo?.includes(q);
        row.style.display = ok ? '' : 'none';
    });
    document.querySelectorAll('.cat-group').forEach(g => {
        const visible = [...g.querySelectorAll('.ex-row')].some(r => r.style.display !== 'none');
        g.style.display = visible ? '' : 'none';
    });
}

function toggleCat(hdr) {
    const items = hdr.nextElementSibling;
    const icon  = hdr.querySelector('i');
    const hidden = items.style.display === 'none';
    items.style.display = hidden ? '' : 'none';
    icon.style.transform = hidden ? '' : 'rotate(180deg)';
}

// ── Autocompletado paciente ──
<?php if (empty($pacientePreload)): ?>
let debPac;
document.getElementById('buscarPaciente').addEventListener('input', function() {
    clearTimeout(debPac);
    const q = this.value.trim();
    const sug = document.getElementById('sugerencias');
    if (q.length < 2) { sug.style.display = 'none'; return; }
    debPac = setTimeout(() => {
        fetch('/pacientes/buscar?q=' + encodeURIComponent(q))
            .then(r => r.json()).then(d => {
                sug.innerHTML = '';
                if (!d.data?.length) { sug.style.display = 'none'; return; }
                d.data.slice(0, 8).forEach(p => {
                    const div = document.createElement('div');
                    div.style.cssText = 'padding:.6rem 1rem;cursor:pointer;font-size:.845rem;border-bottom:1px solid #f1f5f9;transition:background .15s';
                    div.innerHTML = `<strong>${p.nombre_completo}</strong> <span style="color:#94a3b8;font-size:.76rem">${p.cedula ?? ''}</span>`;
                    div.onmouseenter = () => div.style.background = '#f8fafc';
                    div.onmouseleave = () => div.style.background = '';
                    div.onclick = () => elegirPaciente(p, div);
                    sug.appendChild(div);
                });
                sug.style.display = 'block';
            });
    }, 300);
});

function elegirPaciente(p, div) {
    document.getElementById('idPaciente').value = p.id_paciente;
    document.getElementById('buscarPaciente').value = p.nombre_completo;
    document.getElementById('sugerencias').style.display = 'none';
    const sel = document.getElementById('pacienteSeleccionado');
    sel.innerHTML = `<div class="d-flex align-items-center gap-2">
        <div style="width:34px;height:34px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700">${p.nombre_completo[0].toUpperCase()}</div>
        <div><div class="fw-bold" style="font-size:.9rem">${p.nombre_completo}</div><div style="font-size:.75rem;color:#64748b">${p.cedula ?? ''}</div></div>
        <button type="button" onclick="limpiarPaciente()" class="btn-gl btn-outline-gl btn-sm-gl ms-auto"><i class="bi bi-x"></i></button>
    </div>`;
    sel.style.display = 'block';
    validarFormulario();
}

function limpiarPaciente() {
    document.getElementById('idPaciente').value = '';
    document.getElementById('buscarPaciente').value = '';
    document.getElementById('pacienteSeleccionado').style.display = 'none';
    validarFormulario();
}

document.addEventListener('click', e => {
    if (!e.target.closest('#buscarPaciente') && !e.target.closest('#sugerencias'))
        document.getElementById('sugerencias').style.display = 'none';
});
<?php else: ?>
// Paciente pre-cargado - habilitar botón si hay exámenes
document.getElementById('btnCrear'); // ya tiene id_paciente fijo
validarFormulario();
<?php endif; ?>

// Override validarFormulario para pre-cargado
<?php if ($pacientePreload): ?>
const _orig = validarFormulario;
window.validarFormulario = function() {
    const tieneE = Object.keys(seleccionados).length > 0;
    const btn = document.getElementById('btnCrear');
    const msg = document.getElementById('msgBtn');
    btn.disabled = !tieneE;
    msg.textContent = tieneE ? '' : 'Seleccione al menos un examen';
};
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>