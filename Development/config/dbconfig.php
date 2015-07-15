<?php
/**
 * Használ-e adatbázist.
 */
define('AE_USE_DB', false);

if ($_SERVER['HTTP_HOST'] == 'localhost') {
  $driv = 'mysqli';
  $host = 'localhost';
  $user = '';
  $pass = '';
  $name = '';
  $pref = 'pre_'; //nincs kész
} elseif (strpos($_SERVER['HTTP_HOST'], _DOMAIN) !== false) {
  $driv = 'mysqli';
  $host = 'localhost';
  $user = '';
  $pass = '';
  $name = '';
  $pref = 'pre_'; //nincs kész
} else {
  $driv = 'mysqli';
  $host = 'localhost';
  $user = '';
  $pass = '';
  $name = '';
  $pref = 'pre_'; //nincs kész
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
 * Adatbázis tábla prefix (fixme: nem működik)
 */
define('AE_DBPREFIX', $pref); //nincs kész