<?php

require_once ROOT_PATH . '/database/Database.php';

class Usuario
{
    private PDO $db;

    public function __construct()
    {

    }

    public function buscarPorCredenciales(string $nombre, string $contrasena): ?array
    {

        return null;
    }

    public function obtenerTodos(): array
    {

        return [];
    }

    public function crear(array $datos): void
    {

    }

    public function cambiarEstado(int $id, string $estado): void
    {

    }
}
