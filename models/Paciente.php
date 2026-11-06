<?php

require_once ROOT_PATH . '/database/Database.php';

class Paciente
{
    private PDO $db;
    private static bool $tablasListas = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTablas();
    }

    private function asegurarTablas(): void
    {
        if (self::$tablasListas) return;

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS paciente (
                id_paciente       INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                id_usuario        INT(11) NOT NULL,
                fecha_nacimiento  DATE DEFAULT NULL,
                sexo              ENUM('masculino','femenino','otro') DEFAULT NULL,
                grupo_sanguineo   VARCHAR(5) DEFAULT NULL,
                estado            ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
                created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id_paciente),
                UNIQUE KEY uq_paciente_usuario (id_usuario),
                CONSTRAINT fk_paciente_usuario FOREIGN KEY (id_usuario)
                    REFERENCES usuario (id_usuario) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$tablasListas = true;
    }

    private const SELECT_BASE =
        "SELECT u.id_usuario, u.nombre_completo, u.identificacion,
                u.nombre_usuario, u.telefono, u.correo, u.estado AS estado_usuario,
                p.id_paciente, p.fecha_nacimiento, p.sexo,
                p.grupo_sanguineo, p.estado,
                TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad
         FROM usuario u
         LEFT JOIN paciente p ON p.id_usuario = u.id_usuario
         WHERE EXISTS (
             SELECT 1 FROM usuario_rol ur
             JOIN rol r ON r.id_rol = ur.id_rol
             WHERE ur.id_usuario = u.id_usuario AND r.nombre = 'paciente'
         )";

    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(self::SELECT_BASE . " ORDER BY u.nombre_completo ASC");
        return $stmt->fetchAll();
    }

    public function buscar(string $termino): array
    {
        $stmt = $this->db->prepare(
            self::SELECT_BASE .
            " AND (u.nombre_completo LIKE ? OR u.identificacion LIKE ? OR u.correo LIKE ?)
              ORDER BY u.nombre_completo ASC LIMIT 50"
        );
        $like = '%' . $termino . '%';
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function obtenerPorUsuario(int $idUsuario): ?array
    {
        $stmt = $this->db->prepare(
            self::SELECT_BASE . " AND u.id_usuario = ?"
        );
        $stmt->execute([$idUsuario]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerPorId(int $idPaciente): ?array
    {
        $stmt = $this->db->prepare(
            self::SELECT_BASE . " AND p.id_paciente = ?"
        );
        $stmt->execute([$idPaciente]);
        return $stmt->fetch() ?: null;
    }

    public function crear(array $datos): int
    {
        $this->db->prepare(
            "INSERT INTO paciente (id_usuario, fecha_nacimiento, sexo, grupo_sanguineo)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                fecha_nacimiento = VALUES(fecha_nacimiento),
                sexo             = VALUES(sexo),
                grupo_sanguineo  = VALUES(grupo_sanguineo)"
        )->execute([
            $datos['id_usuario'],
            ($datos['fecha_nacimiento'] ?? '') !== '' ? $datos['fecha_nacimiento'] : null,
            ($datos['sexo']             ?? '') !== '' ? $datos['sexo']             : null,
            ($datos['grupo_sanguineo']  ?? '') !== '' ? $datos['grupo_sanguineo']  : null,
        ]);

        $nuevo = (int) $this->db->lastInsertId();
        if ($nuevo) return $nuevo;

        $stmt = $this->db->prepare("SELECT id_paciente FROM paciente WHERE id_usuario = ?");
        $stmt->execute([$datos['id_usuario']]);
        return (int) $stmt->fetchColumn();
    }

    public function actualizar(int $idPaciente, array $datos): void
    {
        $this->db->prepare(
            "UPDATE paciente
             SET fecha_nacimiento = ?, sexo = ?, grupo_sanguineo = ?
             WHERE id_paciente = ?"
        )->execute([
            ($datos['fecha_nacimiento'] ?? '') !== '' ? $datos['fecha_nacimiento'] : null,
            ($datos['sexo']             ?? '') !== '' ? $datos['sexo']             : null,
            ($datos['grupo_sanguineo']  ?? '') !== '' ? $datos['grupo_sanguineo']  : null,
            $idPaciente,
        ]);
    }

    public function cambiarEstado(int $idPaciente, string $estado): void
    {
        $this->db->prepare(
            "UPDATE paciente SET estado = ? WHERE id_paciente = ?"
        )->execute([$estado, $idPaciente]);
    }

    public function activos(): array
    {
        $stmt = $this->db->query(
            self::SELECT_BASE .
            " AND (p.estado = 'activo' OR p.estado IS NULL)
              ORDER BY u.nombre_completo ASC"
        );
        return $stmt->fetchAll();
    }
}
