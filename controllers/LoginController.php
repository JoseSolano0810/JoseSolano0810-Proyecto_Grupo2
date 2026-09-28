<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Usuario.php';
require_once ROOT_PATH . '/services/AuthService.php';

class LoginController
{
    private ?Usuario $model = null;

    private function model(): Usuario
    {
        return $this->model ??= new Usuario();
    }

    /**
     * Vista login
     */
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
        ];
        $error = $mensajes[$codigoError] ?? null;

        require_once ROOT_PATH . '/views/login/index.php';
    }

    /**
     * Form login 
     */
    public function autenticar(): void
    {
        $nombre     = trim($_POST['nombre']     ?? '');
        $contrasena = trim($_POST['contrasena'] ?? '');

        if ($nombre === '' || $contrasena === '') {
            header('Location: ' . BASE_URL . '/index.php?error=campos');
            exit;
        }

        $usuario = $this->model()->buscarPorNombreUsuario($nombre);

        if (!$usuario || !password_verify($contrasena, $usuario['contrasena_hash'])) {
            header('Location: ' . BASE_URL . '/index.php?error=credenciales');
            exit;
        }

        if ($usuario['estado'] !== 'activo') {
            header('Location: ' . BASE_URL . '/index.php?error=inactivo');
            exit;
        }

        AuthService::login($usuario);
        $this->redirigirPorRol();
    }

    /**
     * Cerrar sesión
     */
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