<?php

/**
 *
 * @author cycool89
 */
interface iApplication {

  function index();

  function render();
  
  /**
   * @return boolean
   */
  function beforeCall($class, $method);

  function afterCall($class, $method);
}
