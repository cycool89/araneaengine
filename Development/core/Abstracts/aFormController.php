<?php

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

  abstract function checkValues();

  abstract function storeData();
}
