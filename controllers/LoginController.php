<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Usuario.php';
require_once ROOT_PATH . '/services/AuthService.php';
require_once ROOT_PATH . '/models/Bitacora.php';

class LoginController
{
    private ?Usuario $model = null;

    private function model(): Usuario
    {
        return $this->model ??= new Usuario();
    }

    public function mostrar(): void
    {
        $usuario = AuthService::usuarioActual();
        if ($usuario) {
            $this->redirigirPorRol();
        }

        $codigoError = $_GET['error'] ?? null;
        $mensajes = [
            'acceso'       => 'No tiene permisos para acceder a ese módulo',
            'sesion'       => 'Su sesión expiró, inicie sesión nuevamente',
            'credenciales' => 'Usuario o contraseña incorrectos',
            'campos'       => 'Complete todos los campos',
            'inactivo'     => 'Su cuenta está inactiva, contacte al administrador',
            'inactividad'  => 'Su sesión se cerró por inactividad, ingrese nuevamente',
        ];
        $error = $mensajes[$codigoError] ?? null;

        require_once ROOT_PATH . '/views/login/index.php';
    }

    public function autenticar(): void
    {
        $nombre     = (string) ($_POST['nombre']     ?? '');
        $contrasena = (string) ($_POST['contrasena'] ?? '');

        // USU-03 esc. 3: limpia caracteres de control y valida formato
        $limpio = static fn(string $t): string => trim(preg_replace('/[\x00-\x1F\x7F]/', '', $t));
        $nombreLimpio     = $limpio($nombre);
        $contrasenaLimpia = $limpio($contrasena);

        if ($nombreLimpio === '' || $contrasenaLimpia === '') {
            header('Location: ' . BASE_URL . '/index.php?error=campos');
            exit;
        }

        $sospechoso = $nombreLimpio !== trim($nombre)
            || $contrasenaLimpia !== trim($contrasena)
            || mb_strlen($nombreLimpio) > 50
            || mb_strlen($contrasenaLimpia) > 100
            || !preg_match('/^[A-Za-z0-9._@\-]+$/', $nombreLimpio);

        if ($sospechoso) {
            Bitacora::registrar('LOGIN_SOSPECHOSO', 'Intento de inicio de sesión con datos sospechosos rechazado', null, 'Desconocido');
            header('Location: ' . BASE_URL . '/index.php?error=credenciales');
            exit;
        }

        $usuario = $this->model()->buscarPorNombreUsuario($nombreLimpio);

        // USU-03 esc. 2: mensaje genérico
        if (!$usuario || !password_verify($contrasenaLimpia, $usuario['contrasena_hash'])) {
            Bitacora::registrar('LOGIN_FALLIDO', 'Intento de inicio de sesión fallido', $usuario ? (int) $usuario['id_usuario'] : null, $nombreLimpio);
            header('Location: ' . BASE_URL . '/index.php?error=credenciales');
            exit;
        }

        if ($usuario['estado'] !== 'activo') {
            Bitacora::registrar('LOGIN_FALLIDO', 'Intento de acceso con cuenta inactiva', (int) $usuario['id_usuario'], $nombreLimpio);
            header('Location: ' . BASE_URL . '/index.php?error=inactivo');
            exit;
        }

        AuthService::login($usuario);
        $this->redirigirPorRol();
    }

    /** USU-05 esc. 1 y 2: el usuario cambia su propia contraseña */
    public function cambiarContrasena(): void
    {
        AuthService::requerir(AuthService::ROLES);

        $datos     = json_decode(file_get_contents('php://input'), true) ?? [];
        $actual    = (string) ($datos['actual']    ?? '');
        $nueva     = (string) ($datos['nueva']     ?? '');
        $confirmar = (string) ($datos['confirmar'] ?? '');

        $fallo = function (string $msg, int $codigo = 400): void {
            http_response_code($codigo);
            echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
        };

        if ($actual === '' || $nueva === '' || $confirmar === '') {
            $fallo('Complete todos los campos');
            return;
        }

        $idUsuario = (int) $_SESSION['usuario']['id'];
        $registro  = $this->model()->obtenerPorId($idUsuario);

        if (!$registro || !password_verify($actual, $registro['contrasena_hash'])) {
            $fallo('La contraseña actual es incorrecta');
            return;
        }
        if ($nueva !== $confirmar) {
            $fallo('La confirmación no coincide con la nueva contraseña');
            return;
        }
        if ($error = AuthService::validarPoliticaContrasena($nueva)) {
            $fallo($error);
            return;
        }
        if (password_verify($nueva, $registro['contrasena_hash'])) {
            $fallo('La nueva contraseña debe ser distinta a la actual');
            return;
        }

        $this->model()->restablecerContrasena($idUsuario, $nueva);
        Bitacora::registrar('CONTRASENA_CAMBIADA', 'El usuario cambió su propia contraseña');

        echo json_encode(['ok' => true, 'mensaje' => 'Contraseña actualizada correctamente'], JSON_UNESCAPED_UNICODE);
    }

    public function logout(): void
    {
        AuthService::logout();
    }

    private function redirigirPorRol(): void
    {
        header('Location: ' . BASE_URL . '/index.php?accion=panel');
        exit;
    }
}