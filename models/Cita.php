<?php

class Cita
{
    public function __construct() {}

    public function obtenerTodas(): array { return []; }
    public function obtenerPorOdontologo(int $odontologoId): array { return []; }
    public function existeTraslape(int $odontologoId, string $fecha, string $hora): bool { return false; }
    public function crear(array $datos): void {}
    public function cambiarEstado(int $id, string $estado): void {}
}