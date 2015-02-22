<?php

namespace aecore;

/**
 * Description of View_Internal_Template
 *
 * @author cycool89
 */
class View_Internal_Template {

  /** @var \Smarty_Internal_Template */
  private $internal;
  private $incDir;

  public function __construct(&$internal, $incDir) {
    $this->internal = $internal;
    $this->incDir = $incDir;
  }

  public function __call($name, $arguments) {
    if (method_exists($this->internal, $name)) {
      return call_user_func_array(array($this->internal, $name), $arguments);
    }
  }

  public function &getDOM() {
    $html = $this->internal->fetch();
    $dom_object = str_get_html($html);
    $dom = $this->_globalizeHTML($dom_object);
    return $dom;
  }

  public function fetch() {
    return $this->getDOM()->save();
  }

  public function display() {
    echo $this->getDOM()->save();
  }

  private function &_globalizeHTML(\simple_html_dom $dom) {
    foreach ($dom->find("*[src]") as $e) {
      if (!isset($e->araneaengine)) {
        $this->checkSrc($e);
        $e->araneaengine = "processed";
      }
    }

    foreach ($dom->find("*[href]") as $e) {
      if (!isset($e->araneaengine)) {
        $this->checkHref($e);
        $e->araneaengine = "processed";
      }
    }

    foreach ($dom->find("form[!araneaengine]") as $e) {
      $this->checkForm($e);
      $e->araneaengine = "processed";
    }

    if ($dom->find('html') !== array()) {
      foreach ($dom->find('*[araneaengine]') as $e) {
        unset($e->araneaengine);
      }
    }
    return $dom;
  }

  private function checkHref($e) {
    switch ($e->tag){
      case 'a':
        $this->checkAnchor($e, 'href');
        break;
      case 'link':
      default:
        $this->checkLink($e);
        break;
    }
  }

  private function checkAnchor($e, $attr = 'href') {
    $this->remove_noise("'(<\{(.*?)\}>)'s", $e, $attr);

    $href = (isset($e->$attr)) ? trim($e->$attr, '/') : '';
    $hrefPieces = explode('/', $href);
    $hrefPieces = ($hrefPieces[0] == '') ? array() : $hrefPieces;
    if (strpos($href, ':') !== false) {
//Ha van benn ':' ne csinálj semmit. 'http:' 'Module:'
      $e->$attr = $href;
    } elseif (strpos($href, '.') !== false) {
      $e->$attr = $this->incDir . $href;
    } else {
//Ha nincs benn ':'
//Appreq van-e és van-e benn
      if (Request::isAppReq() && array_search(Config::getEntry('AppReqWord'), $hrefPieces) === false) {
        array_unshift($hrefPieces, Config::getEntry('AppReqWord'));
      }
//Multilanguage van-e és van-e benn
      if (Config::getEntry('Multilanguage')) {
        $foundLanguage = false;
        $i = 0;
        while ($i < count($hrefPieces) && $foundLanguage === false) {
          $foundLanguage = Config::isLanguage($hrefPieces[$i]);
          $i++;
        }
        if ($foundLanguage === false) {
          $hrefPieces = array_merge(array(Session::get('lang', 'code')), $hrefPieces);
        } else {
          $temp = $hrefPieces[$i - 1];
          unset($hrefPieces[$i - 1]); //Közbülső helyről eltűntet
          $hrefPieces = array_values($hrefPieces); //Tömb újraszámozása
          $hrefPieces = array_merge(array($temp), $hrefPieces); //Tömb eléfűzése
        }
      }
      $modrewrite = (AE_MOD_REWRITE) ? '' : 'index.php/';
      $e->$attr = AE_BASE_PATH . $modrewrite . implode('/', $hrefPieces);
      if (strpos($e->$attr, '?') === false) {
        $e->$attr = rtrim($e->$attr, '/');
        $e->$attr .= '/';
      }
    }

    $e->$attr = $this->restore_noise($e->$attr);
  }

  private function checkSrc($e) {
    if (strpos($e->src, ':') === false && isset($e->src)) {
      $e->src = $this->incDir . trim($e->src, '/');
    }
  }

  private function checkLink($e) {
    $this->remove_noise("'(<\{(.*?)\}>)'s", $e, 'href');
    if (strpos($e->href, ':') === false) {
      $e->href = $this->incDir . trim($e->href, '/');
    }
    $e->href = $this->restore_noise($e->href);
  }

  public function checkForm($e) {
    $this->checkAnchor($e, 'action');
  }

  protected function remove_noise($pattern, $e, $attr, $remove_tag = false) {
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
