<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Cita.php';

/**
 * Reporte.php — REP-01 y REP-02
 * Consultas de solo lectura para los reportes del administrador.
 */
class Reporte
{
    /** A partir de este largo de período, la serie se agrupa por mes en vez de por día */
    private const DIAS_MAX_SERIE_DIARIA = 62;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        new Cita();   // garantiza que exista la tabla cita
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
        $dias   = (int) round((strtotime($hasta) - strtotime($desde)) / 86400) + 1;
        $porMes = $dias > self::DIAS_MAX_SERIE_DIARIA;
        $expr   = $porMes ? "DATE_FORMAT(fecha, '%Y-%m')" : "fecha";

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
}
