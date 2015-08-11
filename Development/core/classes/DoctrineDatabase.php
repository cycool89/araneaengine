<?php

namespace aecore;

/**
 * Description of DoctrineDatabase
 *
 * @author cycool89
 */
class DoctrineDatabase extends \aecore\ASingleton {

  private $classLoader = null;
  private $config = array();
  private $conn = array();
  private $driver = 'pdo_mysql';
  private $prefix = '';

  public function connect($host, $username, $password, $dbname) {

    require \AE_CORE_DIR . 'Plugins' . DS . 'Doctrine' . DS . 'Common' . DS . 'ClassLoader.php';

    $this->classLoader = new \Doctrine\Common\ClassLoader('Doctrine', \AE_CORE_DIR . 'Plugins');
    $this->classLoader->register();

    $this->config = new \Doctrine\DBAL\Configuration();

    $connectionParams = $this->makeConnectionParams(func_get_args());

    $this->conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $this->config);

    $dbname = "`" . str_replace("`", "``", $dbname) . "`";

    switch ($this->driver) {
      case 'pdo_mysqli':
      case 'pdo_mysql':
        /*$this->conn->query(sprintf('SET GLOBAL storage_engine = MyISAM;'))->execute();
        $this->conn->query(sprintf('SET SESSION storage_engine = MyISAM;'))->execute();*/
        $this->conn->query("CREATE DATABASE IF NOT EXISTS $dbname DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci")->execute();
        $this->conn->query("use $dbname")->execute();
        break;
      case 'pdo_sqlite':
        break;
      default:
        break;
    }
  }

  private function makeConnectionParams($p) {
    $ret = array();
    switch ($this->driver) {
      case 'pdo_mysql':
        $ret = array(
          'dbname' => null,
          'user' => $p[1],
          'password' => $p[2],
          'host' => $p[0],
          'driver' => $this->driver,
        );
        break;
      case 'pdo_sqlite':
        $ret = array(
          'memory' => false,
          'path' => $p[3] . '.db',
          'user' => $p[1],
          'password' => $p[2],
          'host' => $p[0],
          'driver' => $this->driver,
        );
        break;
      default:

        break;
    }
    return $ret;
  }

  function getDriver() {
    return $this->driver;
  }

  function setDriver($driver) {
    $this->driver = $driver;
  }

  /**
   * 
   * @return \Doctrine\DBAL\Connection
   */
  function getConnection() {
    return $this->conn;
  }

  protected function __construct() {
    
  }

  public function execute($query) {
    return $this->conn->executeQuery($query);
  }

}
