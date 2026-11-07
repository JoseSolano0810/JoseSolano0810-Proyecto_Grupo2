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
                    'subtotal'           => (float) $r['subtotal'],
                    'descuento'          => (float) $r['descuento'],
                    'iva'                => (float) $r['iva'],
                    'total'              => (float) $r['total'],
                    'estado'             => $r['estado'],
                    'observaciones'      => $r['observaciones'] ?? '',
                    'fecha'              => date('d/m/Y', strtotime($r['created_at'])),
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
            $rows = $this->model->tratamientosSinCotizacion();
            $out  = [];
            foreach ($rows as $r) {
                $out[] = [
                    'id_tratamiento'  => (int) $r['id_tratamiento'],
                    'nombre'          => $r['nombre'],
                    'costo_estimado'  => (float) ($r['costo_estimado'] ?? 0),
                    'id_paciente'     => (int) $r['id_paciente'],
                    'paciente_nombre' => $r['paciente_nombre'],
                    'estado'          => $r['estado'],
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

            // Validar campos obligatorios
            $idTratamiento = (int) ($datos['id_tratamiento'] ?? 0);
            $idPaciente    = (int) ($datos['id_paciente']    ?? 0);
            $descripcion   = trim($datos['descripcion']      ?? '');
            $subtotal      = (float) ($datos['subtotal']     ?? 0);
            $descuento     = (float) ($datos['descuento']    ?? 0);

            if (!$idTratamiento || !$idPaciente || $descripcion === '') {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'Tratamiento, paciente y descripción son requeridos.']);
                return;
            }
            if ($subtotal <= 0) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'El subtotal debe ser mayor a 0.']);
                return;
            }
            if ($descuento < 0 || $descuento > $subtotal) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'El descuento no puede ser negativo ni mayor al subtotal.']);
                return;
            }

            // Calcular montos
            $base  = $subtotal - $descuento;
            $iva   = round($base * self::IVA, 2);
            $total = round($base + $iva, 2);

            $idCotizacion = $this->model->crear([
                'id_tratamiento'  => $idTratamiento,
                'id_paciente'     => $idPaciente,
                'descripcion'     => $descripcion,
                'subtotal'        => $subtotal,
                'descuento'       => $descuento,
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
     * POST ?accion=cotizaciones.estado
     * Body JSON: id_cotizacion, estado (pendiente|aprobada|rechazada|pagada)
     */
    public function cambiarEstado(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $datos        = $this->bodyJson();
            $idCotizacion = (int) ($datos['id_cotizacion'] ?? 0);
            $estado       = trim($datos['estado']          ?? '');

            $permitidos = ['pendiente', 'aprobada', 'rechazada', 'pagada'];
            if (!$idCotizacion || !in_array($estado, $permitidos, true)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'mensaje' => 'id_cotizacion y estado válido son requeridos.']);
                return;
            }

            $this->model->cambiarEstado($idCotizacion, $estado);

            $sesion = AuthService::usuarioActual();
            Bitacora::registrar(
                'COTIZACION_ESTADO',
                "Cotización #{$idCotizacion} cambió a estado '{$estado}'",
                $sesion['id'],
                $sesion['nombre_usuario']
            );

            echo json_encode(['ok' => true, 'mensaje' => "Estado actualizado a '{$estado}'."]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
        }
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