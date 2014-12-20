<?php
namespace application;

class {{appName}} extends \aecore\AModule implements \aecore\IApplication {

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
