<?php
namespace aecore;
class Session {

  /**
   * Beállít egy $_SESSION változót
   * <var>$var</var> névvel és <var>$value</var> értékkel
   * 
   * @param string $var
   * @param mixed $value
   */
  static function set($var, $value = NULL) {
    $_SESSION[session_id()][$var] = $value;
  }

  /**
   * Lekérdezi a <var>$var</var> $_SESSION változó értékét.
   * Ha meg van adva <var>$index</var> paraméter és <var>$var</var> egy tömb,
   * akkor visszatérési érték a <var>$var[$index]</var> eleme. Egyébként az <var>$index</var>
   * 
   * Paraméter nélkül visszaadja az összes változót
   * Nem létező változó esetén visszatérési érték false
   * 
   * @param string $var
   * @param string $index
   * @return mixed
   */
  static function get($var = false, $index = false) {
    $ret = false;
    if ($var !== false) {
      if (isset($_SESSION[session_id()][$var])) {
        if ($index !== false && isset($_SESSION[session_id()][$var][$index])) {
          $ret = $_SESSION[session_id()][$var][$index];
        } else {
          $ret = $_SESSION[session_id()][$var];
        }
      }
    } elseif (isset($_SESSION[session_id()])) {
      $ret = $_SESSION[session_id()];
    }
    return $ret;
  }

  /**
   * Törli a <var>$var</var> $_SESSION változót.
   * Visszatérési érték a művelet sikeressége (true/false)
   * 
   * @param string $var
   * @return boolean
   */
  static function del($var = false) {
    if ($var !== false) {
      if (isset($_SESSION[session_id()][$var])) {
        unset($_SESSION[session_id()][$var]);
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  static function getActive() {
    $sess_dir = rtrim(session_save_path(), DS);
    $visitornum = false;
    if ($handle = @opendir($sess_dir)) {
      $visitornum = 0;
      while (($file = readdir($handle)) !== false) {
        if (substr($file, 0, 4) == 'sess') {
          $dir = $sess_dir . DS . $file;
          $k = time() - filemtime($dir);

          if ($k < 900) {
            $visitornum++;
            /* $f = fopen($dir, "r");
              $sor = fgets($f);
              $temp = $sor;
              $findme = 'felhasznalo';
              $pos = strpos($sor, $findme);
              if ($pos !== false)
              {
              $visitornum++;
              $temp = strstr($sor, ';');
              $felhasznalo = str_replace($temp, '', $sor);
              $felhasznalo = strstr($felhasznalo, '"');
              $felhasznalo = str_replace('"', '', $felhasznalo);
              $felhasznalot[$visitornum] = $felhasznalo;
              $jelszo = strstr($sor, ';');
              $jelszo = strstr($jelszo, '"');
              $jelszo = str_replace('"', '', $jelszo);
              $jelszo = str_replace(';', '', $jelszo);
              $jelszot[$visitornum] = $jelszo;
              }
              fclose($f); */
          }
        }
      }
      closedir($handle);
    }
    return $visitornum;
  }

}
