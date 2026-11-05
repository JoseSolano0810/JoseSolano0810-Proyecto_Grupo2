<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Cita.php';
require_once ROOT_PATH . '/models/ReporteAsistente.php';

/** ASI-05: el horario elegido por el paciente ya no está libre */
class HorarioSolicitudNoDisponibleException extends RuntimeException {}

/**
 * SolicitudCitaAsistente.php — ASI-05
 * Solicitudes de cita que el paciente genera a partir de un reporte del asistente.
 * La solicitud puede llevar un horario elegido o ir sin horario para que el personal la coordine;
 * una vez programada, queda enlazada con la cita real del módulo de Citas.
 */
class SolicitudCitaAsistente
{
    public const ESTADOS = ['pendiente', 'programada', 'descartada'];

    public const ESTADOS_TEXTO = [
        'pendiente'  => 'Pendiente',
        'programada' => 'Programada',
        'descartada' => 'Descartada',
    ];

    public const DURACION_MIN      = 30;  // duración de cada horario que se ofrece
    public const ANTICIPACION_MIN  = 60;  // no se ofrecen horarios que empiecen en menos de 1 hora
    public const DIAS_MAX_VISTA    = 7;   // máximo de días con horarios que se muestran
    private const DIAS_A_REVISAR   = 14;  // cuántos días hacia adelante se revisan

    private PDO $db;
    private Cita $citas;
    private static bool $tablasListas = false;

    public function __construct()
    {
        $this->db    = Database::getInstance()->getConnection();
        $this->citas = new Cita();      // también garantiza que exista la tabla cita
        $this->asegurarTablas();
    }

    /** Crea la tabla si no existe (mismo criterio que Cita y ReporteAsistente) */
    private function asegurarTablas(): void
    {
        if (self::$tablasListas) return;

        new ReporteAsistente();         // garantiza que exista asistente_reporte

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS asistente_solicitud_cita (
                id_solicitud  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_reporte    INT UNSIGNED NOT NULL,
                id_usuario    INT NOT NULL,
                id_odontologo INT NULL,
                fecha         DATE NULL,
                hora_inicio   TIME NULL,
                hora_fin      TIME NULL,
                motivo        VARCHAR(500) NOT NULL,
                estado        ENUM('pendiente','programada','descartada') NOT NULL DEFAULT 'pendiente',
                id_cita       INT UNSIGNED NULL,
                created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_asi_sol_reporte (id_reporte),
                INDEX idx_asi_sol_usuario (id_usuario),
                INDEX idx_asi_sol_estado (estado),
                INDEX idx_asi_sol_horario (id_odontologo, fecha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$tablasListas = true;
    }

    /* ── Utilidades de hora ─────────────────────────────────── */

    /** 'HH:MM' o 'HH:MM:SS' → minutos desde la medianoche */
    private static function minutos(string $hora): int
    {
        $p = explode(':', $hora);
        return ((int) ($p[0] ?? 0)) * 60 + (int) ($p[1] ?? 0);
    }

    private static function hhmm(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }

    /** Horario de la clínica según config.php: [apertura, cierre, días de atención] */
    private function horarioClinica(): array
    {
        $apertura = defined('CLINICA_HORA_APERTURA') ? (string) CLINICA_HORA_APERTURA : '09:30';
        $cierre   = defined('CLINICA_HORA_CIERRE')   ? (string) CLINICA_HORA_CIERRE   : '18:00';
        $dias     = defined('CLINICA_DIAS_ATENCION') ? (array) CLINICA_DIAS_ATENCION  : [1, 2, 3, 4, 5];

        return [self::minutos($apertura), self::minutos($cierre), array_map('intval', $dias)];
    }

    /* ── Horarios disponibles (ASI-05 esc. 1 y 2) ───────────── */

    /**
     * Horarios ocupados por odontólogo y fecha, en minutos: [id_odontologo][fecha] => [[ini, fin], ...]
     * Cuenta las citas no canceladas (igual que el módulo de Citas) y las solicitudes pendientes
     * que ya reservaron un horario, para no ofrecer el mismo espacio a dos pacientes.
     */
    private function ocupados(string $desde, string $hasta): array
    {
        $mapa = [];

        $consultas = [
            "SELECT id_odontologo, fecha, hora_inicio, hora_fin
             FROM cita WHERE fecha BETWEEN ? AND ? AND estado <> 'cancelada'",
            "SELECT id_odontologo, fecha, hora_inicio, hora_fin
             FROM asistente_solicitud_cita
             WHERE estado = 'pendiente' AND id_odontologo IS NOT NULL AND fecha BETWEEN ? AND ?",
        ];

        foreach ($consultas as $sql) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$desde, $hasta]);
            foreach ($stmt->fetchAll() as $r) {
                $mapa[(int) $r['id_odontologo']][$r['fecha']][] = [self::minutos($r['hora_inicio']), self::minutos($r['hora_fin'])];
            }
        }
        return $mapa;
    }

    private function libre(array $ocupados, int $odontologo, string $fecha, int $inicio, int $fin): bool
    {
        foreach ($ocupados[$odontologo][$fecha] ?? [] as [$oi, $of]) {
            if ($oi < $fin && $of > $inicio) return false;
        }
        return true;
    }

    /**
     * Próximos horarios libres. Para cada espacio de 30 minutos se asigna el primer odontólogo
     * (por orden alfabético) que esté libre.
     * Devuelve [ ['fecha' => 'YYYY-MM-DD', 'horas' => [ ['hora' => 'HH:MM', 'id_odontologo' => n, 'odontologo' => '...'], ... ]], ... ]
     */
    public function disponibilidad(): array
    {
        $odontologos = $this->citas->odontologosActivos();
        if (!$odontologos) return [];

        [$apertura, $cierre, $diasAtencion] = $this->horarioClinica();

        $ahora  = new DateTimeImmutable('now');
        $limite = $ahora->modify('+' . self::ANTICIPACION_MIN . ' minutes');
        $hoy    = $ahora->setTime(0, 0);
        $ultimo = $hoy->modify('+' . (self::DIAS_A_REVISAR - 1) . ' days');

        $ocupados  = $this->ocupados($hoy->format('Y-m-d'), $ultimo->format('Y-m-d'));
        $resultado = [];

        for ($i = 0; $i < self::DIAS_A_REVISAR && count($resultado) < self::DIAS_MAX_VISTA; $i++) {
            $dia = $hoy->modify("+$i days");
            if (!in_array((int) $dia->format('N'), $diasAtencion, true)) continue;

            $fecha = $dia->format('Y-m-d');
            $horas = [];

            for ($ini = $apertura; $ini + self::DURACION_MIN <= $cierre; $ini += self::DURACION_MIN) {
                if ($dia->setTime(intdiv($ini, 60), $ini % 60) < $limite) continue;

                foreach ($odontologos as $o) {
                    $id = (int) $o['id_usuario'];
                    if ($this->libre($ocupados, $id, $fecha, $ini, $ini + self::DURACION_MIN)) {
                        $horas[] = ['hora' => self::hhmm($ini), 'id_odontologo' => $id, 'odontologo' => $o['nombre_completo']];
                        break;
                    }
                }
            }

            if ($horas) $resultado[] = ['fecha' => $fecha, 'horas' => $horas];
        }

        return $resultado;
    }

    /**
     * Revisa que fecha y hora sean un espacio válido de la clínica (día de atención, dentro del
     * horario, en la cuadrícula de 30 min, con la anticipación mínima y dentro de la ventana).
     * Devuelve el mensaje de error o null si todo está bien.
     */
    public function validarHorario(string $fecha, string $hora): ?string
    {
        $d = DateTimeImmutable::createFromFormat('Y-m-d H:i', $fecha . ' ' . $hora);
        if (!$d || $d->format('Y-m-d H:i') !== $fecha . ' ' . $hora) {
            return 'El horario seleccionado no tiene un formato válido.';
        }

        [$apertura, $cierre, $diasAtencion] = $this->horarioClinica();
        $ini = self::minutos($hora);

        if (!in_array((int) $d->format('N'), $diasAtencion, true)) {
            return 'La clínica no atiende ese día.';
        }
        if ($ini < $apertura || $ini + self::DURACION_MIN > $cierre || ($ini - $apertura) % self::DURACION_MIN !== 0) {
            return 'Ese horario está fuera del horario de atención de la clínica.';
        }

        $ahora = new DateTimeImmutable('now');
        if ($d < $ahora->modify('+' . self::ANTICIPACION_MIN . ' minutes')) {
            return 'Ese horario ya no está disponible. Elija uno más adelante.';
        }
        if ($d > $ahora->setTime(0, 0)->modify('+' . self::DIAS_A_REVISAR . ' days')) {
            return 'Ese horario está demasiado lejos. Elija uno de los que se ofrecen.';
        }
        return null;
    }

    public function odontologoActivo(int $idUsuario): bool
    {
        return $this->citas->usuarioTieneRol($idUsuario, 'odontologo');
    }

    /* ── Escritura ──────────────────────────────────────────── */

    /**
     * Registra la solicitud. Si trae horario, lo reserva: dentro de una transacción vuelve a
     * comprobar que siga libre (por si otro paciente o recepción lo tomó mientras tanto).
     * $d: id_reporte, id_usuario, motivo, y opcionalmente id_odontologo, fecha, hora_inicio, hora_fin
     */
    public function crear(array $d): int
    {
        $conHorario = !empty($d['fecha']);

        $this->db->beginTransaction();
        try {
            if ($conHorario) {
                $this->exigirHorarioLibre((int) $d['id_odontologo'], $d['fecha'], $d['hora_inicio'], $d['hora_fin']);
            }

            $this->db->prepare(
                "INSERT INTO asistente_solicitud_cita
                 (id_reporte, id_usuario, id_odontologo, fecha, hora_inicio, hora_fin, motivo)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                (int) $d['id_reporte'],
                (int) $d['id_usuario'],
                $conHorario ? (int) $d['id_odontologo'] : null,
                $conHorario ? $d['fecha'] : null,
                $conHorario ? $d['hora_inicio'] : null,
                $conHorario ? $d['hora_fin'] : null,
                $d['motivo'],
            ]);
            $id = (int) $this->db->lastInsertId();

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    private function exigirHorarioLibre(int $odontologo, string $fecha, string $inicio, string $fin): void
    {
        $consultas = [
            "SELECT 1 FROM cita
             WHERE id_odontologo = ? AND fecha = ? AND estado <> 'cancelada'
               AND hora_inicio < ? AND hora_fin > ? LIMIT 1 FOR UPDATE",
            "SELECT 1 FROM asistente_solicitud_cita
             WHERE id_odontologo = ? AND fecha = ? AND estado = 'pendiente'
               AND hora_inicio < ? AND hora_fin > ? LIMIT 1 FOR UPDATE",
        ];

        foreach ($consultas as $sql) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$odontologo, $fecha, $fin, $inicio]);
            if ($stmt->fetchColumn()) {
                throw new HorarioSolicitudNoDisponibleException('Ese horario acaba de ser tomado. Elija otro, por favor.');
            }
        }
    }

    /* ── Consultas ──────────────────────────────────────────── */

    private const SELECT_BASE =
        "SELECT s.*,
                up.nombre_completo AS paciente,
                up.identificacion  AS paciente_cedula,
                up.telefono        AS paciente_telefono,
                uo.nombre_completo AS odontologo,
                r.prioridad        AS reporte_prioridad,
                r.descripcion      AS reporte_descripcion,
                r.fecha_hora       AS reporte_fecha
         FROM asistente_solicitud_cita s
         JOIN usuario up             ON up.id_usuario = s.id_usuario
         JOIN asistente_reporte r    ON r.id_reporte  = s.id_reporte
         LEFT JOIN usuario uo        ON uo.id_usuario = s.id_odontologo";

    public function porReporte(int $idReporte): ?array
    {
        $stmt = $this->db->prepare(self::SELECT_BASE . " WHERE s.id_reporte = ?");
        $stmt->execute([$idReporte]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT_BASE . " WHERE s.id_solicitud = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Bandeja del personal. Filtro: estado. Las pendientes van primero, las más urgentes arriba
     * y, dentro de la misma prioridad, las que llevan más tiempo esperando.
     */
    public function listar(array $f = []): array
    {
        $sql    = self::SELECT_BASE . " WHERE 1=1";
        $params = [];

        if (!empty($f['estado']) && in_array($f['estado'], self::ESTADOS, true)) {
            $sql .= " AND s.estado = ?";
            $params[] = $f['estado'];
        }

        $sql .= " ORDER BY FIELD(s.estado,'pendiente','programada','descartada'),
                           FIELD(r.prioridad,'urgente','prioritario','normal'),
                           s.created_at ASC
                  LIMIT 300";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** La solicitud ya se convirtió en una cita real del módulo de Citas */
    public function marcarProgramada(int $id, int $idCita): void
    {
        $this->db->prepare(
            "UPDATE asistente_solicitud_cita SET estado = 'programada', id_cita = ? WHERE id_solicitud = ?"
        )->execute([$idCita, $id]);
    }

    /** El personal decide no programarla (libera el horario que tuviera reservado) */
    public function descartar(int $id): void
    {
        $this->db->prepare(
            "UPDATE asistente_solicitud_cita SET estado = 'descartada' WHERE id_solicitud = ?"
        )->execute([$id]);
    }
}
