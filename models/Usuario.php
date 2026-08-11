<?php
require_once ROOT_PATH . '/database/Database.php';

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        // $this->db = Database::getInstance()->getConnection();
    }

    /**
     * @param string $nombre
     * @param string $contrasena 
     * @return array|null        
     */
    public function buscarPorCredenciales(string $nombre, string $contrasena): ?array
    {
        // $stmt = $this->db->prepare(
        //     "SELECT id, nombre, rol FROM usuarios
        //      WHERE nombre = ? AND estado = 'activo'
        //      LIMIT 1"
        // );
        // $stmt->execute([$nombre]);
        // $usuario = $stmt->fetch();
        //
        // if (!$usuario || !password_verify($contrasena, $usuario['contrasena'])) {
        //     return null;
        // }
        // return $usuario;

        return null; 
    }

    /**
     * @return array
     */
    public function obtenerTodos(): array
    {
        // $stmt = $this->db->query(
        //     "SELECT id, nombre, correo, rol, estado, created_at
        //      FROM usuarios ORDER BY nombre ASC"
        // );
        // return $stmt->fetchAll();
        return [];
    }

    /**
     * @param array $datos
     */
    public function crear(array $datos): void
    {
        // TODO:
        // $hash = password_hash($datos['contrasena'], PASSWORD_BCRYPT);
        // $stmt = $this->db->prepare(
        //     "INSERT INTO usuarios (nombre, correo, contrasena, rol, estado)
        //      VALUES (?, ?, ?, ?, 'activo')"
        // );
        // $stmt->execute([
        //     $datos['nombre'],
        //     $datos['correo'],
        //     $hash,
        //     $datos['rol'],
        // ]);
    }

    /** Cambia el estado de un usuario (activo / inactivo) */
    public function cambiarEstado(int $id, string $estado): void
    {
        // $stmt = $this->db->prepare(
        //     "UPDATE usuarios SET estado = ? WHERE id = ?"
        // );
        // $stmt->execute([$estado, $id]);
    }
}
