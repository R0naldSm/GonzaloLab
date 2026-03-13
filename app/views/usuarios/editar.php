<?php
// Variables: $usuario, $rolesDisponibles, $menuNav, $nombreUsuario, $csrfToken, $flash
$u = $usuario;
$pageTitle   = 'Editar — @' . ($u['username'] ?? '');
$breadcrumbs = [['label'=>'Usuarios','url'=>'/usuarios'],['label'=>'@'.($u['username']??'')]];
require_once __DIR__ . '/../layouts/header.php';

$rolInfo = [
    'administrador' => ['Administrador', '#7c3aed', 'bi-shield-check'],
    'analistaL'     => ['Analista Lab',  '#0891b2', 'bi-eyedropper'],
    'medico'        => ['Médico',        '#059669', 'bi-heart-pulse'],
    'paciente'      => ['Paciente',      '#d97706', 'bi-person-fill'],
];
$estadoOpts = ['activo'=>'Activo','inactivo'=>'Inactivo','bloqueado'=>'Bloqueado'];
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/usuarios" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">
            Editar usuario <span style="color:var(--primary)">@<?= htmlspecialchars($u['username'] ?? '') ?></span>
        </h1>
        <div style="font-size:.8rem;color:#64748b;margin-top:.2rem">
            <?php [$rolLbl, $rolColor] = $rolInfo[$u['rol'] ?? ''] ?? [$u['rol'], '#64748b', '']; ?>
            ID #<?= $u['id_usuario'] ?> · Registrado <?= !empty($u['fecha_creacion']) ? date('d/m/Y', strtotime($u['fecha_creacion'])) : '—' ?>
        </div>
    </div>
</div>

<form method="POST" action="/usuarios/actualizar/<?= $u['id_usuario'] ?>" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

<div class="row g-4">
    <div class="col-lg-8">

        <!-- Datos -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-person-badge" style="color:var(--primary);font-size:1.05rem"></i>
                <h5>Datos del usuario</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="gl-label">Username</label>
                        <input type="text" name="username" class="gl-input"
                            value="<?= htmlspecialchars($u['username'] ?? '') ?>"
                            pattern="[a-zA-Z0-9_\-]{4,30}"
                            oninput="this.value=this.value.toLowerCase()">
                    </div>
                    <div class="col-md-8">
                        <label class="gl-label">Email <span style="color:#ef4444">*</span></label>
                        <input type="email" name="email" class="gl-input" required
                            value="<?= htmlspecialchars($u['email'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Nombre completo <span style="color:#ef4444">*</span></label>
                        <input type="text" name="nombre_completo" class="gl-input" required
                            value="<?= htmlspecialchars($u['nombre_completo'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Cédula</label>
                        <input type="text" name="cedula" class="gl-input" maxlength="10"
                            value="<?= htmlspecialchars($u['cedula'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Rol <span style="color:#ef4444">*</span></label>
                        <select name="rol" class="gl-input gl-select">
                            <?php foreach ($rolesDisponibles as $r): ?>
                            <?php [$rLbl, $rCol] = $rolInfo[$r] ?? [$r, '#64748b']; ?>
                            <option value="<?= $r ?>" <?= ($u['rol'] ?? '') === $r ? 'selected' : '' ?>>
                                <?= $rLbl ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="gl-label">Estado</label>
                        <select name="estado" class="gl-input gl-select">
                            <?php foreach ($estadoOpts as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($u['estado'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /col-lg-8 -->

    <div class="col-lg-4">

        <!-- Metadatos -->
        <div class="gl-card mb-4" style="background:#f8fafc">
            <div class="gl-card-header" style="background:#f1f5f9">
                <i class="bi bi-info-circle" style="color:#94a3b8"></i>
                <h5 style="color:#64748b">Información de la cuenta</h5>
            </div>
            <div class="gl-card-body" style="font-size:.8rem;color:#64748b">
                <div class="d-flex justify-content-between mb-2">
                    <span>ID</span><strong>#<?= $u['id_usuario'] ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Rol actual</span>
                    <span class="fw-semibold" style="color:<?= $rolInfo[$u['rol'] ?? ''][1] ?? '#64748b' ?>">
                        <?= $rolInfo[$u['rol'] ?? ''][0] ?? $u['rol'] ?>
                    </span>
                </div>
                <?php if (!empty($u['ultimo_acceso'])): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Último acceso</span>
                    <span><?= date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) ?></span>
                </div>
                <?php endif; ?>
                <?php if (($u['intentos_fallidos'] ?? 0) > 0): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Intentos fallidos</span>
                    <span class="fw-bold" style="color:#ef4444"><?= $u['intentos_fallidos'] ?></span>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between">
                    <span>Estado</span>
                    <?php $estCls = ['activo'=>'badge-success','inactivo'=>'badge-gray','bloqueado'=>'badge-danger'][$u['estado']??''] ?? 'badge-gray'; ?>
                    <span class="gl-badge <?= $estCls ?>"><?= ucfirst($u['estado'] ?? '—') ?></span>
                </div>

                <?php if (($u['estado'] ?? '') === 'bloqueado' && \RBAC::puede('usuarios.editar')): ?>
                <div class="mt-3">
                    <button type="button" class="btn-gl btn-outline-gl btn-sm-gl w-100"
                        onclick="desbloquear(<?= $u['id_usuario'] ?>)" style="justify-content:center">
                        <i class="bi bi-unlock"></i> Desbloquear cuenta
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resetear clave -->
        <?php if (\RBAC::puede('usuarios.resetear_clave')): ?>
        <div class="gl-card mb-4" style="border:1px solid #fde68a;background:#fffbeb">
            <div class="gl-card-body">
                <div style="font-size:.83rem;font-weight:600;color:#78350f;margin-bottom:.5rem">
                    <i class="bi bi-key-fill me-1"></i>Resetear contraseña
                </div>
                <p style="font-size:.78rem;color:#92400e;margin-bottom:.75rem">Genera una contraseña temporal. Deberá entregársela al usuario de forma segura.</p>
                <button type="button" class="btn-gl btn-sm-gl w-100"
                    style="background:#f59e0b;color:#fff;justify-content:center"
                    onclick="resetear(<?= $u['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($u['username']??'')) ?>')">
                    <i class="bi bi-arrow-repeat"></i> Generar clave temporal
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Botones -->
        <div class="d-flex flex-column gap-2">
            <button type="submit" class="btn-gl btn-primary-gl" style="justify-content:center;padding:.7rem">
                <i class="bi bi-check-lg"></i> Guardar cambios
            </button>
            <a href="/usuarios" class="btn-gl btn-outline-gl" style="justify-content:center">Cancelar</a>
        </div>
    </div>
</div>
</form>

<!-- Modal clave -->
<div id="modalClave" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:1rem;padding:2rem;max-width:380px;width:90%;box-shadow:0 24px 48px rgba(0,0,0,.2)">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem"><i class="bi bi-key-fill me-2" style="color:var(--primary)"></i>Clave temporal generada</h3>
        <div style="background:#f0fdf4;border:2px solid #bbf7d0;border-radius:.75rem;padding:1rem;text-align:center;margin-bottom:1rem">
            <div style="font-size:1.4rem;font-family:monospace;font-weight:800;letter-spacing:.1em;color:#065f46" id="claveModal">—</div>
        </div>
        <p style="font-size:.8rem;color:#64748b;margin-bottom:1rem">Copie y entregue al usuario de forma segura.</p>
        <div class="d-flex gap-2">
            <button onclick="document.getElementById('modalClave').textContent && navigator.clipboard.writeText(document.getElementById('claveModal').textContent)" class="btn-gl btn-outline-gl flex-fill"><i class="bi bi-clipboard"></i> Copiar</button>
            <button onclick="document.getElementById('modalClave').style.display='none'" class="btn-gl btn-primary-gl flex-fill">Cerrar</button>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= htmlspecialchars($csrfToken) ?>';
function resetear(id, username) {
    if (!confirm('¿Resetear contraseña de @' + username + '?')) return;
    fetch('/usuarios/resetear-clave/' + id, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({csrf_token: CSRF})
    }).then(r=>r.json()).then(d => {
        if (d.success) {
            document.getElementById('claveModal').textContent = d.nueva_clave;
            document.getElementById('modalClave').style.display = 'flex';
        } else alert(d.message);
    });
}
function desbloquear(id) {
    fetch('/usuarios/desbloquear/' + id, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({csrf_token: CSRF})
    }).then(r=>r.json()).then(d => {
        if (d.success) location.reload(); else alert(d.message);
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>