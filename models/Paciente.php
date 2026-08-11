<?php

require_once ROOT_PATH . '/database/Database.php';

class Paciente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT p.id, p.nombre, p.cedula, p.telefono, p.correo,
                    TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad,
                    DATE_FORMAT(MAX(c.fecha), '%d/%m/%Y') AS ultima_visita,
                    p.estado
             FROM pacientes p
             LEFT JOIN citas c ON c.paciente_id = p.id
             GROUP BY p.id, p.nombre, p.cedula, p.telefono, p.correo,
                      p.fecha_nacimiento, p.estado
             ORDER BY p.nombre ASC"
        );

        return $stmt->fetchAll();
    }

    public function buscar(string $termino): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM pacientes
             WHERE nombre LIKE ? OR cedula LIKE ?
             ORDER BY nombre ASC"
        );

        $like = '%' . $termino . '%';

        $stmt->execute([$like, $like]);

        return $stmt->fetchAll();
    }

    public function crear(array $datos): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO pacientes
             (nombre, cedula, telefono, correo, fecha_nacimiento, estado)
             VALUES (?, ?, ?, ?, ?, 'activo')"
        );

        $stmt->execute([
            $datos['nombre'],
            $datos['cedula'],
            $datos['telefono'],
            $datos['correo'],
            $datos['nacimiento']
        ]);
    }

    public function actualizar(int $id, array $datos): void
    {
        $stmt = $this->db->prepare(
            "UPDATE pacientes
             SET nombre = ?, cedula = ?, telefono = ?, correo = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $datos['nombre'],
            $datos['cedula'],
            $datos['telefono'],
            $datos['correo'],
            $id
        ]);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $stmt = $this->db->prepare(
            "UPDATE pacientes SET estado = ? WHERE id = ?"
        );

        $stmt->execute([$estado, $id]);
    }
}