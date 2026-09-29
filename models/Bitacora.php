<?php

class Bitacora
{
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
            "CREATE TABLE IF NOT EXISTS bitacora (
                id_bitacora    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_usuario     INT NULL,
                usuario_nombre VARCHAR(100) NOT NULL DEFAULT 'Sistema',
                accion         VARCHAR(40)  NOT NULL,
                descripcion    VARCHAR(255) NOT NULL,
                ip             VARCHAR(45)  NULL,
                created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_bitacora_fecha (created_at),
                INDEX idx_bitacora_accion (accion),
                INDEX idx_bitacora_usuario (usuario_nombre)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
        self::$tablaLista = true;
    }

    /** USU-06 esc. 3: registro automático. Nunca rompe la operación principal. */
    public static function registrar(string $accion, string $descripcion, ?int $idUsuario = null, ?string $usuarioNombre = null): void
    {
        try {
            if ($idUsuario === null || $usuarioNombre === null) {
                if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['usuario'])) {
                    $idUsuario     ??= (int) $_SESSION['usuario']['id'];
                    $usuarioNombre ??= $_SESSION['usuario']['usuario'];
                }
            }
            $bit = new self();
            $stmt = $bit->db->prepare(
                "INSERT INTO bitacora (id_usuario, usuario_nombre, accion, descripcion, ip)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $idUsuario,
                mb_substr($usuarioNombre ?? 'Sistema', 0, 100),
                mb_substr($accion, 0, 40),
                mb_substr($descripcion, 0, 255),
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (Throwable $e) {
            error_log('Bitácora: ' . $e->getMessage());
        }
    }

    /** USU-06 esc. 1 y 2: consulta con filtros */
    public function consultar(array $f = []): array
    {
        $sql    = "SELECT id_bitacora, usuario_nombre, accion, descripcion, ip, created_at
                   FROM bitacora WHERE 1=1";
        $params = [];

        if (($f['usuario'] ?? '') !== '') {
            $sql .= " AND usuario_nombre LIKE ?";
            $params[] = '%' . $f['usuario'] . '%';
        }
        if (($f['accion'] ?? '') !== '') {
            $sql .= " AND accion = ?";
            $params[] = $f['accion'];
        }
        if (($f['desde'] ?? '') !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['desde'])) {
            $sql .= " AND created_at >= ?";
            $params[] = $f['desde'] . ' 00:00:00';
        }
        if (($f['hasta'] ?? '') !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['hasta'])) {
            $sql .= " AND created_at <= ?";
            $params[] = $f['hasta'] . ' 23:59:59';
        }

        $sql .= " ORDER BY created_at DESC, id_bitacora DESC LIMIT 500";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function acciones(): array
    {
        return $this->db->query("SELECT DISTINCT accion FROM bitacora ORDER BY accion")
                        ->fetchAll(PDO::FETCH_COLUMN);
    }
}