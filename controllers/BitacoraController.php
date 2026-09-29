<?php

require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/AuthService.php';

class BitacoraController
{
    private Bitacora $model;

    public function __construct()
    {
        AuthService::requerir(['administrador']);
        $this->model = new Bitacora();
    }

    public function listar(): void
    {
        $filtros = [
            'usuario' => trim($_GET['usuario']  ?? ''),
            'accion'  => trim($_GET['accion_f'] ?? ''),
            'desde'   => trim($_GET['desde']    ?? ''),
            'hasta'   => trim($_GET['hasta']    ?? ''),
        ];

        echo json_encode([
            'ok'       => true,
            'data'     => $this->model->consultar($filtros),
            'acciones' => $this->model->acciones(),
        ], JSON_UNESCAPED_UNICODE);
    }
}