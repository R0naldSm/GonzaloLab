<?php
$pageTitle = 'Editar Paciente';
require_once __DIR__ . '/../layouts/header.php';
$p = $paciente ?? [];
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/pacientes" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">
            Editar: <?= htmlspecialchars(trim(($p['nombres']??'').' '.($p['apellidos']??''))) ?>
        </h1>
        <div style="font-size:.78rem;color:#64748b;margin-top:.15rem">
            C.I. <?= htmlspecialchars($p['cedula'] ?? '—') ?>
            · <?= !empty($p['fecha_nacimiento']) ? date('d/m/Y', strtotime($p['fecha_nacimiento'])) : '—' ?>
        </div>
    </div>
    <div class="ms-auto d-flex gap-2">
        <?php if (\RBAC::puede('pacientes.historial')): ?>
        <a href="/pacientes/historial/<?= $p['id_paciente'] ?>" class="btn-gl btn-outline-gl">
            <i class="bi bi-clock-history"></i> Historial
        </a>
        <?php endif; ?>
        <?php if (\RBAC::puede('ordenes.crear')): ?>
        <a href="/ordenes/crear?id_paciente=<?= $p['id_paciente'] ?>" class="btn-gl btn-primary-gl">
            <i class="bi bi-clipboard2-plus"></i> Nueva orden
        </a>
        <?php endif; ?>
    </div>
</div>

<form method="POST" action="/pacientes/editar/<?= $p['id_paciente'] ?>" id="formPaciente" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

<div class="row g-4">
    <div class="col-lg-8">

        <!-- Identificación -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-person-badge" style="color:var(--primary)"></i>
                <h5>Identificación</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="gl-label">Cédula / Pasaporte <span style="color:#ef4444">*</span></label>
                        <input type="text" name="cedula" class="gl-input" required maxlength="20"
                               value="<?= htmlspecialchars($p['cedula'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Nombres <span style="color:#ef4444">*</span></label>
                        <input type="text" name="nombres" class="gl-input" required
                               value="<?= htmlspecialchars($p['nombres'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Apellidos <span style="color:#ef4444">*</span></label>
                        <input type="text" name="apellidos" class="gl-input" required
                               value="<?= htmlspecialchars($p['apellidos'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="gl-label">Fecha de nacimiento <span style="color:#ef4444">*</span></label>
                        <input type="date" name="fecha_nacimiento" class="gl-input" required
                               max="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($p['fecha_nacimiento'] ?? '') ?>"
                               oninput="calcularEdad(this.value)">
                    </div>
                    <div class="col-md-2">
                        <label class="gl-label">Edad</label>
                        <input type="text" id="edadDisplay" class="gl-input" readonly
                               style="background:#f8fafc;color:#64748b" value="">
                    </div>
                    <div class="col-md-3">
                        <label class="gl-label">Género <span style="color:#ef4444">*</span></label>
                        <select name="genero" class="gl-input gl-select" required>
                            <option value="">— Seleccionar —</option>
                            <option value="M" <?= ($p['genero']??'')==='M'?'selected':'' ?>>Masculino</option>
                            <option value="F" <?= ($p['genero']??'')==='F'?'selected':'' ?>>Femenino</option>
                            <option value="O" <?= ($p['genero']??'')==='O'?'selected':'' ?>>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Tipo de sangre</label>
                        <select name="tipo_sangre" class="gl-input gl-select">
                            <option value="">— —</option>
                            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $ts): ?>
                            <option value="<?= $ts ?>" <?= ($p['tipo_sangre']??'')===$ts?'selected':'' ?>><?= $ts ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <label style="display:flex;align-items:center;gap:.65rem;cursor:pointer">
                            <input type="checkbox" name="activo" value="1"
                                <?= ($p['activo']??true)?'checked':'' ?>
                                style="width:18px;height:18px;accent-color:var(--primary)">
                            <span style="font-size:.875rem;font-weight:600;color:#374151">Paciente activo</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contacto -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-telephone" style="color:var(--primary)"></i>
                <h5>Contacto</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="gl-label">Teléfono principal</label>
                        <input type="tel" name="telefono" class="gl-input"
                               value="<?= htmlspecialchars($p['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Teléfono secundario</label>
                        <input type="tel" name="telefono_secundario" class="gl-input"
                               value="<?= htmlspecialchars($p['telefono_secundario'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Correo electrónico</label>
                        <input type="email" name="email" class="gl-input"
                               value="<?= htmlspecialchars($p['email'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Dirección</label>
                        <input type="text" name="direccion" class="gl-input"
                               value="<?= htmlspecialchars($p['direccion'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Info médica -->
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-heart-pulse" style="color:#ef4444"></i>
                <h5>Información médica</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="gl-label">Alergias conocidas</label>
                        <textarea name="alergias" class="gl-input" rows="2"><?= htmlspecialchars($p['alergias'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Medicamentos actuales / Observaciones</label>
                        <textarea name="medicamentos_actuales" class="gl-input" rows="2"><?= htmlspecialchars($p['medicamentos_actuales'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna lateral -->
    <div class="col-lg-4">
        <!-- Metadatos -->
        <div class="gl-card mb-4" style="background:#f8fafc">
            <div class="gl-card-body" style="font-size:.78rem;color:#64748b">
                <div class="d-flex justify-content-between mb-1"><span>ID</span><span>#<?= $p['id_paciente'] ?></span></div>
                <div class="d-flex justify-content-between mb-1">
                    <span>Registrado</span>
                    <span><?= !empty($p['fecha_registro']) ? date('d/m/Y', strtotime($p['fecha_registro'])) : '—' ?></span>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column gap-2">
            <button type="submit" class="btn-gl btn-primary-gl" style="justify-content:center;padding:.7rem">
                <i class="bi bi-check-lg"></i> Guardar cambios
            </button>
            <a href="/pacientes" class="btn-gl btn-outline-gl" style="justify-content:center">Cancelar</a>
        </div>
    </div>
</div>
</form>

<script>
function calcularEdad(dob) {
    if (!dob) { document.getElementById('edadDisplay').value = ''; return; }
    document.getElementById('edadDisplay').value = Math.floor((Date.now() - new Date(dob)) / 31557600000) + ' años';
}
const fn = document.querySelector('[name=fecha_nacimiento]');
if (fn?.value) calcularEdad(fn.value);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>