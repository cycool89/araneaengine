<?php
namespace core;
/**
 * AraneaEngine MySqli osztály
 * 
 * 2014.02.02
 * @author Kigyós János <cycool89@gmail.com>
 */
class mysqliDatabase extends aSingleton implements iDatabase {

  public $_mysql;
  protected $_type = '';
  protected $_where = array();
  protected $_data = array();
  protected $_orderBy = array();
  protected $_query;
  protected $_limit = false;
  protected $_offset = false;
  protected $_paramTypeList;

  public function connect($host, $username, $password, $dbname) {
    $this->_mysql = new \mysqli($host, $username, $password) or die('Probléma az adatbázis szerver csatlakozáskor!');
    if (!$this->_mysql->select_db($dbname)) {
      $this->_mysql->query(sprintf('SET GLOBAL storage_engine = MyISAM;'));
      $this->_mysql->query(sprintf('SET SESSION storage_engine = MyISAM;'));
      $this->_mysql->query(sprintf('CREATE DATABASE IF NOT EXISTS %s CHARACTER SET utf8 COLLATE utf8_general_ci', $dbname));
      if (!$this->_mysql->select_db($dbname)) {
        Log::write('Nem tudtam létrehozni az adatbázist', true);
      }
    }
  }

  protected function _reset() {
    $this->_type = '';
    $this->_where = array();
    $this->_data = array();
    $this->_orderBy = array();
    $this->_query = '';
    $this->_limit = false;
    $this->_offset = false;
    $this->_paramTypeList;
  }

  /**
   * Közvetlen sql(<var>$query</var>) lekérdezés
   * Visszatérési érték mysqli objektum
   * 
   * @param type string $query
   * @return type mixed
   */
  function execute($query) {
    return $this->_mysql->query($query);
  }

  /**
   * SQL lekérdezés opcionális limit megadással.
   * A fv az előzőleg meghívott fv-ekkel együtt dolgozva hozzáfűzi a <var>$query</var> -hez a lekérés többi részét.
   * Visszatérési érték mysqli objektum
   * 
   * @param string $query
   * @param integer $offset
   * @param integer $limit
   * @return mixed
   */
  function query($query, $offset = false, $limit = false) {
    if ($limit !== false && $offset !== false) {
      $this->_limit = $limit;
      $this->_offset = $offset;
    }
    $this->_query = $query;
    $this->_buildQuery();
    $q = $this->_query;
    //echo 'qqqq:'.$q;
    $this->_reset();
    //echo $q;
    return $this->_mysql->query($q);
  }

  /**
   * Azonos a query fv-el
   * Visszatérési érték a lekérdezés első sor, első oszlopának értéke
   * vagy false sikertelenség esetén
   * 
   * @param string $query
   * @return mixed
   */
  function getOne($query) {
    if (is_string($query)) {
      $res = $this->query($query);
    } else {
      $res = $query;
    }
    if ($this->_mysql->affected_rows > 0) {
      $row = $res->fetch_row();
      $item = $row[0];

      return $item;
    }
    return false;
  }

  /**
   * Azonos a query fv-el
   * Visszatérési érték a lekérdezés első sora asszociatív tömbben
   * vagy false sikertelenség esetén
   * 
   * @param string $query
   * @return mixed
   */
  function getRow($query) {
    if (is_string($query)) {
      $res = $this->query($query);
    } else {
      $res = $query;
    }
    if ($this->_mysql->affected_rows > 0) {
      $row = $res->fetch_assoc();
      $item = $row;

      return $item;
    }
    return false;
  }

  /**
   * Azonos a query fv-el
   * Visszatérési értéke az eredmény halmaz sorszámotott tömbben
   * vagy false sikertelenség esetén
   * 
   * @param string $query
   * @return mixed
   */
  function getArray($query) {
    if (is_string($query)) {
      $res = $this->query($query);
    } else {
      $res = $query;
    }
    $items = array();
    if ($this->_mysql->affected_rows > 0) {
      while ($row = $res->fetch_assoc()) {
        $items[] = $row;
      }
    }
    return $items;
  }

  /**
   * Azonos a query fv-el
   * Visszatérési értéke az eredmény halmaz minden sor, első oszlopának értéke sorszámotott tömbben
   * vagy false sikertelenség esetén
   * 
   * @param string $query
   * @return mixed
   */
  function getArrayOne($query) {
    if (is_string($query)) {
      $res = $this->query($query);
    } else {
      $res = $query;
    }
    if ($this->_mysql->affected_rows > 0) {
      $items = array();
      while ($row = $res->fetch_array()) {
        $items[] = $row[0];
      }
      return $items;
    }
    return false;
  }

  /**
   * Azonos a query fv-el
   * Visszatérési értéke az eredmény halmaz <var>$key</var> indexű tömbben
   * vagy false sikertelenség esetén
   * 
   * @param string $query
   * @param string $key
   * @return mixed
   */
  function getKeyedArray($query, $key) {
    if (is_string($query)) {
      $res = $this->query($query);
    } else {
      $res = $query;
    }
    if ($this->_mysql->affected_rows > 0) {
      $items = array();
      while ($row = $res->fetch_assoc()) {
        $items[$row[$key]] = $row;
      }
      return $items;
    }
    return false;
  }

  /**
   * Előkészítő fv, amivel a későbbi lekéréshez lehet beállítani limitet.
   * 
   * @param integer $offset
   * @param integer $limit
   */
  function addLimit($offset = false, $limit = false) {
    if ($limit !== false && $offset !== false) {
      $this->_limit = $limit;
      $this->_offset = $offset;
    }
  }

  /**
   * Előkészítő fv, amivel a későbbi lekéréshez lehet beállítani rendezést.
   * <var>$field</var> a mező neve ami szerint rendezünk.
   * [<var>$mode</var> = ASC,DESC növekvő, csökkenő sorrend.]
   * Többször meghívva lehet több mező szerint rendezni.
   * 
   * @param string $field
   */
  function orderBy($field, $mode = 'ASC') {
    if (is_string($field) && $field != '') {
      $this->_orderBy[] = $field . ' ' . $mode;
    }
  }

  /**
   * Oszlop (<var>$field</var>) hozzáadása <var>$table</var> táblához
   * <var>$props</var> tulajdonságokkal
   * 
   * <var>$props</var> szintaxisa megegyezik a MYSQL szintaxissal pl.:
   *    'INT(11)'
   * NOT NULL tulajdonság alapértelmezetten hozzáadódik.
   * 
   * Visszatérési érték siker esetén true, egyébként false
   * 
   * @param string $table
   * @param string $field
   * @param string $props
   * @return boolean
   */
  function addColumn($table, $field, $props) {
    $sql = sprintf('ALTER TABLE %s ADD COLUMN %s %s NOT NULL', $table, $field, $props);
    return $this->execute($sql);
  }

  /**
   * Oszlop (<var>$field</var>) törlése <var>$table</var> táblából
   * 
   * Visszatérési érték siker esetén true, egyébként false
   * 
   * @param string $table
   * @param string $field
   * @return boolean
   */
  function dropColumn($table, $field) {
    $sql = sprintf('ALTER TABLE %s DROP COLUMN %s', $table, $field);
    return $this->execute($sql);
  }

  /**
   * Oszlop (<var>$field</var>) módosítása <var>$table</var> táblában
   * <var>$props</var> tulajdonságokra
   * 
   * <var>$props</var> szintaxisa megegyezik a MYSQL szintaxissal pl.:
   *    'INT(11)'
   * NOT NULL tulajdonság alapértelmezetten hozzáadódik.
   * 
   * Visszatérési érték siker esetén true, egyébként false
   * 
   * @param string $table
   * @param string $field
   * @param string $props
   * @return boolean
   */
  function modifyColumn($table, $field, $props) {
    $sql = sprintf('ALTER TABLE %s MODIFY COLUMN %s %s NOT NULL', $table, $field, $props);
    return $this->execute($sql);
  }

  /**
   * Befejező utasítás
   * SQL SELECT utasítást állít össze és futtat le az előkészítő fv-ekkel összedolgozva.
   * A lekérdezés <var>$tableName</var> táblából
   * <var>$fields</var> mezőket kérdezi le, ahol
   * <var>$fields</var> lehet string illetve az oszlopneveket tartalmazó tömb.
   * 
   * @param mixed $fields
   * @param string $tableName
   * @return MySQL result Object
   */
  public function select($fields, $tableName) {
    if (is_array($fields)) {
      $fields = implode(',', $fields);
    }

    $query = sprintf('SELECT %s FROM %s', $fields, $tableName);

    return $this->query($query);
  }

  /**
   * Befejező utasítás
   * SQL INSERT utasítást állít össze és futtat le az előkészítő fv-ekkel összedolgozva.
   * A lekérdezés <var>$tableName</var> táblába
   * <var>$insertData</var> [és <var>$insertData2</var>] adatokat szúrja be, ahol
   * <var>$insertData</var> legyen mezőnév(kulcs) => érték párosításban összeállított tömb
   *  vagy
   * <var>$insertData</var>  legyen mezőneveket tartalmazó sorszámozott tömb és
   * <var>$insertData2</var> legyen értékeket tartalmazó sorszámozott tömb
   * 
   * @param string $tableName
   * @param array $insertData
   * @param array $insertData2
   * @return boolean
   */
  public function insert($tableName, $insertData = array(), $insertData2 = false) {
    $query = "INSERT INTO $tableName";
    if (!empty($insertData)) {
      if ($insertData2 !== false) {
        if (count($insertData) != count($insertData2)) {
          return false;
        }
        for ($i = 0; $i < count($insertData); $i++) {
          $this->_data[$insertData[$i]] = $insertData2[$i];
        }
      } else {
        $this->_data = $insertData;
      }
    } else {
      echo 'Nincs insertData';
      return false;
    }

    $this->query($query);

    if ($this->_mysql->affected_rows > 0)
      return $this->_mysql->insert_id;

    return false;
  }

  /**
   * Befejező utasítás
   * SQL UPDATE utasítást állít össze és futtat le az előkészítő fv-ekkel összedolgozva.
   * A lekérdezés <var>$tableName</var> táblában
   * <var>$tableData</var> [és <var>$tableData2</var>] adatokat UPDATE-li, ahol
   * <var>$tableData</var> legyen mezőnév(kulcs) => érték párosításban összeállított tömb
   *  vagy
   * <var>$tableData</var>  legyen mezőneveket tartalmazó sorszámozott tömb és
   * <var>$tableData2</var> legyen értékeket tartalmazó sorszámozott tömb
   * 
   * @param string $tableName
   * @param array $insertData
   * @param array $insertData2
   * @return boolean
   */
  public function update($tableName, $tableData, $tableData2 = false) {
    $query = "UPDATE $tableName SET";

    if (!empty($tableData)) {
      if ($tableData2 !== false) {
        if (count($tableData) != count($tableData2)) {
          return false;
        }
        for ($i = 0; $i < count($tableData); $i++) {
          $this->_data[$tableData[$i]] = $tableData2[$i];
        }
      } else {
        $this->_data = $tableData;
      }

      $this->query($query);
    } else {
      return false;
    }

    if ($this->_mysql->affected_rows > 0)
      return true;

    return false;
  }

  /**
   * Befejező utasítás
   * SQL DELETE utasítást állít össze és futtat le az előkészítő fv-ekkel összedolgozva.
   * A lekérdezés <var>$tableName</var> táblából törli az addWhere fv feltételekkel megadott eleme(ke)t
   * 
   * @param string $tableName
   * @return boolean
   */
  public function delete($tableName) {
    $query = "DELETE FROM $tableName";

    if (!empty($this->_where)) {
      //$q = $this->_buildQuery();
      $this->query($query);
    } else {
      return false;
    }

    if ($this->_mysql->affected_rows > 0)
      return true;

    return false;
  }

  /**
   * Előkészítő fv
   * WHERE feltételt fűz a később lekérdezéshez a következő módon:
   * <var>$whereProp</var> mezőnév <var>$rel</var>('=','<','>') <var>$whereValue</var> érték
   * 
   * Különböző fv hívások AND-el lesznek összefűzve
   * 
   * @param type string $whereProp
   * @param type mixed $whereValue
   */
  public function addWhere($whereProp, $whereValue, $rel = '=') {
    $this->_where[$whereProp] = array(
        'value' => $whereValue,
        'rel' => $rel);
  }

  public function getQueryString() {
    return $this->_query;
  }

  public function affected_rows() {
    return $this->_mysql->affected_rows;
  }

  public function last_insert_id() {
    return $this->_mysql->insert_id;
  }

  protected function _makeWhere() {
    $w = '';
    if (!empty($this->_where)) {
      if (strpos($this->_query, 'WHERE') === false) {
        $w .= 'WHERE ';
      } else {
        $w .= ' AND ';
      }
      $i = 1;
      foreach ($this->_where as $key => $value) {
        $w .= $key . ' ' . $value['rel'] . ' ' . $value['value'];
        if ($i < count($this->_where)) {
          $w .= ' AND ';
        }
        $i++;
      }
    }

    return $w;
  }

  protected function _makeDataForUpdate() {
    $d = '';
    if (!empty($this->_data)) {
      $i = 1;
      foreach ($this->_data as $key => $value) {
        $d .= $key . ' = ' . $value;
        if ($i < count($this->_data)) {
          $d .= ', ';
        }
        $i++;
      }
    }

    return $d;
  }

  protected function _makeOrderBy() {
    $d = '';
    if (!empty($this->_orderBy)) {
      $d .= ' ORDER BY ';
      $d .= implode(',', $this->_orderBy);
    }

    return $d;
  }

  protected function _makeDataForInsert() {
    $d = '';
    if (!empty($this->_data)) {
      $props = array_keys($this->_data);
      $d .= ' ';
      $d .= '(' . implode(',', $props) . ') ';
      $d .= 'VALUES (' . implode(',', $this->_data) . ')';
    } else {
      $d = ' () VALUES ()';
    }

    return $d;
  }

  protected function _buildQuery() {
    if ($this->_setQuery_type()) {
      $q = ' ';
      $limit = '';
      if ($this->_limit !== false && $this->_offset !== false) {
        $limit .= sprintf(' LIMIT %d,%d', $this->_offset, $this->_limit);
      }
      $where = $this->_makeWhere();
      $orderBy = $this->_makeOrderBy();
      switch ($this->_type) {
        case 'SELECT':
          $q .= $where . $orderBy . $limit;
          break;
        case 'UPDATE':
          $datas = $this->_makeDataForUpdate();
          $q .= $datas . ' ' . $where;
          break;
        case 'INSERT':
          $datas = $this->_makeDataForInsert();
          $q .= $datas; // . ' ' . $where;
          break;
        case 'DELETE':
          $q .= $where;
          break;
      }

      $this->_query .= $q;
      return true;
    }
    return false;
  }

  protected function _setQuery_type() {
    switch (true) {
      case (strpos($this->_query, 'SELECT') === 0):
        $this->_type = 'SELECT';
        break;
      case (strpos($this->_query, 'DELETE') === 0):
        $this->_type = 'DELETE';
        break;
      case (strpos($this->_query, 'INSERT') === 0):
        $this->_type = 'INSERT';
        break;
      case (strpos($this->_query, 'UPDATE') === 0):
        $this->_type = 'UPDATE';
        break;
      default:
        $this->_type = '';
        return false;
        break;
    }
    return true;
  }

  public function __destruct() {
    $this->_mysql->close();
  }

}
