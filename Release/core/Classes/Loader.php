<?php

/**
 * Description of Loader
 *
 * @author cycool89
 */
class Loader {

  /**
   *
   * @var aClass
   */
  private static $classes = array();

  /** @var aClass[] */
  private $parent;
  private $incDir = '';

  public function __construct(aClass &$parent, $inc_dir) {
    $this->parent = $parent;
    $this->incDir = $inc_dir;
  }

  public function controller($name) {
    $bootValue = $this->load($name . '_Controller', 'Controllers');
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
    $this->parent->$name = new Proxy(self::$classes[$name . '_Controller']);
    return $bootValue;
  }

  public function model($name) {
    return $this->load($name . '_Model', 'Models');
  }

  public function module($name) {
    $path = Config::getEntry('ModuleDirectory') . $name . DS;
    return $this->load($name, $path, true);
  }

  public function view($name) {
    $view = AE()->getApplication()->getView();
    $tpl = $view->createTemplate(get_class($this->parent->getModule()) . DS . $name . AE_VIEW_EXT, null, null, $view);
    $view->addTemplate(get_class($this->parent->getModule()), $name, $tpl);
  }

  public static function isLoaded($className) {
    return (isset(self::$classes[$className]));
  }

  public static function getLoadedClass($className) {
    return (self::isLoaded($className) ? self::$classes[$className] : null);
  }

  public static function isModuleExists($moduleName) {
    if (file_exists(Config::getEntry('ModuleDirectory') . $moduleName . DS . $moduleName . AE_EXT)) {
      require_once Config::getEntry('ModuleDirectory') . $moduleName . DS . $moduleName . AE_EXT;
      return Config::getEntry('ModuleDirectory') . $moduleName . DS;
    }
    return false;
  }

  public static function isControllerExists($module, $controller) {
    if (file_exists(self::isModuleExists($module) . 'Controllers' . DS . $controller . AE_EXT)) {
      require_once self::isModuleExists($module) . 'Controllers' . DS . $controller . AE_EXT;
      return true;
    }
    return false;
  }

  public static function isMethodExists($module, $controller, $method) {
    $dir = self::isModuleExists($module);
    if (self::isControllerExists($module, $controller)) {
      require_once $dir . 'Controllers' . DS . $controller . AE_EXT;
      return method_exists($controller, $method);
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
    self::$classes[$appName] = new $appName();
    return self::$classes[$appName];
  }

  private function load($name, $path, $absolutePath = false) {
    $namePieces = explode('_', $name);
    $shortName = $name;
    if (count($namePieces) > 1) {
      $shortName = $namePieces[0];
    }
    if (!Loader::isLoaded($name)) {
      if (!$absolutePath) {
        require_once $this->incDir . $path . DS . $name . AE_EXT;
        $path = $this->incDir;
      } else {
        require_once $path . DS . $name . AE_EXT;
      }
      self::$classes[$name] = new $name();
      //View hozzáadása
      $view = AE()->getApplication()->getView();
      self::$classes[$name]->setView($view);
      //Module hozzáadása
      $module = $this->parent->getModule();
      self::$classes[$name]->setModule($module);
      //Loader hozzáadása
      $loader = new Loader(self::$classes[$name], $path);
      self::$classes[$name]->setLoader($loader);

      $this->parent->$shortName = self::$classes[$name];
      self::$classes[$name]->setBootValue(self::$classes[$name]->boot());
    } else {
      $this->parent->$shortName = self::$classes[$name];
    }
    return $this->parent->$shortName->getBootValue();
  }

}
