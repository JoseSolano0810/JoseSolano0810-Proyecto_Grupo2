<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odent | Portal del paciente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/estilos.css">
</head>
<body>

<?php
$rol = 'paciente';

$usuario = [
    'nombre' => 'Ana Rojas',
    'iniciales' => 'AR'
];

$pagina_activa = 'inicio';

$proxima_cita = [
    'mes' => 'AGO',
    'dia' => '14',
    'hora' => '09:00 a.m.',
    'odontologo' => 'Dr. Carlos Méndez',
    'tratamiento' => 'Control de ortodoncia'
];

$mis_citas = [
    [
        'fecha' => '14/08/2026',
        'hora' => '09:00',
        'odontologo' => 'Dr. Méndez',
        'tratamiento' => 'Ortodoncia control',
        'estado' => 'confirmada'
    ],
    [
        'fecha' => '28/07/2026',
        'hora' => '08:00',
        'odontologo' => 'Dr. Méndez',
        'tratamiento' => 'Limpieza dental',
        'estado' => 'completada'
    ],
    [
        'fecha' => '10/06/2026',
        'hora' => '10:00',
        'odontologo' => 'Dr. Méndez',
        'tratamiento' => 'Revisión general',
        'estado' => 'completada'
    ],
    [
        'fecha' => '03/05/2026',
        'hora' => '11:30',
        'odontologo' => 'Dr. Méndez',
        'tratamiento' => 'Ortodoncia inicio',
        'estado' => 'completada'
    ]
];

$mis_tratamientos = [
    [
        'nombre' => 'Ortodoncia fase 2',
        'progreso' => 72,
        'inicio' => '10/03/2026',
        'sesiones_total' => 12,
        'sesiones_realizadas' => 8,
        'estado' => 'proceso'
    ],
    [
        'nombre' => 'Limpieza semestral',
        'progreso' => 100,
        'inicio' => '28/07/2026',
        'sesiones_total' => 1,
        'sesiones_realizadas' => 1,
        'estado' => 'completada'
    ]
];

$mis_pagos = [
    [
        'id' => 'PAG-001',
        'fecha' => '28/07/2026',
        'concepto' => 'Limpieza dental',
        'monto' => 50000,
        'metodo' => 'Tarjeta',
        'estado' => 'completada'
    ],
    [
        'id' => 'PAG-003',
        'fecha' => '03/08/2026',
        'concepto' => 'Cuota ortodoncia 8/12',
        'monto' => 60000,
        'metodo' => 'Sinpe',
        'estado' => 'pendiente'
    ]
];

$saldo_pendiente = 60000;
?>

<div class="layout-app">

    <?php include ROOT_PATH . '/views/shared/sidebar.php'; ?>

    <div class="contenido-principal">

        <header class="topbar">
            <span class="topbar-titulo" id="topbar-titulo">Mi portal</span>

            <div class="topbar-acciones">
                <span class="topbar-fecha"><?= date('d/m/Y') ?></span>

                <div class="topbar-notificacion">
                    <i class="bi bi-bell"></i>
                    <span class="notif-badge">1</span>
                </div>
            </div>
        </header>

        <div class="pagina activa" id="pagina-inicio">

            <div class="paciente-bienvenida">
                <div>
                    <span class="etiqueta-odent" style="color:rgba(255,255,255,.7);">
                        BIENVENIDA
                    </span>

                    <h2>
                        ¡Hola, <?= htmlspecialchars($usuario['nombre']) ?>!
                    </h2>

                    <p>
                        Aquí puedes consultar tus citas, tratamientos y pagos.
                    </p>
                </div>

                <i class="bi bi-emoji-smile bienvenida-icono"></i>
            </div>

            <div class="panel-grid col-3">

                <div style="display:flex;flex-direction:column;gap:18px;">

                    <div class="tarjeta">

                        <h3 style="margin-bottom:14px;">
                            Próxima cita
                        </h3>

                        <div class="proxima-cita">

                            <div class="proxima-cita-fecha">
                                <div class="mes">
                                    <?= $proxima_cita['mes'] ?>
                                </div>

                                <div class="dia">
                                    <?= $proxima_cita['dia'] ?>
                                </div>
                            </div>

                            <div class="proxima-cita-info">

                                <h4>
                                    <?= htmlspecialchars($proxima_cita['tratamiento']) ?>
                                </h4>

                                <p>
                                    <?= htmlspecialchars($proxima_cita['odontologo']) ?>
                                </p>

                                <p>
                                    <i class="bi bi-clock"></i>
                                    <?= $proxima_cita['hora'] ?>
                                </p>

                            </div>
                        </div>
                    </div>

                    <div class="tarjeta">

                        <h3 style="margin-bottom:14px;">
                            Saldo pendiente
                        </h3>

                        <?php if ($saldo_pendiente > 0): ?>

                        <div class="saldo-card">
                            <div class="saldo-monto">
                                ₡<?= number_format($saldo_pendiente, 0, ',', '.') ?>
                            </div>

                            <p style="font-size:13px;color:#856404;margin-top:6px;">
                                Cuota de ortodoncia pendiente
                            </p>
                        </div>

                        <div class="alerta aviso" style="margin-top:12px;">
                            <i class="bi bi-exclamation-triangle"></i>
                            Contacte recepción para coordinar el pago.
                        </div>

                        <?php else: ?>

                        <div class="alerta exito">
                            <i class="bi bi-check-circle"></i>
                            No tiene saldos pendientes.
                        </div>

                        <?php endif; ?>

                    </div>

                </div>

                <div class="tarjeta">

                    <div class="panel-titulo">
                        <h3>Mis tratamientos</h3>
                    </div>

                    <?php foreach ($mis_tratamientos as $t): ?>

                    <div class="tratamiento-item">

                        <div class="tratamiento-header">
                            <span><?= htmlspecialchars($t['nombre']) ?></span>
                            <span><?= $t['progreso'] ?>%</span>
                        </div>

                        <div style="font-size:12px;color:var(--gris-azulado);margin-bottom:6px;">
                            <?= $t['sesiones_realizadas'] ?>
                            de
                            <?= $t['sesiones_total'] ?>
                            sesiones
                            —
                            Inicio:
                            <?= $t['inicio'] ?>
                        </div>

                        <div class="barra-progreso">
                            <div
                                class="barra-relleno"
                                style="width:<?= $t['progreso'] ?>%;"
                            ></div>
                        </div>

                        <span
                            class="badge-estado badge-<?= $t['estado'] ?>"
                            style="margin-top:8px;display:inline-block;"
                        >
                            <?= ucfirst($t['estado'] === 'proceso' ? 'En proceso' : 'Completado') ?>
                        </span>

                    </div>

                    <?php endforeach; ?>

                </div>

            </div>
        </div>

        <div class="pagina" id="pagina-citas">

            <h2 style="margin-bottom:20px;">
                Mis citas
            </h2>

            <div class="alerta info">
                <i class="bi bi-info-circle"></i>
                Este portal es de solo lectura. Para agendar o modificar una cita, comuníquese con la recepción.
            </div>

            <div class="tarjeta">

                <table class="tabla-odent">

                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Odontólogo</th>
                            <th>Tratamiento</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($mis_citas as $c): ?>

                        <tr>
                            <td><?= $c['fecha'] ?></td>
                            <td><?= $c['hora'] ?></td>

                            <td>
                                <?= htmlspecialchars($c['odontologo']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($c['tratamiento']) ?>
                            </td>

                            <td>
                                <span class="badge-estado badge-<?= $c['estado'] ?>">
                                    <?= ucfirst($c['estado']) ?>
                                </span>
                            </td>
                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="pagina" id="pagina-tratamientos">

            <h2 style="margin-bottom:20px;">
                Mis tratamientos
            </h2>

            <div class="panel-grid col-2">

                <?php foreach ($mis_tratamientos as $t): ?>

                <div class="tarjeta">

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">

                        <h3>
                            <?= htmlspecialchars($t['nombre']) ?>
                        </h3>

                        <span class="badge-estado badge-<?= $t['estado'] ?>">
                            <?= $t['estado'] === 'proceso' ? 'En proceso' : 'Completado' ?>
                        </span>

                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;color:var(--gris-azulado);margin-bottom:14px;">

                        <div>
                            <strong style="color:var(--gris-texto);">
                                Inicio
                            </strong>
                            <br>
                            <?= $t['inicio'] ?>
                        </div>

                        <div>
                            <strong style="color:var(--gris-texto);">
                                Sesiones
                            </strong>
                            <br>
                            <?= $t['sesiones_realizadas'] ?>
                            /
                            <?= $t['sesiones_total'] ?>
                        </div>

                    </div>

                    <div class="tratamiento-header">
                        <span>Progreso</span>
                        <span><?= $t['progreso'] ?>%</span>
                    </div>

                    <div class="barra-progreso">
                        <div
                            class="barra-relleno"
                            style="width:<?= $t['progreso'] ?>%;"
                        ></div>
                    </div>

                </div>

                <?php endforeach; ?>

            </div>

        </div>

        <div class="pagina" id="pagina-pagos">

            <h2 style="margin-bottom:20px;">
                Mis pagos
            </h2>

            <?php if ($saldo_pendiente > 0): ?>

            <div class="alerta aviso">
                <i class="bi bi-exclamation-triangle"></i>
                Tiene un saldo pendiente de
                <strong>
                    ₡<?= number_format($saldo_pendiente, 0, ',', '.') ?>
                </strong>.
                Contacte a recepción para coordinarlo.
            </div>

            <?php endif; ?>

            <div class="tarjeta">

                <table class="tabla-odent">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($mis_pagos as $p): ?>

                        <tr>

                            <td>
                                <strong><?= $p['id'] ?></strong>
                            </td>

                            <td><?= $p['fecha'] ?></td>

                            <td>
                                <?= htmlspecialchars($p['concepto']) ?>
                            </td>

                            <td>
                                ₡<?= number_format($p['monto'], 0, ',', '.') ?>
                            </td>

                            <td><?= $p['metodo'] ?></td>

                            <td>
                                <span class="badge-estado badge-<?= $p['estado'] ?>">
                                    <?= ucfirst($p['estado']) ?>
                                </span>
                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="pagina" id="pagina-asistente">

            <h2 style="margin-bottom:6px;">
                Asistente de triaje
            </h2>

            <p style="font-size:14px;color:var(--gris-azulado);margin-bottom:20px;">
                Cuénteme sus síntomas o consultas. Le ayudaré a orientar su atención.
            </p>

            <div class="panel-grid col-3">

                <div class="tarjeta" style="grid-column:1 / span 2;">

                    <div class="asistente-chat">

                        <div class="chat-mensajes" id="chat-mensajes">

                            <div class="mensaje bot">
                                Hola
                                <strong><?= htmlspecialchars($usuario['nombre']) ?></strong>,
                                soy el asistente de triaje de Odent.
                                Puede contarme sus síntomas o hacerme preguntas sobre su atención dental.
                                Le ayudaré a determinar qué tan urgente es su situación. 😊
                            </div>

                        </div>

                        <div class="chat-input">

                            <input
                                type="text"
                                id="chat-input-texto"
                                placeholder="Escriba su consulta o síntoma..."
                                onkeydown="chatEnter(event)"
                            >

                            <button onclick="enviarMensajeChat()">
                                <i class="bi bi-send-fill"></i>
                            </button>

                        </div>

                    </div>

                </div>

                <div class="tarjeta">

                    <h4 style="margin-bottom:14px;">
                        Preguntas frecuentes
                    </h4>

                    <div style="display:flex;flex-direction:column;gap:8px;">

                        <?php

                        $preguntas = [
                            'Tengo dolor de muela fuerte',
                            'Se me cayó un diente',
                            '¿Cuándo es mi próxima cita?',
                            'Tengo encías inflamadas',
                            'Rompí un bracket'
                        ];

                        foreach ($preguntas as $preg): ?>

                        <button
                            onclick="preguntaRapida('<?= htmlspecialchars($preg, ENT_QUOTES) ?>')"
                            style="text-align:left;border:1.5px solid var(--borde);border-radius:8px;padding:10px 14px;font-size:13px;background:var(--blanco);cursor:pointer;color:var(--gris-texto);transition:all .2s;"
                            onmouseover="this.style.borderColor='var(--verde-odent)'"
                            onmouseout="this.style.borderColor='var(--borde)'"
                        >
                            <i
                                class="bi bi-chat-dots"
                                style="color:var(--verde-odent);"
                            ></i>

                            <?= htmlspecialchars($preg) ?>
                        </button>

                        <?php endforeach; ?>

                    </div>

                    <div class="alerta aviso" style="margin-top:16px;">
                        <i class="bi bi-exclamation-triangle"></i>
                        Este asistente es orientativo. En emergencias, llame directamente a la clínica.
                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

<script src="<?= BASE_URL ?>/public/js/app.js"></script>
</body>
</html>