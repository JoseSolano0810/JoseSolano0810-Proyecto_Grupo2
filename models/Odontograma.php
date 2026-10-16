<?php
/**
 * Odontograma.php — CLI-03 / CLI-04
 * Gestiona el estado por pieza dental de un expediente clínico.
 * Notación FDI: piezas 11-18, 21-28, 31-38, 41-48.
 */
class Odontograma
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTablas();
    }

    /* ── Migración auto-ejecutada ───────────────────────────── */
    private function asegurarTablas(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS odontograma_pieza (
                id_pieza        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_expediente   INT UNSIGNED NOT NULL,
                numero_pieza    TINYINT UNSIGNED NOT NULL,
                estado          ENUM('sano','caries','corona','ausente','endodoncia','extraccion','implante')
                                NOT NULL DEFAULT 'sano',
                observaciones   TEXT DEFAULT NULL,
                updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_pieza (id_expediente, numero_pieza),
                CONSTRAINT fk_odo_expediente
                    FOREIGN KEY (id_expediente)
                    REFERENCES expediente_clinico (id_expediente)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /* ── Lectura ────────────────────────────────────────────── */

    /**
     * Devuelve array asociativo [numero_pieza => ['estado', 'observaciones', 'updated_at']]
     * Sólo incluye las piezas que tienen registro; el cliente completa las ausentes como 'sano'.
     */
    public function obtenerPorExpediente(int $idExpediente): array
    {
        $stmt = $this->db->prepare(
            "SELECT numero_pieza, estado, observaciones, updated_at
             FROM   odontograma_pieza
             WHERE  id_expediente = :ide
             ORDER  BY numero_pieza"
        );
        $stmt->execute([':ide' => $idExpediente]);
        $resultado = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $resultado[(int)$fila['numero_pieza']] = $fila;
        }
        return $resultado;
    }

    /* ── Escritura ──────────────────────────────────────────── */

    /**
     * Upsert de una pieza.
     * $datos debe incluir: id_expediente (int), numero_pieza (int), estado (string).
     * observaciones es opcional.
     * Devuelve ['ok' => bool, 'id_pieza' => int].
     */
    public function guardarPieza(array $datos): array
    {
        $estadosValidos = ['sano', 'caries', 'corona', 'ausente', 'endodoncia', 'extraccion', 'implante'];
        if (!in_array($datos['estado'] ?? '', $estadosValidos, true)) {
            return ['ok' => false, 'mensaje' => 'Estado no válido.'];
        }

        $piezasValidas = array_merge(
            range(11, 18), range(21, 28),
            range(31, 38), range(41, 48)
        );
        if (!in_array((int)($datos['numero_pieza'] ?? 0), $piezasValidas, true)) {
            return ['ok' => false, 'mensaje' => 'Número de pieza no válido.'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO odontograma_pieza (id_expediente, numero_pieza, estado, observaciones)
             VALUES (:ide, :np, :est, :obs)
             ON DUPLICATE KEY UPDATE
                 estado        = VALUES(estado),
                 observaciones = VALUES(observaciones),
                 updated_at    = CURRENT_TIMESTAMP"
        );
        $stmt->execute([
            ':ide' => (int)$datos['id_expediente'],
            ':np'  => (int)$datos['numero_pieza'],
            ':est' => $datos['estado'],
            ':obs' => $datos['observaciones'] ?? null,
        ]);

        // Recuperar el id generado o el existente
        $id = (int)$this->db->lastInsertId();
        if (!$id) {
            $s2 = $this->db->prepare(
                "SELECT id_pieza FROM odontograma_pieza WHERE id_expediente=:ide AND numero_pieza=:np"
            );
            $s2->execute([':ide' => (int)$datos['id_expediente'], ':np' => (int)$datos['numero_pieza']]);
            $id = (int)$s2->fetchColumn();
        }

        return ['ok' => true, 'id_pieza' => $id];
    }

    /**
     * Guarda varias piezas en una sola llamada (usado en la edición masiva futura).
     * $piezas es un array de subarrays con las mismas claves que guardarPieza().
     * Devuelve conteo de filas afectadas.
     */
    public function guardarMultiple(array $piezas, int $idExpediente): int
    {
        $afectadas = 0;
        foreach ($piezas as $p) {
            $p['id_expediente'] = $idExpediente;
            $r = $this->guardarPieza($p);
            if ($r['ok']) $afectadas++;
        }
        return $afectadas;
    }
}