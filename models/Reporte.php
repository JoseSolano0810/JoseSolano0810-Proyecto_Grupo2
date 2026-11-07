<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Cita.php';

/**
 * Reporte.php — REP-01 y REP-02
 * Consultas de los reportes del administrador. Los ingresos se leen de la tabla `pago`
 * (módulo de Finanzas) y los egresos de la tabla `egreso`, que crea y administra este modelo.
 */
class Reporte
{
    /** A partir de este largo de período, las series se agrupan por mes en vez de por día */
    private const DIAS_MAX_SERIE_DIARIA = 62;

    public const CATEGORIAS = [
        'insumos'   => 'Insumos y materiales',
        'salarios'  => 'Salarios',
        'servicios' => 'Servicios (luz, agua, internet)',
        'alquiler'  => 'Alquiler',
        'equipo'    => 'Equipo y mantenimiento',
        'otros'     => 'Otros',
    ];

    /** Mismos valores que el ENUM de pago.metodo del módulo de Finanzas */
    public const METODOS = [
        'sinpe'         => 'SINPE Móvil',
        'efectivo'      => 'Efectivo',
        'tarjeta'       => 'Tarjeta',
        'transferencia' => 'Transferencia',
    ];

    private PDO $db;
    private static bool $tablasListas = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        new Cita();   // garantiza que exista la tabla cita
        $this->asegurarTablas();
    }

    private function asegurarTablas(): void
    {
        if (self::$tablasListas) return;

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS egreso (
                id_egreso         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                fecha             DATE NOT NULL,
                categoria         ENUM('insumos','salarios','servicios','alquiler','equipo','otros') NOT NULL DEFAULT 'otros',
                concepto          VARCHAR(150) NOT NULL,
                monto             DECIMAL(12,2) NOT NULL,
                observaciones     VARCHAR(500) NULL,
                id_registrado_por INT NOT NULL,
                created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_egreso_fecha (fecha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$tablasListas = true;
    }

    private function existeTabla(string $tabla): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?"
        );
        $stmt->execute([$tabla]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** ¿Agrupar por mes? Depende de lo largo del período */
    private function porMes(string $desde, string $hasta): array
    {
        $dias = (int) round((strtotime($hasta) - strtotime($desde)) / 86400) + 1;
        return [$dias > self::DIAS_MAX_SERIE_DIARIA, $dias];
    }

    /* ── REP-02: citas ──────────────────────────────────────── */

    /**
     * Citas del período [$desde, $hasta] (formato Y-m-d, ambos días incluidos).
     * Si no hay ninguna devuelve ['total' => 0].
     */
    public function citas(string $desde, string $hasta): array
    {
        $params = [$desde, $hasta];

        // Cantidad por estado
        $stmt = $this->db->prepare("SELECT estado, COUNT(*) AS n FROM cita WHERE fecha BETWEEN ? AND ? GROUP BY estado");
        $stmt->execute($params);

        $porEstado = array_fill_keys(Cita::ESTADOS, 0);
        foreach ($stmt->fetchAll() as $r) {
            $porEstado[$r['estado']] = (int) $r['n'];
        }
        $total = array_sum($porEstado);
        if ($total === 0) return ['total' => 0];

        // Productividad por odontólogo
        $stmt = $this->db->prepare(
            "SELECT c.id_odontologo,
                    COALESCE(u.nombre_completo, CONCAT('Odontólogo #', c.id_odontologo)) AS odontologo,
                    COUNT(*)                                          AS total,
                    SUM(c.estado = 'atendida')                        AS atendidas,
                    SUM(c.estado = 'cancelada')                       AS canceladas,
                    SUM(c.estado IN ('programada', 'confirmada'))     AS pendientes,
                    SUM(c.estado = 'ausente')                         AS ausentes
             FROM cita c
             LEFT JOIN usuario u ON u.id_usuario = c.id_odontologo
             WHERE c.fecha BETWEEN ? AND ?
             GROUP BY c.id_odontologo, u.nombre_completo
             ORDER BY atendidas DESC, total DESC, odontologo ASC"
        );
        $stmt->execute($params);
        $odontologos = array_map(fn($r) => [
            'id'         => (int) $r['id_odontologo'],
            'nombre'     => $r['odontologo'],
            'total'      => (int) $r['total'],
            'atendidas'  => (int) $r['atendidas'],
            'canceladas' => (int) $r['canceladas'],
            'pendientes' => (int) $r['pendientes'],
            'ausentes'   => (int) $r['ausentes'],
        ], $stmt->fetchAll());

        // Por tipo de consulta
        $stmt = $this->db->prepare(
            "SELECT tipo_consulta, COUNT(*) AS n FROM cita
             WHERE fecha BETWEEN ? AND ?
             GROUP BY tipo_consulta ORDER BY n DESC, tipo_consulta ASC"
        );
        $stmt->execute($params);
        $tipos = array_map(fn($r) => ['tipo' => $r['tipo_consulta'], 'cantidad' => (int) $r['n']], $stmt->fetchAll());

        // Serie en el tiempo: por día, o por mes si el período es largo
        [$porMes, $dias] = $this->porMes($desde, $hasta);
        $expr = $porMes ? "DATE_FORMAT(fecha, '%Y-%m')" : "fecha";

        $stmt = $this->db->prepare(
            "SELECT $expr AS periodo,
                    COUNT(*)                    AS total,
                    SUM(estado = 'atendida')    AS atendidas,
                    SUM(estado = 'cancelada')   AS canceladas
             FROM cita
             WHERE fecha BETWEEN ? AND ?
             GROUP BY periodo ORDER BY periodo ASC"
        );
        $stmt->execute($params);
        $serie = array_map(fn($r) => [
            'periodo'    => $r['periodo'],
            'total'      => (int) $r['total'],
            'atendidas'  => (int) $r['atendidas'],
            'canceladas' => (int) $r['canceladas'],
        ], $stmt->fetchAll());

        return [
            'total'       => $total,
            'por_estado'  => $porEstado,
            'odontologos' => $odontologos,
            'tipos'       => $tipos,
            'serie'       => $serie,
            'agrupacion'  => $porMes ? 'mes' : 'dia',
            'dias'        => $dias,
        ];
    }

    /* ── REP-01: financiero ─────────────────────────────────── */

    /**
     * Ingresos (tabla pago), egresos (tabla egreso) y su evolución en el período [$desde, $hasta].
     * Si el módulo de Finanzas todavía no creó la tabla `pago`, los ingresos quedan en cero.
     */
    public function financiero(string $desde, string $hasta): array
    {
        $params   = [$desde, $hasta];
        $hayPagos = $this->existeTabla('pago');
        [$porMes, $dias] = $this->porMes($desde, $hasta);

        // Ingresos por método de pago
        $metodos = [];
        if ($hayPagos) {
            $stmt = $this->db->prepare(
                "SELECT metodo, COUNT(*) AS n, SUM(monto) AS total
                 FROM pago WHERE fecha_pago BETWEEN ? AND ?
                 GROUP BY metodo ORDER BY total DESC"
            );
            $stmt->execute($params);
            $metodos = array_map(fn($r) => [
                'clave' => $r['metodo'], 'cantidad' => (int) $r['n'], 'total' => round((float) $r['total'], 2),
            ], $stmt->fetchAll());
        }

        // Egresos por categoría
        $stmt = $this->db->prepare(
            "SELECT categoria, COUNT(*) AS n, SUM(monto) AS total
             FROM egreso WHERE fecha BETWEEN ? AND ?
             GROUP BY categoria ORDER BY total DESC"
        );
        $stmt->execute($params);
        $categorias = array_map(fn($r) => [
            'clave' => $r['categoria'], 'cantidad' => (int) $r['n'], 'total' => round((float) $r['total'], 2),
        ], $stmt->fetchAll());

        // Evolución: ingresos y egresos por día (o por mes)
        $ing = [];
        if ($hayPagos) {
            $e = $porMes ? "DATE_FORMAT(fecha_pago, '%Y-%m')" : "fecha_pago";
            $stmt = $this->db->prepare("SELECT $e AS periodo, SUM(monto) AS total FROM pago WHERE fecha_pago BETWEEN ? AND ? GROUP BY periodo");
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $r) $ing[$r['periodo']] = round((float) $r['total'], 2);
        }

        $e = $porMes ? "DATE_FORMAT(fecha, '%Y-%m')" : "fecha";
        $stmt = $this->db->prepare("SELECT $e AS periodo, SUM(monto) AS total FROM egreso WHERE fecha BETWEEN ? AND ? GROUP BY periodo");
        $stmt->execute($params);
        $egr = [];
        foreach ($stmt->fetchAll() as $r) $egr[$r['periodo']] = round((float) $r['total'], 2);

        $periodos = array_unique(array_merge(array_keys($ing), array_keys($egr)));
        sort($periodos);
        $serie = array_map(fn($p) => ['periodo' => $p, 'ingresos' => $ing[$p] ?? 0.0, 'egresos' => $egr[$p] ?? 0.0], $periodos);

        // Gastos del período, para poder revisarlos o corregirlos
        $stmt = $this->db->prepare(
            "SELECT e.id_egreso, e.fecha, e.categoria, e.concepto, e.monto, e.observaciones,
                    COALESCE(u.nombre_completo, 'Sistema') AS registrado_por
             FROM egreso e
             LEFT JOIN usuario u ON u.id_usuario = e.id_registrado_por
             WHERE e.fecha BETWEEN ? AND ?
             ORDER BY e.fecha DESC, e.id_egreso DESC
             LIMIT 200"
        );
        $stmt->execute($params);

        return [
            'pagos_disponibles' => $hayPagos,
            'metodos'           => $metodos,
            'categorias'        => $categorias,
            'serie'             => $serie,
            'gastos'            => $stmt->fetchAll(),
            'agrupacion'        => $porMes ? 'mes' : 'dia',
            'dias'              => $dias,
        ];
    }

    /* ── Egresos (gastos) ───────────────────────────────────── */

    public function crearEgreso(array $d): int
    {
        $this->db->prepare(
            "INSERT INTO egreso (fecha, categoria, concepto, monto, observaciones, id_registrado_por)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([
            $d['fecha'], $d['categoria'], $d['concepto'], $d['monto'],
            ($d['observaciones'] ?? '') !== '' ? $d['observaciones'] : null,
            (int) $d['id_registrado_por'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function obtenerEgreso(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM egreso WHERE id_egreso = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function eliminarEgreso(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM egreso WHERE id_egreso = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
