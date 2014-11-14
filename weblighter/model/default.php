<?php

abstract class Model_Default
{
  function __construct()
  {

    if (!empty(\Data_Config::$db))
    {
      $options = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_BOTH
      );

      foreach (\Data_Config::$db as $db)
      {
        if ($db['type'] == 'mysql')
        {
          if (empty($db['host'])) { $db['host'] = 'localhost'; }
          if (empty($db['port'])) { $db['port'] = 3306; }

          $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $db['host'], $db['port'], $db['db'] );
          $this->db[$db['name']] = new PDO($dsn, $db['user'], $db['pass'], $options);

          //Only 1 db ? then index it on $this->db directly
          if (count(\Data_Config::$db) == 1)
          {
            $this->db = new PDO($dsn, $db['user'], $db['pass'], $options);
          }
        }
        elseif ($db['type'] == 'sqlite')
        {
          $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s', $db['host'], $db['port'], $db['db'] );
        }
      }
    }

    // \Data_Config::$database;

  }

  protected $db;
}
