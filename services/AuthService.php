<?php

class AuthService
{
    /** Iniciar sesión  */
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }

    /** 
     * Verifica que el usuario 
     */
    public static function requerir(array $rolesPermitidos): void
    {
        self::iniciar();

        if (!isset($_SESSION['usuario'])) {
            self::responderNoAutorizado('Sesión no iniciada');
        }

        if (!in_array($_SESSION['usuario']['rol'], $rolesPermitidos, true)) {
            self::responderNoAutorizado('No tiene permisos para acceder a este módulo');
        }
    }

    /** login exitoso */
    public static function login(array $usuario): void
    {
        self::iniciar();
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id'       => $usuario['id_usuario'],
            'nombre'   => $usuario['nombre_completo'],
            'iniciales'=> strtoupper(
                            substr($usuario['nombre_completo'], 0, 1) .
                            substr(strrchr($usuario['nombre_completo'], ' '), 1, 1)
                          ),
            'usuario'  => $usuario['nombre_usuario'],
            'rol'      => $usuario['rol'],
        ];
    }

    /** Cerrar sesión */
    public static function logout(): void
    {
        self::iniciar();
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    public static function usuarioActual(): ?array
    {
        self::iniciar();
        return $_SESSION['usuario'] ?? null;
    }

    private static function responderNoAutorizado(string $mensaje): void
    {
        $esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
                  str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        if ($esAjax) {
            http_response_code(401);
            echo json_encode(['error' => $mensaje]);
            exit;
        }

        header('Location: ' . BASE_URL . '/index.php?error=acceso');
        exit;
    }
}