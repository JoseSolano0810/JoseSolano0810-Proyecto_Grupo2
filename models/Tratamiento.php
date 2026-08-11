<?php

require_once ROOT_PATH . '/database/Database.php';

class Tratamiento
{
    private PDO $db;

    public function __construct()
    {

    }

    public function obtenerTodos(): array
    {

        return [];
    }

    public function obtenerPorPaciente(int $pacienteId): array
    {

        return [];
    }

    public function crear(array $datos): void
    {

    }

    public function actualizarProgreso(int $id, int $progreso, int $sesionesRealizadas): void
    {

    }
}
