<?php
/**
 * Használ-e adatbázist.
 */
define('AE_USE_DB', true);

if ($_SERVER['HTTP_HOST'] == 'localhost') {
  $host = 'localhost';
  $user = 'root';
  $pass = 'Janika89!';
  $name = 'AraneaEngine';
  $pref = 'pre_'; //nincs kész
} elseif (strpos($_SERVER['HTTP_HOST'], _DOMAIN) !== false) {
  $host = '';
  $user = '';
  $pass = '';
  $name = '';
  $pref = ''; //nincs kész
} else {
  $host = '';
  $user = '';
  $pass = '';
  $name = '';
  $pref = ''; //nincs kész
}
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