<?php

namespace aecore;

class Request {

  private static $GET = NULL;
  private static $POST = NULL;
  private static $FILES = NULL;
  private static $Module = NULL;
  private static $Controller = NULL;
  private static $Method = NULL;
  private static $Params = NULL;
  private static $appReq = false;
  //appReq-t jelző szó
  private static $key_word;

  static function initRequest() {
    self::$key_word = Config::getEntry('AppReqWord');
    array_walk_recursive($_GET, 'strip_tags');
    self::$GET = $_GET;
    self::$POST = $_POST;
    self::$FILES = $_FILES;
  }

  static function setMVC(array $array = array()) {

    $url = (empty($array) || !is_array($array)) ? URL::arg() : $array;

    $reqStartIndex = (Config::getEntry('Multilanguage')) ? 1 : 0;
    
    if (isset($url[$reqStartIndex]) && strpos($url[$reqStartIndex], 'Module:') === 0) {
      $url[$reqStartIndex] = str_replace('Module:', '', $url[$reqStartIndex]);
    }

    if (Config::getEntry('Multilanguage')) {
      $foundLanguage = false;
      $i = 0;
      while ($i < count($url) && $foundLanguage === false) {
        $foundLanguage = Config::isLanguage($url[$i]);
        $i++;
      }
      if ($foundLanguage === false) {
        if (!Session::get('lang')) {
          Session::set('lang', Config::getDefaultLanguage());
        }
        $url = array_merge(array(Session::get('lang', 'code')), $url);
        URL::redirect('/' . implode('/', $url) . '/' . self::getQueryString());
      } else {
        $urlLang = Config::getLanguage($url[$i - 1]);
        $temp = $url[$i - 1];
        unset($url[$i - 1]); //Közbülső helyről eltűntet
        $url = array_values($url); //Tömb újraszámozása
        $url = array_merge(array($temp), $url); //Tömb eléfűzése
        if (!Session::get('lang') || Session::get('lang', 'code') !== $urlLang['code'] || $i != 1) {
          Session::set('lang', Config::getLanguage($temp));
          URL::redirect('/' . implode('/', $url) . '/' . self::getQueryString());
        }
      }
      array_shift($url);
    }
    
    $appreq = array_search(self::$key_word, $url);
    if ($appreq !== false) {
      self::$appReq = true;
      unset($url[$appreq]); //Közbülső helyről eltűntet
      $url = array_values($url); //Tömb újraszámozása
    }

    $def_req['Module'] = Config::getEntry('Module');
    $def_req['Controller'] = Config::getEntry('Controller');
    $def_req['Method'] = Config::getEntry('Method');
    $def_req['Params'] = Config::getEntry('Params');

    if (empty($url)) {
      self::$Module = $def_req['Module'];
      self::$Controller = $def_req['Controller'];
      self::$Method = $def_req['Method'];
      self::$Params = $def_req['Params'];
    } elseif (is_array($url)) {
      $req = self::getRequestFromArray($url);

      self::$Module = $req['Module'];
      self::$Controller = $req['Controller'];
      self::$Method = $req['Method'];
      if (empty($req['Params'])) {
        self::$Params = $def_req['Params'];
      } else {
        self::$Params = $req['Params'];
      }
    }
  }

  static function getRequestFromArray($url) {
    $def_req['Module'] = Config::getEntry('Module');
    $def_req['Controller'] = Config::getEntry('Controller');
    $def_req['Method'] = Config::getEntry('Method');
    $def_req['Params'] = Config::getEntry('Params');
    $m = $c = $me = $p = '';
    $which = 0;
    if (Loader::isModuleExists($url[$which])) {
      $m = $url[$which];
      array_shift($url);
    } else {
      $m = $def_req['Module'];
    }

    if (isset($url[$which]) && Loader::isControllerExists($m, $url[$which])) {
      $c = $url[$which];
      array_shift($url);
    } else {
      $c = lcfirst($m);
    }

    if (isset($url[$which]) && Loader::isMethodExists($m, $c, $url[$which])) {
      $me = $url[$which];
      array_shift($url);
    } else {
      $me = $def_req['Method'];
    }

    $p = self::makeParamsFromArray($url, $which);
    return array('Module' => $m, 'Controller' => $c, 'Method' => $me, 'Params' => $p);
  }

  public static function getQueryString() {
    $get = array();
    foreach (self::$GET as $key => $value) {
      $get[] = $key . '=' . $value;
    }
    return (!empty($get)) ? '?' . implode('&', $get) : '';
  }

  static function makeParams() {
    $url = URL::arg();
    array_shift($url);
    array_shift($url);
    array_shift($url);
    $Params = null;
    for ($i = 0; $i < count($url); $i += 2) {
      $Params[$url[$i]] = isset($url[$i + 1]) ? $url[$i + 1] : true;
    }
    return $Params;
  }

  static function makeParamsFromArray($url, $which = 0) {
    $Params = array();
    for ($i = $which; $i < count($url); $i++) {
      $Params[] = $url[$i];
    }
    return $Params;
  }

  public static function GET($index = null) {
    $ret = null;
    if (!is_null($index)) {
      if (isset(self::$GET[$index])) {
        $ret = self::$GET[$index];
      }
    } else {
      $ret = self::$GET;
    }
    return $ret;
  }

  public static function POST($index = null) {
    $ret = null;
    if (!is_null($index)) {
      if (isset(self::$POST[$index])) {
        $ret = self::$POST[$index];
      }
    } else {
      $ret = self::$POST;
    }
    return $ret;
  }

  public static function FILES($index = null) {
    $ret = null;
    if (!is_null($index)) {
      if (isset(self::$FILES[$index])) {
        $ret = self::$FILES[$index];
      }
    } else {
      $ret = self::$FILES;
    }
    return $ret;
  }

  public static function Module() {
    return self::$Module;
  }

  public static function Controller() {
    return self::$Controller;
  }

  public static function Method() {
    return self::$Method;
  }

  public static function Params($num = -1) {
    if (is_string($num)) {
      return array_search($num, self::$Params);
    } elseif ($num >= 0) {
      return (isset(self::$Params[$num]) && self::$Params[$num] != '') ? self::$Params[$num] : false;
    } else {
      return (isset(self::$Params[0]) && self::$Params[0] != '') ? self::$Params : false;
    }
  }

  public static function isAppReq() {
    return self::$appReq;
  }

}

Request::initRequest();

/**
 * Visszaadja a Request osztály által paramérként megítélt URL szeletet az alábbiak alapján:
 * Paraméter nélkül:
 *  Az egész paraméter listát sorszámozott tömbben
 * <var>$num</var> paraméter esetén:
 *  -Ha szám, akkor a <var>$num</var>. paraméter értékét
 *  -Ha string, akkor a <var>$num</var> szöveg helyét a paraéterlistában
 * Nem létező <var>$num</var> érték esetén false
 * 
 * @param mixed $num
 * @return mixed
 */
function param($num = -1) {
  return Request::Params($num);
}
