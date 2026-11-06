<?php

require_once ROOT_PATH . '/database/Database.php';

class ExpedienteClinico
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
            "CREATE TABLE IF NOT EXISTS expediente_clinico (
                id_expediente         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                id_paciente           INT(11) UNSIGNED NOT NULL,
                motivo_consulta       TEXT NOT NULL,
                antecedentes          TEXT DEFAULT NULL,
                alergias              TEXT DEFAULT NULL,
                medicamentos_actuales TEXT DEFAULT NULL,
                enfermedades          TEXT DEFAULT NULL,
                observaciones         TEXT DEFAULT NULL,
                created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id_expediente),
                UNIQUE KEY uq_expediente_paciente (id_paciente),
                CONSTRAINT fk_expediente_paciente FOREIGN KEY (id_paciente)
                    REFERENCES paciente (id_paciente) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS atencion_clinica (
                id_atencion           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                id_expediente         INT(11) UNSIGNED NOT NULL,
                id_odontologo         INT(11) NOT NULL,
                diagnostico           TEXT NOT NULL,
                tratamiento_realizado TEXT DEFAULT NULL,
                observaciones         TEXT DEFAULT NULL,
                created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id_atencion),
                KEY idx_atencion_expediente (id_expediente),
                KEY idx_atencion_odontologo (id_odontologo),
                CONSTRAINT fk_atencion_expediente FOREIGN KEY (id_expediente)
                    REFERENCES expediente_clinico (id_expediente) ON DELETE CASCADE,
                CONSTRAINT fk_atencion_odontologo FOREIGN KEY (id_odontologo)
                    REFERENCES usuario (id_usuario)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$tablasListas = true;
    }

    public function obtenerPorPaciente(int $idPaciente): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*,
                    u.nombre_completo AS paciente_nombre,
                    u.identificacion  AS paciente_cedula
             FROM expediente_clinico e
             JOIN paciente p  ON p.id_paciente = e.id_paciente
             JOIN usuario  u  ON u.id_usuario  = p.id_usuario
             WHERE e.id_paciente = ?"
        );
        $stmt->execute([$idPaciente]);
        return $stmt->fetch() ?: null;
    }

    public function mapaPorPaciente(): array
    {
        $map = [];
        foreach ($this->db->query("SELECT id_paciente, id_expediente FROM expediente_clinico") as $f) {
            $map[(int) $f['id_paciente']] = (int) $f['id_expediente'];
        }
        return $map;
    }

    public function obtenerPorId(int $idExpediente): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*,
                    u.nombre_completo AS paciente_nombre,
                    u.identificacion  AS paciente_cedula
             FROM expediente_clinico e
             JOIN paciente p ON p.id_paciente = e.id_paciente
             JOIN usuario  u ON u.id_usuario  = p.id_usuario
             WHERE e.id_expediente = ?"
        );
        $stmt->execute([$idExpediente]);
        return $stmt->fetch() ?: null;
    }

    public function crear(array $datos): int
    {
        $this->db->prepare(
            "INSERT INTO expediente_clinico
             (id_paciente, motivo_consulta, antecedentes, alergias,
              medicamentos_actuales, enfermedades, observaciones)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $datos['id_paciente'],
            $datos['motivo_consulta'],
            ($datos['antecedentes']          ?? '') ?: null,
            ($datos['alergias']              ?? '') ?: null,
            ($datos['medicamentos_actuales'] ?? '') ?: null,
            ($datos['enfermedades']          ?? '') ?: null,
            ($datos['observaciones']         ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar(int $idExpediente, array $datos): void
    {
        $this->db->prepare(
            "UPDATE expediente_clinico
             SET motivo_consulta       = ?,
                 antecedentes          = ?,
                 alergias              = ?,
                 medicamentos_actuales = ?,
                 enfermedades          = ?,
                 observaciones         = ?
             WHERE id_expediente = ?"
        )->execute([
            $datos['motivo_consulta'],
            ($datos['antecedentes']          ?? '') ?: null,
            ($datos['alergias']              ?? '') ?: null,
            ($datos['medicamentos_actuales'] ?? '') ?: null,
            ($datos['enfermedades']          ?? '') ?: null,
            ($datos['observaciones']         ?? '') ?: null,
            $idExpediente,
        ]);
    }

    public function atenciones(int $idExpediente): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, o.nombre_completo AS odontologo
             FROM atencion_clinica a
             JOIN usuario o ON o.id_usuario = a.id_odontologo
             WHERE a.id_expediente = ?
             ORDER BY a.created_at DESC"
        );
        $stmt->execute([$idExpediente]);
        return $stmt->fetchAll();
    }

    public function registrarAtencion(array $datos): int
    {
        $this->db->prepare(
            "INSERT INTO atencion_clinica
             (id_expediente, id_odontologo, diagnostico, tratamiento_realizado, observaciones)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $datos['id_expediente'],
            $datos['id_odontologo'],
            $datos['diagnostico'],
            ($datos['tratamiento_realizado'] ?? '') ?: null,
            ($datos['observaciones']         ?? '') ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
