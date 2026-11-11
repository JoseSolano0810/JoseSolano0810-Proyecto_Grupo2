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

    /* ── REP-01: reporte financiero ─────────────────────────── */

    /** GET desde, hasta (Y-m-d) */
    public function financiero(): void
    {
        $periodo = $this->periodo();

        $metodo = trim((string) ($_GET['metodo'] ?? ''));

        if ($metodo !== '' && !array_key_exists($metodo, Reporte::METODOS)) {
            $this->responder([
                'ok' => false,
                'error' => 'El método de pago seleccionado no es válido.',
            ], 400);
        }

        $datos = $this->model->financiero(
            $periodo['desde'],
            $periodo['hasta'],
            $metodo
        );

        $metodoTexto = $metodo !== ''
            ? (Reporte::METODOS[$metodo] ?? $metodo)
            : 'Todos los métodos';

        Bitacora::registrar(
            'REPORTE_FINANCIERO',
            "Reporte financiero del {$periodo['desde_fmt']} al {$periodo['hasta_fmt']} - Método: {$metodoTexto}"
        );

        $totalIngresos = round(
            array_sum(array_column($datos['metodos'], 'total')),
            2
        );

        $totalEgresos = round(
            array_sum(array_column($datos['categorias'], 'total')),
            2
        );

        $nPagos = (int) array_sum(
            array_column($datos['metodos'], 'cantidad')
        );

        $nEgresos = (int) array_sum(
            array_column($datos['categorias'], 'cantidad')
        );

        $totalPorCobrar = round(
            array_sum(
                array_column(
                    $datos['cuentas_por_cobrar'],
                    'saldo'
                )
            ),
            2
        );

        if ($nPagos === 0 && $nEgresos === 0) {
            $this->responder([
                'ok' => true,
                'vacio' => true,
                'periodo' => $periodo,
                'pagos_disponibles' => $datos['pagos_disponibles'],
                'metodo' => $metodo,
                'metodo_texto' => $metodoTexto,
                'mensaje' => $metodo !== ''
                    ? "No existen ingresos registrados con {$metodoTexto} para el período seleccionado."
                    : 'No existen datos financieros para el período seleccionado.',
            ]);
        }

        $conPorcentaje = function (
            array $filas,
            array $etiquetas,
            float $total
        ): array {
            return array_map(
                fn($f) => [
                    'clave' => $f['clave'],
                    'texto' => $etiquetas[$f['clave']] ?? $f['clave'],
                    'cantidad' => $f['cantidad'],
                    'total' => $f['total'],
                    'porcentaje' => $total > 0
                        ? round($f['total'] * 100 / $total, 1)
                        : 0.0,
                ],
                $filas
            );
        };

        $this->responder([
            'ok' => true,
            'vacio' => false,

            'periodo' => $periodo + [
                'dias' => $datos['dias']
            ],

            'metodo' => $metodo,
            'metodo_texto' => $metodoTexto,

            'pagos_disponibles' => $datos['pagos_disponibles'],

            'aviso' => $datos['pagos_disponibles']
                ? null
                : 'Todavía no hay pagos registrados en el sistema, por eso los ingresos aparecen en cero. Se sumarán apenas el módulo de pagos empiece a guardarlos.',

            'resumen' => [
                'ingresos' => $totalIngresos,
                'egresos' => $totalEgresos,
                'balance' => round(
                    $totalIngresos - $totalEgresos,
                    2
                ),
                'por_cobrar' => $totalPorCobrar,
                'n_pagos' => $nPagos,
                'n_egresos' => $nEgresos,
                'n_cuentas_cobrar' => count(
                    $datos['cuentas_por_cobrar']
                ),
            ],

            'metodos' => $conPorcentaje(
                $datos['metodos'],
                Reporte::METODOS,
                $totalIngresos
            ),

            'categorias' => $conPorcentaje(
                $datos['categorias'],
                Reporte::CATEGORIAS,
                $totalEgresos
            ),

            'cuentas_por_cobrar' => $datos['cuentas_por_cobrar'],

            'serie' => array_map(
                fn($p) => $p + [
                    'etiqueta' => $this->etiquetaSerie(
                        $p['periodo'],
                        $datos['agrupacion']
                    )
                ],
                $datos['serie']
            ),

            'agrupacion' => $datos['agrupacion'],

            'gastos' => array_map(
                fn($g) => [
                    'id' => (int) $g['id_egreso'],
                    'fecha_fmt' => date(
                        'd/m/Y',
                        strtotime($g['fecha'])
                    ),
                    'categoria' => Reporte::CATEGORIAS[$g['categoria']]
                        ?? $g['categoria'],
                    'concepto' => $g['concepto'],
                    'monto' => round(
                        (float) $g['monto'],
                        2
                    ),
                    'observaciones' => $g['observaciones'] ?? '',
                    'registrado_por' => $g['registrado_por'],
                ],
                $datos['gastos']
            ),
        ]);
    }

    /* ── Egresos: registrar y eliminar gastos ───────────────── */

    private function entrada(): array
    {
        $d = json_decode(file_get_contents('php://input'), true);
        return is_array($d) ? $d : [];
    }

    /** POST {fecha, categoria, concepto, monto, observaciones} */
    public function egresoCrear(): void
    {
        $d         = $this->entrada();
        $fecha     = trim((string) ($d['fecha'] ?? ''));
        $categoria = trim((string) ($d['categoria'] ?? ''));
        $concepto  = trim((string) ($d['concepto'] ?? ''));
        $montoTxt  = trim((string) ($d['monto'] ?? ''));
        $obs       = trim((string) ($d['observaciones'] ?? ''));

        $faltan = [];
        if ($fecha === '')     $faltan[] = 'fecha';
        if ($categoria === '') $faltan[] = 'categoría';
        if ($concepto === '')  $faltan[] = 'concepto';
        if ($montoTxt === '')  $faltan[] = 'monto';
        if ($faltan) {
            $this->responder(['ok' => false, 'error' => 'Para registrar el gasto falta completar: ' . implode(', ', $faltan) . '.'], 400);
        }

        if (!$this->esFecha($fecha)) {
            $this->responder(['ok' => false, 'error' => 'La fecha del gasto no es válida.'], 400);
        }
        if ($fecha > date('Y-m-d')) {
            $this->responder(['ok' => false, 'error' => 'La fecha del gasto no puede ser futura.'], 400);
        }
        if (!array_key_exists($categoria, Reporte::CATEGORIAS)) {
            $this->responder(['ok' => false, 'error' => 'La categoría seleccionada no es válida.'], 400);
        }
        if (mb_strlen($concepto) > 150 || mb_strlen($obs) > 500) {
            $this->responder(['ok' => false, 'error' => 'El concepto (150) o las observaciones (500) superan el largo permitido.'], 400);
        }

        $montoTxt = str_replace([' ', '₡'], '', $montoTxt);
        if (!is_numeric($montoTxt) || (float) $montoTxt <= 0 || (float) $montoTxt > 99999999.99) {
            $this->responder(['ok' => false, 'error' => 'El monto debe ser un número mayor que cero.'], 400);
        }
        $monto = round((float) $montoTxt, 2);

        try {
            $id = $this->model->crearEgreso([
                'fecha' => $fecha,
                'categoria' => $categoria,
                'concepto' => $concepto,
                'monto' => $monto,
                'observaciones' => $obs,
                'id_registrado_por' => (int) (AuthService::usuarioActual()['id'] ?? 0),
            ]);
            Bitacora::registrar('EGRESO_REGISTRADO', "Gasto #{$id} «" . mb_substr($concepto, 0, 80) . "» por ₡" . number_format($monto, 2, ',', '.'));
            $this->responder(['ok' => true, 'id' => $id, 'mensaje' => 'El gasto quedó registrado.'], 201);
        } catch (Throwable $e) {
            error_log('REP-01 egresoCrear: ' . $e->getMessage());
            $this->responder(['ok' => false, 'error' => 'No pudimos registrar el gasto. Intente de nuevo.'], 500);
        }
    }

    /** POST {id} */
    public function egresoEliminar(): void
    {
        $id = (int) ($this->entrada()['id'] ?? 0);
        $e  = $id ? $this->model->obtenerEgreso($id) : null;
        if (!$e) {
            $this->responder(['ok' => false, 'error' => 'No encontramos ese gasto.'], 404);
        }

        try {
            $this->model->eliminarEgreso($id);
            Bitacora::registrar('EGRESO_ELIMINADO', "Gasto #{$id} «" . mb_substr($e['concepto'], 0, 80) . "» eliminado");
            $this->responder(['ok' => true, 'mensaje' => 'El gasto se eliminó.']);
        } catch (Throwable $err) {
            error_log('REP-01 egresoEliminar: ' . $err->getMessage());
            $this->responder(['ok' => false, 'error' => 'No pudimos eliminar el gasto.'], 500);
        }
    }
}
