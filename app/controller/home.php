<?php

class Controller_Home extends \Controller_Default {

  function display() {

    $this->data['site_slogan'] = Data_Config::$site_slogan;
    $this->data['action'] = 'home';

    $this->translator = new weblighter\Translator(\Data_Config::$default_lang);
    $this->data['t'] = $this->translator;

    $this->data['page_title'] = $this->translator->_('Home');

    //$this->content  = (new weblighter\Tplparser('header.php', $this->data))->display();
    $this->content = (new weblighter\Tplparser('home.php', $this->data))->display();
    //$this->content .= (new weblighter\Tplparser('footer.php', $this->data))->display();

    return $this->content;
  }

}
