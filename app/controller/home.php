<?php

class Controller_Home extends \Controller_Default
{

  function display()
  {
    $this->data['page_title'] = $this->translator->_('home');

    $this->content = (new weblighter\Tplparser('home.php', $this->data))->display();

    return $this->content;
  }

}
