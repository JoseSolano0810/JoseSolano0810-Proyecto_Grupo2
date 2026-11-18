<?php

require_once ROOT_PATH . '/database/Database.php';

/**
 * Notificacion.php — PAC-08
 * Notificaciones del portal del paciente (campana del topbar).
 */
class Notificacion
{
    public const TIPO_AVANCE = 'avance_tratamiento';

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

    public function listar(int $idUsuario, int $limite = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_notificacion, tipo, titulo, mensaje, id_tratamiento, leida, created_at
             FROM notificacion
             WHERE id_usuario = ?
             ORDER BY created_at DESC, id_notificacion DESC
             LIMIT " . max(1, (int) $limite)
        );
        $stmt->execute([$idUsuario]);
        return $stmt->fetchAll();
    }

    public function contarNoLeidas(int $idUsuario): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notificacion WHERE id_usuario = ? AND leida = 0");
        $stmt->execute([$idUsuario]);
        return (int) $stmt->fetchColumn();
    }

    public function marcarLeida(int $id, int $idUsuario): void
    {
        $this->db->prepare("UPDATE notificacion SET leida = 1 WHERE id_notificacion = ? AND id_usuario = ?")
                 ->execute([$id, $idUsuario]);
    }

    public function marcarTodasLeidas(int $idUsuario): void
    {
        $this->db->prepare("UPDATE notificacion SET leida = 1 WHERE id_usuario = ? AND leida = 0")
                 ->execute([$idUsuario]);
    }
}