<?php

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Busca usuario * nombre 
     */
    public function buscarPorNombreUsuario(string $nombreUsuario): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.nombre AS rol
             FROM usuario u
             JOIN rol r ON u.id_rol = r.id_rol
             WHERE u.nombre_usuario = ?
             AND u.estado = 'activo'
             LIMIT 1"
        );
        $stmt->execute([$nombreUsuario]);
        return $stmt->fetch() ?: null;
    }

    /**
     * usuarios * rol
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT u.id_usuario, u.nombre_completo, u.nombre_usuario,
                    u.identificacion, u.telefono, u.correo,
                    u.estado, u.created_at,
                    r.nombre AS rol, r.id_rol
             FROM usuario u
             JOIN rol r ON u.id_rol = r.id_rol
             ORDER BY u.nombre_completo ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * usuario * ID
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.nombre AS rol
             FROM usuario u
             JOIN rol r ON u.id_rol = r.id_rol
             WHERE u.id_usuario = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Crea usuario
     */
    public function crear(array $datos): int
    {
        $hash = password_hash($datos['contrasena'], PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            "INSERT INTO usuario
             (id_rol, identificacion, nombre_completo, telefono,
              correo, nombre_usuario, contrasena_hash, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')"
        );
        $stmt->execute([
            $datos['rol_id'],
            $datos['cedula'],
            $datos['nombre'],
            $datos['telefono'] ?? null,
            $datos['correo'],
            $datos['usuario'],
            $hash,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Restablece contraseña
     */
    public function restablecerContrasena(int $id, string $nuevaContrasena): void
    {
        $hash = password_hash($nuevaContrasena, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            "UPDATE usuario SET contrasena_hash = ? WHERE id_usuario = ?"
        );
        $stmt->execute([$hash, $id]);
    }

    /**
     * activo/inactivo
     */
    public function cambiarEstado(int $id, string $estado): void
    {
        $stmt = $this->db->prepare(
            "UPDATE usuario SET estado = ? WHERE id_usuario = ?"
        );
        $stmt->execute([$estado, $id]);
    }

    /**
     * Lista roles
     */
    public function obtenerRoles(): array
    {
        $stmt = $this->db->query("SELECT * FROM rol ORDER BY id_rol ASC");
        return $stmt->fetchAll();
    }

    /**
     * nombre de usuario ya existe
     */
    public function existeNombreUsuario(string $nombreUsuario, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM usuario WHERE nombre_usuario = ?";
        $params = [$nombreUsuario];
        if ($excludeId) {
            $sql     .= " AND id_usuario != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * correo ya existe
     */
    public function existeCorreo(string $correo, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM usuario WHERE correo = ?";
        $params = [$correo];
        if ($excludeId) {
            $sql     .= " AND id_usuario != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }
}