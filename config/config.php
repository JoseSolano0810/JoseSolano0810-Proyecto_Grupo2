<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'odent_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

$root    = str_replace('\\', '/', dirname(__DIR__));
$docroot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$sub     = rtrim(str_replace($docroot, '', $root), '/');
$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

define('BASE_URL',  $proto . '://' . $_SERVER['HTTP_HOST'] . $sub);
define('ROOT_PATH', $root);

define('SESSION_NAME', 'odent_session');

date_default_timezone_set('America/Costa_Rica');
