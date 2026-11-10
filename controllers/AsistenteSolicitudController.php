<?php

require_once ROOT_PATH . '/models/SolicitudCitaAsistente.php';
require_once ROOT_PATH . '/models/ReporteAsistente.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';

/**
 * AsistenteSolicitudController.php — ASI-05
 * El paciente solicita una cita a partir de un reporte del asistente inteligente.
 */
class AsistenteSolicitudController
{
    private const DIAS  = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    private SolicitudCitaAsistente $solicitudes;
    private ReporteAsistente $reportes;

    public function __construct()
    {
        AuthService::requerir(['paciente']);

        if (($this->sesion()['rol'] ?? '') !== 'paciente') {
            $this->responder(['ok' => false, 'error' => 'Esta opción solo está disponible en el portal del paciente.'], 403);
        }

        $this->solicitudes = new SolicitudCitaAsistente();
        $this->reportes    = new ReporteAsistente();
    }

    /* ── Utilidades ─────────────────────────────────────────── */

    private function sesion(): array
    {
        return AuthService::usuarioActual() ?? [];
    }

    private function idUsuario(): int
    {
        return (int) ($this->sesion()['id'] ?? 0);
    }

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

    /** El reporte debe existir y ser del paciente en sesión */
    private function reporteDelPaciente(int $idReporte): array
    {
        $r = $idReporte ? $this->reportes->obtenerPorId($idReporte) : null;
        if (!$r || (int) $r['id_usuario'] !== $this->idUsuario()) {
            $this->responder(['ok' => false, 'error' => 'No encontramos ese reporte.'], 404);
        }
        return $r;
    }

    private function horaTexto(string $hhmm): string
    {
        return date('g:i a', strtotime($hhmm));
    }

    private function etiquetaDia(string $fecha): string
    {
        $hoy    = date('Y-m-d');
        $manana = date('Y-m-d', strtotime('+1 day'));
        $t      = strtotime($fecha);
        $texto  = self::DIAS[(int) date('w', $t)] . ' ' . (int) date('j', $t) . ' ' . self::MESES[(int) date('n', $t) - 1];

        if ($fecha === $hoy)    return 'Hoy · ' . $texto;
        if ($fecha === $manana) return 'Mañana · ' . $texto;
        return ucfirst($texto);
    }

    private function resumenSolicitud(array $s): array
    {
        $conHorario = !empty($s['fecha']);
        return [
            'id'          => (int) $s['id_solicitud'],
            'estado'      => $s['estado'],
            'con_horario' => $conHorario,
            'fecha'       => $conHorario ? $this->etiquetaDia($s['fecha']) : null,
            'hora'        => $conHorario ? $this->horaTexto(substr($s['hora_inicio'], 0, 5)) : null,
            'odontologo'  => $s['odontologo'] ?? null,
        ];
    }

    /* ── Horarios disponibles ───────────────────────────────── */

    /** GET id_reporte → días y horas libres, o la solicitud existente si ya la hizo */
    public function disponibilidad(): void
    {
        $idReporte = (int) ($_GET['id_reporte'] ?? 0);
        $this->reporteDelPaciente($idReporte);

        $existente = $this->solicitudes->porReporte($idReporte);
        if ($existente) {
            $this->responder([
                'ok'              => true,
                'ya_solicitada'   => true,
                'solicitud'       => $this->resumenSolicitud($existente),
                'hay_disponibilidad' => false,
                'dias'            => [],
            ]);
        }

        $dias = array_map(fn($d) => [
            'fecha'    => $d['fecha'],
            'etiqueta' => $this->etiquetaDia($d['fecha']),
            'horas'    => array_map(fn($h) => [
                'hora'          => $h['hora'],
                'hora_texto'    => $this->horaTexto($h['hora']),
                'id_odontologo' => $h['id_odontologo'],
                'odontologo'    => $h['odontologo'],
            ], $d['horas']),
        ], $this->solicitudes->disponibilidad($this->idUsuario()));

        $this->responder([
            'ok'                 => true,
            'ya_solicitada'      => false,
            'solicitud'          => null,
            'hay_disponibilidad' => count($dias) > 0,
            'dias'               => $dias,
        ]);
    }

    /* ── Solicitar la cita (esc. 1 y 2) ─────────────────────── */

    /** POST {id_reporte, fecha?, hora?, id_odontologo?} */
    public function crear(): void
    {
        $d         = $this->entrada();
        $idReporte = (int) ($d['id_reporte'] ?? 0);
        $fecha     = trim((string) ($d['fecha'] ?? ''));
        $hora      = trim((string) ($d['hora'] ?? ''));
        $idOdont   = (int) ($d['id_odontologo'] ?? 0);

        $reporte = $this->reporteDelPaciente($idReporte);

        if ($this->solicitudes->porReporte($idReporte)) {
            $this->responder([
                'ok'    => false,
                'error' => 'Ya envió una solicitud de cita para este reporte. El personal de la clínica se pondrá en contacto con usted.',
            ], 409);
        }

        $conHorario = ($fecha !== '' || $hora !== '' || $idOdont !== 0);

        if ($conHorario) {
            if ($fecha === '' || $hora === '' || !$idOdont) {
                $this->responder(['ok' => false, 'error' => 'Seleccione un horario disponible para continuar.'], 400);
            }
            if ($msg = $this->solicitudes->validarHorario($fecha, $hora)) {
                $this->responder(['ok' => false, 'error' => $msg], 400);
            }
            if (!$this->solicitudes->odontologoActivo($idOdont)) {
                $this->responder(['ok' => false, 'error' => 'El odontólogo seleccionado no está disponible.'], 400);
            }
        } elseif ($this->solicitudes->disponibilidad($this->idUsuario())) {
            // Escenario 2 solo aplica cuando NO hay horarios; si los hay, debe elegir uno
            $this->responder(['ok' => false, 'error' => 'Seleccione un horario disponible para continuar.'], 400);
        }

        $inicio = $conHorario ? $hora : null;
        $fin    = $conHorario
            ? date('H:i', strtotime($hora) + SolicitudCitaAsistente::DURACION_MIN * 60)
            : null;

        $motivo = sprintf(
            'Reporte #%d (%s): %s. Zona: %s. Intensidad: %d/10. Inicio: %s.',
            $idReporte,
            ucfirst($reporte['prioridad']),
            rtrim($reporte['descripcion'], ". \t\n\r"),
            ReporteAsistente::ZONAS[$reporte['zona']] ?? $reporte['zona'],
            (int) $reporte['intensidad'],
            ReporteAsistente::TIEMPOS[$reporte['tiempo']] ?? $reporte['tiempo']
        );

        try {
            $id = $this->solicitudes->crear([
                'id_reporte'    => $idReporte,
                'id_usuario'    => $this->idUsuario(),
                'id_odontologo' => $idOdont,
                'fecha'         => $conHorario ? $fecha : null,
                'hora_inicio'   => $inicio,
                'hora_fin'      => $fin,
                'motivo'        => mb_substr($motivo, 0, 500),
            ]);

            Bitacora::registrar(
                'ASISTENTE_SOLICITUD_CITA',
                "Solicitud de cita #{$id} generada desde el reporte #{$idReporte}" . ($conHorario ? " ({$fecha} {$hora})" : ' (sin horario)')
            );

            $s = $this->solicitudes->obtenerPorId($id);
            $this->responder([
                'ok'        => true,
                'id'        => $id,
                'solicitud' => $this->resumenSolicitud($s),
                'mensaje'   => $conHorario
                    ? 'Listo, enviamos su solicitud de cita. El personal de la clínica la confirmará y se pondrá en contacto con usted.'
                    : 'Su solicitud quedó registrada. Como por ahora no hay horarios disponibles, el personal de la clínica se pondrá en contacto con usted para coordinar la cita.',
            ], 201);
        } catch (HorarioSolicitudNoDisponibleException $e) {
            $this->responder(['ok' => false, 'conflicto' => true, 'error' => $e->getMessage()], 409);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {   // dos envíos casi simultáneos del mismo reporte
                $this->responder(['ok' => false, 'error' => 'Ya envió una solicitud de cita para este reporte.'], 409);
            }
            error_log('ASI-05 crear solicitud: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No pudimos enviar su solicitud. Intente de nuevo en unos segundos.'], 500);
        } catch (Throwable $e) {
            error_log('ASI-05 crear solicitud: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No pudimos enviar su solicitud. Intente de nuevo en unos segundos.'], 500);
        }
    }

    /* ── Cancelar (esc. 3) ──────────────────────────────────── */

    /** POST {id_reporte}: el paciente decide no solicitar cita; el reporte se conserva */
    public function cancelar(): void
    {
        $idReporte = (int) ($this->entrada()['id_reporte'] ?? 0);
        $this->reporteDelPaciente($idReporte);

        Bitacora::registrar('ASISTENTE_SOLICITUD_CANCELADA', "El paciente decidió no solicitar cita para el reporte #{$idReporte}");

        $this->responder([
            'ok'      => true,
            'mensaje' => 'Entendido, no se generó ninguna solicitud. Su reporte quedó guardado.',
        ]);
    }
}