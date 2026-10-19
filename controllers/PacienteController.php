<?php

require_once ROOT_PATH . '/models/Paciente.php';
require_once ROOT_PATH . '/models/Usuario.php';
require_once ROOT_PATH . '/services/AuthService.php';

class PacienteController
{
    private Paciente $model;

    public function __construct()
    {
        AuthService::requerir(['asistente_dental', 'recepcionista', 'odontologo', 'administrador']);
        $this->model = new Paciente();
    }

    /**
     * Lista todos los pacientes desde la BD.
     * Devuelve el formato que espera la vista recepcionista: id, nombre, cedula,
     * telefono, correo, estado (activo/inactivo), más id_usuario para edición.
     */
    public function listar(): array
    {
        $filas = $this->model->obtenerTodos();
        return array_map(fn($p) => [
            'id'         => $p['id_usuario'],
            'id_usuario' => $p['id_usuario'],
            'nombre'     => $p['nombre_completo'],
            'cedula'     => $p['identificacion'] ?? '',
            'telefono'   => $p['telefono']        ?? '',
            'correo'     => $p['correo']           ?? '',
            'usuario'    => $p['nombre_usuario']   ?? '',
            'edad'       => $p['edad']             ?? null,
            'estado'     => $p['estado_usuario']   ?? 'activo',
        ], $filas);
    }

    /**
     * Recepcionista: crear nuevo paciente (crea usuario con rol paciente).
     */
    public function crear(): void
    {
        AuthService::requerir(['recepcionista', 'administrador']);
        // Pendiente de implementar
        echo json_encode(['ok' => true, 'demo' => true]);
    }

    /**
     * Recepcionista: editar datos completos de un paciente.
     */
    public function editar(): void
    {
        AuthService::requerir(['recepcionista', 'administrador']);
        // Pendiente de implementar
        echo json_encode(['ok' => true, 'demo' => true]);
    }

    /**
     * Asistente dental: editar solo datos básicos del paciente
     * (nombre, cédula, teléfono, correo, nombre_usuario).
     * NO puede cambiar contraseña, rol ni estado.
     */
    public function editarDatosBasicos(): void
    {
        AuthService::requerir(['asistente_dental', 'recepcionista', 'administrador']);

        $idUsuario = (int) ($_POST['id_usuario'] ?? 0);
        $nombre    = trim($_POST['nombre']    ?? '');
        $cedula    = trim($_POST['cedula']    ?? '');
        $telefono  = trim($_POST['telefono']  ?? '');
        $correo    = trim($_POST['correo']    ?? '');
        $usuario   = trim($_POST['usuario']   ?? '');

        if (!$idUsuario || !$nombre || !$cedula || !$correo || !$usuario) {
            echo json_encode(['ok' => false, 'mensaje' => 'Faltan datos obligatorios.']);
            return;
        }

        try {
            $modelUsuario = new Usuario();

            // Verificar unicidad de correo y nombre_usuario (excluyendo al propio usuario)
            if ($modelUsuario->existeCorreo($correo, $idUsuario)) {
                echo json_encode(['ok' => false, 'mensaje' => 'Ya existe otro usuario con ese correo.']);
                return;
            }
            if ($modelUsuario->existeNombreUsuario($usuario, $idUsuario)) {
                echo json_encode(['ok' => false, 'mensaje' => 'Ese nombre de usuario ya está en uso.']);
                return;
            }

            $modelUsuario->actualizar($idUsuario, [
                'cedula'   => $cedula,
                'nombre'   => $nombre,
                'telefono' => $telefono !== '' ? $telefono : null,
                'correo'   => $correo,
                'usuario'  => $usuario,
            ]);

            echo json_encode(['ok' => true, 'mensaje' => 'Datos actualizados correctamente.']);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'mensaje' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    public function inactivar(): void
    {
        AuthService::requerir(['recepcionista', 'administrador']);
        // Pendiente de implementar
        echo json_encode(['ok' => true, 'demo' => true]);
    }
}