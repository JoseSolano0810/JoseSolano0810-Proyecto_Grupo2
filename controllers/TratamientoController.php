<?php
require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Tratamiento.php';
require_once ROOT_PATH . '/services/AuthService.php';

class TratamientoController
{
    private Tratamiento $model;

    public function __construct()
    {
        AuthService::requerir(['odontologo', 'admin', 'paciente']);
        $this->model = new Tratamiento();
    }

    public function listar(): array
    {
        return [
            ['paciente'=>'Ana Rojas',    'tratamiento'=>'Ortodoncia fase 2',     'progreso'=>72, 'sesiones_total'=>12,'sesiones_realizadas'=>8, 'inicio'=>'10/03/2026','estado'=>'proceso'],
            ['paciente'=>'Luis Vargas',  'tratamiento'=>'Implante dental',        'progreso'=>45, 'sesiones_total'=>6, 'sesiones_realizadas'=>3, 'inicio'=>'15/04/2026','estado'=>'proceso'],
            ['paciente'=>'María Solís',  'tratamiento'=>'Blanqueamiento 3 ses.',  'progreso'=>33, 'sesiones_total'=>3, 'sesiones_realizadas'=>1, 'inicio'=>'01/07/2026','estado'=>'proceso'],
            ['paciente'=>'Sofía Torres', 'tratamiento'=>'Revisión de conducto',   'progreso'=>90, 'sesiones_total'=>4, 'sesiones_realizadas'=>4, 'inicio'=>'20/06/2026','estado'=>'proceso'],
            ['paciente'=>'Ana Rojas',    'tratamiento'=>'Limpieza semestral',     'progreso'=>100,'sesiones_total'=>1, 'sesiones_realizadas'=>1, 'inicio'=>'28/07/2026','estado'=>'completada'],
        ];
    }

    public function crear(): void
    {
        echo json_encode(['ok' => true, 'demo' => true]);
    }

    public function actualizarProgreso(): void
    {
        echo json_encode(['ok' => true, 'demo' => true]);
    }
}