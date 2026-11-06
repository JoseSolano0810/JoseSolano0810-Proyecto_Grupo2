<?php

require_once ROOT_PATH . '/models/Reporte.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';

/**
 * ReporteController.php — REP-01 y REP-02
 * Reportes de la clínica. Solo para el administrador.
 */
class ReporteController
{
    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    private Reporte $model;

    public function __construct()
    {
        AuthService::requerir(['administrador']);

        if ((AuthService::usuarioActual()['rol'] ?? '') !== 'administrador') {
            Bitacora::registrar('ACCESO_DENEGADO', 'Intento de abrir reportes sin ser administrador');
            $this->responder(['ok' => false, 'error' => 'Solo el administrador puede generar reportes.'], 403);
        }

        $this->model = new Reporte();
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

    /**
     * Valida el período pedido y corta la petición si no sirve.
     * Escenario 3 de REP-01 y REP-02: fecha inicial mayor que la final.
     */
    private function periodo(): array
    {
        $desde = trim((string) ($_GET['desde'] ?? ''));
        $hasta = trim((string) ($_GET['hasta'] ?? ''));

        if ($desde === '' || $hasta === '') {
            $this->responder(['ok' => false, 'error' => 'Seleccione la fecha inicial y la fecha final del período.'], 400);
        }
        if (!$this->esFecha($desde) || !$this->esFecha($hasta)) {
            $this->responder(['ok' => false, 'error' => 'Las fechas no tienen un formato válido.'], 400);
        }
        if ($desde > $hasta) {
            $this->responder(['ok' => false, 'error' => 'El rango de fechas es inválido: la fecha inicial no puede ser mayor que la fecha final.'], 400);
        }
        if ((strtotime($hasta) - strtotime($desde)) / 86400 > 3660) {
            $this->responder(['ok' => false, 'error' => 'El período es demasiado largo. Elija un rango de hasta 10 años.'], 400);
        }

        return [
            'desde'     => $desde,
            'hasta'     => $hasta,
            'desde_fmt' => date('d/m/Y', strtotime($desde)),
            'hasta_fmt' => date('d/m/Y', strtotime($hasta)),
        ];
    }

    private function porcentaje(int $parte, int $total): float
    {
        return $total > 0 ? round($parte * 100 / $total, 1) : 0.0;
    }

    /** Etiqueta legible de cada punto de la serie: "6 nov" por día o "nov 2026" por mes */
    private function etiquetaSerie(string $periodo, string $agrupacion): string
    {
        if ($agrupacion === 'mes') {
            [$anio, $mes] = explode('-', $periodo);
            return self::MESES[(int) $mes - 1] . ' ' . $anio;
        }
        $t = strtotime($periodo);
        return (int) date('j', $t) . ' ' . self::MESES[(int) date('n', $t) - 1];
    }

    /* ── REP-02: reporte de citas ───────────────────────────── */

    /** GET desde, hasta (Y-m-d) */
    public function citas(): void
    {
        $periodo = $this->periodo();
        $datos   = $this->model->citas($periodo['desde'], $periodo['hasta']);

        Bitacora::registrar('REPORTE_CITAS', "Reporte de citas del {$periodo['desde_fmt']} al {$periodo['hasta_fmt']}");

        // Escenario 2: no hay citas en el período
        if ($datos['total'] === 0) {
            $this->responder([
                'ok'      => true,
                'vacio'   => true,
                'periodo' => $periodo,
                'mensaje' => 'No existen citas registradas para el período seleccionado.',
            ]);
        }

        $total = $datos['total'];
        $e     = $datos['por_estado'];
        $pend  = $e['programada'] + $e['confirmada'];

        $estados = [
            ['clave' => 'atendida',  'texto' => 'Atendidas',  'cantidad' => $e['atendida']],
            ['clave' => 'cancelada', 'texto' => 'Canceladas', 'cantidad' => $e['cancelada']],
            ['clave' => 'pendiente', 'texto' => 'Pendientes', 'cantidad' => $pend],
            ['clave' => 'ausente',   'texto' => 'No asistieron', 'cantidad' => $e['ausente']],
        ];
        foreach ($estados as &$x) {
            $x['porcentaje'] = $this->porcentaje($x['cantidad'], $total);
        }
        unset($x);

        $this->responder([
            'ok'      => true,
            'vacio'   => false,
            'periodo' => $periodo + ['dias' => $datos['dias']],
            'resumen' => [
                'total'      => $total,
                'atendidas'  => $e['atendida'],
                'canceladas' => $e['cancelada'],
                'pendientes' => $pend,
                'ausentes'   => $e['ausente'],
            ],
            'estados'     => $estados,
            'odontologos' => array_map(fn($o) => $o + ['porcentaje_atendidas' => $this->porcentaje($o['atendidas'], $o['total'])], $datos['odontologos']),
            'tipos'       => array_map(fn($t) => $t + ['porcentaje' => $this->porcentaje($t['cantidad'], $total)], $datos['tipos']),
            'serie'       => array_map(fn($p) => $p + ['etiqueta' => $this->etiquetaSerie($p['periodo'], $datos['agrupacion'])], $datos['serie']),
            'agrupacion'  => $datos['agrupacion'],
        ]);
    }
}
