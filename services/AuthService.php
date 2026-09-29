<?php

require_once ROOT_PATH . '/models/Bitacora.php';

class AuthService
{
    /** USU-04 esc. 2: minutos de inactividad permitidos */
    public const MINUTOS_INACTIVIDAD = 30;

    public const ROLES = ['administrador', 'odontologo', 'recepcionista', 'asistente_dental', 'paciente'];

    public static function iniciar(): void
    {
        // USU-04 esc. 3: evita caché de páginas privadas (botón "Atrás")
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
            session_start();
        }
    }

    /** USU-04 esc. 2: true si la sesión expiró por inactividad */
    private static function verificarInactividad(): bool
    {
        $ahora  = time();
        $ultima = $_SESSION['ultima_actividad'] ?? $ahora;

        if (($ahora - $ultima) > self::MINUTOS_INACTIVIDAD * 60) {
            Bitacora::registrar(
                'SESION_EXPIRADA',
                'Sesión cerrada automáticamente por inactividad',
                (int) ($_SESSION['usuario']['id'] ?? 0) ?: null,
                $_SESSION['usuario']['usuario'] ?? null
            );
            session_unset();
            session_destroy();
            return true;
        }

        $_SESSION['ultima_actividad'] = $ahora;
        return false;
    }

    public static function requerir(array $rolesPermitidos): void
    {
        self::iniciar();

        if (!isset($_SESSION['usuario'])) {
            self::responderNoAutorizado('Sesión no iniciada', 'sesion');
        }

        if (self::verificarInactividad()) {
            self::responderNoAutorizado('Sesión expirada por inactividad', 'inactividad');
        }

        self::refrescarSesionDesdeBaseDatos();

        $rolesUsuario = $_SESSION['usuario']['roles'] ?? [$_SESSION['usuario']['rol']];
        if (!array_intersect($rolesUsuario, $rolesPermitidos)) {
            Bitacora::registrar(
                'ACCESO_DENEGADO',
                'Intento de acceso sin permisos a: ' . mb_substr($_GET['accion'] ?? 'desconocido', 0, 60)
            );
            self::responderNoAutorizado('No cuenta con los permisos necesarios.', 'acceso');
        }
    }

    public static function login(array $usuario): void
    {
        self::iniciar();
        session_regenerate_id(true);
        $roles = self::rolesDeUsuario((int) $usuario['id_usuario'], $usuario['rol']);

        $_SESSION['usuario'] = [
            'id'            => (int) $usuario['id_usuario'],
            'nombre'        => $usuario['nombre_completo'],
            'iniciales'     => self::iniciales($usuario['nombre_completo']),
            'usuario'       => $usuario['nombre_usuario'],
            'rol'           => self::rolActivoPorDefecto($roles, $usuario['rol']),
            'rol_principal' => $usuario['rol'],
            'roles'         => $roles,
        ];
        $_SESSION['ultima_actividad'] = time();

        Bitacora::registrar('LOGIN_EXITOSO', 'Inicio de sesión exitoso', (int) $usuario['id_usuario'], $usuario['nombre_usuario']);
    }

    public static function cambiarRolActivo(string $rol): bool
    {
        self::iniciar();
        $roles = $_SESSION['usuario']['roles'] ?? [];

        if (in_array('administrador', $roles, true)) {
            return false;
        }
        if (!in_array($rol, $roles, true)) {
            return false;
        }
        $_SESSION['usuario']['rol'] = $rol;
        return true;
    }

    private static function rolActivoPorDefecto(array $roles, string $rolPrincipal): string
    {
        return in_array('administrador', $roles, true) ? 'administrador' : $rolPrincipal;
    }

    private static function rolesDeUsuario(int $idUsuario, string $rolPrincipal): array
    {
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT r.nombre
             FROM usuario_rol ur
             JOIN rol r ON r.id_rol = ur.id_rol
             WHERE ur.id_usuario = ?
             ORDER BY ur.es_principal DESC, r.id_rol ASC"
        );
        $stmt->execute([$idUsuario]);
        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $roles ?: [$rolPrincipal];
    }

    public static function logout(string $motivo = ''): void
    {
        self::iniciar();
        if (isset($_SESSION['usuario'])) {
            if ($motivo === 'inactividad') {
                Bitacora::registrar('SESION_EXPIRADA', 'Sesión cerrada automáticamente por inactividad');
            } else {
                Bitacora::registrar('LOGOUT', 'Cierre de sesión voluntario');
            }
        }
        session_unset();
        session_destroy();
        if (ini_get('session.use_cookies')) {
            $c = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $c['path'], $c['domain'], $c['secure'], $c['httponly']);
        }
        header('Location: ' . BASE_URL . '/index.php' . ($motivo === 'inactividad' ? '?error=inactividad' : ''));
        exit;
    }

    /** USU-05 esc. 2: política de contraseñas. Devuelve mensaje de error o null */
    public static function validarPoliticaContrasena(string $c): ?string
    {
        if (strlen($c) < 8 || !preg_match('/[A-Z]/', $c) || !preg_match('/[a-z]/', $c) || !preg_match('/\d/', $c)) {
            return 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número';
        }
        return null;
    }

    public static function usuarioActual(): ?array
    {
        self::iniciar();

        if (!isset($_SESSION['usuario'])) {
            return null;
        }
        if (self::verificarInactividad()) {
            return null;
        }

        self::refrescarSesionDesdeBaseDatos();

        return $_SESSION['usuario'];
    }

    private static function refrescarSesionDesdeBaseDatos(): void
    {
        $idUsuario = (int) ($_SESSION['usuario']['id'] ?? 0);

        if (!$idUsuario) {
            self::responderNoAutorizado('Sesión inválida', 'sesion');
        }

        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare(
            "SELECT u.nombre_completo, u.nombre_usuario, u.estado, r.nombre AS rol
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

        $_SESSION['usuario']['nombre']    = $usuario['nombre_completo'];
        $_SESSION['usuario']['iniciales'] = self::iniciales($usuario['nombre_completo']);
        $_SESSION['usuario']['usuario']   = $usuario['nombre_usuario'];

        $roles = self::rolesDeUsuario($idUsuario, $usuario['rol']);
        $_SESSION['usuario']['rol_principal'] = $usuario['rol'];
        $_SESSION['usuario']['roles']         = $roles;

        if (in_array('administrador', $roles, true)) {
            $_SESSION['usuario']['rol'] = 'administrador';
        } elseif (!in_array($_SESSION['usuario']['rol'] ?? '', $roles, true)) {
            $_SESSION['usuario']['rol'] = $usuario['rol'];
        }
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
                  str_contains($_SERVER['HTTP_ACCEPT']  ?? '', 'application/json') ||
                  str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

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