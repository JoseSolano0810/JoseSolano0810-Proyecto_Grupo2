<?php
// Silenciar errores de display ANTES de cualquier require, para que los
// warnings de XAMPP no contaminen respuestas JSON. El log de PHP sigue activo.
$_accionTemp = $_GET['accion'] ?? '';
if (str_contains($_accionTemp, '.')) {
    ini_set('display_errors', '0');
}
unset($_accionTemp);

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
require_once ROOT_PATH . '/controllers/BitacoraController.php';
require_once ROOT_PATH . '/controllers/ExpedienteController.php';
require_once ROOT_PATH . '/controllers/OdontogramaController.php';
require_once ROOT_PATH . '/controllers/DocumentoController.php';
require_once ROOT_PATH . '/models/Bitacora.php';
require_once ROOT_PATH . '/services/RecordatorioService.php';

$accion = $_GET['accion'] ?? 'inicio';
$metodo = $_SERVER['REQUEST_METHOD'];

if (str_contains($accion, '.')) {
    header('Content-Type: application/json; charset=utf-8');
    // Evitar que warnings de PHP contaminen el JSON (especialmente en XAMPP con display_errors=On)
    ini_set('display_errors', '0');
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
        AuthService::logout($_GET['motivo'] ?? '');
        break;

    // mantiene viva la sesión mientras el usuario interactúa (USU-04)
    case 'ping':
        AuthService::requerir(AuthService::ROLES);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
        break;

    // el usuario cambia su propia contraseña
    case 'perfil.contrasena':
        header('Content-Type: application/json; charset=utf-8');
        (new LoginController())->cambiarContrasena();
        break;

    // bitácora
    case 'bitacora.listar':
        (new BitacoraController())->listar();
        break;

    // panel * rol
    case 'panel':
    case 'demo':
        AuthService::requerir(AuthService::ROLES);
        cargarPanel(AuthService::usuarioActual());
        break;

    // cambiar rol activo (usuarios con más de un rol)
    case 'cambiar_rol':
        AuthService::requerir(AuthService::ROLES);
        AuthService::cambiarRolActivo($_GET['rol'] ?? '');
        header('Location: ' . BASE_URL . '/index.php?accion=panel');
        exit;

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

    case 'usuarios.asignarRol':
        $ctrl = new UsuarioController();
        $ctrl->asignarRol();
        break;

    // ── Citas ─────────────────────────────────────────────────
    case 'citas.listar':
        (new CitaController())->consultar();
        break;

    case 'citas.catalogos':
        (new CitaController())->catalogos();
        break;

    case 'citas.historial':
        (new CitaController())->historial();
        break;

    case 'citas.crear':
        (new CitaController())->crear();
        break;

    case 'citas.editar':
        (new CitaController())->editar();
        break;

    case 'citas.reprogramar':
        (new CitaController())->reprogramar();
        break;

    case 'citas.cancelar':
        (new CitaController())->cancelar();
        break;

    case 'citas.estado':
        (new CitaController())->cambiarEstado();
        break;

    case 'citas.recordatorios':
        (new CitaController())->recordatorios();
        break;

    case 'citas.recordatorios.ejecutar':
        (new CitaController())->ejecutarRecordatorios();
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

    // ── Expedientes clínicos  ────────────────
    case 'expedientes.pacientes':
        (new ExpedienteController())->listarPacientes();
        break;

    case 'expedientes.obtener':
        (new ExpedienteController())->obtener();
        break;

    case 'expedientes.crear':
        (new ExpedienteController())->crear();
        break;

    case 'expedientes.actualizar':
        (new ExpedienteController())->actualizar();
        break;

    case 'expedientes.atenciones':
        (new ExpedienteController())->listarAtenciones();
        break;

    case 'expedientes.atencion.registrar':
        (new ExpedienteController())->registrarAtencion();
        break;

    case 'expedientes.paciente.actualizar':
        (new ExpedienteController())->actualizarPaciente();
        break;

    // ── Odontograma  ─────────────────────────
    case 'odontograma.obtener':
        (new OdontogramaController())->obtener();
        break;

    case 'odontograma.guardar':
        (new OdontogramaController())->guardar();
        break;

    // ── Documentos clínicos  ──────────────────────────
    case 'documentos.listar':
        (new DocumentoController())->listar();
        break;

    case 'documentos.subir':
        (new DocumentoController())->subir();
        break;

    case 'documentos.descargar':
        // Descarga directa — no JSON, sirve el archivo con headers
        (new DocumentoController())->descargar();
        break;

    case 'documentos.eliminar':
        (new DocumentoController())->eliminar();
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
 * Panel * rol
 */
function cargarPanel(array $sesion): void
{
    $usuario       = ['nombre' => $sesion['nombre'], 'iniciales' => $sesion['iniciales']];
    $rol           = $sesion['rol'];                 // rol activo
    $roles_sesion  = $sesion['roles'] ?? [$rol];     // todos sus roles
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
            $citaCtrl         = new CitaController();
            $mis_citas        = $citaCtrl->listarMisCitas();
            $proxima_cita     = $citaCtrl->proximaCita();
            $mis_tratamientos = (new TratamientoController())->listar();
            $mis_pagos        = (new PagoController())->listar();
            $saldo_pendiente  = 60000;
            require ROOT_PATH . '/views/paciente/index.php';
            break;

        default:
            AuthService::logout();
    }
}