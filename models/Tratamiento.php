<?php

require_once ROOT_PATH . '/database/Database.php';

class Tratamiento
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT t.*, p.nombre AS paciente
             FROM tratamientos t
             JOIN pacientes p ON t.paciente_id = p.id
             ORDER BY t.estado ASC, t.fecha_inicio DESC"
        );

        return $stmt->fetchAll();
    }

    public function obtenerPorPaciente(int $pacienteId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tratamientos
             WHERE paciente_id = ?
             ORDER BY fecha_inicio DESC"
        );

        $stmt->execute([$pacienteId]);

        return $stmt->fetchAll();
    }

    public function crear(array $datos): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tratamientos
             (paciente_id, odontologo_id, nombre, diagnostico, fecha_inicio, estado)
             VALUES (?, ?, ?, ?, ?, 'proceso')"
        );

        $stmt->execute([
            $datos['paciente_id'],
            $datos['odontologo_id'],
            $datos['nombre'],
            $datos['diagnostico'],
            $datos['fecha_inicio']
        ]);
    }

    public function actualizarProgreso(
        int $id,
        int $progreso,
        int $sesionesRealizadas
    ): void {
        $estado = $progreso >= 100 ? 'completada' : 'proceso';

        $stmt = $this->db->prepare(
            "UPDATE tratamientos
             SET progreso = ?, sesiones_realizadas = ?, estado = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $progreso,
            $sesionesRealizadas,
            $estado,
            $id
        ]);
    }
}