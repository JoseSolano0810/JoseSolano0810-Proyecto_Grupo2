<?php
$host    = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
$esLocal = in_array(explode(':', $host)[0], ['localhost', '127.0.0.1', '::1'], true);

define('APP_ENV', $esLocal ? 'local' : 'produccion');

// ── Base de datos ─────────────────────────────────────────
if (APP_ENV === 'local') {

    define('DB_HOST', 'srv1780.hstgr.io');
} else {

    define('DB_HOST', 'localhost');
}

define('DB_PORT',    '3306');
define('DB_NAME',    'u546222637_odent_db');
define('DB_USER',    'u546222637_odent_db');
define('DB_PASS',    '1342JoZa$');
define('DB_CHARSET', 'utf8mb4');


$docroot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$root    = rtrim(str_replace('\\', '/', ROOT_PATH), '/');
$sub     = str_ireplace($docroot, '', $root);  
$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

define('BASE_URL',     $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $sub);
define('SESSION_NAME', 'odent_session');


ini_set('display_errors', APP_ENV === 'local' ? '1' : '0');
error_reporting(E_ALL);

date_default_timezone_set('America/Costa_Rica');