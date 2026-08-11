<?php

class Cita
{
    private PDO $db;

    public function __construct()
    {
        // TODO: $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerTodas(): array
    {
        // TODO:
        // $stmt = $this->db->prepare(
        //     "SELECT c.id, c.hora, c.tratamiento, c.estado,
        //             p.nombre AS paciente,
        //             u.nombre AS odontologo
        //      FROM citas c
        //      JOIN pacientes p ON c.paciente_id  = p.id
        //      JOIN usuarios  u ON c.odontologo_id = u.id
        //      WHERE c.fecha = CURDATE()
        //      ORDER BY c.hora ASC"
        // );
        // $stmt->execute();
        // return $stmt->fetchAll();
        return [];
    }

    public function obtenerPorOdontologo(int $odontologoId): array
    {
        // TODO:
        // $stmt = $this->db->prepare(
        //     "SELECT c.id, c.hora, c.tratamiento, c.estado,
        //             p.nombre AS paciente
        //      FROM citas c
        //      JOIN pacientes p ON c.paciente_id = p.id
        //      WHERE c.odontologo_id = ? AND c.fecha = CURDATE()
        //      ORDER BY c.hora ASC"
        // );
        // $stmt->execute([$odontologoId]);
        // return $stmt->fetchAll();
        return [];
    }

    public function existeTraslape(int $odontologoId, string $fecha, string $hora): bool
    {
        // TODO:
        // $stmt = $this->db->prepare(
        //     "SELECT COUNT(*) FROM citas
        //      WHERE odontologo_id = ? AND fecha = ? AND hora = ?
        //      AND estado != 'cancelada'"
        // );
        // $stmt->execute([$odontologoId, $fecha, $hora]);
        // return (bool) $stmt->fetchColumn();
        return false;
    }

    public function crear(array $datos): void
    {
        // TODO:
        // $stmt = $this->db->prepare(
        //     "INSERT INTO citas
        //      (paciente_id, odontologo_id, fecha, hora, tratamiento, estado, notas)
        //      VALUES (?, ?, ?, ?, ?, 'pendiente', ?)"
        // );
        // $stmt->execute([
        //     $datos['paciente_id'],
        //     $datos['odontologo_id'],
        //     $datos['fecha'],
        //     $datos['hora'],
        //     $datos['tratamiento'],
        //     $datos['notas'] ?? ''
        // ]);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        // TODO:
        // $stmt = $this->db->prepare(
        //     "UPDATE citas SET estado = ? WHERE id = ?"
        // );
        // $stmt->execute([$estado, $id]);
    }
}