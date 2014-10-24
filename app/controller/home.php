<?php

class Controller_Home extends \Controller_Default {

  function display() {

    $this->data['site_slogan'] = Data_Config::$site_slogan;
    $this->data['action'] = 'home';

    /* get a custom translator here
    * not based on default_lang in config file
    * but based on lang param passed to this class
    * from the route defined in index.php of the application
    */
    $this->params = @func_get_args()[0];
    if (!empty($this->params)) {
      if (!empty($this->params['lang'])) {
        $thelang = $this->params['lang'];
      }
    }
    if (empty($thelang) && !empty($_GET['lang'])) {
      $thelang=$_GET['lang'];
    }
    if (!empty($thelang)) {
      $this->translator = new weblighter\Translator($thelang);
      $this->data['t'] = $this->translator;
    }

    $this->data['page_title'] = $this->translator->_('home');

    $this->content = (new weblighter\Tplparser('home.php', $this->data))->display();

    return $this->content;
  }

}
