```php
<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Cita.php';
require_once ROOT_PATH . '/services/AuthService.php';

class CitaController
{
    private Cita $model;

    public function __construct()
    {
        AuthService::requerir(['odontologo', 'recepcionista', 'admin']);
        $this->model = new Cita();
    }

    public function listar(): array
    {
        return [
            ['id'=>1,'hora'=>'08:00','paciente'=>'Ana Rojas','odontologo'=>'Dr. Méndez','tratamiento'=>'Limpieza dental','estado'=>'confirmada'],
            ['id'=>2,'hora'=>'09:30','paciente'=>'Luis Vargas','odontologo'=>'Dr. Méndez','tratamiento'=>'Extracción molar','estado'=>'confirmada'],
            ['id'=>3,'hora'=>'11:00','paciente'=>'María Solís','odontologo'=>'Dr. Méndez','tratamiento'=>'Ortodoncia','estado'=>'pendiente'],
            ['id'=>4,'hora'=>'14:00','paciente'=>'Jorge Pérez','odontologo'=>'Dr. Méndez','tratamiento'=>'Blanqueamiento','estado'=>'confirmada'],
            ['id'=>5,'hora'=>'15:30','paciente'=>'Sofía Torres','odontologo'=>'Dr. Méndez','tratamiento'=>'Revisión general','estado'=>'pendiente'],
        ];
    }

    public function crear(): void
    {
        echo json_encode(['ok' => true, 'demo' => true]);
    }

    public function editar(): void
    {
        echo json_encode(['ok' => true, 'demo' => true]);
    }

    public function cancelar(): void
    {
        echo json_encode(['ok' => true, 'demo' => true]);
    }
}
```
