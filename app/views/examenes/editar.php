<?php
// view: examenes/editar.php — Reutiliza la misma estructura de crear.php
// Variables: $examen, $categorias, $csrfToken, $flash
$pageTitle = 'Editar Examen';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/examenes" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">
            Editar: <?= htmlspecialchars($examen['nombre'] ?? '') ?>
        </h1>
        <div class="d-flex align-items-center gap-2 mt-1">
            <code style="font-size:.8rem;background:#f1f5f9;padding:.15rem .5rem;border-radius:.3rem;color:var(--primary)">
                <?= htmlspecialchars($examen['codigo'] ?? '') ?>
            </code>
            <span class="gl-badge <?= ($examen['activo'] ?? false) ? 'badge-success' : 'badge-gray' ?>">
                <?= ($examen['activo'] ?? false) ? 'Activo' : 'Inactivo' ?>
            </span>
        </div>
    </div>
    <?php if (\RBAC::puede('examenes.parametros')): ?>
    <a href="/examenes/parametros/<?= $examen['id_examen'] ?>" class="btn-gl btn-outline-gl ms-auto">
        <i class="bi bi-sliders"></i> Gestionar parámetros
    </a>
    <?php endif; ?>
</div>

<form method="POST" action="/examenes/actualizar/<?= $examen['id_examen'] ?>" id="formExamen" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

<div class="row g-4">
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
                            value="<?= htmlspecialchars($examen['nombre'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Código <span style="color:#ef4444">*</span>
                            <span id="codigoStatus" style="font-size:.72rem;margin-left:.4rem"></span>
                        </label>
                        <input type="text" name="codigo" id="codigo" class="gl-input" required
                            value="<?= htmlspecialchars($examen['codigo'] ?? '') ?>"
                            oninput="validarCodigo(this.value)" style="text-transform:uppercase">
                    </div>
                    <div class="col-md-6">
                        <label class="gl-label">Categoría <span style="color:#ef4444">*</span></label>
                        <select name="id_categoria" class="gl-input gl-select" required>
                            <option value="">— Seleccionar —</option>
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
                            value="<?= htmlspecialchars($examen['metodo_analisis'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Descripción</label>
                        <textarea name="descripcion" class="gl-input" rows="3"><?= htmlspecialchars($examen['descripcion'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Preparación del paciente</label>
                        <textarea name="preparacion_paciente" class="gl-input" rows="2"><?= htmlspecialchars($examen['preparacion_paciente'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Muestra -->
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
                                'sangre_venosa'         => 'Sangre venosa',
                                'sangre_capilar'        => 'Sangre capilar',
                                'orina'                 => 'Orina',
                                'heces'                 => 'Heces',
                                'saliva'                => 'Saliva',
                                'esputo'                => 'Esputo',
                                'liquido_cefalorraquideo' => 'LCR',
                                'biopsia'               => 'Biopsia',
                                'hisopado'              => 'Hisopado',
                                'otro'                  => 'Otro',
                            ] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($examen['tipo_muestra'] ?? '') === $val ? 'selected' : '' ?>>
                                <?= $lbl ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="gl-label">Volumen (mL)</label>
                        <input type="number" name="volumen_muestra_ml" class="gl-input"
                            placeholder="5" min="0" step="0.5"
                            value="<?= htmlspecialchars($examen['volumen_muestra_ml'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="gl-label">Contenedor</label>
                        <select name="contenedor" class="gl-input gl-select">
                            <option value="">— —</option>
                            <?php foreach ([
                                'tubo_rojo'    => 'Tubo rojo',
                                'tubo_lila'    => 'Tubo lila (EDTA)',
                                'tubo_verde'   => 'Tubo verde',
                                'tubo_azul'    => 'Tubo azul',
                                'tubo_gris'    => 'Tubo gris',
                                'frasco_orina' => 'Frasco orina',
                                'copa_heces'   => 'Copa heces',
                                'otro'         => 'Otro',
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
                            value="<?= htmlspecialchars($examen['condiciones_conservacion'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-tag" style="color:#10b981;font-size:1.05rem"></i>
                <h5>Precio y entrega</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="gl-label">Precio ($) <span style="color:#ef4444">*</span></label>
                        <input type="number" name="precio" class="gl-input" required min="0" step="0.01"
                            value="<?= htmlspecialchars($examen['precio'] ?? '0') ?>">
                    </div>
                    <div class="col-6">
                        <label class="gl-label">En minutos</label>
                        <input type="number" name="tiempo_entrega_min" class="gl-input" min="0"
                            value="<?= htmlspecialchars($examen['tiempo_entrega_min'] ?? '') ?>"
                            oninput="if(this.value) document.getElementById('tDias').value=''">
                    </div>
                    <div class="col-6">
                        <label class="gl-label">En días</label>
                        <input type="number" id="tDias" name="tiempo_entrega_dias" class="gl-input" min="0"
                            value="<?= htmlspecialchars($examen['tiempo_entrega_dias'] ?? '') ?>"
                            oninput="if(this.value) document.querySelector('[name=tiempo_entrega_min]').value=''">
                    </div>
                </div>
            </div>
        </div>

        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-toggles" style="color:var(--warning);font-size:1.05rem"></i>
                <h5>Opciones</h5>
            </div>
            <div class="gl-card-body">
                <div class="d-flex flex-column gap-3">
                    <?php foreach ([
                        ['requiere_ayuno',      'Requiere ayuno'],
                        ['activo',              'Examen activo'],
                        ['requiere_medico',     'Requiere orden médica'],
                        ['resultado_inmediato', 'Resultado inmediato'],
                    ] as [$field, $label]): ?>
                    <label style="display:flex;align-items:center;gap:.75rem;cursor:pointer">
                        <input type="checkbox" name="<?= $field ?>" value="1"
                            <?= !empty($examen[$field]) ? 'checked' : '' ?>
                            style="width:18px;height:18px;accent-color:var(--primary)">
                        <span style="font-size:.875rem;font-weight:500;color:#374151"><?= $label ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Metadatos -->
        <div class="gl-card mb-4" style="background:#f8fafc">
            <div class="gl-card-body" style="font-size:.78rem;color:#64748b">
                <div class="d-flex justify-content-between mb-1">
                    <span>Creado</span>
                    <span><?= !empty($examen['fecha_creacion']) ? date('d/m/Y', strtotime($examen['fecha_creacion'])) : '—' ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Modificado</span>
                    <span><?= !empty($examen['fecha_modificacion']) ? date('d/m/Y H:i', strtotime($examen['fecha_modificacion'])) : '—' ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>ID</span>
                    <span>#<?= $examen['id_examen'] ?? '—' ?></span>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column gap-2">
            <button type="submit" class="btn-gl btn-primary-gl" style="justify-content:center;padding:.7rem">
                <i class="bi bi-check-lg"></i> Guardar cambios
            </button>
            <a href="/examenes" class="btn-gl btn-outline-gl" style="justify-content:center">Cancelar</a>
        </div>
    </div>
</div>
</form>

<script>
const CODIGO_ORIGINAL = '<?= addslashes($examen['codigo'] ?? '') ?>';
let timer;
function validarCodigo(val) {
    clearTimeout(timer);
    this.value = (val || '').toUpperCase();
    const status = document.getElementById('codigoStatus');
    if (!val || val === CODIGO_ORIGINAL) { status.textContent = ''; return; }
    status.textContent = '⏳'; status.style.color = '#94a3b8';
    timer = setTimeout(() => {
        fetch('/examenes/verificar-codigo?codigo=' + encodeURIComponent(val) + '&exclude=<?= $examen['id_examen'] ?>')
            .then(r => r.json()).then(d => {
                status.textContent = d.disponible ? '✓ Disponible' : '✗ En uso';
                status.style.color  = d.disponible ? '#10b981' : '#ef4444';
            }).catch(() => { status.textContent = ''; });
    }, 500);
}
document.getElementById('codigo').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
    validarCodigo(this.value);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>