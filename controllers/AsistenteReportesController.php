<?php

require_once ROOT_PATH . '/models/ReporteAsistente.php';
require_once ROOT_PATH . '/models/SolicitudCitaAsistente.php';
require_once ROOT_PATH . '/models/Cita.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';

/**
 * AsistenteReportesController.php — ASI-04 y seguimiento de ASI-05
 * Recepción, asistente dental y administrador consultan los reportes que los pacientes registran con el
 * asistente inteligente y atienden las solicitudes de cita que salen de ellos.
 */
class AsistenteReportesController
{
    private const ROLES = ['recepcionista', 'asistente_dental', 'administrador'];

    private const ESTADOS = [
        'registrado'     => 'Registrado',
        'en_seguimiento' => 'En seguimiento',
        'atendido'       => 'Atendido',
    ];

    private ReporteAsistente $model;
    private SolicitudCitaAsistente $solicitudes;
    private Cita $citas;

    public function __construct()
    {
        AuthService::requerir(self::ROLES);

        $rol = (string) (AuthService::usuarioActual()['rol'] ?? '');
        if (!in_array($rol, self::ROLES, true)) {
            $this->responder(['ok' => false, 'error' => 'No cuenta con los permisos necesarios para consultar estos reportes.'], 403);
        }

        $this->model       = new ReporteAsistente();
        $this->solicitudes = new SolicitudCitaAsistente();
        $this->citas       = new Cita();
    }

    /* ── Utilidades ─────────────────────────────────────────── */

    private function responder(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function entrada(): array
    {
        $d = json_decode(file_get_contents('php://input'), true);
        return is_array($d) ? $d : [];
    }

    private function esFecha(string $f): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $f);
        return $d && $d->format('Y-m-d') === $f;
    }

    private function formatear(array $r, array $estadosSolicitud = []): array
    {
        $ts = strtotime($r['fecha_hora']);

        return [
            'id'               => (int) $r['id_reporte'],
            'id_usuario'       => (int) $r['id_usuario'],
            'paciente'         => $r['paciente'] ?? ('Paciente #' . $r['id_usuario']),
            'cedula'           => $r['paciente_cedula'] ?? '',
            'fecha'            => date('d/m/Y', $ts),
            'hora'             => date('g:i a', $ts),
            'descripcion'      => $r['descripcion'],
            'zona'             => ReporteAsistente::ZONAS[$r['zona']] ?? $r['zona'],
            'intensidad'       => (int) $r['intensidad'],
            'tiempo'           => ReporteAsistente::TIEMPOS[$r['tiempo']] ?? $r['tiempo'],
            'prioridad'        => $r['prioridad'],
            'prioridad_texto'  => ucfirst($r['prioridad']),
            'estado'           => $r['estado'],
            'estado_texto'     => self::ESTADOS[$r['estado']] ?? $r['estado'],
            'solicitud_estado' => $estadosSolicitud[(int) $r['id_reporte']] ?? null,
            'criterios'        => ($r['criterios'] ?? '') !== ''
                                    ? array_values(array_filter(array_map('trim', explode(' | ', $r['criterios']))))
                                    : [],
        ];
    }

    private function formatearSolicitud(array $s): array
    {
        $con = !empty($s['fecha']);
        $hora = $con ? substr($s['hora_inicio'], 0, 5) : null;

        return [
            'id'              => (int) $s['id_solicitud'],
            'id_reporte'      => (int) $s['id_reporte'],
            'id_usuario'      => (int) $s['id_usuario'],
            'paciente'        => $s['paciente'] ?? ('Paciente #' . $s['id_usuario']),
            'cedula'          => $s['paciente_cedula'] ?? '',
            'telefono'        => $s['paciente_telefono'] ?? '',
            'prioridad'       => $s['reporte_prioridad'],
            'prioridad_texto' => ucfirst($s['reporte_prioridad']),
            'motivo'          => $s['motivo'],
            'con_horario'     => $con,
            'fecha'           => $con ? $s['fecha'] : null,
            'fecha_fmt'       => $con ? date('d/m/Y', strtotime($s['fecha'])) : null,
            'hora'            => $hora,
            'hora_texto'      => $con ? date('g:i a', strtotime($hora)) : null,
            'id_odontologo'   => $s['id_odontologo'] !== null ? (int) $s['id_odontologo'] : null,
            'odontologo'      => $s['odontologo'] ?? null,
            'estado'          => $s['estado'],
            'estado_texto'    => SolicitudCitaAsistente::ESTADOS_TEXTO[$s['estado']] ?? $s['estado'],
            'id_cita'         => $s['id_cita'] !== null ? (int) $s['id_cita'] : null,
            'solicitada'      => date('d/m/Y g:i a', strtotime($s['created_at'])),
        ];
    }

    /* ── ASI-04 esc. 1 y 3: listado con búsqueda ────────────── */

    public function listar(): void
    {
        $q         = trim((string) ($_GET['q'] ?? ''));
        $prioridad = trim((string) ($_GET['prioridad'] ?? ''));
        $desde     = trim((string) ($_GET['desde'] ?? ''));
        $hasta     = trim((string) ($_GET['hasta'] ?? ''));

        if (($desde !== '' && !$this->esFecha($desde)) || ($hasta !== '' && !$this->esFecha($hasta))) {
            $this->responder(['ok' => false, 'error' => 'Revise las fechas: no tienen un formato válido.'], 400);
        }
        if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
            $this->responder(['ok' => false, 'error' => 'La fecha inicial no puede ser posterior a la fecha final.'], 400);
        }
        if ($prioridad !== '' && !in_array($prioridad, ReporteAsistente::PRIORIDADES, true)) {
            $this->responder(['ok' => false, 'error' => 'La prioridad seleccionada no es válida.'], 400);
        }

        $filas = $this->model->listar([
            'q'         => $q,
            'prioridad' => $prioridad,
            'desde'     => $desde,
            'hasta'     => $hasta,
        ]);

        $estadosSolicitud = $this->solicitudes->estadosPorReportes(array_column($filas, 'id_reporte'));

        $this->responder([
            'ok'    => true,
            'total' => count($filas),
            'data'  => array_map(fn($r) => $this->formatear($r, $estadosSolicitud), $filas),
        ]);
    }

    /* ── ASI-04 esc. 2: detalle de un reporte ───────────────── */

    public function detalle(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $r  = $id ? $this->model->obtenerPorId($id) : null;

        if (!$r) {
            $this->responder(['ok' => false, 'error' => 'No encontramos ese reporte.'], 404);
        }

        // Es información de salud: queda constancia de quién la abrió
        Bitacora::registrar('ASISTENTE_REPORTE_CONSULTADO', "Consulta del reporte #{$id} del asistente");

        $solicitud = $this->solicitudes->porReporte($id);

        $this->responder([
            'ok'        => true,
            'data'      => $this->formatear($r, $solicitud ? [$id => $solicitud['estado']] : []),
            'solicitud' => $solicitud ? $this->formatearSolicitud($solicitud) : null,
        ]);
    }

    /* ── ASI-05: bandeja de solicitudes de cita ─────────────── */

    /** GET estado (opcional: pendiente, programada, descartada) */
    public function solicitudes(): void
    {
        $estado = trim((string) ($_GET['estado'] ?? ''));
        if ($estado !== '' && !in_array($estado, SolicitudCitaAsistente::ESTADOS, true)) {
            $this->responder(['ok' => false, 'error' => 'El estado seleccionado no es válido.'], 400);
        }

        $filas = $this->solicitudes->listar(['estado' => $estado]);

        $this->responder([
            'ok'         => true,
            'total'      => count($filas),
            'pendientes' => $this->solicitudes->contarPendientes(),
            'data'       => array_map([$this, 'formatearSolicitud'], $filas),
        ]);
    }

    /**
     * POST {id_solicitud, id_cita}: la cita ya se creó con citas.crear; aquí se enlaza con la solicitud.
     * Se comprueba que la cita sea realmente del paciente que la pidió.
     */
    public function programada(): void
    {
        $d       = $this->entrada();
        $idSol   = (int) ($d['id_solicitud'] ?? 0);
        $idCita  = (int) ($d['id_cita'] ?? 0);

        $s = $idSol ? $this->solicitudes->obtenerPorId($idSol) : null;
        if (!$s) {
            $this->responder(['ok' => false, 'error' => 'No encontramos esa solicitud.'], 404);
        }
        if ($s['estado'] !== 'pendiente') {
            $this->responder(['ok' => false, 'error' => 'Esta solicitud ya fue atendida.'], 409);
        }

        $cita = $idCita ? $this->citas->obtenerPorId($idCita) : null;
        if (!$cita || (int) $cita['id_paciente'] !== (int) $s['id_usuario'] || !in_array($cita['estado'], Cita::ESTADOS_ACTIVOS, true)) {
            $this->responder(['ok' => false, 'error' => 'La cita indicada no corresponde al paciente de esta solicitud.'], 400);
        }

        try {
            $this->solicitudes->marcarProgramada($idSol, $idCita);
            Bitacora::registrar('ASISTENTE_SOLICITUD_PROGRAMADA', "Solicitud #{$idSol} programada como cita #{$idCita}");
            $this->responder(['ok' => true, 'mensaje' => 'La solicitud quedó marcada como programada.']);
        } catch (Throwable $e) {
            error_log('ASI-05 programada: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No pudimos actualizar la solicitud.'], 500);
        }
    }

    /** POST {id_solicitud}: el personal decide no programarla (libera el horario reservado) */
    public function descartar(): void
    {
        $idSol = (int) ($this->entrada()['id_solicitud'] ?? 0);

        $s = $idSol ? $this->solicitudes->obtenerPorId($idSol) : null;
        if (!$s) {
            $this->responder(['ok' => false, 'error' => 'No encontramos esa solicitud.'], 404);
        }
        if ($s['estado'] !== 'pendiente') {
            $this->responder(['ok' => false, 'error' => 'Esta solicitud ya fue atendida.'], 409);
        }

        try {
            $this->solicitudes->descartar($idSol);
            Bitacora::registrar('ASISTENTE_SOLICITUD_DESCARTADA', "Solicitud #{$idSol} descartada");
            $this->responder(['ok' => true, 'mensaje' => 'La solicitud se descartó.']);
        } catch (Throwable $e) {
            error_log('ASI-05 descartar: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No pudimos descartar la solicitud.'], 500);
        }
    }
}