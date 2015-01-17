<?php

namespace aecore;

/**
 * Description of View
 *
 * @author cycool89
 */
class View extends \Smarty {

  private static $templates = array();
  private $incDir = '';
  private $noise = '';
  private $size;
  private $char;

  public function __construct() {
    parent::__construct();
    mkdir_r('_smarty', 0775);
    mkdir_r('_smarty' . DS . 'templates_c' . DS, 0775);
    mkdir_r('_smarty' . DS . 'cache' . DS, 0775);
    $this->setCompileDir(AE_BASE_DIR . '_smarty' . DS . 'templates_c' . DS);
    $this->setCacheDir(AE_BASE_DIR . '_smarty' . DS . 'cache' . DS);
    $this->setTemplateDir(AE_TEMPLATES);
    //$smarty->setConfigDir('/web/www.example.com/guestbook/configs/');
    //$this->setDefaultTemplateExt(VIEW_EXT);
    $this->error_reporting = E_ALL & ~E_NOTICE;
  }

  public function addTemplate($moduleName, $templateName, View_Internal_Template &$tpl) {
    self::$templates[$moduleName][$templateName] = $tpl;
  }

  /**
   * 
   * @param string $moduleName
   * @param boolean $erase
   * @return array
   */
  public function getTemplates($moduleName = null, $erase = false) {
    $ret = array();
    if (is_null($moduleName)) {
      $ret = self::$templates;
      if ($erase) {
        self::$templates = array();
      }
    } elseif (isset(self::$templates[$moduleName])) {
      $ret = self::$templates[$moduleName];
      if ($erase) {
        unset(self::$templates[$moduleName]);
      }
    }
    return $ret;
  }
  
  public function createTemplate($template, $cache_id = null, $compile_id = null, $parent = null, $do_clone = true) {
    $moduleName = explode(DS, $template);
    $file = array_pop($moduleName);
    $this->incDir = AE_BASE_PATH . basename(AE_TEMPLATES) . DS . implode('/', $moduleName) . DS;
    $tpl = parent::createTemplate($template, $cache_id, $compile_id, $parent, $do_clone);
    
    return new View_Internal_Template($tpl,$this->incDir);
  }
  
  public function setIncDir($incDir) {
    $this->incDir = $incDir;
  }

  public function getIncDir() {
    return $this->incDir;
  }

  

}
