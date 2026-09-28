<?php
define('ROOT_PATH', str_replace('\\', '/', __DIR__));

require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/models/Usuario.php';
require_once ROOT_PATH . '/services/AuthService.php';
require_once ROOT_PATH . '/controllers/LoginController.php';
require_once ROOT_PATH . '/controllers/UsuarioController.php';
require_once ROOT_PATH . '/controllers/CitaController.php';
require_once ROOT_PATH . '/controllers/PacienteController.php';
require_once ROOT_PATH . '/controllers/TratamientoController.php';
require_once ROOT_PATH . '/controllers/CotizacionController.php';
require_once ROOT_PATH . '/controllers/PagoController.php';

$accion = $_GET['accion'] ?? 'inicio';
$metodo = $_SERVER['REQUEST_METHOD'];

if (str_contains($accion, '.')) {
    header('Content-Type: application/json; charset=utf-8');
}

switch ($accion) {

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
        AuthService::logout();
        break;

    // panel * rol
    case 'panel':
    case 'demo':
        AuthService::requerir(AuthService::ROLES);
        cargarPanel(AuthService::usuarioActual());
        break;

    // ── Usuarios ──────────────────────────────────────────────
    case 'usuarios.listar':
        (new UsuarioController())->listar();
        break;

    case 'usuarios.roles':
        (new UsuarioController())->roles();
        break;

    case 'usuarios.crear':
        $ctrl = new UsuarioController();
        $ctrl->crear();
        break;

    case 'usuarios.restablecer':
        $ctrl = new UsuarioController();
        $ctrl->restablecerContrasena();
        break;

    case 'usuarios.estado':
        $ctrl = new UsuarioController();
        $ctrl->cambiarEstado();
        break;

    case 'usuarios.editar':
        $ctrl = new UsuarioController();
        $ctrl->editar();
        break;

    // ── Citas ─────────────────────────────────────────────────
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

    // ── Pacientes ─────────────────────────────────────────────
    case 'pacientes.listar':
        $ctrl = new PacienteController();
        echo json_encode($ctrl->listar());
        break;

    case 'pacientes.crear':
        $ctrl = new PacienteController();
        $ctrl->crear();
        break;

    // ── Cotizaciones ──────────────────────────────────────────
    case 'cotizaciones.listar':
        $ctrl = new CotizacionController();
        echo json_encode($ctrl->listar());
        break;

    case 'cotizaciones.crear':
        $ctrl = new CotizacionController();
        $ctrl->crear();
        break;

    // ── Pagos ─────────────────────────────────────────────────
    case 'pagos.listar':
        $ctrl = new PagoController();
        echo json_encode($ctrl->listar());
        break;

    case 'pagos.registrar':
        $ctrl = new PagoController();
        $ctrl->registrar();
        break;

    // ── Tratamientos ──────────────────────────────────────────
    case 'tratamientos.listar':
        $ctrl = new TratamientoController();
        echo json_encode($ctrl->listar());
        break;

    case 'tratamientos.crear':
        $ctrl = new TratamientoController();
        $ctrl->crear();
        break;

    default:
        http_response_code(404);
        $ctrl = new LoginController();
        $ctrl->mostrar();
        break;
}

/**
 * 
 * Panel * rol
 */
function cargarPanel(array $sesion): void
{
    $usuario       = ['nombre' => $sesion['nombre'], 'iniciales' => $sesion['iniciales']];
    $rol           = $sesion['rol'];
    $pagina_activa = 'inicio';

    switch ($rol) {

        case 'administrador':
        case 'odontologo':
            $citas        = (new CitaController())->listar();
            $pacientes    = (new PacienteController())->listar();
            $tratamientos = (new TratamientoController())->listar();
            $usuarios_sistema = [];
            $roles            = [];
            if ($rol === 'administrador') {
                $datos            = (new UsuarioController())->datosVista();
                $usuarios_sistema = $datos['usuarios'];
                $roles            = $datos['roles'];
            }

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
            require ROOT_PATH . '/views/odontologo/index.php';
            break;

        case 'recepcionista':
        case 'asistente_dental':
            $citas        = (new CitaController())->listar();
            $pacientes    = (new PacienteController())->listar();
            $cotizaciones = (new CotizacionController())->listar();
            $pagos        = (new PagoController())->listar();
            require ROOT_PATH . '/views/recepcionista/index.php';
            break;

        case 'paciente':
            $mis_citas        = (new CitaController())->listar();
            $mis_tratamientos = (new TratamientoController())->listar();
            $mis_pagos        = (new PagoController())->listar();
            $saldo_pendiente  = 60000;
            $proxima_cita     = [
                'mes'         => 'AGO',
                'dia'         => '14',
                'hora'        => '09:00 a.m.',
                'odontologo'  => 'Dra. Melissa Salguero',
                'tratamiento' => 'Control de ortodoncia',
            ];
            require ROOT_PATH . '/views/paciente/index.php';
            break;

        default:
            AuthService::logout();
    }
}
