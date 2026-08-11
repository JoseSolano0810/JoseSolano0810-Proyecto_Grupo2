<?php
/*  Base de datos   */
define('DB_HOST',    'localhost');     
define('DB_NAME',    'odent_db');       
define('DB_USER',    'root');           
define('DB_PASS',    '');               
define('DB_CHARSET', 'utf8mb4');

/*  Rutas dinamicas  */
$docroot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$root    = rtrim(str_replace('\\', '/', ROOT_PATH), '/');
$sub     = str_replace($docroot, '', $root);
$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

define('BASE_URL', $proto . '://' . $_SERVER['HTTP_HOST'] . $sub);

/*  Sesion  */
define('SESSION_NAME', 'odent_session');

/*  Zona horaria  */
date_default_timezone_set('America/Costa_Rica');