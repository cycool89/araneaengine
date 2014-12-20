<?php
namespace aecore;
class URL {

  private static $URL = array();
  private static $PATH_INFO = '';

  static function initURL() {
    switch (true) {
      case isset($_SERVER['PATH_INFO']):
        $URL = self::makeArrayFromURI($_SERVER['PATH_INFO']);
        self::$PATH_INFO = $_SERVER['PATH_INFO'];
        break;
      case isset($_SERVER['ORIG_PATH_INFO']):
        $URL = self::makeArrayFromURI($_SERVER['ORIG_PATH_INFO']);
        self::$PATH_INFO = $_SERVER['ORIG_PATH_INFO'];
        break;
      default:
        $URL = array();
        break;
    }
    self::$URL = $URL;
  }

  static function makeArrayFromURI($string) {
    $URL = trim($string,'/');
    $URL = explode('/', strip_tags($URL));
    array_walk_recursive($URL, 'strip_tags');
    return $URL;
  }

  /**
   * Visszaadja az URL <var>$num</var>. argumentumát
   * 0-tól kezdve az indexelést
   * Paraméter nélkül az egész URL-t sorszámozott tömbben
   * 
   * @param integer $num
   * @return mixed
   */
  static function arg($num = -1) {
    if ($num >= 0) {
      return (isset(self::$URL[$num])) ? self::$URL[$num] : false;
    } else {
      return (!empty(self::$URL)) ? self::$URL : array();
    }
  }

  /**
   * Összeolvaszt két stringet:
   *  <var>$path1</var>-et és <var>$path2</var>-t a <var>$separator</var> stringgel
   * 
   * @param string $path1
   * @param string $path2
   * @param string $separator
   * @return string
   */
  static function implode($path1, $path2, $separator = '/') {
    $path1 = rtrim($path1, $separator);
    $path2 = ltrim($path2, $separator);
    $result = $path1 . $separator . $path2;
    return $result;
  }

  /**
   * Oldal átirányítás <var>$url</var>-re
   * 
   * @param string $url
   */
  static function redirect($url) {
    if (strpos($url, 'http') === false) {
      $url = self::implode(AE_BASE_PATH, $url); //BASE_PATH . $url;
    }

    if (!headers_sent()) {
      header('Location: ' . $url);
      exit();
    }
    exit('<meta http-equiv="refresh" content="0; url=' . $url . '"/>');
  }

  static function getPathInfo() {
    return self::$PATH_INFO;
  }

}

/**
 * Visszaadja az URL <var>$x</var>. argumentumát
 * 0-tól kezdve az indexelést
 * Paraméter nélkül az egész URL-t sorszámozott tömbben
 * 
 * @param integer $x
 * @return mixed
 */
function arg($x = -1) {
  return URL::arg($x);
}
