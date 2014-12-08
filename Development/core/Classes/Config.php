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

}
