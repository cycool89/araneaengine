<?php

class {{appName}} extends aModule implements iApplication {

  public function boot() {
    
  }

  public function index() {
    $this->load->view('index');
  }

  public function render() {
    foreach ($this->view->getTemplates() as $key => $value) {
      foreach ($value as $tpl) {
        $tpl->display();
      }
    }
  }

  public function afterCall($class, $method) {
    
  }

  public function beforeCall($class, $method) {
    return true;
  }

}
