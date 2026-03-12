<?php
/* ============================================
   LAYOUT HEADER — GonzaloLabs
   Incluir al inicio de cada vista interna:
   Variables esperadas: $menuNav, $nombreUsuario, $pageTitle (opcional)
   ============================================ */
$pageTitle = $pageTitle ?? 'GonzaloLabs';
$rol = $_SESSION['user_rol'] ?? '';
$rolLabel = [
    'administrador' => ['Administrador', '#7c3aed'],
    'analistaL'     => ['Analista',       '#0891b2'],
    'medico'        => ['Médico',          '#059669'],
    'paciente'      => ['Paciente',        '#d97706'],
][$rol] ?? ['Usuario', '#6b7280'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — GonzaloLabs</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Fuente -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:       #06b6d4;
            --primary-dark:  #0891b2;
            --secondary:     #3b82f6;
            --accent:        #8b5cf6;
            --success:       #10b981;
            --warning:       #f59e0b;
            --danger:        #ef4444;
            --sidebar-w:     260px;
            --sidebar-bg:    #0f172a;
            --sidebar-hover: rgba(6,182,212,.12);
            --sidebar-active:rgba(6,182,212,.18);
            --topbar-h:      60px;
            --font:         'Plus Jakarta Sans', system-ui, sans-serif;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font); background: #f1f5f9; color: #1e293b; overflow-x: hidden; }

        /* ── SIDEBAR ─────────────────────────── */
        #sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            display: flex; flex-direction: column;
            z-index: 1040; transition: transform .3s ease;
        }
        .sidebar-brand {
            display: flex; align-items: center; gap: .75rem;
            padding: 1.25rem 1.25rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .sidebar-brand .brand-icon {
            width: 36px; height: 36px; border-radius: 9px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .sidebar-brand h2 { font-size: 1.1rem; font-weight: 700; color: #fff; letter-spacing: -.02em; }
        .sidebar-brand p  { font-size: .7rem; color: rgba(255,255,255,.4); }

        .sidebar-nav { flex: 1; overflow-y: auto; padding: .75rem 0; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.1) transparent; }
        .nav-section { padding: .5rem 1.25rem .3rem; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: rgba(255,255,255,.3); }
        .sidebar-link {
            display: flex; align-items: center; gap: .75rem;
            padding: .6rem 1.25rem; color: rgba(255,255,255,.6);
            text-decoration: none; font-size: .855rem; font-weight: 500;
            border-radius: 0; transition: all .2s; position: relative;
            margin: .1rem 0;
        }
        .sidebar-link:hover  { color: #fff; background: var(--sidebar-hover); }
        .sidebar-link.active { color: var(--primary); background: var(--sidebar-active); font-weight: 600; }
        .sidebar-link.active::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 3px; background: var(--primary); border-radius: 0 2px 2px 0;
        }
        .sidebar-link i { font-size: 1.05rem; width: 20px; text-align: center; }
        .badge-nav { margin-left: auto; font-size: .65rem; padding: .2rem .45rem; border-radius: 9px; }

        .sidebar-footer {
            padding: .875rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,.07);
        }
        .user-card {
            display: flex; align-items: center; gap: .75rem;
            padding: .6rem; border-radius: .5rem;
            background: rgba(255,255,255,.05); cursor: pointer;
        }
        .user-avatar {
            width: 32px; height: 32px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: .8rem; font-weight: 700; flex-shrink: 0;
        }
        .user-name  { font-size: .8rem; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
        .user-role  { font-size: .68rem; border-radius: 9px; padding: .1rem .4rem; }

        /* ── TOPBAR ─────────────────────────── */
        #topbar {
            position: fixed; top: 0; left: var(--sidebar-w); right: 0;
            height: var(--topbar-h); background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center;
            padding: 0 1.5rem; gap: 1rem; z-index: 1030;
        }
        #topbar .page-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; flex: 1; }
        .topbar-btn {
            width: 36px; height: 36px; border-radius: .5rem;
            border: 1px solid #e2e8f0; background: #fff;
            display: flex; align-items: center; justify-content: center;
            color: #64748b; cursor: pointer; transition: all .2s;
        }
        .topbar-btn:hover { background: #f8fafc; color: var(--primary); border-color: var(--primary); }

        /* ── MAIN CONTENT ─────────────────── */
        #main { margin-left: var(--sidebar-w); padding-top: var(--topbar-h); min-height: 100vh; }
        .page-content { padding: 1.75rem; }

        /* ── CARDS ────────────────────────── */
        .gl-card { background: #fff; border-radius: .875rem; border: 1px solid #e2e8f0; }
        .gl-card-header { padding: 1.1rem 1.25rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: .75rem; }
        .gl-card-header h5 { font-size: .925rem; font-weight: 700; color: #0f172a; margin: 0; }
        .gl-card-body { padding: 1.25rem; }

        /* ── STAT CARDS ───────────────────── */
        .stat-card { background: #fff; border-radius: .875rem; border: 1px solid #e2e8f0; padding: 1.25rem; display: flex; align-items: center; gap: 1rem; }
        .stat-icon { width: 48px; height: 48px; border-radius: .75rem; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
        .stat-value { font-size: 1.6rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .stat-label { font-size: .78rem; color: #64748b; margin-top: .2rem; }

        /* ── ALERTS FLASH ─────────────────── */
        .gl-alert { display: flex; align-items: center; gap: .75rem; padding: .875rem 1rem; border-radius: .625rem; font-size: .875rem; margin-bottom: 1.25rem; }
        .gl-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #065f46; }
        .gl-alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #7f1d1d; }
        .gl-alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #78350f; }
        .gl-alert-info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }

        /* ── FORMS ────────────────────────── */
        .gl-label { font-size: .8rem; font-weight: 600; color: #374151; margin-bottom: .35rem; }
        .gl-input { border: 1px solid #d1d5db; border-radius: .5rem; padding: .55rem .875rem; font-size: .875rem; color: #1e293b; width: 100%; font-family: var(--font); transition: border .2s, box-shadow .2s; background: #fff; }
        .gl-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(6,182,212,.12); }
        .gl-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right .7rem center; padding-right: 2rem; }

        /* ── BADGES ───────────────────────── */
        .gl-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .6rem; border-radius: 9px; font-size: .72rem; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger  { background: #fee2e2; color: #7f1d1d; }
        .badge-info    { background: #dbeafe; color: #1e3a8a; }
        .badge-purple  { background: #ede9fe; color: #4c1d95; }
        .badge-gray    { background: #f1f5f9; color: #475569; }

        /* ── TABLES ───────────────────────── */
        .gl-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .855rem; }
        .gl-table thead th { background: #f8fafc; padding: .75rem 1rem; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; border-bottom: 1px solid #e2e8f0; }
        .gl-table tbody tr { transition: background .15s; }
        .gl-table tbody tr:hover { background: #f8fafc; }
        .gl-table tbody td { padding: .875rem 1rem; border-bottom: 1px solid #f1f5f9; color: #374151; vertical-align: middle; }

        /* ── BUTTONS ──────────────────────── */
        .btn-gl { display: inline-flex; align-items: center; gap: .4rem; padding: .55rem 1rem; border-radius: .5rem; font-size: .855rem; font-weight: 600; cursor: pointer; border: none; transition: all .2s; font-family: var(--font); text-decoration: none; }
        .btn-primary-gl { background: linear-gradient(135deg,var(--primary),var(--secondary)); color: #fff; }
        .btn-primary-gl:hover { opacity: .9; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(6,182,212,.3); color: #fff; }
        .btn-outline-gl { background: transparent; border: 1px solid #d1d5db; color: #374151; }
        .btn-outline-gl:hover { background: #f8fafc; border-color: var(--primary); color: var(--primary); }
        .btn-danger-gl { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
        .btn-danger-gl:hover { background: #fecaca; }
        .btn-sm-gl { padding: .35rem .7rem; font-size: .78rem; }

        /* ── RESPONSIVE ───────────────────── */
        @media (max-width: 768px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #topbar, #main { left: 0; margin-left: 0; }
        }

        /* ── CRÍTICOS ─────────────────────── */
        .critico-badge { display: inline-flex; align-items: center; gap: .3rem; background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; padding: .2rem .55rem; border-radius: 9px; font-size: .72rem; font-weight: 700; animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 0%,100%{opacity:1} 50%{opacity:.7} }

        /* ── PRINT ────────────────────────── */
        @media print { #sidebar, #topbar { display: none !important; } #main { margin: 0; padding: 0; } }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M12 3L20 9V21H4V9L12 3Z"/><path d="M12 12V21M8 12H16"/></svg>
        </div>
        <div>
            <h2>GonzaloLabs</h2>
            <p>Sistema Clínico</p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if (!empty($menuNav)): ?>
            <?php foreach ($menuNav as $item): ?>
                <?php if ($item['type'] === 'section'): ?>
                    <div class="nav-section"><?= htmlspecialchars($item['label']) ?></div>
                <?php else: ?>
                    <?php
                        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                        $isActive = str_starts_with($currentPath, $item['url']);
                    ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>" class="sidebar-link <?= $isActive ? 'active' : '' ?>">
                        <i class="bi bi-<?= htmlspecialchars($item['icon'] ?? 'circle') ?>"></i>
                        <?= htmlspecialchars($item['label']) ?>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="badge-nav badge bg-danger"><?= $item['badge'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="nav-section">Navegación</div>
            <a href="/dashboard" class="sidebar-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="/ordenes" class="sidebar-link"><i class="bi bi-clipboard-pulse"></i> Órdenes</a>
            <a href="/pacientes" class="sidebar-link"><i class="bi bi-people"></i> Pacientes</a>
            <a href="/examenes" class="sidebar-link"><i class="bi bi-flask"></i> Exámenes</a>
            <a href="/resultados" class="sidebar-link"><i class="bi bi-graph-up"></i> Resultados</a>
            <a href="/cotizaciones" class="sidebar-link"><i class="bi bi-receipt"></i> Cotizaciones</a>
            <a href="/facturas" class="sidebar-link"><i class="bi bi-qr-code"></i> Facturas/QR</a>
            <a href="/reportes" class="sidebar-link"><i class="bi bi-bar-chart"></i> Reportes</a>
            <a href="/usuarios" class="sidebar-link"><i class="bi bi-person-gear"></i> Usuarios</a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper(substr($nombreUsuario ?? 'U', 0, 1)) ?></div>
            <div style="min-width:0;flex:1">
                <div class="user-name"><?= htmlspecialchars($nombreUsuario ?? 'Usuario') ?></div>
                <span class="user-role gl-badge" style="background:<?= $rolLabel[1] ?>22;color:<?= $rolLabel[1] ?>"><?= $rolLabel[0] ?></span>
            </div>
            <a href="/logout" title="Cerrar sesión" style="color:rgba(255,255,255,.4);font-size:1rem">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</aside>

<!-- TOPBAR -->
<header id="topbar">
    <button class="topbar-btn d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
        <i class="bi bi-list" style="font-size:1.2rem"></i>
    </button>
    <span class="page-title"><?= htmlspecialchars($pageTitle ?? 'GonzaloLabs') ?></span>

    <!-- Flash message inline -->
    <?php if (!empty($flash['message'])): ?>
    <div class="gl-alert gl-alert-<?= htmlspecialchars($flash['type'] ?? 'info') ?>" style="margin:0;padding:.5rem .875rem;font-size:.8rem">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <div class="d-flex align-items-center gap-2">
        <a href="/resultados/alertas" class="topbar-btn" title="Alertas críticas">
            <i class="bi bi-bell"></i>
        </a>
        <a href="/logout" class="topbar-btn" title="Cerrar sesión">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</header>

<!-- MAIN CONTENT WRAPPER -->
<main id="main">
<div class="page-content">