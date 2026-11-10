<?php

require_once ROOT_PATH . '/models/Cotizacion.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';

class CotizacionController
{
    private Cotizacion $model;
    private const IVA = 0.13;

    public function __construct()
    {
        AuthService::requerir(['recepcionista', 'administrador', 'odontologo']);
        $this->model = new Cotizacion();
    }

    /* ── GET (para cargarPanel) ──────────────────────────────────── */

    /**
     * Retorna todas las cotizaciones formateadas para la vista PHP.
     * Siempre devuelve un array (nunca lanza excepción pública).
     */
    public function listar(): array
    {
        try {
            $rows = $this->model->obtenerTodas();
            $out  = [];
            foreach ($rows as $r) {
                $out[] = [
                    'id_cotizacion'      => (int) $r['id_cotizacion'],
                    'id_tratamiento'     => (int) $r['id_tratamiento'],
                    'id_paciente'        => (int) $r['id_paciente'],
                    'paciente'           => $r['paciente_nombre']    ?? '—',
                    'tratamiento'        => $r['tratamiento_nombre'] ?? '—',
                    'descripcion'        => $r['descripcion']        ?? '',
                    'piezas_dentales'    => $r['piezas_dentales']    ?? '',
                    'tasa_iva'           => (float) ($r['tasa_iva'] ?? 13),
                    'subtotal'           => (float) $r['subtotal'],
                    'descuento'          => (float) $r['descuento'],
                    'iva'                => (float) $r['iva'],
                    'total'              => (float) $r['total'],
                    'estado'             => $r['estado'],
                    'observaciones'      => $r['observaciones'] ?? '',
                    'fecha'              => date('d/m/Y', strtotime($r['created_at'])),
                    'fecha_decision'     => !empty($r['fecha_decision']) ? date('d/m/Y H:i', strtotime($r['fecha_decision'])) : null,
                    'observaciones_decision' => $r['observaciones_decision'] ?? '',
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            // Si la tabla aún no existe en el primer arranque devolvemos []
            return [];
        }
    }

    /* ── JSON endpoints ─────────────────────────────────────────── */

    /**
     * GET ?accion=cotizaciones.listarJson
     * Devuelve el mismo array pero en JSON (para recargas AJAX).
     */
    public function listarJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->listar());
    }

    /**
     * GET ?accion=cotizaciones.tratamientosDisponibles
     * Lista los tratamientos sin cotización para el selector del modal.
     */
    public function tratamientosDisponibles(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $rows   = $this->model->tratamientosSinCotizacion();
            $piezas = $this->model->piezasPorTratamiento(array_column($rows, 'id_tratamiento'));
            $out    = [];
            foreach ($rows as $r) {
                $out[] = [
                    'id_tratamiento'  => (int) $r['id_tratamiento'],
                    'nombre'          => $r['nombre'],
                    'costo_estimado'  => (float) ($r['costo_estimado'] ?? 0),
                    'id_paciente'     => (int) $r['id_paciente'],
                    'paciente_nombre' => $r['paciente_nombre'],
                    'estado'          => $r['estado'],
                    'piezas'          => $piezas[(int) $r['id_tratamiento']] ?? [],
                ];
            }
            echo json_encode(['ok' => true, 'tratamientos' => $out]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        }
    }

    /**
     * POST ?accion=cotizaciones.crear
     * Body JSON: id_tratamiento, id_paciente, descripcion,
     *            subtotal, descuento, observaciones
     */
    public function crear(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $datos  = $this->bodyJson();
            $sesion = AuthService::usuarioActual();

            // Validar campos obligatorios (FIN-01 esc. 2): se informa CUÁLES faltan
            $idTratamiento = (int) ($datos['id_tratamiento'] ?? 0);
            $idPaciente    = (int) ($datos['id_paciente']    ?? 0);
            $descripcion   = trim($datos['descripcion']      ?? '');
            $piezas        = trim((string) ($datos['piezas_dentales'] ?? ''));
            $subtotal      = (float) ($datos['subtotal']     ?? 0);
            $descuento     = (float) ($datos['descuento']    ?? 0);
            $tasaTxt       = trim((string) ($datos['iva_porcentaje'] ?? ''));
            $tasa          = $tasaTxt === '' ? self::IVA * 100 : (float) $tasaTxt;

            $faltan = [];
            if (!$idPaciente)    $faltan[] = 'paciente';
            if (!$idTratamiento) $faltan[] = 'tratamiento';
            if ($piezas === '')  $faltan[] = 'piezas dentales';
            if ($descripcion === '') $faltan[] = 'descripción';
            if ($subtotal <= 0)  $faltan[] = 'subtotal (mayor a 0)';
            if ($faltan) {
                http_response_code(400);
                echo json_encode([
                    'ok'        => false,
                    'faltantes' => $faltan,
                    'mensaje'   => 'Complete los campos obligatorios: ' . implode(', ', $faltan) . '.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $piezasNorm = $this->normalizarPiezas($piezas);
            if ($piezasNorm === null) {
                http_response_code(400);
                echo json_encode([
                    'ok'      => false,
                    'mensaje' => 'Las piezas dentales no son válidas. Use numeración FDI separada por comas (ej.: 16, 26, 36).',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            if ($tasa < 0 || $tasa > 100) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'El porcentaje de impuesto debe estar entre 0 y 100.']);
                return;
            }
            if ($descuento < 0 || $descuento > $subtotal) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'El descuento no puede ser negativo ni mayor al subtotal.']);
                return;
            }

            // Calcular montos
            $base  = $subtotal - $descuento;
            $iva   = round($base * ($tasa / 100), 2);
            $total = round($base + $iva, 2);

            $idCotizacion = $this->model->crear([
                'id_tratamiento'  => $idTratamiento,
                'id_paciente'     => $idPaciente,
                'descripcion'     => $descripcion,
                'piezas_dentales' => $piezasNorm,
                'subtotal'        => $subtotal,
                'descuento'       => $descuento,
                'tasa_iva'        => $tasa,
                'iva'             => $iva,
                'total'           => $total,
                'observaciones'   => $datos['observaciones'] ?? '',
                'id_creado_por'   => $sesion['id'],
            ]);

            Bitacora::registrar(
                'COTIZACION_CREADA',
                "Cotización #{$idCotizacion} creada para tratamiento #{$idTratamiento} (total ₡{$total})",
                $sesion['id'],
                $sesion['nombre_usuario']
            );

            echo json_encode([
                'ok'           => true,
                'id_cotizacion' => $idCotizacion,
                'total'        => $total,
                'mensaje'      => 'Cotización creada correctamente.',
            ]);
        } catch (\PDOException $e) {
            // Violación de UNIQUE (tratamiento ya tiene cotización)
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '23000')) {
                http_response_code(409);
                echo json_encode(['ok' => false, 'mensaje' => 'Este tratamiento ya tiene una cotización asignada.']);
            } else {
                http_response_code(500);
                echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar: ' . $e->getMessage()]);
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => 'Error interno: ' . $e->getMessage()]);
        }
    }

    /**
     * POST ?accion=cotizaciones.estado   (FIN-02)
     * Body: id_cotizacion, estado (aprobada|rechazada), observaciones (opcional)
     * Solo una cotización PENDIENTE puede aprobarse o rechazarse. Se guarda fecha y observaciones.
     */
    public function cambiarEstado(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $datos        = $this->bodyJson();
            $idCotizacion = (int) ($datos['id_cotizacion'] ?? 0);
            $estado       = trim($datos['estado']          ?? '');
            $obs          = trim((string) ($datos['observaciones'] ?? ''));

            if (!$idCotizacion || !in_array($estado, ['aprobada', 'rechazada'], true)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'Seleccione la cotización y el estado «Aprobada» o «Rechazada».']);
                return;
            }
            if (mb_strlen($obs) > 500) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'Las observaciones no pueden superar 500 caracteres.']);
                return;
            }

            $cot = $this->model->obtenerPorId($idCotizacion);
            if (!$cot) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'mensaje' => 'Cotización no encontrada.']);
                return;
            }
            if ($cot['estado'] !== 'pendiente') {
                http_response_code(409);
                echo json_encode(['ok' => false, 'mensaje' => "Solo se puede cambiar el estado de una cotización pendiente (esta está '{$cot['estado']}')."]);
                return;
            }

            $sesion = AuthService::usuarioActual();
            $this->model->cambiarEstado($idCotizacion, $estado, $obs, (int) $sesion['id']);

            Bitacora::registrar(
                'COTIZACION_ESTADO',
                "Cotización #{$idCotizacion} cambió a estado '{$estado}'" . ($obs !== '' ? ' — ' . mb_substr($obs, 0, 120) : ''),
                $sesion['id'],
                $sesion['nombre_usuario']
            );

            echo json_encode([
                'ok'      => true,
                'estado'  => $estado,
                'fecha'   => date('d/m/Y H:i'),
                'mensaje' => $estado === 'aprobada' ? 'Cotización aprobada correctamente.' : 'Cotización rechazada. El registro se conserva en el historial.',
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        }
    }

    /**
     * GET ?accion=cotizaciones.detalle&id=N   (FIN-01 esc. 3)
     * Detalle completo de una cotización ya registrada, para consultarla o imprimirla.
     */
    public function detalle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $id  = (int) ($_GET['id'] ?? 0);
        $cot = $id ? $this->model->obtenerPorId($id) : null;
        if (!$cot) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'mensaje' => 'Cotización no encontrada.']);
            return;
        }

        echo json_encode([
            'ok'   => true,
            'data' => [
                'id_cotizacion'   => (int) $cot['id_cotizacion'],
                'paciente'        => $cot['paciente_nombre'] ?? '—',
                'tratamiento'     => $cot['tratamiento_nombre'] ?? '—',
                'descripcion'     => $cot['descripcion'] ?? '',
                'piezas_dentales' => $cot['piezas_dentales'] ?? '',
                'subtotal'        => (float) $cot['subtotal'],
                'descuento'       => (float) $cot['descuento'],
                'tasa_iva'        => (float) ($cot['tasa_iva'] ?? 13),
                'iva'             => (float) $cot['iva'],
                'total'           => (float) $cot['total'],
                'estado'          => $cot['estado'],
                'observaciones'   => $cot['observaciones'] ?? '',
                'fecha'           => date('d/m/Y', strtotime($cot['created_at'])),
                'fecha_decision'  => !empty($cot['fecha_decision']) ? date('d/m/Y H:i', strtotime($cot['fecha_decision'])) : null,
                'observaciones_decision' => $cot['observaciones_decision'] ?? '',
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Valida numeración FDI (permanentes 11-48, temporales 51-85), separada por comas/espacios.
     * Devuelve el texto normalizado ("16, 26") o null si algo no es válido.
     */
    private function normalizarPiezas(string $texto): ?string
    {
        $tokens = preg_split('/[\s,;]+/', $texto, -1, PREG_SPLIT_NO_EMPTY);
        if (!$tokens) return null;

        $validas = [];
        foreach ($tokens as $t) {
            if (!preg_match('/^[1-8][1-8]$/', $t)) return null;
            $cuadrante = (int) $t[0];
            $num       = (int) $t[1];
            $max       = $cuadrante <= 4 ? 8 : 5;
            if ($num < 1 || $num > $max) return null;
            $validas[$t] = true;
        }
        return implode(', ', array_keys($validas));
    }

    /* ── Helper ─────────────────────────────────────────────────── */

    private function bodyJson(): array
    {
        // Soporta tanto JSON (odontologo/guardarJSON) como FormData (recepcionista)
        if (!empty($_POST)) {
            return $_POST;
        }
        $raw  = file_get_contents('php://input');
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}