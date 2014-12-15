<?php
namespace core;
/**
 * Description of aFormController
 *
 * @author cycool89
 */
abstract class aFormController extends aController {

  protected $modify = false;
  protected $values = array();
  protected $errors = array();

  public function __construct() {
    //parent::__construct();
    if (!is_null(Request::GET('id'))) {
      $this->setModify(true);
      AE()->getApplication()->view->assign('modify', true);
      AE()->getApplication()->view->assign('id', Request::GET('id'));
    }
  }

  final public function getModify() {
    return $this->modify;
  }

  final public function setModify($modify) {
    $this->modify = $modify;
  }

  final public function getValues() {
    return $this->values;
  }

  final public function getErrors() {
    return $this->errors;
  }

  final public function setValues(array $values) {
    $this->values = $values;
  }

  final public function setErrors(array $errors) {
    $this->errors = $errors;
  }

  /**
   * Legenerál egy szövegmezőhöz szükséges adatokat.
   * A nézetben elérhető változók:
   * 
   * <var>$name</var>_name
   * <var>$name</var>_value
   * <var>$name</var>_id
   * 
   * Használat nézetben pl.:
   * <input type="text" name="<{$name_name}>" value="<{$name_value}>" />
   * 
   * @param string $name - Mező neve
   * @param string $value - Mező alapértelmezett értéke
   * @param string $modifyValue - Mező értéke POST után
   */
  public function textField($name = false, $value = false, $modifyValue = null) {
    $this->view->assign($name . '_name', get_class($this) . '[' . $name . ']');
    $this->view->assign($name . '_id', $name);
    if (!is_null($modifyValue)) {
      $this->view->assign($name . '_value', $modifyValue);
    } else {
      $this->view->assign($name . '_value', $value);
    }
  }

  public function checkField($name = false, $value = false, $modifyValue = null) {
    $this->view->assign($name . '_name', get_class($this) . '[' . $name . ']');
    $this->view->assign($name . '_id', $name);
    if (!is_null($modifyValue)) {
      $checked = $modifyValue ? 'checked="checked"' : '';
      $this->view->assign($name . '_value', $modifyValue);
    } else {
      $checked = $value ? 'checked="checked"' : '';
      $this->view->assign($name . '_value', $value);
    }
    $this->view->assign($name . '_checked', $checked);
  }

  public function selectField($name = false, $values = false, $selectedValueId = null) {
    $this->view->assign($name . '_name', get_class($this) . '[' . $name . ']');
    $this->view->assign($name . '_id', $name);
    $this->view->assign($name . '_values', $values);

    $this->view->assign($name . '_selected', $selectedValueId);
  }

  abstract function checkValues();

  abstract function storeData();
}
