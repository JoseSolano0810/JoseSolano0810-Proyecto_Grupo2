<?php

require_once ROOT_PATH . '/models/Notificacion.php';
require_once ROOT_PATH . '/services/AuthService.php';

/**
 * NotificacionController.php — PAC-08
 * Campana del portal del paciente + apertura de una notificación (portal o enlace del correo).
 */
class NotificacionController
{
    private Notificacion $model;

    public function __construct()
    {
        AuthService::requerir(['paciente']);
        $this->model = new Notificacion();
    }

    private function idUsuario(): int
    {
        return (int) (AuthService::usuarioActual()['id'] ?? 0);
    }

    private function responder(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function fechaRelativa(string $f): string
    {
        $t = strtotime($f);
        if (!$t) return '';
        $dia = date('Y-m-d', $t);
        if ($dia === date('Y-m-d'))                    return 'Hoy ' . date('H:i', $t);
        if ($dia === date('Y-m-d', strtotime('-1 day'))) return 'Ayer ' . date('H:i', $t);
        return date('d/m/Y', $t);
    }

    /** Datos que usa la vista para pintar la campana (sin pasar por HTTP) */
    public function datosCampana(): array
    {
        try {
            return $this->formatear();
        } catch (Throwable $e) {
            error_log('PAC-08 campana: ' . $e->getMessage());
            return ['items' => [], 'no_leidas' => 0];
        }
    }

    private function formatear(): array
    {
        $items = array_map(fn($n) => [
            'id'             => (int) $n['id_notificacion'],
            'tipo'           => $n['tipo'],
            'titulo'         => $n['titulo'],
            'mensaje'        => $n['mensaje'],
            'id_tratamiento' => $n['id_tratamiento'] !== null ? (int) $n['id_tratamiento'] : null,
            'leida'          => (bool) $n['leida'],
            'fecha'          => self::fechaRelativa($n['created_at']),
        ], $this->model->listar($this->idUsuario()));

        return ['items' => $items, 'no_leidas' => $this->model->contarNoLeidas($this->idUsuario())];
    }

    /** GET: notificaciones.listar */
    public function listar(): void
    {
        $this->responder(['ok' => true] + $this->formatear());
    }

    /** POST: notificaciones.leer  { id } o { todas: true } */
    public function leer(): void
    {
        $d = json_decode(file_get_contents('php://input'), true);
        $d = is_array($d) ? $d : [];

        if (!empty($d['todas'])) {
            $this->model->marcarTodasLeidas($this->idUsuario());
        } else {
            $id = (int) ($d['id'] ?? 0);
            if (!$id) $this->responder(['ok' => false, 'error' => 'Falta la notificación.'], 400);
            $this->model->marcarLeida($id, $this->idUsuario());
        }
        $this->responder(['ok' => true, 'no_leidas' => $this->model->contarNoLeidas($this->idUsuario())]);
    }

    /**
     * PAC-08 esc. 2: enlace de la notificación (viene en el correo).
     */
    public static function abrir(): void
    {
        $id = (int) ($_GET['id'] ?? 0);

        $usuario = AuthService::usuarioActual();
        if (!$usuario) {
            header('Location: ' . BASE_URL . '/index.php?accion=login&notif=' . $id);
            exit;
        }
        if (($usuario['rol'] ?? '') !== 'paciente') {
            header('Location: ' . BASE_URL . '/index.php?accion=panel');
            exit;
        }

        $modelo = new Notificacion();
        $n      = $id ? $modelo->obtenerDeUsuario($id, (int) $usuario['id']) : null;

        // Solo se abre si es una notificación de este paciente; cualquier otra cosa va al inicio.
        if (!$n) {
            header('Location: ' . BASE_URL . '/index.php?accion=panel');
            exit;
        }

        $modelo->marcarLeida($id, (int) $usuario['id']);
        $dest = BASE_URL . '/index.php?accion=panel';
        if ($n['id_tratamiento'] !== null) $dest .= '&trat=' . (int) $n['id_tratamiento'];
        header('Location: ' . $dest . '#tratamientos');
        exit;
    }
}