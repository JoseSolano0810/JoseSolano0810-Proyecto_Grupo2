<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Usuario.php';
require_once ROOT_PATH . '/services/AuthService.php';
require_once ROOT_PATH . '/models/Bitacora.php';

class UsuarioController
{
    private Usuario $model;

    public function __construct()
    {
        AuthService::requerir(['administrador']);
        $this->model = new Usuario();
    }

    /** Lista usuarios */
    public function listar(): void
    {
        echo json_encode(['ok' => true, 'data' => $this->model->obtenerTodos()]);
    }

    /** Lista roles */
    public function roles(): void
    {
        echo json_encode(['ok' => true, 'data' => $this->model->obtenerRoles()]);
    }

    /** Datos para la vista del panel */
    public function datosVista(): array
    {
        $rolesPorUsuario = $this->model->obtenerRolesPorUsuario();

        $usuarios = array_map(fn($u) => [
            'id'        => (int) $u['id_usuario'],
            'nombre'    => $u['nombre_completo'],
            'usuario'   => $u['nombre_usuario'],
            'cedula'    => $u['identificacion'],
            'telefono'  => $u['telefono'] ?? '',
            'correo'    => $u['correo'],
            'rol'       => $u['rol'],
            'rol_id'    => (int) $u['id_rol'],  
            'roles'     => $rolesPorUsuario[(int) $u['id_usuario']] ?? [],
            'roles_ids' => array_column($rolesPorUsuario[(int) $u['id_usuario']] ?? [], 'id'),
            'estado'    => $u['estado'],
        ], $this->model->obtenerTodos());

        $roles = array_map(fn($r) => [
            'id'     => (int) $r['id_rol'],
            'nombre' => $r['nombre'],
        ], $this->model->obtenerRoles());

        return ['usuarios' => $usuarios, 'roles' => $roles];
    }

    /** Registra usuario */
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

        // USU-05: política de contraseñas
        if ($errorPolitica = AuthService::validarPoliticaContrasena((string) $datos['contrasena'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $errorPolitica]);
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
            Bitacora::registrar('USUARIO_CREADO', "Creó al usuario {$usuario['nombre_usuario']} con rol {$usuario['rol']}");

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

    /** Edita los datos de un usuario existente */
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

        foreach (['nombre', 'cedula', 'correo', 'usuario'] as $campo) {
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

        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El correo no tiene un formato válido']);
            return;
        }

        $esUsuarioActual = $id === (int) AuthService::usuarioActual()['id'];
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
            Bitacora::registrar('USUARIO_EDITADO', "Editó los datos del usuario {$datos['usuario']} (ID $id)");

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
     * Asigna uno o varios roles a un usuario y define el rol inicial
     * Body: { id, roles: [ids], rol_principal: id }
     */
    public function asignarRol(): void
    {
        $datos     = json_decode(file_get_contents('php://input'), true) ?? [];
        $id        = (int) ($datos['id'] ?? 0);
        $roles     = array_values(array_unique(array_map('intval', (array) ($datos['roles'] ?? []))));
        $principal = (int) ($datos['rol_principal'] ?? 0);

        if (!$id || !$roles) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Seleccione al menos un rol']);
            return;
        }

        if (!$principal || !in_array($principal, $roles, true)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El rol inicial debe estar entre los roles seleccionados']);
            return;
        }

        if (!$this->model->obtenerPorId($id)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado']);
            return;
        }

        foreach ($roles as $rolId) {
            if (!$this->model->existeRol($rolId)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Uno de los roles seleccionados no existe']);
                return;
            }
        }

        if ($id === (int) AuthService::usuarioActual()['id']) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'No puede cambiar sus propios roles']);
            return;
        }

        try {
            $this->model->asignarRoles($id, $roles, $principal);
            Bitacora::registrar('ROLES_ASIGNADOS', "Actualizó los roles del usuario ID $id (IDs de rol: " . implode(',', $roles) . "; inicial: $principal)");
            echo json_encode(['ok' => true, 'mensaje' => 'Roles actualizados correctamente']);
        } catch (PDOException $e) {
            error_log('USU-02 asignar roles: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudieron actualizar los roles']);
        }
    }

    /** Restablece contraseña */
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

        if ($errorPolitica = AuthService::validarPoliticaContrasena($nueva)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $errorPolitica]);
            return;
        }

        if (!$this->model->obtenerPorId($id)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Usuario no encontrado']);
            return;
        }

        $this->model->restablecerContrasena($id, $nueva);
        Bitacora::registrar('CONTRASENA_RESTABLECIDA', "Restableció la contraseña del usuario ID $id");

        echo json_encode(['ok' => true, 'mensaje' => 'Contraseña restablecida exitosamente']);
    }

    /** Activa / inactiva usuario */
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
        Bitacora::registrar('ESTADO_CAMBIADO', "Cambió el estado del usuario ID $id a $estado");

        echo json_encode(['ok' => true, 'mensaje' => "Usuario $estado exitosamente"]);
    }
}