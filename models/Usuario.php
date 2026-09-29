<?php

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Busca usuario por nombre
     */
    public function buscarPorNombreUsuario(string $nombreUsuario): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.nombre AS rol
             FROM usuario u
             JOIN rol r ON u.id_rol = r.id_rol
             WHERE u.nombre_usuario = ?
             LIMIT 1"
        );
        $stmt->execute([$nombreUsuario]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Todos los usuarios con su rol
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
     * Usuario por ID
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

        $this->db->beginTransaction();
        try {
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
                ($datos['telefono'] ?? '') !== '' ? $datos['telefono'] : null,
                $datos['correo'],
                $datos['usuario'],
                $hash,
            ]);
            $id = (int) $this->db->lastInsertId();

            $this->db->prepare(
                "INSERT INTO usuario_rol (id_usuario, id_rol, es_principal) VALUES (?, ?, 1)"
            )->execute([$id, $datos['rol_id']]);

            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza los datos de un usuario.
     * rol_id = rol inicial.
     */
    public function actualizar(int $id, array $datos): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE usuario
                 SET identificacion = ?, nombre_completo = ?,
                     telefono = ?, correo = ?, nombre_usuario = ?
                 WHERE id_usuario = ?"
            );
            $stmt->execute([
                $datos['cedula'],
                $datos['nombre'],
                ($datos['telefono'] ?? '') !== '' ? $datos['telefono'] : null,
                $datos['correo'],
                $datos['usuario'],
                $id,
            ]);

            $this->establecerRolPrincipal($id, (int) $datos['rol_id']);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Define el rol inicial (principal). Si no lo tenía, se agrega a sus roles.
     * Los demás roles del usuario se conservan.
     */
    public function establecerRolPrincipal(int $id, int $rolId): void
    {
        $this->db->prepare(
            "UPDATE usuario_rol SET es_principal = 0 WHERE id_usuario = ?"
        )->execute([$id]);

        $this->db->prepare(
            "INSERT INTO usuario_rol (id_usuario, id_rol, es_principal)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE es_principal = 1"
        )->execute([$id, $rolId]);

        $this->db->prepare(
            "UPDATE usuario SET id_rol = ? WHERE id_usuario = ?"
        )->execute([$rolId, $id]);
    }

    /**
     * Reemplaza TODOS los roles del usuario y define cuál es el inicial.
     * $rolIds debe incluir a $rolPrincipal.
     */
    public function asignarRoles(int $id, array $rolIds, int $rolPrincipal): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM usuario_rol WHERE id_usuario = ?")
                     ->execute([$id]);

            $ins = $this->db->prepare(
                "INSERT INTO usuario_rol (id_usuario, id_rol, es_principal) VALUES (?, ?, ?)"
            );
            foreach ($rolIds as $rolId) {
                $ins->execute([$id, $rolId, $rolId === $rolPrincipal ? 1 : 0]);
            }

            $this->db->prepare("UPDATE usuario SET id_rol = ? WHERE id_usuario = ?")
                     ->execute([$rolPrincipal, $id]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Roles de todos los usuarios: [id_usuario => [ ['id','nombre','principal'], ... ]]
     * (el principal siempre primero)
     */
    public function obtenerRolesPorUsuario(): array
    {
        $stmt = $this->db->query(
            "SELECT ur.id_usuario, r.id_rol, r.nombre, ur.es_principal
             FROM usuario_rol ur
             JOIN rol r ON r.id_rol = ur.id_rol
             ORDER BY ur.es_principal DESC, r.id_rol ASC"
        );

        $mapa = [];
        foreach ($stmt->fetchAll() as $f) {
            $mapa[(int) $f['id_usuario']][] = [
                'id'        => (int) $f['id_rol'],
                'nombre'    => $f['nombre'],
                'principal' => (bool) $f['es_principal'],
            ];
        }
        return $mapa;
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
     * Activa / inactiva usuario
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
     * Verifica rol
     */
    public function existeRol(int $idRol): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM rol WHERE id_rol = ?");
        $stmt->execute([$idRol]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Nombre de usuario ya existe
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
     * Correo ya existe
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


    public function existeIdentificacion(string $identificacion, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM usuario WHERE identificacion = ?";
        $params = [$identificacion];
        if ($excludeId) {
            $sql     .= " AND id_usuario != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }
}