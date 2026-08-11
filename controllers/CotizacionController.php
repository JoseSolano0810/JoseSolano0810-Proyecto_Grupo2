<?php
require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Cotizacion.php';
require_once ROOT_PATH . '/services/AuthService.php';

class CotizacionController
{
    private Cotizacion $model;
    private const IVA = 0.13;

    public function __construct()
    {
        AuthService::requerir(['recepcionista', 'admin']);
        $this->model = new Cotizacion();
    }

    public function listar(): array
    {
        return [
            ['id'=>'COT-001','paciente'=>'Ana Rojas',    'fecha'=>'28/07/2026','monto'=>84071,'iva'=>10929,'total'=>95000, 'estado'=>'aprobada'],
            ['id'=>'COT-002','paciente'=>'Luis Vargas',  'fecha'=>'29/07/2026','monto'=>106195,'iva'=>13805,'total'=>120000,'estado'=>'pendiente'],
            ['id'=>'COT-003','paciente'=>'Pedro Gómez',  'fecha'=>'30/07/2026','monto'=>39823,'iva'=>5177, 'total'=>45000, 'estado'=>'pendiente'],
            ['id'=>'COT-004','paciente'=>'María Solís',  'fecha'=>'01/08/2026','monto'=>177876,'iva'=>23124,'total'=>201000,'estado'=>'aprobada'],
            ['id'=>'COT-005','paciente'=>'Carlos Castro','fecha'=>'02/08/2026','monto'=>66372,'iva'=>8628, 'total'=>75000, 'estado'=>'rechazada'],
        ];
    }

    public function crear(): void
    {
        echo json_encode(['ok' => true, 'demo' => true]);
    }

    public function aprobar(): void
    {
        echo json_encode(['ok' => true, 'demo' => true]);
    }
}