<?php

/**
 * Description of AraneaEngine
 * 
 * @author cycool89
 */
class AraneaEngine extends aSingleton {

  const VERSION = "0.0.0";

  private $plugins = array(
      'Smarty' => 'Smarty.class.php',
      'SimpleHtmlDom' => 'simple_html_dom.php',
      'KintMaster' => 'Kint.class.php',
      'Inflector' => 'Inflector.php',
      'PHPMailer' => 'class.phpmailer.php'
  );

  /** @var aModule */
  private $application = null;

  public function __construct() {
    require_once AE_BASE_DIR . 'config' . DS . 'dbconfig' . AE_EXT;
    require_once AE_BASE_DIR . 'config' . DS . 'config' . AE_EXT;
    URL::initURL();
    Request::setMVC();
  }

  public function start() {
    date_default_timezone_set(Config::getEntry('Timezone'));
    $this->loadPlugin('Smarty');
    $this->loadPlugin('SimpleHtmlDom');
    $this->loadPlugin('KintMaster');
    $this->loadPlugin('Inflector');
    if (AE_USE_DB) {
      $this->getDatabase()->connect(AE_DBHOST, AE_DBUSER, AE_DBPASS, AE_DBNAME);
    }
    $this->application = Loader::loadApplication(Config::getEntry('Application'), true);
    $view = new View();
    $this->application->setView($view);
    $this->application->setModule($this->application);
    $loader = new Loader($this->application, AE_BASE_DIR . Config::getEntry('Application') . DS);
    $this->application->setLoader($loader);
    $this->application->index();

    $this->application->render();
  }

  /** @return iDatabase */
  public function &getDatabase() {
    $ret = null;
    if (AE_USE_DB) {
      switch (Config::getEntry('DatabaseEngine')) {
        case 'mysqli':
          $ret = mysqliDatabase::getInstance();
          break;
      }
    }
    return $ret;
  }

  /**
   * 
   * @return aModule
   */
  public function &getApplication() {
    return $this->application;
  }

  public function loadPlugin($plugin) {
    require_once AE_CORE_DIR . 'Plugins' . DS . $plugin . DS . $this->plugins[$plugin];
  }

}

/**
 * 
 * @return AraneaEngine
 */
function AE() {
  return AraneaEngine::getInstance();
}
