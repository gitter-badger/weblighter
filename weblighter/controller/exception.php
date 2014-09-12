<?php

class Controller_Exception {
  function __construct($message) {
    $this->content = '<h1>Error</h1><h2>'.$message.'</h2>';
  }
  
  function display() {
    return $this->content;
  }
  
  private $content;
}
