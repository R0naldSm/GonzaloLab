<?php
$pageTitle = 'Nuevo Paciente';
require_once __DIR__ . '/../layouts/header.php';
$pre = $pacienteExistente ?? [];
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/pacientes" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">Nuevo Paciente</h1>
        <p style="font-size:.82rem;color:#64748b;margin:0">Complete los datos del paciente</p>
    </div>
</div>

<?php if ($pacienteExistente): ?>
<div class="gl-alert gl-alert-warning mb-4">
    <i class="bi bi-exclamation-triangle-fill"></i>
    Ya existe un paciente con cédula <strong><?= htmlspecialchars($cedula ?? '') ?></strong>:
    <strong><?= htmlspecialchars(trim(($pre['nombres']??'').' '.($pre['apellidos']??''))) ?></strong>.
    <a href="/pacientes/editar/<?= $pre['id_paciente'] ?>" class="ms-2 fw-semibold">Editar ese paciente →</a>
</div>
<?php endif; ?>

<form method="POST" action="/pacientes/crear" id="formPaciente" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
<?php if (!empty($_GET['redirect_orden'])): ?>
<input type="hidden" name="redirect_orden" value="1">
<?php endif; ?>

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
                        <label class="gl-label">Cédula / Pasaporte <span style="color:#ef4444">*</span>
                            <span id="cedStatus" style="font-size:.72rem;margin-left:.3rem"></span>
                        </label>
                        <input type="text" name="cedula" id="cedula" class="gl-input" required
                               placeholder="1234567890"
                               value="<?= htmlspecialchars($cedula ?? ($pre['cedula'] ?? '')) ?>"
                               oninput="validarCedula(this.value)" maxlength="20">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Nombres <span style="color:#ef4444">*</span></label>
                        <input type="text" name="nombres" class="gl-input" required
                               placeholder="Nombres del paciente"
                               value="<?= htmlspecialchars($pre['nombres'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Apellidos <span style="color:#ef4444">*</span></label>
                        <input type="text" name="apellidos" class="gl-input" required
                               placeholder="Apellidos del paciente"
                               value="<?= htmlspecialchars($pre['apellidos'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="gl-label">Fecha de nacimiento <span style="color:#ef4444">*</span></label>
                        <input type="date" name="fecha_nacimiento" class="gl-input" required
                               max="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($pre['fecha_nacimiento'] ?? '') ?>"
                               oninput="calcularEdad(this.value)">
                    </div>
                    <div class="col-md-2">
                        <label class="gl-label">Edad</label>
                        <input type="text" id="edadDisplay" class="gl-input" readonly
                               style="background:#f8fafc;color:#64748b"
                               placeholder="—" value="">
                    </div>
                    <div class="col-md-3">
                        <label class="gl-label">Género <span style="color:#ef4444">*</span></label>
                        <select name="genero" class="gl-input gl-select" required>
                            <option value="">— Seleccionar —</option>
                            <option value="M" <?= ($pre['genero']??'')==='M'?'selected':'' ?>>Masculino</option>
                            <option value="F" <?= ($pre['genero']??'')==='F'?'selected':'' ?>>Femenino</option>
                            <option value="O" <?= ($pre['genero']??'')==='O'?'selected':'' ?>>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Tipo de sangre</label>
                        <select name="tipo_sangre" class="gl-input gl-select">
                            <option value="">— —</option>
                            <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $ts): ?>
                            <option value="<?= $ts ?>" <?= ($pre['tipo_sangre']??'')===$ts?'selected':'' ?>><?= $ts ?></option>
                            <?php endforeach; ?>
                        </select>
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
                               placeholder="0991234567"
                               value="<?= htmlspecialchars($pre['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Teléfono secundario</label>
                        <input type="tel" name="telefono_secundario" class="gl-input"
                               placeholder="Opcional"
                               value="<?= htmlspecialchars($pre['telefono_secundario'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Correo electrónico</label>
                        <input type="email" name="email" class="gl-input"
                               placeholder="paciente@correo.com"
                               value="<?= htmlspecialchars($pre['email'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Dirección</label>
                        <input type="text" name="direccion" class="gl-input"
                               placeholder="Calle, barrio, ciudad…"
                               value="<?= htmlspecialchars($pre['direccion'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Info médica -->
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-heart-pulse" style="color:#ef4444"></i>
                <h5>Información médica relevante</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="gl-label">Alergias conocidas</label>
                        <textarea name="alergias" class="gl-input" rows="2"
                            placeholder="Ej: Penicilina, látex, medios de contraste yodados…"><?= htmlspecialchars($pre['alergias'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Medicamentos actuales / Observaciones</label>
                        <textarea name="medicamentos_actuales" class="gl-input" rows="2"
                            placeholder="Medicamentos que pueden interferir con resultados analíticos (anticoagulantes, corticoides, etc.)…"><?= htmlspecialchars($pre['medicamentos_actuales'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna lateral -->
    <div class="col-lg-4">
        <div class="gl-card mb-4" style="position:sticky;top:calc(var(--topbar-h) + 1rem)">
            <div class="gl-card-header">
                <i class="bi bi-person-circle" style="color:var(--primary)"></i>
                <h5>Vista previa</h5>
            </div>
            <div class="gl-card-body" style="text-align:center">
                <div id="prevAvatar" style="width:64px;height:64px;border-radius:50%;
                    background:linear-gradient(135deg,var(--primary),var(--secondary));
                    display:inline-flex;align-items:center;justify-content:center;
                    color:#fff;font-size:1.5rem;font-weight:800;margin-bottom:.75rem">?</div>
                <div id="prevNombre" class="fw-bold" style="font-size:.95rem;color:#0f172a;margin-bottom:.25rem">—</div>
                <div id="prevCedula" style="font-size:.8rem;color:#64748b">—</div>
                <div id="prevEdad" style="font-size:.78rem;color:#94a3b8;margin-top:.15rem"></div>
            </div>
        </div>

        <!-- Botones -->
        <div class="d-flex flex-column gap-2">
            <button type="submit" class="btn-gl btn-primary-gl" style="justify-content:center;padding:.7rem">
                <i class="bi bi-check-lg"></i> Registrar paciente
            </button>
            <a href="/pacientes" class="btn-gl btn-outline-gl" style="justify-content:center">Cancelar</a>
        </div>
    </div>
</div>
</form>

<script>
// Vista previa en tiempo real
const campos = {nombres:'', apellidos:'', cedula:''};
function actualizarPreview() {
    const nom = campos.nombres + (campos.nombres && campos.apellidos ? ' ' : '') + campos.apellidos;
    document.getElementById('prevNombre').textContent = nom || '—';
    document.getElementById('prevCedula').textContent = campos.cedula || '—';
    document.getElementById('prevAvatar').textContent = campos.nombres ? campos.nombres[0].toUpperCase() : '?';
}
document.querySelector('[name=nombres]').oninput = function(){ campos.nombres = this.value; actualizarPreview(); };
document.querySelector('[name=apellidos]').oninput = function(){ campos.apellidos = this.value; actualizarPreview(); };
document.querySelector('[name=cedula]').oninput = function(){ campos.cedula = this.value; actualizarPreview(); };

function calcularEdad(dob) {
    if (!dob) { document.getElementById('edadDisplay').value = ''; document.getElementById('prevEdad').textContent = ''; return; }
    const years = Math.floor((Date.now() - new Date(dob)) / 31557600000);
    document.getElementById('edadDisplay').value = years + ' años';
    document.getElementById('prevEdad').textContent = years + ' años';
}

// Validación de cédula ecuatoriana
let cedTimer;
function validarCedula(val) {
    clearTimeout(cedTimer);
    const st = document.getElementById('cedStatus');
    val = val.trim();
    if (!val) { st.textContent = ''; return; }
    // Solo validar si son exactamente 10 dígitos (cédula EC)
    if (!/^\d{10}$/.test(val)) { st.textContent = ''; return; }
    // Algoritmo módulo 10 para cédula ecuatoriana
    const prov = parseInt(val.substring(0,2));
    if (prov < 1 || prov > 24) { st.textContent = '✗ Provincia inválida'; st.style.color='#ef4444'; return; }
    const coeficientes = [2,1,2,1,2,1,2,1,2];
    let suma = 0;
    for (let i = 0; i < 9; i++) {
        let prod = parseInt(val[i]) * coeficientes[i];
        if (prod >= 10) prod -= 9;
        suma += prod;
    }
    const verificador = (10 - (suma % 10)) % 10;
    const ok = verificador === parseInt(val[9]);
    st.textContent = ok ? '✓ Válida' : '✗ Inválida';
    st.style.color = ok ? '#10b981' : '#ef4444';

    // Verificar duplicado en sistema
    if (ok) {
        cedTimer = setTimeout(() => {
            fetch('/pacientes/buscar?q=' + encodeURIComponent(val) + '&tipo=cedula')
                .then(r => r.json()).then(d => {
                    if (d.data?.length) {
                        st.textContent = '⚠ Ya registrada';
                        st.style.color = '#d97706';
                    }
                }).catch(()=>{});
        }, 400);
    }
}

// Pre-calcular edad si viene pre-cargada
const fnInput = document.querySelector('[name=fecha_nacimiento]');
if (fnInput?.value) calcularEdad(fnInput.value);
actualizarPreview();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>