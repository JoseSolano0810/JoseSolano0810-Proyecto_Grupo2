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
                    c.total          AS cotizacion_total,
                    c.estado         AS cotizacion_estado,
                    u.nombre_completo AS paciente_nombre
             FROM pago pa
             JOIN cotizacion c ON c.id_cotizacion = pa.id_cotizacion
             JOIN paciente  p  ON p.id_paciente   = c.id_paciente
             JOIN usuario   u  ON u.id_usuario    = p.id_usuario
             ORDER BY pa.fecha_pago DESC, pa.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    /** Suma de pagos registrados para una cotización. */
    public function totalPagado(int $cotizacionId): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM pago WHERE id_cotizacion = ?"
        );
        $stmt->execute([$cotizacionId]);
        return (float) $stmt->fetchColumn();
    }

    /** Lista de pagos de una cotización específica. */
    public function porCotizacion(int $cotizacionId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM pago WHERE id_cotizacion = ? ORDER BY fecha_pago ASC"
        );
        $stmt->execute([$cotizacionId]);
        return $stmt->fetchAll();
    }

    /* ── Escritura ───────────────────────────────────────────────── */

    /**
     * Registra un pago.
     * $datos: id_cotizacion, monto, metodo, referencia(optional),
     *         observaciones(optional), id_registrado_por, fecha_pago
     * Retorna id_pago.
     */
    public function crear(array $datos): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO pago
                (id_cotizacion, monto, metodo, referencia,
                 observaciones, id_registrado_por, fecha_pago)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            (int)   $datos['id_cotizacion'],
            (float) $datos['monto'],
                    $datos['metodo'],
                    ($datos['referencia']    ?? '') ?: null,
                    ($datos['observaciones'] ?? '') ?: null,
            (int)   $datos['id_registrado_por'],
                    $datos['fecha_pago'],
        ]);
        return (int) $this->db->lastInsertId();
    }
}
