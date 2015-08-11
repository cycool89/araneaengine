<?php

namespace aecore;

/**
 * Description of aModel
 *
 * @author cycool89
 */
abstract class AModel extends AClass {

  protected $pairedValues = array();

  /** @var \Doctrine\DBAL\Connection */
  protected $db = null;

  /** @var \Doctrine\DBAL\Query\QueryBuilder */
  protected $qb = null;

  /** @var \Doctrine\DBAL\Schema\AbstractSchemaManager */
  protected $sm = null;

  /** @var \Doctrine\DBAL\Schema\Table */
  protected $tableObj = null;
  protected $table = '';
  protected $prefix = '';
  protected $fields = array();  //Táblában levő mezők
  protected $id_field = '';

  function __construct() {
    $this->db = AE()->getDatabase()->getConnection();
    $this->qb = $this->db->createQueryBuilder();
    $this->sm = $this->db->getSchemaManager();

    if ($this->table == '' || $this->prefix == '') {
      $c = new \ReflectionClass($this);
      $file = AE_BASE_PATH . str_replace(AE_BASE_DIR, '', $c->getFileName());
      Log::write("Nincs definiálva \$table vagy \$prefix a modelben!\n\t\t\t" . $file, true, true);
    }
    $this->table = AE_DBPREFIX . $this->table;

    if (!$this->sm->tablesExist(array($this->table))) {
      $schema = $this->sm->createSchema();
      $myTable = $schema->createTable($this->table);
      $myTable->addColumn($this->prefix . "id", "integer", array(
        "unsigned" => true,
        "autoincrement" => true,
        "customSchemaOptions" => array(
          'charset' => 'utf8',
          'collate' => 'utf8_general_ci'
        )
              )
      );
      $myTable->setPrimaryKey(array($this->prefix . "id"));

      $array = $schema->toSql($this->db->getDatabasePlatform());
      $this->db->executeQuery(array_pop($array));
    }

    $this->_getFields();
  }

  abstract protected function install();

  final public function getFields() {
    $this->_getFields();
    $ret = new \stdClass;
    foreach ($this->fields as $key => $value) {
      $ret->$key = new \stdClass();
      $ret->$key->name = $key;
      $ret->$key->type = $value->getType()->getName();
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
    $this->tableObj = $this->sm->createSchema()->getTable($this->table);
    $this->fields = $this->tableObj->getColumns();
    $this->id_field = $this->prefix . 'id';
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

      $or = $this->qb->expr()->orX();
      $or->add($this->qb->expr()->like($this->prefix . $toField, $this->qb->createNamedParameter($surl, \PDO::PARAM_STR)));
      $or->add($this->qb->expr()->like($this->prefix . $toField, $this->qb->createNamedParameter($surl . '-[0-9]', \PDO::PARAM_STR)));
      $or->add($this->qb->expr()->like($this->prefix . $toField, $this->qb->createNamedParameter($surl . '-[0-9][0-9]', \PDO::PARAM_STR)));

      $not = $this->qb->expr()->neq($this->id_field, $this->qb->createNamedParameter($itemId));

      $felt = $this->qb->expr()->andX();
      $felt->add($or);
      $felt->add($not);

      $this->qb->select("count({$this->id_field})")
              ->from($this->table)
              ->where($felt);

      $darab = $this->qb->getOne($this->qb->execute());

      /* $darab = $this->qb->getOne(
        $this->db->executeQuery("SELECT count({$this->id_field}) as db "
        . "FROM {$this->table} "
        . "WHERE ({$this->prefix}$toField LIKE '$surl' "
        . "OR {$this->prefix}$toField LIKE '{$surl}-[0-9]' "
        . "OR {$this->prefix}$toField LIKE '{$surl}-[0-9][0-9]') "
        . "AND {$this->id_field} <> {$itemId}")
        ); */

      if ($darab > 0) {
        $surl .= '-' . $itemId;
      }
      $this->pair($toField, $surl);

      $felt = $this->qb->expr()->eq($this->id_field, $this->qb->createNamedParameter($itemId));

      $this->qb->update($this->table)
              ->set($this->prefix . $toField, $this->qb->createNamedParameter($surl, \PDO::PARAM_STR))
              ->where($felt)
              ->execute();

      $this->pairedValues = array();
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
   * 
   * @param mixed $array
   */
  final function delItems(array $array) {
    $felt = $this->qb->expr()->in($this->id_field, array_map('intval', $array));

    $this->qb->delete($this->table)
            ->where($felt)
            -execute();
  }

  /**
   * Visszaadja a tábla összes elemét
   * 
   * @return array
   */
  final function getItems() {
    return $this->qb->getArray(
                    $this->qb->select('*')
                            ->from($this->table)
                            ->execute()
    );
  }

  /**
   * Visszaadja az <var>$id</var> id-jű elemet a táblából
   * 
   * @return array
   */
  final function getItem($id) {
    $felt = $this->qb->expr()->eq($this->id_field, $this->qb->createNamedParameter($id));
    $this->qb->select('*')
            ->from($this->table)
            ->where($felt);
    return $this->qb->getRow($this->qb->execute());
  }

  /**
   * Törli az <var>$id</var> paraméterben megadott id-jű sort.
   * 
   * @param integer $id
   * @return integer
   */
  final function delItem($id) {
    $felt = $this->qb->expr()->eq($this->id_field, $this->qb->createNamedParameter($id));
    return $this->qb->delete($this->table)->where($felt)->execute();
  }

  /**
   * Hozzáad egy sort a táblához, a pair() előkészítő fv-ben megadott adatokkal.
   * 
   * @return integer Létrehozott elem ID-je
   */
  final function addItem() {
    $this->qb->insert($this->table)/* ->values($this->pairedValues) */;
    foreach ($this->pairedValues as $field => $datas) {
      $this->qb->set($field, $datas['param']);
      $this->qb->setParameter($datas['param'], $datas['value'], $this->_getPDOParamType($field));
      $this->pairedValues[$field] = $datas['param'];
    }
    $this->qb->values($this->pairedValues);
    $this->qb->execute();
    $id = $this->db->lastInsertId();
    $this->pairedValues = array();
    if ($id !== false) {
      $this->_genShortUrl($id);
    } else {
      Log::write("addItem() sikertelen.", true, false, 1);
    }
    return $id;
  }

  /**
   * Frissít egy sort a táblában, a pair() előkészítő fv-ben megadott adatokkal.
   * 
   * @return bool
   */
  final function updateItem($id) {
    $success = $this->qb->update($this->table);
    foreach ($this->pairedValues as $field => $datas) {
      $this->qb->set($field, $this->qb->createNamedParameter($datas['value'], $this->_getPDOParamType($field), $datas['param']));
    }
    $felt = $this->qb->expr()->eq($this->id_field, $this->qb->createNamedParameter($id));
    $this->qb->where($felt);

    $this->qb->execute();
    $this->pairedValues = array();
    if ($success) {
      $this->_genShortUrl($id);
    } else {
      Log::write("updateItem() sikertelen.", true, false, 1);
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
      //$value = $this->_correctize($f, $value);
      //$this->pairedValues[$f] = $value;
      $this->pairedValues[$f] = array(
        'param' => ':' . $f,
        'type' => $this->fields[$f]->getType()->getName(),
        'value' => $value
      );
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
    $ftype = $this->fields[$field]->getType()->getName();
    $v = $value;
    switch (true) {
      case (strpos($ftype, 'int') !== false):
      case (strpos($ftype, 'decimal') !== false):
      case (strpos($ftype, 'integer') !== false):
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
      case (strpos($ftype, 'string') !== false):
        $v = AEstr()->qstr($v);
        break;
    }
    return $v;
  }

  private function _getPDOParamType($field) {
    $ftype = $this->fields[$field]->getType()->getName();
    switch (true) {
      case (strpos($ftype, 'int') !== false):
      case (strpos($ftype, 'decimal') !== false):
      case (strpos($ftype, 'integer') !== false):
        $v = \PDO::PARAM_INT;
        break;
      case (strpos($ftype, 'float') !== false):
      case (strpos($ftype, 'double') !== false):
      case (strpos($ftype, 'real') !== false):
        $v = null;
        break;
      case (strpos($ftype, 'char') !== false):
      case (strpos($ftype, 'text') !== false):
      case (strpos($ftype, 'blob') !== false):
      case (strpos($ftype, 'string') !== false):
        $v = \PDO::PARAM_STR;
        break;
    }
    return $v;
  }

}
