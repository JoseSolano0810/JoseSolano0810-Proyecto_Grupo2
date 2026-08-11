<?php
require_once ROOT_PATH . '/models/Usuario.php';
require_once ROOT_PATH . '/services/AuthService.php';

class LoginController
{
    /** Muestra la vista del login */
    public function mostrar(): void
    {
        // 
        // $usuario = AuthService::usuarioActual();
        // if (!empty($usuario)) {
        //     $this->redirigirPorRol($usuario['rol']);
        // }

        $error = $_GET['error'] ?? null;
        require_once ROOT_PATH . '/views/login/index.php';
    }

    /** Procesa el formulario POST del login */
    public function autenticar(): void
    {
        // Sanitizar entradas
        $nombre    = trim($_POST['nombre']    ?? '');
        $contrasena = trim($_POST['contrasena'] ?? '');

        if (empty($nombre) || empty($contrasena)) {
            header('Location: ' . BASE_URL . '/index.php?error=campos');
            exit;
        }

        // $usuarioModel = new Usuario();
        // $usuario = $usuarioModel->buscarPorCredenciales($nombre, $contrasena);

        // if (!$usuario) {
        //     header('Location: ' . BASE_URL . '/index.php?error=credenciales');
        //     exit;
        // }

        // AuthService::iniciarSesion($usuario);
        // $this->redirigirPorRol($usuario['rol']);

        //  Demo sin BD 
        header('Location: ' . BASE_URL . '/index.php?error=demo');
        exit;
    }

    /** Redirige al dashboard correcto según el rol del usuario */
    private function redirigirPorRol(string $rol): void
    {
        $rutas = [
            'admin'         => BASE_URL . '/controllers/AdminController.php',
            'odontologo'    => BASE_URL . '/views/odontologo/index.php',
            'recepcionista' => BASE_URL . '/views/recepcionista/index.php',
            'asistente'     => BASE_URL . '/views/asistente/index.php',
            'paciente'      => BASE_URL . '/views/paciente/index.php',
        ];

        $destino = $rutas[$rol] ?? BASE_URL . '/index.php';
        header('Location: ' . $destino);
        exit;
    }
}
