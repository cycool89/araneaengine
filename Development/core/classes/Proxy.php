<?php

namespace aecore;

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

      if (!$this->recursiveCheck) {
        $this->recursiveCheck = true;
        if (AE()->getApplication()->beforeCall(get_class($this->proxifiedClass), $methodName) === true) {
          $ret = call_user_func_array(array(&$this->proxifiedClass, $methodName), $arguments);
          AE()->getApplication()->afterCall(get_class($this->proxifiedClass), $methodName);
        }
        $this->recursiveCheck = false;
      }
      return $ret;
    } else {
      $class = get_class($this->proxifiedClass);
      Log::write("Hibás metódusnév: {$class}->{$methodName}()", true, true, 2);
      throw new \BadMethodCallException("No callable method $methodName at $class class");
    }
  }

  public function &__get($name) {
    return $this->proxifiedClass->$name;
  }

  public function __set($name, $value) {
    $this->proxifiedClass->$name = $value;
  }

}
