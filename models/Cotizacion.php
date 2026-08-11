<?php

require_once ROOT_PATH . '/database/Database.php';

class Cotizacion
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerTodas(): array
    {
        $stmt = $this->db->query(
            "SELECT c.*, p.nombre AS paciente
             FROM cotizaciones c
             JOIN pacientes p ON c.paciente_id = p.id
             ORDER BY c.fecha DESC"
        );

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM cotizaciones WHERE id = ?"
        );

        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public function crear(array $datos): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO cotizaciones
             (paciente_id, tratamiento, monto, iva, total, estado, fecha)
             VALUES (?, ?, ?, ?, ?, 'pendiente', CURDATE())"
        );

        $stmt->execute([
            $datos['paciente_id'],
            $datos['tratamiento'],
            $datos['monto'],
            $datos['iva'],
            $datos['total']
        ]);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $stmt = $this->db->prepare(
            "UPDATE cotizaciones SET estado = ? WHERE id = ?"
        );

        $stmt->execute([$estado, $id]);
    }
}