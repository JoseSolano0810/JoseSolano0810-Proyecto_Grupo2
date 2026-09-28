<?php

class AuthService
{
    /**
     * Todos los roles del sistema 
    */
    public const ROLES = ['administrador', 'odontologo', 'recepcionista', 'asistente_dental', 'paciente'];

    /** Iniciar sesión PHP */
    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }
    }

    /** 
     * Verifica sesión y rol permitido 
     */
    public static function requerir(array $rolesPermitidos): void
    {
        self::iniciar();

        if (!isset($_SESSION['usuario'])) {
            self::responderNoAutorizado('Sesión no iniciada', 'sesion');
        }

        if (!in_array($_SESSION['usuario']['rol'], $rolesPermitidos, true)) {
            self::responderNoAutorizado('No tiene permisos para acceder a este módulo', 'acceso');
        }
    }

    /** 
     * Login exitoso 
     */
    public static function login(array $usuario): void
    {
        self::iniciar();
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id'        => (int) $usuario['id_usuario'],
            'nombre'    => $usuario['nombre_completo'],
            'iniciales' => self::iniciales($usuario['nombre_completo']),
            'usuario'   => $usuario['nombre_usuario'],
            'rol'       => $usuario['rol'],
        ];
    }

    /** 
     * Cerrar sesión 
    */
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

  
    public static function iniciales(string $nombre): string
    {
        $partes = preg_split('/\s+/', trim($nombre)) ?: [''];
        $ini    = mb_substr($partes[0], 0, 1);
        if (count($partes) > 1) {
            $ini .= mb_substr(end($partes), 0, 1);
        }
        return mb_strtoupper($ini);
    }

    private static function responderNoAutorizado(string $mensaje, string $codigo = 'acceso'): void
    {
        $esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
                  str_contains($_SERVER['HTTP_ACCEPT']      ?? '', 'application/json') ||
                  str_contains($_SERVER['CONTENT_TYPE']     ?? '', 'application/json');

        if ($esAjax) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => $mensaje], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: ' . BASE_URL . '/index.php?error=' . $codigo);
        exit;
    }
}