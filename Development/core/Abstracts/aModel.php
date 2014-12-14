<?php

/**
 * Description of aModel
 *
 * @author cycool89
 */
abstract class aModel extends aClass {

  /** @var iDatabase */
  protected $db = null;
  protected $table = '';
  protected $prefix = '';
  protected $fields = array();  //Táblában levő mezők
  protected $id_field = '';

  function __construct() {
    //parent::__construct();
    $this->db = AE()->getDatabase();
    if ($this->table == '' || $this->prefix == '') {
      $c = new ReflectionClass($this);
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
      $this->pair($toField, $this->db->qstr($surl));
      $this->updateItem($itemId);
      return $surl;
    }
  }

  final public function repairShortUrl() {
    $items = $this->getItems();
    echo d($items);
    foreach ($items as $i => $item) {
      $this->_genShortUrl($item[$this->id_field]);
    }
    echo d($items);
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
    foreach ($this->fields as $key => $value) {
      if (array_key_exists('Value', $value)) {
        $Datas[$key] = $value['Value'];
        unset($this->fields[$key]['Value']);
      }
    }
    $id = $this->db->insert($this->table, $Datas);
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
    foreach ($this->fields as $key => $value) {
      if (array_key_exists('Value', $value)) {
        $Datas[$key] = $value['Value'];
        unset($this->fields[$key]['Value']);
      }
    }
    $this->db->addWhere($this->id_field, $id);
    $success = $this->db->update($this->table, $Datas);
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
  final function pair($field, $value, $addPrefix = true) {
    $f = ($addPrefix) ? $this->prefix . $field : $field;
    if (!array_key_exists($f, $this->fields)) {
      $this->_getFields();
      if (array_key_exists($f, $this->fields)) {
        return $this->pair($field, $value, $addPrefix);
      }
    } else {
      $this->fields[$f]['Value'] = $value;
      return true;
    }
    return false;
  }

}
