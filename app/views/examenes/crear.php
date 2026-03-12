<?php
$pageTitle = 'Nuevo Examen';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/examenes" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">Nuevo Examen</h1>
        <p style="font-size:.82rem;color:#64748b;margin:0">Complete los datos del examen clínico</p>
    </div>
</div>

<form method="POST" action="/examenes/guardar" id="formExamen" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

<div class="row g-4">

    <!-- Columna principal -->
    <div class="col-lg-8">

        <!-- Datos básicos -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-info-circle" style="color:var(--primary);font-size:1.05rem"></i>
                <h5>Datos del examen</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="gl-label">Nombre del examen <span style="color:#ef4444">*</span></label>
                        <input type="text" name="nombre" class="gl-input" required
                            placeholder="Ej: Hemograma Completo"
                            value="<?= htmlspecialchars($examen['nombre'] ?? '') ?>"
                            oninput="sugerirCodigo(this.value)">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Código <span style="color:#ef4444">*</span>
                            <span id="codigoStatus" style="font-size:.72rem;margin-left:.4rem"></span>
                        </label>
                        <input type="text" name="codigo" id="codigo" class="gl-input" required
                            placeholder="HEM-001"
                            value="<?= htmlspecialchars($examen['codigo'] ?? '') ?>"
                            oninput="validarCodigo(this.value)" style="text-transform:uppercase">
                    </div>
                    <div class="col-md-6">
                        <label class="gl-label">Categoría <span style="color:#ef4444">*</span></label>
                        <select name="id_categoria" class="gl-input gl-select" required>
                            <option value="">— Seleccionar categoría —</option>
                            <?php foreach ($categorias ?? [] as $cat): ?>
                            <option value="<?= $cat['id_categoria'] ?>"
                                <?= ($examen['id_categoria'] ?? '') == $cat['id_categoria'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="gl-label">Método de análisis</label>
                        <input type="text" name="metodo_analisis" class="gl-input"
                            placeholder="Ej: Espectrofotometría, ELISA, PCR…"
                            value="<?= htmlspecialchars($examen['metodo_analisis'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Descripción</label>
                        <textarea name="descripcion" class="gl-input" rows="3"
                            placeholder="Descripción del examen, indicaciones clínicas…"><?= htmlspecialchars($examen['descripcion'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Preparación del paciente</label>
                        <textarea name="preparacion_paciente" class="gl-input" rows="2"
                            placeholder="Instrucciones de preparación (ej: ayuno de 8 horas, no ejercicio previo…)"><?= htmlspecialchars($examen['preparacion_paciente'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Muestra y método -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-droplet-half" style="color:#8b5cf6;font-size:1.05rem"></i>
                <h5>Tipo de muestra</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="gl-label">Tipo de muestra <span style="color:#ef4444">*</span></label>
                        <select name="tipo_muestra" class="gl-input gl-select" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ([
                                'sangre_venosa'    => 'Sangre venosa',
                                'sangre_capilar'   => 'Sangre capilar',
                                'orina'            => 'Orina',
                                'heces'            => 'Heces',
                                'saliva'           => 'Saliva',
                                'esputo'           => 'Esputo',
                                'liquido_cefalorraquideo' => 'LCR',
                                'biopsia'          => 'Biopsia',
                                'hisopado'         => 'Hisopado',
                                'otro'             => 'Otro',
                            ] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($examen['tipo_muestra'] ?? '') === $val ? 'selected' : '' ?>>
                                <?= $lbl ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="gl-label">Volumen muestra (mL)</label>
                        <input type="number" name="volumen_muestra_ml" class="gl-input"
                            placeholder="5" min="0" step="0.5"
                            value="<?= htmlspecialchars($examen['volumen_muestra_ml'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="gl-label">Contenedor</label>
                        <select name="contenedor" class="gl-input gl-select">
                            <option value="">— —</option>
                            <?php foreach ([
                                'tubo_rojo'      => 'Tubo rojo (suero)',
                                'tubo_lila'      => 'Tubo lila (EDTA)',
                                'tubo_verde'     => 'Tubo verde (heparina)',
                                'tubo_azul'      => 'Tubo azul (citrato)',
                                'tubo_gris'      => 'Tubo gris (glucólisis)',
                                'frasco_orina'   => 'Frasco orina',
                                'copa_heces'     => 'Copa heces',
                                'otro'           => 'Otro',
                            ] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($examen['contenedor'] ?? '') === $v ? 'selected' : '' ?>>
                                <?= $l ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Condiciones de conservación</label>
                        <input type="text" name="condiciones_conservacion" class="gl-input"
                            placeholder="Ej: Refrigeración 2–8°C, procesamiento en menos de 2h…"
                            value="<?= htmlspecialchars($examen['condiciones_conservacion'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna lateral -->
    <div class="col-lg-4">

        <!-- Precio y tiempo -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-tag" style="color:#10b981;font-size:1.05rem"></i>
                <h5>Precio y entrega</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="gl-label">Precio ($) <span style="color:#ef4444">*</span></label>
                        <input type="number" name="precio" class="gl-input" required
                            placeholder="0.00" min="0" step="0.01"
                            value="<?= htmlspecialchars($examen['precio'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <hr style="border-color:#f1f5f9;margin:.25rem 0 .75rem">
                        <p style="font-size:.78rem;color:#64748b;margin-bottom:.75rem">
                            Tiempo de entrega de resultados (indique <strong>uno</strong>):
                        </p>
                    </div>
                    <div class="col-6">
                        <label class="gl-label">En minutos</label>
                        <input type="number" name="tiempo_entrega_min" class="gl-input"
                            placeholder="Ej: 120" min="0"
                            value="<?= htmlspecialchars($examen['tiempo_entrega_min'] ?? '') ?>"
                            oninput="if(this.value) document.getElementById('tDias').value=''">
                    </div>
                    <div class="col-6">
                        <label class="gl-label">En días</label>
                        <input type="number" id="tDias" name="tiempo_entrega_dias" class="gl-input"
                            placeholder="Ej: 2" min="0"
                            value="<?= htmlspecialchars($examen['tiempo_entrega_dias'] ?? '') ?>"
                            oninput="if(this.value) document.querySelector('[name=tiempo_entrega_min]').value=''">
                    </div>
                </div>
            </div>
        </div>

        <!-- Opciones -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-toggles" style="color:var(--warning);font-size:1.05rem"></i>
                <h5>Opciones</h5>
            </div>
            <div class="gl-card-body">
                <div class="d-flex flex-column gap-3">
                    <?php foreach ([
                        ['requiere_ayuno',       'Requiere ayuno',          'El paciente debe estar en ayunas'],
                        ['activo',               'Examen activo',           'Disponible para cotizaciones y órdenes'],
                        ['requiere_medico',      'Requiere orden médica',   'Solo con prescripción médica'],
                        ['resultado_inmediato',  'Resultado inmediato',     'Disponible en pocos minutos (urgencias)'],
                    ] as [$field, $label, $desc]): ?>
                    <label style="display:flex;align-items:flex-start;gap:.75rem;cursor:pointer">
                        <div style="position:relative;flex-shrink:0;margin-top:2px">
                            <input type="checkbox" name="<?= $field ?>" value="1"
                                <?= !empty($examen[$field]) ? 'checked' : ($field === 'activo' ? 'checked' : '') ?>
                                style="width:18px;height:18px;accent-color:var(--primary)">
                        </div>
                        <div>
                            <div style="font-size:.855rem;font-weight:600;color:#374151"><?= $label ?></div>
                            <div style="font-size:.75rem;color:#94a3b8"><?= $desc ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="d-flex flex-column gap-2">
            <button type="submit" class="btn-gl btn-primary-gl" style="justify-content:center;padding:.7rem">
                <i class="bi bi-check-lg"></i> Guardar examen
            </button>
            <a href="/examenes" class="btn-gl btn-outline-gl" style="justify-content:center">
                Cancelar
            </a>
        </div>
    </div>
</div>
</form>

<script>
let codigoTimer;
function sugerirCodigo(nombre) {
    const campo = document.getElementById('codigo');
    if (campo.dataset.touched) return; // No sobreescribir si ya editó
    if (!nombre) return;
    const palabras = nombre.trim().split(/\s+/);
    const sig = palabras.map(p => p[0].toUpperCase()).join('').substring(0, 3);
    campo.value = sig + '-' + String(Math.floor(Math.random()*900)+100);
    validarCodigo(campo.value);
}

function validarCodigo(val) {
    clearTimeout(codigoTimer);
    document.getElementById('codigo').dataset.touched = '1';
    const status = document.getElementById('codigoStatus');
    if (!val || val.length < 2) { status.textContent = ''; return; }
    status.textContent = '⏳';
    status.style.color = '#94a3b8';
    codigoTimer = setTimeout(() => {
        fetch('/examenes/verificar-codigo?codigo=' + encodeURIComponent(val))
            .then(r => r.json()).then(d => {
                if (d.disponible) {
                    status.textContent = '✓ Disponible';
                    status.style.color = '#10b981';
                } else {
                    status.textContent = '✗ En uso';
                    status.style.color = '#ef4444';
                }
            }).catch(() => { status.textContent = ''; });
    }, 500);
}
document.getElementById('codigo').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>