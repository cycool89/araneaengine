<?php
namespace aecore;
/**
 * Alap osztály
 * 
 * Ebből származik minden később örklendő osztály.
 * 
 * Tartalmaz egy boot() abstract metódust amit implementálni kell az osztályokban.
 *
 * @author cycool89
 */
abstract class AClass {

  /** @var Loader */
  public $load;

  /** @var aModule */
  protected $module = null;
  protected $bootValue = null;
  /** @var View */
  protected $view = null;
  
  final public function setLoader(Loader &$load) {
    $this->load = $load;
  }
  
  final public function getBootValue() {
    return $this->bootValue;
  }

  final public function setBootValue($bootValue) {
    $this->bootValue = $bootValue;
  }

  /**
   * 
   * @return aModule
   */
  final public function getModule() {
    return ($this instanceof AModule) ? $this : $this->module;
  }

  final public function setModule(AModule &$module) {
    $this->module = $module;
  }

  /**
   * 
   * @return View
   */
  final public function getView() {
    return $this->view;
  }

  final public function setView(View &$view) {
    $this->view = $view;
  }

  /**
   * Implementálandó a későbbi leszármazottakban.
   * Javaslat: Inicializációs metódusként használjuk.
   */
  abstract function boot();
}
