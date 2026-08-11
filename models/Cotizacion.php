<?php

class Cotizacion
{
    public function __construct() {}

    public function obtenerTodas(): array { return []; }
    public function obtenerPorId(int $id): ?array { return null; }
    public function crear(array $datos): void {}
    public function cambiarEstado(int $id, string $estado): void {}
}