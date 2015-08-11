<?php
/**
 * Használ-e adatbázist.
 */
define('AE_USE_DB', true);

if ($_SERVER['HTTP_HOST'] == 'localhost') {
  $driv = 'pdo_mysql'; // Doctrine driverek (pdo_sqlite || pdo_mysql || ... )
  $host = 'localhost';
  $user = '';
  $pass = '';
  $name = '';
  $pref = 'pre_';
} elseif (strpos($_SERVER['HTTP_HOST'], _DOMAIN) !== false) {
  $driv = 'pdo_mysql'; // Doctrine driverek (pdo_sqlite || pdo_mysql || ... )
  $host = 'localhost';
  $user = '';
  $pass = '';
  $name = '';
  $pref = 'pre_';
} else {
  $driv = 'pdo_mysql'; // Doctrine driverek (pdo_sqlite || pdo_mysql || ... )
  $host = 'localhost';
  $user = '';
  $pass = '';
  $name = '';
  $pref = '';
}
/**
 * Adatbázis driver.
 */
define('AE_DBDRIV', $driv);
/**
 * Adatbázis host név.
 */
define('AE_DBHOST', $host);
/**
 * Adatbázis Felhasználónév.
 */
define('AE_DBUSER', $user);
/**
 * Adatbázis jelszó.
 */
define('AE_DBPASS', $pass);
/**
 * Adatbázis neve.
 */
define('AE_DBNAME', $name);
/**
 * Adatbázis tábla prefix
 */
define('AE_DBPREFIX', $pref); 