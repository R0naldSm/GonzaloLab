<?php
// view: examenes/parametros.php
// Variables: $examen, $parametros (array con valores_referencia anidados), $csrfToken, $flash
$pageTitle = 'Parámetros — ' . htmlspecialchars($examen['nombre'] ?? '');
require_once __DIR__ . '/../layouts/header.php';

// ── Plantillas clínicas predefinidas por tipo de examen ──────────────────────
// Basado en: UB Farmacia Práctica - Interpretación de pruebas analíticas
// y estándares internacionales (CLSI, OPS, IFCC)
$plantillas = [
    'hemograma' => [
        'nombre' => 'Hemograma Completo (CBC)',
        'icono'  => 'bi-droplet-fill',
        'color'  => '#ef4444',
        'parametros' => [
            ['nombre' => 'Hemoglobina', 'unidad' => 'g/dL', 'tipo' => 'numerico',
             'orden' => 1,
             'ref' => [
                 ['sexo' => 'M', 'edad_min' => 18, 'ref_min' => 13.5, 'ref_max' => 17.5, 'crit_min' => 7.0,  'crit_max' => 20.0, 'desc' => 'Hombre adulto'],
                 ['sexo' => 'F', 'edad_min' => 18, 'ref_min' => 12.0, 'ref_max' => 16.0, 'crit_min' => 7.0,  'crit_max' => 20.0, 'desc' => 'Mujer adulta'],
             ]],
            ['nombre' => 'Hematocrito', 'unidad' => '%', 'tipo' => 'numerico',
             'orden' => 2,
             'ref' => [
                 ['sexo' => 'M', 'edad_min' => 18, 'ref_min' => 41,   'ref_max' => 53,   'crit_min' => 21,   'crit_max' => 60,   'desc' => 'Hombre adulto'],
                 ['sexo' => 'F', 'edad_min' => 18, 'ref_min' => 36,   'ref_max' => 46,   'crit_min' => 21,   'crit_max' => 60,   'desc' => 'Mujer adulta'],
             ]],
            ['nombre' => 'Eritrocitos (GR)', 'unidad' => '×10⁶/µL', 'tipo' => 'numerico',
             'orden' => 3,
             'ref' => [
                 ['sexo' => 'M', 'edad_min' => 18, 'ref_min' => 4.5,  'ref_max' => 5.9,  'crit_min' => 2.0,  'crit_max' => 7.0,  'desc' => 'Hombre adulto'],
                 ['sexo' => 'F', 'edad_min' => 18, 'ref_min' => 4.0,  'ref_max' => 5.2,  'crit_min' => 2.0,  'crit_max' => 7.0,  'desc' => 'Mujer adulta'],
             ]],
            ['nombre' => 'VCM', 'unidad' => 'fL', 'tipo' => 'numerico',
             'orden' => 4,
             'ref' => [['sexo' => 'A', 'ref_min' => 80, 'ref_max' => 100, 'crit_min' => 50, 'crit_max' => 130, 'desc' => 'Ambos sexos']]],
            ['nombre' => 'HCM', 'unidad' => 'pg', 'tipo' => 'numerico',
             'orden' => 5,
             'ref' => [['sexo' => 'A', 'ref_min' => 27,  'ref_max' => 33,  'crit_min' => null, 'crit_max' => null, 'desc' => 'Ambos sexos']]],
            ['nombre' => 'CHCM', 'unidad' => 'g/dL', 'tipo' => 'numerico',
             'orden' => 6,
             'ref' => [['sexo' => 'A', 'ref_min' => 31.5,'ref_max' => 36,  'crit_min' => null, 'crit_max' => null, 'desc' => 'Ambos sexos']]],
            ['nombre' => 'Leucocitos (GB)', 'unidad' => '×10³/µL', 'tipo' => 'numerico',
             'orden' => 7,
             'ref' => [['sexo' => 'A', 'ref_min' => 4.5, 'ref_max' => 11.0,'crit_min' => 2.0,  'crit_max' => 30.0, 'desc' => 'Adultos']]],
            ['nombre' => 'Neutrófilos %', 'unidad' => '%', 'tipo' => 'numerico',
             'orden' => 8,
             'ref' => [['sexo' => 'A', 'ref_min' => 50,  'ref_max' => 70,  'crit_min' => null, 'crit_max' => null, 'desc' => 'Adultos']]],
            ['nombre' => 'Linfocitos %', 'unidad' => '%', 'tipo' => 'numerico',
             'orden' => 9,
             'ref' => [['sexo' => 'A', 'ref_min' => 20,  'ref_max' => 40,  'crit_min' => null, 'crit_max' => null, 'desc' => 'Adultos']]],
            ['nombre' => 'Plaquetas', 'unidad' => '×10³/µL', 'tipo' => 'numerico',
             'orden' => 10,
             'ref' => [['sexo' => 'A', 'ref_min' => 150, 'ref_max' => 400, 'crit_min' => 50,   'crit_max' => 1000, 'desc' => 'Adultos']]],
        ]
    ],
    'quimica_basica' => [
        'nombre' => 'Química Básica (Perfil Metabólico)',
        'icono'  => 'bi-activity',
        'color'  => '#3b82f6',
        'parametros' => [
            ['nombre' => 'Glucosa', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 1,
             'ref' => [['sexo' => 'A', 'ref_min' => 70, 'ref_max' => 110, 'crit_min' => 40, 'crit_max' => 500, 'desc' => 'Ayuno 8h — adultos']]],
            ['nombre' => 'Urea', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 2,
             'ref' => [['sexo' => 'A', 'ref_min' => 15, 'ref_max' => 45, 'crit_min' => null, 'crit_max' => 200, 'desc' => 'Adultos']]],
            ['nombre' => 'Creatinina', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 3,
             'ref' => [
                 ['sexo' => 'M', 'edad_min' => 18, 'ref_min' => 0.7, 'ref_max' => 1.2, 'crit_min' => null, 'crit_max' => 10.0, 'desc' => 'Hombre adulto'],
                 ['sexo' => 'F', 'edad_min' => 18, 'ref_min' => 0.5, 'ref_max' => 1.1, 'crit_min' => null, 'crit_max' => 10.0, 'desc' => 'Mujer adulta'],
             ]],
            ['nombre' => 'Ácido Úrico', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 4,
             'ref' => [
                 ['sexo' => 'M', 'ref_min' => 3.5, 'ref_max' => 7.2, 'crit_min' => null, 'crit_max' => 12.0, 'desc' => 'Hombre adulto'],
                 ['sexo' => 'F', 'ref_min' => 2.6, 'ref_max' => 6.0, 'crit_min' => null, 'crit_max' => 12.0, 'desc' => 'Mujer adulta'],
             ]],
            ['nombre' => 'Proteínas Totales', 'unidad' => 'g/dL', 'tipo' => 'numerico', 'orden' => 5,
             'ref' => [['sexo' => 'A', 'ref_min' => 6.0, 'ref_max' => 8.0, 'crit_min' => null, 'crit_max' => null, 'desc' => 'Adultos']]],
            ['nombre' => 'Albúmina', 'unidad' => 'g/dL', 'tipo' => 'numerico', 'orden' => 6,
             'ref' => [['sexo' => 'A', 'ref_min' => 3.4, 'ref_max' => 4.8, 'crit_min' => 2.0, 'crit_max' => null, 'desc' => 'Adultos']]],
        ]
    ],
    'lipidos' => [
        'nombre' => 'Perfil Lipídico',
        'icono'  => 'bi-heart-pulse',
        'color'  => '#f59e0b',
        'parametros' => [
            ['nombre' => 'Colesterol Total', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 1,
             'ref' => [['sexo' => 'A', 'ref_min' => null, 'ref_max' => 200, 'crit_min' => null, 'crit_max' => null,
                        'desc' => 'Deseable <200; Límite: 200-239; Alto ≥240']]],
            ['nombre' => 'LDL-Colesterol', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 2,
             'ref' => [['sexo' => 'A', 'ref_min' => null, 'ref_max' => 130, 'crit_min' => null, 'crit_max' => null,
                        'desc' => 'Óptimo <100; Cerca/óptimo: 100-129; Límite: 130-159; Alto ≥160']]],
            ['nombre' => 'HDL-Colesterol', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 3,
             'ref' => [
                 ['sexo' => 'M', 'ref_min' => 40,  'ref_max' => null, 'crit_min' => null, 'crit_max' => null, 'desc' => 'Hombre — riesgo si <40'],
                 ['sexo' => 'F', 'ref_min' => 50,  'ref_max' => null, 'crit_min' => null, 'crit_max' => null, 'desc' => 'Mujer — riesgo si <50'],
             ]],
            ['nombre' => 'Triglicéridos', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 4,
             'ref' => [['sexo' => 'A', 'ref_min' => null, 'ref_max' => 150, 'crit_min' => null, 'crit_max' => 500,
                        'desc' => 'Normal <150; Límite: 150-199; Alto: 200-499; Muy alto ≥500']]],
            ['nombre' => 'VLDL-Colesterol', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 5,
             'ref' => [['sexo' => 'A', 'ref_min' => 5, 'ref_max' => 40, 'crit_min' => null, 'crit_max' => null, 'desc' => 'Calculado: Trig/5']]],
        ]
    ],
    'hepatico' => [
        'nombre' => 'Perfil Hepático',
        'icono'  => 'bi-lungs',
        'color'  => '#10b981',
        'parametros' => [
            ['nombre' => 'AST (GOT)', 'unidad' => 'U/L', 'tipo' => 'numerico', 'orden' => 1,
             'ref' => [['sexo' => 'A', 'ref_min' => 5, 'ref_max' => 40, 'crit_min' => null, 'crit_max' => 1000, 'desc' => 'Adultos']]],
            ['nombre' => 'ALT (GPT)', 'unidad' => 'U/L', 'tipo' => 'numerico', 'orden' => 2,
             'ref' => [['sexo' => 'A', 'ref_min' => 7, 'ref_max' => 40, 'crit_min' => null, 'crit_max' => 1000, 'desc' => 'Adultos']]],
            ['nombre' => 'GGT', 'unidad' => 'U/L', 'tipo' => 'numerico', 'orden' => 3,
             'ref' => [
                 ['sexo' => 'M', 'ref_min' => 10, 'ref_max' => 71, 'crit_min' => null, 'crit_max' => null, 'desc' => 'Hombre adulto'],
                 ['sexo' => 'F', 'ref_min' => 6,  'ref_max' => 42, 'crit_min' => null, 'crit_max' => null, 'desc' => 'Mujer adulta'],
             ]],
            ['nombre' => 'Fosfatasa Alcalina', 'unidad' => 'U/L', 'tipo' => 'numerico', 'orden' => 4,
             'ref' => [['sexo' => 'A', 'ref_min' => 30, 'ref_max' => 120, 'crit_min' => null, 'crit_max' => null, 'desc' => 'Adultos (varía con edad)']]],
            ['nombre' => 'Bilirrubina Total', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 5,
             'ref' => [['sexo' => 'A', 'ref_min' => 0.2, 'ref_max' => 1.2, 'crit_min' => null, 'crit_max' => 15.0, 'desc' => 'Adultos']]],
            ['nombre' => 'Bilirrubina Directa', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 6,
             'ref' => [['sexo' => 'A', 'ref_min' => 0.0, 'ref_max' => 0.4, 'crit_min' => null, 'crit_max' => null, 'desc' => 'Adultos']]],
        ]
    ],
    'renal' => [
        'nombre' => 'Perfil Renal',
        'icono'  => 'bi-clipboard2-pulse',
        'color'  => '#8b5cf6',
        'parametros' => [
            ['nombre' => 'Sodio (Na⁺)', 'unidad' => 'mEq/L', 'tipo' => 'numerico', 'orden' => 1,
             'ref' => [['sexo' => 'A', 'ref_min' => 136, 'ref_max' => 145, 'crit_min' => 120, 'crit_max' => 160, 'desc' => 'Adultos']]],
            ['nombre' => 'Potasio (K⁺)', 'unidad' => 'mEq/L', 'tipo' => 'numerico', 'orden' => 2,
             'ref' => [['sexo' => 'A', 'ref_min' => 3.5, 'ref_max' => 5.0, 'crit_min' => 2.5, 'crit_max' => 6.5, 'desc' => 'Adultos — CRÍTICO arritmias']]],
            ['nombre' => 'Cloro (Cl⁻)', 'unidad' => 'mEq/L', 'tipo' => 'numerico', 'orden' => 3,
             'ref' => [['sexo' => 'A', 'ref_min' => 96, 'ref_max' => 106, 'crit_min' => 80, 'crit_max' => 115, 'desc' => 'Adultos']]],
            ['nombre' => 'Calcio Total', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 4,
             'ref' => [['sexo' => 'A', 'ref_min' => 8.5, 'ref_max' => 10.5, 'crit_min' => 6.0, 'crit_max' => 13.0, 'desc' => 'Adultos']]],
            ['nombre' => 'Fósforo', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 5,
             'ref' => [['sexo' => 'A', 'ref_min' => 2.5, 'ref_max' => 4.5, 'crit_min' => 1.0, 'crit_max' => 8.0, 'desc' => 'Adultos']]],
            ['nombre' => 'Creatinina sérica', 'unidad' => 'mg/dL', 'tipo' => 'numerico', 'orden' => 6,
             'ref' => [
                 ['sexo' => 'M', 'ref_min' => 0.7, 'ref_max' => 1.2, 'crit_min' => null, 'crit_max' => 10.0, 'desc' => 'Hombre adulto'],
                 ['sexo' => 'F', 'ref_min' => 0.5, 'ref_max' => 1.1, 'crit_min' => null, 'crit_max' => 10.0, 'desc' => 'Mujer adulta'],
             ]],
        ]
    ],
    'tiroides' => [
        'nombre' => 'Función Tiroidea',
        'icono'  => 'bi-tsunami',
        'color'  => '#06b6d4',
        'parametros' => [
            ['nombre' => 'TSH', 'unidad' => 'µUI/mL', 'tipo' => 'numerico', 'orden' => 1,
             'ref' => [['sexo' => 'A', 'ref_min' => 0.4, 'ref_max' => 4.0, 'crit_min' => 0.01, 'crit_max' => 25.0, 'desc' => 'Adultos eutiroideos']]],
            ['nombre' => 'T4 Libre (FT4)', 'unidad' => 'ng/dL', 'tipo' => 'numerico', 'orden' => 2,
             'ref' => [['sexo' => 'A', 'ref_min' => 0.8, 'ref_max' => 1.8, 'crit_min' => null, 'crit_max' => null, 'desc' => 'Adultos']]],
            ['nombre' => 'T3 Total', 'unidad' => 'ng/dL', 'tipo' => 'numerico', 'orden' => 3,
             'ref' => [['sexo' => 'A', 'ref_min' => 80, 'ref_max' => 200, 'crit_min' => null, 'crit_max' => null, 'desc' => 'Adultos']]],
        ]
    ],
];
?>

<!-- Breadcrumb -->
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="/examenes" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div class="flex-fill">
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">
            Parámetros y Rangos de Referencia
        </h1>
        <div class="d-flex align-items-center gap-2 mt-1">
            <span class="fw-semibold" style="color:#64748b"><?= htmlspecialchars($examen['nombre'] ?? '') ?></span>
            <code style="font-size:.75rem;background:#f1f5f9;padding:.1rem .45rem;border-radius:.3rem;color:var(--primary)"><?= htmlspecialchars($examen['codigo'] ?? '') ?></code>
        </div>
    </div>
    <!-- Plantillas clínicas -->
    <div class="dropdown">
        <button class="btn-gl btn-outline-gl" data-bs-toggle="dropdown">
            <i class="bi bi-magic"></i> Cargar plantilla clínica
            <i class="bi bi-chevron-down ms-1" style="font-size:.75rem"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="border-radius:.75rem;border:1px solid #e2e8f0;box-shadow:0 10px 40px rgba(0,0,0,.1);min-width:260px">
            <?php foreach ($plantillas as $key => $plantilla): ?>
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="#"
                    onclick="cargarPlantilla('<?= $key ?>');return false">
                    <i class="bi <?= $plantilla['icono'] ?>" style="color:<?= $plantilla['color'] ?>;font-size:1rem;width:20px"></i>
                    <span style="font-size:.855rem"><?= $plantilla['nombre'] ?></span>
                </a>
            </li>
            <?php endforeach; ?>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item text-muted" href="#" onclick="limpiarTodo();return false" style="font-size:.82rem">
                <i class="bi bi-trash me-2"></i>Limpiar todos los parámetros
            </a></li>
        </ul>
    </div>
</div>

<!-- Ayuda clínica colapsable -->
<div class="gl-card mb-4" id="helpPanel" style="border-left:3px solid var(--primary)">
    <div class="gl-card-header" style="cursor:pointer" onclick="toggleHelp()">
        <i class="bi bi-info-circle-fill" style="color:var(--primary)"></i>
        <h5 style="color:var(--primary)">Guía clínica de valores de referencia</h5>
        <i class="bi bi-chevron-down ms-auto" id="helpChevron" style="font-size:.8rem;color:#94a3b8;transition:.3s"></i>
    </div>
    <div id="helpBody" class="gl-card-body" style="display:none">
        <div class="row g-3" style="font-size:.83rem;color:#374151">
            <div class="col-md-4">
                <div style="background:#fef2f2;border-radius:.5rem;padding:.75rem">
                    <p class="fw-bold mb-2" style="color:#dc2626"><i class="bi bi-exclamation-triangle-fill me-1"></i>Valores críticos</p>
                    <p class="mb-1">Son límites donde se requiere <strong>acción inmediata</strong>. El sistema enviará alertas automáticas al analista y médico.</p>
                    <p class="mb-0" style="color:#64748b">Ej: Potasio &lt;2.5 mEq/L → riesgo de paro cardíaco</p>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:#f0fdf4;border-radius:.5rem;padding:.75rem">
                    <p class="fw-bold mb-2" style="color:#059669"><i class="bi bi-check-circle-fill me-1"></i>Valores normales</p>
                    <p class="mb-1">Rango de referencia poblacional. Varía por <strong>sexo, edad y método analítico</strong>.</p>
                    <p class="mb-0" style="color:#64748b">Puede definir rangos separados por grupo demográfico con el botón "+".</p>
                </div>
            </div>
            <div class="col-md-4">
                <div style="background:#eff6ff;border-radius:.5rem;padding:.75rem">
                    <p class="fw-bold mb-2" style="color:#1d4ed8"><i class="bi bi-rulers me-1"></i>Unidades SI vs Conv.</p>
                    <p class="mb-1">Factores clave de conversión (multiplicar para pasar a mg/dL):</p>
                    <ul style="list-style:none;padding:0;margin:0;color:#374151">
                        <li>Glucosa: ×18 (mmol/L→mg/dL)</li>
                        <li>Colesterol: ×38.7</li>
                        <li>Creatinina: ×0.011 (µmol/L→mg/dL)</li>
                        <li>Bilirrubina: ×0.058</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     FORMULARIO PRINCIPAL
     ═══════════════════════════════════════════════════════ -->
<form method="POST" action="/examenes/guardar-parametros/<?= $examen['id_examen'] ?>" id="formParametros">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

<div id="listaParametros">
<!-- Los parámetros se renderizan desde PHP ($parametros) o se agregan dinámicamente con JS -->
<?php foreach ($parametros ?? [] as $pi => $param): ?>
<?php include __DIR__ . '/partials/_fila_parametro.php'; // Se usa include dinámico ?>
<?php endforeach; ?>
</div>

<!-- ── Botón agregar parámetro ── -->
<button type="button" class="btn-gl btn-outline-gl mt-3" onclick="agregarParametro()" id="btnAgregarParam">
    <i class="bi bi-plus-lg"></i> Agregar parámetro
</button>

<!-- ── Botones guardar ── -->
<div class="d-flex justify-content-between align-items-center mt-4 pt-4" style="border-top:1px solid #f1f5f9">
    <a href="/examenes" class="btn-gl btn-outline-gl">
        <i class="bi bi-x-lg"></i> Cancelar
    </a>
    <div class="d-flex gap-2">
        <span id="saveStatus" style="font-size:.8rem;color:#94a3b8;align-self:center"></span>
        <button type="submit" class="btn-gl btn-primary-gl" style="padding:.7rem 1.5rem">
            <i class="bi bi-check-lg"></i> Guardar parámetros
        </button>
    </div>
</div>

</form>

<!-- ═══════════════════════════════════════════════════════
     TEMPLATE INVISIBLE — se clona con JS
     ═══════════════════════════════════════════════════════ -->
<template id="tmplParametro">
<div class="param-bloque gl-card mb-3" data-idx="__IDX__">
    <div class="gl-card-header" style="background:#f8fafc;cursor:pointer" onclick="toggleParam(this)">
        <span class="param-numero gl-badge badge-info" style="font-size:.7rem;min-width:24px;text-align:center">__NUM__</span>
        <input type="text" name="parametros[__IDX__][nombre_parametro]" class="param-nombre-input"
            placeholder="Nombre del parámetro (ej: Hemoglobina)"
            style="flex:1;border:none;background:transparent;font-size:.9rem;font-weight:600;color:#0f172a;outline:none;font-family:var(--font)"
            oninput="syncNombreHeader(this)" required>
        <div class="ms-auto d-flex gap-2 align-items-center">
            <span class="param-unidad-badge gl-badge badge-gray" style="font-size:.7rem"></span>
            <button type="button" class="btn-gl btn-outline-gl btn-sm-gl" title="Mover arriba"
                onclick="moverParam(this,-1);event.stopPropagation()"><i class="bi bi-arrow-up"></i></button>
            <button type="button" class="btn-gl btn-outline-gl btn-sm-gl" title="Mover abajo"
                onclick="moverParam(this,1);event.stopPropagation()"><i class="bi bi-arrow-down"></i></button>
            <button type="button" class="btn-gl btn-danger-gl btn-sm-gl" title="Eliminar parámetro"
                onclick="eliminarParam(this);event.stopPropagation()"><i class="bi bi-trash"></i></button>
        </div>
    </div>
    <div class="param-body gl-card-body" style="display:block">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="gl-label">Unidad de medida <span style="color:#ef4444">*</span></label>
                <input type="text" name="parametros[__IDX__][unidad_medida]" class="gl-input param-unidad"
                    placeholder="g/dL, mg/dL, U/L…" oninput="syncUnidad(this)" required>
            </div>
            <div class="col-md-3">
                <label class="gl-label">Tipo de dato</label>
                <select name="parametros[__IDX__][tipo_dato]" class="gl-input gl-select">
                    <option value="numerico">Numérico</option>
                    <option value="texto">Texto</option>
                    <option value="positivo_negativo">Pos/Neg</option>
                    <option value="porcentaje">Porcentaje</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="gl-label">Orden visualiz.</label>
                <input type="number" name="parametros[__IDX__][orden_visualizacion]" class="gl-input"
                    value="__NUM__" min="1">
            </div>
            <div class="col-md-4">
                <label class="gl-label">Observación clínica</label>
                <input type="text" name="parametros[__IDX__][observacion]" class="gl-input"
                    placeholder="Nota interna (opcional)">
            </div>
        </div>

        <!-- Rangos de referencia -->
        <div style="background:#f8fafc;border-radius:.625rem;padding:1rem">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <p style="font-size:.82rem;font-weight:700;color:#374151;margin:0">
                    <i class="bi bi-rulers me-1" style="color:var(--primary)"></i>Rangos de referencia
                </p>
                <button type="button" class="btn-gl btn-outline-gl btn-sm-gl"
                    onclick="agregarRango(this)" style="font-size:.75rem">
                    <i class="bi bi-plus"></i> Agregar rango
                </button>
            </div>
            <div class="rangos-container">
            <!-- Los rangos de referencia se agregan aquí -->
            </div>
        </div>

        <input type="hidden" name="parametros[__IDX__][eliminado]" value="0">
    </div>
</div>
</template>

<!-- Template fila de rango -->
<template id="tmplRango">
<div class="rango-fila mb-2" style="background:#fff;border:1px solid #e2e8f0;border-radius:.5rem;padding:.75rem">
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="gl-label" style="font-size:.7rem">Sexo</label>
            <select name="parametros[__PIDX__][rangos][__RIDX__][sexo]" class="gl-input gl-select" style="font-size:.78rem;padding:.4rem .6rem">
                <option value="A">Ambos</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="gl-label" style="font-size:.7rem">Edad mín</label>
            <input type="number" name="parametros[__PIDX__][rangos][__RIDX__][edad_min]" class="gl-input"
                style="font-size:.78rem;padding:.4rem .6rem" placeholder="0" min="0" max="120">
        </div>
        <div class="col-md-2">
            <label class="gl-label" style="font-size:.7rem">Edad máx</label>
            <input type="number" name="parametros[__PIDX__][rangos][__RIDX__][edad_max]" class="gl-input"
                style="font-size:.78rem;padding:.4rem .6rem" placeholder="∞" min="0" max="120">
        </div>
        <div class="col-md-1 text-center" style="padding-top:1.5rem">
            <span style="font-size:.7rem;color:#94a3b8;font-weight:700">NORMAL</span>
        </div>
        <div class="col-md-1">
            <label class="gl-label" style="font-size:.7rem">Mín</label>
            <input type="number" name="parametros[__PIDX__][rangos][__RIDX__][valor_min_normal]" class="gl-input"
                style="font-size:.78rem;padding:.4rem .6rem;border-color:#10b981" step="any" placeholder="—">
        </div>
        <div class="col-md-1">
            <label class="gl-label" style="font-size:.7rem">Máx</label>
            <input type="number" name="parametros[__PIDX__][rangos][__RIDX__][valor_max_normal]" class="gl-input"
                style="font-size:.78rem;padding:.4rem .6rem;border-color:#10b981" step="any" placeholder="—">
        </div>
        <div class="col-md-1 text-center" style="padding-top:1.5rem">
            <span style="font-size:.7rem;color:#ef4444;font-weight:700">CRÍTICO</span>
        </div>
        <div class="col-md-1">
            <label class="gl-label" style="font-size:.7rem">Mín ⚠</label>
            <input type="number" name="parametros[__PIDX__][rangos][__RIDX__][valor_min_critico]" class="gl-input"
                style="font-size:.78rem;padding:.4rem .6rem;border-color:#fca5a5" step="any" placeholder="—">
        </div>
        <div class="col-md-1">
            <label class="gl-label" style="font-size:.7rem">Máx ⚠</label>
            <input type="number" name="parametros[__PIDX__][rangos][__RIDX__][valor_max_critico]" class="gl-input"
                style="font-size:.78rem;padding:.4rem .6rem;border-color:#fca5a5" step="any" placeholder="—">
        </div>
        <div class="col-12">
            <input type="text" name="parametros[__PIDX__][rangos][__RIDX__][descripcion_rango]" class="gl-input"
                style="font-size:.75rem" placeholder="Descripción del rango (ej: Hombre adulto sano, ayuno 8h…)">
        </div>
        <div class="col-12 text-end">
            <button type="button" class="btn-gl btn-danger-gl btn-sm-gl" onclick="eliminarRango(this)" style="font-size:.75rem">
                <i class="bi bi-trash"></i> Quitar rango
            </button>
        </div>
    </div>
</div>
</template>

<!-- JS ──────────────────────────────────────────────────── -->
<script>
const PLANTILLAS = <?= json_encode($plantillas) ?>;
let paramCount = <?= count($parametros ?? []) ?>;

// ── INICIALIZAR PARÁMETROS EXISTENTES ──
<?php if (!empty($parametros)): ?>
document.addEventListener('DOMContentLoaded', () => {
    // Renderizar parámetros que vienen del servidor PHP
    <?php foreach ($parametros as $pi => $param): ?>
    renderParamFromPHP(<?= $pi ?>, <?= json_encode($param) ?>);
    <?php endforeach; ?>
});
<?php endif; ?>

function renderParamFromPHP(idx, param) {
    const bloque = crearBloqueParam(idx);
    bloque.querySelector('.param-nombre-input').value = param.nombre_parametro || '';
    bloque.querySelector('[name$="[unidad_medida]"]').value = param.unidad_medida || '';
    bloque.querySelector('[name$="[tipo_dato]"]').value = param.tipo_dato || 'numerico';
    bloque.querySelector('[name$="[orden_visualizacion]"]').value = param.orden_visualizacion || (idx+1);
    bloque.querySelector('[name$="[observacion]"]').value = param.observacion || '';
    syncUnidad(bloque.querySelector('.param-unidad'));

    (param.valores_referencia || []).forEach((r, ri) => {
        const rfila = agregarRangoBloque(bloque, idx, ri);
        setRangoValues(rfila, idx, ri, r);
    });
    document.getElementById('listaParametros').appendChild(bloque);
    renumerarParametros();
}

function crearBloqueParam(idx) {
    const tmpl = document.getElementById('tmplParametro').content.cloneNode(true);
    const bloque = tmpl.querySelector('.param-bloque');
    const html = bloque.outerHTML.replaceAll('__IDX__', idx).replaceAll('__NUM__', idx+1);
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.firstElementChild;
}

function agregarParametro() {
    const idx = paramCount++;
    const bloque = crearBloqueParam(idx);
    document.getElementById('listaParametros').appendChild(bloque);
    agregarRangoBloque(bloque, idx, 0); // Agregar un rango vacío por defecto
    bloque.querySelector('.param-nombre-input').focus();
    renumerarParametros();
}

function agregarRango(btn) {
    const bloque = btn.closest('.param-bloque');
    const idx = parseInt(bloque.dataset.idx);
    const container = bloque.querySelector('.rangos-container');
    const ri = container.querySelectorAll('.rango-fila').length;
    agregarRangoBloque(bloque, idx, ri);
}

function agregarRangoBloque(bloque, pidx, ridx) {
    const tmpl = document.getElementById('tmplRango').content.cloneNode(true);
    let html = tmpl.querySelector('.rango-fila').outerHTML
        .replaceAll('__PIDX__', pidx).replaceAll('__RIDX__', ridx);
    const div = document.createElement('div');
    div.innerHTML = html;
    const rfila = div.firstElementChild;
    bloque.querySelector('.rangos-container').appendChild(rfila);
    return rfila;
}

function setRangoValues(rfila, pidx, ridx, r) {
    const set = (suffix, val) => {
        const el = rfila.querySelector('[name="parametros['+pidx+'][rangos]['+ridx+']['+suffix+']"]');
        if (el && val !== null && val !== undefined) el.value = val;
    };
    set('sexo', r.sexo || 'A');
    set('edad_min', r.edad_min || '');
    set('edad_max', r.edad_max || '');
    set('valor_min_normal', r.valor_min_normal ?? r.ref_min ?? '');
    set('valor_max_normal', r.valor_max_normal ?? r.ref_max ?? '');
    set('valor_min_critico', r.valor_min_critico ?? r.crit_min ?? '');
    set('valor_max_critico', r.valor_max_critico ?? r.crit_max ?? '');
    set('descripcion_rango', r.descripcion_rango ?? r.desc ?? '');
}

function eliminarRango(btn) {
    if (!confirm('¿Quitar este rango de referencia?')) return;
    btn.closest('.rango-fila').remove();
}

function eliminarParam(btn) {
    if (!confirm('¿Eliminar este parámetro y todos sus rangos?')) return;
    const bloque = btn.closest('.param-bloque');
    // Marcar como eliminado en lugar de quitar del DOM (para BD)
    const hiddenElim = bloque.querySelector('[name$="[eliminado]"]');
    if (hiddenElim) { hiddenElim.value = '1'; bloque.style.display = 'none'; }
    else bloque.remove();
    renumerarParametros();
}

function moverParam(btn, dir) {
    const bloque = btn.closest('.param-bloque');
    const lista  = document.getElementById('listaParametros');
    if (dir < 0 && bloque.previousElementSibling) {
        lista.insertBefore(bloque, bloque.previousElementSibling);
    } else if (dir > 0 && bloque.nextElementSibling) {
        lista.insertBefore(bloque.nextElementSibling, bloque);
    }
    renumerarParametros();
}

function renumerarParametros() {
    document.querySelectorAll('.param-bloque:not([style*="display: none"])').forEach((b, i) => {
        const numEl = b.querySelector('.param-numero');
        if (numEl) numEl.textContent = i + 1;
        const ordenEl = b.querySelector('[name$="[orden_visualizacion]"]');
        if (ordenEl) ordenEl.value = i + 1;
    });
}

function toggleParam(header) {
    const body = header.nextElementSibling;
    body.style.display = body.style.display === 'none' ? 'block' : 'none';
}

function syncNombreHeader(inp) { /* nombre ya visible en el input mismo */ }

function syncUnidad(inp) {
    const bloque = inp.closest('.param-bloque');
    const badge  = bloque?.querySelector('.param-unidad-badge');
    if (badge) badge.textContent = inp.value || '';
}

// ── CARGAR PLANTILLA CLÍNICA ──────────────────────────────
function cargarPlantilla(key) {
    const pl = PLANTILLAS[key];
    if (!pl) return;
    if (document.querySelectorAll('.param-bloque').length > 0) {
        if (!confirm('¿Reemplazar los parámetros actuales con la plantilla "' + pl.nombre + '"?')) return;
    }
    // Limpiar lista
    document.getElementById('listaParametros').innerHTML = '';
    paramCount = 0;

    pl.parametros.forEach((p, pi) => {
        const idx   = paramCount++;
        const bloque = crearBloqueParam(idx);
        bloque.querySelector('.param-nombre-input').value = p.nombre;
        const unidEl = bloque.querySelector('[name$="[unidad_medida]"]');
        if (unidEl) { unidEl.value = p.unidad; syncUnidad(unidEl); }
        const tipoEl = bloque.querySelector('[name$="[tipo_dato]"]');
        if (tipoEl) tipoEl.value = p.tipo || 'numerico';
        const ordenEl = bloque.querySelector('[name$="[orden_visualizacion]"]');
        if (ordenEl) ordenEl.value = p.orden || (pi + 1);

        document.getElementById('listaParametros').appendChild(bloque);

        (p.ref || []).forEach((r, ri) => {
            const rfila = agregarRangoBloque(bloque, idx, ri);
            setRangoValues(rfila, idx, ri, r);
        });
    });

    renumerarParametros();
    document.getElementById('saveStatus').textContent = '✓ Plantilla "' + pl.nombre + '" cargada';
    document.getElementById('saveStatus').style.color = '#10b981';
    setTimeout(() => document.getElementById('saveStatus').textContent = '', 4000);
}

function limpiarTodo() {
    if (!confirm('¿Eliminar todos los parámetros actuales?')) return;
    document.getElementById('listaParametros').innerHTML = '';
    paramCount = 0;
}

function toggleHelp() {
    const body    = document.getElementById('helpBody');
    const chevron = document.getElementById('helpChevron');
    const shown   = body.style.display !== 'none';
    body.style.display = shown ? 'none' : 'block';
    chevron.style.transform = shown ? 'rotate(0deg)' : 'rotate(180deg)';
}

// ── GUARDAR EN MODO BORRADOR CADA 60s ───────────────────
document.getElementById('formParametros').addEventListener('input', () => {
    clearTimeout(window._draftTimer);
    const status = document.getElementById('saveStatus');
    status.textContent = 'Sin guardar…'; status.style.color = '#f59e0b';
    window._draftTimer = setTimeout(() => {
        status.textContent = ''; 
    }, 3000);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>