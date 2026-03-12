<?php
/**
 * publica/consulta_resultados.php
 * Vista PÚBLICA — sin sesión, sin menú interno, sin RBAC
 * Acceso vía token QR: /consulta/{token}
 * Variables: $orden (con examenes[].resultados[]), $token
 *
 * NOTA RBAC: Esta vista es completamente pública (rol: ninguno).
 * El token QR actúa como credencial temporal de un solo uso.
 * No afecta ni se integra con RBAC; el acceso lo valida
 * Factura::validarToken() en el controlador antes de llegar aquí.
 */
$o = $orden ?? [];
$paciente = trim(($o['pac_nombres'] ?? '') . ' ' . ($o['pac_apellidos'] ?? ''));
$totalCriticos = 0;
foreach ($o['examenes'] ?? [] as $ex) {
    $totalCriticos += (int)($ex['tiene_criticos'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Laboratorio — GonzaloLabs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #06b6d4;
            --secondary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --font: 'Plus Jakarta Sans', system-ui, sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font); background: #f1f5f9; color: #1e293b; }

        /* ── TOPBAR PÚBLICA ─── */
        .pub-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            padding: 1rem 0; position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 16px rgba(0,0,0,.25);
        }
        .pub-brand { display: flex; align-items: center; gap: .75rem; }
        .pub-brand .icon { width: 36px; height: 36px; border-radius: 9px; background: linear-gradient(135deg,var(--primary),var(--secondary)); display: flex; align-items: center; justify-content: center; }
        .pub-brand h1 { font-size: 1.15rem; font-weight: 800; color: #fff; letter-spacing: -.02em; }
        .pub-brand p  { font-size: .7rem; color: rgba(255,255,255,.45); margin: 0; }

        /* ── HERO ─── */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 2.5rem 0 1.5rem;
            color: #fff;
        }
        .hero-avatar { width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; border: 3px solid rgba(255,255,255,.4); flex-shrink: 0; }
        .hero h2 { font-size: 1.3rem; font-weight: 800; margin: 0; }
        .hero .meta { font-size: .8rem; opacity: .8; margin-top: .25rem; }

        /* ── CARDS ─── */
        .pub-card { background: #fff; border-radius: .875rem; border: 1px solid #e2e8f0; margin-bottom: 1.25rem; overflow: hidden; }
        .pub-card-header { padding: .875rem 1.25rem; background: #f8fafc; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: .75rem; }
        .pub-card-header h3 { font-size: .9rem; font-weight: 700; color: #0f172a; margin: 0; }
        .pub-card-body { padding: 1.25rem; }

        /* ── TABLA RESULTADOS ─── */
        .res-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .83rem; }
        .res-table th { background: #f8fafc; padding: .6rem 1rem; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; border-bottom: 1px solid #e2e8f0; }
        .res-table td { padding: .75rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .res-table tr:last-child td { border-bottom: none; }
        .res-table tr.critico-row { background: #fef9f9; }

        /* ── BADGES ─── */
        .badge-gl { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .55rem; border-radius: 9px; font-size: .72rem; font-weight: 700; }
        .bg-normal  { background: #d1fae5; color: #065f46; }
        .bg-alto    { background: #fef3c7; color: #92400e; }
        .bg-bajo    { background: #fef3c7; color: #92400e; }
        .bg-critico { background: #fee2e2; color: #7f1d1d; animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.7} }

        /* ── FOOTER PÚBLICO ─── */
        .pub-footer { background: #0f172a; color: rgba(255,255,255,.45); text-align: center; padding: 2rem 1rem; font-size: .78rem; margin-top: 2rem; }
        .pub-footer a { color: var(--primary); text-decoration: none; }

        /* ── DISCLAIMER ─── */
        .disclaimer { background: #fffbeb; border: 1px solid #fde68a; border-radius: .625rem; padding: 1rem; font-size: .8rem; color: #78350f; }

        @media print {
            .pub-header, .no-print, .pub-footer { display: none !important; }
            body { background: #fff; }
            .pub-card { box-shadow: none; border: 1px solid #ccc; page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="pub-header">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between">
            <div class="pub-brand">
                <div class="icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
                        <path d="M12 3L20 9V21H4V9L12 3Z"/>
                        <line x1="9" y1="21" x2="9" y2="12"/>
                        <line x1="15" y1="21" x2="15" y2="12"/>
                        <line x1="9" y1="12" x2="15" y2="12"/>
                    </svg>
                </div>
                <div>
                    <h1>GonzaloLabs</h1>
                    <p>Laboratorio Clínico · Resultados confidenciales</p>
                </div>
            </div>
            <button onclick="window.print()" class="no-print"
                style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;padding:.45rem .875rem;border-radius:.5rem;font-family:var(--font);font-size:.8rem;cursor:pointer;display:flex;align-items:center;gap:.4rem">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
    </div>
</header>

<!-- Hero del paciente -->
<div class="hero">
    <div class="container">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="hero-avatar"><?= strtoupper(substr($o['pac_nombres'] ?? 'P', 0, 1)) ?></div>
            <div>
                <h2><?= htmlspecialchars($paciente) ?></h2>
                <div class="meta">
                    Cédula: <?= htmlspecialchars($o['pac_cedula'] ?? '—') ?>
                    <?php if (!empty($o['fecha_nacimiento'])): ?>
                    · <?= (int)date_diff(date_create($o['fecha_nacimiento']),date_create())->y ?> años
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Datos de la orden en chips -->
        <div class="d-flex flex-wrap gap-2" style="font-size:.78rem">
            <?php $chips = [
                ['bi-receipt','N° '  . htmlspecialchars($o['numero_orden'] ?? '—')],
                ['bi-calendar3', !empty($o['fecha_orden']) ? date('d/m/Y', strtotime($o['fecha_orden'])) : '—'],
                ['bi-person-badge', 'Médico: ' . htmlspecialchars($o['medico_nombre'] ?? 'No indicado')],
            ]; foreach ($chips as [$icon, $txt]): ?>
            <span style="background:rgba(255,255,255,.15);padding:.3rem .7rem;border-radius:9px;display:inline-flex;align-items:center;gap:.3rem">
                <i class="bi <?= $icon ?>"></i> <?= $txt ?>
            </span>
            <?php endforeach; ?>
            <?php if ($totalCriticos > 0): ?>
            <span style="background:#ef4444;padding:.3rem .7rem;border-radius:9px;display:inline-flex;align-items:center;gap:.3rem;font-weight:700;animation:pulse 2s infinite">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $totalCriticos ?> valor<?= $totalCriticos > 1 ? 'es' : '' ?> crítico<?= $totalCriticos > 1 ? 's' : '' ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Contenido principal -->
<div class="container" style="padding-top:1.5rem;padding-bottom:2rem">

    <!-- Aviso valores críticos -->
    <?php if ($totalCriticos > 0): ?>
    <div style="background:#fef2f2;border:2px solid #fca5a5;border-radius:.875rem;padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:flex-start;gap:.875rem">
        <i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;font-size:1.3rem;flex-shrink:0;margin-top:.1rem"></i>
        <div>
            <div style="font-weight:700;color:#7f1d1d;font-size:.93rem">Se detectaron valores fuera del rango crítico</div>
            <div style="font-size:.82rem;color:#7f1d1d;margin-top:.25rem">
                Comuníquese con su médico tratante de inmediato. Los valores marcados en rojo requieren atención prioritaria.
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Resultados por examen -->
    <?php foreach ($o['examenes'] ?? [] as $ex): ?>
    <div class="pub-card" style="border-left:4px solid <?= ($ex['tiene_criticos'] ?? false) ? '#ef4444' : '#10b981' ?>">
        <div class="pub-card-header">
            <div style="width:34px;height:34px;border-radius:.5rem;background:<?= ($ex['tiene_criticos'] ?? false) ? '#fee2e2' : '#d1fae5' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi bi-flask" style="color:<?= ($ex['tiene_criticos'] ?? false) ? '#dc2626' : '#059669' ?>"></i>
            </div>
            <div>
                <h3><?= htmlspecialchars($ex['nombre_examen'] ?? '—') ?></h3>
                <div style="font-size:.72rem;color:#94a3b8">
                    <?= htmlspecialchars($ex['codigo'] ?? '') ?>
                    <?php if (!empty($ex['metodo_analisis'])): ?>
                    · <?= htmlspecialchars($ex['metodo_analisis']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($ex['resultados'])): ?>
        <div style="overflow-x:auto">
            <table class="res-table">
                <thead>
                    <tr>
                        <th>Parámetro</th>
                        <th>Resultado</th>
                        <th>Unidad</th>
                        <th>Rango de referencia</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ex['resultados'] as $r): ?>
                <?php
                    $val   = $r['valor_resultado'] ?? '—';
                    $minN  = $r['valor_min_normal'] ?? null;
                    $maxN  = $r['valor_max_normal'] ?? null;
                    $esCrit = (bool)($r['es_critico'] ?? false);
                    $numVal = is_numeric($val) ? (float)$val : null;
                    $est = 'normal';
                    if ($esCrit) $est = 'critico';
                    elseif ($numVal !== null) {
                        if ($minN !== null && $numVal < (float)$minN) $est = 'bajo';
                        elseif ($maxN !== null && $numVal > (float)$maxN) $est = 'alto';
                    }
                    $colorVal = ['normal'=>'#0f172a','bajo'=>'#d97706','alto'=>'#d97706','critico'=>'#dc2626'][$est];
                    $labelMap = ['normal'=>'Normal','bajo'=>'Bajo ↓','alto'=>'Alto ↑','critico'=>'⚠ CRÍTICO'][$est];
                    $badgeMap = ['normal'=>'bg-normal','bajo'=>'bg-bajo','alto'=>'bg-alto','critico'=>'bg-critico'][$est];
                ?>
                <tr class="<?= $esCrit ? 'critico-row' : '' ?>">
                    <td class="fw-semibold"><?= htmlspecialchars($r['nombre_parametro'] ?? '—') ?></td>
                    <td>
                        <span style="font-size:1rem;font-weight:800;color:<?= $colorVal ?>">
                            <?= htmlspecialchars($val) ?>
                        </span>
                    </td>
                    <td style="color:#64748b"><?= htmlspecialchars($r['unidad_medida'] ?? '') ?></td>
                    <td style="font-size:.78rem;color:#64748b">
                        <?php
                        if ($minN !== null && $maxN !== null) echo $minN . ' – ' . $maxN;
                        elseif ($minN !== null) echo '≥ ' . $minN;
                        elseif ($maxN !== null) echo '≤ ' . $maxN;
                        else echo 'Ver con su médico';
                        ?>
                    </td>
                    <td><span class="badge-gl <?= $badgeMap ?>"><?= $labelMap ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="pub-card-body text-center" style="color:#94a3b8;font-size:.83rem">
            Resultados no disponibles para este examen
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Disclaimer -->
    <div class="disclaimer">
        <div style="font-weight:700;margin-bottom:.35rem"><i class="bi bi-info-circle-fill me-1"></i>Importante</div>
        Este informe es de carácter informativo. Los resultados deben ser interpretados por un profesional de la salud en el contexto de la historia clínica del paciente. No utilice estos resultados para autodiagnosticarse o modificar tratamientos sin consultar a su médico.
        <div style="margin-top:.5rem;color:#92400e">
            <i class="bi bi-lock-fill me-1"></i>Este enlace es personal e intransferible. Generado el <?= date('d/m/Y H:i') ?>.
        </div>
    </div>

</div><!-- /container -->

<footer class="pub-footer">
    <div>
        <strong style="color:rgba(255,255,255,.7)">GonzaloLabs</strong> — Sistema de Laboratorio Clínico
    </div>
    <div style="margin-top:.35rem">
        Para consultas o aclaraciones comuníquese con el laboratorio ·
        <a href="tel:">+593 00 000-0000</a>
    </div>
</footer>

</body>
</html>