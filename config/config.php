<?php
/*  Base de datos   */
define('DB_HOST', 'localhost');     
define('DB_NAME', 'odent_db');       
define('DB_USER', 'root');           
define('DB_PASS', '');               
define('DB_CHARSET', 'utf8mb4');

/*  Rutas dinamicas  */
$root    = str_replace('\\', '/', dirname(__DIR__)); 
$docroot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$sub     = rtrim(str_replace($docroot, '', $root), '/');
$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

define('BASE_URL',  $proto . '://' . $_SERVER['HTTP_HOST'] . $sub);
define('ROOT_PATH', $root);

/*  Sesion  */
define('SESSION_NAME', 'odent_session');

/*  Zona horaria  */
date_default_timezone_set('America/Costa_Rica');
