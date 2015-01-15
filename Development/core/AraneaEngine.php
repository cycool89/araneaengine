<?php
namespace aecore;
/**
 * AraneaEngine osztály
 * 
 * A rendszer főosztálya. Később elérhető
 * \aecore\AE() fv-ként. Lefuttatja az alkalmazásmodul
 * boot(), index(), render() metódusát ebben a sorrendben.
 * 
 * @author Kigyós János <cycool89@gmail.com>
 */
class AraneaEngine extends ASingleton {

  const VERSION = "0.1.1";
  private $startTime = 0;
  private $startMem = 0;

  /**
   * Rendszer pluginok a core/Plugins könyvtárhoz viszonyítva.
   * Formátum
   * 
   * array ( <b>key</b> => <b>value</b> )
   * core/Plugins/<b>key</b>/<b>value</b>
   * 
   * @var array
   */
  private $plugins = array(
      'Smarty' => 'Smarty.class.php',
      'SimpleHtmlDom' => 'simple_html_dom.php',
      'KintMaster' => 'Kint.class.php',
      'Inflector' => 'Inflector.php',
      'PHPMailer' => 'class.phpmailer.php',
      'WideImage' => 'WideImage.php',
      'Neon' => 'neon.php'
  );

  /** @var aModule */
  private $application = null;

  /**
   * AraneaEngine konstruktor
   * 
   * Elindítja a kimenet buffer-t
   * Rögzíti az aktuális időt, memória használatot.
   * Beolvassa a config/config.php és config/dbconfig.php fájlokat.
   * Inicializálja az URL és Request osztályt
   */
  protected function __construct() {
    ob_start();
    $this->startTime = microtime(true);
    $this->startMem = getMemoryUsage(true, false);
    require_once AE_BASE_DIR . 'config' . DS . 'dbconfig' . AE_EXT;
    require_once AE_BASE_DIR . 'config' . DS . 'config' . AE_EXT;
    URL::initURL();
    Request::setMVC();
  }

  /**
   * Időzóna, alap Pluginek, adatbázis beállítása.
   * Betölti a config-ban alapértelmezett alkalmazásként megjelölt modult-t
   * ami a /Alkalmazásnév/ helyen található. Ha nem létezik létrehoz egy alap alkalmazást.
   * 
   * Beállíta a nézetet (Smarty), Loader-t.
   * Lefuttatja az alkalmazás modul boot(), index(), render() metódusát.
   */
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
    $view->clearAllCache(3600);
    $view->clearCompiledTemplate(null,null,3600);
    $view->assign('AE_VERSION',  self::VERSION);
    $this->application->setView($view);
    $this->application->setModule($this->application);
    $loader = new Loader($this->application, AE_BASE_DIR . Config::getEntry('Application') . DS);
    $this->application->setLoader($loader);
    $this->application->setBootValue($this->application->boot());
    $this->application->index();

    $this->application->render();
  }

  /**
   * Visszaadja az aktuális élő adatbázis kapcsolatot.
   * Használja az IDatabase interface-t.
   * 
   * Ha nincs élő kapcsolat (pl. dbconfig-ban AE_USE_DB = false)
   * , akkor null értéket ad.
   *  
   * @return IDatabase
   *  */
  public function &getDatabase() {
    $ret = null;
    if (AE_USE_DB) {
      switch (Config::getEntry('DatabaseEngine')) {
        case 'mysqli':
          $ret = MysqliDatabase::getInstance();
          break;
      }
    }
    return $ret;
  }

  /**
   * Visszaadja az alkalmazás modul-t.
   * 
   * @return aModule
   */
  public function &getApplication() {
    return $this->application;
  }

  /**
   * Betölt egy rendszerplugin-t.
   * 
   * @param string $plugin Az osztály <var>$plugins</var> változójának egy kulcsa.
   */
  public function loadPlugin($plugin) {
    require_once AE_CORE_DIR . 'Plugins' . DS . $plugin . DS . $this->plugins[$plugin];
  }
  
  /**
   * Megadja, hogy a program elindításától a metódus meghívásáig mennyi idő telt el.
   * pl.: 0.0515
   * 
   * @return double
   */
  public function elapsedTime() {
    $endTime = microtime(true);
    $eT = $endTime - $this->startTime;
    
    return round($eT,4);
  }
  
  /**
   * Megadja, hogy a program elindításától a metódus meghívásáig
   * mennyi memóriát használtunk el szövegesen.
   * pl.: 3.75 Mb
   * 
   * @return double
   */
  public function usedMemory() {
    $endMem = getMemoryUsage(true, false);
    $uM = $endMem - $this->startMem;
    
    return bitToText($uM);
  }

}

/**
 * 
 * @return AraneaEngine
 */
function AE() {
  return AraneaEngine::getInstance();
}
