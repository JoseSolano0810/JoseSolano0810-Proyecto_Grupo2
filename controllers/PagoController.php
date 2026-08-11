<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Pago.php';
require_once ROOT_PATH . '/services/AuthService.php';

class PagoController
{
    private Pago $model;

    public function __construct()
    {
        AuthService::requerir(['recepcionista', 'admin']);
        $this->model = new Pago();
    }

    public function listar(): array
    {

        return [
            ['id'=>'PAG-001','paciente'=>'Ana Rojas',  'fecha'=>'28/07/2026','monto'=>50000, 'metodo'=>'Tarjeta','cotizacion'=>'COT-001','estado'=>'completada'],
            ['id'=>'PAG-002','paciente'=>'María Solís', 'fecha'=>'01/08/2026','monto'=>100000,'metodo'=>'Efectivo','cotizacion'=>'COT-004','estado'=>'completada'],
            ['id'=>'PAG-003','paciente'=>'Luis Vargas', 'fecha'=>'03/08/2026','monto'=>60000, 'metodo'=>'Sinpe',  'cotizacion'=>'COT-002','estado'=>'pendiente'],
        ];
    }

    public function registrar(): void
    {

        echo json_encode(['ok' => true, 'demo' => true]);
    }
}
