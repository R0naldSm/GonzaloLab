<?php
$pageTitle = 'Pacientes';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 style="font-size:1.45rem;font-weight:800;color:#0f172a;margin:0">Pacientes</h1>
        <p style="font-size:.82rem;color:#64748b;margin:.15rem 0 0">Registro y gestión de pacientes del laboratorio</p>
    </div>
    <?php if (\RBAC::puede('pacientes.crear')): ?>
    <a href="/pacientes/crear" class="btn-gl btn-primary-gl">
        <i class="bi bi-person-plus"></i> Nuevo paciente
    </a>
    <?php endif; ?>
</div>

<!-- Búsqueda -->
<div class="gl-card mb-4">
    <div class="gl-card-body">
        <form method="GET" action="/pacientes" class="row g-2 align-items-end">
            <div class="col-md-8">
                <label class="gl-label">Buscar por nombre o cédula</label>
                <div class="position-relative">
                    <i class="bi bi-search" style="position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:.9rem"></i>
                    <input type="text" name="q" class="gl-input" style="padding-left:2.25rem"
                           placeholder="Nombre, apellido o número de cédula…"
                           value="<?= htmlspecialchars($filtros['busqueda'] ?? '') ?>"
                           autofocus>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-gl btn-primary-gl w-100">
                    <i class="bi bi-search"></i> Buscar
                </button>
            </div>
            <div class="col-md-2">
                <a href="/pacientes" class="btn-gl btn-outline-gl w-100">
                    <i class="bi bi-x-lg"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="gl-card">
    <div class="gl-card-header">
        <i class="bi bi-people" style="color:var(--primary);font-size:1.05rem"></i>
        <h5><?= count($pacientes ?? []) ?> paciente(s)</h5>
        <?php if (!empty($filtros['busqueda'])): ?>
        <span style="font-size:.78rem;color:#94a3b8;margin-left:.25rem">— búsqueda: "<?= htmlspecialchars($filtros['busqueda']) ?>"</span>
        <?php endif; ?>
    </div>
    <div style="overflow-x:auto">
        <table class="gl-table">
            <thead>
                <tr>
                    <th>Nombre completo</th>
                    <th>Cédula</th>
                    <th>Fecha nac.</th>
                    <th>Género</th>
                    <th>Contacto</th>
                    <th>Tipo sangre</th>
                    <th>Estado</th>
                    <th style="width:140px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pacientes)): ?>
                <tr><td colspan="8" style="text-align:center;padding:3rem;color:#94a3b8">
                    <i class="bi bi-people" style="font-size:2.5rem;display:block;margin-bottom:.6rem;opacity:.3"></i>
                    <div style="font-size:.9rem">
                        <?= !empty($filtros['busqueda']) ? 'No se encontraron pacientes para "' . htmlspecialchars($filtros['busqueda']) . '"' : 'No hay pacientes registrados aún' ?>
                    </div>
                    <?php if (\RBAC::puede('pacientes.crear')): ?>
                    <a href="/pacientes/crear" class="btn-gl btn-primary-gl mt-3" style="display:inline-flex">
                        <i class="bi bi-person-plus"></i> Registrar primer paciente
                    </a>
                    <?php endif; ?>
                </td></tr>
            <?php else: ?>
            <?php foreach ($pacientes as $p): ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;
                            background:linear-gradient(135deg,var(--primary),var(--secondary));
                            display:flex;align-items:center;justify-content:center;
                            color:#fff;font-size:.78rem;font-weight:700">
                            <?= mb_strtoupper(mb_substr($p['nombres']??'P',0,1)) ?>
                        </div>
                        <div>
                            <div class="fw-semibold" style="color:#0f172a">
                                <?= htmlspecialchars(trim(($p['apellidos']??'') . ', ' . ($p['nombres']??''))) ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <span style="font-family:monospace;font-size:.82rem;color:#64748b">
                        <?= htmlspecialchars($p['cedula'] ?? '—') ?>
                    </span>
                </td>
                <td style="font-size:.82rem;color:#64748b;white-space:nowrap">
                    <?= !empty($p['fecha_nacimiento']) ? date('d/m/Y', strtotime($p['fecha_nacimiento'])) : '—' ?>
                    <?php if (!empty($p['fecha_nacimiento'])): ?>
                    <div style="font-size:.72rem;color:#c4cdd6">
                        <?= (int)floor((time() - strtotime($p['fecha_nacimiento'])) / 31557600) ?> años
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php $genero = $p['genero'] ?? '';
                    $gBadge = ['M'=>['badge-info','Masculino'],'F'=>['badge-purple','Femenino'],'O'=>['badge-gray','Otro']][$genero] ?? ['badge-gray','—'];
                    ?>
                    <span class="gl-badge <?= $gBadge[0] ?>"><?= $gBadge[1] ?></span>
                </td>
                <td style="font-size:.82rem;color:#64748b">
                    <?php if (!empty($p['telefono'])): ?>
                    <div><i class="bi bi-phone me-1"></i><?= htmlspecialchars($p['telefono']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($p['email'])): ?>
                    <div style="font-size:.75rem"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars(mb_substr($p['email'],0,24)) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?= !empty($p['tipo_sangre']) ? '<span class="gl-badge badge-danger">'.$p['tipo_sangre'].'</span>' : '—' ?>
                </td>
                <td>
                    <span class="gl-badge <?= ($p['activo']??true) ? 'badge-success' : 'badge-gray' ?>">
                        <?= ($p['activo']??true) ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <?php if (\RBAC::puede('pacientes.historial')): ?>
                        <a href="/pacientes/historial/<?= $p['id_paciente'] ?>"
                           class="btn-gl btn-outline-gl btn-sm-gl" title="Historial">
                            <i class="bi bi-clock-history"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (\RBAC::puede('ordenes.crear')): ?>
                        <a href="/ordenes/crear?id_paciente=<?= $p['id_paciente'] ?>"
                           class="btn-gl btn-outline-gl btn-sm-gl" title="Nueva orden">
                            <i class="bi bi-clipboard2-plus"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (\RBAC::puede('pacientes.editar')): ?>
                        <a href="/pacientes/editar/<?= $p['id_paciente'] ?>"
                           class="btn-gl btn-outline-gl btn-sm-gl" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (\RBAC::puede('pacientes.eliminar')): ?>
                        <button class="btn-gl btn-danger-gl btn-sm-gl" title="Eliminar"
                            onclick="eliminar(<?= $p['id_paciente'] ?>, '<?= addslashes(trim(($p['nombres']??'').' '.($p['apellidos']??''))) ?>')">
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
    if (!confirm('¿Eliminar al paciente ' + nombre + '?\nSus órdenes e historial permanecerán, solo se ocultará del listado.')) return;
    fetch('/pacientes/eliminar/' + id, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({csrf_token:'<?= htmlspecialchars($csrfToken) ?>'})
    }).then(r=>r.json()).then(d=>{
        if(d.success) location.reload();
        else alert(d.message||'No se pudo eliminar');
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>