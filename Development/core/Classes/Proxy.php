<?php

class Proxy {

  /** @var aFormController */
  private $proxifiedClass;
  private $recursiveCheck = false;

  function __construct(aController &$proxifiedClass) {
    $this->proxifiedClass = &$proxifiedClass;
  }

  public function __call($methodName, $arguments) {
    if (is_callable(array($this->proxifiedClass, $methodName))) {
      $ret = false;

      if (AE()->getApplication()->beforeCall(get_class($this->proxifiedClass), $methodName) === true) {
        $ret = call_user_func_array(array(&$this->proxifiedClass, $methodName), $arguments);
        AE()->getApplication()->afterCall(get_class($this->proxifiedClass), $methodName);
      }
      return $ret;
    } else {
      $class = get_class($this->proxifiedClass);
      throw new BadMethodCallException("No callable method $methodName at $class class");
    }
  }

  public function &__get($name) {
    return $this->proxifiedClass->$name;
  }

  public function __set($name, $value) {
    $this->proxifiedClass->$name = $value;
  }

}
