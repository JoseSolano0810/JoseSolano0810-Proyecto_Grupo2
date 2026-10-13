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
        AuthService::requerir(['odontologo', 'administrador']);
        $this->paciente   = new Paciente();
        $this->expediente = new ExpedienteClinico();
    }

    /*  Pacientes  */

    /** Lista los pacientes con perfil clínico */
    public function listarPacientes(): void
    {
        $q = trim($_GET['q'] ?? '');
        $lista = $q !== ''
            ? $this->paciente->buscar($q)
            : $this->paciente->obtenerTodos();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($lista);
    }

    /*expedientes*/

    /** Devuelve expediente de un paciente */
    public function obtener(): void
    {
        $idPaciente = (int) ($_GET['id_paciente'] ?? 0);
        if (!$idPaciente) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Falta id_paciente']);
            return;
        }

        $exp = $this->expediente->obtenerPorPaciente($idPaciente);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($exp ?? (object)[]);
    }

    /** Crea expediente clínico de paciente*/
    public function crear(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $datos = $this->bodyJson();

        $idPaciente = (int) ($datos['id_paciente'] ?? 0);
        if (!$idPaciente || trim($datos['motivo_consulta'] ?? '') === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Faltan campos requeridos']);
            return;
        }

        /** verifica que no haya ya un expediente */
        if ($this->expediente->obtenerPorPaciente($idPaciente)) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'El paciente ya tiene un expediente']);
            return;
        }

        $id  = $this->expediente->crear($datos);
        $ses = AuthService::usuarioActual();
        Bitacora::registrar(
            $ses['id'],
            $ses['nombre_usuario'],
            'EXPEDIENTE_CREADO',
            "Expediente clínico ID $id creado para paciente ID $idPaciente"
        );

        echo json_encode(['ok' => true, 'id_expediente' => $id]);
    }

    /** actualiza expediente clínico */
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
            $ses['id'],
            $ses['nombre_usuario'],
            'EXPEDIENTE_EDITADO',
            "Expediente clínico ID $idExpediente actualizado"
        );

        echo json_encode(['ok' => true]);
    }

    /* Atenciones */

    /** Lista atenciones de un expediente */
    public function listarAtenciones(): void
    {
        $idExpediente = (int) ($_GET['id_expediente'] ?? 0);
        if (!$idExpediente) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Falta id_expediente']);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->expediente->atenciones($idExpediente));
    }

    /** Registra atención clínica */
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
            $ses['id'],
            $ses['nombre_usuario'],
            'ATENCION_REGISTRADA',
            "Atención clínica ID $id en expediente ID $idExpediente"
        );

        echo json_encode(['ok' => true, 'id_atencion' => $id]);
    }

    /* Helpers */

    private function bodyJson(): array
    {
        $raw = file_get_contents('php://input');
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }
}