<?php
namespace core;
/**
 * Description of aClass
 *
 * @author cycool89
 */
abstract class aClass {

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
    return ($this instanceof aModule) ? $this : $this->module;
  }

  final public function setModule(aModule &$module) {
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

  abstract function boot();
}
