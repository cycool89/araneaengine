<?php

/* session_save_path('sessions'); // mappa ahol tárolni kell a session-t
  ini_set('session.gc_probability', 1);
  ini_set('session.gc_divisor', 1); */

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: text/html; charset=UTF-8');
/**
 * Ügyfél adatok
 */
define('_CNAME', 'SüleWeb Kft.');
define('_NAME', 'Kigyós János');
define('_DOMAIN', 'araneaengine.hu');
define('_EMAIL', 'info@araneaengine.hu');

/**
 * Környezet beállítása<br>
 * 'development': error_reporting(E_ALL);<br>
 * 'product'    : error_reporting(E_ALL & ~E_NOTICE)<br>
 * 'final'      : error_reporting(0);<br>
 */
define('AE_ENVIRONMENT', 'product');

define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);
/**
 * index.php teljes elérési útvonala
 */
define('AE_BASE_DIR', dirname(__FILE__) . DS);
/**
 * core mappa elérési útvonala
 */
define('AE_CORE_DIR', AE_BASE_DIR . 'core' . DS);

require_once AE_CORE_DIR . 'bootstrap.php';
require_once AE_CORE_DIR . 'AraneaEngine.php';

\aecore\AE()->start();
