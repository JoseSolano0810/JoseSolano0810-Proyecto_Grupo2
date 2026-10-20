<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odent | <?= ($rol ?? 'recepcionista') === 'asistente_dental' ? 'Asistente Dental' : 'Recepcionista' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/estilos.css?v=<?= @filemtime(ROOT_PATH . '/public/css/estilos.css') ?>">
</head>

<body>

    <?php
    $rol           = $rol           ?? 'recepcionista';
    $usuario       = $usuario       ?? ['nombre' => '', 'iniciales' => ''];
    $pagina_activa = $pagina_activa ?? 'inicio';

    $esRecepcionista   = $rol === 'recepcionista';
    $esAsistenteDental = $rol === 'asistente_dental'
        || in_array('asistente_dental', $roles_sesion ?? [], true);

    // Las citas vienen de la BD (CitaController::listar); nunca se simulan aquí
    $citas = $citas ?? [];

    $pacientes = $pacientes ?? [];

    $cotizaciones = $cotizaciones ?? [
        ['id' => 'COT-001', 'paciente' => 'Ana Rojas',   'fecha' => '28/07/2026', 'total' => 95000, 'estado' => 'aprobada'],
        ['id' => 'COT-002', 'paciente' => 'Luis Vargas', 'fecha' => '29/07/2026', 'total' => 120000, 'estado' => 'pendiente'],
        ['id' => 'COT-003', 'paciente' => 'Pedro Gómez', 'fecha' => '30/07/2026', 'total' => 45000, 'estado' => 'pendiente'],
        ['id' => 'COT-004', 'paciente' => 'María Solís', 'fecha' => '01/08/2026', 'total' => 200000, 'estado' => 'aprobada'],
        ['id' => 'COT-005', 'paciente' => 'Carlos Castro', 'fecha' => '02/08/2026', 'total' => 75000, 'estado' => 'rechazada'],
    ];

    $pagos = $pagos ?? [
        ['id' => 'PAG-001', 'paciente' => 'Ana Rojas', 'fecha' => '28/07/2026', 'monto' => 50000, 'metodo' => 'Tarjeta', 'cotizacion' => 'COT-001', 'estado' => 'completada'],
        ['id' => 'PAG-002', 'paciente' => 'María Solís', 'fecha' => '01/08/2026', 'monto' => 100000, 'metodo' => 'Efectivo', 'cotizacion' => 'COT-004', 'estado' => 'completada'],
        ['id' => 'PAG-003', 'paciente' => 'Luis Vargas', 'fecha' => '03/08/2026', 'monto' => 60000, 'metodo' => 'Sinpe',  'cotizacion' => 'COT-002', 'estado' => 'pendiente'],
    ];
    ?>

    <div class="layout-app">

        <?php include ROOT_PATH . '/views/shared/sidebar.php'; ?>

        <div class="contenido-principal">

            <header class="topbar">
                <span class="topbar-titulo" id="topbar-titulo">
                    <?= $esAsistenteDental ? 'Panel de asistente dental' : 'Panel de recepción' ?>
                </span>
                <div class="topbar-acciones">
                    <span class="topbar-fecha"><?= date('d/m/Y') ?></span>
                    <div class="topbar-notificacion">
                        <i class="bi bi-bell"></i>
                        <span class="notif-badge">2</span>
                    </div>
                </div>
            </header>

            <div class="pagina activa" id="pagina-inicio">

                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-icono azul"><i class="bi bi-calendar3"></i></div>
                        <div>
                            <div class="kpi-numero"><?= count($citas) ?></div>
                            <div class="kpi-etiqueta">Citas hoy</div>
                        </div>
                    </div>

                    <?php if ($esRecepcionista): ?>
                        <div class="kpi-card">
                            <div class="kpi-icono verde"><i class="bi bi-people"></i></div>
                            <div>
                                <div class="kpi-numero"><?= count($pacientes) ?></div>
                                <div class="kpi-etiqueta">Pacientes registrados</div>
                            </div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-icono amarillo"><i class="bi bi-receipt"></i></div>
                            <div>
                                <div class="kpi-numero">2</div>
                                <div class="kpi-etiqueta">Cotizaciones pendientes</div>
                            </div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-icono rojo"><i class="bi bi-cash-stack"></i></div>
                            <div>
                                <div class="kpi-numero">₡150K</div>
                                <div class="kpi-etiqueta">Cobrado hoy</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel-grid <?= $esRecepcionista ? 'col-2' : '' ?>">

                    <div class="tarjeta">
                        <div class="panel-titulo">
                            <h3>Agenda del día</h3>
                            <button class="btn-odent" onclick="Citas.nueva()" style="font-size:13px;padding:7px 14px;">
                                <i class="bi bi-plus-lg"></i> Nueva cita
                            </button>
                        </div>

                        <?php if (!$citas): ?>
                            <p style="color:var(--gris-azulado);font-size:14px;">No hay citas programadas para hoy.</p>
                        <?php endif; ?>

                        <?php foreach (array_slice($citas, 0, 5) as $c): ?>
                            <div class="cita-item">
                                <div class="cita-hora"><?= $c['hora'] ?></div>
                                <div class="cita-info">
                                    <div class="cita-paciente"><?= htmlspecialchars($c['paciente']) ?></div>
                                    <div class="cita-tratamiento">
                                        <?= htmlspecialchars($c['odontologo']) ?> — <?= htmlspecialchars($c['tratamiento']) ?>
                                    </div>
                                </div>
                                <span class="badge-estado badge-<?= $c['estado'] ?>">
                                    <?= ucfirst($c['estado']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>

                        <div style="margin-top:14px;text-align:right;">
                            <button class="btn-outline-odent" onclick="mostrarPagina('agenda')" style="font-size:13px;padding:6px 14px;">
                                Ver todas las citas →
                            </button>
                        </div>
                    </div>

                    <?php if ($esRecepcionista): ?>
                        <div class="tarjeta">
                            <div class="panel-titulo">
                                <h3>Últimos pagos</h3>
                                <button class="btn-odent" onclick="abrirModal('modal-nuevo-pago')" style="font-size:13px;padding:7px 14px;">
                                    <i class="bi bi-plus-lg"></i> Registrar pago
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
                                            <td><?= htmlspecialchars($pago['paciente']) ?></td>
                                            <td>₡<?= number_format($pago['monto'], 0, ',', '.') ?></td>
                                            <td><?= $pago['metodo'] ?></td>
                                            <td><span class="badge-estado badge-<?= $pago['estado'] ?>"><?= ucfirst($pago['estado']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

            <?php include ROOT_PATH . '/views/shared/agenda_pagina.php'; ?>

            <!-- =====================================================
     INVENTARIO
====================================================== -->

            <?php if ($esRecepcionista || $esAsistenteDental): ?>

                <div class="pagina" id="pagina-inventario">

                    <div class="panel-titulo" style="margin-bottom:20px;">
                        <div>
                            <h2>Inventario</h2>
                            <p style="color:var(--gris-azulado);margin-top:5px;">
                                Control de insumos odontológicos.
                            </p>
                        </div>

                        <button class="btn-odent"
                            onclick="abrirModal('modal-nuevo-insumo')">
                            <i class="bi bi-plus-lg"></i> Registrar insumo
                        </button>
                    </div>


                    <!-- Alertas de inventario -->
                    <div id="inventario-alertas"
                        style="display:none;margin-bottom:18px;">
                    </div>


                    <div class="tarjeta">

                        <table class="tabla-odent">

                            <thead>
                                <tr>
                                    <th>Insumo</th>
                                    <th>Categoría</th>
                                    <th>Disponible</th>
                                    <th>Nivel mínimo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody id="inventario-lista">
                                <tr>
                                    <td colspan="6" style="text-align:center;">
                                        No hay insumos registrados.
                                    </td>
                                </tr>
                            </tbody>

                        </table>

                    </div>

                </div>

            <?php endif; ?>

            <?php if ($esRecepcionista || $esAsistenteDental): ?>

                <div class="pagina" id="pagina-pacientes">

                    <div class="panel-titulo" style="margin-bottom:20px;">
                        <div>
                            <h2>Pacientes</h2>
                            <p style="color:var(--gris-azulado);margin-top:5px;">
                                <?= $esAsistenteDental ? 'Consulta de pacientes registrados.' : 'Registro y gestión de pacientes.' ?>
                            </p>
                        </div>

                        <?php if ($esRecepcionista): ?>
                            <button class="btn-odent" onclick="abrirModal('modal-nuevo-paciente')">
                                <i class="bi bi-person-plus"></i> Nuevo paciente
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="tarjeta">

                        <div style="display:flex;gap:10px;margin-bottom:18px;">
                            <input type="text" id="buscar-paciente"
                                placeholder="Buscar por nombre o cédula..."
                                style="border:1.5px solid var(--borde);border-radius:8px;padding:8px 14px;font-size:14px;flex:1;">

                            <button type="button" class="btn-odent" onclick="buscarPacienteTabla()">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>

                        <table class="tabla-odent" id="tabla-pacientes">

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

                                    <tr class="fila-paciente">
                                        <td><?= htmlspecialchars($p['id']) ?></td>

                                        <td>
                                            <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                                        </td>

                                        <td><?= htmlspecialchars($p['cedula']) ?></td>
                                        <td><?= htmlspecialchars($p['telefono'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($p['correo']) ?></td>

                                        <td>
                                            <span class="badge-estado badge-<?= $p['estado'] === 'activo' ? 'confirmada' : 'cancelada' ?>">
                                                <?= ucfirst($p['estado']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if ($esRecepcionista): ?>
                                                <button class="btn-outline-odent" style="font-size:12px;padding:4px 10px;">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </button>
                                            <?php elseif ($esAsistenteDental): ?>
                                                <button class="btn-outline-odent" style="font-size:12px;padding:4px 10px;"
                                                    onclick='pacAbrirEditar(<?= htmlspecialchars(json_encode([
                                                        "id_usuario" => $p["id_usuario"] ?? $p["id"],
                                                        "nombre"     => $p["nombre"],
                                                        "cedula"     => $p["cedula"],
                                                        "telefono"   => $p["telefono"] ?? "",
                                                        "correo"     => $p["correo"],
                                                        "usuario"    => $p["usuario"] ?? "",
                                                    ]), ENT_QUOTES) ?>)'>
                                                    <i class="bi bi-pencil"></i> Editar
                                                </button>
                                            <?php endif; ?>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            <?php endif; ?>


            <?php if ($esRecepcionista): ?>

                <div class="pagina" id="pagina-cotizaciones">
                    <div class="panel-titulo" style="margin-bottom:20px;">
                        <h2>Cotizaciones</h2>
                        <button class="btn-odent" onclick="abrirModal('modal-nueva-cotizacion')">
                            <i class="bi bi-file-earmark-plus"></i> Nueva cotización
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
                                        <td><strong><?= $c['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($c['paciente']) ?></td>
                                        <td><?= $c['fecha'] ?></td>
                                        <td>₡<?= number_format($c['total'], 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge-estado badge-<?= $c['estado'] === 'aprobada' ? 'confirmada' : ($c['estado'] === 'rechazada' ? 'cancelada' : 'pendiente') ?>">
                                                <?= ucfirst($c['estado']) ?>
                                            </span>
                                        </td>
                                        <td style="display:flex;gap:6px;">
                                            <button class="btn-outline-odent" style="font-size:12px;padding:4px 10px;"><i class="bi bi-eye"></i> Ver</button>

                                            <?php if ($c['estado'] === 'aprobada'): ?>
                                                <button class="btn-odent" onclick="abrirModal('modal-nuevo-pago')" style="font-size:12px;padding:4px 10px;">
                                                    <i class="bi bi-cash"></i> Cobrar
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
                        <button class="btn-odent" onclick="abrirModal('modal-nuevo-pago')">
                            <i class="bi bi-plus-lg"></i> Registrar pago
                        </button>
                    </div>

                    <div class="kpi-grid" style="margin-bottom:22px;">
                        <div class="kpi-card">
                            <div class="kpi-icono verde"><i class="bi bi-cash-stack"></i></div>
                            <div>
                                <div class="kpi-numero">₡150K</div>
                                <div class="kpi-etiqueta">Cobrado hoy</div>
                            </div>
                        </div>

                        <div class="kpi-card">
                            <div class="kpi-icono amarillo"><i class="bi bi-hourglass"></i></div>
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
                                        <td><strong><?= $p['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($p['paciente']) ?></td>
                                        <td><?= $p['fecha'] ?></td>
                                        <td>₡<?= number_format($p['monto'], 0, ',', '.') ?></td>
                                        <td><?= $p['metodo'] ?></td>
                                        <td><?= $p['cotizacion'] ?></td>
                                        <td><span class="badge-estado badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>

            <?php if ($esRecepcionista || $esAsistenteDental): ?>
        <!-- ───────────────── EXPEDIENTES (asistente_dental) ─── -->
        <div class="pagina" id="pagina-expedientes">

            <!-- Vista: lista de pacientes -->
            <div id="exp-vista-lista">
                <div class="panel-titulo" style="margin-bottom:20px;">
                    <div>
                        <h2>Expedientes clínicos</h2>
                        <p style="font-size:14px;color:var(--gris-azulado);margin-top:4px;">
                            Seleccione un paciente para ver o crear su expediente
                        </p>
                    </div>
                    <div style="display:flex;gap:10px;">
                        <input type="text" id="exp-buscar" placeholder="Buscar por nombre o cédula…"
                            style="border:1.5px solid var(--borde);border-radius:8px;padding:8px 14px;font-size:14px;min-width:240px;"
                            oninput="expBuscar(this.value)">
                        <button class="btn-odent" onclick="expBuscar(document.getElementById('exp-buscar').value)">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </div>

                <div class="tarjeta">
                    <table class="tabla-odent">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Paciente</th>
                                <th>Cédula</th>
                                <th>Edad</th>
                                <th>Grupo sanguíneo</th>
                                <th>Estado</th>
                                <th>Expediente</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="exp-tbody">
                            <tr><td colspan="8" style="text-align:center;color:var(--gris-azulado);">Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Vista: detalle de expediente de un paciente -->
            <div id="exp-vista-detalle" style="display:none;">
                <div class="panel-titulo" style="margin-bottom:20px;">
                    <div>
                        <button class="btn-outline-odent" onclick="expVolverLista()" style="margin-bottom:8px;font-size:13px;">
                            <i class="bi bi-arrow-left"></i> Volver
                        </button>
                        <h2 id="exp-detalle-nombre">Expediente clínico</h2>
                        <p id="exp-detalle-cedula" style="font-size:14px;color:var(--gris-azulado);margin-top:2px;"></p>
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button class="btn-outline-odent" onclick="abrirOdontograma()" id="btn-ver-odontograma" style="display:none;">
                            <i class="bi bi-grid-3x3"></i> Odontograma
                        </button>
                        <button class="btn-outline-odent" onclick="abrirDocumentos()" id="btn-ver-documentos" style="display:none;">
                            <i class="bi bi-file-earmark-medical"></i> Documentos
                        </button>
                        <button class="btn-outline-odent" onclick="abrirEditarPaciente()" id="btn-editar-pac" style="display:none;">
                            <i class="bi bi-person-gear"></i> Editar paciente
                        </button>
                        <button class="btn-outline-odent" onclick="abrirEditarExpediente()" id="btn-editar-exp" style="display:none;">
                            <i class="bi bi-pencil"></i> Editar expediente
                        </button>
                        <button class="btn-odent" onclick="abrirRegistrarAtencion()">
                            <i class="bi bi-plus-lg"></i> Registrar atención
                        </button>
                    </div>
                </div>

                <!-- Sin expediente aún -->
                <div id="exp-sin-expediente" style="display:none;">
                    <div class="tarjeta" style="text-align:center;padding:40px 20px;">
                        <i class="bi bi-folder2-open" style="font-size:48px;color:var(--gris-azulado);display:block;margin-bottom:16px;"></i>
                        <h3 style="margin-bottom:8px;">Este paciente no tiene expediente clínico</h3>
                        <p style="color:var(--gris-azulado);font-size:14px;margin-bottom:24px;">
                            Cree el expediente para registrar la historia clínica y las atenciones del paciente.
                        </p>
                        <button class="btn-odent" onclick="abrirModal('modal-exp-crear')">
                            <i class="bi bi-file-earmark-medical"></i> Crear expediente
                        </button>
                    </div>
                </div>

                <!-- Con expediente -->
                <div id="exp-con-expediente" style="display:none;">
                    <div class="panel-grid col-2" style="margin-bottom:24px;">
                        <div class="tarjeta">
                            <h3 style="margin-bottom:16px;"><i class="bi bi-clipboard2-pulse" style="color:var(--azul-odent);margin-right:6px;"></i>Datos del expediente</h3>
                            <div class="campo-grupo" style="margin-bottom:10px;">
                                <label style="font-size:12px;color:var(--gris-azulado);">Motivo de consulta</label>
                                <p id="exp-motivo" style="font-size:14px;margin:0;"></p>
                            </div>
                            <div class="panel-grid col-2" style="gap:10px;">
                                <div class="campo-grupo" style="margin-bottom:10px;">
                                    <label style="font-size:12px;color:var(--gris-azulado);">Alergias</label>
                                    <p id="exp-alergias" style="font-size:14px;margin:0;"></p>
                                </div>
                                <div class="campo-grupo" style="margin-bottom:10px;">
                                    <label style="font-size:12px;color:var(--gris-azulado);">Enfermedades</label>
                                    <p id="exp-enfermedades" style="font-size:14px;margin:0;"></p>
                                </div>
                            </div>
                            <div class="campo-grupo" style="margin-bottom:10px;">
                                <label style="font-size:12px;color:var(--gris-azulado);">Medicamentos actuales</label>
                                <p id="exp-medicamentos" style="font-size:14px;margin:0;"></p>
                            </div>
                            <div class="campo-grupo" style="margin-bottom:10px;">
                                <label style="font-size:12px;color:var(--gris-azulado);">Antecedentes</label>
                                <p id="exp-antecedentes" style="font-size:14px;margin:0;"></p>
                            </div>
                            <div class="campo-grupo" style="margin-bottom:0;">
                                <label style="font-size:12px;color:var(--gris-azulado);">Observaciones generales</label>
                                <p id="exp-observaciones" style="font-size:14px;margin:0;"></p>
                            </div>
                        </div>

                        <div class="tarjeta">
                            <h3 style="margin-bottom:16px;"><i class="bi bi-person-vcard" style="color:var(--azul-odent);margin-right:6px;"></i>Datos del paciente</h3>
                            <div class="panel-grid col-2" style="gap:10px;">
                                <div class="campo-grupo" style="margin-bottom:10px;">
                                    <label style="font-size:12px;color:var(--gris-azulado);">Fecha de nacimiento</label>
                                    <p id="exp-pac-fnac" style="font-size:14px;margin:0;"></p>
                                </div>
                                <div class="campo-grupo" style="margin-bottom:10px;">
                                    <label style="font-size:12px;color:var(--gris-azulado);">Edad</label>
                                    <p id="exp-pac-edad" style="font-size:14px;margin:0;"></p>
                                </div>
                                <div class="campo-grupo" style="margin-bottom:10px;">
                                    <label style="font-size:12px;color:var(--gris-azulado);">Sexo</label>
                                    <p id="exp-pac-sexo" style="font-size:14px;margin:0;"></p>
                                </div>
                                <div class="campo-grupo" style="margin-bottom:10px;">
                                    <label style="font-size:12px;color:var(--gris-azulado);">Grupo sanguíneo</label>
                                    <p id="exp-pac-sangre" style="font-size:14px;margin:0;"></p>
                                </div>
                            </div>
                            <div class="campo-grupo" style="margin-bottom:10px;">
                                <label style="font-size:12px;color:var(--gris-azulado);">Teléfono</label>
                                <p id="exp-pac-tel" style="font-size:14px;margin:0;"></p>
                            </div>
                            <div class="campo-grupo" style="margin-bottom:0;">
                                <label style="font-size:12px;color:var(--gris-azulado);">Correo electrónico</label>
                                <p id="exp-pac-correo" style="font-size:14px;margin:0;"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de atenciones -->
                    <div class="tarjeta">
                        <div class="panel-titulo" style="margin-bottom:16px;">
                            <h3><i class="bi bi-journal-medical" style="color:var(--azul-odent);margin-right:6px;"></i>Historial de atenciones</h3>
                            <span id="exp-atenciones-count" style="font-size:13px;color:var(--gris-azulado);"></span>
                        </div>
                        <div id="exp-atenciones-lista">
                            <div style="text-align:center;padding:20px;color:var(--gris-azulado);">Cargando atenciones…</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ───────────────── ODONTOGRAMA (asistente_dental) ─── -->
        <div class="pagina" id="pagina-odontograma">
            <div class="panel-titulo" style="margin-bottom:6px;">
                <div>
                    <button class="btn-outline-odent" id="odo-btn-volver" onclick="odoVolverExpediente()" style="margin-bottom:8px;font-size:13px;">
                        <i class="bi bi-arrow-left"></i> <span id="odo-btn-volver-texto">Volver al expediente</span>
                    </button>
                    <h2>Odontograma</h2>
                    <p id="odo-cabecera-paciente" style="font-size:14px;color:var(--gris-azulado);margin-top:2px;">
                        Haga clic en un diente para editar su estado
                    </p>
                </div>
            </div>

            <div id="odo-sin-expediente" style="display:none;">
                <div class="tarjeta">
                    <div class="panel-titulo" style="margin-bottom:16px;">
                        <div>
                            <h3 style="margin:0;">Seleccionar paciente</h3>
                            <p style="color:var(--gris-azulado);font-size:13px;margin:4px 0 0;">
                                Elija un paciente para ver o editar su odontograma
                            </p>
                        </div>
                    </div>
                    <div style="margin-bottom:14px;">
                        <input type="text" id="odo-buscar-paciente" placeholder="Buscar paciente…"
                               style="width:100%;padding:8px 12px;border:1px solid var(--borde);border-radius:6px;font-size:14px;"
                               oninput="odoBuscarPaciente(this.value)">
                    </div>
                    <div id="odo-lista-pacientes" style="max-height:420px;overflow-y:auto;">
                        <div style="text-align:center;padding:30px;color:var(--gris-azulado);">
                            <div class="spinner-border spinner-border-sm" role="status"></div> Cargando pacientes…
                        </div>
                    </div>
                </div>
            </div>

            <div id="odo-con-expediente" style="display:none;">
                <div class="panel-grid col-2">
                    <div class="odontograma-container">
                        <p style="font-size:12px;color:var(--gris-azulado);margin-bottom:12px;text-align:center;font-weight:600;">MAXILAR SUPERIOR</p>
                        <div class="odontograma-fila" id="odo-fila-superior"></div>
                        <div style="border-top:2px dashed var(--borde);margin:10px 0;"></div>
                        <p style="font-size:12px;color:var(--gris-azulado);margin-bottom:12px;text-align:center;font-weight:600;">MAXILAR INFERIOR</p>
                        <div class="odontograma-fila" id="odo-fila-inferior"></div>
                        <div class="leyenda-odontograma">
                            <div class="leyenda-item"><div class="leyenda-color" style="background:#fff;border-color:var(--borde);"></div> Sano</div>
                            <div class="leyenda-item"><div class="leyenda-color" style="background:rgba(192,57,43,.2);border-color:#C0392B;"></div> Caries</div>
                            <div class="leyenda-item"><div class="leyenda-color" style="background:rgba(245,158,11,.2);border-color:#f59e0b;"></div> Corona</div>
                            <div class="leyenda-item"><div class="leyenda-color" style="background:var(--fondo-odent);border-color:var(--gris-azulado);"></div> Ausente</div>
                            <div class="leyenda-item"><div class="leyenda-color" style="background:rgba(139,92,246,.15);border-color:#7c3aed;"></div> Endodoncia</div>
                            <div class="leyenda-item"><div class="leyenda-color" style="background:rgba(239,68,68,.15);border-color:#ef4444;"></div> Extracción</div>
                            <div class="leyenda-item"><div class="leyenda-color" style="background:rgba(16,185,129,.15);border-color:#10b981;"></div> Implante</div>
                        </div>
                    </div>

                    <div class="tarjeta">
                        <h3 style="margin-bottom:14px;">Detalle de pieza</h3>
                        <div id="odo-hint">
                            <div class="alerta info"><i class="bi bi-hand-index"></i> Seleccione un diente para editar su estado.</div>
                        </div>
                        <div id="odo-loading" style="display:none;text-align:center;padding:20px;color:var(--gris-azulado);">
                            <div class="spinner-border spinner-border-sm" role="status" style="margin-right:6px;"></div> Cargando…
                        </div>
                        <div id="odo-panel-edicion" style="display:none;">
                            <div class="campo-grupo">
                                <label>Pieza dental (FDI)</label>
                                <input type="text" id="odo-numero" readonly style="background:var(--fondo-odent);">
                            </div>
                            <div class="campo-grupo">
                                <label>Estado</label>
                                <select id="odo-estado">
                                    <option value="sano">Sano</option>
                                    <option value="caries">Caries</option>
                                    <option value="corona">Corona</option>
                                    <option value="ausente">Ausente</option>
                                    <option value="endodoncia">Endodoncia</option>
                                    <option value="extraccion">Extracción</option>
                                    <option value="implante">Implante</option>
                                </select>
                            </div>
                            <div class="campo-grupo">
                                <label>Observaciones</label>
                                <textarea id="odo-observaciones" rows="3" placeholder="Notas clínicas opcionales…"></textarea>
                            </div>
                            <div id="odo-ultima-mod" style="font-size:12px;color:var(--gris-azulado);margin-bottom:14px;display:none;">
                                <i class="bi bi-clock-history"></i> Última modificación: <span id="odo-fecha-mod"></span>
                            </div>
                            <div style="display:flex;gap:10px;">
                                <button class="btn-odent" id="odo-btn-guardar" onclick="odoGuardarDiente()">
                                    <i class="bi bi-check-lg"></i> Guardar
                                </button>
                                <button class="btn-outline-odent" onclick="odoCancelarEdicion()">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ───────────────── DOCUMENTOS (asistente_dental) ─── -->
        <div class="pagina" id="pagina-documentos">

            <div class="panel-titulo" style="margin-bottom:6px;">
                <div>
                    <button class="btn-outline-odent" id="doc-btn-volver" onclick="docVolverExpediente()" style="margin-bottom:8px;font-size:13px;">
                        <i class="bi bi-arrow-left"></i> <span id="doc-btn-volver-texto">Volver al expediente</span>
                    </button>
                    <h2>Documentos clínicos</h2>
                    <p id="doc-cabecera-paciente" style="font-size:14px;color:var(--gris-azulado);margin-top:2px;">
                        Archivos adjuntos del expediente
                    </p>
                </div>
                <button class="btn-odent" id="doc-btn-subir-cabecera" onclick="abrirModal('modal-doc-subir')" style="display:none;">
                    <i class="bi bi-upload"></i> Subir documento
                </button>
            </div>

            <div id="doc-sin-expediente">
                <div class="tarjeta">
                    <div class="panel-titulo" style="margin-bottom:16px;">
                        <div>
                            <h3 style="margin:0;">Seleccionar paciente</h3>
                            <p style="color:var(--gris-azulado);font-size:13px;margin:4px 0 0;">
                                Elija un paciente para ver o subir sus documentos clínicos
                            </p>
                        </div>
                    </div>
                    <div style="margin-bottom:14px;">
                        <input type="text" id="doc-buscar-paciente" placeholder="Buscar paciente…"
                               style="width:100%;padding:8px 12px;border:1px solid var(--borde);border-radius:6px;font-size:14px;"
                               oninput="docBuscarPaciente(this.value)">
                    </div>
                    <div id="doc-lista-pacientes" style="max-height:420px;overflow-y:auto;">
                        <div style="text-align:center;padding:30px;color:var(--gris-azulado);">
                            <div class="spinner-border spinner-border-sm" role="status"></div> Cargando pacientes…
                        </div>
                    </div>
                </div>
            </div>

            <div id="doc-con-expediente" style="display:none;">
                <div class="tarjeta">
                    <div class="panel-titulo" style="margin-bottom:16px;">
                        <h3><i class="bi bi-paperclip" style="color:var(--azul-odent);margin-right:6px;"></i>Archivos adjuntos</h3>
                        <span id="doc-count" style="font-size:13px;color:var(--gris-azulado);"></span>
                    </div>
                    <div id="doc-loading" style="text-align:center;padding:20px;color:var(--gris-azulado);">
                        <div class="spinner-border spinner-border-sm" role="status" style="margin-right:6px;"></div> Cargando documentos…
                    </div>
                    <div id="doc-lista" style="display:none;"></div>
                    <div id="doc-vacio" style="display:none;text-align:center;padding:30px 20px;">
                        <i class="bi bi-file-earmark-x" style="font-size:40px;color:var(--gris-azulado);display:block;margin-bottom:12px;"></i>
                        <p style="color:var(--gris-azulado);font-size:14px;margin-bottom:16px;">Este expediente no tiene documentos adjuntos aún.</p>
                        <button class="btn-odent" onclick="abrirModal('modal-doc-subir')">
                            <i class="bi bi-upload"></i> Subir primer documento
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal subir documento -->
            <?php endif; ?>

        </div>

    </div>

    <?php include ROOT_PATH . '/views/shared/agenda_modales.php'; ?>

    <?php if ($esRecepcionista): ?>

        <div class="modal-overlay" id="modal-nuevo-paciente">
            <div class="modal-caja">
                <div class="modal-header">
                    <h3>Registrar paciente</h3>
                    <button class="modal-cerrar" onclick="cerrarModal('modal-nuevo-paciente')"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="campo-grupo"><label>Nombre completo</label><input type="text" placeholder="Nombre y apellidos"></div>
                <div class="campo-grupo"><label>Cédula</label><input type="text" placeholder="0-0000-0000"></div>
                <div class="campo-grupo"><label>Teléfono</label><input type="tel" placeholder="0000-0000"></div>
                <div class="campo-grupo"><label>Correo electrónico</label><input type="email" placeholder="correo@ejemplo.com"></div>
                <div class="campo-grupo"><label>Fecha de nacimiento</label><input type="date"></div>
                <div class="modal-footer">
                    <button class="btn-outline-odent" onclick="cerrarModal('modal-nuevo-paciente')">Cancelar</button>
                    <button class="btn-odent"><i class="bi bi-check-lg"></i> Guardar</button>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="modal-nuevo-pago">
            <div class="modal-caja">
                <div class="modal-header">
                    <h3>Registrar pago</h3>
                    <button class="modal-cerrar" onclick="cerrarModal('modal-nuevo-pago')"><i class="bi bi-x-lg"></i></button>
                </div>

                <div class="campo-grupo">
                    <label>Cotización</label>
                    <select>
                        <?php foreach ($cotizaciones as $c): if ($c['estado'] === 'aprobada'): ?>
                                <option value="<?= $c['id'] ?>"><?= $c['id'] ?> — <?= htmlspecialchars($c['paciente']) ?> (₡<?= number_format($c['total'], 0, ',', '.') ?>)</option>
                        <?php endif;
                        endforeach; ?>
                    </select>
                </div>

                <div class="campo-grupo"><label>Monto recibido (₡)</label><input type="number" placeholder="0"></div>

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

                <div class="campo-grupo"><label>Comprobante (opcional)</label><input type="text" placeholder="N.° comprobante o referencia"></div>

                <div class="modal-footer">
                    <button class="btn-outline-odent" onclick="cerrarModal('modal-nuevo-pago')">Cancelar</button>
                    <button class="btn-odent"><i class="bi bi-check-lg"></i> Registrar</button>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="modal-nueva-cotizacion">
            <div class="modal-caja">
                <div class="modal-header">
                    <h3>Nueva cotización</h3>
                    <button class="modal-cerrar" onclick="cerrarModal('modal-nueva-cotizacion')"><i class="bi bi-x-lg"></i></button>
                </div>

                <div class="campo-grupo">
                    <label>Paciente</label>
                    <select>
                        <option value="">Seleccione un paciente</option>
                        <?php foreach ($pacientes as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo-grupo"><label>Tratamiento</label><input type="text" placeholder="Descripción del tratamiento"></div>
                <div class="campo-grupo"><label>Monto (₡)</label><input type="number" placeholder="0" id="cot-monto"></div>
                <div class="campo-grupo"><label>IVA (13%)</label><input type="text" id="cot-iva" readonly placeholder="Se calcula automáticamente"></div>
                <div class="campo-grupo"><label>Total</label><input type="text" id="cot-total" readonly placeholder="Se calcula automáticamente"></div>
                <div class="campo-grupo"><label>Observaciones</label><textarea rows="2" placeholder="Notas adicionales..."></textarea></div>

                <div class="modal-footer">
                    <button class="btn-outline-odent" onclick="cerrarModal('modal-nueva-cotizacion')">Cancelar</button>
                    <button class="btn-odent"><i class="bi bi-check-lg"></i> Crear cotización</button>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <!-- =============================================================
     MODAL INVENTARIO
============================================================= -->

    <?php if ($esRecepcionista || $esAsistenteDental): ?>

        <!-- Registrar insumo -->
        <div class="modal-overlay" id="modal-nuevo-insumo">
            <div class="modal-caja">

                <div class="modal-header">
                    <h3>Registrar insumo</h3>

                    <button
                        class="modal-cerrar"
                        onclick="cerrarModal('modal-nuevo-insumo')">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form id="form-nuevo-insumo">

                    <div class="campo-grupo">
                        <label>Nombre *</label>
                        <input
                            type="text"
                            id="insumo-nombre"
                            name="nombre">
                    </div>

                    <div class="campo-grupo">
                        <label>Descripción *</label>
                        <textarea
                            id="insumo-descripcion"
                            name="descripcion"
                            rows="3"></textarea>
                    </div>

                    <div class="campo-grupo">
                        <label>Categoría *</label>

                        <select
                            id="insumo-categoria"
                            name="categoria">

                            <option value="">
                                Seleccione una categoría
                            </option>

                            <option value="Protección">
                                Protección
                            </option>

                            <option value="Higiene y desinfección">
                                Higiene y desinfección
                            </option>

                            <option value="Material clínico">
                                Material clínico
                            </option>

                            <option value="Anestesia">
                                Anestesia
                            </option>

                            <option value="Restauración">
                                Restauración
                            </option>

                            <option value="Endodoncia">
                                Endodoncia
                            </option>

                            <option value="Impresión">
                                Impresión
                            </option>

                            <option value="Otros">
                                Otros
                            </option>

                        </select>
                    </div>

                    <div class="campo-grupo">
                        <label>Cantidad disponible *</label>

                        <div style="display:flex;gap:8px;align-items:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('insumo-cantidad', -1)">
                                −
                            </button>

                            <input
                                type="number"
                                id="insumo-cantidad"
                                name="cantidad_disponible"
                                min="0"
                                step="1"
                                value="0"
                                style="text-align:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('insumo-cantidad', 1)">
                                +
                            </button>

                        </div>
                    </div>

                    <div class="campo-grupo">
                        <label>Nivel mínimo de alerta *</label>

                        <div style="display:flex;gap:8px;align-items:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('insumo-minimo', -1)">
                                −
                            </button>

                            <input
                                type="number"
                                id="insumo-minimo"
                                name="nivel_minimo"
                                min="0"
                                step="1"
                                value="0"
                                style="text-align:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('insumo-minimo', 1)">
                                +
                            </button>

                        </div>
                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn-outline-odent"
                            onclick="cerrarModal('modal-nuevo-insumo')">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn-odent">
                            <i class="bi bi-check-lg"></i>
                            Guardar
                        </button>

                    </div>

                </form>

            </div>
        </div>


        <!-- Editar insumo -->
        <div class="modal-overlay" id="modal-editar-insumo">
            <div class="modal-caja">

                <div class="modal-header">
                    <h3>Editar insumo</h3>

                    <button
                        class="modal-cerrar"
                        onclick="cerrarModal('modal-editar-insumo')">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form id="form-editar-insumo">

                    <input
                        type="hidden"
                        id="editar-insumo-id">

                    <div class="campo-grupo">
                        <label>Nombre *</label>

                        <input
                            type="text"
                            id="editar-insumo-nombre">
                    </div>

                    <div class="campo-grupo">
                        <label>Descripción *</label>

                        <textarea
                            id="editar-insumo-descripcion"
                            rows="3"></textarea>
                    </div>

                    <div class="campo-grupo">
                        <label>Categoría *</label>

                        <select id="editar-insumo-categoria">

                            <option value="">
                                Seleccione una categoría
                            </option>

                            <option value="Protección">
                                Protección
                            </option>

                            <option value="Higiene y desinfección">
                                Higiene y desinfección
                            </option>

                            <option value="Material clínico">
                                Material clínico
                            </option>

                            <option value="Anestesia">
                                Anestesia
                            </option>

                            <option value="Restauración">
                                Restauración
                            </option>

                            <option value="Endodoncia">
                                Endodoncia
                            </option>

                            <option value="Impresión">
                                Impresión
                            </option>

                            <option value="Otros">
                                Otros
                            </option>

                        </select>
                    </div>

                    <div class="campo-grupo">
                        <label>Nivel mínimo de alerta *</label>

                        <input
                            type="number"
                            id="editar-insumo-minimo"
                            min="0">
                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn-outline-odent"
                            onclick="cerrarModal('modal-editar-insumo')">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn-odent">
                            <i class="bi bi-check-lg"></i>
                            Guardar cambios
                        </button>

                    </div>

                </form>

            </div>
        </div>


        <!-- Entrada / salida de inventario -->
        <div class="modal-overlay" id="modal-movimiento-inventario">
            <div class="modal-caja">

                <div class="modal-header">

                    <h3 id="movimiento-titulo">
                        Movimiento de inventario
                    </h3>

                    <button
                        class="modal-cerrar"
                        onclick="cerrarModal('modal-movimiento-inventario')">
                        <i class="bi bi-x-lg"></i>
                    </button>

                </div>

                <form id="form-movimiento-inventario">

                    <input
                        type="hidden"
                        id="movimiento-insumo-id">

                    <input
                        type="hidden"
                        id="movimiento-tipo">

                    <div class="campo-grupo">
                        <label>Insumo</label>

                        <input
                            type="text"
                            id="movimiento-insumo-nombre"
                            readonly>
                    </div>

                    <div class="campo-grupo">
                        <label>Stock actual</label>

                        <input
                            type="number"
                            id="movimiento-stock-actual"
                            readonly>
                    </div>

                    <div class="campo-grupo">
                        <label>Cantidad *</label>

                        <div style="display:flex;gap:8px;align-items:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('movimiento-cantidad', -1)">
                                −
                            </button>

                            <input
                                type="number"
                                id="movimiento-cantidad"
                                min="1"
                                step="1"
                                value="1"
                                style="text-align:center;">

                            <button
                                type="button"
                                class="btn-outline-odent"
                                onclick="cambiarCantidad('movimiento-cantidad', 1)">
                                +
                            </button>

                        </div>
                    </div>

                    <div class="campo-grupo">
                        <label>Observación</label>

                        <textarea
                            id="movimiento-observacion"
                            rows="3"
                            placeholder="Ej. Compra de materiales, uso clínico, ajuste de inventario..."></textarea>
                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn-outline-odent"
                            onclick="cerrarModal('modal-movimiento-inventario')">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn-odent"
                            id="movimiento-boton">
                            <i class="bi bi-check-lg"></i>
                            Registrar
                        </button>

                    </div>

                </form>

            </div>
        </div>


        <!-- Historial de movimientos -->
        <div class="modal-overlay" id="modal-historial-inventario">

            <div
                class="modal-caja"
                style="max-width:850px;">

                <div class="modal-header">

                    <div>
                        <h3>Historial de movimientos</h3>

                        <div
                            id="historial-insumo-nombre"
                            style="
                            font-size:13px;
                            color:var(--gris-azulado);
                            margin-top:4px;
                        ">
                        </div>
                    </div>

                    <button
                        class="modal-cerrar"
                        onclick="cerrarModal('modal-historial-inventario')">
                        <i class="bi bi-x-lg"></i>
                    </button>

                </div>

                <div style="overflow-x:auto;">

                    <table class="tabla-odent">

                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Anterior</th>
                                <th>Resultante</th>
                                <th>Observación</th>
                            </tr>
                        </thead>

                        <tbody id="inventario-historial-lista">

                            <tr>
                                <td
                                    colspan="6"
                                    style="text-align:center;">
                                    No hay movimientos registrados.
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn-outline-odent"
                        onclick="cerrarModal('modal-historial-inventario')">
                        Cerrar
                    </button>

                </div>

            </div>

        </div>

    <?php endif; ?>

    <script>
        function buscarPacienteTabla() {
            const texto = document.getElementById('buscar-paciente').value.toLowerCase().trim();
            const filas = document.querySelectorAll('#tabla-pacientes .fila-paciente');

            filas.forEach(fila => {
                const contenido = fila.textContent.toLowerCase();
                fila.style.display = contenido.includes(texto) ? '' : 'none';
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const campo = document.getElementById('buscar-paciente');

            if (campo) {
                campo.addEventListener('input', buscarPacienteTabla);
            }
        });
    </script>

    <?php if ($esRecepcionista || $esAsistenteDental): ?>

        <!-- Modal editar datos básicos de paciente (recepcionista / asistente_dental) -->
        <div class="modal-overlay" id="modal-pac-editar-basico">
            <div class="modal-caja" style="max-width:480px;">
                <div class="modal-header">
                    <h3><i class="bi bi-person-gear" style="color:var(--azul-odent);margin-right:6px;"></i>Editar paciente</h3>
                    <button class="modal-cerrar" onclick="cerrarModal('modal-pac-editar-basico')"><i class="bi bi-x-lg"></i></button>
                </div>
                <input type="hidden" id="pac-edit-id-usuario">
                <div class="campo-grupo">
                    <label>Nombre completo *</label>
                    <input type="text" id="pac-edit-nombre" maxlength="150" autocomplete="off">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="campo-grupo">
                        <label>Identificación *</label>
                        <input type="text" id="pac-edit-cedula" maxlength="30" autocomplete="off">
                    </div>
                    <div class="campo-grupo">
                        <label>Teléfono</label>
                        <input type="text" id="pac-edit-telefono" maxlength="20" autocomplete="off">
                    </div>
                </div>
                <div class="campo-grupo">
                    <label>Correo electrónico *</label>
                    <input type="email" id="pac-edit-correo" maxlength="150" autocomplete="off">
                </div>
                <div class="campo-grupo">
                    <label>Nombre de usuario *</label>
                    <input type="text" id="pac-edit-usuario" maxlength="60" autocomplete="off"
                        placeholder="Sin espacios ni caracteres especiales">
                </div>
                <div class="modal-footer">
                    <button class="btn-outline-odent" onclick="cerrarModal('modal-pac-editar-basico')">Cancelar</button>
                    <button class="btn-odent" id="pac-edit-btn-guardar" onclick="pacGuardarEditar()">
                        <i class="bi bi-check-lg"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="modal-doc-subir">
            <div class="modal-caja" style="max-width:480px;">
                <div class="modal-header">
                    <h3><i class="bi bi-upload" style="margin-right:6px;"></i>Subir documento</h3>
                    <button class="modal-cerrar" onclick="cerrarModal('modal-doc-subir')"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="campo-grupo">
                    <label>Archivo *</label>
                    <input type="file" id="doc-archivo" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                    <small style="color:var(--gris-azulado);font-size:12px;">PDF, imagen (JPG/PNG/WEBP) o Word — máximo 8 MB</small>
                </div>
                <div class="campo-grupo">
                    <label>Descripción <span style="color:var(--gris-azulado);font-size:12px;">(opcional)</span></label>
                    <input type="text" id="doc-descripcion" placeholder="Ej. Radiografía panorámica, Consentimiento informado…" maxlength="500">
                </div>
                <div class="modal-footer">
                    <button class="btn-outline-odent" onclick="cerrarModal('modal-doc-subir')">Cancelar</button>
                    <button class="btn-odent" id="doc-btn-subir" onclick="docSubir()">
                        <i class="bi bi-upload"></i> Subir
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal confirmar eliminación documento -->
        <div class="modal-overlay" id="modal-doc-confirmar-eliminar">
            <div class="modal-caja" style="max-width:420px;">
                <div class="modal-header">
                    <h3>Eliminar documento</h3>
                    <button class="modal-cerrar" onclick="cerrarModal('modal-doc-confirmar-eliminar')"><i class="bi bi-x-lg"></i></button>
                </div>
                <p style="padding:20px 0 10px;font-size:14px;">
                    ¿Está seguro que desea eliminar <strong id="doc-nombre-a-eliminar"></strong>?<br>
                    <span style="color:var(--rojo-odent,#C0392B);font-size:13px;">Esta acción no se puede deshacer.</span>
                </p>
                <div class="modal-footer">
                    <button class="btn-outline-odent" onclick="cerrarModal('modal-doc-confirmar-eliminar')">Cancelar</button>
                    <button class="btn-peligro" id="doc-btn-confirmar-eliminar" onclick="docConfirmarEliminar()">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal crear expediente -->
        <div class="modal-overlay" id="modal-exp-crear">
            <div class="modal-caja">
                <div class="modal-header">
                    <h3>Crear expediente clínico</h3>
                    <button class="modal-cerrar" onclick="cerrarModal('modal-exp-crear')"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="campo-grupo">
                    <label>Motivo de consulta *</label>
                    <textarea id="ec-motivo" rows="3" placeholder="Describa el motivo principal de consulta del paciente…"></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="campo-grupo">
                        <label>Alergias</label>
                        <textarea id="ec-alergias" rows="2" placeholder="Alergias conocidas…"></textarea>
                    </div>
                    <div class="campo-grupo">
                        <label>Enfermedades</label>
                        <textarea id="ec-enfermedades" rows="2" placeholder="Enfermedades de base…"></textarea>
                    </div>
                </div>
                <div class="campo-grupo">
                    <label>Medicamentos actuales</label>
                    <textarea id="ec-medicamentos" rows="2" placeholder="Medicamentos que toma actualmente…"></textarea>
                </div>
                <div class="campo-grupo">
                    <label>Antecedentes</label>
                    <textarea id="ec-antecedentes" rows="2" placeholder="Antecedentes médicos o quirúrgicos relevantes…"></textarea>
                </div>
                <div class="campo-grupo">
                    <label>Observaciones</label>
                    <textarea id="ec-observaciones" rows="2" placeholder="Observaciones generales…"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn-outline-odent" onclick="cerrarModal('modal-exp-crear')">Cancelar</button>
                    <button class="btn-odent" onclick="guardarCrearExpediente()">
                        <i class="bi bi-file-earmark-check"></i> Crear expediente
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal editar expediente -->
        <div class="modal-overlay" id="modal-exp-editar">
            <div class="modal-caja">
                <div class="modal-header">
                    <h3>Editar expediente clínico</h3>
                    <button class="modal-cerrar" onclick="cerrarModal('modal-exp-editar')"><i class="bi bi-x-lg"></i></button>
                </div>
                <input type="hidden" id="ee-id-expediente">
                <div class="campo-grupo">
                    <label>Motivo de consulta *</label>
                    <textarea id="ee-motivo" rows="3"></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="campo-grupo">
                        <label>Alergias</label>
                        <textarea id="ee-alergias" rows="2"></textarea>
                    </div>
                    <div class="campo-grupo">
                        <label>Enfermedades</label>
                        <textarea id="ee-enfermedades" rows="2"></textarea>
                    </div>
                </div>
                <div class="campo-grupo">
                    <label>Medicamentos actuales</label>
                    <textarea id="ee-medicamentos" rows="2"></textarea>
                </div>
                <div class="campo-grupo">
                    <label>Antecedentes</label>
                    <textarea id="ee-antecedentes" rows="2"></textarea>
                </div>
                <div class="campo-grupo">
                    <label>Observaciones</label>
                    <textarea id="ee-observaciones" rows="2"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn-outline-odent" onclick="cerrarModal('modal-exp-editar')">Cancelar</button>
                    <button class="btn-odent" onclick="guardarEditarExpediente()">
                        <i class="bi bi-check-lg"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal editar datos del paciente -->
        <div class="modal-overlay" id="modal-editar-paciente">
            <div class="modal-caja">
                <div class="modal-header">
                    <h3><i class="bi bi-person-gear" style="color:var(--azul-odent);margin-right:6px;"></i>Datos del paciente</h3>
                    <button class="modal-cerrar" onclick="cerrarModal('modal-editar-paciente')"><i class="bi bi-x-lg"></i></button>
                </div>
                <input type="hidden" id="ep-id-paciente">
                <div class="campo-grupo">
                    <label>Fecha de nacimiento</label>
                    <input type="date" id="ep-fecha-nac" max="<?= date('Y-m-d') ?>"
                           oninput="epActualizarEdad(this.value)">
                    <small id="ep-edad-preview" style="color:var(--gris-azulado);font-size:12px;margin-top:4px;display:block;"></small>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                    <div class="campo-grupo">
                        <label>Sexo</label>
                        <select id="ep-sexo">
                            <option value="">— Sin especificar —</option>
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="campo-grupo">
                        <label>Grupo sanguíneo</label>
                        <select id="ep-sangre">
                            <option value="">— Sin especificar —</option>
                            <option value="A+">A+</option>
                            <option value="A-">A−</option>
                            <option value="B+">B+</option>
                            <option value="B-">B−</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB−</option>
                            <option value="O+">O+</option>
                            <option value="O-">O−</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn-outline-odent" onclick="cerrarModal('modal-editar-paciente')">Cancelar</button>
                    <button class="btn-odent" id="ep-btn-guardar" onclick="guardarEditarPaciente()">
                        <i class="bi bi-check-lg"></i> Guardar cambios
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal registrar atención -->
        <div class="modal-overlay" id="modal-exp-atencion">
            <div class="modal-caja">
                <div class="modal-header">
                    <h3>Registrar atención clínica</h3>
                    <button class="modal-cerrar" onclick="cerrarModal('modal-exp-atencion')"><i class="bi bi-x-lg"></i></button>
                </div>
                <input type="hidden" id="ea-id-expediente">
                <div style="background:var(--fondo-odent);border-radius:8px;padding:10px 14px;margin-bottom:16px;">
                    <p style="font-size:13px;font-weight:600;margin:0;" id="ea-paciente-nombre"></p>
                </div>
                <div class="campo-grupo">
                    <label>Diagnóstico *</label>
                    <textarea id="ea-diagnostico" rows="3" placeholder="Diagnóstico de la atención…"></textarea>
                </div>
                <div class="campo-grupo">
                    <label>Tratamiento realizado</label>
                    <textarea id="ea-tratamiento" rows="2" placeholder="Procedimiento o tratamiento realizado en esta sesión…"></textarea>
                </div>
                <div class="campo-grupo">
                    <label>Observaciones</label>
                    <textarea id="ea-observaciones" rows="2" placeholder="Notas adicionales…"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn-outline-odent" onclick="cerrarModal('modal-exp-atencion')">Cancelar</button>
                    <button class="btn-odent" onclick="guardarAtencion()">
                        <i class="bi bi-journal-plus"></i> Registrar atención
                    </button>
                </div>
            </div>
        </div>

    <?php endif; /* $esRecepcionista || $esAsistenteDental */ ?>

    <script src="<?= BASE_URL ?>/public/js/app.js?v=<?= @filemtime(ROOT_PATH . '/public/js/app.js') ?>"></script>
    <script src="<?= BASE_URL ?>/public/js/citas.js?v=<?= @filemtime(ROOT_PATH . '/public/js/citas.js') ?>"></script>

    <?php if ($esRecepcionista || $esAsistenteDental): ?>
        <script src="<?= BASE_URL ?>/public/js/inventario.js?v=<?= @filemtime(ROOT_PATH . '/public/js/inventario.js') ?>"></script>
    <?php endif; ?>

    <?php if ($esRecepcionista || $esAsistenteDental): ?>
    <script>
        /* ═══════════════════════════════════════════════════════════
           MÓDULO PACIENTES — edición básica (recepcionista / asistente_dental)
           ═══════════════════════════════════════════════════════════ */

        const BASE = '<?= BASE_URL ?>';

        function pacAbrirEditar(paciente) {
            document.getElementById('pac-edit-id-usuario').value = paciente.id_usuario;
            document.getElementById('pac-edit-nombre').value     = paciente.nombre   || '';
            document.getElementById('pac-edit-cedula').value     = paciente.cedula   || '';
            document.getElementById('pac-edit-telefono').value   = paciente.telefono || '';
            document.getElementById('pac-edit-correo').value     = paciente.correo   || '';
            document.getElementById('pac-edit-usuario').value    = paciente.usuario  || '';
            abrirModal('modal-pac-editar-basico');
        }

        async function pacGuardarEditar() {
            const idUsuario = document.getElementById('pac-edit-id-usuario').value;
            const nombre    = document.getElementById('pac-edit-nombre').value.trim();
            const cedula    = document.getElementById('pac-edit-cedula').value.trim();
            const telefono  = document.getElementById('pac-edit-telefono').value.trim();
            const correo    = document.getElementById('pac-edit-correo').value.trim();
            const usuario   = document.getElementById('pac-edit-usuario').value.trim();

            if (!nombre || !cedula || !correo || !usuario) {
                mostrarToast('Complete todos los campos obligatorios.', 'aviso');
                return;
            }

            const btn = document.getElementById('pac-edit-btn-guardar');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Guardando…';

            try {
                const form = new FormData();
                form.append('id_usuario', idUsuario);
                form.append('nombre',     nombre);
                form.append('cedula',     cedula);
                form.append('telefono',   telefono);
                form.append('correo',     correo);
                form.append('usuario',    usuario);

                const res  = await fetch(`${BASE}/index.php?accion=pacientes.editarDatosBasicos`, {
                    method: 'POST', body: form
                });
                const data = await res.json();

                if (!data.ok) {
                    mostrarToast(data.mensaje || 'Error al guardar.', 'error');
                } else {
                    mostrarToast('Datos del paciente actualizados.', 'exito');
                    cerrarModal('modal-pac-editar-basico');
                    // Actualizar la fila en la tabla sin recargar la página
                    const filas = document.querySelectorAll('#tabla-pacientes .fila-paciente');
                    filas.forEach(fila => {
                        const celdas = fila.querySelectorAll('td');
                        if (celdas[1]?.querySelector('strong')?.textContent === document.getElementById('pac-edit-nombre').value ||
                            celdas[0]?.textContent.trim() === idUsuario) {
                            if (celdas[1]) celdas[1].innerHTML = `<strong>${nombre}</strong>`;
                            if (celdas[2]) celdas[2].textContent = cedula;
                            if (celdas[3]) celdas[3].textContent = telefono || '—';
                            if (celdas[4]) celdas[4].textContent = correo;
                        }
                    });
                }
            } catch(e) {
                mostrarToast('Error de conexión.', 'error');
            } finally {
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Guardar cambios';
            }
        }

        /* ═══════════════════════════════════════════════════════════
           MÓDULO EXPEDIENTES CLÍNICOS (asistente_dental)
           ═══════════════════════════════════════════════════════════ */

        const escapar = t => String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        let _expPacienteActual = null;
        let _expDatos          = null;

        const _exp    = (id) => document.getElementById(id);
        const _expEsc = (t)  => String(t ?? '—').replace(/[&<>"']/g, c =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        async function expCargarLista(q = '') {
            const tbody = _exp('exp-tbody');
            if (!tbody) return;
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--gris-azulado);">Cargando…</td></tr>';
            try {
                const url  = `${BASE}/index.php?accion=expedientes.pacientes` + (q ? `&q=${encodeURIComponent(q)}` : '');
                const res  = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (!Array.isArray(data) || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--gris-azulado);">No se encontraron pacientes.</td></tr>';
                    return;
                }
                // Guardamos los objetos paciente en un mapa global para evitar
                // problemas de escaping con comillas en atributos HTML.
                window._expPacientesData = {};
                tbody.innerHTML = data.map((p, i) => {
                    const key = 'pac_' + (p.id_usuario ?? i);
                    window._expPacientesData[key] = p;
                    return `
                    <tr>
                        <td>${i + 1}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="sidebar-perfil-avatar" style="width:32px;height:32px;font-size:11px;flex-shrink:0;">
                                    ${_expIniciales(p.nombre_completo)}
                                </div>
                                <strong>${_expEsc(p.nombre_completo)}</strong>
                            </div>
                        </td>
                        <td>${_expEsc(p.identificacion)}</td>
                        <td>${p.edad != null ? p.edad + ' años' : '—'}</td>
                        <td>${_expEsc(p.grupo_sanguineo)}</td>
                        <td>
                            <span class="badge-estado badge-${p.estado === 'activo' ? 'confirmada' : 'cancelada'}">
                                ${p.estado ? (p.estado.charAt(0).toUpperCase() + p.estado.slice(1)) : '—'}
                            </span>
                        </td>
                        <td>
                            ${p.id_paciente
                                ? '<span class="badge-estado badge-completada"><i class="bi bi-check-circle"></i> Creado</span>'
                                : '<span class="badge-estado badge-pendiente"><i class="bi bi-dash-circle"></i> Sin expediente</span>'}
                        </td>
                        <td>
                            <button class="btn-outline-odent" style="font-size:12px;padding:4px 10px;"
                                data-pac-key="${key}"
                                onclick="expAbrirPaciente(window._expPacientesData[this.dataset.pacKey])">
                                <i class="bi bi-clipboard2-pulse"></i> Ver expediente
                            </button>
                        </td>
                    </tr>`;
                }).join('');
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--gris-azulado);">Error al cargar la lista de pacientes.</td></tr>';
            }
        }

        function _expIniciales(nombre) {
            if (!nombre) return '?';
            const p = nombre.trim().split(/\s+/);
            return (p[0]?.[0] ?? '').toUpperCase() + (p[1]?.[0] ?? '').toUpperCase();
        }

        let _expBuscarTimer = null;
        function expBuscar(q) {
            clearTimeout(_expBuscarTimer);
            _expBuscarTimer = setTimeout(() => expCargarLista(q.trim()), 300);
        }

        async function expAbrirPaciente(paciente) {
            _expPacienteActual = paciente;
            _exp('exp-vista-lista').style.display   = 'none';
            _exp('exp-vista-detalle').style.display = '';
            _exp('exp-sin-expediente').style.display = 'none';
            _exp('exp-con-expediente').style.display  = 'none';

            _exp('exp-detalle-nombre').textContent = paciente.nombre_completo ?? '—';
            _exp('exp-detalle-cedula').textContent = 'Cédula: ' + (paciente.identificacion ?? '—');

            if (!paciente.id_paciente) {
                _exp('exp-sin-expediente').style.display  = '';
                _exp('btn-editar-pac').style.display      = 'none';
                _exp('btn-editar-exp').style.display      = 'none';
                _exp('btn-ver-odontograma').style.display = 'none';
                _exp('btn-ver-documentos').style.display  = 'none';
                return;
            }

            _exp('btn-editar-pac').style.display = '';

            try {
                const res  = await fetch(`${BASE}/index.php?accion=expedientes.obtener&id_paciente=${paciente.id_paciente}`, {
                    headers: { Accept: 'application/json' }
                });
                const data = await res.json();

                if (!data || !data.id_expediente) {
                    _exp('exp-sin-expediente').style.display  = '';
                    _exp('btn-editar-exp').style.display      = 'none';
                    _exp('btn-ver-odontograma').style.display = 'none';
                    _exp('btn-ver-documentos').style.display  = 'none';
                } else {
                    _expDatos = data;
                    expRenderizarExpediente(data, paciente);
                    _exp('exp-con-expediente').style.display  = '';
                    _exp('btn-editar-exp').style.display      = '';
                    _exp('btn-ver-odontograma').style.display = '';
                    _exp('btn-ver-documentos').style.display  = '';
                    expCargarAtenciones(data.id_expediente);
                }
            } catch (e) {
                mostrarToast('Error al cargar el expediente.', 'error');
            }
        }

        function expRenderizarExpediente(data, paciente) {
            _exp('exp-motivo').textContent        = data.motivo_consulta           || '—';
            _exp('exp-alergias').textContent      = data.alergias                  || 'Ninguna';
            _exp('exp-enfermedades').textContent  = data.enfermedades              || 'Ninguna';
            _exp('exp-medicamentos').textContent  = data.medicamentos_actuales     || 'Ninguno';
            _exp('exp-antecedentes').textContent  = data.antecedentes              || 'Sin antecedentes';
            _exp('exp-observaciones').textContent = data.observaciones             || '—';

            _exp('exp-pac-fnac').textContent  = paciente.fecha_nacimiento ? new Date(paciente.fecha_nacimiento + 'T00:00:00').toLocaleDateString('es-CR') : '—';
            _exp('exp-pac-edad').textContent  = paciente.edad != null ? paciente.edad + ' años' : '—';
            _exp('exp-pac-sexo').textContent  = paciente.sexo ? (paciente.sexo.charAt(0).toUpperCase() + paciente.sexo.slice(1)) : '—';
            _exp('exp-pac-sangre').textContent = paciente.grupo_sanguineo || '—';
            _exp('exp-pac-tel').textContent    = paciente.telefono        || '—';
            _exp('exp-pac-correo').textContent = paciente.correo          || '—';
        }

        async function expCargarAtenciones(idExpediente) {
            const lista = _exp('exp-atenciones-lista');
            lista.innerHTML = '<div style="text-align:center;padding:20px;color:var(--gris-azulado);">Cargando atenciones…</div>';
            try {
                const res  = await fetch(`${BASE}/index.php?accion=expedientes.atenciones&id_expediente=${idExpediente}`, {
                    headers: { Accept: 'application/json' }
                });
                const data = await res.json();
                _exp('exp-atenciones-count').textContent = `${data.length} registro${data.length !== 1 ? 's' : ''}`;
                if (!data.length) {
                    lista.innerHTML = '<div class="alerta info"><i class="bi bi-info-circle"></i> No hay atenciones registradas aún.</div>';
                    return;
                }
                lista.innerHTML = data.map(a => `
                    <div style="border:1.5px solid var(--borde);border-radius:10px;padding:16px;margin-bottom:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;flex-wrap:wrap;gap:6px;">
                            <div>
                                <span style="font-weight:600;font-size:14px;">${_expEsc(a.odontologo)}</span>
                                <span style="font-size:12px;color:var(--gris-azulado);margin-left:8px;">${_expFecha(a.created_at)}</span>
                            </div>
                            <span class="badge-estado badge-confirmada"><i class="bi bi-journal-check"></i> Atención</span>
                        </div>
                        <div style="margin-bottom:8px;">
                            <p style="font-size:12px;color:var(--gris-azulado);margin:0 0 2px;font-weight:600;">DIAGNÓSTICO</p>
                            <p style="font-size:14px;margin:0;">${_expEsc(a.diagnostico)}</p>
                        </div>
                        ${a.tratamiento_realizado ? `
                        <div style="margin-bottom:8px;">
                            <p style="font-size:12px;color:var(--gris-azulado);margin:0 0 2px;font-weight:600;">TRATAMIENTO REALIZADO</p>
                            <p style="font-size:14px;margin:0;">${_expEsc(a.tratamiento_realizado)}</p>
                        </div>` : ''}
                        ${a.observaciones ? `
                        <div>
                            <p style="font-size:12px;color:var(--gris-azulado);margin:0 0 2px;font-weight:600;">OBSERVACIONES</p>
                            <p style="font-size:14px;margin:0;">${_expEsc(a.observaciones)}</p>
                        </div>` : ''}
                    </div>`).join('');
            } catch (e) {
                lista.innerHTML = '<div class="alerta info">No se pudieron cargar las atenciones.</div>';
            }
        }

        function _expFecha(str) {
            if (!str) return '—';
            const d = new Date(str.replace(' ', 'T'));
            return d.toLocaleDateString('es-CR', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
        }

        function expVolverLista() {
            _expPacienteActual = null;
            _expDatos          = null;
            const btnEditPac   = _exp('btn-editar-pac');
            const vistaDetalle = _exp('exp-vista-detalle');
            const vistaLista   = _exp('exp-vista-lista');
            if (btnEditPac)   btnEditPac.style.display   = 'none';
            if (vistaDetalle) vistaDetalle.style.display = 'none';
            if (vistaLista)   vistaLista.style.display   = '';
        }

        async function guardarCrearExpediente() {
            if (!validarCampos(['ec-motivo'], 'El motivo de consulta es obligatorio.')) return;
            if (!_expPacienteActual?.id_paciente) return mostrarToast('No se identificó al paciente.', 'error');

            await guardarJSON('expedientes.crear', {
                id_paciente:           _expPacienteActual.id_paciente,
                motivo_consulta:       _exp('ec-motivo').value.trim(),
                alergias:              _exp('ec-alergias').value.trim(),
                enfermedades:          _exp('ec-enfermedades').value.trim(),
                medicamentos_actuales: _exp('ec-medicamentos').value.trim(),
                antecedentes:          _exp('ec-antecedentes').value.trim(),
                observaciones:         _exp('ec-observaciones').value.trim()
            }, {
                boton: document.querySelector('#modal-exp-crear .modal-footer .btn-odent'),
                cerrarModal: 'modal-exp-crear',
                exito: 'Expediente creado exitosamente.',
                alExito: () => expAbrirPaciente(_expPacienteActual)
            });
        }

        function abrirEditarExpediente() {
            if (!_expDatos) return;
            _exp('ee-id-expediente').value = _expDatos.id_expediente;
            _exp('ee-motivo').value        = _expDatos.motivo_consulta       || '';
            _exp('ee-alergias').value      = _expDatos.alergias              || '';
            _exp('ee-enfermedades').value  = _expDatos.enfermedades          || '';
            _exp('ee-medicamentos').value  = _expDatos.medicamentos_actuales || '';
            _exp('ee-antecedentes').value  = _expDatos.antecedentes          || '';
            _exp('ee-observaciones').value = _expDatos.observaciones         || '';
            abrirModal('modal-exp-editar');
        }

        async function guardarEditarExpediente() {
            if (!validarCampos(['ee-motivo'], 'El motivo de consulta es obligatorio.')) return;

            await guardarJSON('expedientes.actualizar', {
                id_expediente:         _exp('ee-id-expediente').value,
                motivo_consulta:       _exp('ee-motivo').value.trim(),
                alergias:              _exp('ee-alergias').value.trim(),
                enfermedades:          _exp('ee-enfermedades').value.trim(),
                medicamentos_actuales: _exp('ee-medicamentos').value.trim(),
                antecedentes:          _exp('ee-antecedentes').value.trim(),
                observaciones:         _exp('ee-observaciones').value.trim()
            }, {
                boton: document.querySelector('#modal-exp-editar .modal-footer .btn-odent'),
                cerrarModal: 'modal-exp-editar',
                exito: 'Expediente actualizado.',
                alExito: () => expAbrirPaciente(_expPacienteActual)
            });
        }

        function abrirEditarPaciente() {
            if (!_expPacienteActual?.id_paciente) return;
            _exp('ep-id-paciente').value = _expPacienteActual.id_paciente;
            _exp('ep-fecha-nac').value   = _expPacienteActual.fecha_nacimiento || '';
            _exp('ep-sexo').value        = _expPacienteActual.sexo             || '';
            _exp('ep-sangre').value      = _expPacienteActual.grupo_sanguineo  || '';
            epActualizarEdad(_expPacienteActual.fecha_nacimiento || '');
            abrirModal('modal-editar-paciente');
        }

        function epActualizarEdad(fechaStr) {
            const preview = _exp('ep-edad-preview');
            if (!fechaStr) { preview.textContent = ''; return; }
            const hoy  = new Date();
            const nac  = new Date(fechaStr + 'T00:00:00');
            let edad   = hoy.getFullYear() - nac.getFullYear();
            const m    = hoy.getMonth() - nac.getMonth();
            if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) edad--;
            preview.textContent = edad >= 0 ? `Edad: ${edad} años` : '';
        }

        async function guardarEditarPaciente() {
            const pacienteRef = _expPacienteActual;
            const res = await guardarJSON('expedientes.paciente.actualizar', {
                id_paciente:      _exp('ep-id-paciente').value,
                fecha_nacimiento: _exp('ep-fecha-nac').value,
                sexo:             _exp('ep-sexo').value,
                grupo_sanguineo:  _exp('ep-sangre').value
            }, {
                cerrarModal: 'modal-editar-paciente',
                exito: 'Datos del paciente actualizados.'
            });
            if (res?.ok && pacienteRef) {
                pacienteRef.fecha_nacimiento = _exp('ep-fecha-nac').value || null;
                pacienteRef.sexo             = _exp('ep-sexo').value      || null;
                pacienteRef.grupo_sanguineo  = _exp('ep-sangre').value    || null;
                if (pacienteRef.fecha_nacimiento) {
                    const hoy = new Date(), nac = new Date(pacienteRef.fecha_nacimiento + 'T00:00:00');
                    let edad = hoy.getFullYear() - nac.getFullYear();
                    if (hoy.getMonth() - nac.getMonth() < 0 || (hoy.getMonth() - nac.getMonth() === 0 && hoy.getDate() < nac.getDate())) edad--;
                    pacienteRef.edad = edad;
                } else {
                    pacienteRef.edad = null;
                }
                expRenderizarExpediente(_expDatos, pacienteRef);
            }
        }

        function abrirRegistrarAtencion() {
            if (!_expDatos) {
                return mostrarToast('Primero cree el expediente clínico del paciente.', 'aviso');
            }
            _exp('ea-id-expediente').value     = _expDatos.id_expediente;
            _exp('ea-paciente-nombre').textContent = _expPacienteActual?.nombre_completo ?? '';
            _exp('ea-diagnostico').value        = '';
            _exp('ea-tratamiento').value        = '';
            _exp('ea-observaciones').value      = '';
            abrirModal('modal-exp-atencion');
        }

        async function guardarAtencion() {
            if (!validarCampos(['ea-diagnostico'], 'El diagnóstico es obligatorio.')) return;

            await guardarJSON('expedientes.atencion.registrar', {
                id_expediente:         _exp('ea-id-expediente').value,
                diagnostico:           _exp('ea-diagnostico').value.trim(),
                tratamiento_realizado: _exp('ea-tratamiento').value.trim(),
                observaciones:         _exp('ea-observaciones').value.trim()
            }, {
                boton: document.querySelector('#modal-exp-atencion .modal-footer .btn-odent'),
                cerrarModal: 'modal-exp-atencion',
                exito: 'Atención registrada exitosamente.',
                alExito: () => expCargarAtenciones(_expDatos.id_expediente)
            });
        }

        let _navegandoDesdeExpediente = false;

        /* ── Override window.mostrarPagina + arranque al cargar ──────────────
           El script inline parsea ANTES de que DOMContentLoaded dispare,
           y app.js ya está cargado porque su <script src> precede a este bloque.
           Overridear window.mostrarPagina aquí captura los clics del sidebar.
           Para la restauración inicial (sessionStorage/hash), app.js llama
           mostrarPagina() por su nombre de scope local — no pasa por window —
           así que usamos el MutationObserver para ese caso, conectándolo con
           setTimeout(0) dentro de DOMContentLoaded para que corra DESPUÉS del
           listener de app.js que ya cambió la clase. ── */

        /* 1) Override para clics del sidebar */
        const _mostrarPaginaOriginal = window.mostrarPagina;
        window.mostrarPagina = function (slug) {
            if (typeof _mostrarPaginaOriginal === 'function') _mostrarPaginaOriginal(slug);
            _despacharSlug(slug);
        };

        /* 2) Arranque inmediato — este script está al final del body, el DOM ya existe
           y DOMContentLoaded ya disparó (app.js corrió su listener antes que nosotros).
           Usamos setTimeout(0) para ceder el turno y dejar que app.js termine de
           restaurar la página activa antes de leerla. */
        setTimeout(function () {
            const activa = document.querySelector('.pagina.activa');
            if (activa) _despacharSlug(activa.id.replace('pagina-', ''));

            /* Observar futuros cambios de página */
            const obs = new MutationObserver(function (muts) {
                muts.forEach(function (m) {
                    if (m.attributeName === 'class') {
                        const el = m.target;
                        if (el.classList.contains('activa')) {
                            _despacharSlug(el.id.replace('pagina-', ''));
                        }
                    }
                });
            });
            document.querySelectorAll('.pagina').forEach(function (p) {
                obs.observe(p, { attributes: true, attributeFilter: ['class'] });
            });
        }, 0);

        function _despacharSlug(slug) {
            if (slug === 'expedientes') {
                expVolverLista();
                expCargarLista();
            } else if (slug === 'odontograma') {
                if (_navegandoDesdeExpediente) {
                    _navegandoDesdeExpediente = false;
                } else {
                    _odoExpediente     = null;
                    _odoPacientesCache = null;
                }
                odoIniciar();
            } else if (slug === 'documentos') {
                if (_navegandoDesdeExpediente) {
                    _navegandoDesdeExpediente = false;
                } else {
                    _docExpediente     = null;
                    _docPacientesCache = null;
                }
                docIniciar();
            }
        }

        /* ═══════════════════════════════════════════════════════════
           MÓDULO ODONTOGRAMA (asistente_dental)
           ═══════════════════════════════════════════════════════════ */

        let _odoExpediente  = null;
        let _odoPiezas      = {};
        let _odoPiezaActual = null;

        const ODO_SUPERIOR = [18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28];
        const ODO_INFERIOR = [48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38];

        const ODO_CLASES = {
            sano:'sano', caries:'caries', corona:'corona', ausente:'ausente',
            endodoncia:'endodoncia', extraccion:'extraccion', implante:'implante',
        };

        function _odo(id) { return document.getElementById(id); }
        function _odoEstado(num) { return (_odoPiezas[num]?.estado) ?? 'sano'; }

        function _odoRenderizarDiente(num) {
            const estado = _odoEstado(num);
            return `<div class="diente ${estado}" id="diente-${num}" onclick="odoSeleccionarDiente(${num})">
                        <i class="bi bi-circle-fill" style="font-size:10px;"></i>
                        <span>${num}</span>
                    </div>`;
        }

        function _odoRenderizarFila(nums) { return nums.map(_odoRenderizarDiente).join(''); }

        function _odoActualizarDomPieza(num) {
            const el = document.getElementById('diente-' + num);
            if (!el) return;
            Object.keys(ODO_CLASES).forEach(c => el.classList.remove(c));
            el.classList.add(_odoEstado(num));
        }

        function odoIniciar() {
            if (!_odoExpediente) {
                _odo('odo-sin-expediente').style.display = '';
                _odo('odo-con-expediente').style.display = 'none';
                _odoCargarListaPacientes();
                return;
            }
            _odo('odo-sin-expediente').style.display = 'none';
            _odo('odo-con-expediente').style.display = '';
            _odo('odo-cabecera-paciente').innerHTML =
                `Paciente: <strong>${escapar(_odoExpediente.nombre_completo)}</strong> — Haga clic en un diente para editar`;
            const textoVolver = document.getElementById('odo-btn-volver-texto');
            if (textoVolver) textoVolver.textContent = _expPacienteActual ? 'Volver al expediente' : 'Volver a la lista';
            _odoRenderizarArcadas();
            _odoCancelarSeleccion();
            _odoCargarPiezas();
        }

        function _odoRenderizarArcadas() {
            _odo('odo-fila-superior').innerHTML = _odoRenderizarFila(ODO_SUPERIOR);
            _odo('odo-fila-inferior').innerHTML = _odoRenderizarFila(ODO_INFERIOR);
        }

        async function _odoCargarPiezas() {
            if (!_odoExpediente) return;
            try {
                const r    = await fetch(`${BASE}/index.php?accion=odontograma.obtener&id_expediente=${_odoExpediente.id_expediente}`);
                const data = await r.json();
                if (!data.ok) return;
                _odoPiezas = data.piezas ?? {};
                [...ODO_SUPERIOR, ...ODO_INFERIOR].forEach(_odoActualizarDomPieza);
            } catch(e) {
                mostrarToast('Error al cargar el odontograma.', 'error');
            }
        }

        function odoSeleccionarDiente(num) {
            _odoPiezaActual = num;
            const datos = _odoPiezas[num] ?? { estado: 'sano', observaciones: '', updated_at: null };

            _odo('odo-hint').style.display         = 'none';
            _odo('odo-loading').style.display       = 'none';
            _odo('odo-panel-edicion').style.display = '';

            _odo('odo-numero').value        = num;
            _odo('odo-estado').value        = datos.estado        ?? 'sano';
            _odo('odo-observaciones').value = datos.observaciones ?? '';

            if (datos.updated_at) {
                _odo('odo-fecha-mod').textContent    = datos.updated_at;
                _odo('odo-ultima-mod').style.display = '';
            } else {
                _odo('odo-ultima-mod').style.display = 'none';
            }

            document.querySelectorAll('#pagina-odontograma .diente').forEach(el => el.classList.remove('seleccionado'));
            const elDiente = document.getElementById('diente-' + num);
            if (elDiente) elDiente.classList.add('seleccionado');
        }

        function _odoCancelarSeleccion() {
            _odoPiezaActual = null;
            _odo('odo-hint').style.display         = '';
            _odo('odo-loading').style.display       = 'none';
            _odo('odo-panel-edicion').style.display = 'none';
            document.querySelectorAll('#pagina-odontograma .diente').forEach(el => el.classList.remove('seleccionado'));
        }

        function odoCancelarEdicion() { _odoCancelarSeleccion(); }

        async function odoGuardarDiente() {
            if (!_odoPiezaActual || !_odoExpediente) return;
            const estado = _odo('odo-estado').value;
            const obs    = _odo('odo-observaciones').value.trim();

            await guardarJSON('odontograma.guardar', {
                id_expediente: _odoExpediente.id_expediente,
                numero_pieza:  _odoPiezaActual,
                estado,
                observaciones: obs || null,
            }, {
                boton:  _odo('odo-btn-guardar'),
                exito:  `Pieza ${_odoPiezaActual} guardada como "${estado}".`,
                alExito: () => {
                    if (!_odoPiezas[_odoPiezaActual]) _odoPiezas[_odoPiezaActual] = {};
                    _odoPiezas[_odoPiezaActual].estado        = estado;
                    _odoPiezas[_odoPiezaActual].observaciones = obs || null;
                    _odoPiezas[_odoPiezaActual].updated_at    = new Date().toLocaleString('es-CR');
                    _odoActualizarDomPieza(_odoPiezaActual);
                    _odoCancelarSeleccion();
                }
            });
        }

        function abrirOdontograma() {
            if (!_expDatos || !_expPacienteActual) {
                return mostrarToast('Primero abra un expediente clínico.', 'aviso');
            }
            _odoExpediente = {
                id_expediente:   _expDatos.id_expediente,
                nombre_completo: _expPacienteActual.nombre_completo,
            };
            _navegandoDesdeExpediente = true;
            mostrarPagina('odontograma');
        }

        function odoVolverExpediente() {
            if (_expPacienteActual) {
                const pacienteGuardado = _expPacienteActual;
                _mostrarPaginaBase('expedientes');
                expAbrirPaciente(pacienteGuardado);
            } else {
                _odoExpediente = null;
                odoIniciar();
            }
        }

        let _odoPacientesCache = null;

        async function _odoCargarListaPacientes() {
            if (_odoPacientesCache !== null) {
                _odoRenderizarListaPacientes(_odoPacientesCache, '');
                return;
            }
            try {
                const r    = await fetch(`${BASE}/index.php?accion=expedientes.pacientes`);
                const data = await r.json();
                _odoPacientesCache = Array.isArray(data) ? data : (data.pacientes ?? []);
                _odoRenderizarListaPacientes(_odoPacientesCache, '');
            } catch(e) {
                document.getElementById('odo-lista-pacientes').innerHTML =
                    '<p style="color:var(--gris-azulado);text-align:center;padding:20px;">No se pudo cargar la lista de pacientes.</p>';
            }
        }

        function _odoRenderizarListaPacientes(lista, filtro) {
            const contenedor = document.getElementById('odo-lista-pacientes');
            const termino    = filtro.trim().toLowerCase();
            const filtrados  = termino ? lista.filter(p => (p.nombre_completo || '').toLowerCase().includes(termino)) : lista;

            if (!filtrados.length) {
                contenedor.innerHTML = '<p style="color:var(--gris-azulado);text-align:center;padding:20px;">No se encontraron pacientes.</p>';
                return;
            }

            contenedor.innerHTML = filtrados.map(p => {
                const nombre   = escapar(p.nombre_completo || 'Paciente');
                const idPac    = p.id_paciente;
                const nombreJs = (p.nombre_completo || 'Paciente').replace(/'/g, "\\'");
                return `
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--borde);">
                    <div>
                        <div style="font-weight:600;font-size:14px;">${nombre}</div>
                        <div style="font-size:12px;color:var(--gris-azulado);">${escapar(p.identificacion || '')}</div>
                    </div>
                    <button class="btn-outline-odent" style="font-size:12px;padding:5px 14px;"
                        onclick="odoAbrirDesdeListado(${idPac}, '${nombreJs}', this)">
                        <i class="bi bi-clipboard2-pulse"></i> Ver odontograma
                    </button>
                </div>`;
            }).join('');

            const items = contenedor.querySelectorAll('[style*="border-bottom"]');
            if (items.length) items[items.length - 1].style.borderBottom = 'none';
        }

        function odoBuscarPaciente(valor) {
            if (_odoPacientesCache) _odoRenderizarListaPacientes(_odoPacientesCache, valor);
        }

        async function odoAbrirDesdeListado(idPaciente, nombreCompleto, btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            try {
                const r    = await fetch(`${BASE}/index.php?accion=expedientes.obtener&id_paciente=${idPaciente}`);
                const data = await r.json();
                if (!data || !data.id_expediente) {
                    mostrarToast('Este paciente no tiene expediente clínico aún.', 'aviso');
                    btn.disabled  = false;
                    btn.innerHTML = '<i class="bi bi-clipboard2-pulse"></i> Ver odontograma';
                    return;
                }
                _odoExpediente = { id_expediente: data.id_expediente, nombre_completo: nombreCompleto };
                _navegandoDesdeExpediente = true;
                odoIniciar();
            } catch(e) {
                mostrarToast('Error al cargar el expediente.', 'error');
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-clipboard2-pulse"></i> Ver odontograma';
            }
        }

        /* ═══════════════════════════════════════════════════════════
           MÓDULO DOCUMENTOS CLÍNICOS (asistente_dental)
           ═══════════════════════════════════════════════════════════ */

        let _docExpediente      = null;
        let _docIdAEliminar     = null;
        let _docNombreAEliminar = '';

        function _doc(id) { return document.getElementById(id); }

        function _docIcono(mime) {
            if (mime === 'application/pdf')                     return 'bi-file-earmark-pdf text-danger';
            if (mime.startsWith('image/'))                      return 'bi-file-earmark-image text-success';
            if (mime.includes('word') || mime.includes('docx')) return 'bi-file-earmark-word text-primary';
            return 'bi-file-earmark';
        }

        function docIniciar() {
            if (!_docExpediente) {
                _doc('doc-sin-expediente').style.display     = '';
                _doc('doc-con-expediente').style.display     = 'none';
                _doc('doc-btn-subir-cabecera').style.display = 'none';
                _docCargarListaPacientes();
                return;
            }
            _doc('doc-sin-expediente').style.display     = 'none';
            _doc('doc-con-expediente').style.display     = '';
            _doc('doc-btn-subir-cabecera').style.display = '';
            _doc('doc-cabecera-paciente').innerHTML =
                `Paciente: <strong>${escapar(_docExpediente.nombre_completo)}</strong> — Archivos adjuntos del expediente`;
            const textoVolver = document.getElementById('doc-btn-volver-texto');
            if (textoVolver) textoVolver.textContent = _expPacienteActual ? 'Volver al expediente' : 'Volver a la lista';
            docCargarLista();
        }

        async function docCargarLista() {
            _doc('doc-loading').style.display = '';
            _doc('doc-lista').style.display   = 'none';
            _doc('doc-vacio').style.display   = 'none';

            try {
                const r    = await fetch(`${BASE}/index.php?accion=documentos.listar&id_expediente=${_docExpediente.id_expediente}`);
                const data = await r.json();

                _doc('doc-loading').style.display = 'none';

                if (!data.ok || !data.documentos.length) {
                    _doc('doc-vacio').style.display = '';
                    _doc('doc-count').textContent   = '0 documentos';
                    return;
                }

                _doc('doc-count').textContent = data.documentos.length + ' documento' + (data.documentos.length !== 1 ? 's' : '');
                _doc('doc-lista').innerHTML = data.documentos.map(d => `
                    <div style="display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid var(--borde);">
                        <i class="bi ${_docIcono(d.tipo_mime)}" style="font-size:28px;flex-shrink:0;"></i>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:600;font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${escapar(d.nombre_original)}">
                                ${escapar(d.nombre_original)}
                            </div>
                            ${d.descripcion ? `<div style="font-size:13px;color:var(--gris-azulado);">${escapar(d.descripcion)}</div>` : ''}
                            <div style="font-size:12px;color:var(--gris-azulado);margin-top:2px;">
                                ${escapar(d.tamano_fmt)} · ${escapar(d.created_at.substring(0,10))} · ${escapar(d.subido_por_nombre)}
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;flex-shrink:0;">
                            <a href="${escapar(d.url_descarga)}" class="btn-outline-odent" style="font-size:12px;padding:5px 12px;text-decoration:none;" download>
                                <i class="bi bi-download"></i> Descargar
                            </a>
                            <button class="btn-outline-odent" style="font-size:12px;padding:5px 10px;color:var(--rojo-odent,#C0392B);border-color:var(--rojo-odent,#C0392B);"
                                onclick="docAbrirEliminar(${d.id_documento}, '${escapar(d.nombre_original).replace(/'/g,"\\'")}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>`).join('');

                const items = _doc('doc-lista').querySelectorAll('[style*="border-bottom"]');
                if (items.length) items[items.length - 1].style.borderBottom = 'none';

                _doc('doc-lista').style.display = '';
            } catch(e) {
                _doc('doc-loading').style.display = 'none';
                mostrarToast('Error al cargar los documentos.', 'error');
            }
        }

        async function docSubir() {
            const archivoInput = _doc('doc-archivo');
            if (!archivoInput.files.length) {
                archivoInput.classList.add('campo-invalido');
                return mostrarToast('Seleccione un archivo para subir.', 'aviso');
            }
            archivoInput.classList.remove('campo-invalido');

            const formData = new FormData();
            formData.append('archivo',       archivoInput.files[0]);
            formData.append('id_expediente', _docExpediente.id_expediente);
            formData.append('descripcion',   _doc('doc-descripcion').value.trim());

            const btn = _doc('doc-btn-subir');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Subiendo…';

            try {
                const res  = await fetch(`${BASE}/index.php?accion=documentos.subir`, { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.ok) {
                    mostrarToast(data.mensaje || 'Error al subir el archivo.', 'error');
                } else {
                    mostrarToast('Documento subido correctamente.', 'exito');
                    cerrarModal('modal-doc-subir');
                    archivoInput.value           = '';
                    _doc('doc-descripcion').value = '';
                    docCargarLista();
                }
            } catch(e) {
                mostrarToast('Error de conexión al subir el archivo.', 'error');
            } finally {
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-upload"></i> Subir';
            }
        }

        function docAbrirEliminar(idDocumento, nombre) {
            _docIdAEliminar     = idDocumento;
            _docNombreAEliminar = nombre;
            _doc('doc-nombre-a-eliminar').textContent = nombre;
            abrirModal('modal-doc-confirmar-eliminar');
        }

        async function docConfirmarEliminar() {
            if (!_docIdAEliminar || !_docExpediente) return;

            await guardarJSON('documentos.eliminar', {
                id_documento:  _docIdAEliminar,
                id_expediente: _docExpediente.id_expediente,
            }, {
                boton:       _doc('doc-btn-confirmar-eliminar'),
                cerrarModal: 'modal-doc-confirmar-eliminar',
                exito:       'Documento eliminado.',
                alExito:     () => {
                    _docIdAEliminar     = null;
                    _docNombreAEliminar = '';
                    docCargarLista();
                }
            });
        }

        function abrirDocumentos() {
            if (!_expDatos || !_expPacienteActual) {
                return mostrarToast('Primero abra un expediente clínico.', 'aviso');
            }
            _docExpediente = {
                id_expediente:   _expDatos.id_expediente,
                nombre_completo: _expPacienteActual.nombre_completo,
            };
            _navegandoDesdeExpediente = true;
            mostrarPagina('documentos');
        }

        function docVolverExpediente() {
            if (_expPacienteActual) {
                const pacienteGuardado = _expPacienteActual;
                _mostrarPaginaBase('expedientes');
                expAbrirPaciente(pacienteGuardado);
            } else {
                _docExpediente = null;
                docIniciar();
            }
        }

        let _docPacientesCache = null;

        async function _docCargarListaPacientes() {
            if (_docPacientesCache !== null) {
                _docRenderizarListaPacientes(_docPacientesCache, '');
                return;
            }
            try {
                const r    = await fetch(`${BASE}/index.php?accion=expedientes.pacientes`);
                const data = await r.json();
                _docPacientesCache = Array.isArray(data) ? data : (data.pacientes ?? []);
                _docRenderizarListaPacientes(_docPacientesCache, '');
            } catch(e) {
                document.getElementById('doc-lista-pacientes').innerHTML =
                    '<p style="color:var(--gris-azulado);text-align:center;padding:20px;">No se pudo cargar la lista de pacientes.</p>';
            }
        }

        function _docRenderizarListaPacientes(lista, filtro) {
            const contenedor = document.getElementById('doc-lista-pacientes');
            const termino    = filtro.trim().toLowerCase();
            const filtrados  = termino ? lista.filter(p => (p.nombre_completo || '').toLowerCase().includes(termino)) : lista;

            if (!filtrados.length) {
                contenedor.innerHTML = '<p style="color:var(--gris-azulado);text-align:center;padding:20px;">No se encontraron pacientes.</p>';
                return;
            }

            contenedor.innerHTML = filtrados.map(p => {
                const nombre   = escapar(p.nombre_completo || 'Paciente');
                const idPac    = p.id_paciente;
                const nombreJs = (p.nombre_completo || 'Paciente').replace(/'/g, "\\'");
                return `
                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--borde);">
                    <div>
                        <div style="font-weight:600;font-size:14px;">${nombre}</div>
                        <div style="font-size:12px;color:var(--gris-azulado);">${escapar(p.identificacion || '')}</div>
                    </div>
                    <button class="btn-outline-odent" style="font-size:12px;padding:5px 14px;"
                        onclick="docAbrirDesdeListado(${idPac}, '${nombreJs}', this)">
                        <i class="bi bi-folder2-open"></i> Ver documentos
                    </button>
                </div>`;
            }).join('');

            const items = contenedor.querySelectorAll('[style*="border-bottom"]');
            if (items.length) items[items.length - 1].style.borderBottom = 'none';
        }

        function docBuscarPaciente(valor) {
            if (_docPacientesCache) _docRenderizarListaPacientes(_docPacientesCache, valor);
        }

        async function docAbrirDesdeListado(idPaciente, nombreCompleto, btn) {
            btn.disabled  = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            try {
                const r    = await fetch(`${BASE}/index.php?accion=expedientes.obtener&id_paciente=${idPaciente}`);
                const data = await r.json();
                if (!data || !data.id_expediente) {
                    mostrarToast('Este paciente no tiene expediente clínico aún.', 'aviso');
                    btn.disabled  = false;
                    btn.innerHTML = '<i class="bi bi-folder2-open"></i> Ver documentos';
                    return;
                }
                _docExpediente = { id_expediente: data.id_expediente, nombre_completo: nombreCompleto };
                _navegandoDesdeExpediente = true;
                docIniciar();
            } catch(e) {
                mostrarToast('Error al cargar el expediente.', 'error');
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-folder2-open"></i> Ver documentos';
            }
        }
    </script>
    <?php endif; /* $esRecepcionista || $esAsistenteDental JS */ ?>

</body>

</html>