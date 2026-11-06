<?php

require_once ROOT_PATH . '/models/Paciente.php';
require_once ROOT_PATH . '/models/ExpedienteClinico.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';

class ExpedienteController
{
    private Paciente $paciente;
    private ExpedienteClinico $expediente;

    public function __construct()
    {
        AuthService::requerir(['odontologo', 'administrador', 'asistente_dental', 'recepcionista']);
        $this->paciente   = new Paciente();
        $this->expediente = new ExpedienteClinico();
    }

    public function listarPacientes(): void
    {
        $q = trim($_GET['q'] ?? '');
        $lista = $q !== ''
            ? $this->paciente->buscar($q)
            : $this->paciente->obtenerTodos();

        $mapa = $this->expediente->mapaPorPaciente();
        foreach ($lista as &$p) {
            $idPac = (int) ($p['id_paciente'] ?? 0);
            $p['id_expediente']    = $idPac && isset($mapa[$idPac]) ? $mapa[$idPac] : null;
            $p['tiene_expediente'] = $p['id_expediente'] !== null;
        }
        unset($p);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($lista);
    }

    public function obtener(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $idPaciente = (int) ($_GET['id_paciente'] ?? 0);
        if (!$idPaciente) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Falta id_paciente']);
            return;
        }

        $exp = $this->expediente->obtenerPorPaciente($idPaciente);
        echo json_encode($exp ?? (object)[]);
    }

    public function crear(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $datos = $this->bodyJson();

        $idPaciente = (int) ($datos['id_paciente'] ?? 0);

        if (!$idPaciente && !empty($datos['id_usuario'])) {
            $pac = $this->paciente->obtenerPorUsuario((int) $datos['id_usuario']);
            if (!$pac) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'El usuario no es un paciente']);
                return;
            }
            $idPaciente = (int) ($pac['id_paciente'] ?: $this->paciente->crear(['id_usuario' => (int) $datos['id_usuario']]));
            $datos['id_paciente'] = $idPaciente;
        }

        if (!$idPaciente || trim($datos['motivo_consulta'] ?? '') === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Faltan campos requeridos']);
            return;
        }

        if ($this->expediente->obtenerPorPaciente($idPaciente)) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'El paciente ya tiene un expediente']);
            return;
        }

        $id  = $this->expediente->crear($datos);
        $ses = AuthService::usuarioActual();
        Bitacora::registrar(
            'EXPEDIENTE_CREADO',
            "Expediente clínico ID $id creado para paciente ID $idPaciente",
            $ses['id'],
            $ses['usuario']
        );

        echo json_encode(['ok' => true, 'id_expediente' => $id]);
    }

    public function actualizar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $datos = $this->bodyJson();

        $idExpediente = (int) ($datos['id_expediente'] ?? 0);
        if (!$idExpediente || trim($datos['motivo_consulta'] ?? '') === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Faltan campos requeridos']);
            return;
        }

        $this->expediente->actualizar($idExpediente, $datos);
        $ses = AuthService::usuarioActual();
        Bitacora::registrar(
            'EXPEDIENTE_EDITADO',
            "Expediente clínico ID $idExpediente actualizado",
            $ses['id'],
            $ses['usuario']
        );

        echo json_encode(['ok' => true]);
    }

    public function listarAtenciones(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $idExpediente = (int) ($_GET['id_expediente'] ?? 0);
        if (!$idExpediente) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Falta id_expediente']);
            return;
        }

        echo json_encode($this->expediente->atenciones($idExpediente));
    }

    public function registrarAtencion(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $datos = $this->bodyJson();

        $idExpediente = (int) ($datos['id_expediente'] ?? 0);
        if (!$idExpediente || trim($datos['diagnostico'] ?? '') === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Faltan campos requeridos']);
            return;
        }

        $ses = AuthService::usuarioActual();
        $datos['id_odontologo'] = $ses['id'];

        $id = $this->expediente->registrarAtencion($datos);
        Bitacora::registrar(
            'ATENCION_REGISTRADA',
            "Atención clínica ID $id en expediente ID $idExpediente",
            $ses['id'],
            $ses['usuario']
        );

        echo json_encode(['ok' => true, 'id_atencion' => $id]);
    }

    public function actualizarPaciente(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $datos = $this->bodyJson();

            $idPaciente = (int) ($datos['id_paciente'] ?? 0);
            if (!$idPaciente) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Falta id_paciente']);
                return;
            }

            $this->paciente->actualizar($idPaciente, $datos);

            $ses = AuthService::usuarioActual();
            Bitacora::registrar(
                'PACIENTE_ACTUALIZADO',
                "Datos clínicos del paciente ID $idPaciente actualizados",
                $ses['id'],
                $ses['usuario']
            );

            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    private function bodyJson(): array
    {
        $raw = file_get_contents('php://input');
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }
}
