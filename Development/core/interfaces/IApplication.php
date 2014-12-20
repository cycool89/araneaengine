<?php
namespace aecore;
/**
 *
 * @author cycool89
 */
interface IApplication {

  function index();

  function render();
  
  /**
   * @return boolean
   */
  function beforeCall($class, $method);

  function afterCall($class, $method);
}
