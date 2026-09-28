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
        echo json_encode(['ok' => true, 'data' => $this->model->obtenerTodos()]);
    }

    /**
     * Lista roles 
     */
    public function roles(): void
    {
        echo json_encode(['ok' => true, 'data' => $this->model->obtenerRoles()]);
    }

    /**
     * Datos para la vista del panel 
     */
    public function datosVista(): array
    {
            $usuarios = array_map(fn($u) => [
            'id'       => (int) $u['id_usuario'],
            'nombre'   => $u['nombre_completo'],
            'usuario'  => $u['nombre_usuario'],
            'cedula'   => $u['identificacion'],
            'telefono' => $u['telefono'] ?? '',
            'correo'   => $u['correo'],
            'rol'      => $u['rol'],
            'rol_id'   => (int) $u['id_rol'],
            'estado'   => $u['estado'],
        ], $this->model->obtenerTodos());

        $roles = array_map(fn($r) => [
            'id'     => (int) $r['id_rol'],
            'nombre' => $r['nombre'],
        ], $this->model->obtenerRoles());

        return ['usuarios' => $usuarios, 'roles' => $roles];
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

        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El correo no tiene un formato válido']);
            return;
        }

        if (strlen($datos['contrasena']) < 8) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres']);
            return;
        }

        if (!$this->model->existeRol((int) $datos['rol_id'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El rol seleccionado no existe']);
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

        if ($this->model->existeIdentificacion($datos['cedula'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La identificación ya está registrada']);
            return;
        }

        $datos['rol_id']   = (int) $datos['rol_id'];
        $datos['telefono'] = trim($datos['telefono'] ?? '');

        try {
            $id      = $this->model->crear($datos);
            $usuario = $this->model->obtenerPorId($id);
            unset($usuario['contrasena_hash']);

            echo json_encode([
                'ok'      => true,
                'mensaje' => 'Usuario registrado exitosamente',
                'data'    => $usuario,
            ]);
        } catch (PDOException $e) {
            error_log('USR-01 crear usuario: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo registrar el usuario']);
        }
    }

        /**
     * Edita los datos de un usuario existente
     */
    public function editar(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = (int) ($datos['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID de usuario requerido']);
            return;
        }

        $actual = $this->model->obtenerPorId($id);
        if (!$actual) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado']);
            return;
        }

        foreach (['nombre', 'cedula', 'correo', 'usuario', 'rol_id'] as $campo) {
            if (empty(trim((string) ($datos[$campo] ?? '')))) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => "El campo $campo es requerido"]);
                return;
            }
        }

        $datos['nombre']   = trim($datos['nombre']);
        $datos['cedula']   = trim($datos['cedula']);
        $datos['correo']   = trim($datos['correo']);
        $datos['usuario']  = trim($datos['usuario']);
        $datos['telefono'] = trim($datos['telefono'] ?? '');
        $datos['rol_id']   = (int) $datos['rol_id'];

        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El correo no tiene un formato válido']);
            return;
        }

        if (!$this->model->existeRol($datos['rol_id'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El rol seleccionado no existe']);
            return;
        }

        // El admin no puede quitarse a sí mismo el rol
        $esUsuarioActual = $id === (int) AuthService::usuarioActual()['id'];
        if ($esUsuarioActual && $datos['rol_id'] !== (int) $actual['id_rol']) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No puede cambiar su propio rol']);
            return;
        }

        // Duplicados: el tercer parámetro ($id) excluye al propio usuario
        if ($this->model->existeNombreUsuario($datos['usuario'], $id)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El nombre de usuario ya está en uso']);
            return;
        }
        if ($this->model->existeCorreo($datos['correo'], $id)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El correo ya está registrado']);
            return;
        }
        if ($this->model->existeIdentificacion($datos['cedula'], $id)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La identificación ya está registrada']);
            return;
        }

        try {
            $this->model->actualizar($id, $datos);

            // Si se editó a sí mismo, refrescar la sesión
            if ($esUsuarioActual) {
                $_SESSION['usuario']['nombre']    = $datos['nombre'];
                $_SESSION['usuario']['iniciales'] = AuthService::iniciales($datos['nombre']);
                $_SESSION['usuario']['usuario']   = $datos['usuario'];
            }

            echo json_encode(['ok' => true, 'mensaje' => 'Usuario actualizado exitosamente']);
        } catch (PDOException $e) {
            error_log('USR-02 editar usuario: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar el usuario']);
        }
    }

    /**
     *  Restablece contraseña
     */
    public function restablecerContrasena(): void
    {
        $datos = json_decode(file_get_contents('php://input'), true);
        $id    = (int) ($datos['id']        ?? 0);
        $nueva = trim($datos['contrasena']  ?? '');

        if (!$id || $nueva === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID y nueva contraseña son requeridos']);
            return;
        }

        if (strlen($nueva) < 8) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres']);
            return;
        }

        if (!$this->model->obtenerPorId($id)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado']);
            return;
        }

        $this->model->restablecerContrasena($id, $nueva);

        echo json_encode(['ok' => true, 'mensaje' => 'Contraseña restablecida exitosamente']);
    }

    /**
     * Activa / inactiva usuario
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

        if ($estado === 'inactivo' && $id === (int) AuthService::usuarioActual()['id']) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No puede inactivar su propia cuenta']);
            return;
        }

        if (!$this->model->obtenerPorId($id)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado']);
            return;
        }

        $this->model->cambiarEstado($id, $estado);

        echo json_encode(['ok' => true, 'mensaje' => "Usuario $estado exitosamente"]);
    }
}