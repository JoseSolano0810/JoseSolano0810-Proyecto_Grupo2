<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odent | Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/estilos.css?v=<?= @filemtime(ROOT_PATH . '/public/css/estilos.css') ?>">
</head>

<body>

    <?php
    $usuarios_sistema = $usuarios_sistema ?? [];
    $roles            = $roles            ?? [];
    $cotizaciones     = $cotizaciones     ?? [];
    $pagos            = $pagos            ?? [];
    ?>

    <div class="layout-app">

        <?php include ROOT_PATH . '/views/shared/sidebar.php'; ?>

        <div class="contenido-principal">

            <header class="topbar">
                <span class="topbar-titulo" id="topbar-titulo">Panel principal</span>
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
                        <div class="kpi-icono azul"><i class="bi bi-calendar3"></i></div>
                        <div>
                            <div class="kpi-numero"><?= count($citas) ?></div>
                            <div class="kpi-etiqueta">Citas hoy</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icono verde"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="kpi-numero"><?= count($pacientes) ?></div>
                            <div class="kpi-etiqueta">Pacientes activos</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icono amarillo"><i class="bi bi-clipboard2-pulse"></i></div>
                        <div>
                            <?php $trat_activos = array_values(array_filter($tratamientos, fn($t) => in_array($t['estado'], ['pendiente', 'en_proceso'], true))); ?>
<div class="kpi-numero"><?= count($trat_activos) ?></div>
                            <div class="kpi-etiqueta">Tratamientos en curso</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icono rojo"><i class="bi bi-exclamation-circle"></i></div>
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
                            <button class="btn-outline-odent" onclick="mostrarPagina('agenda')" style="font-size:13px;padding:6px 14px;">Ver agenda →</button>
                        </div>
                        <?php if (!$citas): ?>
                            <p style="color:var(--gris-azulado);font-size:14px;">No hay citas programadas para hoy.</p>
                        <?php endif; ?>
                        <?php foreach ($citas as $cita): ?>
                            <div class="cita-item">
                                <div class="cita-hora"><?= $cita['hora'] ?></div>
                                <div class="cita-info">
                                    <div class="cita-paciente"><?= htmlspecialchars($cita['paciente']) ?></div>
                                    <div class="cita-tratamiento"><?= htmlspecialchars($cita['tratamiento']) ?></div>
                                </div>
                                <span class="badge-estado badge-<?= $cita['estado'] ?>"><?= ucfirst($cita['estado']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="tarjeta">
                        <div class="panel-titulo">
                            <h3>Tratamientos activos</h3>
                        </div>
                        <?php if (!$trat_activos): ?>
    <p style="color:var(--gris-azulado);font-size:14px;">No hay tratamientos activos por ahora.</p>
<?php endif; ?>
<?php foreach (array_slice($trat_activos, 0, 6) as $t): ?>
                            <div class="tratamiento-item">
                                <div class="tratamiento-header">
                                    <span><?= htmlspecialchars($t['paciente']) ?></span>
                                    <span><?= $t['progreso'] ?>%</span>
                                </div>
                                <div style="font-size:12px;color:var(--gris-azulado);margin-bottom:5px;"><?= htmlspecialchars($t['tratamiento']) ?></div>
                                <div class="barra-progreso">
                                    <div class="barra-relleno" style="width:<?= $t['progreso'] ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <div class="pagina" id="pagina-usuarios">

                <div class="panel-titulo" style="margin-bottom:20px;">
                    <div>
                        <h2>Gestión de usuarios</h2>
                        <p style="font-size:14px;color:var(--gris-azulado);margin-top:4px;">
                            Administre el acceso y roles del personal de Odent
                        </p>
                    </div>
                    <button class="btn-odent" onclick="abrirNuevoUsuario()">
                        <i class="bi bi-person-plus"></i> Nuevo usuario
                    </button>
                </div>

                <div class="kpi-grid" style="margin-bottom:24px;">
                    <div class="kpi-card">
                        <div class="kpi-icono azul"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="kpi-numero"><?= count($usuarios_sistema) ?></div>
                            <div class="kpi-etiqueta">Total usuarios</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icono verde"><i class="bi bi-person-check"></i></div>
                        <div>
                            <div class="kpi-numero"><?= count(array_filter($usuarios_sistema, fn($u) => $u['estado'] === 'activo')) ?></div>
                            <div class="kpi-etiqueta">Activos</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icono rojo"><i class="bi bi-person-dash"></i></div>
                        <div>
                            <div class="kpi-numero"><?= count(array_filter($usuarios_sistema, fn($u) => $u['estado'] === 'inactivo')) ?></div>
                            <div class="kpi-etiqueta">Inactivos</div>
                        </div>
                    </div>
                </div>

                <div class="tarjeta">
                    <div style="display:flex;gap:10px;margin-bottom:18px;">
                        <input type="text" id="buscar-usuario" placeholder="Buscar usuario..."
                            style="border:1.5px solid var(--borde);border-radius:8px;padding:8px 14px;font-size:14px;flex:1;"
                            oninput="filtrarUsuarios(this.value)">
                        <button class="btn-odent" onclick="filtrarUsuarios(document.getElementById('buscar-usuario').value)">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                    <table class="tabla-odent">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Usuario</th>
                                <th>Nombre de usuario</th>
                                <th>Roles</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios_sistema as $u): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div class="sidebar-perfil-avatar" style="width:34px;height:34px;font-size:12px;flex-shrink:0;">
                                                <?= mb_strtoupper(mb_substr($u['nombre'], 0, 1)) . mb_strtoupper(mb_substr(strrchr($u['nombre'], ' ') ?: ' ', 1, 1)) ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($u['nombre']) ?></div>
                                                <div style="font-size:12px;color:var(--gris-azulado);"><?= htmlspecialchars($u['correo']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="color:var(--gris-azulado);">@<?= htmlspecialchars($u['usuario']) ?></td>
                                    <td>
                                        <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                            <?php foreach ($u['roles'] as $r): ?>
                                                <span class="badge-estado <?= $r['nombre'] === 'administrador' ? 'badge-completada' : 'badge-confirmada' ?>"
                                                      title="<?= $r['principal'] ? 'Rol inicial' : 'Rol adicional' ?>">
                                                    <?= $r['principal'] ? '<i class="bi bi-star-fill" style="font-size:10px;"></i> ' : '' ?>
                                                    <?= ucfirst(str_replace('_', ' ', $r['nombre'])) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-estado badge-<?= $u['estado'] === 'activo' ? 'confirmada' : 'cancelada' ?>">
                                            <?= ucfirst($u['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:6px;">

                                            <button class="btn-outline-odent"
                                                onclick="abrirRestablecerContrasena(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>')"
                                                style="font-size:12px;padding:4px 10px;"
                                                title="Restablecer contraseña">
                                                <i class="bi bi-key"></i>
                                            </button>

                                            <button class="btn-outline-odent"
                                                onclick='abrirAsignarRol(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>)'
                                                style="font-size:12px;padding:4px 10px;"
                                                title="Asignar rol">
                                                <i class="bi bi-person-gear"></i>
                                            </button>

                                            <button class="btn-outline-odent"
                                                onclick='abrirEditarUsuario(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>)'
                                                style="font-size:12px;padding:4px 10px;"
                                                title="Editar usuario">
                                                <i class="bi bi-pencil"></i>
                                            </button>

                                            <button class="<?= $u['estado'] === 'activo' ? 'btn-peligro' : 'btn-odent' ?>"
                                                onclick="confirmarCambioEstado(<?= $u['id'] ?>, '<?= $u['estado'] ?>', '<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>')"
                                                style="font-size:12px;padding:4px 10px;"
                                                title="<?= $u['estado'] === 'activo' ? 'Inactivar' : 'Activar' ?>">
                                                <i class="bi bi-<?= $u['estado'] === 'activo' ? 'person-dash' : 'person-check' ?>"></i>
                                            </button>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php include ROOT_PATH . '/views/shared/agenda_pagina.php'; ?>

            <div class="pagina" id="pagina-pacientes">
                <div class="panel-titulo" style="margin-bottom:20px;">
                    <h2>Registro de pacientes</h2>
                    <button class="btn-odent" onclick="abrirModal('modal-nuevo-paciente')">
                        <i class="bi bi-person-plus"></i> Nuevo paciente
                    </button>
                </div>
                <div class="tarjeta">
                    <div style="display:flex;gap:10px;margin-bottom:18px;">
                        <input type="text" placeholder="Buscar por nombre o cédula..."
                            style="border:1.5px solid var(--borde);border-radius:8px;padding:8px 14px;font-size:14px;flex:1;">
                        <button class="btn-odent"><i class="bi bi-search"></i> Buscar</button>
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
                                    <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                                    <td><?= $p['cedula'] ?></td>
                                    <td><?= $p['telefono'] ?></td>
                                    <td><?= $p['correo'] ?></td>
                                    <td><span class="badge-estado badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                                    <td>
                                        <button class="btn-outline-odent" style="font-size:12px;padding:4px 10px;">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="pagina" id="pagina-expedientes">

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
                                                        <button class="btn-outline-odent" onclick="treAbrirHistorial()" id="btn-ver-historial-trat" style="display:none;">
                                <i class="bi bi-clipboard2-pulse"></i> Ver Historial de Tratamientos
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
                            <div class="odontograma-fila" id="odo-fila-superior">

                            </div>
                            <div style="border-top:2px dashed var(--borde);margin:10px 0;"></div>
                            <p style="font-size:12px;color:var(--gris-azulado);margin-bottom:12px;text-align:center;font-weight:600;">MAXILAR INFERIOR</p>
                            <div class="odontograma-fila" id="odo-fila-inferior">

                            </div>
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

            <div class="pagina" id="pagina-tratamientos">
                <?php include ROOT_PATH . '/views/shared/tratamientos_pagina.php'; ?>
            </div>

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

            <?php if ($rol === 'administrador'): ?>
                <?php include ROOT_PATH . '/views/shared/inventario_pagina.php'; ?>
            <?php endif; ?>

            <div class="pagina" id="pagina-reportes">
                <div class="panel-titulo" style="margin-bottom:20px;">
                    <h2>Reportes</h2>
                </div>
                <div class="tarjeta">
                    <div class="alerta info">
                        <i class="bi bi-info-circle"></i>
                        El módulo de reportes está siendo desarrollado por el equipo.
                    </div>
                </div>
            </div>

            <?php if ($rol === 'administrador'): ?>
            <div class="pagina" id="pagina-bitacora">
                <div class="panel-titulo" style="margin-bottom:20px;">
                    <h2>Bitácora de auditoría</h2>
                </div>
                <div class="tarjeta">
                    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:18px;align-items:flex-end;">
                        <div class="campo-grupo" style="margin:0;flex:1;min-width:160px;">
                            <label>Usuario</label>
                            <input type="text" id="bit-usuario" placeholder="Nombre de usuario">
                        </div>
                           <div class="campo-grupo" style="margin:0;min-width:180px;">
                            <label>Acción</label>
                            <select id="bit-accion"><option value="">Todas</option></select>
                        </div>
                        <div class="campo-grupo" style="margin:0;">
                            <label>Desde</label>
                            <input type="date" id="bit-desde">
                        </div>
                        <div class="campo-grupo" style="margin:0;">
                            <label>Hasta</label>
                            <input type="date" id="bit-hasta">
                        </div>
                        <button class="btn-odent" onclick="cargarBitacora()"><i class="bi bi-funnel"></i> Filtrar</button>
                        <button class="btn-outline-odent" onclick="limpiarFiltrosBitacora()">Limpiar</button>
                    </div>
                    <table class="tabla-odent">
                        <thead>
                            <tr><th>Fecha y hora</th><th>Responsable</th><th>Acción</th><th>Detalle</th><th>IP</th></tr>
                        </thead>
                        <tbody id="bit-tbody">
                            <tr><td colspan="5" style="text-align:center;color:var(--gris-azulado);">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

          <?php include ROOT_PATH . '/views/shared/tratamientos_modales.php'; ?>
    <?php if ($rol === 'administrador'): ?>
        <?php include ROOT_PATH . '/views/shared/inventario_modales.php'; ?>
    <?php endif; ?>

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

    <div class="modal-overlay" id="modal-nuevo-usuario">
        <div class="modal-caja">
            <div class="modal-header">
                <h3>Registrar nuevo usuario</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-nuevo-usuario')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="campo-grupo"><label>Nombre completo *</label>
                <input type="text" id="nu-nombre" placeholder="Nombre y apellidos">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="campo-grupo"><label>Identificación *</label>
                    <input type="text" id="nu-cedula" placeholder="0-0000-0000">
                </div>
                <div class="campo-grupo"><label>Teléfono</label>
                    <input type="tel" id="nu-telefono" placeholder="0000-0000">
                </div>
            </div>
            <div class="campo-grupo"><label>Correo electrónico *</label>
                <input type="email" id="nu-correo" placeholder="correo@ejemplo.com">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="campo-grupo"><label>Nombre de usuario *</label>
                    <input type="text" id="nu-usuario" placeholder="nombre.apellido">
                </div>
                <div class="campo-grupo"><label>Rol inicial *</label>
                    <select id="nu-rol">
                        <option value="">Seleccione un rol</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= ucfirst(str_replace('_', ' ', $r['nombre'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="campo-grupo"><label>Contraseña inicial *</label>
                <div class="input-icono">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="nu-contrasena" placeholder="Mín. 8 caracteres, mayúscula, minúscula y número">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline-odent" onclick="cerrarModal('modal-nuevo-usuario')">Cancelar</button>
                <button class="btn-odent" onclick="guardarNuevoUsuario()">
                    <i class="bi bi-check-lg"></i> Registrar usuario
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-asignar-rol">
        <div class="modal-caja">
            <div class="modal-header">
                <h3>Asignar roles</h3>

                <button
                    class="modal-cerrar"
                    onclick="cerrarModal('modal-asignar-rol')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <input type="hidden" id="ar-usuario-id">

            <div class="campo-grupo">
                <label>Usuario</label>
                <p>
                    <strong id="ar-usuario-nombre"></strong>
                </p>
            </div>

            <div class="campo-grupo">
                <label>Roles del usuario *</label>
                <p style="font-size:12px;color:var(--gris-azulado);margin:0 0 8px;">
                    Marque todos los roles que tendrá. Con la estrella defina el rol inicial.
                </p>

                <?php foreach ($roles as $r): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee;">
                        <label style="display:flex;align-items:center;gap:8px;margin:0;font-weight:500;">
                            <input type="checkbox" class="ar-check" value="<?= $r['id'] ?>"
                                   onchange="alCambiarRolCheck(this)">
                            <?= ucfirst(str_replace('_', ' ', $r['nombre'])) ?>
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;margin:0;font-size:12px;">
                            <input type="radio" name="ar-principal" class="ar-radio" value="<?= $r['id'] ?>"
                                   onchange="alCambiarRolRadio(this)">
                            Inicial
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="modal-footer">
                <button
                    class="btn-outline-odent"
                    onclick="cerrarModal('modal-asignar-rol')">
                    Cancelar
                </button>

                <button
                    class="btn-odent"
                    onclick="guardarAsignacionRol()">
                    <i class="bi bi-person-check"></i>
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-editar-usuario">
        <div class="modal-caja">
            <div class="modal-header">
                <h3>Editar usuario</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-editar-usuario')"><i class="bi bi-x-lg"></i></button>
            </div>
            <input type="hidden" id="eu-id">
            <div class="campo-grupo"><label>Nombre completo *</label>
                <input type="text" id="eu-nombre" placeholder="Nombre y apellidos">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="campo-grupo"><label>Identificación *</label>
                    <input type="text" id="eu-cedula" placeholder="0-0000-0000">
                </div>
                <div class="campo-grupo"><label>Teléfono</label>
                    <input type="tel" id="eu-telefono" placeholder="0000-0000">
                </div>
            </div>
            <div class="campo-grupo"><label>Correo electrónico *</label>
                <input type="email" id="eu-correo" placeholder="correo@ejemplo.com">
            </div>
            <div class="campo-grupo"><label>Nombre de usuario *</label>
                <input type="text" id="eu-usuario" placeholder="nombre.apellido">
            </div>
            <div class="modal-footer">
                <button class="btn-outline-odent" onclick="cerrarModal('modal-editar-usuario')">Cancelar</button>
                <button class="btn-odent" onclick="guardarEdicionUsuario()">
                    <i class="bi bi-check-lg"></i> Guardar cambios
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-restablecer">
        <div class="modal-caja">
            <div class="modal-header">
                <h3>Restablecer contraseña</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-restablecer')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div style="background:var(--fondo-odent);border-radius:8px;padding:14px;margin-bottom:18px;">
                <p style="font-size:14px;font-weight:600;" id="restablecer-nombre-usuario"></p>
                <p style="font-size:12px;color:var(--gris-azulado);">Se asignará una nueva contraseña a este usuario</p>
            </div>
            <div class="campo-grupo"><label>Nueva contraseña *</label>
                <div class="input-icono">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="nueva-contrasena" placeholder="Mín. 8 caracteres, mayúscula, minúscula y número">
                </div>
            </div>
            <div class="campo-grupo"><label>Confirmar contraseña *</label>
                <div class="input-icono">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" id="confirmar-contrasena" placeholder="Repita la contraseña">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-outline-odent" onclick="cerrarModal('modal-restablecer')">Cancelar</button>
                <button class="btn-odent" onclick="guardarNuevaContrasena()">
                    <i class="bi bi-key"></i> Restablecer contraseña
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-confirmar-estado">
        <div class="modal-caja" style="max-width:400px;">
            <div class="modal-header">
                <h3 id="confirmar-titulo">Confirmar acción</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-confirmar-estado')"><i class="bi bi-x-lg"></i></button>
            </div>
            <p id="confirmar-mensaje" style="font-size:14px;color:var(--gris-texto);margin-bottom:20px;"></p>
            <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-confirmar-estado')">Cancelar</button>
                <button id="btn-confirmar-estado" class="btn-peligro" onclick="ejecutarCambioEstado()">Confirmar</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modal-nueva-cotizacion">
        <div class="modal-caja">
            <div class="modal-header">
                <h3>Nueva cotización</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-nueva-cotizacion')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="campo-grupo"><label>Paciente</label>
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
            <div class="modal-footer">
                <button class="btn-outline-odent" onclick="cerrarModal('modal-nueva-cotizacion')">Cancelar</button>
                <button class="btn-odent"><i class="bi bi-check-lg"></i> Crear cotización</button>
            </div>
        </div>
    </div>

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

    <script src="<?= BASE_URL ?>/public/js/app.js?v=<?= @filemtime(ROOT_PATH . '/public/js/app.js') ?>"></script>
    <script src="<?= BASE_URL ?>/public/js/citas.js?v=<?= @filemtime(ROOT_PATH . '/public/js/citas.js') ?>"></script>
        <script src="<?= BASE_URL ?>/public/js/tratamientos.js?v=<?= @filemtime(ROOT_PATH . '/public/js/tratamientos.js') ?>"></script>
    <?php if ($rol === 'administrador'): ?>
        <script src="<?= BASE_URL ?>/public/js/inventario.js?v=<?= @filemtime(ROOT_PATH . '/public/js/inventario.js') ?>"></script>
    <?php endif; ?>
    <script>
        const BASE = '<?= BASE_URL ?>';
        let usuarioIdSeleccionado = null;
        let estadoActualSeleccionado = null;

        function abrirNuevoUsuario() {
            ['nu-nombre', 'nu-cedula', 'nu-telefono', 'nu-correo', 'nu-usuario', 'nu-contrasena', 'nu-rol']
                .forEach(id => document.getElementById(id).value = '');
            abrirModal('modal-nuevo-usuario');
        }

        const escapar = t => String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        async function cargarBitacora() {
            const tbody = document.getElementById('bit-tbody');
            if (!tbody) return;
            const params = new URLSearchParams({
                accion: 'bitacora.listar',
                usuario: document.getElementById('bit-usuario').value.trim(),
                accion_f: document.getElementById('bit-accion').value,
                desde: document.getElementById('bit-desde').value,
                hasta: document.getElementById('bit-hasta').value
            });
            try {
                const res = await fetch(`${BASE}/index.php?${params}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!data.ok) throw new Error(data.error);

                const select = document.getElementById('bit-accion');
                if (select.options.length === 1) {
                    data.acciones.forEach(a => select.add(new Option(a.replaceAll('_', ' '), a)));
                }

                tbody.innerHTML = data.data.length ? data.data.map(r => `
                    <tr>
                        <td>${escapar(r.created_at)}</td>
                        <td>@${escapar(r.usuario_nombre)}</td>
                        <td><span class="badge-estado badge-confirmada">${escapar(r.accion.replaceAll('_', ' '))}</span></td>
                        <td>${escapar(r.descripcion)}</td>
                        <td style="color:var(--gris-azulado);">${escapar(r.ip)}</td>
                    </tr>`).join('')
                    : '<tr><td colspan="5" style="text-align:center;color:var(--gris-azulado);">No hay registros que cumplan los criterios.</td></tr>';
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No se pudo cargar la bitácora.</td></tr>';
            }
        }

        function limpiarFiltrosBitacora() {
            ['bit-usuario', 'bit-accion', 'bit-desde', 'bit-hasta'].forEach(id => document.getElementById(id).value = '');
            cargarBitacora();
        }

        function filtrarUsuarios(texto) {
            const filtro = texto.toLowerCase().trim();
            document.querySelectorAll('#pagina-usuarios tbody tr').forEach(fila => {
                const nombre = fila.cells[1]?.textContent.toLowerCase() ?? '';
                const usuario = fila.cells[2]?.textContent.toLowerCase() ?? '';
                const rol = fila.cells[3]?.textContent.toLowerCase() ?? '';
                fila.style.display = (!filtro || nombre.includes(filtro) || usuario.includes(filtro) || rol.includes(filtro)) ?
                    '' : 'none';
            });
        }

        function abrirRestablecerContrasena(id, nombre) {
            usuarioIdSeleccionado = id;
            document.getElementById('restablecer-nombre-usuario').textContent = nombre;
            document.getElementById('nueva-contrasena').value = '';
            document.getElementById('confirmar-contrasena').value = '';
            abrirModal('modal-restablecer');
        }

        async function guardarNuevaContrasena() {
            const nueva = document.getElementById('nueva-contrasena').value;
            const confirmar = document.getElementById('confirmar-contrasena').value;
            if (!validarCampos(['nueva-contrasena', 'confirmar-contrasena'], 'Complete ambos campos de contraseña.')) return;
            const errPolitica = validarPoliticaContrasena(nueva);
            if (errPolitica) return mostrarToast(errPolitica, 'aviso', 6000);
            if (nueva !== confirmar) return mostrarToast('Las contraseñas no coinciden.', 'aviso');

            await guardarJSON('usuarios.restablecer', { id: usuarioIdSeleccionado, contrasena: nueva }, {
                boton: document.querySelector('#modal-restablecer .modal-footer .btn-odent'),
                cerrarModal: 'modal-restablecer',
                exito: 'Contraseña restablecida con éxito.'
            });
        }

        async function guardarNuevoUsuario() {
            if (!validarCampos(['nu-nombre', 'nu-cedula', 'nu-correo', 'nu-usuario', 'nu-contrasena', 'nu-rol'])) return;

            const contrasena = document.getElementById('nu-contrasena').value;
            const errPolitica = validarPoliticaContrasena(contrasena);
            if (errPolitica) return mostrarToast(errPolitica, 'aviso', 6000);

            const correo = document.getElementById('nu-correo').value.trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
                document.getElementById('nu-correo').classList.add('campo-invalido');
                return mostrarToast('Ingrese un correo electrónico válido.', 'aviso');
            }

            const usuario = document.getElementById('nu-usuario').value.trim();
            await guardarJSON('usuarios.crear', {
                nombre: document.getElementById('nu-nombre').value.trim(),
                cedula: document.getElementById('nu-cedula').value.trim(),
                telefono: document.getElementById('nu-telefono').value.trim(),
                correo,
                usuario,
                contrasena,
                rol_id: document.getElementById('nu-rol').value
            }, {
                boton: document.querySelector('#modal-nuevo-usuario .modal-footer .btn-odent'),
                cerrarModal: 'modal-nuevo-usuario',
                exito: `Usuario @${usuario} registrado con éxito.`,
                recargar: ['usuarios', 'inicio']
            });
        }

        function abrirAsignarRol(u) {
            document.getElementById('ar-usuario-id').value = u.id;
            document.getElementById('ar-usuario-nombre').textContent = u.nombre;

            const asignados = (u.roles_ids ?? [u.rol_id]).map(String);
            document.querySelectorAll('.ar-check').forEach(c => {
                c.checked = asignados.includes(c.value);
            });
            document.querySelectorAll('.ar-radio').forEach(r => {
                r.checked = String(u.rol_id) === r.value;
            });

            abrirModal('modal-asignar-rol');
        }
        function alCambiarRolRadio(radio) {
            const check = document.querySelector(`.ar-check[value="${radio.value}"]`);
            if (check) check.checked = true;
        }

        function alCambiarRolCheck(check) {
            if (!check.checked) {
                const radio = document.querySelector(`.ar-radio[value="${check.value}"]`);
                if (radio && radio.checked) radio.checked = false;
            }
        }

        async function guardarAsignacionRol() {
            const id = document.getElementById('ar-usuario-id').value;
            const roles = [...document.querySelectorAll('.ar-check:checked')].map(c => Number(c.value));
            const principal = document.querySelector('.ar-radio:checked');

            if (roles.length === 0) return mostrarToast('Seleccione al menos un rol.', 'aviso');
            if (!principal) return mostrarToast('Seleccione cuál será el rol inicial.', 'aviso');

            await guardarJSON('usuarios.asignarRol', { id, roles, rol_principal: Number(principal.value) }, {
                boton: document.querySelector('#modal-asignar-rol .modal-footer .btn-odent'),
                cerrarModal: 'modal-asignar-rol',
                exito: 'Roles actualizados con éxito.',
                recargar: ['usuarios']
            });
        }

        function abrirEditarUsuario(u) {
            document.getElementById('eu-id').value = u.id;
            document.getElementById('eu-nombre').value = u.nombre ?? '';
            document.getElementById('eu-cedula').value = u.cedula ?? '';
            document.getElementById('eu-telefono').value = u.telefono ?? '';
            document.getElementById('eu-correo').value = u.correo ?? '';
            document.getElementById('eu-usuario').value = u.usuario ?? '';
            abrirModal('modal-editar-usuario');
        }

        async function guardarEdicionUsuario() {
            if (!validarCampos(['eu-nombre', 'eu-cedula', 'eu-correo', 'eu-usuario'])) return;

            await guardarJSON('usuarios.editar', {
                id: document.getElementById('eu-id').value,
                nombre: document.getElementById('eu-nombre').value.trim(),
                cedula: document.getElementById('eu-cedula').value.trim(),
                telefono: document.getElementById('eu-telefono').value.trim(),
                correo: document.getElementById('eu-correo').value.trim(),
                usuario: document.getElementById('eu-usuario').value.trim()
            }, {
                boton: document.querySelector('#modal-editar-usuario .modal-footer .btn-odent'),
                cerrarModal: 'modal-editar-usuario',
                exito: 'Usuario actualizado con éxito.',
                recargar: ['usuarios']
            });
        }

        let _expPacienteActual = null;
        let _expDatos          = null;

        const _exp = (id) => document.getElementById(id);
        const _expEsc = (t) => String(t ?? '—').replace(/[&<>"']/g, c =>
            ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

        async function expCargarLista(q = '') {
            const tbody = _exp('exp-tbody');
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--gris-azulado);">Cargando…</td></tr>';
            try {
                const url = `${BASE}/index.php?accion=expedientes.pacientes` + (q ? `&q=${encodeURIComponent(q)}` : '');
                const res  = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (!Array.isArray(data) || data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--gris-azulado);">No se encontraron pacientes.</td></tr>';
                    return;
                }
                tbody.innerHTML = data.map((p, i) => `
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
                            ${p.id_expediente
                                ? '<span class="badge-estado badge-completada"><i class="bi bi-check-circle"></i> Creado</span>'
                                : '<span class="badge-estado badge-pendiente"><i class="bi bi-dash-circle"></i> Sin expediente</span>'}
                        </td>
                        <td>
                            <button class="btn-outline-odent" style="font-size:12px;padding:4px 10px;"
                                onclick='expAbrirPaciente(${JSON.stringify(p)})'>
                                <i class="bi bi-clipboard2-pulse"></i> Ver expediente
                            </button>
                        </td>
                    </tr>`).join('');
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
                _exp('exp-sin-expediente').style.display      = '';
                _exp('btn-editar-pac').style.display          = 'none';
                _exp('btn-editar-exp').style.display          = 'none';
                _exp('btn-ver-odontograma').style.display     = 'none';
                _exp('btn-ver-documentos').style.display      = 'none';
                _exp('btn-ver-historial-trat').style.display = 'none';
                return;
            }

            _exp('btn-editar-pac').style.display = '';

            try {
                const res  = await fetch(`${BASE}/index.php?accion=expedientes.obtener&id_paciente=${paciente.id_paciente}`, {
                    headers: { Accept: 'application/json' }
                });
                const data = await res.json();

                if (!data || !data.id_expediente) {
                    _exp('exp-sin-expediente').style.display      = '';
                    _exp('btn-editar-exp').style.display          = 'none';
                    _exp('btn-ver-odontograma').style.display     = 'none';
                    _exp('btn-ver-documentos').style.display      = 'none';
                    _exp('btn-ver-historial-trat').style.display = 'none';
                } else {
                    _expDatos = data;
                    expRenderizarExpediente(data, paciente);
                    _exp('exp-con-expediente').style.display      = '';
                    _exp('btn-editar-exp').style.display          = '';
                    _exp('btn-ver-odontograma').style.display     = '';
                    _exp('btn-ver-documentos').style.display      = '';
                    _exp('btn-ver-historial-trat').style.display = '';
                    expCargarAtenciones(data.id_expediente);
                }
            } catch (e) {
                mostrarToast('Error al cargar el expediente.', 'error');
            }
        }

        function expRenderizarExpediente(data, paciente) {
            _exp('exp-motivo').textContent        = data.motivo_consulta  || '—';
            _exp('exp-alergias').textContent      = data.alergias         || 'Ninguna';
            _exp('exp-enfermedades').textContent  = data.enfermedades     || 'Ninguna';
            _exp('exp-medicamentos').textContent  = data.medicamentos_actuales || 'Ninguno';
            _exp('exp-antecedentes').textContent  = data.antecedentes     || 'Sin antecedentes';
            _exp('exp-observaciones').textContent = data.observaciones    || '—';

            _exp('exp-pac-fnac').textContent  = paciente.fecha_nacimiento ? new Date(paciente.fecha_nacimiento + 'T00:00:00').toLocaleDateString('es-CR') : '—';
            _exp('exp-pac-edad').textContent  = paciente.edad != null ? paciente.edad + ' años' : '—';
            _exp('exp-pac-sexo').textContent  = paciente.sexo ? (paciente.sexo.charAt(0).toUpperCase() + paciente.sexo.slice(1)) : '—';
            _exp('exp-pac-sangre').textContent = paciente.grupo_sanguineo || '—';
            _exp('exp-pac-tel').textContent    = paciente.telefono   || '—';
            _exp('exp-pac-correo').textContent = paciente.correo     || '—';
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
            _exp('btn-editar-pac').style.display = 'none';
            _exp('exp-vista-detalle').style.display = 'none';
            _exp('exp-vista-lista').style.display   = '';
        }

        async function _expRefrescarPaciente() {
            const ref = _expPacienteActual;
            if (!ref) return;
            try {
                const res  = await fetch(`${BASE}/index.php?accion=expedientes.pacientes&q=${encodeURIComponent(ref.identificacion || ref.nombre_completo || '')}`, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                const nuevo = Array.isArray(data) ? data.find(x => String(x.id_usuario) === String(ref.id_usuario)) : null;
                expAbrirPaciente(nuevo || ref);
            } catch (e) {
                expAbrirPaciente(ref);
            }
        }

        async function guardarCrearExpediente() {
            if (!validarCampos(['ec-motivo'], 'El motivo de consulta es obligatorio.')) return;
            if (!_expPacienteActual) return mostrarToast('No se identificó al paciente.', 'error');

            await guardarJSON('expedientes.crear', {
                id_paciente:          _expPacienteActual.id_paciente,
                id_usuario:          _expPacienteActual.id_usuario,
                motivo_consulta:      _exp('ec-motivo').value.trim(),
                alergias:             _exp('ec-alergias').value.trim(),
                enfermedades:         _exp('ec-enfermedades').value.trim(),
                medicamentos_actuales:_exp('ec-medicamentos').value.trim(),
                antecedentes:         _exp('ec-antecedentes').value.trim(),
                observaciones:        _exp('ec-observaciones').value.trim()
            }, {
                boton: document.querySelector('#modal-exp-crear .modal-footer .btn-odent'),
                cerrarModal: 'modal-exp-crear',
                exito: 'Expediente creado exitosamente.',
                alExito: () => _expRefrescarPaciente()
            });
        }

        function abrirEditarExpediente() {
            if (!_expDatos) return;
            _exp('ee-id-expediente').value = _expDatos.id_expediente;
            _exp('ee-motivo').value        = _expDatos.motivo_consulta          || '';
            _exp('ee-alergias').value      = _expDatos.alergias                 || '';
            _exp('ee-enfermedades').value  = _expDatos.enfermedades             || '';
            _exp('ee-medicamentos').value  = _expDatos.medicamentos_actuales    || '';
            _exp('ee-antecedentes').value  = _expDatos.antecedentes             || '';
            _exp('ee-observaciones').value = _expDatos.observaciones            || '';
            abrirModal('modal-exp-editar');
        }

        async function guardarEditarExpediente() {
            if (!validarCampos(['ee-motivo'], 'El motivo de consulta es obligatorio.')) return;

            await guardarJSON('expedientes.actualizar', {
                id_expediente:        _exp('ee-id-expediente').value,
                motivo_consulta:      _exp('ee-motivo').value.trim(),
                alergias:             _exp('ee-alergias').value.trim(),
                enfermedades:         _exp('ee-enfermedades').value.trim(),
                medicamentos_actuales:_exp('ee-medicamentos').value.trim(),
                antecedentes:         _exp('ee-antecedentes').value.trim(),
                observaciones:        _exp('ee-observaciones').value.trim()
            }, {
                boton: document.querySelector('#modal-exp-editar .modal-footer .btn-odent'),
                cerrarModal: 'modal-exp-editar',
                exito: 'Expediente actualizado.',
                alExito: () => expAbrirPaciente(_expPacienteActual)
            });
        }

        function abrirEditarPaciente() {
            if (!_expPacienteActual?.id_paciente) return;
            _exp('ep-id-paciente').value       = _expPacienteActual.id_paciente;
            _exp('ep-fecha-nac').value         = _expPacienteActual.fecha_nacimiento || '';
            _exp('ep-sexo').value              = _expPacienteActual.sexo            || '';
            _exp('ep-sangre').value            = _expPacienteActual.grupo_sanguineo || '';
            epActualizarEdad(_expPacienteActual.fecha_nacimiento || '');
            abrirModal('modal-editar-paciente');
        }

        function epActualizarEdad(fechaStr) {
            const preview = _exp('ep-edad-preview');
            if (!fechaStr) { preview.textContent = ''; return; }
            const hoy   = new Date();
            const nac   = new Date(fechaStr + 'T00:00:00');
            let edad    = hoy.getFullYear() - nac.getFullYear();
            const m     = hoy.getMonth() - nac.getMonth();
            if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) edad--;
            preview.textContent = edad >= 0 ? `Edad: ${edad} años` : '';
        }

        async function guardarEditarPaciente() {
            const pacienteRef = _expPacienteActual;
            const res = await guardarJSON('expedientes.paciente.actualizar', {
                id_paciente:     _exp('ep-id-paciente').value,
                fecha_nacimiento:_exp('ep-fecha-nac').value,
                sexo:            _exp('ep-sexo').value,
                grupo_sanguineo: _exp('ep-sangre').value
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
                id_expediente:        _exp('ea-id-expediente').value,
                diagnostico:          _exp('ea-diagnostico').value.trim(),
                tratamiento_realizado:_exp('ea-tratamiento').value.trim(),
                observaciones:        _exp('ea-observaciones').value.trim()
            }, {
                boton: document.querySelector('#modal-exp-atencion .modal-footer .btn-odent'),
                cerrarModal: 'modal-exp-atencion',
                exito: 'Atención registrada exitosamente.',
                alExito: () => expCargarAtenciones(_expDatos.id_expediente)
            });
        }

        const _mostrarPaginaBase = window.mostrarPagina;

        let _navegandoDesdeExpediente = false;

        window.mostrarPagina = function (slug) {
            _mostrarPaginaBase(slug);
            if (slug === 'expedientes') {
                expVolverLista();
                expCargarLista();
            }
            if (slug === 'bitacora') cargarBitacora();
            if (slug === 'inventario' && window.Inventario) Inventario.listar();
            if (slug === 'odontograma') {
                if (_navegandoDesdeExpediente) {
                    _navegandoDesdeExpediente = false;
                } else {
                    _odoExpediente = null;
                    _odoPacientesCache = null;
                }
                odoIniciar();
            }
            if (slug === 'documentos') {
                if (_navegandoDesdeExpediente) {
                    _navegandoDesdeExpediente = false;
                } else {
                    _docExpediente = null;
                    _docPacientesCache = null;
                }
                docIniciar();
            }
        };

        let _odoExpediente  = null;
        let _odoPiezas      = {};
        let _odoPiezaActual = null;

        const ODO_SUPERIOR = [18,17,16,15,14,13,12,11,21,22,23,24,25,26,27,28];
        const ODO_INFERIOR = [48,47,46,45,44,43,42,41,31,32,33,34,35,36,37,38];

        const ODO_CLASES = {
            sano:        'sano',
            caries:      'caries',
            corona:      'corona',
            ausente:     'ausente',
            endodoncia:  'endodoncia',
            extraccion:  'extraccion',
            implante:    'implante',
        };

        function _odo(id) { return document.getElementById(id); }

        function _odoEstado(num) {
            return (_odoPiezas[num]?.estado) ?? 'sano';
        }

        function _odoRenderizarDiente(num) {
            const estado = _odoEstado(num);
            return `<div class="diente ${estado}" id="diente-${num}" onclick="odoSeleccionarDiente(${num})">
                        <i class="bi bi-circle-fill" style="font-size:10px;"></i>
                        <span>${num}</span>
                    </div>`;
        }

        function _odoRenderizarFila(nums) {
            return nums.map(_odoRenderizarDiente).join('');
        }

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
                const r = await fetch(`${BASE}/index.php?accion=odontograma.obtener&id_expediente=${_odoExpediente.id_expediente}`);
                const data = await r.json();
                if (!data.ok) return;
                _odoPiezas = data.piezas ?? {};
                [...ODO_SUPERIOR, ...ODO_INFERIOR].forEach(_odoActualizarDomPieza);
                if (typeof treOdontograma === 'function') treOdontograma(_odoExpediente.id_expediente);
            } catch(e) {
                mostrarToast('Error al cargar el odontograma.', 'error');
            }
        }

        function odoSeleccionarDiente(num) {
            _odoPiezaActual = num;
            const datos = _odoPiezas[num] ?? { estado: 'sano', observaciones: '', updated_at: null };

            _odo('odo-hint').style.display          = 'none';
            _odo('odo-loading').style.display        = 'none';
            _odo('odo-panel-edicion').style.display  = '';

            _odo('odo-numero').value          = num;
            _odo('odo-estado').value          = datos.estado ?? 'sano';
            _odo('odo-observaciones').value   = datos.observaciones ?? '';

            if (datos.updated_at) {
                _odo('odo-fecha-mod').textContent = datos.updated_at;
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
            _odo('odo-hint').style.display          = '';
            _odo('odo-loading').style.display        = 'none';
            _odo('odo-panel-edicion').style.display  = 'none';
            document.querySelectorAll('#pagina-odontograma .diente').forEach(el => el.classList.remove('seleccionado'));
        }

        function odoCancelarEdicion() {
            _odoCancelarSeleccion();
        }

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
                alExito: (res) => {
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
                id_expediente:  _expDatos.id_expediente,
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
                const r = await fetch(`${BASE}/index.php?accion=expedientes.pacientes`);
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
            lista = lista.filter(p => p.id_expediente);
            const termino = filtro.trim().toLowerCase();
            const filtrados = termino
                ? lista.filter(p => (p.nombre_completo || '').toLowerCase().includes(termino))
                : lista;

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
                const r = await fetch(`${BASE}/index.php?accion=expedientes.obtener&id_paciente=${idPaciente}`);
                const data = await r.json();
                if (!data || !data.id_expediente) {
                    mostrarToast('Este paciente no tiene expediente clínico aún.', 'aviso');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-clipboard2-pulse"></i> Ver odontograma';
                    return;
                }
                _odoExpediente = { id_expediente: data.id_expediente, nombre_completo: nombreCompleto };
                _navegandoDesdeExpediente = true;
                odoIniciar();
            } catch(e) {
                mostrarToast('Error al cargar el expediente.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-clipboard2-pulse"></i> Ver odontograma';
            }
        }

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
                _doc('doc-sin-expediente').style.display = '';
                _doc('doc-con-expediente').style.display = 'none';
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
                const res  = await fetch(`${BASE}/index.php?accion=documentos.subir`, {
                    method: 'POST',
                    body:   formData,
                });
                const data = await res.json();

                if (!data.ok) {
                    mostrarToast(data.mensaje || 'Error al subir el archivo.', 'error');
                } else {
                    mostrarToast('Documento subido correctamente.', 'exito');
                    cerrarModal('modal-doc-subir');
                    archivoInput.value  = '';
                    _doc('doc-descripcion').value = '';
                    docCargarLista();
                }
            } catch(e) {
                mostrarToast('Error de conexión al subir el archivo.', 'error');
            } finally {
                btn.disabled = false;
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
                boton:        _doc('doc-btn-confirmar-eliminar'),
                cerrarModal:  'modal-doc-confirmar-eliminar',
                exito:        'Documento eliminado.',
                alExito:      () => {
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
                const r = await fetch(`${BASE}/index.php?accion=expedientes.pacientes`);
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
            lista = lista.filter(p => p.id_expediente);
            const termino = filtro.trim().toLowerCase();
            const filtrados = termino
                ? lista.filter(p => (p.nombre_completo || '').toLowerCase().includes(termino))
                : lista;

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
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            try {
                const r = await fetch(`${BASE}/index.php?accion=expedientes.obtener&id_paciente=${idPaciente}`);
                const data = await r.json();
                if (!data || !data.id_expediente) {
                    mostrarToast('Este paciente no tiene expediente clínico aún.', 'aviso');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-folder2-open"></i> Ver documentos';
                    return;
                }
                _docExpediente = { id_expediente: data.id_expediente, nombre_completo: nombreCompleto };
                _navegandoDesdeExpediente = true;
                docIniciar();
            } catch(e) {
                mostrarToast('Error al cargar el expediente.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-folder2-open"></i> Ver documentos';
            }
        }

        function confirmarCambioEstado(id, estadoActual, nombre) {
            usuarioIdSeleccionado = id;
            estadoActualSeleccionado = estadoActual;
            const accion = estadoActual === 'activo' ? 'inactivar' : 'activar';
            document.getElementById('confirmar-titulo').textContent = accion === 'inactivar' ? 'Inactivar usuario' : 'Activar usuario';
            document.getElementById('confirmar-mensaje').textContent = `¿Está seguro que desea ${accion} a ${nombre}?`;

            const btnConfirmar = document.getElementById('btn-confirmar-estado');
            btnConfirmar.className = accion === 'inactivar' ? 'btn-peligro' : 'btn-odent';
            btnConfirmar.textContent = accion === 'inactivar' ? 'Inactivar' : 'Activar';

            abrirModal('modal-confirmar-estado');
        }

        async function ejecutarCambioEstado() {
            const nuevoEstado = estadoActualSeleccionado === 'activo' ? 'inactivo' : 'activo';

            await guardarJSON('usuarios.estado', { id: usuarioIdSeleccionado, estado: nuevoEstado }, {
                boton: document.getElementById('btn-confirmar-estado'),
                cerrarModal: 'modal-confirmar-estado',
                exito: nuevoEstado === 'activo' ? 'Usuario activado con éxito.' : 'Usuario inactivado con éxito.',
                recargar: ['usuarios', 'inicio']
            });
        }
    </script>
</body>

</html>
