<?php

/**
 * Description of View
 *
 * @author cycool89
 */
class View extends Smarty {

  private $templates = array();
  private $incDir = '';

  public function __construct() {
    parent::__construct();
    $this->setCompileDir(AE_BASE_DIR . '_smarty' . DS . 'templates_c' . DS);
    $this->setCacheDir(AE_BASE_DIR . '_smarty' . DS . 'cache' . DS);
    $this->setTemplateDir(AE_TEMPLATES);
    //$smarty->setConfigDir('/web/www.example.com/guestbook/configs/');
    //$this->setDefaultTemplateExt(VIEW_EXT);
    $this->error_reporting = error_reporting();
  }

  public function addTemplate($moduleName, $templateName, Smarty_Internal_Template $tpl) {
    if (!isset($this->templates[$moduleName])) {
      $this->templates[$moduleName][$templateName] = $tpl;
    } else {
      $this->templates[$moduleName][$templateName] = $tpl;
    }
  }

  /**
   * 
   * @param string $moduleName
   * @return mixed
   */
  public function getTemplates($moduleName = null) {
    $ret = null;
    if (is_null($moduleName)) {
      $ret = $this->templates;
    } else {
      if (isset($this->templates[$moduleName])) {
        $ret = $this->templates[$moduleName];
      }
    }
    return $ret;
  }

  public function createTemplate($template, $cache_id = null, $compile_id = null, $parent = null, $do_clone = true) {
    $moduleName = explode(DS, $template);
    $moduleName = $moduleName[0];
    $this->incDir = AE_BASE_PATH . basename(AE_TEMPLATES) . DS . $moduleName . DS;
    $dom_object = file_get_html($this->getTemplateDir(0) . $template);
    $html = $this->_globalizeHTML($dom_object);
    return parent::createTemplate('string:' . $html, $cache_id, $compile_id, $parent, $do_clone);
  }

  private function _globalizeHTML(simple_html_dom $dom) {
    foreach ($dom->find("a") as $e) {
      $this->checkLink($e);
    }
    foreach ($dom->find("img") as $e) {
      $this->checkImage($e);
    }
    return $dom->save();
  }

  private function checkLink($a) {
    $href = (isset($a->href)) ? trim($a->href, '/') : '';
    $hrefPieces = explode('/', $href);
    $hrefPieces = ($hrefPieces[0] == '') ? array() : $hrefPieces;
    if (strpos($href, ':') !== false) {
      //Ha van benn ':' ne csinálj semmit. 'http:' 'Module:'
      $a->href = $href;
    } elseif (strpos($href, '.') !== false) {
      $a->href = $this->incDir . $href;
    } else {
      //Ha nincs benn ':'
      //Appreq van-e és van-e benn
      if (Request::isAppReq() && array_search(Config::getEntry('AppReqWord'), $hrefPieces) === false) {
        array_unshift($hrefPieces, Config::getEntry('AppReqWord'));
      }
      //Multilanguage van-e és van-e benn
      if (Config::getEntry('Multilanguage') && array_search(Session::get('lang', 'code'), $hrefPieces) === false) {
        array_unshift($hrefPieces, Session::get('lang', 'code'));
      }
      $modrewrite = (AE_MOD_REWRITE) ? '' : 'index.php/';
      $a->href = AE_BASE_PATH . $modrewrite . implode('/', $hrefPieces);
      if ($a->href[strlen($a->href) - 1] !== '/') {
        $a->href .= '/';
      }
    }
  }

  private function checkImage($e) {
  }

}
