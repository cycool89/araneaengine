<?php

namespace aecore;

class HPosition {

  public static $instance = false;
  private $db = null;
  private $table = '';
  private $prefix = '';
  private $posfield = '';

  function __construct($table, $prefix) {
    $this->table = $table;
    $this->prefix = $prefix;
    $this->posfield = $prefix . 'pos';
    $this->db = AE()->getDatabase();
    if (!$this->db->select($this->posfield, $this->table)) {
      $this->db->addColumn($this->table, $this->posfield, 'INT(11)');
    }
  }

  private function _checkPos($pos, $where, $in = 1) {
    foreach ($where as $key => $value) {
      $this->db->addWhere($key, $value);
    }
    $Max = $this->db->getOne($this->db->select('COUNT(' . $this->prefix . 'id)', $this->table));
    if ($pos > $Max + $in)
      $pos = $Max + $in;
    if ($pos < 1)
      $pos = 1;
    return $pos;
  }

  function add($id, $pos, $where = array()) {
    $pos = $this->_checkPos($pos, $where, 1);
    foreach ($where as $key => $value) {
      $this->db->addWhere($key, $value);
    }
    $sql = sprintf('UPDATE %1$s SET %2$s = %2$s + 1 WHERE %2$s >= %3$d ', $this->table, $this->posfield, $pos);
    $this->db->query($sql);

    $sql = sprintf('UPDATE %1$s SET %2$s = %4$d WHERE %3$s = %5$d ', $this->table, $this->posfield, $this->prefix . 'id', $pos, $id);
    $this->db->execute($sql);
  }

  function modify($old, $new, $where = array()) {
    if ($old - $new < 0) {
      $this->_moveRight($old, $new, $where);
    } else {
      $this->_moveLeft($old, $new, $where);
    }
  }

  private function _moveLeft($old, $new, $where) {
    $new = $this->_checkPos($new, $where, 0);
    foreach ($where as $key => $value) {
      $this->db->addWhere($key, $value);
    }
    $this->db->addWhere($this->posfield, $old);
    $id = $this->db->getOne($this->db->select($this->prefix . 'id', $this->table));

    foreach ($where as $key => $value) {
      $this->db->addWhere($key, $value);
    }
    $sql = sprintf('UPDATE %1$s SET %2$s = %2$s + 1 WHERE %2$s < %3$d AND %2$s >= %4$d', $this->table, $this->posfield, $old, $new);
    $this->db->query($sql);

    $sql = sprintf('UPDATE %1$s SET %2$s = %4$d WHERE %3$s = %5$d', $this->table, $this->posfield, $this->prefix . 'id', $new, $id);
    $this->db->execute($sql);
  }

  private function _moveRight($old, $new, $where) {
    $new = $this->_checkPos($new, $where, 0);
    foreach ($where as $key => $value) {
      $this->db->addWhere($key, $value);
    }
    $this->db->addWhere($this->posfield, $old);
    $id = $this->db->getOne($this->db->select($this->prefix . 'id', $this->table));

    foreach ($where as $key => $value) {
      $this->db->addWhere($key, $value);
    }
    $sql = sprintf('UPDATE %1$s SET %2$s = %2$s - 1 WHERE %2$s > %3$d AND %2$s <= %4$d', $this->table, $this->posfield, $old, $new);
    $this->db->query($sql);

    $sql = sprintf('UPDATE %1$s SET %2$s = %4$d WHERE %3$s = %5$d', $this->table, $this->posfield, $this->prefix . 'id', $new, $id);
    $this->db->execute($sql);
  }

  function delete($id, $where = array()) {
    $this->db->addWhere($this->prefix . 'id', $id);
    $pos = $this->db->getOne($this->db->select($this->posfield, $this->table));
    foreach ($where as $key => $value) {
      $this->db->addWhere($key, $value);
    }
    $sql = sprintf('UPDATE %1$s SET %2$s = %2$s - 1 WHERE %2$s >= %3$d', $this->table, $this->posfield, $pos);
    $this->db->query($sql);
  }

  function reOrder($where = array()) {
    foreach ($where as $key => $value) {
      $this->db->addWhere($key, $value);
    }
    $this->db->orderBy($this->posfield);
    $this->db->orderBy($this->prefix . 'id');
    if ($items = $this->db->getArray($this->db->select($this->prefix . 'id', $this->table))) {
      for ($i = 1; $i <= count($items); $i++) {
        $this->db->addWhere($this->prefix . 'id', $items[$i - 1][$this->prefix . 'id']);
        $this->db->update($this->table, array($this->posfield => $i));
      }
    }
  }

}
