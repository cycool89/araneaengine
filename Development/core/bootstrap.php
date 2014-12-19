<?php

/**
 * PHP script fájlok kiterjesztése.
 */
define('AE_EXT', '.php');
/**
 * Nézet fájlok kiterjesztése.
 */
define('AE_VIEW_EXT', '.html');
/**
 * Templatek elérési útvonala
 */
define('AE_TEMPLATES', AE_BASE_DIR . 'Templates' . DS);

require_once 'common.php';

switch (AE_ENVIRONMENT) {
  case 'development':
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    define('AE_DEBUG_MODE',true);
    break;
  case 'product':
    ini_set('display_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE);
    define('AE_DEBUG_MODE',true);
    break;
  case 'final':
    ini_set('display_errors', 0);
    error_reporting(0);
    define('AE_DEBUG_MODE',false);
    break;
}

$base_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', AE_BASE_DIR);
$base_path = str_replace('\\', '/', $base_path);
if (count($base_path) < 1) {
  $base_path = '/';
}
/**
 * index.php elérési útvonala a
 * DocumentRoot-hoz viszonyítva
 */
define('AE_BASE_PATH', $base_path);

if (!file_exists(AE_BASE_DIR . '.htaccess')) {
  $fileContents = "
IndexIgnore *
<ifmodule mod_rewrite.c>
  SetEnv HTTP_MOD_REWRITE On
  RewriteEngine on

  RewriteBase $base_path

  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d

  RewriteRule ^(.*)$ index.php/$1 [L,QSA]
</IfModule>
    ";
  file_force_contents(AE_BASE_DIR . '.htaccess', $fileContents);
}

$mod_rewrite = false;
if (function_exists('apache_get_modules')) {
  $modules = apache_get_modules();
  $mod_rewrite = (in_array('mod_rewrite', $modules) && getenv('HTTP_MOD_REWRITE') == 'On');
} else {
  $mod_rewrite = (getenv('HTTP_MOD_REWRITE') == 'On' ? true : false );
}
/**
 * Rewrite modul aktív-e.
 */
define('AE_MOD_REWRITE', $mod_rewrite);

if (!is_dir(AE_BASE_DIR . 'Modules')) {
  mkdir_r('Modules', 0775);
}
if (!is_dir(AE_BASE_DIR . 'Templates')) {
  mkdir_r('Templates', 0775);
}