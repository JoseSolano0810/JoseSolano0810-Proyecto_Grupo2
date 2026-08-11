<?php

require_once ROOT_PATH . '/database/Database.php';

class Cotizacion
{
    private PDO $db;

    public function __construct()
    {

    }

    public function obtenerTodas(): array
    {

        return [];
    }

    public function obtenerPorId(int $id): ?array
    {

        return null;
    }

    public function crear(array $datos): void
    {

    }

    public function cambiarEstado(int $id, string $estado): void
    {

    }
}
