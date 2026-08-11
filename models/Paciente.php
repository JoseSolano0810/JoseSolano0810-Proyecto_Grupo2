<?php

require_once ROOT_PATH . '/database/Database.php';

class Paciente
{
    private PDO $db;

    public function __construct()
    {

    }

    public function obtenerTodos(): array
    {

        return [];
    }

    public function buscar(string $termino): array
    {

        return [];
    }

    public function crear(array $datos): void
    {

    }

    public function actualizar(int $id, array $datos): void
    {

    }

    public function cambiarEstado(int $id, string $estado): void
    {

    }
}
