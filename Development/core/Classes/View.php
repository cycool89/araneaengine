<?php

/**
 * Description of View
 *
 * @author cycool89
 */
class View extends Smarty {

  private $templates = array();

  public function __construct() {
    parent::__construct();
    $this->setCompileDir(AE_BASE_DIR . '_smarty' . DS . 'templates_c' . DS);
    $this->setCacheDir(AE_BASE_DIR . '_smarty' . DS . 'cache' . DS);
    $this->setTemplateDir(AE_TEMPLATES);
    //$smarty->setConfigDir('/web/www.example.com/guestbook/configs/');
    //$this->setDefaultTemplateExt(VIEW_EXT);
    $this->error_reporting = error_reporting();
  }

  public function addTemplate($moduleName, Smarty_Internal_Template $tpl) {
    if (!isset($this->templates[$moduleName])) {
      $this->templates[$moduleName][] = $tpl;
    } else
    {
      $this->templates[$moduleName][] = $tpl;
    }
  }

  /**
   * 
   * @param string $moduleName
   * @return mixed
   */
  public function getTemplates($moduleName = null) {
    return (!is_null($moduleName)) ?
            $this->templates[$moduleName] : $this->templates;
  }

  public function createTemplate($template, $cache_id = null, $compile_id = null, $parent = null, $do_clone = true) {
    $dom_object = file_get_html($this->getTemplateDir(0) . $template);
    $html = $this->_globalizeHTML($dom_object);
    return parent::createTemplate('string:'.$html, $cache_id, $compile_id, $parent, $do_clone);
  }

  private function _globalizeHTML(simple_html_dom $dom) {
    /*foreach ($dom->find("a") as $e) {
      $e->href = "xxx";
    }*/
    return $dom->save();
  }
  
}
