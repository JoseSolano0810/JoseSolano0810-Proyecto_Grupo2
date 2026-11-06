<?php

require_once ROOT_PATH . '/database/Database.php';

class Cotizacion
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
            "CREATE TABLE IF NOT EXISTS cotizacion (
                id_cotizacion  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_tratamiento INT UNSIGNED NOT NULL,
                id_paciente    INT UNSIGNED NOT NULL,
                descripcion    TEXT NOT NULL,
                subtotal       DECIMAL(12,2) NOT NULL DEFAULT 0,
                descuento      DECIMAL(12,2) NOT NULL DEFAULT 0,
                iva            DECIMAL(12,2) NOT NULL DEFAULT 0,
                total          DECIMAL(12,2) NOT NULL DEFAULT 0,
                estado         ENUM('pendiente','aprobada','rechazada','pagada')
                                NOT NULL DEFAULT 'pendiente',
                observaciones  TEXT NULL,
                id_creado_por  INT NOT NULL,
                created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_cotizacion_tratamiento (id_tratamiento)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$tablasListas = true;
    }

    /* ── Lecturas ────────────────────────────────────────────────── */

    /**
     * Devuelve todas las cotizaciones enriquecidas con nombre del paciente
     * y nombre del tratamiento.
     */
    public function obtenerTodas(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT c.*,
                        u.nombre_completo   AS paciente_nombre,
                        t.nombre            AS tratamiento_nombre
                 FROM cotizacion c
                 JOIN paciente  p ON p.id_paciente    = c.id_paciente
                 JOIN usuario   u ON u.id_usuario     = p.id_usuario
                 LEFT JOIN tratamiento t ON t.id_tratamiento = c.id_tratamiento
                 ORDER BY c.created_at DESC"
            );
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /** Devuelve una cotización por su ID, con datos del paciente y tratamiento. */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,
                    u.nombre_completo   AS paciente_nombre,
                    t.nombre            AS tratamiento_nombre
             FROM cotizacion c
             JOIN paciente  p ON p.id_paciente    = c.id_paciente
             JOIN usuario   u ON u.id_usuario     = p.id_usuario
             JOIN tratamiento t ON t.id_tratamiento = c.id_tratamiento
             WHERE c.id_cotizacion = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Devuelve los tratamientos que aún NO tienen cotización asignada,
     * incluyendo el nombre del paciente para mostrarlo en el selector.
     * Sólo trae tratamientos en estado activo (pendiente o en_proceso).
     * Retorna [] si la tabla tratamiento aún no existe.
     */
    public function tratamientosSinCotizacion(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT t.id_tratamiento,
                        t.nombre,
                        t.costo_estimado,
                        t.estado,
                        p.id_paciente,
                        u.nombre_completo AS paciente_nombre
                 FROM tratamiento t
                 JOIN paciente p ON p.id_paciente = t.id_paciente
                 JOIN usuario  u ON u.id_usuario  = p.id_usuario
                 WHERE t.estado IN ('pendiente','en_proceso')
                   AND NOT EXISTS (
                       SELECT 1 FROM cotizacion c2
                       WHERE c2.id_tratamiento = t.id_tratamiento
                   )
                 ORDER BY u.nombre_completo ASC, t.nombre ASC"
            );
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            // Si la tabla tratamiento no existe todavía, retornar vacío
            return [];
        }
    }

    /* ── Escritura ───────────────────────────────────────────────── */

    /**
     * Inserta una nueva cotización.
     * $datos debe tener: id_tratamiento, id_paciente, descripcion,
     *                    subtotal, descuento, iva, total,
     *                    observaciones (nullable), id_creado_por.
     * Retorna el id_cotizacion insertado.
     * Lanza PDOException con código 23000 si el tratamiento ya tiene cotización.
     */
    public function crear(array $datos): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO cotizacion
                (id_tratamiento, id_paciente, descripcion,
                 subtotal, descuento, iva, total,
                 observaciones, id_creado_por)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            (int)   $datos['id_tratamiento'],
            (int)   $datos['id_paciente'],
                    $datos['descripcion'],
            (float) $datos['subtotal'],
            (float) ($datos['descuento']   ?? 0),
            (float) $datos['iva'],
            (float) $datos['total'],
                    ($datos['observaciones'] ?? '') ?: null,
            (int)   $datos['id_creado_por'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Actualiza el estado de una cotización (pendiente / aprobada / rechazada / pagada). */
    public function cambiarEstado(int $id, string $estado): void
    {
        $stmt = $this->db->prepare(
            "UPDATE cotizacion SET estado = ? WHERE id_cotizacion = ?"
        );
        $stmt->execute([$estado, $id]);
    }
}
