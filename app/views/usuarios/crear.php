<?php
// Variables: $rolesDisponibles, $menuNav, $nombreUsuario, $csrfToken, $flash
$pageTitle   = 'Nuevo Usuario';
$breadcrumbs = [['label'=>'Usuarios','url'=>'/usuarios'],['label'=>'Nuevo usuario']];
require_once __DIR__ . '/../layouts/header.php';

$rolInfo = [
    'administrador' => ['Administrador', '#7c3aed', 'bi-shield-check',    'Acceso total al sistema. Puede gestionar usuarios, configuración y ver todos los reportes.'],
    'analistaL'     => ['Analista Lab',  '#0891b2', 'bi-eyedropper',      'Puede crear órdenes, cargar resultados, gestionar pacientes y cotizaciones.'],
    'medico'        => ['Médico',        '#059669', 'bi-heart-pulse',      'Solo lectura de resultados de sus propios pacientes. No puede crear órdenes.'],
    'paciente'      => ['Paciente',      '#d97706', 'bi-person-fill',      'Acceso solo al portal de resultados propios mediante QR o login.'],
];
?>

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="/usuarios" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
    <div>
        <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0">Crear nuevo usuario</h1>
        <p style="font-size:.82rem;color:#64748b;margin:0">Los datos sensibles se cifran antes de guardarse</p>
    </div>
</div>

<form method="POST" action="/usuarios/guardar" id="formUsuario" novalidate>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

<div class="row g-4">
    <div class="col-lg-8">

        <!-- Datos de acceso -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-person-badge" style="color:var(--primary);font-size:1.05rem"></i>
                <h5>Datos de acceso</h5>
            </div>
            <div class="gl-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="gl-label">Nombre de usuario <span style="color:#ef4444">*</span>
                            <span id="usernameStatus" style="font-size:.72rem;margin-left:.3rem"></span>
                        </label>
                        <input type="text" name="username" id="username" class="gl-input" required
                            placeholder="min. 4 caracteres, sin espacios"
                            pattern="[a-zA-Z0-9_\-]{4,30}"
                            oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9_\-]/g,'');verificarUsername(this.value)">
                    </div>
                    <div class="col-md-6">
                        <label class="gl-label">Email <span style="color:#ef4444">*</span></label>
                        <input type="email" name="email" class="gl-input" required placeholder="correo@dominio.com">
                    </div>
                    <div class="col-12">
                        <label class="gl-label">Nombre completo <span style="color:#ef4444">*</span></label>
                        <input type="text" name="nombre_completo" class="gl-input" required
                            placeholder="Nombre(s) y Apellido(s) completos">
                    </div>
                    <div class="col-md-6">
                        <label class="gl-label">Cédula <span style="font-size:.72rem;color:#94a3b8">(opcional, para vinculación con paciente)</span></label>
                        <input type="text" name="cedula" class="gl-input" maxlength="10" placeholder="0000000000">
                    </div>
                </div>
            </div>
        </div>

        <!-- Contraseña -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-key" style="color:#f59e0b;font-size:1.05rem"></i>
                <h5>Contraseña</h5>
            </div>
            <div class="gl-card-body">
                <div class="gl-alert gl-alert-info mb-3" style="font-size:.8rem">
                    <i class="bi bi-info-circle" style="flex-shrink:0"></i>
                    Si deja los campos vacíos, se generará una <strong>contraseña temporal aleatoria</strong> que deberá entregar al usuario de forma segura.
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="gl-label">Contraseña</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="passInput" class="gl-input" style="padding-right:2.5rem"
                                placeholder="Min. 8 caracteres" autocomplete="new-password"
                                oninput="evalFortaleza(this.value)">
                            <button type="button" onclick="toggleVer('passInput')" style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0">
                                <i class="bi bi-eye" id="ojoPass"></i>
                            </button>
                        </div>
                        <!-- Barra de fortaleza -->
                        <div style="margin-top:.5rem">
                            <div style="height:4px;border-radius:2px;background:#f1f5f9;overflow:hidden">
                                <div id="barraFuerza" style="height:100%;width:0%;transition:all .3s;border-radius:2px"></div>
                            </div>
                            <div id="textoFuerza" style="font-size:.72rem;color:#94a3b8;margin-top:.2rem"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="gl-label">Confirmar contraseña</label>
                        <div class="position-relative">
                            <input type="password" name="confirmar_password" id="passConf" class="gl-input" style="padding-right:2.5rem"
                                placeholder="Repetir contraseña" autocomplete="new-password"
                                oninput="checkCoincide()">
                            <button type="button" onclick="toggleVer('passConf')" style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;padding:0">
                                <i class="bi bi-eye" id="ojoConf"></i>
                            </button>
                        </div>
                        <div id="msgCoincide" style="font-size:.72rem;margin-top:.3rem"></div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /col-lg-8 -->

    <div class="col-lg-4">

        <!-- Selector de rol -->
        <div class="gl-card mb-4">
            <div class="gl-card-header">
                <i class="bi bi-shield-lock" style="color:#8b5cf6;font-size:1.05rem"></i>
                <h5>Rol del sistema</h5>
            </div>
            <div class="gl-card-body" style="padding:.75rem">
                <input type="hidden" name="rol" id="rolInput" value="analistaL">
                <?php foreach ($rolInfo as $valor => [$nombre, $color, $icon, $desc]): ?>
                <label class="rol-card d-flex align-items-start gap-2 p-3 mb-2 rounded"
                       style="cursor:pointer;border:2px solid <?= $valor === 'analistaL' ? $color : '#f1f5f9' ?>;background:<?= $valor === 'analistaL' ? $color.'11' : '#f8fafc' ?>;transition:all .2s"
                       onclick="elegirRol('<?= $valor ?>', '<?= $color ?>', this)">
                    <div style="width:32px;height:32px;border-radius:.5rem;background:<?= $color ?>22;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.1rem">
                        <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:.9rem"></i>
                    </div>
                    <div>
                        <div style="font-size:.85rem;font-weight:700;color:#0f172a"><?= $nombre ?></div>
                        <div style="font-size:.73rem;color:#64748b;margin-top:.15rem;line-height:1.4"><?= $desc ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Acciones -->
        <div class="d-flex flex-column gap-2">
            <button type="submit" class="btn-gl btn-primary-gl" style="justify-content:center;padding:.7rem">
                <i class="bi bi-check-lg"></i> Crear usuario
            </button>
            <a href="/usuarios" class="btn-gl btn-outline-gl" style="justify-content:center">Cancelar</a>
        </div>
    </div>
</div>
</form>

<script>
function elegirRol(valor, color, el) {
    document.getElementById('rolInput').value = valor;
    document.querySelectorAll('.rol-card').forEach(c => {
        c.style.borderColor = '#f1f5f9';
        c.style.background  = '#f8fafc';
    });
    el.style.borderColor = color;
    el.style.background  = color + '11';
}

let debUser;
function verificarUsername(val) {
    const st = document.getElementById('usernameStatus');
    if (!val || val.length < 4) { st.textContent = ''; return; }
    clearTimeout(debUser);
    debUser = setTimeout(() => {
        // Verificación visual básica (el server valida en submit)
        st.textContent = val.length >= 4 ? '' : '✗ Muy corto';
    }, 300);
}

function evalFortaleza(val) {
    const barra = document.getElementById('barraFuerza');
    const texto = document.getElementById('textoFuerza');
    if (!val) { barra.style.width = '0'; texto.textContent = ''; return; }
    let score = 0;
    if (val.length >= 8)                    score++;
    if (val.length >= 12)                   score++;
    if (/[A-Z]/.test(val))                  score++;
    if (/[0-9]/.test(val))                  score++;
    if (/[^a-zA-Z0-9]/.test(val))          score++;
    const niveles = [
        [20, '#ef4444', 'Muy débil'],
        [40, '#f97316', 'Débil'],
        [60, '#f59e0b', 'Regular'],
        [80, '#84cc16', 'Buena'],
        [100,'#10b981', 'Muy fuerte'],
    ];
    const [w, c, l] = niveles[Math.min(score, 4)];
    barra.style.width = w + '%';
    barra.style.background = c;
    texto.textContent = l;
    texto.style.color = c;
}

function checkCoincide() {
    const a = document.getElementById('passInput').value;
    const b = document.getElementById('passConf').value;
    const msg = document.getElementById('msgCoincide');
    if (!b) { msg.textContent = ''; return; }
    if (a === b) {
        msg.textContent = '✓ Coinciden'; msg.style.color = '#10b981';
    } else {
        msg.textContent = '✗ No coinciden'; msg.style.color = '#ef4444';
    }
}

function toggleVer(id) {
    const inp = document.getElementById(id);
    const ico = document.getElementById(id === 'passInput' ? 'ojoPass' : 'ojoConf');
    if (inp.type === 'password') {
        inp.type = 'text'; ico.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password'; ico.className = 'bi bi-eye';
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>