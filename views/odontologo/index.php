<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odent | Odontólogo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/estilos.css">
</head>
<body>

<div class="layout-app">
    <?php include ROOT_PATH . '/views/shared/sidebar.php'; ?>

    <div class="contenido-principal">
        <header class="topbar">
            <span class="topbar-titulo" id="topbar-titulo">Panel del odontólogo</span>

            <div class="topbar-acciones">
                <span class="topbar-fecha"><?= date('d/m/Y') ?></span>

                <div class="topbar-notificacion">
                    <i class="bi bi-bell"></i>
                    <span class="notif-badge">3</span>
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
                        <div class="kpi-numero"><?= count($citas) ?></div>
                        <div class="kpi-etiqueta">Citas hoy</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icono verde">
                        <i class="bi bi-people"></i>
                    </div>

                    <div>
                        <div class="kpi-numero"><?= count($pacientes) ?></div>
                        <div class="kpi-etiqueta">Pacientes activos</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icono amarillo">
                        <i class="bi bi-clipboard2-pulse"></i>
                    </div>

                    <div>
                        <div class="kpi-numero"><?= count($tratamientos) ?></div>
                        <div class="kpi-etiqueta">Tratamientos en curso</div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icono rojo">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>

                    <div>
                        <div class="kpi-numero">2</div>
                        <div class="kpi-etiqueta">Pendientes de revisión</div>
                    </div>
                </div>

            </div>

            <div class="panel-grid col-3">

                <div class="tarjeta">
                    <div class="panel-titulo">
                        <h3>Citas de hoy</h3>

                        <button
                            class="btn-outline-odent"
                            onclick="mostrarPagina('agenda')"
                            style="font-size:13px;padding:6px 14px;"
                        >
                            Ver agenda →
                        </button>
                    </div>

                    <?php foreach ($citas as $cita): ?>
                    <div class="cita-item">
                        <div class="cita-hora">
                            <?= $cita['hora'] ?>
                        </div>

                        <div class="cita-info">
                            <div class="cita-paciente">
                                <?= htmlspecialchars($cita['paciente']) ?>
                            </div>

                            <div class="cita-tratamiento">
                                <?= htmlspecialchars($cita['tratamiento']) ?>
                            </div>
                        </div>

                        <span class="badge-estado badge-<?= $cita['estado'] ?>">
                            <?= ucfirst($cita['estado']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="tarjeta">
                    <div class="panel-titulo">
                        <h3>Tratamientos activos</h3>
                    </div>

                    <?php foreach ($tratamientos as $t): ?>
                    <div class="tratamiento-item">

                        <div class="tratamiento-header">
                            <span><?= htmlspecialchars($t['paciente']) ?></span>
                            <span><?= $t['progreso'] ?>%</span>
                        </div>

                        <div style="font-size:12px;color:var(--gris-azulado);margin-bottom:5px;">
                            <?= htmlspecialchars($t['tratamiento']) ?>
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
        </div>

        <div class="pagina" id="pagina-agenda">

            <div class="panel-titulo" style="margin-bottom:20px;">
                <h2>Mi agenda</h2>

                <button
                    class="btn-odent"
                    onclick="abrirModal('modal-nueva-cita')"
                >
                    <i class="bi bi-plus-lg"></i>
                    Nueva cita
                </button>
            </div>

            <div class="alerta info">
                <i class="bi bi-info-circle"></i>
                El calendario interactivo se integrará con la BD en el backend.
            </div>

            <div class="tarjeta">
                <table class="tabla-odent">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Paciente</th>
                            <th>Tratamiento</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($citas as $cita): ?>
                        <tr>
                            <td>
                                <strong><?= $cita['hora'] ?></strong>
                            </td>

                            <td>
                                <?= htmlspecialchars($cita['paciente']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($cita['tratamiento']) ?>
                            </td>

                            <td>
                                <span class="badge-estado badge-<?= $cita['estado'] ?>">
                                    <?= ucfirst($cita['estado']) ?>
                                </span>
                            </td>

                            <td>
                                <button
                                    class="btn-outline-odent"
                                    style="font-size:12px;padding:5px 12px;"
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

        <div class="pagina" id="pagina-expedientes">

            <div class="panel-titulo" style="margin-bottom:20px;">
                <h2>Expedientes clínicos</h2>

                <div style="display:flex;gap:10px;">
                    <input
                        type="text"
                        placeholder="Buscar paciente..."
                        style="border:1.5px solid var(--borde);border-radius:8px;padding:8px 14px;font-size:14px;min-width:220px;"
                    >

                    <button class="btn-odent">
                        <i class="bi bi-search"></i>
                        Buscar
                    </button>
                </div>
            </div>

            <div class="tarjeta">
                <table class="tabla-odent">
                    <thead>
                        <tr>
                            <th>N.°</th>
                            <th>Paciente</th>
                            <th>Cédula</th>
                            <th>Edad</th>
                            <th>Última visita</th>
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
                            <td><?= $p['edad'] ?> años</td>
                            <td><?= $p['ultima_visita'] ?></td>

                            <td>
                                <span class="badge-estado badge-<?= $p['estado'] ?>">
                                    <?= ucfirst($p['estado']) ?>
                                </span>
                            </td>

                            <td>
                                <button
                                    class="btn-outline-odent"
                                    onclick="mostrarPagina('odontograma')"
                                    style="font-size:12px;padding:5px 12px;"
                                >
                                    <i class="bi bi-clipboard2-pulse"></i>
                                    Ver expediente
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <div class="pagina" id="pagina-odontograma">

            <h2 style="margin-bottom:6px;">Odontograma</h2>

            <p style="font-size:14px;color:var(--gris-azulado);margin-bottom:20px;">
                Paciente: <strong>Ana Rojas</strong> — Haga clic en un diente para editar
            </p>

            <div class="panel-grid col-2">

                <div class="odontograma-container">

                    <p style="font-size:12px;color:var(--gris-azulado);margin-bottom:12px;text-align:center;font-weight:600;">
                        MAXILAR SUPERIOR
                    </p>

                    <div class="odontograma-fila">
                        <?php foreach ([18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28] as $num): ?>
                        <div
                            class="diente <?= $estado_dientes[$num] ?? 'sano' ?>"
                            onclick="seleccionarDiente(<?= $num ?>)"
                            id="diente-<?= $num ?>"
                        >
                            <i class="bi bi-circle-fill" style="font-size:10px;"></i>
                            <span><?= $num ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="border-top:2px dashed var(--borde);margin:10px 0;"></div>

                    <p style="font-size:12px;color:var(--gris-azulado);margin-bottom:12px;text-align:center;font-weight:600;">
                        MAXILAR INFERIOR
                    </p>

                    <div class="odontograma-fila">
                        <?php foreach ([48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38] as $num): ?>
                        <div
                            class="diente <?= $estado_dientes[$num] ?? 'sano' ?>"
                            onclick="seleccionarDiente(<?= $num ?>)"
                            id="diente-<?= $num ?>"
                        >
                            <i class="bi bi-circle-fill" style="font-size:10px;"></i>
                            <span><?= $num ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="leyenda-odontograma">

                        <div class="leyenda-item">
                            <div
                                class="leyenda-color"
                                style="background:#fff;border-color:var(--borde);"
                            ></div>
                            Sano
                        </div>

                        <div class="leyenda-item">
                            <div
                                class="leyenda-color"
                                style="background:rgba(192,57,43,.2);border-color:#C0392B;"
                            ></div>
                            Caries
                        </div>

                        <div class="leyenda-item">
                            <div
                                class="leyenda-color"
                                style="background:rgba(245,158,11,.2);border-color:#f59e0b;"
                            ></div>
                            Corona
                        </div>

                        <div class="leyenda-item">
                            <div
                                class="leyenda-color"
                                style="background:var(--fondo-odent);border-color:var(--gris-azulado);"
                            ></div>
                            Ausente
                        </div>

                    </div>
                </div>

                <div class="tarjeta">

                    <h3 style="margin-bottom:14px;">
                        Detalle de pieza
                    </h3>

                    <div id="detalle-diente">
                        <div class="alerta info">
                            <i class="bi bi-hand-index"></i>
                            Seleccione un diente para editar.
                        </div>
                    </div>

                    <div id="panel-edicion-diente" style="display:none;">

                        <div class="campo-grupo">
                            <label>Pieza dental (FDI)</label>
                            <input type="text" id="diente-numero" readonly>
                        </div>

                        <div class="campo-grupo">
                            <label>Estado</label>

                            <select id="diente-estado">
                                <option value="sano">Sano</option>
                                <option value="caries">Caries</option>
                                <option value="corona">Corona</option>
                                <option value="ausente">Ausente</option>
                            </select>
                        </div>

                        <div class="campo-grupo">
                            <label>Observaciones</label>
                            <textarea rows="3" placeholder="Notas clínicas..."></textarea>
                        </div>

                        <div style="display:flex;gap:10px;">
                            <button class="btn-odent" onclick="guardarDiente()">
                                <i class="bi bi-check-lg"></i>
                                Guardar
                            </button>

                            <button class="btn-outline-odent" onclick="cancelarDiente()">
                                Cancelar
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <div class="pagina" id="pagina-tratamientos">

            <div class="panel-titulo" style="margin-bottom:20px;">
                <h2>Planes de tratamiento</h2>

                <button
                    class="btn-odent"
                    onclick="abrirModal('modal-nuevo-tratamiento')"
                >
                    <i class="bi bi-plus-lg"></i>
                    Nuevo plan
                </button>
            </div>

            <div class="panel-grid col-2">

                <?php foreach ($pacientes as $p):
                    $idx = $p['id'] - 1;
                    $t = $tratamientos[$idx] ?? [
                        'tratamiento' => 'Sin plan activo',
                        'progreso' => 0
                    ];
                ?>

                <div class="tarjeta">

                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">

                        <div>
                            <h4><?= htmlspecialchars($p['nombre']) ?></h4>

                            <p style="font-size:13px;color:var(--gris-azulado);">
                                Cédula: <?= $p['cedula'] ?>
                            </p>
                        </div>

                        <span class="badge-estado badge-<?= $p['estado'] ?>">
                            <?= ucfirst($p['estado']) ?>
                        </span>

                    </div>

                    <div class="tratamiento-item">

                        <div class="tratamiento-header">
                            <span><?= htmlspecialchars($t['tratamiento']) ?></span>
                            <span><?= $t['progreso'] ?>%</span>
                        </div>

                        <div
                            class="barra-progreso"
                            style="margin-bottom:12px;"
                        >
                            <div
                                class="barra-relleno"
                                style="width:<?= $t['progreso'] ?>%;"
                            ></div>
                        </div>

                    </div>

                    <button
                        class="btn-outline-odent"
                        style="font-size:13px;padding:7px 14px;width:100%;"
                    >
                        <i class="bi bi-eye"></i>
                        Ver detalle
                    </button>

                </div>

                <?php endforeach; ?>

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
                <option value="">Seleccione un paciente</option>

                <?php foreach ($pacientes as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo-grupo">
            <label>Fecha</label>
            <input type="date" value="<?= date('Y-m-d') ?>">
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

        <div class="campo-grupo">
            <label>Notas</label>
            <textarea rows="3" placeholder="Observaciones opcionales..."></textarea>
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

<div class="modal-overlay" id="modal-nuevo-tratamiento">
    <div class="modal-caja">

        <div class="modal-header">
            <h3>Nuevo plan de tratamiento</h3>

            <button
                class="modal-cerrar"
                onclick="cerrarModal('modal-nuevo-tratamiento')"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="campo-grupo">
            <label>Paciente</label>

            <select>
                <option value="">Seleccione un paciente</option>

                <?php foreach ($pacientes as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo-grupo">
            <label>Nombre del plan</label>
            <input type="text" placeholder="Ej: Ortodoncia fase 1">
        </div>

        <div class="campo-grupo">
            <label>Diagnóstico</label>
            <textarea rows="3" placeholder="Descripción del diagnóstico..."></textarea>
        </div>

        <div class="campo-grupo">
            <label>Fecha de inicio</label>
            <input type="date" value="<?= date('Y-m-d') ?>">
        </div>

        <div class="modal-footer">
            <button
                class="btn-outline-odent"
                onclick="cerrarModal('modal-nuevo-tratamiento')"
            >
                Cancelar
            </button>

            <button class="btn-odent">
                <i class="bi bi-check-lg"></i>
                Crear plan
            </button>
        </div>

    </div>
</div>

<script src="<?= BASE_URL ?>/public/js/app.js"></script>
</body>
</html>