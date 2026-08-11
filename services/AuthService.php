<?php

class AuthService
{

    public static function requerir(string|array $rolesPermitidos): void
    {

    }

    public static function iniciarSesion(array $usuario): void
    {

    }

    public static function cerrarSesion(): void
    {

        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    public static function usuarioActual(): array
    {

        return [];
    }
}
