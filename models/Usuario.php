<?php

class Usuario
{
    public function __construct() {}

    public function buscarPorCredenciales(string $nombre, string $contrasena): ?array { return null; }
    public function obtenerTodos(): array { return []; }
    public function crear(array $datos): void {}
    public function cambiarEstado(int $id, string $estado): void {}
}