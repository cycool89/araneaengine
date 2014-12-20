<?php
namespace aecore;
/**
 *
 * @author cycool89
 */
interface IConfig {
  static function addEntry($entry,$value);
  static function getEntry($entry);
}
