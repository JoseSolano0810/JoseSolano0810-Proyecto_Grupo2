```php
<?php

require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Paciente.php';
require_once ROOT_PATH . '/services/AuthService.php';

class PacienteController
{
    private Paciente $model;

    public function __construct()
    {
        AuthService::requerir(['recepcionista', 'odontologo', 'admin']);
        $this->model = new Paciente();
    }

    public function listar(): array
    {
        return [
            ['id'=>1,'nombre'=>'Ana Rojas','cedula'=>'1-0234-5678','telefono'=>'8888-1111','correo'=>'ana.rojas@mail.com','edad'=>34,'ultima_visita'=>'15/07/2026','estado'=>'activo'],
            ['id'=>2,'nombre'=>'Luis Vargas','cedula'=>'2-0345-6789','telefono'=>'8888-2222','correo'=>'luis.vargas@mail.com','edad'=>45,'ultima_visita'=>'20/07/2026','estado'=>'activo'],
            ['id'=>3,'nombre'=>'Pedro Gómez','cedula'=>'1-0901-2345','telefono'=>'8888-3333','correo'=>'pedro.gomez@mail.com','edad'=>31,'ultima_visita'=>'10/07/2026','estado'=>'activo'],
            ['id'=>4,'nombre'=>'María Solís','cedula'=>'1-0456-7890','telefono'=>'8888-4444','correo'=>'maria.solis@mail.com','edad'=>28,'ultima_visita'=>'05/07/2026','estado'=>'activo'],
            ['id'=>5,'nombre'=>'Carlos Castro','cedula'=>'3-0123-4567','telefono'=>'8888-5555','correo'=>'carlos.castro@mail.com','edad'=>40,'ultima_visita'=>'01/06/2026','estado'=>'inactivo'],
            ['id'=>6,'nombre'=>'Jorge Pérez','cedula'=>'3-0567-8901','telefono'=>'8888-6666','correo'=>'jorge.perez@mail.com','edad'=>52,'ultima_visita'=>'01/06/2026','estado'=>'inactivo'],
            ['id'=>7,'nombre'=>'Sofía Torres','cedula'=>'1-0678-9012','telefono'=>'8888-7777','correo'=>'sofia.torres@mail.com','edad'=>19,'ultima_visita'=>'22/07/2026','estado'=>'activo'],
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

    public function inactivar(): void
    {
        echo json_encode(['ok' => true, 'demo' => true]);
    }
}
```
