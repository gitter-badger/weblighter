<?php

abstract class Model_Default
{
  function __construct()
  {
    $this->db = \Data_Config::$theme;

    // \Data_Config::$database;

  }

  protected $db;
}
