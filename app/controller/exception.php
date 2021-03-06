<?php

class Controller_Exception
{

  function __construct($message)
  {
    $this->message = $message;
  }

  function display()
  {

    try
    {
      $this->data['site_title'] = \Data_Config::$site_title;
      $this->data['theme'] = \Data_Config::$theme;
      $this->data['action'] = CURRENT_ACTION;
      $this->data['site_slogan'] = Data_Config::$site_slogan;
      $this->data['page_title'] = 'Erreur';
      
      $this->translator = new weblighter\Translator($_SESSION['user']['lang']);
      $this->data['t'] = $this->translator;
      
      $this->data['url_prefix'] = \Data_Config::$url_prefix;     
      $this->data['exception'] = $this->data['t']->_($this->message);

      //Default Translator or the one set in the route
      if (!empty(func_num_args()))
      {
        $lang = func_get_args()[0];
        $this->prepareTranslator($lang);
      }
      else
      {
        $this->prepareTranslator(\Data_Config::$default_lang);
      }
      $this->data['t'] = $this->translator;

      $this->content = (new weblighter\Tplparser('exception.php', $this->data))->display();
    }
    catch (Exception $e)
    {
      die('ERROR: '.$e->getMessage());
    }
    return $this->content;
  }

  function prepareTranslator($lang)
  {
    if (!empty($lang))
    {
      $this->translator = new weblighter\Translator($lang);
    }
    else
    {
      $this->translator = new weblighter\Translator(\Data_Config::$default_lang);

    }
    $this->data['t'] = $this->translator;
  }

  private $message;

}
