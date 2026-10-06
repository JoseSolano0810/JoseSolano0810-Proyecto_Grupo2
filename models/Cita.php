<?php

require_once ROOT_PATH . '/database/Database.php';

/** CIT-03: el odontólogo ya tiene una cita que se traslapa con el horario solicitado */
class HorarioNoDisponibleException extends RuntimeException {}

class Cita
{
    public const ESTADOS         = ['programada', 'confirmada', 'atendida', 'cancelada', 'ausente'];
    public const ESTADOS_ACTIVOS = ['programada', 'confirmada'];

    public const TIPOS = [
        'Limpieza dental', 'Revisión general', 'Extracción', 'Ortodoncia',
        'Blanqueamiento', 'Endodoncia', 'Restauración', 'Urgencia', 'Otro',
    ];
    public const DURACIONES = [15, 30, 45, 60, 90, 120, 180];

    private PDO $db;
    private static bool $tablasListas = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTablas();
    }

    /** Crea las tablas del módulo si no existen (mismo criterio que Bitacora). Ver database/citas.sql */
    private function asegurarTablas(): void
    {
        if (self::$tablasListas) return;

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS cita (
                id_cita        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_paciente    INT NOT NULL,
                id_odontologo  INT NOT NULL,
                fecha          DATE NOT NULL,
                hora_inicio    TIME NOT NULL,
                hora_fin       TIME NOT NULL,
                duracion_min   SMALLINT UNSIGNED NOT NULL,
                tipo_consulta  VARCHAR(60) NOT NULL,
                observaciones  VARCHAR(500) NULL,
                estado         ENUM('programada','confirmada','atendida','cancelada','ausente') NOT NULL DEFAULT 'programada',
                creada_por     INT NULL,
                created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_cita_odontologo_fecha (id_odontologo, fecha),
                INDEX idx_cita_paciente (id_paciente),
                INDEX idx_cita_fecha (fecha),
                INDEX idx_cita_estado (estado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS cita_historial (
                id_historial   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_cita        INT UNSIGNED NOT NULL,
                accion         VARCHAR(30)  NOT NULL,
                detalle        VARCHAR(255) NOT NULL,
                id_usuario     INT NULL,
                usuario_nombre VARCHAR(100) NOT NULL DEFAULT 'Sistema',
                created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_hist_cita (id_cita)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS recordatorio_cita (
                id_recordatorio INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_cita         INT UNSIGNED NOT NULL,
                medio           VARCHAR(20)  NOT NULL DEFAULT 'correo',
                destino         VARCHAR(150) NULL,
                estado          ENUM('enviado','fallido','omitido') NOT NULL,
                detalle         VARCHAR(255) NOT NULL,
                created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_rec_cita (id_cita, estado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$tablasListas = true;
    }

    /* ── Consultas ──────────────────────────────────────────── */

    private const SELECT_BASE =
        "SELECT c.*,
                p.nombre_completo AS paciente, p.identificacion AS paciente_cedula,
                o.nombre_completo AS odontologo
         FROM cita c
         LEFT JOIN usuario p ON p.id_usuario = c.id_paciente
         LEFT JOIN usuario o ON o.id_usuario = c.id_odontologo";

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT_BASE . " WHERE c.id_cita = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Consulta con filtros (CIT-02 esc. 1, CIT-04).
     * Filtros: id_paciente, id_odontologo, q (nombre/cédula del paciente), fecha, desde, hasta, estado
     */
    public function consultar(array $f = []): array
    {
        $sql    = self::SELECT_BASE . " WHERE 1=1";
        $params = [];

        if (!empty($f['id_paciente']))   { $sql .= " AND c.id_paciente = ?";   $params[] = (int) $f['id_paciente']; }
        if (!empty($f['id_odontologo'])) { $sql .= " AND c.id_odontologo = ?"; $params[] = (int) $f['id_odontologo']; }
        if (($f['q'] ?? '') !== '') {
            $sql .= " AND (p.nombre_completo LIKE ? OR p.identificacion LIKE ?)";
            $params[] = '%' . $f['q'] . '%';
            $params[] = '%' . $f['q'] . '%';
        }
        if (!empty($f['fecha']))  { $sql .= " AND c.fecha = ?";  $params[] = $f['fecha']; }
        if (!empty($f['desde']))  { $sql .= " AND c.fecha >= ?"; $params[] = $f['desde']; }
        if (!empty($f['hasta']))  { $sql .= " AND c.fecha <= ?"; $params[] = $f['hasta']; }
        if (!empty($f['estado']) && in_array($f['estado'], self::ESTADOS, true)) {
            $sql .= " AND c.estado = ?";
            $params[] = $f['estado'];
        }

        $sql .= " ORDER BY c.fecha ASC, c.hora_inicio ASC LIMIT 1000";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Compatibilidad con el stub original */
    public function obtenerTodas(): array
    {
        return $this->consultar();
    }

    /** Compatibilidad con el stub original */
    public function obtenerPorOdontologo(int $odontologoId): array
    {
        return $this->consultar(['id_odontologo' => $odontologoId]);
    }

    /* ── Disponibilidad (CIT-03) ────────────────────────────── */

    /**
     * Busca una cita activa del odontólogo que se traslape con [inicio, fin).
     * Las canceladas liberan el horario. $bloquear = true dentro de una transacción.
     */
    private function buscarTraslape(int $odontologoId, string $fecha, string $horaInicio, string $horaFin, ?int $excluirId, bool $bloquear): ?array
    {
        $sql = "SELECT id_cita, hora_inicio, hora_fin
                FROM cita
                WHERE id_odontologo = ? AND fecha = ? AND estado <> 'cancelada'
                  AND hora_inicio < ? AND hora_fin > ? AND id_cita <> ?
                LIMIT 1" . ($bloquear ? " FOR UPDATE" : "");
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$odontologoId, $fecha, $horaFin, $horaInicio, $excluirId ?? 0]);
        return $stmt->fetch() ?: null;
    }

    public function existeTraslape(int $odontologoId, string $fecha, string $horaInicio, string $horaFin, ?int $excluirId = null): bool
    {
        return $this->buscarTraslape($odontologoId, $fecha, $horaInicio, $horaFin, $excluirId, false) !== null;
    }

    private function lanzarSiTraslape(int $odontologoId, string $fecha, string $horaInicio, string $horaFin, ?int $excluirId): void
    {
        $choque = $this->buscarTraslape($odontologoId, $fecha, $horaInicio, $horaFin, $excluirId, true);
        if ($choque) {
            throw new HorarioNoDisponibleException(
                'El odontólogo no se encuentra disponible en ese horario (ya tiene una cita de '
                . substr($choque['hora_inicio'], 0, 5) . ' a ' . substr($choque['hora_fin'], 0, 5) . ').'
            );
        }
    }

    /* ── Escritura ──────────────────────────────────────────── */

    /**
     * Registra la cita validando traslape dentro de una transacción.
     * $datos: id_paciente, id_odontologo, fecha (Y-m-d), hora_inicio (H:i:s), hora_fin (H:i:s),
     *         duracion_min, tipo_consulta, observaciones, estado, creada_por
     * @throws HorarioNoDisponibleException
     */
    public function crear(array $datos): int
    {
        $this->db->beginTransaction();
        try {
            $this->lanzarSiTraslape((int) $datos['id_odontologo'], $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin'], null);

            $this->db->prepare(
                "INSERT INTO cita
                 (id_paciente, id_odontologo, fecha, hora_inicio, hora_fin, duracion_min,
                  tipo_consulta, observaciones, estado, creada_por)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $datos['id_paciente'],
                $datos['id_odontologo'],
                $datos['fecha'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                $datos['duracion_min'],
                $datos['tipo_consulta'],
                ($datos['observaciones'] ?? '') !== '' ? $datos['observaciones'] : null,
                $datos['estado'],
                $datos['creada_por'] ?? null,
            ]);
            $id = (int) $this->db->lastInsertId();

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza odontólogo, fecha, horario, tipo y observaciones (CIT-02 esc. 2 y CIT-03 esc. 3).
     * @throws HorarioNoDisponibleException
     */
    public function actualizar(int $id, array $datos): void
    {
        $this->db->beginTransaction();
        try {
            $this->lanzarSiTraslape((int) $datos['id_odontologo'], $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin'], $id);

            $this->db->prepare(
                "UPDATE cita
                 SET id_odontologo = ?, fecha = ?, hora_inicio = ?, hora_fin = ?,
                     duracion_min = ?, tipo_consulta = ?, observaciones = ?
                 WHERE id_cita = ?"
            )->execute([
                $datos['id_odontologo'],
                $datos['fecha'],
                $datos['hora_inicio'],
                $datos['hora_fin'],
                $datos['duracion_min'],
                $datos['tipo_consulta'],
                ($datos['observaciones'] ?? '') !== '' ? $datos['observaciones'] : null,
                $id,
            ]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->db->prepare("UPDATE cita SET estado = ? WHERE id_cita = ?")->execute([$estado, $id]);
    }

    /* ── Historial de cambios ───────────────────────────────── */

    public function registrarHistorial(int $idCita, string $accion, string $detalle, ?int $idUsuario, ?string $usuarioNombre): void
    {
        $this->db->prepare(
            "INSERT INTO cita_historial (id_cita, accion, detalle, id_usuario, usuario_nombre)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $idCita,
            mb_substr($accion, 0, 30),
            mb_substr($detalle, 0, 255),
            $idUsuario,
            mb_substr($usuarioNombre ?? 'Sistema', 0, 100),
        ]);
    }

    public function historial(int $idCita): array
    {
        $stmt = $this->db->prepare(
            "SELECT accion, detalle, usuario_nombre, created_at
             FROM cita_historial WHERE id_cita = ?
             ORDER BY created_at DESC, id_historial DESC"
        );
        $stmt->execute([$idCita]);
        return $stmt->fetchAll();
    }

    /* ── Catálogos (usuarios con rol paciente / odontólogo) ─── */

    private function usuariosConRol(string $rol): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id_usuario, u.nombre_completo, u.identificacion, u.telefono, u.correo
             FROM usuario u
             WHERE u.estado = 'activo'
               AND EXISTS (SELECT 1 FROM usuario_rol ur
                           JOIN rol r ON r.id_rol = ur.id_rol
                           WHERE ur.id_usuario = u.id_usuario AND r.nombre = ?)
             ORDER BY u.nombre_completo ASC"
        );
        $stmt->execute([$rol]);
        return $stmt->fetchAll();
    }

    public function pacientesActivos(): array   { return $this->usuariosConRol('paciente'); }
    public function odontologosActivos(): array { return $this->usuariosConRol('odontologo'); }

    /** ¿El usuario existe, está activo y tiene ese rol? */
    public function usuarioTieneRol(int $idUsuario, string $rol): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM usuario u
             WHERE u.id_usuario = ? AND u.estado = 'activo'
               AND EXISTS (SELECT 1 FROM usuario_rol ur
                           JOIN rol r ON r.id_rol = ur.id_rol
                           WHERE ur.id_usuario = u.id_usuario AND r.nombre = ?)"
        );
        $stmt->execute([$idUsuario, $rol]);
        return (bool) $stmt->fetchColumn();
    }

    /* ── Recordatorios (CIT-05) ─────────────────────────────── */

    /** Citas activas que ocurren entre $desde y $hasta y aún no tienen un recordatorio enviado */
    public function pendientesDeRecordatorio(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, p.nombre_completo AS paciente, p.correo AS paciente_correo,
                    o.nombre_completo AS odontologo
             FROM cita c
             JOIN usuario p ON p.id_usuario = c.id_paciente
             LEFT JOIN usuario o ON o.id_usuario = c.id_odontologo
             WHERE c.estado IN ('programada','confirmada')
               AND TIMESTAMP(c.fecha, c.hora_inicio) > ?
               AND TIMESTAMP(c.fecha, c.hora_inicio) <= ?
               AND NOT EXISTS (SELECT 1 FROM recordatorio_cita r
                               WHERE r.id_cita = c.id_cita AND r.estado = 'enviado')
             ORDER BY c.fecha, c.hora_inicio"
        );
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll();
    }

    public function registrarRecordatorio(int $idCita, string $medio, string $estado, string $detalle, ?string $destino): void
    {
        $this->db->prepare(
            "INSERT INTO recordatorio_cita (id_cita, medio, destino, estado, detalle)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$idCita, $medio, $destino, $estado, mb_substr($detalle, 0, 255)]);
    }

    public function contarRecordatorios(int $idCita, string $estado): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM recordatorio_cita WHERE id_cita = ? AND estado = ?");
        $stmt->execute([$idCita, $estado]);
        return (int) $stmt->fetchColumn();
    }

    public function listarRecordatorios(int $limite = 50): array
    {
        $limite = max(1, min(200, $limite));
        return $this->db->query(
            "SELECT r.id_recordatorio, r.id_cita, r.medio, r.destino, r.estado, r.detalle, r.created_at,
                    c.fecha, c.hora_inicio, p.nombre_completo AS paciente
             FROM recordatorio_cita r
             LEFT JOIN cita c ON c.id_cita = r.id_cita
             LEFT JOIN usuario p ON p.id_usuario = c.id_paciente
             ORDER BY r.created_at DESC, r.id_recordatorio DESC
             LIMIT $limite"
        )->fetchAll();
    }
}
