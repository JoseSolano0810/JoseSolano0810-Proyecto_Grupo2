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
        // Crea la tabla solo si no existe. Si ya existe con columna 'condicion'
        // (versión anterior del esquema) las consultas usan alias para compatibilidad.
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS odontograma_pieza (
                id_registro     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                id_expediente   INT UNSIGNED NOT NULL,
                numero_pieza    TINYINT UNSIGNED NOT NULL,
                condicion       ENUM('sano','caries','obturado','corona','ausente','endodoncia','extraccion','implante')
                                NOT NULL DEFAULT 'sano',
                observaciones   VARCHAR(255) DEFAULT NULL,
                updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_pieza_expediente (id_expediente, numero_pieza)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Si la tabla ya existía con columna 'estado', renombrarla a 'condicion'
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM odontograma_pieza LIKE 'estado'")->fetchAll();
            if (!empty($cols)) {
                $this->db->exec("ALTER TABLE odontograma_pieza CHANGE estado condicion
                    ENUM('sano','caries','obturado','corona','ausente','endodoncia','extraccion','implante')
                    NOT NULL DEFAULT 'sano'");
            }
        } catch (\Throwable $e) { /* ignorar si falla la migración */ }

        // Si la PK se llama 'id_pieza', renombrarla a 'id_registro'
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM odontograma_pieza LIKE 'id_pieza'")->fetchAll();
            if (!empty($cols)) {
                $this->db->exec("ALTER TABLE odontograma_pieza CHANGE id_pieza id_registro INT UNSIGNED AUTO_INCREMENT");
            }
        } catch (\Throwable $e) { /* ignorar */ }
    }

    /* ── Lectura ────────────────────────────────────────────── */

    /**
     * Devuelve array asociativo [numero_pieza => ['estado', 'observaciones', 'updated_at']]
     * Sólo incluye las piezas que tienen registro; el cliente completa las ausentes como 'sano'.
     */
    public function obtenerPorExpediente(int $idExpediente): array
    {
        $stmt = $this->db->prepare(
            "SELECT numero_pieza, condicion AS estado, observaciones, updated_at
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
        $estadosValidos = ['sano', 'caries', 'obturado', 'corona', 'ausente', 'endodoncia', 'extraccion', 'implante'];
        if (!in_array($datos['estado'] ?? '', $estadosValidos, true)) {
            return ['ok' => false, 'mensaje' => 'Estado no válido: ' . ($datos['estado'] ?? 'vacío')];
        }

        $piezasValidas = array_merge(
            range(11, 18), range(21, 28),
            range(31, 38), range(41, 48)
        );
        if (!in_array((int)($datos['numero_pieza'] ?? 0), $piezasValidas, true)) {
            return ['ok' => false, 'mensaje' => 'Número de pieza no válido.'];
        }

        // Detectar si la tabla tiene columna id_odontologo (esquema de producción)
        $tieneOdontologo = false;
        try {
            $cols = $this->db->query("SHOW COLUMNS FROM odontograma_pieza LIKE 'id_odontologo'")->fetchAll();
            $tieneOdontologo = !empty($cols);
        } catch (\Throwable $e) {}

        if ($tieneOdontologo) {
            $idOdontologo = (int)($datos['id_odontologo'] ?? 0);
            $stmt = $this->db->prepare(
                "INSERT INTO odontograma_pieza (id_expediente, numero_pieza, condicion, observaciones, id_odontologo)
                 VALUES (:ide, :np, :est, :obs, :odo)
                 ON DUPLICATE KEY UPDATE
                     condicion     = VALUES(condicion),
                     observaciones = VALUES(observaciones),
                     id_odontologo = VALUES(id_odontologo),
                     updated_at    = CURRENT_TIMESTAMP"
            );
            $stmt->execute([
                ':ide' => (int)$datos['id_expediente'],
                ':np'  => (int)$datos['numero_pieza'],
                ':est' => $datos['estado'],
                ':obs' => $datos['observaciones'] ?? null,
                ':odo' => $idOdontologo ?: 1,
            ]);
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO odontograma_pieza (id_expediente, numero_pieza, condicion, observaciones)
                 VALUES (:ide, :np, :est, :obs)
                 ON DUPLICATE KEY UPDATE
                     condicion     = VALUES(condicion),
                     observaciones = VALUES(observaciones),
                     updated_at    = CURRENT_TIMESTAMP"
            );
            $stmt->execute([
                ':ide' => (int)$datos['id_expediente'],
                ':np'  => (int)$datos['numero_pieza'],
                ':est' => $datos['estado'],
                ':obs' => $datos['observaciones'] ?? null,
            ]);
        }

        // Recuperar el id generado o el existente
        $id = (int)$this->db->lastInsertId();
        if (!$id) {
            $s2 = $this->db->prepare(
                "SELECT id_registro FROM odontograma_pieza WHERE id_expediente=:ide AND numero_pieza=:np"
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