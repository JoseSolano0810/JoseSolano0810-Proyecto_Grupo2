<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odent | Portal del paciente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/estilos.css?v=<?= @filemtime(ROOT_PATH . '/public/css/estilos.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/asistente.css?v=<?= @filemtime(ROOT_PATH . '/public/css/asistente.css') ?>">
</head>
<body>

<?php

$rol           = $rol           ?? 'paciente';
$usuario       = $usuario       ?? ['nombre' => '', 'iniciales' => ''];
$pagina_activa = $pagina_activa ?? 'inicio';

// Citas reales (CitaController::listarMisCitas / proximaCita)
$proxima_cita = $proxima_cita ?? null;
$mis_citas    = $mis_citas    ?? [];

// Tratamientos reales del paciente (el controlador ya filtra por el paciente en sesión)
$mis_tratamientos = $mis_tratamientos ?? [];
$etiquetas_trat   = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado'];

// PAC-07: los finalizados van a su propia sección (historial); "Mis tratamientos" queda con los vigentes
$trat_activos     = array_values(array_filter($mis_tratamientos, fn($t) => $t['estado'] !== 'finalizado'));
$trat_finalizados = array_values(array_filter($mis_tratamientos, fn($t) => $t['estado'] === 'finalizado'));
usort($trat_finalizados, fn($a, $b) => strcmp((string) ($b['fecha_fin_real'] ?: $b['fecha_inicio']), (string) ($a['fecha_fin_real'] ?: $a['fecha_inicio'])));

// PAC-08: campana de notificaciones y tratamiento a abrir si el paciente llegó desde una notificación
$notif_campana     = $notif_campana     ?? ['items' => [], 'no_leidas' => 0];
$abrir_tratamiento = (int) ($abrir_tratamiento ?? 0);

// Pagos reales (Pago::resumenPortalPaciente); misma fuente que el asistente
$resumen_pagos    = $resumen_pagos ?? ['pagos' => [], 'pendientes' => [], 'saldo' => 0.0];
$mis_pagos        = $resumen_pagos['pagos'];
$mis_pendientes   = $resumen_pagos['pendientes'];
$saldo_pendiente  = (float) $resumen_pagos['saldo'];
?>

<div class="layout-app">

    <?php include ROOT_PATH . '/views/shared/sidebar.php'; ?>

    <div class="contenido-principal">

        <header class="topbar">
            <span class="topbar-titulo" id="topbar-titulo">Mi portal</span>
            <div class="topbar-acciones">
                <span class="topbar-fecha"><?= date('d/m/Y') ?></span>
                <div class="notif-wrap" id="notif-wrap"
                     data-inicial="<?= htmlspecialchars(json_encode($notif_campana, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
                    <button type="button" class="topbar-notificacion" id="notif-boton"
                            aria-label="Notificaciones" aria-haspopup="true" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <span class="notif-badge" id="notif-badge" hidden></span>
                    </button>
                    <div class="notif-panel" id="notif-panel" role="menu" hidden>
                        <div class="notif-panel-cab">
                            <strong>Notificaciones</strong>
                            <button type="button" class="notif-marcar" id="notif-marcar-todas">Marcar todas como leídas</button>
                        </div>
                        <div class="notif-lista" id="notif-lista"></div>
                    </div>
                </div>
            </div>
        </header>

        <div class="pagina activa" id="pagina-inicio">

            <div class="paciente-bienvenida">
                <div>
                    <span class="etiqueta-odent" style="color:rgba(255,255,255,.7);">BIENVENIDA</span>
                    <h2>¡Hola, <?= htmlspecialchars($usuario['nombre']) ?>!</h2>
                    <p>Aquí puedes consultar tus citas, tratamientos y pagos.</p>
                </div>
                <i class="bi bi-emoji-smile bienvenida-icono"></i>
            </div>

            <div class="panel-grid col-3">

                <div style="display:flex;flex-direction:column;gap:18px;">

                    <div class="tarjeta">
                        <h3 style="margin-bottom:14px;">Próxima cita</h3>
                        <?php if ($proxima_cita): ?>
                        <div class="proxima-cita">
                            <div class="proxima-cita-fecha">
                                <div class="mes"><?= $proxima_cita['mes'] ?></div>
                                <div class="dia"><?= $proxima_cita['dia'] ?></div>
                            </div>
                            <div class="proxima-cita-info">
                                <h4><?= htmlspecialchars($proxima_cita['tratamiento']) ?></h4>
                                <p><?= htmlspecialchars($proxima_cita['odontologo']) ?></p>
                                <p><i class="bi bi-clock"></i> <?= htmlspecialchars($proxima_cita['hora']) ?></p>
                            </div>
                        </div>
                        <?php else: ?>
                        <p style="color:var(--gris-azulado);font-size:14px;">No tiene citas próximas programadas.</p>
                        <?php endif; ?>
                    </div>

                    <div class="tarjeta">
                        <h3 style="margin-bottom:14px;">Saldo pendiente</h3>
                        <?php if($saldo_pendiente > 0): ?>
                        <div class="saldo-card">
                            <div class="saldo-monto">₡<?= number_format($saldo_pendiente,0,',','.') ?></div>
                            <p style="font-size:13px;color:#856404;margin-top:6px;">
                                <?php if (count($mis_pendientes) === 1): ?>
                                    <?= htmlspecialchars($mis_pendientes[0]['concepto']) ?> pendiente
                                <?php else: ?>
                                    <?= count($mis_pendientes) ?> cobros pendientes
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="alerta aviso" style="margin-top:12px;">
                            <i class="bi bi-exclamation-triangle"></i>
                            Contacte recepción para coordinar el pago.
                        </div>
                        <?php else: ?>
                        <div class="alerta exito"><i class="bi bi-check-circle"></i> No tiene saldos pendientes.</div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="tarjeta">
                    <div class="panel-titulo">
                        <h3>Mis tratamientos</h3>
                    </div>
                    <?php if (!$trat_activos): ?>
    <p style="color:var(--gris-azulado);font-size:14px;">
        <?= $trat_finalizados
            ? 'No tiene tratamientos en curso. Puede revisar los concluidos en «Tratamientos finalizados».'
            : 'Aún no tiene tratamientos registrados. Cuando su odontólogo cree un plan, lo verá aquí.' ?>
    </p>
<?php endif; ?>
<?php foreach (array_slice($trat_activos, 0, 3) as $t): ?>
<div class="tratamiento-item">
    <div class="tratamiento-header">
        <span><?= htmlspecialchars($t['nombre']) ?></span>
        <span><?= (int) $t['progreso'] ?>%</span>
    </div>
    <div style="font-size:12px;color:var(--gris-azulado);margin-bottom:6px;">
        Inicio: <?= $t['inicio'] ?>
    </div>
    <div class="barra-progreso">
        <div class="barra-relleno" style="width:<?= (int) $t['progreso'] ?>%;"></div>
    </div>
    <span class="badge-estado badge-<?= htmlspecialchars($t['estado']) ?>" style="margin-top:8px;display:inline-block;">
        <?= $etiquetas_trat[$t['estado']] ?? ucfirst($t['estado']) ?>
    </span>
</div>
<?php endforeach; ?>
                </div>

            </div>

        </div>

        <div class="pagina" id="pagina-citas">
            <h2 style="margin-bottom:20px;">Mis citas</h2>
            <div class="alerta info">
                <i class="bi bi-info-circle"></i>
                Este portal es de solo lectura. Para agendar o modificar una cita, comuníquese con la recepción.
            </div>
            <div class="tarjeta">
                <table class="tabla-odent">
                    <thead>
                        <tr><th>Fecha</th><th>Hora</th><th>Odontólogo</th><th>Tratamiento</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$mis_citas): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--gris-azulado);">Aún no tiene citas registradas.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($mis_citas as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['fecha_fmt']) ?></td>
                            <td><?= htmlspecialchars($c['hora']) ?></td>
                            <td><?= htmlspecialchars($c['odontologo']) ?></td>
                            <td><?= htmlspecialchars($c['tratamiento']) ?></td>
                            <td><span class="badge-estado badge-<?= htmlspecialchars($c['estado']) ?>"><?= ucfirst($c['estado']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagina" id="pagina-tratamientos">
            <h2 style="margin-bottom:20px;">Mis tratamientos</h2>
            <?php if (!$trat_activos): ?>
    <div class="tarjeta" style="text-align:center;padding:40px 16px;">
        <i class="bi bi-clipboard2-pulse" style="font-size:36px;color:var(--verde-odent);"></i>
        <h3 style="margin:10px 0 6px;"><?= $trat_finalizados ? 'No tiene tratamientos en curso' : 'Todavía no tiene tratamientos' ?></h3>
        <p style="color:var(--gris-azulado);font-size:14px;">
            <?= $trat_finalizados
                ? 'Sus tratamientos concluidos están en «Tratamientos finalizados».'
                : 'Cuando su odontólogo cree un plan para usted, podrá seguir su avance desde aquí.' ?>
        </p>
    </div>
<?php endif; ?>
<div class="panel-grid col-2">
    <?php foreach ($trat_activos as $t): ?>
    <div class="tarjeta">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;gap:10px;">
            <h3><?= htmlspecialchars($t['nombre']) ?></h3>
            <span class="badge-estado badge-<?= htmlspecialchars($t['estado']) ?>">
                <?= $etiquetas_trat[$t['estado']] ?? ucfirst($t['estado']) ?>
            </span>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;color:var(--gris-azulado);margin-bottom:14px;">
            <div><strong style="color:var(--gris-texto);">Inicio</strong><br><?= $t['inicio'] ?></div>
            <div><strong style="color:var(--gris-texto);">Fin estimado</strong><br><?= date('d/m/Y', strtotime($t['fecha_fin_estimada'])) ?></div>
            <div><strong style="color:var(--gris-texto);">Su odontólogo</strong><br><?= htmlspecialchars($t['odontologo']) ?></div>
        </div>
        <?php if (!empty($t['piezas'])): ?>
            <div class="tre-piezas">
                <?php foreach ($t['piezas'] as $pz): ?><span class="tre-chip-pieza"><?= (int) $pz ?></span><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="tratamiento-header">
            <span>Progreso</span><span><?= (int) $t['progreso'] ?>%</span>
        </div>
        <div class="barra-progreso">
            <div class="barra-relleno <?= $t['estado'] === 'cancelado' ? 'tre-cancelado' : '' ?>" style="width:<?= (int) $t['progreso'] ?>%;"></div>
        </div>
        <?php if (!empty($t['descripcion'])): ?>
            <p style="font-size:13px;color:var(--gris-azulado);margin:12px 0 0;"><?= htmlspecialchars($t['descripcion']) ?></p>
        <?php endif; ?>
        <?php if (!empty($t['observaciones'])): ?>
            <div class="tre-pac-obs"><strong>Nota de su odontólogo:</strong> <?= htmlspecialchars($t['observaciones']) ?></div>
        <?php endif; ?>
        <div style="margin-top:14px;">
            <button type="button" class="btn-outline-odent" style="font-size:13px;padding:7px 12px;"
                    onclick="abrirDetalleTratamiento(<?= (int) $t['id'] ?>)">
                <i class="bi bi-list-check"></i> Ver detalle y avances
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
        </div>

        <!-- PAC-07: historial de tratamientos concluidos -->
        <div class="pagina" id="pagina-finalizados">
            <h2 style="margin-bottom:6px;">Tratamientos finalizados</h2>
            <p style="font-size:14px;color:var(--gris-azulado);margin-bottom:20px;">
                Historial de la atención que ya concluyó.
            </p>
            <?php if (!$trat_finalizados): ?>
            <div class="tarjeta" style="text-align:center;padding:40px 16px;">
                <i class="bi bi-clipboard2-check" style="font-size:36px;color:var(--verde-odent);"></i>
                <h3 style="margin:10px 0 6px;">Sin tratamientos finalizados</h3>
                <p style="color:var(--gris-azulado);font-size:14px;">No existen tratamientos finalizados para consultar.</p>
            </div>
            <?php else: ?>
            <div class="tarjeta" style="overflow-x:auto;">
                <table class="tabla-odent">
                    <thead>
                        <tr><th>Tratamiento</th><th>Inicio</th><th>Finalización</th><th>Odontólogo</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trat_finalizados as $t): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($t['nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($t['inicio']) ?></td>
                            <td><?= htmlspecialchars($t['fin_real'] ?: $t['fin_estimada']) ?></td>
                            <td><?= htmlspecialchars($t['odontologo']) ?></td>
                            <td style="text-align:right;">
                                <button type="button" class="btn-outline-odent" style="font-size:13px;padding:6px 12px;"
                                        onclick="abrirDetalleTratamiento(<?= (int) $t['id'] ?>)">
                                    <i class="bi bi-eye"></i> Ver detalle
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="pagina" id="pagina-pagos">
            <h2 style="margin-bottom:20px;">Mis pagos</h2>
            <?php if($saldo_pendiente > 0): ?>
            <div class="alerta aviso">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Tiene un saldo pendiente de <strong>₡<?= number_format($saldo_pendiente,0,',','.') ?></strong>. Contacte a recepción para coordinarlo.</span>
            </div>
            <?php endif; ?>
            <div class="tarjeta">
                <table class="tabla-odent">
                    <thead>
                        <tr><th>ID</th><th>Fecha</th><th>Concepto</th><th>Monto</th><th>Método</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$mis_pagos): ?>
                        <tr><td colspan="6" style="text-align:center;color:var(--gris-azulado);">Aún no tiene pagos registrados.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($mis_pagos as $p): ?>
                        <tr>
                            <td><strong><?= $p['id'] ?></strong></td>
                            <td><?= $p['fecha'] ?></td>
                            <td><?= htmlspecialchars($p['concepto']) ?></td>
                            <td>₡<?= number_format($p['monto'],0,',','.') ?></td>
                            <td><?= htmlspecialchars($p['metodo']) ?></td>
                            <td><span class="badge-estado badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php include ROOT_PATH . '/views/paciente/asistente.php'; ?>

    </div>

</div>

<!-- Detalle de un tratamiento (finalizados y notificaciones) -->
<div class="modal-overlay" id="modal-trat-detalle">
    <div class="modal-caja" style="max-width:580px;">
        <div class="modal-header">
            <h3 id="td-titulo">Detalle del tratamiento</h3>
            <button type="button" class="modal-cerrar" onclick="cerrarModal('modal-trat-detalle')" aria-label="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div id="td-cuerpo"></div>
        <div class="modal-footer">
            <button type="button" class="btn-outline-odent" onclick="cerrarModal('modal-trat-detalle')">Cerrar</button>
        </div>
    </div>
</div>

<script>window.PACIENTE = { abrirTratamiento: <?= $abrir_tratamiento ?> };</script>

<script src="<?= BASE_URL ?>/public/js/app.js?v=<?= @filemtime(ROOT_PATH . '/public/js/app.js') ?>"></script>
<script src="<?= BASE_URL ?>/public/js/paciente.js?v=<?= @filemtime(ROOT_PATH . '/public/js/paciente.js') ?>"></script>
<script src="<?= BASE_URL ?>/public/js/asistente.js?v=<?= @filemtime(ROOT_PATH . '/public/js/asistente.js') ?>"></script>
</body>
</html>