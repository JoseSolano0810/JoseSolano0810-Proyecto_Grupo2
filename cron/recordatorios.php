<?php
/**
 * CIT-05: proceso automático de recordatorios.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo puede ejecutarse desde la línea de comandos.');
}

define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)));
$_SERVER['HTTP_HOST']     = $argv[1] ?? 'localhost';   
$_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] ?? '';

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/database/Database.php';
require_once ROOT_PATH . '/services/RecordatorioService.php';

$r = RecordatorioService::procesar();
echo date('Y-m-d H:i:s') . " | revisadas: {$r['revisadas']} | enviados: {$r['enviados']} | fallidos: {$r['fallidos']} | sin medio válido: {$r['omitidos']}\n";
