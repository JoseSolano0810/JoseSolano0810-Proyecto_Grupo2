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

            <!-- ───────────────── INICIO ───────────────── -->
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
                            <div class="kpi-numero"><?= count($tratamientos) ?></div>
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
                        <?php foreach ($tratamientos as $t): ?>
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

            <!-- ───────────────── USUARIOS ───────────────── -->
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

            <!-- ───────────────── AGENDA ───────────────── -->
            <div class="pagina" id="pagina-agenda">
                <div class="panel-titulo" style="margin-bottom:20px;">
                    <h2>Mi agenda</h2>
                    <button class="btn-odent" onclick="abrirModal('modal-nueva-cita')">
                        <i class="bi bi-plus-lg"></i> Nueva cita
                    </button>
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
                                    <td><strong><?= $cita['hora'] ?></strong></td>
                                    <td><?= htmlspecialchars($cita['paciente']) ?></td>
                                    <td><?= htmlspecialchars($cita['tratamiento']) ?></td>
                                    <td><span class="badge-estado badge-<?= $cita['estado'] ?>"><?= ucfirst($cita['estado']) ?></span></td>
                                    <td>
                                        <button class="btn-outline-odent" style="font-size:12px;padding:5px 12px;">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ───────────────── PACIENTES ───────────────── -->
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

            <!-- ───────────────── EXPEDIENTES ───────────────── -->
            <div class="pagina" id="pagina-expedientes">
                <div class="panel-titulo" style="margin-bottom:20px;">
                    <h2>Expedientes clínicos</h2>
                    <div style="display:flex;gap:10px;">
                        <input type="text" placeholder="Buscar paciente..."
                            style="border:1.5px solid var(--borde);border-radius:8px;padding:8px 14px;font-size:14px;min-width:220px;">
                        <button class="btn-odent"><i class="bi bi-search"></i> Buscar</button>
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
                                    <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                                    <td><?= $p['cedula'] ?></td>
                                    <td><?= $p['edad'] ?> años</td>
                                    <td><?= $p['ultima_visita'] ?></td>
                                    <td><span class="badge-estado badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span></td>
                                    <td>
                                        <button class="btn-outline-odent" onclick="mostrarPagina('odontograma')" style="font-size:12px;padding:5px 12px;">
                                            <i class="bi bi-clipboard2-pulse"></i> Ver expediente
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ───────────────── ODONTOGRAMA ───────────────── -->
            <div class="pagina" id="pagina-odontograma">
                <h2 style="margin-bottom:6px;">Odontograma</h2>
                <p style="font-size:14px;color:var(--gris-azulado);margin-bottom:20px;">
                    Paciente: <strong>Ana Rojas</strong> — Haga clic en un diente para editar
                </p>
                <div class="panel-grid col-2">
                    <div class="odontograma-container">
                        <p style="font-size:12px;color:var(--gris-azulado);margin-bottom:12px;text-align:center;font-weight:600;">MAXILAR SUPERIOR</p>
                        <div class="odontograma-fila">
                            <?php foreach ([18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28] as $num): ?>
                                <div class="diente <?= $estado_dientes[$num] ?? 'sano' ?>" onclick="seleccionarDiente(<?= $num ?>)" id="diente-<?= $num ?>">
                                    <i class="bi bi-circle-fill" style="font-size:10px;"></i>
                                    <span><?= $num ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="border-top:2px dashed var(--borde);margin:10px 0;"></div>
                        <p style="font-size:12px;color:var(--gris-azulado);margin-bottom:12px;text-align:center;font-weight:600;">MAXILAR INFERIOR</p>
                        <div class="odontograma-fila">
                            <?php foreach ([48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38] as $num): ?>
                                <div class="diente <?= $estado_dientes[$num] ?? 'sano' ?>" onclick="seleccionarDiente(<?= $num ?>)" id="diente-<?= $num ?>">
                                    <i class="bi bi-circle-fill" style="font-size:10px;"></i>
                                    <span><?= $num ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="leyenda-odontograma">
                            <div class="leyenda-item">
                                <div class="leyenda-color" style="background:#fff;border-color:var(--borde);"></div> Sano
                            </div>
                            <div class="leyenda-item">
                                <div class="leyenda-color" style="background:rgba(192,57,43,.2);border-color:#C0392B;"></div> Caries
                            </div>
                            <div class="leyenda-item">
                                <div class="leyenda-color" style="background:rgba(245,158,11,.2);border-color:#f59e0b;"></div> Corona
                            </div>
                            <div class="leyenda-item">
                                <div class="leyenda-color" style="background:var(--fondo-odent);border-color:var(--gris-azulado);"></div> Ausente
                            </div>
                        </div>
                    </div>
                    <div class="tarjeta">
                        <h3 style="margin-bottom:14px;">Detalle de pieza</h3>
                        <div id="detalle-diente">
                            <div class="alerta info"><i class="bi bi-hand-index"></i> Seleccione un diente para editar.</div>
                        </div>
                        <div id="panel-edicion-diente" style="display:none;">
                            <div class="campo-grupo"><label>Pieza dental (FDI)</label><input type="text" id="diente-numero" readonly></div>
                            <div class="campo-grupo">
                                <label>Estado</label>
                                <select id="diente-estado">
                                    <option value="sano">Sano</option>
                                    <option value="caries">Caries</option>
                                    <option value="corona">Corona</option>
                                    <option value="ausente">Ausente</option>
                                </select>
                            </div>
                            <div class="campo-grupo"><label>Observaciones</label><textarea rows="3" placeholder="Notas clínicas..."></textarea></div>
                            <div style="display:flex;gap:10px;">
                                <button class="btn-odent" onclick="guardarDiente()"><i class="bi bi-check-lg"></i> Guardar</button>
                                <button class="btn-outline-odent" onclick="cancelarDiente()">Cancelar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ───────────────── TRATAMIENTOS ───────────────── -->
            <div class="pagina" id="pagina-tratamientos">
                <div class="panel-titulo" style="margin-bottom:20px;">
                    <h2>Planes de tratamiento</h2>
                    <button class="btn-odent" onclick="abrirModal('modal-nuevo-tratamiento')"><i class="bi bi-plus-lg"></i> Nuevo plan</button>
                </div>
                <div class="panel-grid col-2">
                    <?php foreach ($pacientes as $p):
                        $idx = $p['id'] - 1;
                        $t   = $tratamientos[$idx] ?? ['tratamiento' => 'Sin plan activo', 'progreso' => 0];
                    ?>
                        <div class="tarjeta">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                                <div>
                                    <h4><?= htmlspecialchars($p['nombre']) ?></h4>
                                    <p style="font-size:13px;color:var(--gris-azulado);">Cédula: <?= $p['cedula'] ?></p>
                                </div>
                                <span class="badge-estado badge-<?= $p['estado'] ?>"><?= ucfirst($p['estado']) ?></span>
                            </div>
                            <div class="tratamiento-item">
                                <div class="tratamiento-header"><span><?= htmlspecialchars($t['tratamiento']) ?></span><span><?= $t['progreso'] ?>%</span></div>
                                <div class="barra-progreso" style="margin-bottom:12px;">
                                    <div class="barra-relleno" style="width:<?= $t['progreso'] ?>%;"></div>
                                </div>
                            </div>
                            <button class="btn-outline-odent" style="font-size:13px;padding:7px 14px;width:100%;"><i class="bi bi-eye"></i> Ver detalle</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ───────────────── COTIZACIONES ───────────────── -->
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

            <!-- ───────────────── PAGOS ───────────────── -->
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

            <!-- ───────────────── INVENTARIO ───────────────── -->
            <div class="pagina" id="pagina-inventario">
                <div class="panel-titulo" style="margin-bottom:20px;">
                    <h2>Inventario</h2>
                </div>
                <div class="tarjeta">
                    <div class="alerta info">
                        <i class="bi bi-info-circle"></i>
                        El módulo de inventario está siendo desarrollado por el equipo.
                    </div>
                </div>
            </div>

            <!-- ───────────────── REPORTES ───────────────── -->
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

            <!-- ───────────────── BITÁCORA ───────────────── -->
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

    <!-- Modal nueva cita -->
    <div class="modal-overlay" id="modal-nueva-cita">
        <div class="modal-caja">
            <div class="modal-header">
                <h3>Nueva cita</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-nueva-cita')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="campo-grupo"><label>Paciente</label>
                <select>
                    <option value="">Seleccione un paciente</option>
                    <?php foreach ($pacientes as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo-grupo"><label>Fecha</label><input type="date" value="<?= date('Y-m-d') ?>"></div>
            <div class="campo-grupo"><label>Hora</label><input type="time"></div>
            <div class="campo-grupo"><label>Tratamiento</label>
                <select>
                    <option>Limpieza dental</option>
                    <option>Extracción</option>
                    <option>Ortodoncia</option>
                    <option>Blanqueamiento</option>
                    <option>Revisión general</option>
                </select>
            </div>
            <div class="campo-grupo"><label>Notas</label><textarea rows="3" placeholder="Observaciones opcionales..."></textarea></div>
            <div class="modal-footer">
                <button class="btn-outline-odent" onclick="cerrarModal('modal-nueva-cita')">Cancelar</button>
                <button class="btn-odent"><i class="bi bi-check-lg"></i> Agendar</button>
            </div>
        </div>
    </div>

    <!-- Modal nuevo tratamiento -->
    <div class="modal-overlay" id="modal-nuevo-tratamiento">
        <div class="modal-caja">
            <div class="modal-header">
                <h3>Nuevo plan de tratamiento</h3>
                <button class="modal-cerrar" onclick="cerrarModal('modal-nuevo-tratamiento')"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="campo-grupo"><label>Paciente</label>
                <select>
                    <option value="">Seleccione un paciente</option>
                    <?php foreach ($pacientes as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo-grupo"><label>Nombre del plan</label><input type="text" placeholder="Ej: Ortodoncia fase 1"></div>
            <div class="campo-grupo"><label>Diagnóstico</label><textarea rows="3" placeholder="Descripción del diagnóstico..."></textarea></div>
            <div class="campo-grupo"><label>Fecha de inicio</label><input type="date" value="<?= date('Y-m-d') ?>"></div>
            <div class="modal-footer">
                <button class="btn-outline-odent" onclick="cerrarModal('modal-nuevo-tratamiento')">Cancelar</button>
                <button class="btn-odent"><i class="bi bi-check-lg"></i> Crear plan</button>
            </div>
        </div>
    </div>

    <!-- Modal nuevo paciente -->
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

    <!-- Modal nuevo usuario -->
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


    <!-- Modal asignar rol -->
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





    <!-- Modal editar usuario -->
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



    <!-- Modal restablecer contraseña -->
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

    <!-- Modal confirmar cambio estado -->
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

    <!-- Modal nueva cotizacion -->
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

    <script src="<?= BASE_URL ?>/public/js/app.js?v=<?= @filemtime(ROOT_PATH . '/public/js/app.js') ?>"></script>
    <script>
        const BASE = '<?= BASE_URL ?>';
        let usuarioIdSeleccionado = null;
        let estadoActualSeleccionado = null;


        /* ── USU-01 esc. 3: cancelar descarta lo digitado ───────── */
        function abrirNuevoUsuario() {
            ['nu-nombre', 'nu-cedula', 'nu-telefono', 'nu-correo', 'nu-usuario', 'nu-contrasena', 'nu-rol']
                .forEach(id => document.getElementById(id).value = '');
            abrirModal('modal-nuevo-usuario');
        }

        /* ── USU-06: bitácora ───────────────────────────────────── */
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

        const mostrarPaginaOriginal = window.mostrarPagina;
        window.mostrarPagina = function (slug) {
            mostrarPaginaOriginal(slug);
            if (slug === 'bitacora') cargarBitacora();
        };

        /* ── Filtrar usuarios ───────────────────────────────────── */
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

        /* ── Restablecer contraseña ─────────────────────────────── */
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

        /* ── Crear usuario ──────────────────────────────────────── */
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

        /* ── Asignar roles (varios) ─────────────────────────────── */
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

        /* ── Editar usuario ─────────────────────────────────────── */
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

        /* ── Cambiar estado ─────────────────────────────────────── */
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