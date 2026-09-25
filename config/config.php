<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'u546222637_odent_db');
define('DB_USER',    'u546222637_odent_db');
define('DB_PASS',    '1342JoZa$');
define('DB_CHARSET', 'utf8mb4');

$docroot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$root    = rtrim(str_replace('\\', '/', ROOT_PATH), '/');
$sub     = str_replace($docroot, '', $root);
$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

define('BASE_URL',  $proto . '://' . $_SERVER['HTTP_HOST'] . $sub);
define('SESSION_NAME', 'odent_session');
date_default_timezone_set('America/Costa_Rica');