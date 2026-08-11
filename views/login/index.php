<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odent | Iniciar sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/estilos.css">
</head>
<body>

<div class="pagina-login">

    //  Panel izquierdo decorativo
    <div class="login-panel-izquierdo">
        <div class="login-logo">
            <div class="login-logo-icono"><i class="bi bi-heart-pulse"></i></div>
            <span class="login-logo-texto">Odent</span>
        </div>
        <h2 class="login-titulo-panel">Centro Odontológico<br>en un solo lugar</h2>
        <p class="login-subtitulo-panel">
            Gestión clínica, administrativa y financiera
            integrada para todo el equipo de la clínica.
        </p>
        <div class="login-caracteristicas">
            <div class="login-caracteristica">
                <i class="bi bi-calendar2-check"></i>
                <span>Agenda y citas en tiempo real</span>
            </div>
            <div class="login-caracteristica">
                <i class="bi bi-file-medical"></i>
                <span>Expedientes clínicos digitales</span>
            </div>
            <div class="login-caracteristica">
                <i class="bi bi-shield-check"></i>
                <span>Acceso controlado por rol</span>
            </div>
            <div class="login-caracteristica">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Reportes y bitácora de auditoría</span>
            </div>
        </div>
    </div>

    // Panel derecho
    <div class="login-panel-derecho">
        <div class="login-card">

            <div class="login-encabezado">
                <span class="etiqueta-odent">ODENT</span>
                <h1>Iniciar sesión</h1>
                <p>Ingrese sus credenciales para acceder al sistema</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alerta peligro" style="margin-bottom:16px;">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            // Formulario
            <form action="<?= BASE_URL ?>/index.php?accion=login" method="POST" id="form-login">

                <div class="campo-grupo">
                    <label for="nombre">Nombre de usuario</label>
                    <div class="input-icono">
                        <i class="bi bi-person"></i>
                        <input type="text" id="nombre" name="nombre"
                               placeholder="Ingrese su usuario" autocomplete="username">
                    </div>
                </div>

                <div class="campo-grupo">
                    <label for="contrasena">Contraseña</label>
                    <div class="input-icono">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="contrasena" name="contrasena"
                               placeholder="Ingrese su contraseña" autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="btn-login-submit">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Ingresar al sistema
                </button>

            </form>

            <div class="divisor-acceso">Acceso de demostración</div>

            <div class="acceso-rapido">
                <p>Seleccione un perfil para explorar la interfaz:</p>

                <a href="<?= BASE_URL ?>/index.php?accion=demo&rol=odontologo" class="btn-acceso odontologo">
                    <i class="bi bi-person-badge"></i>
                    <span>Odontólogo</span>
                    <span class="badge-demo">DEMO</span>
                    <i class="bi bi-chevron-right flecha"></i>
                </a>

                <a href="<?= BASE_URL ?>/index.php?accion=demo&rol=recepcionista" class="btn-acceso recepcionista">
                    <i class="bi bi-headset"></i>
                    <span>Recepcionista</span>
                    <span class="badge-demo">DEMO</span>
                    <i class="bi bi-chevron-right flecha"></i>
                </a>

                <a href="<?= BASE_URL ?>/index.php?accion=demo&rol=paciente" class="btn-acceso paciente">
                    <i class="bi bi-person-heart"></i>
                    <span>Paciente</span>
                    <span class="badge-demo">DEMO</span>
                    <i class="bi bi-chevron-right flecha"></i>
                </a>
            </div>

        </div>
    </div>

</div>

<script src="<?= BASE_URL ?>/public/js/app.js"></script>
</body>
</html>
