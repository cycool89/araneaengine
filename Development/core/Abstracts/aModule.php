<?php
namespace core;
/**
 * Description of aModule
 *
 * @author cycool89
 */
abstract class aModule extends aClass {

  /**
   * Végrehajtja a megadott paraméterek alapján
   * a <var>$controller</var>-><var>$method</var>(<var>$params[0],...</var>);
   * parancsot.
   * 
   * @param string $controller
   * @param string $method
   * @param array $params
   * @return mixed
   */
  final public function run($controller, $method, array $params = array()) {
    $this->load->controller($controller);
    return call_user_func_array(array($this->$controller, $method), $params);
  }

}
