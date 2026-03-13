<?php
// Variables: $usuarios, $filtros, $stats, $menuNav, $nombreUsuario, $csrfToken, $flash
$pageTitle = 'Usuarios del Sistema';
require_once __DIR__ . '/../layouts/header.php';

$rolLabels = [
    'administrador' => ['Administrador', 'badge-purple', 'bi-shield-check'],
    'analistaL'     => ['Analista Lab',  'badge-info',   'bi-eyedropper'],
    'medico'        => ['Médico',        'badge-success','bi-heart-pulse'],
    'paciente'      => ['Paciente',      'badge-warning','bi-person'],
];
$estadoLabels = [
    'activo'    => ['badge-success', 'Activo'],
    'inactivo'  => ['badge-gray',    'Inactivo'],
    'bloqueado' => ['badge-danger',  'Bloqueado'],
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 style="font-size:1.45rem;font-weight:800;color:#0f172a;margin:0">Usuarios</h1>
        <p style="font-size:.82rem;color:#64748b;margin:.15rem 0 0">Administración de cuentas del sistema</p>
    </div>
    <?php if (\RBAC::puede('usuarios.crear')): ?>
    <a href="/usuarios/crear" class="btn-gl btn-primary-gl">
        <i class="bi bi-person-plus-fill"></i> Nuevo usuario
    </a>
    <?php endif; ?>
</div>

<!-- Stats rápidas -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total',         $stats['total'] ?? 0,     '#eff6ff', '#3b82f6', 'bi-people'],
        ['Activos',       $stats['activos'] ?? 0,   '#f0fdf4', '#10b981', 'bi-person-check'],
        ['Bloqueados',    $stats['bloqueados'] ?? 0,'#fef2f2', '#ef4444', 'bi-person-lock'],
        ['Administradores',$stats['admins'] ?? 0,   '#faf5ff', '#8b5cf6', 'bi-shield-check'],
    ];
    foreach ($cards as [$lbl, $val, $bg, $col, $ico]):
    ?>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:<?= $bg ?>"><i class="bi <?= $ico ?>" style="color:<?= $col ?>"></i></div>
            <div><div class="stat-value" style="color:<?= $val > 0 && $lbl === 'Bloqueados' ? '#ef4444' : 'inherit' ?>"><?= $val ?></div><div class="stat-label"><?= $lbl ?></div></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filtros -->
<div class="gl-card mb-4">
    <div class="gl-card-body">
        <form method="GET" action="/usuarios" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="gl-label">Buscar</label>
                <input type="text" name="q" class="gl-input" placeholder="Username o email…"
                    value="<?= htmlspecialchars($filtros['busqueda'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="gl-label">Rol</label>
                <select name="rol" class="gl-input gl-select">
                    <option value="">Todos</option>
                    <?php foreach ($rolLabels as $v => [$l]): ?>
                    <option value="<?= $v ?>" <?= ($filtros['rol'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="gl-label">Estado</label>
                <select name="estado" class="gl-input gl-select">
                    <option value="">Todos</option>
                    <?php foreach ($estadoLabels as $v => [$c, $l]): ?>
                    <option value="<?= $v ?>" <?= ($filtros['estado'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn-gl btn-primary-gl flex-fill"><i class="bi bi-search"></i></button>
                <a href="/usuarios" class="btn-gl btn-outline-gl" title="Limpiar"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="gl-card">
    <div class="gl-card-header">
        <i class="bi bi-people" style="color:var(--primary);font-size:1.05rem"></i>
        <h5><?= count($usuarios ?? []) ?> usuario(s)</h5>
    </div>
    <div style="overflow-x:auto">
        <table class="gl-table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Último acceso</th>
                    <th>Registrado</th>
                    <th style="width:160px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="7" style="text-align:center;padding:3rem;color:#94a3b8">
                    <i class="bi bi-people" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.35"></i>
                    No hay usuarios con los filtros seleccionados
                </td></tr>
            <?php else: ?>
            <?php foreach ($usuarios as $u): ?>
            <?php
                [$rolLbl, $rolCls, $rolIco] = $rolLabels[$u['rol']] ?? [$u['rol'], 'badge-gray', 'bi-person'];
                [$estCls, $estLbl] = $estadoLabels[$u['estado']] ?? ['badge-gray', $u['estado']];
                $esSelf = (int)$u['id_usuario'] === (int)($_SESSION['user_id'] ?? 0);
            ?>
            <tr style="<?= $esSelf ? 'background:#f0fdfe' : '' ?>">
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#fff;flex-shrink:0">
                            <?= strtoupper(substr($u['username'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-semibold" style="font-size:.87rem;color:#0f172a">
                                @<?= htmlspecialchars($u['username']) ?>
                                <?php if ($esSelf): ?><span class="gl-badge badge-info ms-1" style="font-size:.62rem">Tú</span><?php endif; ?>
                            </div>
                            <?php if (!empty($u['nombre_completo'])): ?>
                            <div style="font-size:.73rem;color:#94a3b8"><?= htmlspecialchars($u['nombre_completo']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td style="font-size:.83rem;color:#475569"><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                <td>
                    <span class="gl-badge <?= $rolCls ?>">
                        <i class="bi <?= $rolIco ?>" style="font-size:.7rem"></i> <?= $rolLbl ?>
                    </span>
                </td>
                <td>
                    <span class="gl-badge <?= $estCls ?>"><?= $estLbl ?></span>
                    <?php if (($u['intentos_fallidos'] ?? 0) > 0): ?>
                    <div style="font-size:.68rem;color:#f59e0b;margin-top:.15rem">
                        <i class="bi bi-exclamation-triangle-fill"></i> <?= $u['intentos_fallidos'] ?> intentos fallidos
                    </div>
                    <?php endif; ?>
                </td>
                <td style="font-size:.8rem;color:#64748b;white-space:nowrap">
                    <?= !empty($u['ultimo_acceso']) ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : '<span style="color:#d1d5db">Nunca</span>' ?>
                </td>
                <td style="font-size:.8rem;color:#94a3b8;white-space:nowrap">
                    <?= !empty($u['fecha_creacion']) ? date('d/m/Y', strtotime($u['fecha_creacion'])) : '—' ?>
                </td>
                <td>
                    <div class="d-flex gap-1 flex-wrap">
                        <?php if (\RBAC::puede('usuarios.editar') && !$esSelf): ?>
                        <a href="/usuarios/editar/<?= $u['id_usuario'] ?>" class="btn-gl btn-outline-gl btn-sm-gl" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (\RBAC::puede('usuarios.resetear_clave') && !$esSelf): ?>
                        <button class="btn-gl btn-outline-gl btn-sm-gl" title="Resetear contraseña"
                            onclick="resetearClave(<?= $u['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                            <i class="bi bi-key"></i>
                        </button>
                        <?php endif; ?>

                        <?php if ($u['estado'] === 'bloqueado' && \RBAC::puede('usuarios.editar')): ?>
                        <button class="btn-gl btn-outline-gl btn-sm-gl" title="Desbloquear" style="color:#10b981"
                            onclick="desbloquear(<?= $u['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                            <i class="bi bi-unlock"></i>
                        </button>
                        <?php endif; ?>

                        <?php if (\RBAC::puede('usuarios.desactivar') && !$esSelf): ?>
                        <button class="btn-gl btn-danger-gl btn-sm-gl" title="Desactivar"
                            onclick="desactivar(<?= $u['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
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

<!-- Modal clave temporal -->
<div id="modalClave" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:1rem;padding:2rem;max-width:420px;width:90%;box-shadow:0 24px 48px rgba(0,0,0,.2)">
        <h3 style="font-size:1.1rem;font-weight:700;color:#0f172a;margin-bottom:1rem">
            <i class="bi bi-key-fill me-2" style="color:var(--primary)"></i>Contraseña temporal generada
        </h3>
        <div style="background:#f0fdf4;border:2px solid #bbf7d0;border-radius:.75rem;padding:1rem 1.25rem;margin-bottom:1rem;text-align:center">
            <div style="font-size:1.5rem;font-family:monospace;font-weight:800;letter-spacing:.1em;color:#065f46" id="claveModal">—</div>
        </div>
        <p style="font-size:.82rem;color:#64748b;margin-bottom:1.25rem">
            <i class="bi bi-exclamation-triangle-fill me-1" style="color:#f59e0b"></i>
            Copie esta clave y entréguela al usuario de forma segura. No se podrá recuperar después.
        </p>
        <div class="d-flex gap-2 justify-content-end">
            <button onclick="copiarClave()" class="btn-gl btn-outline-gl">
                <i class="bi bi-clipboard"></i> Copiar
            </button>
            <button onclick="cerrarModal()" class="btn-gl btn-primary-gl">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= htmlspecialchars($csrfToken) ?>';

function desactivar(id, username) {
    if (!confirm('¿Desactivar al usuario @' + username + '?\nNo podrá acceder al sistema.')) return;
    fetch('/usuarios/desactivar/' + id, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({csrf_token: CSRF})
    }).then(r=>r.json()).then(d => {
        if (d.success) location.reload(); else alert(d.message);
    });
}

function resetearClave(id, username) {
    if (!confirm('¿Resetear la contraseña de @' + username + '?\nSe generará una clave temporal.')) return;
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

function desbloquear(id, username) {
    if (!confirm('¿Desbloquear la cuenta de @' + username + '?')) return;
    fetch('/usuarios/desbloquear/' + id, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({csrf_token: CSRF})
    }).then(r=>r.json()).then(d => {
        if (d.success) location.reload(); else alert(d.message);
    });
}

function copiarClave() {
    const txt = document.getElementById('claveModal').textContent;
    navigator.clipboard.writeText(txt).then(() => alert('Copiada al portapapeles'));
}

function cerrarModal() {
    document.getElementById('modalClave').style.display = 'none';
    location.reload();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>