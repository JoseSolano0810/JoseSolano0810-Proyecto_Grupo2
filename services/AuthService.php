<?php

class AuthService
{
    /**
     * @param string|array $rolesPermitidos 
     */
    public static function requerir(string|array $rolesPermitidos): void
    {
        // if (session_status() === PHP_SESSION_NONE) {
        //     session_name(SESSION_NAME);
        //     session_start();
        // }

        // if (!isset($_SESSION['usuario'])) {
        //     header('Location: ' . BASE_URL . '/index.php');
        //     exit;
        // }

        // $rol = $_SESSION['usuario']['rol'];
        // $permitidos = (array) $rolesPermitidos;

        // if (!in_array($rol, $permitidos, true)) {
        //     header('Location: ' . BASE_URL . '/index.php?error=acceso');
        //     exit;
        // }
    }

    /**
     * @param array $usuario  
     */
    public static function iniciarSesion(array $usuario): void
    {
        // session_name(SESSION_NAME);
        // session_start();
        // session_regenerate_id(true); 
        // $_SESSION['usuario'] = [
        //     'id'        => $usuario['id'],
        //     'nombre'    => $usuario['nombre'],
        //     'iniciales' => strtoupper(substr($usuario['nombre'], 0, 1) .
        //                   substr(strrchr($usuario['nombre'], ' '), 1, 1)),
        //     'rol'       => $usuario['rol'],
        // ];
    }

    public static function cerrarSesion(): void
    {
        // session_name(SESSION_NAME);
        // session_start();
        // session_unset();
        // session_destroy();
        // header('Location: ' . BASE_URL . '/index.php');
        // exit;
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    public static function usuarioActual(): array
    {
        // return $_SESSION['usuario'] ?? [];
        return [];
    }
}
