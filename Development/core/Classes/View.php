<?php

/**
 * Description of View
 *
 * @author cycool89
 */
class View extends Smarty {

  private $templates = array();
  private $incDir = '';
  private $noise = '';
  private $size;
  private $char;

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
      $this->checkAnchor($e);
    }
    foreach ($dom->find("img") as $e) {
      $this->checkImage($e);
    }
    foreach ($dom->find("link") as $e) {
      $this->checkLink($e);
    }
    foreach ($dom->find("script") as $e) {
      $this->checkScript($e);
    }
    return $dom->save();
  }

  private function checkAnchor($e) {
    $this->remove_noise("'(<\{(.*?)\}>)'s",$e,'href');
    
    $href = (isset($e->href)) ? trim($e->href, '/') : '';
    $hrefPieces = explode('/', $href);
    $hrefPieces = ($hrefPieces[0] == '') ? array() : $hrefPieces;
    if (strpos($href, ':') !== false) {
      //Ha van benn ':' ne csinálj semmit. 'http:' 'Module:'
      $e->href = $href;
    } elseif (strpos($href, '.') !== false) {
      $e->href = $this->incDir . $href;
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
      $e->href = AE_BASE_PATH . $modrewrite . implode('/', $hrefPieces);
      if ($e->href[strlen($e->href) - 1] !== '/') {
        $e->href .= '/';
      }
    }
    
    $e->href = $this->restore_noise($e->href);
  }

  private function checkImage($e) {
    
  }

  private function checkLink($e) {
    $this->remove_noise("'(<\{(.*?)\}>)'s",$e,'href');
    $e->href = $this->incDir . trim($e->href, '/');
    $e->href = $this->restore_noise($e->href);
  }

  private function checkScript($e) {
    $e->src = $this->incDir . trim($e->src, '/');
  }

  protected function remove_noise($pattern, $e , $attr, $remove_tag = false) {
    $this->noise = array();
    $count = preg_match_all($pattern, $e->$attr, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    for ($i = $count - 1; $i > -1; --$i) {
      $key = '___noise___' . sprintf('% 5d', count($this->noise) + 100);
      
      $idx = ($remove_tag) ? 0 : 1;
      $this->noise[$key] = $matches[$i][$idx][0];
      $e->$attr = substr_replace($e->$attr, $key, $matches[$i][$idx][1], strlen($matches[$i][$idx][0]));
    }

    // reset the length of content
    $this->size = strlen($e->$attr);
    if ($this->size > 0) {
      $this->char = $e->$attr[0];
    }
  }

  function restore_noise($text) {

    while (($pos = strpos($text, '___noise___')) !== false) {
      // Sometimes there is a broken piece of markup, and we don't GET the pos+11 etc... token which indicates a problem outside of us...
      if (strlen($text) > $pos + 15) {
        $key = '___noise___' . $text[$pos + 11] . $text[$pos + 12] . $text[$pos + 13] . $text[$pos + 14] . $text[$pos + 15];

        if (isset($this->noise[$key])) {
          $text = substr($text, 0, $pos) . $this->noise[$key] . substr($text, $pos + 16);
        } else {
          // do this to prevent an infinite loop.
          $text = substr($text, 0, $pos) . 'UNDEFINED NOISE FOR KEY: ' . $key . substr($text, $pos + 16);
        }
      } else {
        // There is no valid key being given back to us... We must get rid of the ___noise___ or we will have a problem.
        $text = substr($text, 0, $pos) . 'NO NUMERIC NOISE KEY' . substr($text, $pos + 11);
      }
    }
    return $text;
  }

  function search_noise($text) {
    foreach ($this->noise as $noiseElement) {
      if (strpos($noiseElement, $text) !== false) {
        return $noiseElement;
      }
    }
  }

}
