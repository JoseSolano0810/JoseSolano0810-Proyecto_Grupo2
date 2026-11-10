<?php

require_once ROOT_PATH . '/models/Pago.php';
require_once ROOT_PATH . '/models/Cotizacion.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';

class PagoController
{
    private Pago       $model;
    private Cotizacion $cotizacionModel;

    public function __construct()
    {
        AuthService::requerir([
            'recepcionista',
            'asistente_dental',
            'administrador',
            'odontologo'
        ]);

        $this->model           = new Pago();
        $this->cotizacionModel = new Cotizacion();
    }

    /* ── Para cargarPanel ────────────────────────────────────────── */

    /**
     * Devuelve todos los pagos formateados para la vista PHP.
     */
    public function listar(): array
    {
        try {
            $rows = $this->model->obtenerTodos();
            $out  = [];

            foreach ($rows as $r) {
                $out[] = [
                    'id_pago'            => (int) $r['id_pago'],
                    'id_cotizacion'      => (int) $r['id_cotizacion'],
                    'paciente'           => $r['paciente_nombre'] ?? '—',
                    'monto'              => (float) $r['monto'],
                    'metodo'             => ucfirst($r['metodo']),
                    'referencia'         => $r['referencia'] ?? '',
                    'fecha'              => date(
                        'd/m/Y',
                        strtotime($r['fecha_pago'])
                    ),
                    'cotizacion_total'   => (float) $r['cotizacion_total'],
                    'cotizacion_estado'  => $r['cotizacion_estado'],
                ];
            }

            return $out;

        } catch (\Throwable $e) {
            return [];
        }
    }

    /* ── FIN-05 Estado financiero del paciente ──────────────────── */

    /**
     * GET ?accion=pagos.estadoFinanciero&id_paciente=1
     */
    public function estadoFinanciero(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $idPaciente = (int) ($_GET['id_paciente'] ?? 0);

            if ($idPaciente <= 0) {
                http_response_code(400);

                echo json_encode([
                    'ok'      => false,
                    'mensaje' => 'Debe indicar un paciente válido.',
                ]);

                return;
            }

            $datos = $this->model->estadoFinancieroPaciente($idPaciente);

            if (!$datos) {
                http_response_code(404);

                echo json_encode([
                    'ok'      => false,
                    'mensaje' => 'Paciente no encontrado.',
                ]);

                return;
            }

            echo json_encode(
                [
                    'ok' => true,
                    'data' => $datos,
                ],
                JSON_UNESCAPED_UNICODE
            );

        } catch (\Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'ok'      => false,
                'mensaje' => 'No se pudo consultar el estado financiero.',
            ]);
        }
    }

    /* ── JSON endpoints ─────────────────────────────────────────── */

    /**
     * POST ?accion=pagos.registrar
     */
    public function registrar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $datos  = $this->bodyJson();
            $sesion = AuthService::usuarioActual();

            $idCotizacion = (int) ($datos['id_cotizacion'] ?? 0);
            $monto        = (float) ($datos['monto'] ?? 0);
            $metodo       = trim($datos['metodo'] ?? '');
            $fechaPago    = trim(
                $datos['fecha_pago'] ?? date('Y-m-d')
            );

            $metodosValidos = [
                'sinpe',
                'efectivo',
                'tarjeta',
                'transferencia'
            ];

            if ($monto <= 0) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'mensaje' => 'El monto ingresado no es válido: debe ser mayor a cero.']);
                return;
            }

            if (
                !$idCotizacion ||
                !in_array($metodo, $metodosValidos, true)
            ) {
                http_response_code(400);

                echo json_encode([
                    'ok'      => false,
                    'mensaje' => 'La cotización y un método de pago válido (sinpe/efectivo/tarjeta/transferencia) son requeridos.',
                ]);

                return;
            }

            $cotizacion = $this->cotizacionModel->obtenerPorId(
                $idCotizacion
            );

            if (!$cotizacion) {
                http_response_code(404);

                echo json_encode([
                    'ok'      => false,
                    'mensaje' => 'Cotización no encontrada.',
                ]);

                return;
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPago) || strtotime($fechaPago) === false) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'La fecha del pago no es válida.']);
                return;
            }

            // FIN-03 esc. 2: la validación del monto vs. saldo se hace en el servidor
            // (dentro de una transacción), no solo en el navegador.
            try {
                $r = $this->model->crearValidado([
                    'id_cotizacion'     => $idCotizacion,
                    'monto'             => $monto,
                    'metodo'            => $metodo,
                    'referencia'        => $datos['referencia'] ?? '',
                    'observaciones'     => $datos['observaciones'] ?? '',
                    'id_registrado_por' => $sesion['id'],
                    'fecha_pago'        => $fechaPago,
                ]);
            } catch (\DomainException $e) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                return;
            }

            $idPago      = $r['id_pago'];
            $totalPagado = $r['total_pagado'];
            $totalCot    = $r['total'];
            $saldoPend   = $r['saldo_pendiente'];

            if ($saldoPend <= 0.009) {
                $this->cotizacionModel->cambiarEstado($idCotizacion, 'pagada');
            }

            Bitacora::registrar(
                'PAGO_REGISTRADO',
                "Pago #{$idPago} de ₡{$monto} ({$metodo}) en cotización #{$idCotizacion}. Saldo pendiente: ₡{$saldoPend}",
                $sesion['id'],
                $sesion['nombre_usuario']
            );

            echo json_encode([
                'ok'                => true,
                'id_pago'           => $idPago,
                'total_pagado'      => $totalPagado,
                'saldo_pendiente'   => $saldoPend,
                'estado_cotizacion' => $saldoPend <= 0.009 ? 'pagada' : $cotizacion['estado'],
                'mensaje'           => 'Pago registrado correctamente.',
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'ok'      => false,
                'mensaje' => $e->getMessage(),
            ]);
        }
    }

    /* ── Helper ─────────────────────────────────────────────────── */

    private function bodyJson(): array
    {
        if (!empty($_POST)) {
            return $_POST;
        }

        $raw = file_get_contents('php://input');

        if (!$raw) {
            return [];
        }

        $data = json_decode($raw, true);

        return is_array($data)
            ? $data
            : [];
    }
}