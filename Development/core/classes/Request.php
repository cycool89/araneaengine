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

    $reqStartIndex = 0;
    if (Config::getEntry('Multilanguage')) {
      $reqStartIndex = 1;
    }
    if (isset($url[$reqStartIndex]) && strpos($url[$reqStartIndex], 'Module:') === 0) {
      $url[$reqStartIndex] = str_replace('Module:', '', $url[$reqStartIndex]);
    }
    $langs = array_keys(Config::getEntry('Languages'));
    if (Config::getEntry('Multilanguage')) {
      if (!Session::get('lang')) {
        if (isset($url[0]) && in_array($url[0], $langs)) {
          Session::set('lang', config::getLanguage($url[0]));
          array_shift($url);
        } else {
          Session::set('lang', config::getDefaultLanguage());
          URL::redirect('/' . Session::get('lang', 'code') . '/');
        }
      } elseif (isset($url[0])) {
        if (Session::get('lang', 'code') !== $url[0]) {
          if (in_array($url[0], $langs)) {
            Session::set('lang', config::getLanguage($url[0]));
            array_shift($url);
          } else {
            URL::redirect('/' . Session::get('lang', 'code') . URL::getPathInfo());
          }
        } else {
          array_shift($url);
        }
      } elseif (!isset($url[0])) {
        URL::redirect('/' . Session::get('lang', 'code') . URL::getPathInfo() . '/');
      }
    } elseif (Session::get('lang')) {

      Session::del('lang');
    }

    if (isset($url[0]) && $url[0] === self::$key_word) {
      self::$appReq = true;
      array_shift($url);
    }

    $def_req['Module'] = Config::getEntry('Module');
    $def_req['Controller'] = Config::getEntry('Controller');
    $def_req['Method'] = Config::getEntry('Method');
    $def_req['Params'] = Config::getEntry('Params');

    if (!$url) {
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
