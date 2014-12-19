<?php

namespace core;

/**
 * Description of aModel
 *
 * @author cycool89
 */
abstract class aModel extends aClass {

  /** @var iDatabase */
  protected $pairedValues = array();
  protected $db = null;
  protected $table = '';
  protected $prefix = '';
  protected $fields = array();  //Táblában levő mezők
  protected $id_field = '';

  function __construct() {
    //parent::__construct();
    $this->db = AE()->getDatabase();
    if ($this->table == '' || $this->prefix == '') {
      $c = new \ReflectionClass($this);
      echo 'Definiáld a <b>"table" -t, "prefix"-et</b> a modelben:<br>' . $c->getFileName();
      exit();
    }
    $sql = sprintf('CREATE TABLE IF NOT EXISTS %s ('
            . '%sid INT(11) AUTO_INCREMENT PRIMARY KEY NOT NULL) '
            . 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE MYISAM', $this->table, $this->prefix);
    $this->db->execute($sql);

    $this->_getFields();
  }

  abstract protected function install();

  final public function getFields() {
    $ret = new \stdClass;
    foreach ($this->fields as $key => $value) {
      $ret->$key = new \stdClass();
      $ret->$key->name = $value['Field'];
      $ret->$key->type = $value['Type'];
      $ret->$key->comment = $value['Comment'];
    }
    return $ret;
  }

  /**
   * Lekérdezi a táblában levő mezőket és tárolja a tulajdonságaikat a $this->fields változóban
   * Lekérdezi a táblában levő id mezőt és tárolja a tulajdonságait a $this->id_field változóban
   * 
   * Ha a táblában csak id mező található, akkor lefuttatja a model install() fv-ét (ha létezik)
   * 
   */
  protected function _getFields($req = false) {
    $this->fields = $this->db->getKeyedArray(sprintf('SHOW FULL COLUMNS FROM %s', $this->table), 'Field');
    $res = $this->db->query(sprintf('SHOW FULL COLUMNS FROM %s WHERE Extra = "auto_increment"', $this->table));
    $row = $res->fetch_assoc();

    $this->id_field = $row['Field'];
    if (count($this->fields) <= 1) {
      $this->install();
      if (!$req) {
        $this->_getFields(true);
      }
    }
    return $this->fields;
  }

  /**
   * URL barát url-t készít a <var>$fromField</var> mező-ből.
   * A táblában léteznie kell $this->prefix.<var>$toField</var> mezőnek és abban tárolja.
   * Ha több egyforma van a táblában, akkor beszámozza
   * pl.:
   * Ez egy hasznos cikk címe
   * ez-egy-hasznos-cikk-cime-2
   * 
   * (megjegyzés: célszerű 'title' és 'short_url' mezőket létrehozni erre a célra, mert, akkor automatikus)
   * 
   * @param string $fromField
   * @return string
   */
  protected function _genShortUrl($itemId, $fromField = 'title', $toField = 'short_url') {
    if (isset($this->fields[$this->prefix . $toField]) && isset($this->fields[$this->prefix . $fromField])) {
      $item = $this->getItem($itemId);
      $surl = AEstr()->urlize($item[$this->prefix . $fromField]);
      $darab = $this->db->getOne(
              $this->db->execute("SELECT count({$this->id_field}) as db FROM {$this->table} WHERE ({$this->prefix}$toField LIKE '$surl' OR {$this->prefix}$toField LIKE '{$surl}-[0-9]' OR {$this->prefix}$toField LIKE '{$surl}-[0-9][0-9]') AND {$this->id_field} <> {$itemId}")
      );

      if ($darab > 0) {
        $surl .= '-' . $itemId;
      }
      $this->pair($toField, $surl);
      $this->updateItem($itemId);
      return $surl;
    }
  }

  final public function repairShortUrl() {
    $items = $this->getItems();
    foreach ($items as $i => $item) {
      $this->_genShortUrl($item[$this->id_field]);
    }
  }

  /**
   * Törli az <var>$array</var> tömbben beadott id-jű sorokat.
   * Visszatérési érték a sikeresen törölt sorok száma.
   * 
   * @param mixed $array
   * @return bool
   */
  final function delItems($array) {
    for ($i = 0; $i < count($array); $i++) {
      $this->db->addWhere($this->id_field, $array[$i]);
      if (!$this->db->delete($this->table)) {
        break;
      }
    }
    return $i + 1;
  }

  /**
   * Visszaadja a tábla összes elemét
   * 
   * @return array
   */
  final function getItems() {
    return $this->db->getArray($this->db->select('*', $this->table));
  }

  /**
   * Visszaadja az <var>$id</var> id-jű elemet a táblából
   * 
   * @return array
   */
  final function getItem($id) {
    $this->db->addWhere($this->id_field, $id);
    return $this->db->getRow($this->db->select('*', $this->table));
  }

  /**
   * Törli az <var>$id</var> paraméterben megadott id-jű sort.
   * 
   * @param integer $id
   * @return integer
   */
  final function delItem($id) {
    $this->db->addWhere($this->id_field, $id);
    return $this->db->delete($this->table);
  }

  /**
   * Hozzáad egy sort a táblához, a pair() előkészítő fv-ben megadott adatokkal.
   * 
   * @return bool
   */
  final function addItem() {
    $Datas = array();
    $id = $this->db->insert($this->table, $this->pairedValues);
    $this->pairedValues = array();
    if ($id !== false) {
      $this->_genShortUrl($id);
    }
    return $id;
  }

  /**
   * Frissít egy sort a táblában, a pair() előkészítő fv-ben megadott adatokkal.
   * 
   * @return bool
   */
  final function updateItem($id) {
    $Datas = array();
    $this->db->addWhere($this->id_field, $id);
    $success = $this->db->update($this->table, $this->pairedValues);
    $this->pairedValues = array();
    if ($success !== false) {
      $this->_genShortUrl($id);
    }
    return $success;
  }

  /**
   * Előkészítő fv.
   * Összepárosítja a mező nevet (<var>$field</var>) egy értékkel (<var>$value</var>)
   * a későbbi feldolgozáshoz.
   * 
   * <var>$addPrefix</var> paraméterben megadható, hogy a függvény hozzáadja-e a
   *  tábla prefix-ét a megadott mezőnévhez (alapértelmezetten hozzáadja)
   * 
   * Táblában NEM létező mezőhöz nem párosít adatot!
   * 
   * @param string $field
   * @param mixed $value
   * @param bool
   * @return bool
   */
  final public function pair($field, $value, $addPrefix = true) {
    $f = ($addPrefix) ? $this->prefix . $field : $field;
    $fields = array_keys($this->fields);
    if (!in_array($f, $fields)) {
      $this->_getFields();
      $fields = array_keys($this->fields);
    }
    if (in_array($f, $fields)) {
      $value = $this->_correctize($f, $value);
      $this->pairedValues[$f] = $value;
      return true;
    }
    return false;
  }

  /**
   * Előkészítő fv.
   * Összepárosítja a <var>$values</var> tömbben található mező neveket (kulcs)
   * az értékekkel, ha tudja.
   * 
   * Táblában NEM létező mezőhöz nem párosít adatot!
   * 
   * Visszaadja a hibás mezőneveket. (Siker esetén üres tömböt.)
   * 
   * @param array $values Asszociatív tömb. mezőnév=>érték formátumban
   * @return array
   */
  final public function pairAll(array $values) {
    $errors = array();
    $fields = array_keys($this->_getFields());
    foreach ($values as $field => $value) {
      $f = $field;
      if (!in_array($f, $fields)) {
        $f = $this->prefix . $field;
        if (!in_array($f, $fields)) {
          $errors[] = $field;
          continue;
        }
      }
      $this->pair($f, $value, false);
    }
    return $errors;
  }

  private function _correctize($field, $value) {
    $ftype = $this->fields[$field]['Type'];
    $v = $value;
    switch (true) {
      case (strpos($ftype, 'int') !== false):
      case (strpos($ftype, 'decimal') !== false):
        $v = intval($v);
        break;
      case (strpos($ftype, 'float') !== false):
      case (strpos($ftype, 'double') !== false):
      case (strpos($ftype, 'real') !== false):
        $v = floatval($v);
        break;
      case (strpos($ftype, 'char') !== false):
      case (strpos($ftype, 'text') !== false):
      case (strpos($ftype, 'blob') !== false):
        $v = AEstr()->qstr($v);
        break;
    }
    return $v;
  }

}
