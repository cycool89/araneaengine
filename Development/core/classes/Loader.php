<?php

namespace aecore;

/**
 * Description of Loader
 *
 * @author cycool89
 */
class Loader {

  /**
   * Betöltött objektumokat tartalmazó tömb.
   * 
   * @var aClass
   */
  private static $classes = array();
  private static $prixifiedClasses = array();

  /**
   * Az objektum ami tartalmazza a Loader osztály
   * ezen példányát.
   * 
   * @var aClass 
   */
  private $parent;

  /**
   * A modul gyökérkönyvtára ahonnan betölti a modulhoz tartozó osztályokat.
   * 
   * @var string 
   */
  private $incDir = '';

  /**
   * Loader konstruktora
   * 
   * A <var>$parent</var> paraméterben azt az objektumot várja,
   * amiben példűnyosítva lett. Az <var>$inc_dir</var> paraméterben pedig
   * a modul gyökérkönyvtárát.
   * 
   * @param aClass $parent
   * @param string $inc_dir
   */
  public function __construct(aClass &$parent, $inc_dir) {
    $this->parent = $parent;
    $this->incDir = $inc_dir;
  }

  /**
   * PHP varászmetódus
   * 
   * Segítségével példányosíthatunk külső osztályokat.
   * 
   * A külső osztályt a következő helyre kell tenni:
   * 
   * /modulkönyvtár/<var>$name</var>/osztály.php
   * 
   * Példa: Alkalmazás modulból kiadva:
   * $this->load->helper('html');
   * /Alkalmazás/Helpers/html.php => 'class html {}'
   * 
   * Ha az osztály konstruktora paramétert vár akkor soroljuk fel az osztály neve után.
   * Pl.: $this->load->handler('css',[$param1[,$param2...]]);
   * 
   * (Vegyük észre hogy a könyvtár a metódusnév nagykezdőbetűs, többesszámú formája:
   * $this->load->handler(...) => /Handlers/...
   * 
   * @param string $name
   * @param array $arguments
   * @return mixed
   */
  public function &__call($name, $arguments) {
    $className = array_shift($arguments);
    $path = ucfirst(AEstr()->pluralize($name));
    $rModule = new \ReflectionClass($this->parent->getModule());
    $namespace = "\\" . $rModule->getNamespaceName() . "\\" . strtolower(str_replace(DS, "\\", $path)) . "\\";
    $fullName = $namespace . $className;
    if (file_exists($this->incDir . $path . DS . $className . AE_EXT)) {
      require_once $this->incDir . $path . DS . $className . AE_EXT;
    } else {
      Log::write("Nincs ilyen " . $name . ": " . $className, true, true, 2);
    }
    if (method_exists($className, '__construct')) {
      $rc = new \ReflectionClass($className);
      $this->parent->$className = $rc->newInstanceArgs($arguments);
    } else {
      $this->parent->$className = new $className();
    }
    return $this->parent->$className;
  }

  /**
   * Controller hozzáadása
   * 
   * Hozzáad a <var>$parent</var> objektumhoz egy <var>$name</var> mezőt,
   * ami ezután a <var>$parent</var> objektumban $this->$name -ként lesz elérhető.
   * A <var>$name</var> osztály egy AController vagy AFromController
   * leszármazottja kell legyen és a modul gyökeréhez viszonyítva a 
   * /Controllers/<var>$name</var>.php helyen kell legyen.
   * 
   * POST adatok esetén, és ha a POST adatok tartalmaznak adatokat
   * a betöltött AFormController részére, akkor lefuttatja a
   * checkValues() metódust. Ha ezek után az $errors tömbb üres,
   * akkor végrehajtja a storeData() metódust.
   * 
   * @param string $name
   * @return AController Egy Proxy osztály ami tartalmazza a <var>$name</var> controller-t
   */
  public function &controller($name) {
    $fullName = $this->load($name, 'Controllers');
    if ($this->parent->$name instanceof aFormController && !is_null(Request::POST(get_class($this->parent->$name)))) {
      $this->parent->$name->setValues(Request::POST(get_class($this->parent->$name)));
      $this->parent->$name->checkValues();
      $errors = $this->parent->$name->getErrors();
      if (empty($errors)) {
        $this->parent->$name->storeData();
      }
      $this->parent->$name->getView()->assign('values', $this->parent->$name->getValues());
      $this->parent->$name->getView()->assign('errors', $this->parent->$name->getErrors());
    }
    if (!isset(self::$prixifiedClasses[$fullName])) {
      self::$prixifiedClasses[$fullName] = new Proxy(self::$classes[$fullName]);
    }
    $this->parent->$name = & self::$prixifiedClasses[$fullName];
    return $this->parent->$name;
  }

  /**
   * Model hozzáadása
   * 
   * Hozzáad a <var>$parent</var> objektumhoz egy <var>$name</var> mezőt,
   * ami ezután a <var>$parent</var> objektumban $this->$name -ként lesz elérhető.
   * A <var>$name</var> osztály egy \core\aModel
   * leszármazottja kell legyen és a modul gyökeréhez viszonyítva a 
   * /Models/<var>$name</var>.php helyen kell legyen.
   * 
   * @param string $name
   * @return AModel A <var>$name</var> model
   */
  public function &model($name) {
    $fullName = $this->load($name, 'Models');
    return $this->parent->$name;
  }

  /**
   * Almodul hozzáadása
   * 
   * Hozzáad a <var>$parent</var> objektumhoz egy <var>$name</var> mezőt,
   * ami ezután a <var>$parent</var> objektumban $this->$name -ként lesz elérhető.
   * A <var>$name</var> osztály egy \core\aModule
   * leszármazottja kell legyen és a modul gyökeréhez viszonyítva a 
   * /Submodules/<var>$name</var>/<var>$name</var>.php helyen kell legyen.
   * 
   * @param string $name
   * @return AModule A <var>$name</var> almodul
   */
  public function &submodule($name) {
    $fullName = $this->load($name, $this->incDir . 'Submodules' . DS . $name, true);
    return $this->parent->$name;
  }

  /**
   * Modul hozzáadása
   * 
   * Hozzáad a <var>$parent</var> objektumhoz egy <var>$name</var> mezőt,
   * ami ezután a <var>$parent</var> objektumban $this->$name -ként lesz elérhető.
   * A <var>$name</var> osztály egy \core\aModule
   * leszármazottja kell legyen és az alkalmazás gyökeréhez viszonyítva a 
   * /Modules/<var>$name</var>/<var>$name</var>.php helyen kell legyen.
   * 
   * @param string $name
   * @return AModule A <var>$name</var> modul
   */
  public function &module($name) {
    $path = Config::getEntry('ModuleDirectory') . $name . DS;
    $fullName = $this->load($name, $path, true);
    return $this->parent->$name;
  }

  /**
   * Nézet betöltése
   * 
   * A <var>$name</var> paraméter alapján létrehoz egy Smarty_Internal_template objektumot.
   * Ha <var>$alias</var> paraméter nem üres, akkor később ezen a néven lesz elérhető.
   * A $this->view->getTemplate() metódus visszatérési értékében a
   * következőképp fog szerepelni:
   * 
   * $t = $this->view->getTemplates();
   * 
   * $t[Modulenév][$name];
   * vagy
   * $t[Modulenév][$alias];
   * 
   * @param string $name
   * @param string $alias
   * @return \Smarty_Internal_Template
   */
  public function &view($name, $alias = '') {
    $view = $this->parent->getView();
    $shortName = getClassName($this->parent->getModule());
    $alias = ($alias == '' || !is_string($alias)) ? $name : $alias;
    $tpl = $view->createTemplate($shortName . DS . $name . AE_VIEW_EXT, null, null, $view);
    $view->addTemplate(get_class($this->parent->getModule()), $alias, $tpl);
    return $tpl;
  }

  /**
   * Megadja, hogy a <var>$className</var> osztály példányosítva lett-e.
   * 
   * @param string $className
   * @return boolean
   */
  public static function isLoaded($className) {
    return (isset(self::$classes[$className]));
  }

  /**
   * Visszaadja a <var>$className</var> objektumot, ha be volt töltve.
   * Egyébként null
   * 
   * @param string $className
   * @return mixed
   */
  public static function getLoadedClass($className) {
    return (self::isLoaded($className) ? self::$classes[$className] : null);
  }

  /**
   * Megadja, hogy a <var>$moduleName</var> modul létezik-e.
   * 
   * @param string $moduleName
   * @return mixed A module elérési útvonala, egyébként false
   */
  public static function isModuleExists($moduleName) {
    $path = DS . trim(Config::getEntry('ModuleDirectory'), DS) . DS;
    if (file_exists($path . $moduleName . DS . $moduleName . AE_EXT)) {
      return $path . $moduleName . DS;
    }
    return false;
  }

  /**
   * Megadja, hogy a <var>$module</var> modul <var>$controller</var> controllere létezik-e.
   * 
   * @param string $module
   * @param string $controller
   * @return mixed A controller elérési útvonala, egyébként false
   */
  public static function isControllerExists($module, $controller) {
    $dir = self::isModuleExists($module);
    if (file_exists($dir . 'Controllers' . DS . $controller . AE_EXT)) {
      return $dir . 'Controllers' . DS . $controller . AE_EXT;
    }
    return false;
  }

  /**
   * Megadja, hogy a <var>$module</var> modul <var>$controller</var> controllerének
   * <var>$method</var> metódusa létezik-e.
   * 
   * @param string $module
   * @param string $controller
   * @param string $method
   * @return boolean
   */
  public static function isMethodExists($module, $controller, $method) {
    $namespace = "\\application\\modules\\" . strtolower($module) . "\\controllers\\";
    $fullName = $namespace . $controller;
    $dir = self::isControllerExists($module, $controller);
    if ($dir) {
      require_once $dir;
      return method_exists($fullName, $method);
    }
    return false;
  }

  /**
   * Visszaadja a Loader-t tartalmazó modul gyökerének helyét.
   * 
   * @return string
   */
  public function getIncDir() {
    return $this->incDir;
  }

  /**
   * Visszaadja az objektumot amiben a Loader példányosítva lett.
   * 
   * @return AClass
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Beállítja az objektumot amiben példányosítva lett a Loader.
   * 
   * @param AClass $parent
   */
  public function setParent(AClass &$parent) {
    $this->parent = $parent;
  }

  /**
   * Betölti az <var>$appName</var> alkalmazásmodul-t.
   * <var>$create_on_failure</var> true esetén létrehozza, az index.php-vel
   * egy szinten.
   *  
   * @param string $appName alkalmazásmodul neve
   * @param boolean $create_on_failure
   * @return aModule
   */
  public static function loadApplication($appName, $create_on_failure = false) {
    $dir = AE_BASE_DIR . $appName . DS;
    $file = $dir . $appName . AE_EXT;
    if (!file_exists($file) && $create_on_failure) {
      $appFile = file_get_contents(AE_CORE_DIR . 'DefaultFiles' . DS . 'DefaultApplication' . AE_EXT);
      $appFile = str_replace("{{appName}}", $appName, $appFile);
      file_force_contents($file, $appFile);

      mkdir_r($dir . 'Controllers');
      mkdir_r($dir . 'Models');
      if (!is_dir(AE_TEMPLATES . $appName)) {
        file_force_contents(AE_TEMPLATES . $appName . DS . 'index' . AE_VIEW_EXT, "Hello World!");
      }
    } elseif (!file_exists($file)) {
      return false; //Nem található alkalmazás
    }
    require_once $file;
    $fullName = "\\application\\" . $appName;
    self::$classes[$appName] = new $fullName();
    return self::$classes[$appName];
  }

  /**
   * Betölti a <var>$name</var> osztályt.
   *
   * Ha nem model, akkor
   * Hozzáadja egy saját Loader-t, View-t és beállítja a modulját.
   * 
   * Lefuttatja az osztály boot() metódusát, ha még nem volt betöltve előzőleg.
   * 
   * @param string $name
   * @param string $path
   * @param boolean $absolutePath
   * @return string A betöltött osztály Fully-qualified neve
   */
  private function load($name, $path, $absolutePath = false) {
    $rModule = new \ReflectionClass($this->parent->getModule());
    $newIncDir = trim(dirname($rModule->getFileName()), DS);
    $fullName = '';
    if (!$absolutePath) {

      $namespace = "\\" . $rModule->getNamespaceName() . "\\" . strtolower(str_replace(DS, "\\", $path)) . "\\";
      $fullName = $namespace . $name;

      $path = $newIncDir . DS . trim($path, DS);
      if (file_exists(DS . $path . DS . $name . AE_EXT)) {
        require_once DS . $path . DS . $name . AE_EXT;
      } else {
        Log::write("Nincs ilyen osztály: " . $fullName, true, true, 2);
      }
    } else {
      $path = trim($path, DS);
      $newIncDir = $path;
      $namespace = strtolower(basename(dirname($path)));
      $namespace = "\\application\\" . $namespace . "\\" . strtolower($name) . '\\';
      $fullName = $namespace . $name;
      if (file_exists(DS . $path . DS . $name . AE_EXT)) {
        require_once DS . $path . DS . $name . AE_EXT;
      } else {
        Log::write("Nincs ilyen osztály: " . $fullName, true, true, 2);
      }
    }
    if (!Loader::isLoaded($fullName)) {
      self::$classes[$fullName] = new $fullName();
      if (!(self::$classes[$fullName] instanceof \aecore\aModel)) {
        //View hozzáadása
        $view = new View();//AE()->getApplication()->getView();
        self::$classes[$fullName]->setView($view);
        //Module hozzáadása
        $module = $this->parent->getModule();
        self::$classes[$fullName]->setModule($module);
        //Loader hozzáadása
        $loader = new Loader(self::$classes[$fullName], DS . $newIncDir . DS);
        self::$classes[$fullName]->setLoader($loader);
      }
      self::$classes[$fullName]->setBootValue(self::$classes[$fullName]->boot());
    }

    $this->parent->$name = & self::$classes[$fullName];

    return $fullName;
  }

}
