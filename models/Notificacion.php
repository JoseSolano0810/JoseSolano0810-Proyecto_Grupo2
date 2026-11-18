<?php

require_once ROOT_PATH . '/database/Database.php';

/**
 * Notificacion.php — PAC-08
 * Notificaciones del portal del paciente (campana del topbar).
 */
class Notificacion
{
    public const TIPO_AVANCE     = 'avance_tratamiento';   // paciente
    public const TIPO_REPORTE    = 'reporte_asistente';    // personal: paciente reportó una molestia
    public const TIPO_SOLICITUD  = 'solicitud_cita';       // personal: paciente pidió cita desde el asistente
    public const TIPO_INSUMO     = 'insumo_bajo';          // personal: insumo bajo el nivel mínimo
    public const TIPO_CITA       = 'cita_nueva';           // odontólogo: nueva cita asignada

    /** Sección a la que lleva cada tipo al hacer clic (null = el paciente abre su tratamiento) */
    public const DESTINOS = [
        self::TIPO_REPORTE   => 'asistente-reportes',
        self::TIPO_SOLICITUD => 'asistente-reportes',
        self::TIPO_INSUMO    => 'inventario',
        self::TIPO_CITA      => 'agenda',
    ];

    /** Tipos que ve cada rol activo en su campana (un usuario con varios roles ve solo los del rol activo) */
    public static function tiposDeRol(string $rol): array
    {
        return match ($rol) {
            'paciente'         => [self::TIPO_AVANCE],
            'administrador'    => [self::TIPO_REPORTE, self::TIPO_SOLICITUD, self::TIPO_INSUMO, self::TIPO_CITA],
            'odontologo'       => [self::TIPO_CITA],
            'recepcionista',
            'asistente_dental' => [self::TIPO_REPORTE, self::TIPO_SOLICITUD, self::TIPO_INSUMO],
            default            => [],
        };
    }

    /** Placeholders y parámetros para "tipo IN (...)" */
    private static function enTipos(array $tipos): array
    {
        $tipos = $tipos ?: ['-'];
        return [implode(',', array_fill(0, count($tipos), '?')), array_values($tipos)];
    }

    private PDO $db;
    private static bool $tablaLista = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->asegurarTabla();
    }

    private function asegurarTabla(): void
    {
        if (self::$tablaLista) return;

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS notificacion (
                id_notificacion INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_usuario      INT NOT NULL,
                tipo            VARCHAR(30)  NOT NULL,
                titulo          VARCHAR(120) NOT NULL,
                mensaje         VARCHAR(255) NOT NULL,
                id_tratamiento  INT UNSIGNED NULL,
                envio_correo    VARCHAR(12)  NULL,
                leida           TINYINT(1)   NOT NULL DEFAULT 0,
                created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_notif_usuario (id_usuario, leida, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$tablaLista = true;
    }

    public function crear(int $idUsuario, string $tipo, string $titulo, string $mensaje, ?int $idTratamiento): int
    {
        $this->db->prepare(
            "INSERT INTO notificacion (id_usuario, tipo, titulo, mensaje, id_tratamiento)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$idUsuario, $tipo, mb_substr($titulo, 0, 120), mb_substr($mensaje, 0, 255), $idTratamiento]);

        return (int) $this->db->lastInsertId();
    }

    public function registrarEnvioCorreo(int $id, string $estado): void
    {
        $this->db->prepare("UPDATE notificacion SET envio_correo = ? WHERE id_notificacion = ?")
                 ->execute([$estado, $id]);
    }

    /** Una notificación, solo si pertenece al usuario indicado */
    public function obtenerDeUsuario(int $id, int $idUsuario): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM notificacion WHERE id_notificacion = ? AND id_usuario = ?");
        $stmt->execute([$id, $idUsuario]);
        return $stmt->fetch() ?: null;
    }

    public function listar(int $idUsuario, array $tipos, int $limite = 20): array
    {
        [$in, $par] = self::enTipos($tipos);
        $stmt = $this->db->prepare(
            "SELECT id_notificacion, tipo, titulo, mensaje, id_tratamiento, leida, created_at
             FROM notificacion
             WHERE id_usuario = ? AND tipo IN ($in)
             ORDER BY created_at DESC, id_notificacion DESC
             LIMIT " . max(1, (int) $limite)
        );
        $stmt->execute([$idUsuario, ...$par]);
        return $stmt->fetchAll();
    }

    public function contarNoLeidas(int $idUsuario, array $tipos): int
    {
        [$in, $par] = self::enTipos($tipos);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notificacion WHERE id_usuario = ? AND leida = 0 AND tipo IN ($in)");
        $stmt->execute([$idUsuario, ...$par]);
        return (int) $stmt->fetchColumn();
    }

    /** Ids de usuarios activos que tengan alguno de los roles (rol principal o asignado) */
    public function usuariosConRol(array $roles): array
    {
        if (!$roles) return [];
        $in = implode(',', array_fill(0, count($roles), '?'));
        $stmt = $this->db->prepare(
            "SELECT DISTINCT u.id_usuario
             FROM usuario u
             LEFT JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
             JOIN rol r ON r.id_rol = u.id_rol OR r.id_rol = ur.id_rol
             WHERE u.estado = 'activo' AND r.nombre IN ($in)"
        );
        $stmt->execute(array_values($roles));
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function marcarLeida(int $id, int $idUsuario): void
    {
        $this->db->prepare("UPDATE notificacion SET leida = 1 WHERE id_notificacion = ? AND id_usuario = ?")
                 ->execute([$id, $idUsuario]);
    }

    public function marcarTodasLeidas(int $idUsuario, array $tipos): void
    {
        [$in, $par] = self::enTipos($tipos);
        $this->db->prepare("UPDATE notificacion SET leida = 1 WHERE id_usuario = ? AND leida = 0 AND tipo IN ($in)")
                 ->execute([$idUsuario, ...$par]);
    }
}