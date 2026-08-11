<?php

class Tratamiento
{
    private PDO $db;

    public function __construct()
    {
        // TODO: $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerTodos(): array
    {
        // TODO:
        // $stmt = $this->db->query(
        //     "SELECT t.*, p.nombre AS paciente
        //      FROM tratamientos t
        //      JOIN pacientes p ON t.paciente_id = p.id
        //      ORDER BY t.estado ASC, t.fecha_inicio DESC"
        // );
        // return $stmt->fetchAll();
        return [];
    }

    public function obtenerPorPaciente(int $pacienteId): array
    {
        // TODO:
        // $stmt = $this->db->prepare(
        //     "SELECT * FROM tratamientos WHERE paciente_id = ?
        //      ORDER BY fecha_inicio DESC"
        // );
        // $stmt->execute([$pacienteId]);
        // return $stmt->fetchAll();
        return [];
    }

    public function crear(array $datos): void
    {
        // TODO:
        // $stmt = $this->db->prepare(
        //     "INSERT INTO tratamientos
        //      (paciente_id, odontologo_id, nombre, diagnostico, fecha_inicio, estado)
        //      VALUES (?, ?, ?, ?, ?, 'proceso')"
        // );
        // $stmt->execute([...]);
    }

    public function actualizarProgreso(int $id, int $progreso, int $sesionesRealizadas): void
    {
        // TODO:
        // $estado = $progreso >= 100 ? 'completada' : 'proceso';
        // $stmt = $this->db->prepare(
        //     "UPDATE tratamientos
        //      SET progreso=?, sesiones_realizadas=?, estado=?
        //      WHERE id=?"
        // );
        // $stmt->execute([$progreso, $sesionesRealizadas, $estado, $id]);
    }
}