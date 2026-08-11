<?php

class Cotizacion
{
    private PDO $db;

    public function __construct()
    {
        // TODO: $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerTodas(): array
    {
        // TODO:
        // $stmt = $this->db->query(
        //     "SELECT c.*, p.nombre AS paciente
        //      FROM cotizaciones c
        //      JOIN pacientes p ON c.paciente_id = p.id
        //      ORDER BY c.fecha DESC"
        // );
        // return $stmt->fetchAll();
        return [];
    }

    public function obtenerPorId(int $id): ?array
    {
        // TODO:
        // $stmt = $this->db->prepare("SELECT * FROM cotizaciones WHERE id=?");
        // $stmt->execute([$id]);
        // return $stmt->fetch() ?: null;
        return null;
    }

    public function crear(array $datos): void
    {
        // TODO:
        // $stmt = $this->db->prepare(
        //     "INSERT INTO cotizaciones
        //      (paciente_id, tratamiento, monto, iva, total, estado, fecha)
        //      VALUES (?, ?, ?, ?, ?, 'pendiente', CURDATE())"
        // );
        // $stmt->execute([
        //     $datos['paciente_id'], $datos['tratamiento'],
        //     $datos['monto'], $datos['iva'], $datos['total'],
        // ]);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        // TODO:
        // $stmt = $this->db->prepare("UPDATE cotizaciones SET estado=? WHERE id=?");
        // $stmt->execute([$estado, $id]);
    }
}