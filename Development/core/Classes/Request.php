<?php

class Request {

  static $GET = NULL;
  static $POST = NULL;
  static $FILES = NULL;
  static $Module = NULL;
  static $Controller = NULL;
  static $Method = NULL;
  static $Params = NULL;
  static $appReq = false;
  //appReq-t jelző szó
  static $key_word;

  static function initRequest() {
    //self::setMVC();
    self::$key_word = config::$appReqWord;
    array_walk_recursive($_GET, 'strip_tags');
    //array_walk_recursive($_POST, 'strip_tags');
    self::$GET = $_GET;
    self::$POST = $_POST;
    self::$FILES = $_FILES;
  }

  static function setMVC($array = array()) {

    self::$Module = null;
    self::$Controller = null;
    self::$Method = null;
    self::$Params = null;

    $def_req = config::getDefaultRequest();
    $url = (empty($array) || !is_array($array)) ? URL::arg() : $array;

    if (config::$MultiLanguage) {
      if (isset($url[1]) && strpos($url[1], 'Module:') === 0) {
        $url[1] = str_replace('Module:', '', $url[1]);
      }
    } else {
      if (isset($url[0]) && strpos($url[0], 'Module:') === 0) {
        $url[0] = str_replace('Module:', '', $url[0]);
      }
    }

    $langs = array_keys(config::getLanguages());
    if (config::$MultiLanguage) {
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
    } else {
      if (Session::get('lang'))
        Session::del('lang');
    }

    if (isset($url[0]) && $url[0] === self::$key_word) {
      self::$appReq = true;
      array_shift($url);
    }
    //echo d(Session::get('lang'), $url);


    /* if (isset($url[0]) && !in_array($url[0], array_keys(config::getLanguages())) && config::$MultiLanguage)
      {
      $langs = array_keys(config::getLanguages());
      URL::redirect($langs[0] . '/' . implode('/', $url));
      } */
    if (!$url) {
////////////////////////////////////////////////////////
// Alapértelmezett Modul/Controller/Method beállítása //
////////////////////////////////////////////////////////
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

      /* self::$Module = (URL::arg(0)) ? $url[0] : $def_req['Module'];
        self::$Controller = (URL::arg(1)) ? $url[1] : $def_req['Controller'];
        self::$Method = (URL::arg(2)) ? $url[2] : $def_req['Method'];
        self::$Params = (URL::arg(3)) ? self::makeParams() : $def_req['Params']; */
    }
    //echo d(get_class_vars('Request'),URL::arg());
  }

  static function getRequestFromArray($url) {
    $def_req = config::getDefaultRequest();
    $m = $c = $me = $p = '';
    //$which = config::$MultiLanguage ? 1 : 0;
    $which = 0;
    if (Modules::isModuleExists($url[$which])) {
      $m = $url[$which];
      array_shift($url);
    } else {
      $m = $def_req['Module'];
      //$which++;
    }

    if (isset($url[$which]) && Modules::isControllerExists($m, $url[$which])) {
      $c = $url[$which];
      array_shift($url);
    } else {
      $c = lcfirst($m);
      //$c = $def_req['Controller'];
      //$which++;
    }

    if (isset($url[$which]) && Modules::isMethodExists($m, $c, $url[$which])) {
      $me = $url[$which];
      array_shift($url);
    } else {
      $me = $def_req['Method'];
      //$which++;
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
    /* for ($i = $which; $i < count($url); $i += 2)
      {
      $Params[$url[$i]] = isset($url[$i + 1]) ? $url[$i + 1] : true;
      } */
    return $Params;
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
  if (is_string($num)) {
    return array_search($num, Request::$Params);
  } elseif ($num >= 0) {
    return (isset(Request::$Params[$num]) && Request::$Params[$num] != '') ? Request::$Params[$num] : false;
  } else {
    return (isset(Request::$Params[0]) && Request::$Params[0] != '') ? Request::$Params : false;
  }
}
