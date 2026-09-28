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

    <div class="login-panel-izquierdo">
        <img src="<?= BASE_URL ?>/public/img/Odent_Logo.jpg"
             alt="Odent Logo"
             style="width: 160px; height: 160px; border-radius: 24px; object-fit: cover; margin-bottom: 24px;">
        <h2 class="login-titulo-panel">Odent Centro Odontológico</h2>
        <p class="login-subtitulo-panel">Dra. Melissa Salguero Zárate</p>
    </div>

    <div class="login-panel-derecho">
        <div class="login-card">

            <div class="login-encabezado">
                <h1>Iniciar sesión</h1>
            </div>

            <?php if (isset($error)): ?>
                <div class="alerta peligro" style="margin-bottom:16px;">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

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
                    Iniciar sesión
                </button>

            </form>

        </div>
    </div>

</div>

<script src="<?= BASE_URL ?>/public/js/app.js"></script>
</body>
</html>