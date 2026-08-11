<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odent | Recepcionista</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/estilos.css">
</head>
<body>

<?php

$rol = 'recepcionista';

$usuario = [
    'nombre' => 'Laura Jiménez',
    'iniciales' => 'LJ'
];

$pagina_activa = 'inicio';

$citas = [
    [
        'id' => 1,
        'hora' => '08:00',
        'paciente' => 'Ana Rojas',
        'odontologo' => 'Dr. Méndez',
        'tratamiento' => 'Limpieza',
        'estado' => 'confirmada'
    ],
    [
        'id' => 2,
        'hora' => '09:30',
        'paciente' => 'Luis Vargas',
        'odontologo' => 'Dr. Méndez',
        'tratamiento' => 'Extracción molar',
        'estado' => 'confirmada'
    ],
    [
        'id' => 3,
        'hora' => '10:00',
        'paciente' => 'Pedro Gómez',
        'odontologo' => 'Dra. Flores',
        'tratamiento' => 'Ortodoncia',
        'estado' => 'pendiente'
    ],
    [
        'id' => 4,
        'hora' => '11:00',
        'paciente' => 'María Solís',
        'odontologo' => 'Dr. Méndez',
        'tratamiento' => 'Ortodoncia',
        'estado' => 'pendiente'
    ],
    [
        'id' => 5,
        'hora' => '13:00',
        'paciente' => 'Carlos Castro',
        'odontologo' => 'Dra. Flores',
        'tratamiento' => 'Blanqueamiento',
        'estado' => 'cancelada'
    ],
    [
        'id' => 6,
        'hora' => '14:00',
        'paciente' => 'Jorge Pérez',
        'odontologo' => 'Dr. Méndez',
        'tratamiento' => 'Blanqueamiento',
        'estado' => 'confirmada'
    ],
    [
        'id' => 7,
        'hora' => '15:30',
        'paciente' => 'Sofía Torres',
        'odontologo' => 'Dra. Flores',
        'tratamiento' => 'Revisión',
        'estado' => 'confirmada'
    ]
];

$pacientes = [
    [
        'id' => 1,
        'nombre' => 'Ana Rojas',
        'cedula' => '1-0234-5678',
        'telefono' => '8888-1111',
        'correo' => 'ana.rojas@mail.com',
        'estado' => 'activo'
    ],
    [
        'id' => 2,
        'nombre' => 'Luis Vargas',
        'cedula' => '2-0345-6789',
        'telefono' => '8888-2222',
        'correo' => 'luis.vargas@mail.com',
        'estado' => 'activo'
    ],
    [
        'id' => 3,
        'nombre' => 'Pedro Gómez',
        'cedula' => '1-0901-2345',
        'telefono' => '8888-3333',
        'correo' => 'pedro.gomez@mail.com',
        'estado' => 'activo'
    ],
    [
        'id' => 4,
        'nombre' => 'María Solís',
        'cedula' => '1-0456-7890',
        'telefono' => '8888-4444',
        'correo' => 'maria.solis@mail.com',
        'estado' => 'activo'
    ],
    [
        'id' => 5,
        'nombre' => 'Carlos Castro',
        'cedula' => '3-0123-4567',
        'telefono' => '8888-5555',
        'correo' => 'carlos.castro@mail.com',
        'estado' => 'inactivo'
    ],
    [
        'id' => 6,
        'nombre' => 'Jorge Pérez',
        'cedula' => '3-0567-8901',
        'telefono' => '8888-6666',
        'correo' => 'jorge.perez@mail.com',
        'estado' => 'inactivo'
    ],
    [
        'id' => 7,
        'nombre' => 'Sofía Torres',
        'cedula' => '1-0678-9012',
        'telefono' => '8888-7777',
        'correo' => 'sofia.torres@mail.com',
        'estado' => 'activo'
    ]
];

$cotizaciones = [
    [
        'id' => 'COT-001',
        'paciente' => 'Ana Rojas',
        'fecha' => '28/07/2026',
        'total' => 95000,
        'estado' => 'aprobada'
    ],
    [
        'id' => 'COT-002',
        'paciente' => 'Luis Vargas',
        'fecha' => '29/07/2026',
        'total' => 120000,
        'estado' => 'pendiente'
    ],
    [
        'id' => 'COT-003',
        'paciente' => 'Pedro Gómez',
        'fecha' => '30/07/2026',
        'total' => 45000,
        'estado' => 'pendiente'
    ],
    [
        'id' => 'COT-004',
        'paciente' => 'María Solís',
        'fecha' => '01/08/2026',
        'total' => 200000,
        'estado' => 'aprobada'
    ],
    [
        'id' => 'COT-005',
        'paciente' => 'Carlos Castro',
        'fecha' => '02/08/2026',
        'total' => 75000,
        'estado' => 'rechazada'
    ]
];

$pagos = [
    [
        'id' => 'PAG-001',
        'paciente' => 'Ana Rojas',
        'fecha' => '28/07/2026',
        'monto' => 50000,
        'metodo' => 'Tarjeta',
        'cotizacion' => 'COT-001',
        'estado' => 'completada'
    ],
    [
        'id' => 'PAG-002',
        'paciente' => 'María Solís',
        'fecha' => '01/08/2026',
        'monto' => 100000,
        'metodo' => 'Efectivo',
        'cotizacion' => 'COT-004',
        'estado' => 'completada'
    ],
    [
        'id' => 'PAG-003',
        'paciente' => 'Luis Vargas',
        'fecha' => '03/08/2026',
        'monto' => 60000,
        'metodo' => 'Sinpe',
        'cotizacion' => 'COT-002',
        'estado' => 'pendiente'
    ]
];

?>

<div class="layout-app">

    <?php include __DIR__ . '/../shared/sidebar.php'; ?>

    <div class="contenido-principal">

        <header class="topbar">

            <span class="topbar-titulo" id="topbar-titulo">
                Panel de recepción
            </span>

            <div class="topbar-acciones">

                <span class="topbar-fecha">
                    <?= date('d/m/Y') ?>
                </span>

                <div class="topbar-notificacion">
                    <i class="bi bi-bell"></i>
                    <span class="notif-badge">2</span>
                </div>

            </div>

        </header>

        <div class="pagina activa" id="pagina-inicio">

            <div class="kpi-grid">

                <div class="kpi-card">

                    <div class="kpi-icono azul">
                        <i class="bi bi-calendar3"></i>
                    </div>

                    <div>
                        <div class="kpi-numero">7</div>
                        <div class="kpi-etiqueta">Citas hoy</div>
                    </div>

                </div>

                <div class="kpi-card">

                    <div class="kpi-icono verde">
                        <i class="bi bi-people"></i>
                    </div>

                    <div>
                        <div class="kpi-numero"><?= count($pacientes) ?></div>
                        <div class="kpi-etiqueta">Pacientes registrados</div>
                    </div>

                </div>

                <div class="kpi-card">

                    <div class="kpi-icono amarillo">
                        <i class="bi bi-receipt"></i>
                    </div>

                    <div>
                        <div class="kpi-numero">2</div>
                        <div class="kpi-etiqueta">Cotizaciones pendientes</div>
                    </div>

                </div>

                <div class="kpi-card">

                    <div class="kpi-icono rojo">
                        <i class="bi bi-cash-stack"></i>
                    </div>

                    <div>
                        <div class="kpi-numero">₡150K</div>
                        <div class="kpi-etiqueta">Cobrado hoy</div>
                    </div>

                </div>

            </div>

            <div class="panel-grid col-2">

                <div class="tarjeta">

                    <div class="panel-titulo">

                        <h3>Agenda del día</h3>

                        <button
                            class="btn-odent"
                            onclick="abrirModal('modal-nueva-cita')"
                            style="font-size:13px;padding:7px 14px;"
                        >
                            <i class="bi bi-plus-lg"></i>
                            Nueva cita
                        </button>

                    </div>

                    <?php foreach (array_slice($citas, 0, 5) as $c): ?>

                    <div class="cita-item">

                        <div class="cita-hora">
                            <?= $c['hora'] ?>
                        </div>

                        <div class="cita-info">

                            <div class="cita-paciente">
                                <?= htmlspecialchars($c['paciente']) ?>
                            </div>

                            <div class="cita-tratamiento">
                                <?= htmlspecialchars($c['odontologo']) ?>
                                —
                                <?= htmlspecialchars($c['tratamiento']) ?>
                            </div>

                        </div>

                        <span class="badge-estado badge-<?= $c['estado'] ?>">
                            <?= ucfirst($c['estado']) ?>
                        </span>

                    </div>

                    <?php endforeach; ?>

                    <div style="margin-top:14px;text-align:right;">

                        <button
                            class="btn-outline-odent"
                            onclick="mostrarPagina('agenda')"
                            style="font-size:13px;padding:6px 14px;"
                        >
                            Ver todas las citas →
                        </button>

                    </div>

                </div>

                <div class="tarjeta">

                    <div class="panel-titulo">

                        <h3>Últimos pagos</h3>

                        <button
                            class="btn-odent"
                            onclick="abrirModal('modal-nuevo-pago')"
                            style="font-size:13px;padding:7px 14px;"
                        >
                            <i class="bi bi-plus-lg"></i>
                            Registrar pago
                        </button>

                    </div>

                    <table class="tabla-odent">

                        <thead>
                            <tr>
                                <th>Paciente</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($pagos as $pago): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($pago['paciente']) ?>
                                </td>

                                <td>
                                    ₡<?= number_format($pago['monto'], 0, ',', '.') ?>
                                </td>

                                <td>
                                    <?= $pago['metodo'] ?>
                                </td>

                                <td>
                                    <span class="badge-estado badge-<?= $pago['estado'] ?>">
                                        <?= ucfirst($pago['estado']) ?>
                                    </span>
                                </td>

                            </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

        <div class="pagina" id="pagina-agenda">

            <div class="panel-titulo" style="margin-bottom:20px;">

                <h2>Agenda general</h2>

                <button
                    class="btn-odent"
                    onclick="abrirModal('modal-nueva-cita')"
                >
                    <i class="bi bi-plus-lg"></i>
                    Nueva cita
                </button>

            </div>

            <div class="tarjeta">

                <div style="display:flex;gap:12px;margin-bottom:18px;flex-wrap:wrap;">

                    <input
                        type="date"
                        value="<?= date('Y-m-d') ?>"
                        style="border:1.5px solid var(--borde);border-radius:8px;padding:8px 12px;font-size:14px;"
                    >

                    <select
                        style="border:1.5px solid var(--borde);border-radius:8px;padding:8px 12px;font-size:14px;"
                    >
                        <option>Todos los odontólogos</option>
                        <option>Dr. Méndez</option>
                        <option>Dra. Flores</option>
                    </select>

                    <select
                        style="border:1.5px solid var(--borde);border-radius:8px;padding:8px 12px;font-size:14px;"
                    >
                        <option>Todos los estados</option>
                        <option>Confirmada</option>
                        <option>Pendiente</option>
                        <option>Cancelada</option>
                    </select>

                </div>

                <table class="tabla-odent">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Hora</th>
                            <th>Paciente</th>
                            <th>Odontólogo</th>
                            <th>Tratamiento</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($citas as $c): ?>

                        <tr>

                            <td><?= $c['id'] ?></td>

                            <td>
                                <strong><?= $c['hora'] ?></strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($c['paciente']) ?>
                            </td>

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

                            <td style="display:flex;gap:6px;">

                                <button
                                    class="btn-outline-odent"
                                    style="font-size:12px;padding:4px 10px;"
                                >
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <button
                                    class="btn-peligro"
                                    style="font-size:12px;padding:4px 10px;"
                                >
                                    <i class="bi bi-x-lg"></i>
                                </button>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="pagina" id="pagina-pacientes">

            <div class="panel-titulo" style="margin-bottom:20px;">

                <h2>Registro de pacientes</h2>

                <button
                    class="btn-odent"
                    onclick="abrirModal('modal-nuevo-paciente')"
                >
                    <i class="bi bi-person-plus"></i>
                    Nuevo paciente
                </button>

            </div>

            <div class="tarjeta">

                <div style="display:flex;gap:10px;margin-bottom:18px;">

                    <input
                        type="text"
                        placeholder="Buscar por nombre o cédula..."
                        style="border:1.5px solid var(--borde);border-radius:8px;padding:8px 14px;font-size:14px;flex:1;"
                    >

                    <button class="btn-odent">
                        <i class="bi bi-search"></i>
                        Buscar
                    </button>

                </div>

                <table class="tabla-odent">

                    <thead>

                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($pacientes as $p): ?>

                        <tr>

                            <td><?= $p['id'] ?></td>

                            <td>
                                <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                            </td>

                            <td><?= $p['cedula'] ?></td>
                            <td><?= $p['telefono'] ?></td>
                            <td><?= $p['correo'] ?></td>

                            <td>
                                <span class="badge-estado badge-<?= $p['estado'] ?>">
                                    <?= ucfirst($p['estado']) ?>
                                </span>
                            </td>

                            <td>

                                <button
                                    class="btn-outline-odent"
                                    style="font-size:12px;padding:4px 10px;"
                                >
                                    <i class="bi bi-pencil"></i>
                                    Editar
                                </button>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="pagina" id="pagina-cotizaciones">

            <div class="panel-titulo" style="margin-bottom:20px;">

                <h2>Cotizaciones</h2>

                <button
                    class="btn-odent"
                    onclick="abrirModal('modal-nueva-cotizacion')"
                >
                    <i class="bi bi-file-earmark-plus"></i>
                    Nueva cotización
                </button>

            </div>

            <div class="tarjeta">

                <table class="tabla-odent">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Paciente</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($cotizaciones as $c): ?>

                        <tr>

                            <td>
                                <strong><?= $c['id'] ?></strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($c['paciente']) ?>
                            </td>

                            <td><?= $c['fecha'] ?></td>

                            <td>
                                ₡<?= number_format($c['total'], 0, ',', '.') ?>
                            </td>

                            <td>

                                <span class="badge-estado badge-<?= $c['estado'] === 'aprobada' ? 'confirmada' : ($c['estado'] === 'rechazada' ? 'cancelada' : 'pendiente') ?>">
                                    <?= ucfirst($c['estado']) ?>
                                </span>

                            </td>

                            <td style="display:flex;gap:6px;">

                                <button
                                    class="btn-outline-odent"
                                    style="font-size:12px;padding:4px 10px;"
                                >
                                    <i class="bi bi-eye"></i>
                                    Ver
                                </button>

                                <?php if ($c['estado'] === 'aprobada'): ?>

                                <button
                                    class="btn-odent"
                                    onclick="abrirModal('modal-nuevo-pago')"
                                    style="font-size:12px;padding:4px 10px;"
                                >
                                    <i class="bi bi-cash"></i>
                                    Cobrar
                                </button>

                                <?php endif; ?>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="pagina" id="pagina-pagos">

            <div class="panel-titulo" style="margin-bottom:20px;">

                <h2>Registro de pagos</h2>

                <button
                    class="btn-odent"
                    onclick="abrirModal('modal-nuevo-pago')"
                >
                    <i class="bi bi-plus-lg"></i>
                    Registrar pago
                </button>

            </div>

            <div class="kpi-grid" style="margin-bottom:22px;">

                <div class="kpi-card">

                    <div class="kpi-icono verde">
                        <i class="bi bi-cash-stack"></i>
                    </div>

                    <div>
                        <div class="kpi-numero">₡150K</div>
                        <div class="kpi-etiqueta">Cobrado hoy</div>
                    </div>

                </div>

                <div class="kpi-card">

                    <div class="kpi-icono amarillo">
                        <i class="bi bi-hourglass"></i>
                    </div>

                    <div>
                        <div class="kpi-numero">₡60K</div>
                        <div class="kpi-etiqueta">Pendiente de cobro</div>
                    </div>

                </div>

            </div>

            <div class="tarjeta">

                <table class="tabla-odent">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Paciente</th>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Método</th>
                            <th>Cotización</th>
                            <th>Estado</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($pagos as $p): ?>

                        <tr>

                            <td>
                                <strong><?= $p['id'] ?></strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($p['paciente']) ?>
                            </td>

                            <td><?= $p['fecha'] ?></td>

                            <td>
                                ₡<?= number_format($p['monto'], 0, ',', '.') ?>
                            </td>

                            <td><?= $p['metodo'] ?></td>
                            <td><?= $p['cotizacion'] ?></td>

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

    </div>

</div>

<div class="modal-overlay" id="modal-nueva-cita">

    <div class="modal-caja">

        <div class="modal-header">

            <h3>Nueva cita</h3>

            <button
                class="modal-cerrar"
                onclick="cerrarModal('modal-nueva-cita')"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>

        <div class="campo-grupo">

            <label>Paciente</label>

            <select>

                <option value="">
                    Seleccione un paciente
                </option>

                <?php foreach ($pacientes as $p): ?>

                <option value="<?= $p['id'] ?>">
                    <?= htmlspecialchars($p['nombre']) ?>
                </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="campo-grupo">

            <label>Odontólogo</label>

            <select>
                <option>Dr. Méndez</option>
                <option>Dra. Flores</option>
            </select>

        </div>

        <div class="campo-grupo">

            <label>Fecha</label>

            <input
                type="date"
                value="<?= date('Y-m-d') ?>"
            >

        </div>

        <div class="campo-grupo">

            <label>Hora</label>

            <input type="time">

        </div>

        <div class="campo-grupo">

            <label>Tratamiento</label>

            <select>
                <option>Limpieza dental</option>
                <option>Extracción</option>
                <option>Ortodoncia</option>
                <option>Blanqueamiento</option>
                <option>Revisión general</option>
            </select>

        </div>

        <div class="modal-footer">

            <button
                class="btn-outline-odent"
                onclick="cerrarModal('modal-nueva-cita')"
            >
                Cancelar
            </button>

            <button class="btn-odent">
                <i class="bi bi-check-lg"></i>
                Agendar
            </button>

        </div>

    </div>

</div>

<div class="modal-overlay" id="modal-nuevo-paciente">

    <div class="modal-caja">

        <div class="modal-header">

            <h3>Registrar paciente</h3>

            <button
                class="modal-cerrar"
                onclick="cerrarModal('modal-nuevo-paciente')"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>

        <div class="campo-grupo">
            <label>Nombre completo</label>
            <input type="text" placeholder="Nombre y apellidos">
        </div>

        <div class="campo-grupo">
            <label>Cédula</label>
            <input type="text" placeholder="0-0000-0000">
        </div>

        <div class="campo-grupo">
            <label>Teléfono</label>
            <input type="tel" placeholder="0000-0000">
        </div>

        <div class="campo-grupo">
            <label>Correo electrónico</label>
            <input type="email" placeholder="correo@ejemplo.com">
        </div>

        <div class="campo-grupo">
            <label>Fecha de nacimiento</label>
            <input type="date">
        </div>

        <div class="modal-footer">

            <button
                class="btn-outline-odent"
                onclick="cerrarModal('modal-nuevo-paciente')"
            >
                Cancelar
            </button>

            <button class="btn-odent">
                <i class="bi bi-check-lg"></i>
                Guardar
            </button>

        </div>

    </div>

</div>

<div class="modal-overlay" id="modal-nuevo-pago">

    <div class="modal-caja">

        <div class="modal-header">

            <h3>Registrar pago</h3>

            <button
                class="modal-cerrar"
                onclick="cerrarModal('modal-nuevo-pago')"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>

        <div class="campo-grupo">

            <label>Cotización</label>

            <select>

                <?php foreach ($cotizaciones as $c): ?>

                    <?php if ($c['estado'] === 'aprobada'): ?>

                    <option value="<?= $c['id'] ?>">
                        <?= $c['id'] ?>
                        —
                        <?= htmlspecialchars($c['paciente']) ?>
                        (₡<?= number_format($c['total'], 0, ',', '.') ?>)
                    </option>

                    <?php endif; ?>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="campo-grupo">

            <label>Monto recibido (₡)</label>

            <input
                type="number"
                placeholder="0"
            >

        </div>

        <div class="campo-grupo">

            <label>Método de pago</label>

            <select>
                <option>Efectivo</option>
                <option>Tarjeta débito</option>
                <option>Tarjeta crédito</option>
                <option>Sinpe Móvil</option>
                <option>Transferencia</option>
            </select>

        </div>

        <div class="campo-grupo">

            <label>Comprobante (opcional)</label>

            <input
                type="text"
                placeholder="N.° comprobante o referencia"
            >

        </div>

        <div class="modal-footer">

            <button
                class="btn-outline-odent"
                onclick="cerrarModal('modal-nuevo-pago')"
            >
                Cancelar
            </button>

            <button class="btn-odent">
                <i class="bi bi-check-lg"></i>
                Registrar
            </button>

        </div>

    </div>

</div>

<div class="modal-overlay" id="modal-nueva-cotizacion">

    <div class="modal-caja">

        <div class="modal-header">

            <h3>Nueva cotización</h3>

            <button
                class="modal-cerrar"
                onclick="cerrarModal('modal-nueva-cotizacion')"
            >
                <i class="bi bi-x-lg"></i>
            </button>

        </div>

        <div class="campo-grupo">

            <label>Paciente</label>

            <select>

                <option value="">
                    Seleccione un paciente
                </option>

                <?php foreach ($pacientes as $p): ?>

                <option value="<?= $p['id'] ?>">
                    <?= htmlspecialchars($p['nombre']) ?>
                </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="campo-grupo">

            <label>Tratamiento</label>

            <input
                type="text"
                placeholder="Descripción del tratamiento"
            >

        </div>

        <div class="campo-grupo">

            <label>Monto (₡)</label>

            <input
                type="number"
                placeholder="0"
                id="cot-monto"
            >

        </div>

        <div class="campo-grupo">

            <label>IVA (13%)</label>

            <input
                type="text"
                id="cot-iva"
                readonly
                placeholder="Se calcula automáticamente"
            >

        </div>

        <div class="campo-grupo">

            <label>Total</label>

            <input
                type="text"
                id="cot-total"
                readonly
                placeholder="Se calcula automáticamente"
            >

        </div>

        <div class="campo-grupo">

            <label>Observaciones</label>

            <textarea
                rows="2"
                placeholder="Notas adicionales..."
            ></textarea>

        </div>

        <div class="modal-footer">

            <button
                class="btn-outline-odent"
                onclick="cerrarModal('modal-nueva-cotizacion')"
            >
                Cancelar
            </button>

            <button class="btn-odent">
                <i class="bi bi-check-lg"></i>
                Crear cotización
            </button>

        </div>

    </div>

</div>

<script src="<?= BASE_URL ?>/public/js/app.js"></script>
</body>
</html>