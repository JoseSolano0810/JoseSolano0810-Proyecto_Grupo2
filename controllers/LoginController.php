<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Usuario.php';
require_once ROOT_PATH . '/services/AuthService.php';

class LoginController
{

    public function mostrar(): void
    {

        $error = $_GET['error'] ?? null;
        require_once ROOT_PATH . '/views/login/index.php';
    }

    public function autenticar(): void
    {

        $nombre    = trim($_POST['nombre']    ?? '');
        $contrasena = trim($_POST['contrasena'] ?? '');

        if (empty($nombre) || empty($contrasena)) {
            header('Location: ' . BASE_URL . '/index.php?error=campos');
            exit;
        }

        header('Location: ' . BASE_URL . '/index.php?error=demo');
        exit;
    }

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
