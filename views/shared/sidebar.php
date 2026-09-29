<?php ?>
<aside class="sidebar" id="sidebar">

    <div class="sidebar-logo">
        <div class="sidebar-logo-icono"><i class="bi bi-heart-pulse"></i></div>
        <span class="sidebar-logo-texto">Odent</span>
    </div>

    <div class="sidebar-perfil">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="sidebar-perfil-avatar">
                <?= htmlspecialchars($usuario['iniciales']) ?>
            </div>
            <div class="sidebar-perfil-info">
                <div class="sidebar-perfil-nombre"><?= htmlspecialchars($usuario['nombre']) ?></div>
                <div class="sidebar-perfil-rol"><?= ucfirst(str_replace('_', ' ', $rol)) ?></div>
            </div>
        </div>

        <?php if (count($roles_sesion ?? []) > 1 && !in_array('administrador', $roles_sesion, true)): ?>
            <select class="sidebar-select-rol" style="margin-top:10px;width:100%;"
                onchange="location.href='<?= BASE_URL ?>/index.php?accion=cambiar_rol&rol=' + encodeURIComponent(this.value)">
                <?php foreach ($roles_sesion as $r): ?>
                    <option value="<?= htmlspecialchars($r) ?>" <?= $r === $rol ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', $r)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>

    <nav class="sidebar-nav">

        <?php if ($rol === 'odontologo' || $rol === 'administrador'): ?>

            <a class="sidebar-enlace <?= $pagina_activa === 'inicio'       ? 'activo' : '' ?>" onclick="mostrarPagina('inicio')">
                <i class="bi bi-speedometer2"></i> Inicio
            </a>
            <?php if ($rol === 'administrador'): ?>
            <a class="sidebar-enlace <?= $pagina_activa === 'usuarios'     ? 'activo' : '' ?>" onclick="mostrarPagina('usuarios')">
                <i class="bi bi-people"></i> Usuarios
            </a>
            <?php endif; ?>
            <a class="sidebar-enlace <?= $pagina_activa === 'agenda'       ? 'activo' : '' ?>" onclick="mostrarPagina('agenda')">
                <i class="bi bi-calendar3"></i> Agenda
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'pacientes'    ? 'activo' : '' ?>" onclick="mostrarPagina('pacientes')">
                <i class="bi bi-person-vcard"></i> Pacientes
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'expedientes'  ? 'activo' : '' ?>" onclick="mostrarPagina('expedientes')">
                <i class="bi bi-folder2-open"></i> Expedientes
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'odontograma'  ? 'activo' : '' ?>" onclick="mostrarPagina('odontograma')">
                <i class="bi bi-clipboard2-pulse"></i> Odontograma
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'tratamientos' ? 'activo' : '' ?>" onclick="mostrarPagina('tratamientos')">
                <i class="bi bi-bandaid"></i> Tratamientos
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'cotizaciones' ? 'activo' : '' ?>" onclick="mostrarPagina('cotizaciones')">
                <i class="bi bi-receipt"></i> Cotizaciones
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'pagos'        ? 'activo' : '' ?>" onclick="mostrarPagina('pagos')">
                <i class="bi bi-cash-coin"></i> Pagos
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'inventario'   ? 'activo' : '' ?>" onclick="mostrarPagina('inventario')">
                <i class="bi bi-box-seam"></i> Inventario
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'reportes'     ? 'activo' : '' ?>" onclick="mostrarPagina('reportes')">
                <i class="bi bi-bar-chart-line"></i> Reportes
            </a>
            <?php if ($rol === 'administrador'): ?>
            <a class="sidebar-enlace <?= $pagina_activa === 'bitacora'     ? 'activo' : '' ?>" onclick="mostrarPagina('bitacora')">
                <i class="bi bi-journal-text"></i> Bitácora
            </a>
            <?php endif; ?>

        <?php elseif ($rol === 'recepcionista' || $rol === 'asistente_dental'): ?>

            <div class="sidebar-seccion-titulo">Principal</div>
            <a class="sidebar-enlace <?= $pagina_activa === 'inicio'       ? 'activo' : '' ?>" onclick="mostrarPagina('inicio')">
                <i class="bi bi-speedometer2"></i> Inicio
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'agenda'       ? 'activo' : '' ?>" onclick="mostrarPagina('agenda')">
                <i class="bi bi-calendar2-week"></i> Agenda general
            </a>
            <div class="sidebar-seccion-titulo">Pacientes</div>
            <a class="sidebar-enlace <?= $pagina_activa === 'pacientes'    ? 'activo' : '' ?>" onclick="mostrarPagina('pacientes')">
                <i class="bi bi-people"></i> Pacientes
            </a>
            <div class="sidebar-seccion-titulo">Finanzas</div>
            <a class="sidebar-enlace <?= $pagina_activa === 'cotizaciones' ? 'activo' : '' ?>" onclick="mostrarPagina('cotizaciones')">
                <i class="bi bi-receipt"></i> Cotizaciones
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'pagos'        ? 'activo' : '' ?>" onclick="mostrarPagina('pagos')">
                <i class="bi bi-cash-coin"></i> Pagos
            </a>

        <?php elseif ($rol === 'paciente'): ?>

            <div class="sidebar-seccion-titulo">Mi portal</div>
            <a class="sidebar-enlace <?= $pagina_activa === 'inicio'       ? 'activo' : '' ?>" onclick="mostrarPagina('inicio')">
                <i class="bi bi-house"></i> Inicio
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'citas'        ? 'activo' : '' ?>" onclick="mostrarPagina('citas')">
                <i class="bi bi-calendar-check"></i> Mis citas
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'tratamientos' ? 'activo' : '' ?>" onclick="mostrarPagina('tratamientos')">
                <i class="bi bi-bandaid"></i> Mis tratamientos
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'pagos'        ? 'activo' : '' ?>" onclick="mostrarPagina('pagos')">
                <i class="bi bi-wallet2"></i> Mis pagos
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'asistente'    ? 'activo' : '' ?>" onclick="mostrarPagina('asistente')">
                <i class="bi bi-robot"></i> Asistente de triaje
            </a>

        <?php endif; ?>

    </nav>

    <div class="sidebar-pie">
        <a class="sidebar-enlace" onclick="abrirCambiarContrasena()" style="cursor:pointer;">
            <i class="bi bi-key"></i> Cambiar contraseña
        </a>
        <a class="sidebar-enlace" href="<?= BASE_URL ?>/index.php?accion=logout">
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
        </a>
    </div>

</aside>

<!-- USU-05: modal cambiar mi contraseña -->
<div class="modal-overlay" id="modal-cambiar-contrasena">
    <div class="modal-caja" style="max-width:420px;">
        <div class="modal-header">
            <h3>Cambiar mi contraseña</h3>
            <button class="modal-cerrar" onclick="cerrarModal('modal-cambiar-contrasena')"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="campo-grupo"><label>Contraseña actual *</label>
            <div class="input-icono"><i class="bi bi-lock"></i>
                <input type="password" id="cc-actual" autocomplete="current-password">
            </div>
        </div>
        <div class="campo-grupo"><label>Nueva contraseña *</label>
            <div class="input-icono"><i class="bi bi-key"></i>
                <input type="password" id="cc-nueva" autocomplete="new-password"
                       placeholder="Mín. 8 caracteres, mayúscula, minúscula y número">
            </div>
        </div>
        <div class="campo-grupo"><label>Confirmar nueva contraseña *</label>
            <div class="input-icono"><i class="bi bi-lock-fill"></i>
                <input type="password" id="cc-confirmar" autocomplete="new-password">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-outline-odent" onclick="cerrarModal('modal-cambiar-contrasena')">Cancelar</button>
            <button class="btn-odent" onclick="guardarCambioContrasena()"><i class="bi bi-check-lg"></i> Guardar cambios</button>
        </div>
    </div>
</div>

<script>
    window.ODENT = {
        base: '<?= BASE_URL ?>',
        minutosInactividad: <?= (int) AuthService::MINUTOS_INACTIVIDAD ?>
    };
</script>