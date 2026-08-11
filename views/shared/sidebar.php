<?php
?>
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
                <div class="sidebar-perfil-rol"><?= ucfirst($rol) ?></div>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">

        <?php if ($rol === 'odontologo'): ?>

            <div class="sidebar-seccion-titulo">Principal</div>
            <a class="sidebar-enlace <?= $pagina_activa === 'inicio'       ? 'activo' : '' ?>" onclick="mostrarPagina('inicio')">
                <i class="bi bi-speedometer2"></i> Inicio
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'agenda'       ? 'activo' : '' ?>" onclick="mostrarPagina('agenda')">
                <i class="bi bi-calendar3"></i> Mi agenda
            </a>
            <div class="sidebar-seccion-titulo">Clínico</div>
            <a class="sidebar-enlace <?= $pagina_activa === 'expedientes'  ? 'activo' : '' ?>" onclick="mostrarPagina('expedientes')">
                <i class="bi bi-folder2-open"></i> Expedientes
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'odontograma'  ? 'activo' : '' ?>" onclick="mostrarPagina('odontograma')">
                <i class="bi bi-clipboard2-pulse"></i> Odontograma
            </a>
            <a class="sidebar-enlace <?= $pagina_activa === 'tratamientos' ? 'activo' : '' ?>" onclick="mostrarPagina('tratamientos')">
                <i class="bi bi-bandaid"></i> Tratamientos
            </a>

        <?php elseif ($rol === 'recepcionista'): ?>

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
        <a class="sidebar-enlace" href="<?= BASE_URL ?>/index.php?accion=logout">
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
        </a>
    </div>

</aside>
