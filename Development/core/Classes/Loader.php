<?php

namespace core;

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
   * @param \core\aClass $parent
   * @param string $inc_dir
   */
  public function __construct(aClass &$parent, $inc_dir) {
    $this->parent = $parent;
    $this->incDir = $inc_dir;
  }

  /**
   * Controller hozzáadása
   * 
   * Hozzáad a <var>$parent</var> objektumhoz egy <var>$name</var> mezőt,
   * ami ezután a <var>$parent</var> objektumban $this->$name -ként lesz elérhető.
   * A <var>$name</var> osztály egy \core\aController vagy \core\aFromController
   * leszármazottja kell legyen és a modul gyökeréhez viszonyítva a 
   * /Controllers/<var>$name</var>.php helyen kell legyen.
   * 
   * @param string $name
   * @return mixed A <var>$name</var> objektum boot() metódusának visszatérési értéke
   */
  public function controller($name) {
    $bootValue = $this->load($name/* . '_Controller' */, 'Controllers');
    if ($this->parent->$name instanceof aFormController && !is_null(Request::POST(get_class($this->parent->$name)))) {
      $this->parent->$name->setValues(Request::POST(get_class($this->parent->$name)));
      $this->parent->$name->checkValues();
      $errors = $this->parent->$name->getErrors();
      if (empty($errors)) {
        $this->parent->$name->storeData();
      }
      AE()->getApplication()->view->assign('values', $this->parent->$name->getValues());
      AE()->getApplication()->view->assign('errors', $this->parent->$name->getErrors());
    }
    $this->parent->$name = new Proxy(self::$classes[$name/* . '_Controller' */]);
    return $bootValue;
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
   * @return mixed A <var>$name</var> objektum boot() metódusának visszatérési értéke
   */
  public function model($name) {
    return $this->load($name, 'Models');
  }

  /**
   * Almodul hozzáadása
   * 
   * Hozzáad a <var>$parent</var> objektumhoz egy <var>$name</var> mezőt,
   * ami ezután a <var>$parent</var> objektumban $this->$name -ként lesz elérhető.
   * A <var>$name</var> osztály egy \core\aModule
   * leszármazottja kell legyen és a modul gyökeréhez viszonyítva a 
   * /Modules/<var>$name</var>/<var>$name</var>.php helyen kell legyen.
   * 
   * @param string $name
   * @return mixed A <var>$name</var> objektum boot() metódusának visszatérési értéke
   */
  public function submodule($name) {
    return $this->load($name, $this->incDir . 'Modules' . DS . $name . DS, true);
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
   * @return mixed A <var>$name</var> objektum boot() metódusának visszatérési értéke
   */
  public function module($name) {
    $path = Config::getEntry('ModuleDirectory') . $name . DS;
    return $this->load($name, $path, true);
  }

  /**
   * Nézet betöltése
   * 
   * A <var>$name</var> paraméter alapján létrehoz egy Smarty_Internal_template objektumot.
   * A $this->view->getTemplate() metódus visszatérési értékében a
   * következőképp fog szerepelni:
   * 
   * $t = $this->view->getTemplates();
   * 
   * $t[Modulenév][$name];
   * 
   * @param string $name
   * @return \Smarty_Internal_Template
   */
  public function &view($name) {
    $view = AE()->getApplication()->getView();
    $module = getClassName($this->parent->getModule());
    $tpl = $view->createTemplate($module . DS . $name . AE_VIEW_EXT, null, null, $view);
    $view->addTemplate($module, $name, $tpl);
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

    if (file_exists(Config::getEntry('ModuleDirectory') . $moduleName . DS . $moduleName . AE_EXT)) {
      return Config::getEntry('ModuleDirectory') . $moduleName . DS;
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
    $fullName = "\\Controllers\\" . $controller;
    $dir = self::isControllerExists($module, $controller);
    if ($dir) {
      require_once $dir;
      return method_exists($fullName, $method);
    }
    return false;
  }

  public function getIncDir() {
    return $this->incDir;
  }

  public function getParent() {
    return $this->parent;
  }

  public function setParent(aClass &$parent) {
    $this->parent = $parent;
  }

  /** @return aModule */
  public static function loadApplication($appName, $create_on_failure = false) {
    $dir = AE_BASE_DIR . $appName . DS;
    $file = $dir . $appName . AE_EXT;
    if (!file_exists($file) && $create_on_failure) {
      mkdir_r($dir);
      $appFile = file_get_contents(AE_CORE_DIR . 'DefaultFiles' . DS . 'DefaultApplication' . AE_EXT);
      $appFile = str_replace("{{appName}}", $appName, $appFile);
      file_force_contents($file, $appFile);

      mkdir_r($dir . 'Controllers');
      mkdir_r($dir . 'Models');
      if (!is_dir(AE_TEMPLATES . $appName)) {
        mkdir_r(AE_TEMPLATES . $appName);
        file_force_contents(AE_TEMPLATES . $appName . DS . 'index' . AE_VIEW_EXT, "Hello World!");
      }
    } elseif (!file_exists($file)) {
      return false; //Nem található alkalmazás
    }
    require_once $file;
    $fullName = "\\Modules\\" . $appName;
    self::$classes[$appName] = new $fullName();
    return self::$classes[$appName];
  }

  private function load($name, $path, $absolutePath = false) {
    if (!Loader::isLoaded($name)) {
      if (!$absolutePath) {
        require_once $this->incDir . $path . DS . $name . AE_EXT;
        $fullName = "$path\\$name";
        $path = $this->incDir;
      } else {
        require_once $path . DS . $name . AE_EXT;
        $temp = trim($path, DS);
        $parts = explode(DS, $temp);
        array_pop($parts);
        $fullName = end($parts) . '\\' . $name;
      }
      self::$classes[$name] = new $fullName();
      if (!(self::$classes[$name] instanceof \core\aModel)) {
        //View hozzáadása
        $view = AE()->getApplication()->getView();
        self::$classes[$name]->setView($view);
        //Module hozzáadása
        $module = $this->parent->getModule();
        self::$classes[$name]->setModule($module);
        //Loader hozzáadása
        $loader = new Loader(self::$classes[$name], $path);
        self::$classes[$name]->setLoader($loader);
      }
      $this->parent->$name = self::$classes[$name];
      self::$classes[$name]->setBootValue(self::$classes[$name]->boot());
    } else {
      $this->parent->$name = self::$classes[$name];
    }
    return $this->parent->$name->getBootValue();
  }

}
