<?php

require_once ROOT_PATH . '/database/Database.php';

/**
 * ReporteAsistente.php — ASI-02 / ASI-03
 * Reportes de molestias que el paciente registra con el asistente, ya con su
 * prioridad. También guarda las reglas de clasificación que define la clínica.
 */
class ReporteAsistente
{
    public const PRIORIDADES = ['normal', 'prioritario', 'urgente'];

    public const ZONAS = [
        'diente'     => 'Diente o muela',
        'encias'     => 'Encías',
        'mandibula'  => 'Mandíbula o articulación',
        'cara'       => 'Mejilla o cara',
        'lengua'     => 'Lengua, paladar o labios',
        'ortodoncia' => 'Brackets o alambres',
        'restauracion' => 'Empaste, corona o prótesis',
        'otra'       => 'Otra zona',
    ];

    public const TIEMPOS = [
        'menos_24h'  => 'Menos de 24 horas',
        '1_2_dias'   => '1 a 2 días',
        '3_7_dias'   => '3 a 7 días',
        'mas_semana' => 'Más de una semana',
    ];

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
            "CREATE TABLE IF NOT EXISTS asistente_reporte (
                id_reporte     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_usuario     INT NOT NULL,
                fecha_hora     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                descripcion    VARCHAR(500) NOT NULL,
                zona           VARCHAR(30)  NOT NULL,
                intensidad     TINYINT UNSIGNED NOT NULL,
                tiempo         VARCHAR(20)  NOT NULL,
                prioridad      ENUM('normal','prioritario','urgente') NOT NULL,
                criterios      VARCHAR(500) NULL,
                estado         ENUM('registrado','en_seguimiento','atendido') NOT NULL DEFAULT 'registrado',
                INDEX idx_asi_rep_usuario (id_usuario),
                INDEX idx_asi_rep_fecha (fecha_hora),
                INDEX idx_asi_rep_prioridad (prioridad)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS asistente_regla_prioridad (
                id_regla  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                nivel     ENUM('prioritario','urgente') NOT NULL,
                campo     ENUM('intensidad','tiempo','descripcion') NOT NULL,
                operador  ENUM('>=','en','contiene') NOT NULL,
                valor     VARCHAR(500) NOT NULL,
                detalle   VARCHAR(150) NOT NULL,
                activa    TINYINT(1) NOT NULL DEFAULT 1,
                INDEX idx_asi_regla_nivel (nivel, activa)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->sembrarReglas();

        self::$tablasListas = true;
    }

    private function sembrarReglas(): void
    {
        $total = (int) $this->db->query("SELECT COUNT(*) FROM asistente_regla_prioridad")->fetchColumn();
        if ($total > 0) return;

        $reglas = [
            ['urgente', 'intensidad', '>=', '8', 'Dolor de intensidad 8 o más'],
            ['urgente', 'descripcion', 'contiene',
                'sangrado abundante,hemorragia,no para de sangrar,sangra mucho,cara hinchada,cara inflamada,hinchazon en la cara,hinchazon facial,'
                . 'fiebre,dificultad para respirar,dificultad para tragar,no puedo respirar,no puedo tragar,pus,absceso,'
                . 'golpe,trauma,accidente,diente caido,se cayo el diente,se me cayo un diente,diente roto,se me quebro,desmayo',
                'Síntoma de alarma descrito por el paciente'],
            ['prioritario', 'intensidad', '>=', '5', 'Dolor de intensidad 5 a 7'],
            ['prioritario', 'tiempo', 'en', '3_7_dias,mas_semana', 'Molestia con más de 48 horas de evolución'],
            ['prioritario', 'descripcion', 'contiene',
                'sangrado,sangra,sangran,sangro,sangre,inflamacion,inflamada,hinchazon,hinchado,empeora,cada vez peor,bracket roto,alambre,se solto,se despego,'
                . 'corona suelta,empaste caido,sensibilidad fuerte',
                'La descripción indica una molestia que requiere revisión'],
        ];

        $ins = $this->db->prepare(
            "INSERT INTO asistente_regla_prioridad (nivel, campo, operador, valor, detalle) VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($reglas as $r) $ins->execute($r);
    }

    /* ── Reglas ─────────────────────────────────────────────── */

    public function reglasActivas(): array
    {
        return $this->db
            ->query("SELECT * FROM asistente_regla_prioridad WHERE activa = 1 ORDER BY id_regla ASC")
            ->fetchAll();
    }

    /* ── Reportes ───────────────────────────────────────────── */

    public function crear(array $d): int
    {
        $this->db->prepare(
            "INSERT INTO asistente_reporte
                (id_usuario, fecha_hora, descripcion, zona, intensidad, tiempo, prioridad, criterios)
             VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)"
        )->execute([
            (int) $d['id_usuario'],
            $d['descripcion'],
            $d['zona'],
            (int) $d['intensidad'],
            $d['tiempo'],
            $d['prioridad'],
            $d['criterios'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    private const SELECT_BASE =
        "SELECT r.*, u.nombre_completo AS paciente, u.identificacion AS paciente_cedula
         FROM asistente_reporte r
         LEFT JOIN usuario u ON u.id_usuario = r.id_usuario";

    /** Para ASI-04: listado general (más recientes primero) */
    public function listar(array $f = []): array
    {
        $sql    = self::SELECT_BASE . " WHERE 1=1";
        $params = [];

        if (!empty($f['id_usuario'])) { $sql .= " AND r.id_usuario = ?"; $params[] = (int) $f['id_usuario']; }
        if (!empty($f['prioridad']) && in_array($f['prioridad'], self::PRIORIDADES, true)) {
            $sql .= " AND r.prioridad = ?";
            $params[] = $f['prioridad'];
        }
        if (!empty($f['desde'])) { $sql .= " AND DATE(r.fecha_hora) >= ?"; $params[] = $f['desde']; }
        if (!empty($f['hasta'])) { $sql .= " AND DATE(r.fecha_hora) <= ?"; $params[] = $f['hasta']; }

        $sql .= " ORDER BY r.fecha_hora DESC LIMIT 500";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Para ASI-04: detalle de un reporte */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT_BASE . " WHERE r.id_reporte = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}