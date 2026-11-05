<?php

require_once ROOT_PATH . '/models/ReporteAsistente.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';

/**
 * AsistenteReportesController.php — ASI-04
 * Consulta, por parte de recepción y asistente dental, de los reportes que los
 * pacientes registran con el asistente inteligente. Solo lectura.
 */
class AsistenteReportesController
{
    private const ROLES = ['recepcionista', 'asistente_dental'];

    private const ESTADOS = [
        'registrado'     => 'Registrado',
        'en_seguimiento' => 'En seguimiento',
        'atendido'       => 'Atendido',
    ];

    private ReporteAsistente $model;

    public function __construct()
    {
        AuthService::requerir(self::ROLES);

        $rol = (string) (AuthService::usuarioActual()['rol'] ?? '');
        if (!in_array($rol, self::ROLES, true)) {
            $this->responder(['ok' => false, 'error' => 'No cuenta con los permisos necesarios para consultar estos reportes.'], 403);
        }

        $this->model = new ReporteAsistente();
    }

    /* ── Utilidades ─────────────────────────────────────────── */

    private function responder(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function esFecha(string $f): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $f);
        return $d && $d->format('Y-m-d') === $f;
    }

    private function formatear(array $r): array
    {
        $ts = strtotime($r['fecha_hora']);

        return [
            'id'              => (int) $r['id_reporte'],
            'id_usuario'      => (int) $r['id_usuario'],
            'paciente'        => $r['paciente'] ?? ('Paciente #' . $r['id_usuario']),
            'cedula'          => $r['paciente_cedula'] ?? '',
            'fecha'           => date('d/m/Y', $ts),
            'hora'            => date('g:i a', $ts),
            'descripcion'     => $r['descripcion'],
            'zona'            => ReporteAsistente::ZONAS[$r['zona']] ?? $r['zona'],
            'intensidad'      => (int) $r['intensidad'],
            'tiempo'          => ReporteAsistente::TIEMPOS[$r['tiempo']] ?? $r['tiempo'],
            'prioridad'       => $r['prioridad'],
            'prioridad_texto' => ucfirst($r['prioridad']),
            'estado'          => $r['estado'],
            'estado_texto'    => self::ESTADOS[$r['estado']] ?? $r['estado'],
            'criterios'       => ($r['criterios'] ?? '') !== ''
                                    ? array_values(array_filter(array_map('trim', explode(' | ', $r['criterios']))))
                                    : [],
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

        $this->responder([
            'ok'    => true,
            'total' => count($filas),
            'data'  => array_map([$this, 'formatear'], $filas),
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

        $this->responder(['ok' => true, 'data' => $this->formatear($r)]);
    }
}
