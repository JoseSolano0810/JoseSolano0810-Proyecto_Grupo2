<?php

require_once ROOT_PATH . '/database/Database.php';

class Insumo
{
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
            "CREATE TABLE IF NOT EXISTS insumo (
                id_insumo INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(120) NOT NULL,
                descripcion VARCHAR(500) NOT NULL,
                categoria VARCHAR(80) NOT NULL,
                cantidad_disponible INT UNSIGNED NOT NULL DEFAULT 0,
                nivel_minimo INT UNSIGNED NOT NULL DEFAULT 0,
                estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_insumo_nombre (nombre),
                INDEX idx_insumo_categoria (categoria),
                INDEX idx_insumo_estado (estado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS movimiento_inventario (
                id_movimiento INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                id_insumo INT UNSIGNED NOT NULL,
                tipo ENUM('entrada','salida') NOT NULL,
                cantidad INT UNSIGNED NOT NULL,
                cantidad_anterior INT UNSIGNED NOT NULL,
                cantidad_resultante INT UNSIGNED NOT NULL,
                id_usuario INT NULL,
                observacion VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_mov_insumo (id_insumo),
                INDEX idx_mov_tipo (tipo),
                INDEX idx_mov_fecha (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        self::$tablasListas = true;
    }

    public function listar(): array
    {
        $sql = "SELECT *,
                CASE
                    WHEN estado = 'inactivo' THEN 'inactivo'
                    WHEN cantidad_disponible < nivel_minimo THEN 'bajo'
                    WHEN cantidad_disponible = nivel_minimo THEN 'minimo'
                    ELSE 'normal'
                END AS estado_stock
            FROM insumo
            ORDER BY estado DESC, nombre ASC";

        return $this->db->query($sql)->fetchAll();
    }

    public function obtener(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT *,
                CASE
                    WHEN cantidad_disponible < nivel_minimo THEN 'bajo'
                    WHEN cantidad_disponible = nivel_minimo THEN 'minimo'
                    ELSE 'normal'
                END AS estado_stock
             FROM insumo
             WHERE id_insumo = ?
             LIMIT 1"
        );

        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function crear(array $datos, int $idUsuario): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO insumo
                    (nombre, descripcion, categoria, cantidad_disponible, nivel_minimo)
                 VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $datos['nombre'],
                $datos['descripcion'],
                $datos['categoria'],
                $datos['cantidad_disponible'],
                $datos['nivel_minimo']
            ]);

            $id = (int) $this->db->lastInsertId();

            if ((int) $datos['cantidad_disponible'] > 0) {
                $mov = $this->db->prepare(
                    "INSERT INTO movimiento_inventario
                        (id_insumo, tipo, cantidad, cantidad_anterior,
                         cantidad_resultante, id_usuario, observacion)
                     VALUES (?, 'entrada', ?, 0, ?, ?, ?)"
                );

                $mov->execute([
                    $id,
                    $datos['cantidad_disponible'],
                    $datos['cantidad_disponible'],
                    $idUsuario,
                    'Stock inicial'
                ]);
            }

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function registrarMovimiento(
        int $idInsumo,
        string $tipo,
        int $cantidad,
        int $idUsuario,
        string $observacion = ''
    ): array {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "SELECT *
                 FROM insumo
                 WHERE id_insumo = ?
                   AND estado = 'activo'
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmt->execute([$idInsumo]);
            $insumo = $stmt->fetch();

            if (!$insumo) {
                throw new RuntimeException('El insumo no existe o se encuentra inactivo.');
            }

            if ($cantidad <= 0) {
                throw new RuntimeException('La cantidad debe ser mayor a cero.');
            }

            $anterior = (int) $insumo['cantidad_disponible'];

            if ($tipo === 'entrada') {
                $resultante = $anterior + $cantidad;
            } elseif ($tipo === 'salida') {
                if ($cantidad > $anterior) {
                    throw new RuntimeException('No existe suficiente inventario disponible.');
                }

                $resultante = $anterior - $cantidad;
            } else {
                throw new RuntimeException('Tipo de movimiento no válido.');
            }

            $update = $this->db->prepare(
                "UPDATE insumo
                 SET cantidad_disponible = ?
                 WHERE id_insumo = ?"
            );

            $update->execute([$resultante, $idInsumo]);

            $mov = $this->db->prepare(
                "INSERT INTO movimiento_inventario
                    (id_insumo, tipo, cantidad, cantidad_anterior,
                     cantidad_resultante, id_usuario, observacion)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $mov->execute([
                $idInsumo,
                $tipo,
                $cantidad,
                $anterior,
                $resultante,
                $idUsuario,
                $observacion !== '' ? $observacion : null
            ]);

            $this->db->commit();

            return $this->obtener($idInsumo);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function movimientos(int $idInsumo): array
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM movimiento_inventario
             WHERE id_insumo = ?
             ORDER BY created_at DESC, id_movimiento DESC"
        );

        $stmt->execute([$idInsumo]);
        return $stmt->fetchAll();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE insumo
         SET nombre = ?,
             descripcion = ?,
             categoria = ?,
             nivel_minimo = ?
         WHERE id_insumo = ?
           AND estado = 'activo'"
        );

        return $stmt->execute([
            $datos['nombre'],
            $datos['descripcion'],
            $datos['categoria'],
            $datos['nivel_minimo'],
            $id
        ]);
    }

    public function desactivar(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE insumo
         SET estado = 'inactivo'
         WHERE id_insumo = ?
           AND estado = 'activo'"
        );

        return $stmt->execute([$id]);
    }

    public function activar(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE insumo
         SET estado = 'activo'
         WHERE id_insumo = ?
           AND estado = 'inactivo'"
        );

        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}
