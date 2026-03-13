<?php
// Variables: $preview (array|null), $erroresIm (array), $menuNav, $nombreUsuario, $csrfToken, $flash
$pageTitle   = 'Importar Resultados CSV';
$breadcrumbs = [['label'=>'Resultados','url'=>'/resultados'],['label'=>'Importar CSV']];
require_once __DIR__ . '/../layouts/header.php';

$tienePreview = !empty($preview);
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/resultados" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">Importar Resultados — CSV</h1>
        <p style="font-size:.82rem;color:#64748b;margin:0">Carga masiva desde archivo de equipo de laboratorio</p>
    </div>
</div>

<!-- Errores del procesamiento anterior -->
<?php if (!empty($erroresIm)): ?>
<div class="gl-alert gl-alert-error mb-4" style="align-items:flex-start">
    <i class="bi bi-x-circle-fill" style="flex-shrink:0;margin-top:.1rem"></i>
    <div>
        <strong>Se encontraron <?= count($erroresIm) ?> error(es) en el archivo:</strong>
        <ul style="margin:.5rem 0 0;padding-left:1.25rem;font-size:.82rem">
            <?php foreach (array_slice($erroresIm, 0, 10) as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
            <?php if (count($erroresIm) > 10): ?>
            <li style="color:#94a3b8">… y <?= count($erroresIm) - 10 ?> más.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">

        <?php if (!$tienePreview): ?>
        <!-- ── PASO 1: Subir archivo ── -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <div style="width:32px;height:32px;border-radius:.5rem;background:#eff6ff;display:flex;align-items:center;justify-content:center">
                    <span style="font-size:.8rem;font-weight:800;color:var(--secondary)">1</span>
                </div>
                <h5>Seleccionar archivo CSV</h5>
            </div>
            <div class="gl-card-body">
                <form method="POST" action="/resultados/procesar-importacion" enctype="multipart/form-data" id="formSubir">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="accion" value="procesar">

                <!-- Drop zone -->
                <div id="dropZone"
                     style="border:2px dashed #c7d2fe;border-radius:.875rem;padding:2.5rem;text-align:center;background:#f8faff;transition:all .2s;cursor:pointer"
                     onclick="document.getElementById('fileInput').click()"
                     ondragover="e=>{e.preventDefault();this.style.borderColor='var(--primary)';this.style.background='#f0fdfe'}"
                     ondragleave="this.style.borderColor='#c7d2fe';this.style.background='#f8faff'"
                     ondrop="handleDrop(event)">
                    <i class="bi bi-cloud-upload" style="font-size:2.5rem;color:#a5b4fc;display:block;margin-bottom:.75rem"></i>
                    <div style="font-size:.95rem;font-weight:600;color:#374151;margin-bottom:.3rem">
                        Arrastre el archivo aquí o <span style="color:var(--primary)">haga clic para seleccionar</span>
                    </div>
                    <div style="font-size:.78rem;color:#94a3b8">CSV o TXT · Máximo 5 MB</div>
                    <div id="fileName" style="margin-top:.75rem;font-size:.82rem;font-weight:600;color:var(--primary);display:none"></div>
                </div>

                <input type="file" id="fileInput" name="archivo" accept=".csv,.txt" style="display:none"
                       onchange="mostrarNombre(this)">

                <div class="d-flex gap-2 justify-content-end mt-3">
                    <button type="submit" class="btn-gl btn-primary-gl" id="btnProcesar" disabled>
                        <i class="bi bi-eye"></i> Procesar y previsualizar
                    </button>
                </div>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- ── PASO 2: Previsualización y confirmación ── -->
        <div class="gl-card mb-4">
            <div class="gl-card-header" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7)">
                <div style="width:32px;height:32px;border-radius:.5rem;background:#d1fae5;display:flex;align-items:center;justify-content:center">
                    <span style="font-size:.8rem;font-weight:800;color:#059669">2</span>
                </div>
                <h5>Confirmar importación</h5>
                <span class="gl-badge badge-success ms-auto"><?= count($preview) ?> resultado(s) procesados</span>
            </div>
            <div style="overflow-x:auto">
                <table class="gl-table" style="font-size:.82rem">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Orden</th>
                            <th>Examen</th>
                            <th>Parámetro</th>
                            <th>Valor</th>
                            <th>Unidad</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($preview, 0, 50) as $i => $fila): ?>
                    <?php $esCrit = $fila['es_critico'] ?? false; ?>
                    <tr style="<?= $esCrit ? 'background:#fef9f9' : '' ?>">
                        <td style="color:#94a3b8"><?= $i + 1 ?></td>
                        <td><span class="fw-semibold" style="font-family:monospace;color:var(--primary)"><?= htmlspecialchars($fila['numero_orden'] ?? '—') ?></span></td>
                        <td><?= htmlspecialchars($fila['nombre_examen'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($fila['nombre_parametro'] ?? '—') ?></td>
                        <td class="fw-bold" style="color:<?= $esCrit ? '#ef4444' : '#0f172a' ?>">
                            <?= htmlspecialchars($fila['valor'] ?? '—') ?>
                        </td>
                        <td style="color:#94a3b8"><?= htmlspecialchars($fila['unidad'] ?? '') ?></td>
                        <td>
                            <?php if ($esCrit): ?>
                            <span class="gl-badge badge-danger" style="font-size:.65rem">⚠ Crítico</span>
                            <?php elseif (isset($fila['error'])): ?>
                            <span class="gl-badge badge-warning" style="font-size:.65rem">Error</span>
                            <?php else: ?>
                            <span class="gl-badge badge-success" style="font-size:.65rem">OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (count($preview) > 50): ?>
                    <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:1rem;font-size:.8rem">
                        + <?= count($preview) - 50 ?> filas más. Solo se muestran las primeras 50.
                    </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Botones confirmar/cancelar -->
            <div class="gl-card-body" style="border-top:1px solid #f1f5f9">
                <div class="d-flex gap-2 justify-content-end">
                    <form method="POST" action="/resultados/procesar-importacion" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="accion" value="cancelar">
                        <button type="submit" class="btn-gl btn-outline-gl">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                    </form>
                    <form method="POST" action="/resultados/procesar-importacion" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="accion" value="confirmar">
                        <button type="submit" class="btn-gl btn-primary-gl">
                            <i class="bi bi-check-lg"></i> Confirmar importación
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /col-lg-7 -->

    <!-- Sidebar de ayuda -->
    <div class="col-lg-5">

        <!-- Formato esperado -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-filetype-csv" style="color:#10b981;font-size:1.05rem"></i>
                <h5>Formato del archivo CSV</h5>
            </div>
            <div class="gl-card-body">
                <p style="font-size:.82rem;color:#64748b;margin-bottom:.875rem">
                    El archivo debe usar <strong>punto y coma (;)</strong> como separador y contener las siguientes columnas en la primera fila:
                </p>
                <div style="background:#0f172a;border-radius:.625rem;padding:1rem;font-family:monospace;font-size:.78rem;color:#a5f3fc;overflow-x:auto;margin-bottom:.875rem">
                    <div style="color:#6ee7b7;margin-bottom:.3rem"># Encabezado obligatorio</div>
                    numero_orden;codigo_examen;codigo_parametro;valor;unidad<br>
                    <br>
                    <div style="color:#6ee7b7;margin-bottom:.3rem"># Ejemplo de datos</div>
                    ORD-20250115-0001;HEMAT;HB;14.5;g/dL<br>
                    ORD-20250115-0001;HEMAT;HTO;43.2;%<br>
                    ORD-20250115-0002;QUIM;GLUC;98;mg/dL
                </div>

                <div style="font-size:.78rem">
                    <div class="mb-2"><strong style="color:#0f172a">numero_orden</strong> <span style="color:#64748b">— N° de la orden (ej: ORD-20250115-0001)</span></div>
                    <div class="mb-2"><strong style="color:#0f172a">codigo_examen</strong> <span style="color:#64748b">— Código del examen en el catálogo</span></div>
                    <div class="mb-2"><strong style="color:#0f172a">codigo_parametro</strong> <span style="color:#64748b">— Código del parámetro configurado</span></div>
                    <div class="mb-2"><strong style="color:#0f172a">valor</strong> <span style="color:#64748b">— Valor numérico o texto del resultado</span></div>
                    <div><strong style="color:#0f172a">unidad</strong> <span style="color:#64748b">— Unidad de medida (puede estar vacía)</span></div>
                </div>
            </div>
        </div>

        <!-- Notas -->
        <div class="gl-card" style="border:1px solid #fde68a;background:#fffbeb">
            <div class="gl-card-body" style="font-size:.8rem;color:#78350f">
                <div class="fw-bold mb-2"><i class="bi bi-lightbulb-fill me-1" style="color:#f59e0b"></i>Notas importantes</div>
                <ul style="padding-left:1.1rem;margin:0;line-height:1.8">
                    <li>El sistema evalúa automáticamente los rangos críticos al importar.</li>
                    <li>Si ya existen resultados para un parámetro, se <strong>sobreescriben</strong>.</li>
                    <li>Las órdenes no encontradas o en estado incorrecto se reportan como error.</li>
                    <li>Puede revisar el preview antes de confirmar.</li>
                    <li>Codificación recomendada: <strong>UTF-8</strong>.</li>
                </ul>
            </div>
        </div>

    </div>
</div>

<script>
function mostrarNombre(input) {
    const nombre = input.files[0]?.name;
    const zone   = document.getElementById('dropZone');
    const fn     = document.getElementById('fileName');
    const btn    = document.getElementById('btnProcesar');
    if (nombre) {
        fn.textContent = '📄 ' + nombre;
        fn.style.display = 'block';
        zone.style.borderColor = 'var(--primary)';
        zone.style.background  = '#f0fdfe';
        btn.disabled = false;
    }
}
function handleDrop(e) {
    e.preventDefault();
    const zone = document.getElementById('dropZone');
    zone.style.borderColor = '#c7d2fe';
    zone.style.background  = '#f8faff';
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const input = document.getElementById('fileInput');
    const dt    = new DataTransfer();
    dt.items.add(file);
    input.files = dt.files;
    mostrarNombre(input);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>