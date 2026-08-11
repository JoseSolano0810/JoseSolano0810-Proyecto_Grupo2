<?php
require_once __DIR__ . '/config/config.php';

// Cargar controllers
require_once ROOT_PATH . '/controllers/LoginController.php';
require_once ROOT_PATH . '/controllers/CitaController.php';
require_once ROOT_PATH . '/controllers/PacienteController.php';
require_once ROOT_PATH . '/controllers/TratamientoController.php';
require_once ROOT_PATH . '/controllers/CotizacionController.php';
require_once ROOT_PATH . '/controllers/PagoController.php';
require_once ROOT_PATH . '/services/AuthService.php';


$accion = $_GET['accion'] ?? 'inicio';
$metodo = $_SERVER['REQUEST_METHOD'];

//  Enrutador 
switch ($accion) {

    /*  Login  */
    case 'inicio':
    case 'login':
        $ctrl = new LoginController();
        if ($metodo === 'POST') {
            $ctrl->autenticar();
        } else {
            $ctrl->mostrar();
        }
        break;

    case 'logout':
        AuthService::cerrarSesion();
        break;

    /*  Demo sin BD  */
    case 'demo':
        $rol = $_GET['rol'] ?? 'odontologo';
        cargarDashboardDemo($rol);
        break;

    /*  Citas   */
    case 'citas.listar':
        $ctrl = new CitaController();
        echo json_encode($ctrl->listar());
        break;

    case 'citas.crear':
        $ctrl = new CitaController();
        $ctrl->crear();
        break;

    case 'citas.cancelar':
        $ctrl = new CitaController();
        $ctrl->cancelar();
        break;

    /*  Pacientes  */
    case 'pacientes.listar':
        $ctrl = new PacienteController();
        echo json_encode($ctrl->listar());
        break;

    case 'pacientes.crear':
        $ctrl = new PacienteController();
        $ctrl->crear();
        break;

    /*  Cotizaciones  */
    case 'cotizaciones.listar':
        $ctrl = new CotizacionController();
        echo json_encode($ctrl->listar());
        break;

    case 'cotizaciones.crear':
        $ctrl = new CotizacionController();
        $ctrl->crear();
        break;

    /*  Pagos  */
    case 'pagos.listar':
        $ctrl = new PagoController();
        echo json_encode($ctrl->listar());
        break;

    case 'pagos.registrar':
        $ctrl = new PagoController();
        $ctrl->registrar();
        break;

    /*  Tratamientos  */
    case 'tratamientos.listar':
        $ctrl = new TratamientoController();
        echo json_encode($ctrl->listar());
        break;

    case 'tratamientos.crear':
        $ctrl = new TratamientoController();
        $ctrl->crear();
        break;

    /*  Error  */
    default:
        http_response_code(404);
        $ctrl = new LoginController();
        $ctrl->mostrar();
        break;
}

/*  Funcion auxiliar modo demo */
function cargarDashboardDemo(string $rol): void
{
    $citaCtrl        = new CitaController();
    $pacienteCtrl    = new PacienteController();
    $tratamientoCtrl = new TratamientoController();
    $cotizacionCtrl  = new CotizacionController();
    $pagoCtrl        = new PagoController();

    switch ($rol) {

        case 'odontologo':
            $usuario       = ['nombre' => 'Dra. Melissa Salguero', 'iniciales' => 'MS'];
            $pagina_activa = 'inicio';
            $citas         = $citaCtrl->listar();
            $pacientes     = $pacienteCtrl->listar();
            $tratamientos  = $tratamientoCtrl->listar();
            $estado_dientes = [
                11=>'sano',  12=>'sano',  13=>'sano',  14=>'corona', 15=>'sano',
                16=>'sano',  17=>'caries',18=>'sano',
                21=>'sano',  22=>'sano',  23=>'sano',  24=>'sano',   25=>'sano',
                26=>'ausente',27=>'sano', 28=>'sano',
                31=>'sano',  32=>'sano',  33=>'sano',  34=>'sano',   35=>'caries',
                36=>'sano',  37=>'sano',  38=>'ausente',
                41=>'sano',  42=>'sano',  43=>'sano',  44=>'sano',   45=>'sano',
                46=>'corona',47=>'sano',  48=>'sano',
            ];
            require_once ROOT_PATH . '/views/odontologo/index.php';
            break;

        case 'recepcionista':
            $usuario       = ['nombre' => 'Sofia Salguero', 'iniciales' => 'SS'];
            $pagina_activa = 'inicio';
            $citas         = $citaCtrl->listar();
            $pacientes     = $pacienteCtrl->listar();
            $cotizaciones  = $cotizacionCtrl->listar();
            $pagos         = $pagoCtrl->listar();
            require_once ROOT_PATH . '/views/recepcionista/index.php';
            break;

        case 'paciente':
            $usuario         = ['nombre' => 'Jose Solano', 'iniciales' => 'JS'];
            $pagina_activa   = 'inicio';
            $mis_citas       = $citaCtrl->listar();
            $mis_tratamientos = $tratamientoCtrl->listar();
            $mis_pagos       = $pagoCtrl->listar();
            $saldo_pendiente = 60000;
            $proxima_cita    = [
                'mes'=>'AGO','dia'=>'14','hora'=>'09:00 a.m.',
                'odontologo'=>'Dra. Melissa Salguero','tratamiento'=>'Control de ortodoncia',
            ];
            require_once ROOT_PATH . '/views/paciente/index.php';
            break;

        default:
            header('Location: ' . BASE_URL . '/index.php');
            exit;
    }
}
