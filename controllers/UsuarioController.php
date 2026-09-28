<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Usuario.php';
require_once ROOT_PATH . '/services/AuthService.php';

class UsuarioController
{
    private Usuario $model;

    public function __construct()
    {
        AuthService::requerir(['administrador']);
        $this->model = new Usuario();
    }

    /**
     * Lista usuarios
     */
    public function listar(): void
    {
        $usuarios = $this->model->obtenerTodos();
        echo json_encode(['ok' => true, 'data' => $usuarios]);
    }

    /**
     * Lista roles
     */
    public function roles(): void
    {
        $roles = $this->model->obtenerRoles();
        echo json_encode(['ok' => true, 'data' => $roles]);
    }

    /**
     * Registra usuario
     */
    public function crear(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true);

        $requeridos = ['nombre', 'cedula', 'correo', 'usuario', 'contrasena', 'rol_id'];
        foreach ($requeridos as $campo) {
            if (empty($datos[$campo])) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => "El campo $campo es requerido"]);
                return;
            }
        }

        if (strlen($datos['contrasena']) < 8) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres']);
            return;
        }

        if ($this->model->existeNombreUsuario($datos['usuario'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El nombre de usuario ya está en uso']);
            return;
        }

        if ($this->model->existeCorreo($datos['correo'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El correo ya está registrado']);
            return;
        }

        $id      = $this->model->crear($datos);
        $usuario = $this->model->obtenerPorId($id);

        echo json_encode([
            'ok'      => true,
            'mensaje' => 'Usuario registrado exitosamente',
            'data'    => $usuario
        ]);
    }

    /**
     * Restablece contraseña
     */
    public function restablecerContrasena(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true);
        $id    = (int) ($datos['id']        ?? 0);
        $nueva = trim($datos['contrasena']  ?? '');

        if (!$id || empty($nueva)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID y nueva contraseña son requeridos']);
            return;
        }

        if (strlen($nueva) < 8) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres']);
            return;
        }

        $usuario = $this->model->obtenerPorId($id);
        if (!$usuario) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado']);
            return;
        }

        $this->model->restablecerContrasena($id, $nueva);

        echo json_encode([
            'ok'      => true,
            'mensaje' => 'Contraseña restablecida exitosamente'
        ]);
    }

    /**
     * activo/inactivo
     */
    public function cambiarEstado(): void
    {
        $datos  = json_decode(file_get_contents('php://input'), true);
        $id     = (int) ($datos['id']     ?? 0);
        $estado = $datos['estado'] ?? '';

        if (!$id || !in_array($estado, ['activo', 'inactivo'], true)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
            return;
        }

        $this->model->cambiarEstado($id, $estado);

        echo json_encode([
            'ok'      => true,
            'mensaje' => "Usuario $estado exitosamente"
        ]);
    }
}