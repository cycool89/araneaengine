<?php

/**
 * Description of Config
 *
 * @author cycool89
 */
class Config implements iConfig {

  private static $entries = array();

  public static function addEntry($entry, $value) {
    self::$entries[$entry] = $value;
  }

  public static function getEntry($entry) {
    return (isset(self::$entries[$entry]) ? self::$entries[$entry] : null);
  }
  
  public static function getDefaultLanguage()
  {
    $langs = array_keys(self::$entries['Languages']);
    return array('code' => $langs[0], 'name' => self::$entries['Languages'][$langs[0]]);
  }
  
  static function getLanguage($code)
  {
    return array('code' => $code, 'name' => self::$entries['Languages'][$code]);
  }

}
