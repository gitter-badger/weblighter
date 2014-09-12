<?php

class Controller_Exception {

  function __construct($message) {
    $this->message = $message;
  }

  function display() {

    try {
      $this->data['site_title'] = \Data_Config::$site_title;
      $this->data['theme'] = \Data_Config::$theme;
      $this->data['action'] = @$_GET['action'];
      $this->data['site_slogan'] = Data_Config::$site_slogan;
      $this->data['page_title'] = 'Erreur';
      $this->data['url_prefix'] = \Data_Config::$url_prefix;
      $this->data['exception'] = $this->message;

      var_dump($this->message);
      /*$this->translator = new weblighter\Translator(\Data_Config::$default_lang);
      $this->data['t'] = $this->translator;*/

      //$this->content  = (new weblighter\Tplparser('header.php', $this->data))->display();
      $this->content = (new weblighter\Tplparser('exception.php', $this->data))->display();
      //$this->content .= (new weblighter\Tplparser('footer.php', $this->data))->display();
    }
    catch (Exception $e) {
      die('ERROR: '.$e->getMessage());
    }
    return $this->content;
  }

  private $message;

}
