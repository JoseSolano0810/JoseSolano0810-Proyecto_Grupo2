<?php

require_once ROOT_PATH . '/database/Database.php';

class Pago
{
    private PDO $db;

    public function __construct()
    {

    }

    public function obtenerTodos(): array
    {

        return [];
    }

    public function totalPagado(int $cotizacionId): float
    {

        return 0.0;
    }

    public function crear(array $datos): void
    {

    }
}
