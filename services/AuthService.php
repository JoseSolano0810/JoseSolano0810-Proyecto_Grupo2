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
            self::responderNoAutorizado(
                'Sesión no iniciada',
                'sesion'
            );
        }

        self::refrescarSesionDesdeBaseDatos();

        if (!in_array($_SESSION['usuario']['rol'], $rolesPermitidos, true)) {
            self::responderNoAutorizado(
                'No cuenta con los permisos necesarios.',
                'acceso'
            );
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

        if (!isset($_SESSION['usuario'])) {
            return null;
        }

        self::refrescarSesionDesdeBaseDatos();

        return $_SESSION['usuario'];
    }


    /**
     * Actualiza el rol y estado de la sesión desde la base de datos.
     */
    private static function refrescarSesionDesdeBaseDatos(): void
    {
        $idUsuario = (int) ($_SESSION['usuario']['id'] ?? 0);

        if (!$idUsuario) {
            self::responderNoAutorizado(
                'Sesión inválida',
                'sesion'
            );
        }

        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare(
            "SELECT u.nombre_completo,
                u.nombre_usuario,
                u.estado,
                r.nombre AS rol
         FROM usuario u
         JOIN rol r ON r.id_rol = u.id_rol
         WHERE u.id_usuario = ?
         LIMIT 1"
        );

        $stmt->execute([$idUsuario]);
        $usuario = $stmt->fetch();

        if (!$usuario || $usuario['estado'] !== 'activo') {
            self::logout();
        }

        $_SESSION['usuario']['nombre'] = $usuario['nombre_completo'];
        $_SESSION['usuario']['iniciales'] = self::iniciales(
            $usuario['nombre_completo']
        );
        $_SESSION['usuario']['usuario'] = $usuario['nombre_usuario'];
        $_SESSION['usuario']['rol'] = $usuario['rol'];
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