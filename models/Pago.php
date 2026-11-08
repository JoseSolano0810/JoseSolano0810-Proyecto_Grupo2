<?php

require_once ROOT_PATH . '/database/Database.php';

class Pago
{
    private PDO $db;
    private static bool $tablasListas = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTablas();
    }

    /* ── Creación automática de tabla ───────────────────────────── */

    private function asegurarTablas(): void
    {
        if (self::$tablasListas) return;

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS pago (
                id_pago        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_cotizacion  INT UNSIGNED NOT NULL,
                monto          DECIMAL(12,2) NOT NULL,
                metodo         ENUM('sinpe','efectivo','tarjeta','transferencia')
                               NOT NULL DEFAULT 'efectivo',
                referencia     VARCHAR(100) NULL COMMENT 'Número SINPE, voucher, etc.',
                observaciones  TEXT NULL,
                id_registrado_por INT NOT NULL,
                fecha_pago     DATE NOT NULL,
                created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$tablasListas = true;
    }

    /* ── Lecturas ────────────────────────────────────────────────── */

    /**
     * Lista todos los pagos con datos de cotización y paciente.
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT pa.*,
                    c.total           AS cotizacion_total,
                    c.estado          AS cotizacion_estado,
                    u.nombre_completo AS paciente_nombre
             FROM pago pa
             JOIN cotizacion c ON c.id_cotizacion = pa.id_cotizacion
             JOIN paciente  p  ON p.id_paciente   = c.id_paciente
             JOIN usuario   u  ON u.id_usuario    = p.id_usuario
             ORDER BY pa.fecha_pago DESC, pa.created_at DESC"
        );

        return $stmt->fetchAll();
    }

    /**
     * Suma de pagos registrados para una cotización.
     */
    public function totalPagado(int $cotizacionId): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0)
             FROM pago
             WHERE id_cotizacion = ?"
        );

        $stmt->execute([$cotizacionId]);

        return (float) $stmt->fetchColumn();
    }

    /**
     * Lista de pagos de una cotización específica.
     */
    public function porCotizacion(int $cotizacionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM pago
             WHERE id_cotizacion = ?
             ORDER BY fecha_pago ASC"
        );

        $stmt->execute([$cotizacionId]);

        return $stmt->fetchAll();
    }

    /**
     * FIN-05
     * Obtiene el estado financiero completo de un paciente.
     */
    public function estadoFinancieroPaciente(int $idPaciente): ?array
    {
        /* ── Datos del paciente ──────────────────────────────── */

        $stmtPaciente = $this->db->prepare(
            "SELECT
                p.id_paciente,
                p.id_usuario,
                u.nombre_completo,
                u.identificacion,
                u.telefono,
                u.correo
             FROM paciente p
             JOIN usuario u ON u.id_usuario = p.id_usuario
             WHERE p.id_paciente = ?
             LIMIT 1"
        );

        $stmtPaciente->execute([$idPaciente]);
        $paciente = $stmtPaciente->fetch();

        if (!$paciente) {
            return null;
        }

        /* ── Cotizaciones del paciente ───────────────────────── */

        $stmtCotizaciones = $this->db->prepare(
            "SELECT
                c.*,
                t.nombre AS tratamiento_nombre,
                (
                    SELECT COALESCE(SUM(pa.monto), 0)
                    FROM pago pa
                    WHERE pa.id_cotizacion = c.id_cotizacion
                ) AS total_pagado
             FROM cotizacion c
             LEFT JOIN tratamiento t
                    ON t.id_tratamiento = c.id_tratamiento
             WHERE c.id_paciente = ?
             ORDER BY c.id_cotizacion DESC"
        );

        $stmtCotizaciones->execute([$idPaciente]);
        $cotizacionesRaw = $stmtCotizaciones->fetchAll();

        $cotizaciones = [];

        $totalCotizado = 0.0;
        $totalPagado   = 0.0;
        $saldoTotal    = 0.0;

        foreach ($cotizacionesRaw as $c) {
            $total       = (float) ($c['total'] ?? 0);
            $pagado      = (float) ($c['total_pagado'] ?? 0);
            $saldo       = $total - $pagado;
            $estado      = $c['estado'] ?? 'pendiente';

            /*
             * Una cotización rechazada no representa una deuda
             * activa del paciente.
             */
            if ($estado !== 'rechazada') {
                $totalCotizado += $total;
                $totalPagado   += $pagado;
                $saldoTotal    += $saldo;
            }

            $fechaRaw =
                $c['fecha_cotizacion']
                ?? $c['fecha']
                ?? $c['created_at']
                ?? null;

            $fecha = $fechaRaw
                ? date('d/m/Y', strtotime($fechaRaw))
                : '—';

            $cotizaciones[] = [
                'id_cotizacion' => (int) $c['id_cotizacion'],
                'id_paciente'    => (int) $c['id_paciente'],
                'id_tratamiento' => isset($c['id_tratamiento'])
                    ? (int) $c['id_tratamiento']
                    : null,
                'tratamiento'    => $c['tratamiento_nombre'] ?? '—',
                'fecha'          => $fecha,
                'total'          => $total,
                'pagado'         => $pagado,
                'saldo'          => $saldo,
                'estado'         => $estado,
            ];
        }

        /* ── Pagos del paciente ──────────────────────────────── */

        $stmtPagos = $this->db->prepare(
            "SELECT
                pa.id_pago,
                pa.id_cotizacion,
                pa.monto,
                pa.metodo,
                pa.referencia,
                pa.observaciones,
                pa.fecha_pago,
                c.estado AS cotizacion_estado
             FROM pago pa
             JOIN cotizacion c
                  ON c.id_cotizacion = pa.id_cotizacion
             WHERE c.id_paciente = ?
             ORDER BY pa.fecha_pago DESC, pa.created_at DESC"
        );

        $stmtPagos->execute([$idPaciente]);

        $pagos = [];

        foreach ($stmtPagos->fetchAll() as $pago) {
            $pagos[] = [
                'id_pago'           => (int) $pago['id_pago'],
                'id_cotizacion'     => (int) $pago['id_cotizacion'],
                'monto'             => (float) $pago['monto'],
                'metodo'            => ucfirst($pago['metodo']),
                'referencia'        => $pago['referencia'] ?? '',
                'observaciones'     => $pago['observaciones'] ?? '',
                'fecha'             => date(
                    'd/m/Y',
                    strtotime($pago['fecha_pago'])
                ),
                'cotizacion_estado' => $pago['cotizacion_estado'],
            ];
        }

        /* ── Estado financiero general ───────────────────────── */

        $tieneMovimientos =
            count($cotizaciones) > 0 ||
            count($pagos) > 0;

        if (!$tieneMovimientos) {
            $estadoFinanciero = 'sin_movimientos';
        } elseif ($saldoTotal > 0.009) {
            $estadoFinanciero = 'con_deuda';
        } elseif ($saldoTotal < -0.009) {
            /*
             * Lo dejamos preparado para FIN-04 escenario
             * saldo a favor, aunque por ahora FIN-03
             * impide sobrepagos.
             */
            $estadoFinanciero = 'saldo_favor';
        } else {
            $estadoFinanciero = 'al_dia';
        }

        return [
            'paciente' => [
                'id_paciente'     => (int) $paciente['id_paciente'],
                'id_usuario'      => (int) $paciente['id_usuario'],
                'nombre'          => $paciente['nombre_completo'],
                'identificacion'  => $paciente['identificacion'],
                'telefono'        => $paciente['telefono'] ?? '',
                'correo'          => $paciente['correo'] ?? '',
            ],

            'resumen' => [
                'total_cotizado'   => $totalCotizado,
                'total_pagado'     => $totalPagado,
                'saldo'            => $saldoTotal,
                'estado'           => $estadoFinanciero,
                'tiene_movimientos' => $tieneMovimientos,
            ],

            'cotizaciones' => $cotizaciones,
            'pagos'        => $pagos,
        ];
    }

    /* ── Escritura ───────────────────────────────────────────────── */

    /**
     * Registra un pago.
     */
    public function crear(array $datos): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO pago
                (
                    id_cotizacion,
                    monto,
                    metodo,
                    referencia,
                    observaciones,
                    id_registrado_por,
                    fecha_pago
                )
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            (int) $datos['id_cotizacion'],
            (float) $datos['monto'],
            $datos['metodo'],
            ($datos['referencia'] ?? '') ?: null,
            ($datos['observaciones'] ?? '') ?: null,
            (int) $datos['id_registrado_por'],
            $datos['fecha_pago'],
        ]);

        return (int) $this->db->lastInsertId();
    }
}