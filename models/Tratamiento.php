<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Paciente.php';
require_once ROOT_PATH . '/models/ExpedienteClinico.php';

/**
 * Tratamiento.php — TRA-01 / TRA-02 / TRA-03 / TRA-04
 * Planes de tratamiento de los pacientes, con sus piezas dentales,
 * su historial de cambios y su vínculo con el expediente y el odontograma.
 */
class Tratamiento
{
    public const ESTADOS = ['pendiente', 'en_proceso', 'finalizado', 'cancelado'];

    public const ETIQUETAS = [
        'pendiente'  => 'Pendiente',
        'en_proceso' => 'En proceso',
        'finalizado' => 'Finalizado',
        'cancelado'  => 'Cancelado',
    ];

    private PDO $db;
    private static bool $tablasListas = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTablas();
    }

    /** Crea las tablas del módulo si no existen (mismo criterio que Cita y Bitacora) */
    private function asegurarTablas(): void
    {
        if (self::$tablasListas) return;

        // Estas dos clases crean sus propias tablas al instanciarse; las consultas
        // de este modelo hacen JOIN con ellas, así que deben existir antes.
        new Paciente();
        new ExpedienteClinico();

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS tratamiento (
                id_tratamiento     INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_paciente        INT UNSIGNED NOT NULL,
                id_odontologo      INT NOT NULL,
                id_expediente      INT UNSIGNED NULL,
                nombre             VARCHAR(120) NOT NULL,
                descripcion        TEXT NOT NULL,
                costo_estimado     DECIMAL(12,2) NOT NULL DEFAULT 0,
                fecha_inicio       DATE NOT NULL,
                fecha_fin_estimada DATE NOT NULL,
                fecha_fin_real     DATE NULL,
                estado             ENUM('pendiente','en_proceso','finalizado','cancelado') NOT NULL DEFAULT 'pendiente',
                progreso           TINYINT UNSIGNED NOT NULL DEFAULT 0,
                observaciones      TEXT NULL,
                fecha_asociacion   DATETIME NULL,
                created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_trat_paciente (id_paciente),
                INDEX idx_trat_odontologo (id_odontologo),
                INDEX idx_trat_expediente (id_expediente),
                INDEX idx_trat_estado (estado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS tratamiento_pieza (
                id_tratamiento INT UNSIGNED NOT NULL,
                numero_pieza   TINYINT UNSIGNED NOT NULL,
                PRIMARY KEY (id_tratamiento, numero_pieza),
                INDEX idx_trat_pieza (numero_pieza)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS tratamiento_historial (
                id_historial    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_tratamiento  INT UNSIGNED NOT NULL,
                accion          VARCHAR(20)  NOT NULL,
                estado_anterior VARCHAR(20)  NULL,
                estado_nuevo    VARCHAR(20)  NULL,
                observaciones   TEXT NULL,
                id_usuario      INT NULL,
                usuario_nombre  VARCHAR(100) NOT NULL DEFAULT 'Sistema',
                created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_trat_hist (id_tratamiento)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$tablasListas = true;
    }

    /* ── Utilidades estáticas ───────────────────────────────── */

    /** Piezas dentales válidas en notación FDI (igual que el odontograma) */
    public static function piezasValidas(): array
    {
        return array_merge(range(11, 18), range(21, 28), range(31, 38), range(41, 48));
    }

    /** Devuelve las piezas como enteros únicos y ordenados; descarta cualquier valor que no sea FDI */
    public static function normalizarPiezas(array $piezas): array
    {
        $validas = self::piezasValidas();
        $limpias = [];
        foreach ($piezas as $p) {
            $n = (int) $p;
            if (in_array($n, $validas, true)) $limpias[$n] = $n;
        }
        ksort($limpias);
        return array_values($limpias);
    }

    /**
     * Porcentaje de avance según el estado.
     * Pendiente = 0, finalizado = 100, en proceso = el valor indicado (1-99),
     * cancelado conserva el avance que tenía.
     */
    public static function calcularProgreso(string $estado, ?int $solicitado, int $actual = 0): int
    {
        return match ($estado) {
            'pendiente'  => 0,
            'finalizado' => 100,
            'en_proceso' => max(1, min(99, $solicitado ?? (($actual > 0 && $actual < 100) ? $actual : 25))),
            default      => $actual,
        };
    }

    /* ── Consultas ──────────────────────────────────────────── */

    private const SELECT_BASE =
        "SELECT t.*,
                up.nombre_completo AS paciente,
                up.identificacion  AS paciente_cedula,
                p.id_usuario       AS paciente_id_usuario,
                uo.nombre_completo AS odontologo
         FROM tratamiento t
         JOIN paciente p       ON p.id_paciente = t.id_paciente
         JOIN usuario  up      ON up.id_usuario = p.id_usuario
         LEFT JOIN usuario uo  ON uo.id_usuario = t.id_odontologo";

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT_BASE . " WHERE t.id_tratamiento = ?");
        $stmt->execute([$id]);
        $fila = $stmt->fetch();
        if (!$fila) return null;
        return $this->adjuntarPiezas([$fila])[0];
    }

    /**
     * Consulta con filtros (TRA-02 esc. 1, TRA-04).
     * Filtros: id_paciente, id_odontologo, q (paciente, cédula o nombre del tratamiento),
     *          estado (texto o lista de estados)
     */
    public function consultar(array $f = []): array
    {
        $sql    = self::SELECT_BASE . " WHERE 1=1";
        $params = [];

        if (!empty($f['id_paciente']))   { $sql .= " AND t.id_paciente = ?";   $params[] = (int) $f['id_paciente']; }
        if (!empty($f['id_odontologo'])) { $sql .= " AND t.id_odontologo = ?"; $params[] = (int) $f['id_odontologo']; }

        if (($f['q'] ?? '') !== '') {
            $sql .= " AND (up.nombre_completo LIKE ? OR up.identificacion LIKE ? OR t.nombre LIKE ?)";
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like);
        }

        if (!empty($f['estado'])) {
            $estados = array_values(array_intersect((array) $f['estado'], self::ESTADOS));
            if ($estados) {
                $sql .= " AND t.estado IN (" . implode(',', array_fill(0, count($estados), '?')) . ")";
                array_push($params, ...$estados);
            }
        }

        $sql .= " ORDER BY FIELD(t.estado,'en_proceso','pendiente','finalizado','cancelado'),
                           t.fecha_inicio DESC, t.id_tratamiento DESC
                  LIMIT 500";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->adjuntarPiezas($stmt->fetchAll());
    }

    /** Agrega a cada tratamiento su lista de piezas dentales ('piezas' => [11, 12, ...]) */
    private function adjuntarPiezas(array $filas): array
    {
        if (!$filas) return [];

        $ids = array_map(fn($t) => (int) $t['id_tratamiento'], $filas);
        $stmt = $this->db->prepare(
            "SELECT id_tratamiento, numero_pieza FROM tratamiento_pieza
             WHERE id_tratamiento IN (" . implode(',', array_fill(0, count($ids), '?')) . ")
             ORDER BY numero_pieza"
        );
        $stmt->execute($ids);

        $mapa = [];
        foreach ($stmt->fetchAll() as $r) {
            $mapa[(int) $r['id_tratamiento']][] = (int) $r['numero_pieza'];
        }
        foreach ($filas as &$t) {
            $t['piezas'] = $mapa[(int) $t['id_tratamiento']] ?? [];
        }
        unset($t);
        return $filas;
    }

    /** Pacientes activos para el formulario, indicando si ya tienen expediente clínico */
    public function pacientesActivos(): array
    {
                // Usuarios con rol "paciente" que todavía no tienen su fila clínica (por ejemplo,
        // los creados antes de que existiera esa tabla). Se completa solo, sin duplicar nada.
        $this->db->exec(
            "INSERT IGNORE INTO paciente (id_usuario, estado)
             SELECT u.id_usuario, 'activo'
             FROM usuario u
             WHERE u.estado = 'activo'
               AND EXISTS (SELECT 1 FROM usuario_rol ur
                           JOIN rol r ON r.id_rol = ur.id_rol
                           WHERE ur.id_usuario = u.id_usuario AND r.nombre = 'paciente')"
        );
        return $this->db->query(
            "SELECT p.id_paciente, u.nombre_completo, u.identificacion, e.id_expediente
             FROM paciente p
             JOIN usuario u ON u.id_usuario = p.id_usuario
             LEFT JOIN expediente_clinico e ON e.id_paciente = p.id_paciente
             WHERE p.estado = 'activo'
             ORDER BY u.nombre_completo ASC"
        )->fetchAll();
    }

    public function existePaciente(int $idPaciente): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM paciente WHERE id_paciente = ?");
        $stmt->execute([$idPaciente]);
        return (bool) $stmt->fetchColumn();
    }

    /** Para el portal del paciente: id_paciente a partir del usuario en sesión */
    public function idPacientePorUsuario(int $idUsuario): ?int
    {
        $stmt = $this->db->prepare("SELECT id_paciente FROM paciente WHERE id_usuario = ?");
        $stmt->execute([$idUsuario]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    public function expedienteDePaciente(int $idPaciente): ?int
    {
        $stmt = $this->db->prepare("SELECT id_expediente FROM expediente_clinico WHERE id_paciente = ?");
        $stmt->execute([$idPaciente]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    /* ── Escritura ──────────────────────────────────────────── */

    /**
     * TRA-01: crea el plan con sus piezas y deja constancia en el historial.
     * $d: id_paciente, id_odontologo, nombre, descripcion, costo_estimado, fecha_inicio,
     *     fecha_fin_estimada, estado, progreso, observaciones (opcional), piezas[],
     *     id_usuario, usuario_nombre
     */
    public function crear(array $d): int
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "INSERT INTO tratamiento
                 (id_paciente, id_odontologo, nombre, descripcion, costo_estimado,
                  fecha_inicio, fecha_fin_estimada, fecha_fin_real, estado, progreso, observaciones)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                (int) $d['id_paciente'],
                (int) $d['id_odontologo'],
                $d['nombre'],
                $d['descripcion'],
                $d['costo_estimado'],
                $d['fecha_inicio'],
                $d['fecha_fin_estimada'],
                $d['estado'] === 'finalizado' ? date('Y-m-d') : null,
                $d['estado'],
                (int) $d['progreso'],
                ($d['observaciones'] ?? '') !== '' ? $d['observaciones'] : null,
            ]);
            $id = (int) $this->db->lastInsertId();

            $this->guardarPiezas($id, $d['piezas']);
            $this->registrarHistorial(
                $id, 'creado', null, $d['estado'], 'Plan de tratamiento creado.',
                $d['id_usuario'] ?? null, $d['usuario_nombre'] ?? 'Sistema'
            );

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * TRA-02 esc. 2: cambia el estado y las observaciones, y registra quién y cuándo.
     * Devuelve el estado anterior.
     */
    public function actualizarEstado(int $id, string $estado, ?string $observaciones, ?int $progreso, ?int $idUsuario, string $usuarioNombre): string
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT estado, progreso FROM tratamiento WHERE id_tratamiento = ? FOR UPDATE");
            $stmt->execute([$id]);
            $actual = $stmt->fetch();
            if (!$actual) throw new RuntimeException('Tratamiento no encontrado');

            $nuevoProgreso = self::calcularProgreso($estado, $progreso, (int) $actual['progreso']);

            $this->db->prepare(
                "UPDATE tratamiento
                 SET estado = ?, progreso = ?, fecha_fin_real = ?,
                     observaciones = COALESCE(?, observaciones)
                 WHERE id_tratamiento = ?"
            )->execute([
                $estado,
                $nuevoProgreso,
                $estado === 'finalizado' ? date('Y-m-d') : null,
                ($observaciones ?? '') !== '' ? $observaciones : null,
                $id,
            ]);

            $this->registrarHistorial(
                $id, 'estado', $actual['estado'], $estado,
                ($observaciones ?? '') !== '' ? $observaciones : null,
                $idUsuario, $usuarioNombre
            );

            $this->db->commit();
            return $actual['estado'];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** TRA-03: vincula el tratamiento al expediente y fija las piezas que se reflejan en el odontograma */
    public function asociar(int $id, int $idExpediente, array $piezas, ?int $idUsuario, string $usuarioNombre): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "UPDATE tratamiento SET id_expediente = ?, fecha_asociacion = NOW() WHERE id_tratamiento = ?"
            )->execute([$idExpediente, $id]);

            $this->guardarPiezas($id, $piezas);
            $this->registrarHistorial(
                $id, 'asociado', null, null,
                'Asociado al expediente y al odontograma. Piezas: ' . implode(', ', $piezas) . '.',
                $idUsuario, $usuarioNombre
            );

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** Reemplaza las piezas del tratamiento por la lista indicada */
    private function guardarPiezas(int $idTratamiento, array $piezas): void
    {
        $this->db->prepare("DELETE FROM tratamiento_pieza WHERE id_tratamiento = ?")->execute([$idTratamiento]);

        $ins = $this->db->prepare("INSERT INTO tratamiento_pieza (id_tratamiento, numero_pieza) VALUES (?, ?)");
        foreach ($piezas as $pieza) {
            $ins->execute([$idTratamiento, (int) $pieza]);
        }
    }

    private function registrarHistorial(int $id, string $accion, ?string $anterior, ?string $nuevo, ?string $obs, ?int $idUsuario, string $usuarioNombre): void
    {
        $this->db->prepare(
            "INSERT INTO tratamiento_historial
             (id_tratamiento, accion, estado_anterior, estado_nuevo, observaciones, id_usuario, usuario_nombre)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        )->execute([$id, $accion, $anterior, $nuevo, $obs, $idUsuario, mb_substr($usuarioNombre, 0, 100)]);
    }

    /** Línea de tiempo de un tratamiento, de lo más reciente a lo más antiguo */
    public function historial(int $id): array
    {
        $stmt = $this->db->prepare(
            "SELECT accion, estado_anterior, estado_nuevo, observaciones, usuario_nombre, created_at
             FROM tratamiento_historial
             WHERE id_tratamiento = ?
             ORDER BY created_at DESC, id_historial DESC"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    /**
     * TRA-03 esc. 2 / CLI-03 esc. 2: tratamientos por pieza dental de un expediente.
     * Devuelve [numero_pieza => [ ['id'=>, 'nombre'=>, 'estado'=>], ... ]]; omite los cancelados.
     */
    public function porExpediente(int $idExpediente): array
    {
        $stmt = $this->db->prepare(
            "SELECT tp.numero_pieza, t.id_tratamiento, t.nombre, t.estado
             FROM tratamiento t
             JOIN tratamiento_pieza tp ON tp.id_tratamiento = t.id_tratamiento
             WHERE t.id_expediente = ? AND t.estado <> 'cancelado'
             ORDER BY tp.numero_pieza,
                      FIELD(t.estado,'en_proceso','pendiente','finalizado'),
                      t.id_tratamiento DESC"
        );
        $stmt->execute([$idExpediente]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $r) {
            $mapa[(int) $r['numero_pieza']][] = [
                'id'     => (int) $r['id_tratamiento'],
                'nombre' => $r['nombre'],
                'estado' => $r['estado'],
            ];
        }
        return $mapa;
    }
}
