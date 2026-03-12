<?php
$pageTitle = 'Historial — ' . trim(($paciente['nombres']??'') . ' ' . ($paciente['apellidos']??''));
require_once __DIR__ . '/../layouts/header.php';

$estadoBadge = [
    'creada'              => ['badge-info',    'Creada'],
    'en_proceso'          => ['badge-warning', 'En proceso'],
    'resultados_cargados' => ['badge-purple',  'Resultados listos'],
    'validada'            => ['badge-teal',    'Validada'],
    'publicada'           => ['badge-success', 'Publicada'],
    'cancelada'           => ['badge-danger',  'Cancelada'],
];
$p = $paciente ?? [];
?>

<!-- Cabecera -->
<div class="d-flex align-items-start justify-content-between mb-4 flex-wrap gap-3">
    <div class="d-flex align-items-center gap-3">
        <a href="/pacientes" class="btn-gl btn-outline-gl btn-sm-gl"><i class="bi bi-arrow-left"></i></a>
        <div class="d-flex align-items-center gap-3">
            <div style="width:52px;height:52px;border-radius:50%;
                background:linear-gradient(135deg,var(--primary),var(--secondary));
                display:flex;align-items:center;justify-content:center;
                color:#fff;font-size:1.25rem;font-weight:800;flex-shrink:0">
                <?= mb_strtoupper(mb_substr($p['nombres']??'P', 0, 1)) ?>
            </div>
            <div>
                <h1 style="font-size:1.35rem;font-weight:800;color:#0f172a;margin:0">
                    <?= htmlspecialchars(trim(($p['nombres']??'').' '.($p['apellidos']??''))) ?>
                </h1>
                <div class="d-flex gap-2 align-items-center mt-1 flex-wrap" style="font-size:.78rem;color:#64748b">
                    <span><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($p['cedula']??'—') ?></span>
                    <?php if (!empty($p['fecha_nacimiento'])): ?>
                    <span>· <?= date('d/m/Y', strtotime($p['fecha_nacimiento'])) ?> (<?= floor((time()-strtotime($p['fecha_nacimiento']))/31557600) ?> años)</span>
                    <?php endif; ?>
                    <?php if (!empty($p['genero'])): ?>
                    <span>· <?= ['M'=>'Masculino','F'=>'Femenino','O'=>'Otro'][$p['genero']]??$p['genero'] ?></span>
                    <?php endif; ?>
                    <?php if (!empty($p['tipo_sangre'])): ?>
                    <span class="gl-badge badge-danger"><?= $p['tipo_sangre'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <?php if (\RBAC::puede('pacientes.editar')): ?>
        <a href="/pacientes/editar/<?= $p['id_paciente'] ?>" class="btn-gl btn-outline-gl">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <?php endif; ?>
        <?php if (\RBAC::puede('ordenes.crear')): ?>
        <a href="/ordenes/crear?id_paciente=<?= $p['id_paciente'] ?>" class="btn-gl btn-primary-gl">
            <i class="bi bi-clipboard2-plus"></i> Nueva orden
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">

    <!-- Historial de órdenes -->
    <div class="col-lg-8">
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-clock-history" style="color:var(--primary)"></i>
                <h5>Historial de órdenes</h5>
                <span class="gl-badge badge-info ms-auto"><?= count($ordenes ?? []) ?> orden(es)</span>
            </div>

            <?php if (empty($ordenes)): ?>
            <div class="gl-card-body" style="text-align:center;color:#94a3b8;padding:2.5rem">
                <i class="bi bi-clipboard2-x" style="font-size:2.5rem;display:block;margin-bottom:.6rem;opacity:.3"></i>
                <p style="font-size:.9rem;margin:0">Este paciente no tiene órdenes registradas</p>
                <?php if (\RBAC::puede('ordenes.crear')): ?>
                <a href="/ordenes/crear?id_paciente=<?= $p['id_paciente'] ?>" class="btn-gl btn-primary-gl mt-3" style="display:inline-flex">
                    <i class="bi bi-clipboard2-plus"></i> Crear primera orden
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>

            <!-- Línea de tiempo -->
            <div class="gl-card-body" style="padding:.75rem 1.1rem">
                <?php foreach ($ordenes as $o): ?>
                <?php
                [$badgeCls, $badgeLbl] = $estadoBadge[$o['estado']??''] ?? ['badge-gray', ucfirst($o['estado']??'')];
                $criticos = (int)($o['criticos'] ?? 0);
                ?>
                <div class="d-flex gap-3 mb-3 pb-3" style="border-bottom:1px solid #f1f5f9">
                    <!-- Icono estado -->
                    <div style="flex-shrink:0;display:flex;flex-direction:column;align-items:center">
                        <div style="width:34px;height:34px;border-radius:50%;
                            background:<?= $o['estado']==='publicada'?'#d1fae5':($o['estado']==='cancelada'?'#fee2e2':'#dbeafe') ?>;
                            display:flex;align-items:center;justify-content:center;font-size:.9rem">
                            <i class="bi bi-<?= $o['estado']==='publicada'?'check-circle-fill':'clipboard2-pulse' ?>"
                               style="color:<?= $o['estado']==='publicada'?'#059669':($o['estado']==='cancelada'?'#dc2626':'#3b82f6') ?>"></i>
                        </div>
                    </div>

                    <div class="flex-fill">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <a href="/ordenes/validar/<?= $o['id_orden'] ?>"
                                   style="font-weight:700;font-family:monospace;font-size:.85rem;color:var(--primary);text-decoration:none">
                                    <?= htmlspecialchars($o['numero_orden'] ?? '') ?>
                                </a>
                                <span class="gl-badge <?= $badgeCls ?>"><?= $badgeLbl ?></span>
                                <?php if ($criticos > 0): ?>
                                <span class="critico-badge"><i class="bi bi-exclamation-triangle-fill"></i><?= $criticos ?>⚠</span>
                                <?php endif; ?>
                            </div>
                            <span style="font-size:.75rem;color:#94a3b8">
                                <?= !empty($o['fecha_orden']) ? date('d/m/Y H:i', strtotime($o['fecha_orden'])) : '—' ?>
                            </span>
                        </div>

                        <!-- Exámenes de la orden -->
                        <div class="d-flex flex-wrap gap-1 mb-1">
                            <?php foreach ($o['examenes'] ?? [] as $exNom): ?>
                            <span class="gl-badge badge-gray" style="font-size:.68rem"><?= htmlspecialchars($exNom) ?></span>
                            <?php endforeach; ?>
                            <?php if (!empty($o['total_examenes']) && empty($o['examenes'])): ?>
                            <span class="gl-badge badge-info" style="font-size:.68rem"><?= $o['total_examenes'] ?> examen(es)</span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex align-items-center gap-3" style="font-size:.78rem;color:#64748b">
                            <span><i class="bi bi-cash me-1"></i>$<?= number_format((float)($o['total_pagar']??0),2) ?></span>
                            <?php if (!empty($o['medico_nombre'])): ?>
                            <span><i class="bi bi-person me-1"></i><?= htmlspecialchars(mb_substr($o['medico_nombre'],0,20)) ?></span>
                            <?php endif; ?>
                            <?php if ($o['estado']==='publicada' && !empty($o['token_qr'])): ?>
                            <a href="/consulta/<?= $o['token_qr'] ?>" target="_blank" style="color:var(--primary);font-weight:600">
                                <i class="bi bi-qr-code me-1"></i>Ver QR
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar: datos del paciente -->
    <div class="col-lg-4">
        <div class="gl-card mb-3">
            <div class="gl-card-header">
                <i class="bi bi-telephone" style="color:var(--primary)"></i>
                <h5>Contacto</h5>
            </div>
            <div class="gl-card-body" style="font-size:.84rem;color:#374151;line-height:2">
                <?php if (!empty($p['telefono'])): ?>
                <div><i class="bi bi-phone me-2 text-muted"></i><?= htmlspecialchars($p['telefono']) ?></div>
                <?php endif; ?>
                <?php if (!empty($p['telefono_secundario'])): ?>
                <div><i class="bi bi-telephone me-2 text-muted"></i><?= htmlspecialchars($p['telefono_secundario']) ?></div>
                <?php endif; ?>
                <?php if (!empty($p['email'])): ?>
                <div><i class="bi bi-envelope me-2 text-muted"></i><?= htmlspecialchars($p['email']) ?></div>
                <?php endif; ?>
                <?php if (!empty($p['direccion'])): ?>
                <div><i class="bi bi-geo-alt me-2 text-muted"></i><?= htmlspecialchars($p['direccion']) ?></div>
                <?php endif; ?>
                <?php if (empty($p['telefono']) && empty($p['email'])): ?>
                <p style="color:#94a3b8;font-size:.82rem">Sin contacto registrado</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($p['alergias']) || !empty($p['medicamentos_actuales'])): ?>
        <div class="gl-card mb-3" style="border-left:3px solid #ef4444">
            <div class="gl-card-header">
                <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444"></i>
                <h5 style="color:#dc2626">Alertas médicas</h5>
            </div>
            <div class="gl-card-body" style="font-size:.845rem">
                <?php if (!empty($p['alergias'])): ?>
                <div class="mb-2">
                    <div class="gl-label" style="color:#dc2626;margin-bottom:.2rem">Alergias</div>
                    <div><?= nl2br(htmlspecialchars($p['alergias'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($p['medicamentos_actuales'])): ?>
                <div>
                    <div class="gl-label" style="margin-bottom:.2rem">Medicación actual</div>
                    <div style="color:#64748b"><?= nl2br(htmlspecialchars($p['medicamentos_actuales'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Resumen estadístico -->
        <div class="gl-card">
            <div class="gl-card-header">
                <i class="bi bi-bar-chart" style="color:var(--primary)"></i>
                <h5>Estadísticas</h5>
            </div>
            <div class="gl-card-body">
                <?php
                $total     = count($ordenes ?? []);
                $publicadas = count(array_filter($ordenes ?? [], fn($o) => $o['estado'] === 'publicada'));
                $criticos   = array_sum(array_column($ordenes ?? [], 'criticos'));
                ?>
                <div class="row g-2">
                    <div class="col-4 text-center">
                        <div style="font-size:1.4rem;font-weight:800;color:var(--primary)"><?= $total ?></div>
                        <div style="font-size:.7rem;color:#64748b">Total</div>
                    </div>
                    <div class="col-4 text-center">
                        <div style="font-size:1.4rem;font-weight:800;color:#10b981"><?= $publicadas ?></div>
                        <div style="font-size:.7rem;color:#64748b">Publicadas</div>
                    </div>
                    <div class="col-4 text-center">
                        <div style="font-size:1.4rem;font-weight:800;color:#ef4444"><?= $criticos ?></div>
                        <div style="font-size:.7rem;color:#64748b">Críticos</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>