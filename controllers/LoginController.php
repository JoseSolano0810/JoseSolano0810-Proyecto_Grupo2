<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Usuario.php';
require_once ROOT_PATH . '/services/AuthService.php';

class LoginController
{
    private Usuario $model;

    public function __construct()
    {
        $this->model = new Usuario();
    }

    /**
     * vista  login
     */
    public function mostrar(): void
    {
            /**
            * sesión activa/ redirige * rol
            */
        $usuario = AuthService::usuarioActual();
        if ($usuario) {
            $this->redirigirPorRol($usuario['rol']);
        }

        $error = $_GET['error'] ?? null;
        $mensajes = [
            'acceso'       => 'No tiene permisos para acceder a ese módulo',
            'credenciales' => 'Usuario o contraseña incorrectos',
            'campos'       => 'Complete todos los campos',
            'inactivo'     => 'Su cuenta está inactiva, contacte al administrador',
        ];
        $error = isset($mensajes[$error]) ? $mensajes[$error] : null;

        require_once ROOT_PATH . '/views/login/index.php';
    }

    /**
     * form login
     */
    public function autenticar(): void
    {
        $nombre    = trim($_POST['nombre']    ?? '');
        $contrasena = trim($_POST['contrasena'] ?? '');
    
            
        /**
        * valida campos
        */
        if (empty($nombre) || empty($contrasena)) {
            header('Location: ' . BASE_URL . '/index.php?error=campos');
            exit;
        }

            /**
             * usuario en db
            */
        $usuario = $this->model->buscarPorNombreUsuario($nombre);

            /**
             * contraseña correcta
            */
        if (!$usuario || !password_verify($contrasena, $usuario['contrasena_hash'])) {
            header('Location: ' . BASE_URL . '/index.php?error=credenciales');
            exit;
        }

            /**
            * valida estado
            */
        if ($usuario['estado'] !== 'activo') {
            header('Location: ' . BASE_URL . '/index.php?error=inactivo');
            exit;
        }

            /**
            * iniciar sesión
            */
        AuthService::login($usuario);

            /**
            * Redirige * rol
            */
        $this->redirigirPorRol($usuario['rol']);
    }

    /**
     * cerrar sesion
     */
    public function logout(): void
    {
        AuthService::logout();
    }

    /**
     * dashboard * rol
     */
    private function redirigirPorRol(string $rol): void
    {
        $rutas = [
            'administrador'  => BASE_URL . '/index.php?accion=demo&rol=odontologo',
            'odontologo'     => BASE_URL . '/index.php?accion=demo&rol=odontologo',
            'recepcionista'  => BASE_URL . '/index.php?accion=demo&rol=recepcionista',
            'asistente_dental' => BASE_URL . '/index.php?accion=demo&rol=recepcionista',
            'paciente'       => BASE_URL . '/index.php?accion=demo&rol=paciente',
        ];

        $destino = $rutas[$rol] ?? BASE_URL . '/index.php';
        header('Location: ' . $destino);
        exit;
    }
}