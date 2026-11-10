<?php
/**
 * diagnostico.php — BORRAR DESPUÉS DE USAR.
 * Súbalo a la MISMA carpeta donde está index.php y ábralo en el navegador:
 *   https://SU-SITIO/diagnostico.php
 * No muestra contraseñas. Solo dice qué archivo falla y en qué línea.
 */
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

echo "PHP " . PHP_VERSION . "\n";
echo "Carpeta: " . __DIR__ . "\n\n";

define('ROOT_PATH', str_replace('\\', '/', __DIR__));

// Captura errores fatales que no se pueden atrapar con try/catch (ej.: "Cannot redeclare ...")
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR], true)) {
        echo "\n!!! ERROR FATAL !!!\n" . $e['message'] . "\nArchivo: " . $e['file'] . " (línea " . $e['line'] . ")\n";
    }
});

$archivos = [
    'config/config.php',
    'database/Database.php',
    'models/Usuario.php',
    'services/AuthService.php',
    'controllers/LoginController.php',
    'controllers/UsuarioController.php',
    'controllers/CitaController.php',
    'controllers/PacienteController.php',
    'controllers/TratamientoController.php',
    'controllers/CotizacionController.php',
    'controllers/PagoController.php',
    'controllers/BitacoraController.php',
    'controllers/ExpedienteController.php',
    'controllers/OdontogramaController.php',
    'controllers/DocumentoController.php',
    'models/Bitacora.php',
    'services/RecordatorioService.php',
    'controllers/InventarioController.php',
    'controllers/AsistenteController.php',
    'controllers/ReporteController.php',
    'controllers/AsistenteReportesController.php',
    'controllers/AsistenteSolicitudController.php',
    'models/Pago.php',
    'models/Cotizacion.php',
];

// 1) ¿Existe, tiene errores de sintaxis o caracteres antes de <?php?
echo "── Revisión de cada archivo ──\n";
foreach (array_merge(['index.php'], $archivos) as $f) {
    $ruta = ROOT_PATH . '/' . $f;
    if (!is_file($ruta)) {
        echo "NO EXISTE   $f\n";
        continue;
    }
    $txt = file_get_contents($ruta);
    $aviso = '';
    if (substr($txt, 0, 3) === "\xEF\xBB\xBF") $aviso = '  (tiene BOM al inicio)';
    elseif (substr($txt, 0, 5) !== '<?php') $aviso = '  (NO empieza con <?php)';
    try {
        token_get_all($txt, TOKEN_PARSE);
        echo ($aviso ? 'REVISAR     ' : 'sintaxis OK ') . "$f$aviso\n";
    } catch (ParseError $e) {
        echo "ERROR SINTAXIS $f → {$e->getMessage()} (línea {$e->getLine()})\n";
    }
}

// 2) Cargarlos en el mismo orden que index.php
echo "\n── Carga en el orden de index.php ──\n";
foreach ($archivos as $f) {
    $ruta = ROOT_PATH . '/' . $f;
    if (!is_file($ruta)) continue;
    try {
        require_once $ruta;
        echo "OK     $f\n";
    } catch (Throwable $e) {
        echo "FALLA  $f\n       " . get_class($e) . ': ' . $e->getMessage()
            . ' (línea ' . $e->getLine() . ' de ' . basename($e->getFile()) . ")\n";
        break;
    }
}

echo "\nListo. Copie TODO este texto y envíelo. Luego borre diagnostico.php del servidor.\n";