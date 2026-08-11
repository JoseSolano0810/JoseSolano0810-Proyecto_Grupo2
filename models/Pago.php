<?php

require_once ROOT_PATH . '/database/Database.php';

class Pago
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT pg.*, p.nombre AS paciente,
                    CONCAT('COT-', LPAD(c.id, 3, '0')) AS cotizacion
             FROM pagos pg
             JOIN cotizaciones c ON pg.cotizacion_id = c.id
             JOIN pacientes p ON c.paciente_id = p.id
             ORDER BY pg.fecha DESC"
        );

        return $stmt->fetchAll();
    }

    public function totalPagado(int $cotizacionId): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0)
             FROM pagos
             WHERE cotizacion_id = ? AND estado = 'completada'"
        );

        $stmt->execute([$cotizacionId]);

        return (float) $stmt->fetchColumn();
    }

    public function crear(array $datos): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO pagos
             (cotizacion_id, monto, metodo, comprobante, estado, fecha)
             VALUES (?, ?, ?, ?, 'completada', CURDATE())"
        );

        $stmt->execute([
            $datos['cotizacion_id'],
            $datos['monto'],
            $datos['metodo'],
            $datos['comprobante'] ?? ''
        ]);
    }
}